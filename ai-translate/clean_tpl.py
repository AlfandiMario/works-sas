import pandas as pd
import re
from nltk.tokenize import word_tokenize
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

# Define input and output file pairs
file_pairs = [
    ("training-data/tpl_all.csv", "training-data/cleaned_tpl_all.csv")
]

# Initialize Indonesian stopwords and stemmer
indonesian_stopwords = set([
    'yang', 'dan', 'di', 'ke', 'dari', 'adalah', 'ini', 'itu', 'dengan', 
    'atau', 'pada', 'untuk', 'dalam', 'jika', 'sudah', 'belum'
])
factory = StemmerFactory()
stemmer = factory.create_stemmer()

# Define a function to clean Indonesian text
def clean_indonesian_text(text):
    if pd.isnull(text):  # Handle missing or NaN values
        return text
    text = text.lower()  # Convert to lowercase
    text = re.sub(r'[^a-zA-Z0-9\s]', '', text)  # Remove punctuation and non-alphanumeric characters
    text = re.sub(r'\s+', ' ', text).strip()  # Remove extra spaces
    tokens = word_tokenize(text)  # Tokenize text
    cleaned_tokens = [stemmer.stem(word) for word in tokens if word not in indonesian_stopwords]  # Stopword removal & stemming
    return ' '.join(cleaned_tokens)  # Join tokens back to string

# Function to process each file
def clean_and_save_data(input_file, output_file):
    # Load data
    df = pd.read_csv(input_file, sep=';')
    
    # Apply cleaning to 'asli' and 'en' columns
    df['id'] = df['id'].str.replace('_', ' ', regex=False)
    df['en'] = df['en'].str.replace('_', ' ', regex=False)

    df['en'] = df['en'].str.replace('\n', ' ').str.strip()  # Replace newlines with spaces
    df['en'] = df['en'].str.replace(r'[^a-zA-Z0-9\s]', '', regex=True)  # Remove punctuation

    df = df.map(lambda x: x.replace('\n', ' ').strip() if isinstance(x, str) else x)
    df = df.map(lambda x: x.replace('\t', ' ').strip() if isinstance(x, str) else x)
    
    # Apply Indonesian text cleaning to the 'id' column
    df['id'] = df['id'].apply(clean_indonesian_text)
    
    # Drop duplicate rows based on the 'cleaned_asli' column
    df.drop_duplicates(subset='id', inplace=True)
    df.drop_duplicates(subset='en', inplace=True)
    
    # Save cleaned data to output file
    df.to_csv(output_file, index=False)
    
    # Display summary
    print(f"Processed: {input_file} → {output_file}")
    print(f"Number of rows: {df.shape[0]}")
    print()

# Process all files
for input_file, output_file in file_pairs:
    clean_and_save_data(input_file, output_file)