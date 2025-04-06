#initialize venv one time only
python -m venv venv

#activate venv
#called every time starting the script
source venv/bin/activate

#install dependencies
pip install fastapi uvicorn transformers torch

# TBD for cuda gpu

If you need GPU support with CUDA for PyTorch,
use the appropriate installation command from PyTorch's website.
