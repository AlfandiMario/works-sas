import base64
import requests
import cv2
import json
import os

class OCRProcessor:
    def __init__(self, model_name: str = "llama3.2-vision:11b", 
                 base_url: str = "http://localhost:11434/api/generate"):
        self.model_name = model_name
        self.base_url = base_url
        
    def _encode_image(self, image_path: str) -> str:
        """Convert image to base64 string"""
        with open(image_path, "rb") as image_file:
            return base64.b64encode(image_file.read()).decode("utf-8")

    def _preprocess_image(self, image_path: str) -> str:
        """Basic image preprocessing"""
        image = cv2.imread(image_path)
        if image is None:
            raise ValueError(f"Could not read image at {image_path}")

        # Convert to grayscale
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

        # Enhance contrast
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        enhanced = clahe.apply(gray)

        # Denoise
        denoised = cv2.fastNlMeansDenoising(enhanced)

        # Save preprocessed image
        preprocessed_path = f"{image_path}_preprocessed.jpg"
        cv2.imwrite(preprocessed_path, denoised)
        
        return preprocessed_path

    def process_ktp(self, image_path: str) -> dict:
        """Process KTP image and extract specific fields"""
        try:
            # Preprocess image
            processed_path = self._preprocess_image(image_path)
            image_base64 = self._encode_image(processed_path)
            
            # Clean up temporary file
            os.remove(processed_path)

            # KTP-specific prompt
            prompt = """Please analyze this Indonesian ID Card (KTP) image and extract the following fields in key-value pairs:
            - NIK (16-digit number)
            - Nama (full name)
            - Tempat/Tgl Lahir (place and date of birth)
            - Alamat (address)
            
            Return only these fields in the specified format. If a field cannot be read, return empty string for its value."""

            # Prepare request
            payload = {
                "model": self.model_name,
                "prompt": prompt,
                "stream": False,
                "images": [image_base64]
            }

            # Make API call
            response = requests.post(self.base_url, json=payload)
            response.raise_for_status()
            
            # Process response to ensure all required fields
            try:
                result = json.loads(response.json().get("response", "{}"))
            except json.JSONDecodeError:
                # If response is not valid JSON, create structured response from text
                text_response = response.json().get("response", "")
                result = {
                    "NIK": "",
                    "Nama": "",
                    "Tempat/Tgl Lahir": "",
                    "Alamat": ""
                }
                # Basic text parsing logic could be added here

            # Ensure all required fields exist
            required_fields = ["NIK", "Nama", "Tempat/Tgl Lahir", "Alamat"]
            for field in required_fields:
                if field not in result:
                    result[field] = ""

            return result

        except Exception as e:
            raise Exception(f"Error processing KTP: {str(e)}")

    def process_raw(self, image_path: str) -> str:
        """Get raw OCR output from model"""
        try:
            # Preprocess image
            processed_path = self._preprocess_image(image_path)
            image_base64 = self._encode_image(processed_path)
            
            # Clean up temporary file
            os.remove(processed_path)

            # Generic prompt
            prompt = "Please extract and return all text you can see in this image."

            # Prepare request
            payload = {
                "model": self.model_name,
                "prompt": prompt,
                "stream": False,
                "images": [image_base64]
            }

            # Make API call
            response = requests.post(self.base_url, json=payload)
            response.raise_for_status()
            
            return response.json().get("response", "")

        except Exception as e:
            raise Exception(f"Error processing image: {str(e)}")