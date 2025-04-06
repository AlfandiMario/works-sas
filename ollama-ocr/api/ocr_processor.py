import json
from typing import Dict, Any, List, Union
import os
import base64
import requests
from tqdm import tqdm
import concurrent.futures
from pathlib import Path
import cv2
from pdf2image import convert_from_path

class OCRProcessor:
    def __init__(self, model_name: str = "llama3.2-vision:11b", 
                 base_url: str = "http://localhost:11434/api/generate",
                 max_workers: int = 1):
        
        self.model_name = model_name
        self.base_url = base_url
        self.max_workers = max_workers

    def _encode_image(self, image_path: str) -> str:
        """Convert image to base64 string"""
        with open(image_path, "rb") as image_file:
            return base64.b64encode(image_file.read()).decode("utf-8")

    def _decode_image(self, base64_string: str, output_path: str) -> None:
        """Convert base64 string to image and save to output path"""
        image_data = base64.b64decode(base64_string)
        with open(output_path, "wb") as output_file:
            output_file.write(image_data)
            return output_path

    def _preprocess_image(self, image_path: str) -> str:
        """
        Preprocess image before OCR:
        - Convert PDF to image if needed
        - Auto-rotate
        - Enhance contrast
        - Reduce noise
        """
        # Handle PDF files
        if image_path.lower().endswith('.pdf'):
            pages = convert_from_path(image_path)
            if not pages:
                raise ValueError("Could not convert PDF to image")
            # Save first page as temporary image
            temp_path = f"{image_path}_temp.jpg"
            pages[0].save(temp_path, 'JPEG')
            image_path = temp_path

        # Read image
        image = cv2.imread(image_path)
        if image is None:
            raise ValueError(f"Could not read image at {image_path}")

        # Convert to grayscale
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

        # Enhance contrast using CLAHE
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        enhanced = clahe.apply(gray)

        # Denoise
        denoised = cv2.fastNlMeansDenoising(enhanced)

        # Auto-rotate if needed
        # TODO: Implement rotation detection and correction

        # Save preprocessed image
        preprocessed_path = f"{image_path}_preprocessed.jpg"
        cv2.imwrite(preprocessed_path, denoised)

        return preprocessed_path

    def process_image(self, image_path: str, base64: bool, preprocess: bool = True) -> str:        
        """
        Process an image and extract text in the specified format
        
        Args:
            image_path: Path to the image file
            format_type: One of ["markdown", "text", "json", "structured", "key_value"]
            preprocess: Whether to apply image preprocessing
        """
        try:
            format_type = "key_value"   

            if base64:
                # Decode base64 image
                image_path = self._decode_image(image_path, "temp.jpg")
                image_path = self._preprocess_image(image_path)
                image_base64 = self._encode_image(image_path)
            
            else:
                if preprocess:
                    image_path = self._preprocess_image(image_path)
                image_base64 = self._encode_image(image_path)
            
            # Clean up temporary files
            if image_path.endswith(('_preprocessed.jpg', '_temp.jpg')):
                os.remove(image_path)

            # Generic prompt templates for different formats
            prompt = {
                "key_value": """Please look at this image and extract text that appears in key-value pairs:
                - Look for labels and their associated values
                - Focus on field that labeled 'NIK', 'Nama', 'Alamat', and 'Tempat/Tgl Lahir' or similar to that fields
                - Extract the focused field as key and identify the paired information beside it as value
                - Anwer only the OCR result in key-value pairs like { 'NIK': 'value', 'Nama': 'value', 'Alamat': 'value', 'Tempat/Tgl Lahir': 'value' }
                - If you can't find the focused field, make the value empty string but keep the key
                - Do not include any other information""",
            }

            # Get the appropriate prompt
            prompt = prompts.get(format_type, prompts["text"])

            # Prepare the request payload
            payload = {
                "model": self.model_name,
                "prompt": prompt,
                "stream": False,
                "images": [image_base64]
            }

            # Make the API call to Ollama
            response = requests.post(self.base_url, json=payload)
            response.raise_for_status()  # Raise an exception for bad status codes
            
            result = response.json().get("response", "")
            
            # Clean up the result if needed
            if format_type == "json":
                try:
                    # Try to parse and re-format JSON if it's valid
                    json_data = json.loads(result)
                    return json.dumps(json_data, indent=2)
                except json.JSONDecodeError:
                    # If JSON parsing fails, return the raw result
                    return result
            
            return result
        except Exception as e:
            return f"Error processing image: {str(e)}"

