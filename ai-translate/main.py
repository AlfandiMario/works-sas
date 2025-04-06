from typing import Optional

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from transformers import MarianTokenizer, MarianMTModel, MarianConfig
import torch
from torch.nn.functional import softmax
from typing import Optional, Dict, Any
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM, pipeline
from transformers import T5Tokenizer, T5ForConditionalGeneration


# Initialize the FastAPI app
app = FastAPI(title="Sas::Indonesian to English API", version="1.0")

# Define model and tokenizer
model_name = "Helsinki-NLP/opus-mt-id-en"
tokenizer = MarianTokenizer.from_pretrained(model_name)
model = MarianMTModel.from_pretrained(model_name)

model_token_path = "model/tpl_20241120_061638"
try:
    tokenizer_8 = AutoTokenizer.from_pretrained(model_token_path)
    model_8 = AutoModelForSeq2SeqLM.from_pretrained(model_token_path)
except Exception as e:
    print(f"Error loading model: {e}")
    raise

# Define input and output schemas
class TranslationRequest(BaseModel):
    input: str

class TranslationResponse(BaseModel):
    status: str
    output: Optional[str] = None
    confidence: Optional[float] = None
    message: Optional[str] = None

class ArrayTranslationRequest(BaseModel):
    input: list[str]

class ArrayTranslationResponse(BaseModel):
    status: str
    output: Optional[Dict[int, Dict[str, Any]]] = None
    message: Optional[str] = None


@app.post("/translate", response_model=TranslationResponse)
async def translate(request: TranslationRequest):
    try:
        inputs = tokenizer.encode(request.input, return_tensors="pt")
        outputs = model.generate(inputs, output_scores=True, return_dict_in_generate=True)

        translation_ids = outputs.sequences[0]
        output = tokenizer.decode(translation_ids, skip_special_tokens=True)

        scores = outputs.scores
        token_confidences = []

        for score in scores:
            probs = softmax(score, dim=-1)
            # Take the mean of probabilities for each token position
            top_prob = torch.mean(torch.max(probs, dim=-1)[0])
            token_confidences.append(top_prob.item())

        confidence = sum(token_confidences) / len(token_confidences) if token_confidences else 0.0

        return TranslationResponse(status="OK", output=output, confidence=confidence)

    except Exception as e:
        return TranslationResponse(status="ERR", message=str(e))

@app.post("/translate-array", response_model=ArrayTranslationResponse)
async def translate_array(request: ArrayTranslationRequest):
    try:
        result: Dict[int, Dict[str, Any]] = {}
        
        for idx, text in enumerate(request.input):
            # Skip translation if input is numeric
            if text.replace(".", "").isdigit():
                result[idx] = {
                    "original": text,
                    "translated": text,
                    "confidence": 1.0
                }
                continue

            # Translate text
            inputs = tokenizer.encode(text, return_tensors="pt")
            outputs = model.generate(inputs, output_scores=True, return_dict_in_generate=True)
            
            translation_ids = outputs.sequences[0]
            translated = tokenizer.decode(translation_ids, skip_special_tokens=True)
            
            # Calculate confidence
            scores = outputs.scores
            token_confidences = []
            for score in scores:
                probs = softmax(score, dim=-1)
                top_prob = torch.mean(torch.max(probs, dim=-1)[0])
                token_confidences.append(top_prob.item())
            
            confidence = sum(token_confidences) / len(token_confidences) if token_confidences else 0.0
            
            result[idx] = {
                "original": text,
                "translated": translated,
                "confidence": confidence
            }
            
        return ArrayTranslationResponse(status="OK", output=result)

    except Exception as e:
        return ArrayTranslationResponse(status="ERR", message=str(e))

@app.post("/nonlab", response_model=TranslationResponse)
async def nonlab_bdg(request: TranslationRequest):
    try:
        inputs = tokenizer_8.encode(request.input, return_tensors="pt")
        outputs = model_8.generate(inputs, output_scores=True, return_dict_in_generate=True)

        translation_ids = outputs.sequences[0]
        output = tokenizer_8.decode(translation_ids, skip_special_tokens=True)

        scores = outputs.scores
        token_confidences = []

        for score in scores:
            probs = softmax(score, dim=-1)
            # Take the mean of probabilities for each token position
            top_prob = torch.mean(torch.max(probs, dim=-1)[0])
            token_confidences.append(top_prob.item())

        confidence = sum(token_confidences) / len(token_confidences) if token_confidences else 0.0

        return TranslationResponse(status="OK", output=output, confidence=confidence)

    except Exception as e:
        return TranslationResponse(status="ERR", message=str(e))

# To run, use: uvicorn filename:app --reload

# Run with Uvicorn server (if running standalone)
if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="0.0.0.0", port=4321)
