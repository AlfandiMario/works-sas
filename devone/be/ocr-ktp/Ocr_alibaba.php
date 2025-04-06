<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Ocr_alibaba
{
  const API_KEY = "sk-a7de5339f0fa4cf491ffc37b00dae1cc";
  const API_URL = "https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions";
  public $modelName = "qwen-vl-plus";
  public $temperature = 0.2;

  public function extract_ocr($imagePath)
  {
    if (!file_exists($imagePath)) {
      return ["status" => "ERR", "message" => "Error: File not found."];
    }

    // Load image and convert to black and white
    $image = imagecreatefromstring(file_get_contents($imagePath));
    // imagefilter($image, IMG_FILTER_GRAYSCALE);

    // Get original dimensions and resize
    // $originalWidth = imagesx($image);
    // $originalHeight = imagesy($image);
    // $newWidth = intval(640);
    // $newHeight = intval($originalHeight * ($newWidth / $originalWidth));
    // $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    // imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // Convert image to base64
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    $imgStr = base64_encode($imageData);
    $imgStrNew = "data:image/jpeg;base64," . $imgStr;

    // Prepare request payload
    $payload = json_encode([
      "model" => $this->modelName,
      "messages" => [[
        "role" => "user",
        "content" => [
          [
            "type" => "text",
            "text" => "Extract all text from the given image. Return result only, in the pattern label:value as it is, without translation."
          ],
          [
            "type" => "image_url",
            "image_url" => [
              "url" => $imgStrNew
            ],
          ]
        ],
      ]],
      "options" => ["temperature" => $this->temperature],
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

    $parsedData = [];
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
      $parts = explode(":", $line, 2);
      if (count($parts) == 2) {
        $key = trim(str_replace("*", "", $parts[0]));
        $key = strtolower($key);
        $key = str_replace(" ", "_", $key);
        $value = trim(str_replace("*", "", $parts[1]));

        if (strpos($key, "/")) {
          $a_key = explode("/", $key);
          $a_val = explode(",", $value);
          if (strpos($value, "/")) {
            $a_val = explode("/", $value);
          }
          foreach ($a_key as $a_idx => $sub_key) {
            $sub_val = $a_val[$a_idx];
            $parsedData[trim($sub_key)] = trim($sub_val);
          }
        } else {
          $parsedData[$key] = $value;
        }
      }
    }
    $info["prompt_tokens"] = $responseData["usage"]["prompt_tokens"];
    $info["completion_tokens"] = $responseData["usage"]["completion_tokens"];
    $info["total_tokens"] = $responseData["usage"]["total_tokens"];
    $info["estimated_cost"] = $responseData["usage"]["estimated_cost"];
    $info["model"] = $this->modelName;
    $info["temperature"] = $this->temperature;
    return ["status" => "OK", "data" => $parsedData, "info" => $info];
  }
}
