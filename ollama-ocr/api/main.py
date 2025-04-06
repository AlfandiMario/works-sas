from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.responses import JSONResponse
import requests
from PIL import Image
import base64
import cv2
import io
import jsonpickle
from ocr_processor import OCRProcessor
import tempfile


app = FastAPI()

# Configuration
OLLAMA_BASE_URL = "http://localhost:11434/api/generate"
MODEL_NAME = "llama3.2-vision:11b"

processor = OCRProcessor(model_name=MODEL_NAME, base_url=OLLAMA_BASE_URL, max_workers=max_workers)

# Endpoint 1: Receive image file and return key-value pairs
@app.post("/ocr/extract_fields/")
async def extract_fields(file: UploadFile = File(...)):
    try:
        # Read image content
        image_content = await file.read()
        
        # Write the bytes to a temporary file
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
            temp_file.write(image_content)
            temp_image_path = temp_file.name
        
        # Process image and extract fields
        fields = process_image(temp_image_path, base64=False)
        
        # Clean up the temporary file
        os.remove(temp_image_path)
        
        return JSONResponse(content=fields)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# Endpoint 2: Receive base64 image and return key-value pairs
@app.post("/ocr/extract_fields_base64/")
async def extract_fields_base64(base64_image: str):
    try:
        # Decode base64 image
        image_data = base64.b64decode(base64_image)
        # Process image and extract fields
        fields = processor.process_image(image_data, base64=True)
        return JSONResponse(content=fields)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# Endpoint 3: Convert image file to base64
@app.post("/ocr/convert_to_base64/")
async def convert_to_base64(file: UploadFile = File(...)):
    try:
        # Read image content
        image_content = await file.read()
        # Encode to base64
        base64_image = base64.b64encode(image_content).decode('utf-8')
        return JSONResponse(content={"base64_image": base64_image})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# Endpoint 4: Receive image file and return raw OCR output
@app.post("/ocr/raw_output/")
async def raw_output(file: UploadFile = File(...)):
    try:
        # Read image content
        image_content = await file.read()
        # Process image and get raw OCR output
        raw_output = processor.process_image(image_content, preprocess=False, base64=False)
        return JSONResponse(content={"raw_output": raw_output})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


def main():
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8080)