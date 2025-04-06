from transformers import AutoTokenizer
from datasets import load_dataset
from torch.utils.data import DataLoader
import torch
from transformers import AutoModelForSeq2SeqLM, AdamW
import datetime

# Load the tokenizer and dataset
tokenizer = AutoTokenizer.from_pretrained("Helsinki-NLP/opus-mt-id-en")

# Load the dataset from a local CSV file
dataset = load_dataset("csv", data_files="training-data/cleaned_all_tpl.csv", split="train")
model_name = "model_all_tpl"

# Tokenize the dataset
def tokenize_function(examples):
    model_inputs = tokenizer(examples["asli"], max_length=128, truncation=True, padding="max_length")
    with tokenizer.as_target_tokenizer():
        labels = tokenizer(examples["translate"], max_length=128, truncation=True, padding="max_length")
    model_inputs["labels"] = labels["input_ids"]
    return model_inputs

tokenized_dataset = dataset.map(tokenize_function, batched=True, remove_columns=dataset.column_names)

# Convert to PyTorch tensors
tokenized_dataset.set_format(type="torch")

# Set device
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# Set batch size and create data loader
batch_size = 8
train_dataloader = DataLoader(tokenized_dataset, shuffle=True, batch_size=batch_size)

# Load the model
model = AutoModelForSeq2SeqLM.from_pretrained("Helsinki-NLP/opus-mt-id-en")
model.to(device)

# Define optimizer
optimizer = AdamW(model.parameters(), lr=5e-5)

# Training loop
model.train()
num_epochs = 3

for epoch in range(num_epochs):
    total_loss = 0
    for batch_idx, batch in enumerate(train_dataloader):
        # Move batch to device
        input_ids = batch["input_ids"].to(device)
        attention_mask = batch["attention_mask"].to(device)
        labels = batch["labels"].to(device)
        
        # Forward pass
        outputs = model(
            input_ids=input_ids,
            attention_mask=attention_mask,
            labels=labels
        )
        loss = outputs.loss
        
        # Backward pass
        loss.backward()
        optimizer.step()
        optimizer.zero_grad()
        
        total_loss += loss.item()
        
        if batch_idx % 10 == 0:  # Print every 10 batches
            print(f"Epoch {epoch + 1}, Batch {batch_idx}, Loss: {loss.item():.4f}")
    
    avg_loss = total_loss / len(train_dataloader)
    print(f"Epoch {epoch + 1} completed. Average Loss: {avg_loss:.4f}")

# Save the model
model_path = f"model/{model_name}"
tokenizer_path = f"model/{model_name}"

model.save_pretrained(model_path)
tokenizer.save_pretrained(tokenizer_path)
print(f"Model saved to {model_path}")

# Test the model
model.eval()
sentence = "non reaktif"
inputs = tokenizer(sentence, return_tensors="pt", padding=True, truncation=True).to(device)

with torch.no_grad():
    # Generate translation
    outputs = model.generate(**inputs)
    translation = tokenizer.decode(outputs[0], skip_special_tokens=True)
    print(f"Input: {sentence}")
    print(f"Translation: {translation}")
