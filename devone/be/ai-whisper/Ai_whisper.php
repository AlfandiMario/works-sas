<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Ai_whisper
{
    const API_KEY = "a6vizxx0GoH3kDonZLnPD9qDeq8jwZyU";
    const API_URL = "https://api.deepinfra.com/v1/openai/audio/transcriptions";
    const OROUTE_API_KEY = "655f74aaeb4c97fc75d7cdfca0ba24ea133f8adf5330e8b924b4e824928502e8"; // Api Key Open Router
    public $whisperModelName = "openai/whisper-large-v3";
    public $diarizeModelName = "google/gemini-2.0-flash-001";
    public $temperatureWhisper = 0;
    public $temperatureDiarize = 0.7;
    public $whisperLanguage = "id";

    public function with_diarization($audioPath, $persons)
    {
        if (!file_exists($audioPath)) {
            return ["status" => "ERR", "message" => "Error: File not found."];
        }

        // Get file extension and appropriate MIME type
        $extension = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
        $mimeType = $this->getMimeType($extension);

        // Create CURLFile with correct MIME type
        $curlFile = new CURLFile(
            $audioPath,    // File path
            $mimeType,     // MIME type
            basename($audioPath)  // Filename
        );

        // Prepare the multipart form data
        $postFields = [
            'file' => $curlFile,
            'model' => $this->whisperModelName,
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['segment'],
            'language' => $this->whisperLanguage,
        ];

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::API_KEY,
                // Don't set Content-Type header - cURL will set it automatically with boundary
            ],
            CURLOPT_TIMEOUT => 300, // 5 minutes timeout for large files
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // Handle cURL errors
        if ($error) {
            return ["status" => "ERR", "message" => "cURL Error: " . json_encode($error, true)];
        }

        // Handle API errors
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            return ["status" => "ERR", "message" => "API Error: " . ($errorData['error'] ?? "Unknown error occurred")];
        }

        // Decode and return the response
        $result = json_decode($response, true);
        return [
            'status' => 'OK',
            'data' => $this->processDiarization($result, $persons),
        ];
    }

    private function getMimeType($extension)
    {
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg'
        ];

        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'audio/mpeg';
    }

    // * INFO: Sementara begini dulu sampai bisa diarize pakai python
    private function processDiarization($response, $persons)
    {
        $result = [
            'diarized' => [],
            'raw' => $response
        ];

        // Create array of speaker IDs based on number of persons
        $speakers = [];
        for ($i = 1; $i <= $persons; $i++) {
            $speakers[] = sprintf("SPEAKER_%02d", $i);
        }

        // Track current speaker index
        $currentSpeakerIndex = 0;
        $speakerCount = count($speakers);

        foreach ($response['segments'] as $segment) {
            // * 20250224: Maybe digunakan sometime
            // $diarizedSegment = [
            //     'who' => $speakers[$currentSpeakerIndex],
            //     'start' => $segment['start'],
            //     'end' => $segment['end'],
            //     'text' => trim($segment['text'])
            // ];

            $convo =  $speakers[$currentSpeakerIndex] . ": " . trim($segment['text']);

            $result['diarized'][] = $convo;
            // $result['raw'][] = $diarizedSegment;

            $currentSpeakerIndex = ($currentSpeakerIndex + 1) % $speakerCount;
        }

        return $result;
    }

    private function langChainDiarization($response)
    {
        $prompt = "This is a Whisper transcript of a 2-person conversation using Bahasa Indonesia. " .
            "Identify speakers and segment the dialogue based on context because segmentation from whisper is sometimes wrong. " .
            "Return the result in JSON format:\n" .
            "{\n" .
            "\"text\": \"Full transcription text...\",\n" .
            "\"diarized\": [\n" .
            "{\n" .
            "\"who\": \"Speaker 1 or Speaker 2\",\n" .
            "\"start\": timestamp,\n" .
            "\"end\": timestamp,\n" .
            "\"text\": \"...\"\n" .
            "}\n" .
            "]\n" .
            "}\n" .
            "Ensure accurate segmentation and speaker attribution.\n" .
            "The convo:\n\n" .
            $response['text'];

        $payload = json_encode([
            "model" => $this->diarizeModelName,
            "messages" => [
                [
                    "role" => "system",
                    "content" => "You are a helpful assistant that analyzes conversations and identifies speakers."
                ],
                [
                    "role" => "user",
                    "content" => $prompt,
                ]
            ],
            "options" => ["temperature" => $this->temperatureDiarize],
            "stream" => false
        ]);

        // Initialize cURL
        $ch = curl_init(self::API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . self::API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Execute request
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ["status" => "ERR", "message" => curl_error($ch)];
        }
        curl_close($ch);

        $response = strip_tags($response);
        $responseData = json_decode($response, true);
        $content = $responseData['choices'][0]['message']['content'];

        if (is_null($responseData) || !isset($content)) {
            return ["status" => "ERR", "message" => "raw : $response"];
        }
    }
}
