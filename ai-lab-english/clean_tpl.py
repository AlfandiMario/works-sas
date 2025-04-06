import pandas as pd

# Define input and output files in pairs
file_pairs = [
    ("training-data/tpl_bandung.csv", "training-data/cleaned_tpl_bandung.csv"),
    ("training-data/tpl_jakarta.csv", "training-data/cleaned_tpl_jakarta.csv"),
    ("training-data/tpl_surabaya.csv", "training-data/cleaned_tpl_surabaya.csv")
]

def clean_and_save_data(input_file, output_file):
    # Read the CSV file into a DataFrame
    df = pd.read_csv(input_file)
    
    # Clean the 'asli' and 'translate' columns
    df['asli'] = df['asli'].str.replace('_', ' ', regex=False)
    df['translate'] = df['translate'].str.replace('_', ' ', regex=False)
    
    # Drop duplicates
    df.drop_duplicates(inplace=True)
    
    # Check for missing translations
    missing_translations = df[df['translate'].isnull()]
    
    # Save the cleaned data to a new CSV file
    df.to_csv(output_file, index=False)
    
    # Print summary
    print(f"Processed file: {input_file}")
    print(f"Number of missing translations: {missing_translations.shape[0]}")
    print(f"Cleaned data saved to: {output_file}\n")

# Process each file in the list
for input_file, output_file in file_pairs:
    clean_and_save_data(input_file, output_file)
