import pandas as pd
from datasets import load_dataset
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM, Seq2SeqTrainer, Seq2SeqTrainingArguments
from transformers import DataCollatorForSeq2Seq
import evaluate
import datetime

# **Step 1: Load and Clean the CSV**
# Load CSV file and clean multi-line issues
df = pd.read_csv("training-data/cleaned_tpl_all.csv", encoding="utf-8", quoting=3)
df.dropna(inplace=True)  # Remove rows with missing values

# Save cleaned dataset
df.to_csv("training-data/cleaned_tpl_all_cleaned.csv", index=False)

# **Step 2: Load Dataset in Hugging Face Format**
dataset = load_dataset("csv", data_files="training-data/cleaned_tpl_all_cleaned.csv")
train_dataset = dataset["train"]

# **Step 3: Define Tokenizer and Model**
model_name = "Helsinki-NLP/opus-mt-id-en"
tokenizer = AutoTokenizer.from_pretrained(model_name)
model = AutoModelForSeq2SeqLM.from_pretrained(model_name)

# **Step 4: Preprocessing Function**
def preprocess_function(examples):
    # Replace line breaks in source and target texts
    inputs = [text.replace("\n", " ") for text in examples["id"]]
    targets = [text.replace("\n", " ") for text in examples["en"]]

    # Tokenize source and target texts
    model_inputs = tokenizer(inputs, max_length=128, truncation=True, padding="max_length")
    labels = tokenizer(targets, max_length=128, truncation=True, padding="max_length")
    model_inputs["labels"] = labels["input_ids"]
    return model_inputs

# **Step 5: Tokenize Dataset**
# Split dataset into training and evaluation sets
train_dataset, eval_dataset = train_dataset.train_test_split(test_size=0.1).values()

# Tokenize datasets
tokenized_train_dataset = train_dataset.map(preprocess_function, batched=True)
tokenized_eval_dataset = eval_dataset.map(preprocess_function, batched=True)

# **Step 6: Define Data Collator**
data_collator = DataCollatorForSeq2Seq(tokenizer, model=model)

# **Step 7: BLEU Metric for Evaluation**
bleu = evaluate.load("sacrebleu")

def compute_metrics(eval_pred):
    predictions, labels = eval_pred

    # Replace -100 used by Hugging Face for ignored tokens with the tokenizer pad token ID
    labels = [[(label if label != -100 else tokenizer.pad_token_id) for label in l] for l in labels]

    # Decode predictions and references
    decoded_preds = tokenizer.batch_decode(predictions, skip_special_tokens=True)
    decoded_labels = tokenizer.batch_decode(labels, skip_special_tokens=True)

    # Calculate BLEU score
    bleu_score = bleu.compute(predictions=decoded_preds, references=[[ref] for ref in decoded_labels])
    return {"bleu": bleu_score["score"]}

# **Step 8: Training Arguments**
training_args = Seq2SeqTrainingArguments(
    output_dir="./transformer",        # Directory to save the model
    evaluation_strategy="epoch",      # Evaluate at each epoch
    learning_rate=5e-5,               # Learning rate
    per_device_train_batch_size=16,   # Batch size per GPU/CPU
    per_device_eval_batch_size=16,    # Eval batch size
    weight_decay=0.01,                # Weight decay for regularization
    save_total_limit=3,               # Limit checkpoints saved
    num_train_epochs=3,               # Number of epochs
    predict_with_generate=True,       # Enable text generation during evaluation
    logging_dir="./logs",             # Logging directory
    logging_steps=10,                 # Log every 10 steps
    save_steps=1000,                  # Save checkpoint every 1000 steps
    do_train=True,                    # Enable training
    do_eval=True                      # Enable evaluation
)

# **Step 9: Define Trainer**
trainer = Seq2SeqTrainer(
    model=model,
    args=training_args,
    train_dataset=tokenized_train_dataset,
    eval_dataset=tokenized_eval_dataset,
    data_collator=data_collator,
    tokenizer=tokenizer,
    compute_metrics=compute_metrics
)

# **Step 10: Train the Model**
trainer.train()

# **Step 11: Save the Model**
model_name = "tpl_" + datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
model.save_pretrained(f"model/{model_name}")
tokenizer.save_pretrained(f"model/{model_name}")

# **Step 12: Evaluate the Model**
results = trainer.evaluate()
print("Evaluation Results:", results)
