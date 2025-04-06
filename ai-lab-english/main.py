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

model_google = "andreanstev/t5_id_to_en"
tokenizer_g = AutoTokenizer.from_pretrained(model_google)
model_g = AutoModelForSeq2SeqLM.from_pretrained(model_google)

model_3 = "muvazana/flan-t5-base-opus-en-id-id-en"
try:
    tokenizer_3 = AutoTokenizer.from_pretrained(model_3)
    model_3 = T5ForConditionalGeneration.from_pretrained(model_3)
except Exception as e:
    print(f"Error loading model: {e}")
    raise

# Memakai Model Sendiri
model_path = "model/model_20241115_170535"
tokenizer_path = "model/model_20241115_170535_tokenizer"
try:
    tokenizer_4 = AutoTokenizer.from_pretrained(tokenizer_path)
    model_4 = AutoModelForSeq2SeqLM.from_pretrained(model_path)
except Exception as e:
    print(f"Error loading model: {e}")
    raise

model_path = "model/using_trainer"
tokenizer_path = "model/using_trainer"
try:
    tokenizer_5 = AutoTokenizer.from_pretrained(tokenizer_path)
    model_5 = AutoModelForSeq2SeqLM.from_pretrained(model_path)
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

@app.post("/translate-v3", response_model=TranslationResponse)
async def translate_v3(request: TranslationRequest):
    try:
        # Format input with prefix
        input_text = f"translate English to Indonesia: {request.input}"
        
        # Tokenize input
        inputs = tokenizer_3(
            input_text, 
            return_tensors="pt", 
            padding=True, 
            truncation=True, 
            max_length=512
        )
        
        # Generate translation
        outputs = model_3.generate(
            inputs.input_ids,
            output_scores=True,
            return_dict_in_generate=True,
            max_length=512,
            num_beams=4,
            early_stopping=True
        )

        # Decode translation
        translated_text = tokenizer_3.decode(outputs.sequences[0], skip_special_tokens=True)

        # Calculate confidence
        scores = outputs.scores
        token_confidences = []

        for score in scores:
            probs = softmax(score, dim=-1)
            top_prob = torch.mean(torch.max(probs, dim=-1)[0])
            token_confidences.append(top_prob.item())

        confidence = sum(token_confidences) / len(token_confidences) if token_confidences else 0.0

        return TranslationResponse(
            status="OK", 
            output=translated_text, 
            confidence=confidence
        )

    except Exception as e:
        return TranslationResponse(status="ERR", message=str(e))

@app.post("/translate-v4", response_model=TranslationResponse)
async def translate_v4(request: TranslationRequest):
    try:
        inputs = tokenizer_4.encode(request.input, return_tensors="pt")
        outputs = model_4.generate(inputs, output_scores=True, return_dict_in_generate=True)

        translation_ids = outputs.sequences[0]
        output = tokenizer_4.decode(translation_ids, skip_special_tokens=True)

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

@app.post("/translate-v5", response_model=TranslationResponse)
async def translate_v5(request: TranslationRequest):
    try:
        inputs = tokenizer_5.encode(request.input, return_tensors="pt")
        outputs = model_5.generate(inputs, output_scores=True, return_dict_in_generate=True)

        translation_ids = outputs.sequences[0]
        output = tokenizer_5.decode(translation_ids, skip_special_tokens=True)

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


@app.post("/translate-google", response_model=TranslationResponse)
async def translate_google(request: TranslationRequest):
    try:
        
        inputs = tokenizer_g(
            f"ranslate Indonesian to English: {request.input}",
            return_tensors="pt",
            max_length=512,
            truncation=True,
            padding=True
        )
        
        outputs = model_g.generate(
            **inputs, 
            max_length=512, 
            num_beams=4,  # Add beam search for better results
            output_scores=True, 
            return_dict_in_generate=True,
            early_stopping=True,
            return_legacy_cache=True,
        )
        
        translated_text = tokenizer_g.decode(outputs.sequences[0], skip_special_tokens=True)
        
        token_confidences = []
        for score in outputs.scores:
            probs = softmax(score, dim=-1)
            top_prob = torch.mean(torch.max(probs, dim=-1)[0])
            token_confidences.append(top_prob.item())
    
        confidence = sum(token_confidences) / len(token_confidences) if token_confidences else 0.0
        
        return TranslationResponse(
            status="OK", 
            output=translated_text,
            confidence=confidence
        )
    except Exception as e:
        return TranslationResponse(status="ERR", message=str(e))


@app.post("/translate-g2")
async def translate_text(request: TranslationRequest):
    try:
        # Prepare input for the model
        inputs = tokenizer_g(
            f"translate Indonesian to English: {request.input}",
            return_tensors="pt",
            max_length=512,
            truncation=True,
        )
        
        # Generate translation
        output = model_g.generate(**inputs, max_length=512)
        translated_text = tokenizer_g.decode(output[0], skip_special_tokens=True)
        
        return {"translated_text": translated_text}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Translation failed: {str(e)}")


@app.post("/translate-array-google", response_model=ArrayTranslationResponse)
async def translate_array_google(request: ArrayTranslationRequest):
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

            # Prepare input for the model
            inputs = tokenizer_g(
                f"translate Indonesian to English: {text}",
                return_tensors="pt",
                max_length=512,
                truncation=True,
                padding=True
            )
            
            outputs = model_g.generate(
                **inputs, 
                max_length=512,
                return_dict_in_generate=True,
                output_scores=True,
                num_beams=4,  # Add beam search
                early_stopping=True
            )
            translated = tokenizer_g.decode(outputs.sequences[0], skip_special_tokens=True)

           # Calculate confidence using scores
            token_confidences = []
            for score in outputs.scores:
                probs = torch.nn.functional.softmax(score, dim=-1)
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

# To run, use: uvicorn filename:app --reload

# Run with Uvicorn server (if running standalone)
if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="0.0.0.0", port=4321)
