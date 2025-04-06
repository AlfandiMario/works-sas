from transformers import AutoTokenizer, AutoModelForSeq2SeqLM, Seq2SeqTrainer, Seq2SeqTrainingArguments
from datasets import load_dataset
import evaluate  # Import the evaluate library

# Load tokenizer and model
model_name = "Helsinki-NLP/opus-mt-id-en"
tokenizer = AutoTokenizer.from_pretrained(model_name)
model = AutoModelForSeq2SeqLM.from_pretrained(model_name)

# Load dataset (Replace with your actual dataset)
dataset = load_dataset("csv", data_files="training-data/aditya_orderdetail_onlyletters_v1.csv")  # Ensure your dataset has 'indonesian' and 'english' columns
train_dataset = dataset["train"]


# Tokenization
def preprocess_function(examples):
    inputs = tokenizer(examples["asli"], max_length=128, truncation=True, padding="max_length")
    targets = tokenizer(examples["translate"], max_length=128, truncation=True, padding="max_length")
    inputs["labels"] = targets["input_ids"]
    return inputs

tokenized_train_dataset = train_dataset.map(preprocess_function, batched=True)

# Load BLEU metric
bleu = evaluate.load("sacrebleu")  # Updated

# Data Collator
from transformers import DataCollatorForSeq2Seq

data_collator = DataCollatorForSeq2Seq(tokenizer, model=model)

# Training arguments
training_args = Seq2SeqTrainingArguments(
    output_dir="./results-trainer",
    eval_strategy="epoch",
    learning_rate=5e-5,
    per_device_train_batch_size=16,
    per_device_eval_batch_size=16,
    weight_decay=0.01,
    save_total_limit=3,
    num_train_epochs=3,
    predict_with_generate=True,  # Enables text generation during evaluation
    logging_dir="./logs",
    logging_steps=10,
    save_steps=500,
    do_train=True,
    do_eval=True,
)

# Split dataset
train_dataset, eval_dataset = dataset["train"].train_test_split(test_size=0.1).values()

# Tokenize datasets
tokenized_train_dataset = train_dataset.map(preprocess_function, batched=True)
tokenized_eval_dataset = eval_dataset.map(preprocess_function, batched=True)

# BLEU evaluation function
def compute_metrics(eval_preds):
    predictions, labels = eval_preds
    # Decode predictions and labels
    decoded_preds = tokenizer.batch_decode(predictions, skip_special_tokens=True)
    decoded_labels = tokenizer.batch_decode(labels, skip_special_tokens=True)
    # Compute BLEU score
    bleu_score = bleu.compute(predictions=decoded_preds, references=[[ref] for ref in decoded_labels])
    return {"bleu": bleu_score["score"]}

# Trainer
trainer = Seq2SeqTrainer(
    model=model,
    args=training_args,
    train_dataset=tokenized_train_dataset,
    eval_dataset=tokenized_eval_dataset,  # Add evaluation dataset
    data_collator=data_collator,
    compute_metrics=compute_metrics,
)

# Train the model
trainer.train()

# Save the fine-tuned model
model.save_pretrained("model/using_trainer")
tokenizer.save_pretrained("model/using_trainer")
