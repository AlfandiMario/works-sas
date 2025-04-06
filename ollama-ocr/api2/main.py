from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import base64
import io
from PIL import Image
from ocr_processor import OCRProcessor
import json

app = FastAPI(
    title="KTP OCR API",
    description="API for extracting information from Indonesian ID Cards (KTP) using Llama Vision",
    version="1.0.0"
)

# CORS middleware configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize OCR processor
processor = OCRProcessor(model_name="llama3.2-vision:11b")

@app.post("/ocr/file")
async def ocr_from_file(file: UploadFile = File(...)):
    """Process KTP image file and extract fields"""
    try:
        # Validate file type
        if file.content_type not in ["image/jpeg", "image/png"]:
            raise HTTPException(400, "Invalid file type")
        
        # Read and save temp image
        image = Image.open(io.BytesIO(await file.read()))
        temp_path = "temp.jpg"
        image.save(temp_path)
        
        # Process image
        result = processor.process_ktp(temp_path)
        return result

    except Exception as e:
        raise HTTPException(500, str(e))

@app.post("/ocr/base64")
async def ocr_from_base64(request: dict):
    """Process base64 encoded KTP image and extract fields"""
    try:
        # Decode base64 image
        image_data = base64.b64decode(request["image"])
        image = Image.open(io.BytesIO(image_data))
        temp_path = "temp.jpg"
        image.save(temp_path)
        
        # Process image
        result = processor.process_ktp(temp_path)
        return result

    except Exception as e:
        raise HTTPException(500, str(e))

@app.post("/utils/to_base64")
async def convert_to_base64(file: UploadFile = File(...)):
    """Convert image file to base64 string"""
    try:
        # Validate file type
        if file.content_type not in ["image/jpeg", "image/png"]:
            raise HTTPException(400, "Invalid file type")
        
        # Convert to base64
        image_bytes = await file.read()
        base64_string = base64.b64encode(image_bytes).decode()
        return {"base64_image": base64_string}

    except Exception as e:
        raise HTTPException(500, str(e))

@app.post("/ocr/raw")
async def ocr_raw(file: UploadFile = File(...)):
    """Get raw OCR output from model"""
    try:
        # Validate file type
        if file.content_type not in ["image/jpeg", "image/png"]:
            raise HTTPException(400, "Invalid file type")
        
        # Read and save temp image
        image = Image.open(io.BytesIO(await file.read()))
        temp_path = "temp.jpg"
        image.save(temp_path)
        
        # Process image with raw output
        result = processor.process_raw(temp_path)
        return {"raw_output": result}

    except Exception as e:
        raise HTTPException(500, str(e))