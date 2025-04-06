<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Ocr_llama
{
  private $ollamaUrl = "http://10.9.10.205:11434/api/chat";
  private $modelName = "llama3.2-vision:11b";
  private $temperature = 0.1;

  public function extract_ocr($imagePath)
  {
    if (!file_exists($imagePath)) {
      return ["status"=>"ERR", "message"=> "Error: File not found."];
    }

    // Load image and convert to black and white
    $image = imagecreatefromstring(file_get_contents($imagePath));
    imagefilter($image, IMG_FILTER_GRAYSCALE);

    // Get original dimensions and resize
    // $newWidth = intval(imagesx($image) / 2);
    // $newHeight = intval(imagesy($image) / );
    // $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    // imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($image), imagesy($image));

    // Convert image to base64
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    $imgStr = base64_encode($imageData);

    // Prepare request payload
    $payload = json_encode([
      "model" => $this->modelName,
      "messages" => [[
        "role" => "user",
        "content" => "Extract all text from the given image. Return result only, in the pattern label:value as it is, without translation.",
        "images" => [$imgStr]
      ]],
      "options" => ["temperature" => $this->temperature],
      "stream" => false
    ]);

    // Initialize cURL
    $ch = curl_init($this->ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Execute request
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
      $error = curl_error($ch);
      curl_close($ch);
      return ["status" => "ERR", "message" => curl_error($ch)];
    }
    curl_close($ch);
    // Decode response
    // Remove <pre> tags if present
    $response = strip_tags($response);

    $responseData = json_decode($response, true);
    if (!$responseData || !isset($responseData['message']['content'])) {
      return ["status"=>"ERR", "message"=> "raw : $response"];
    }

    // Parse content into JSON object
    $content = $responseData["message"]["content"];
    // echo "content:\n";
    // echo $content . "\n";
    // echo "\n";
    $parsedData = [];
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
      $parts = explode(":", $line, 2);
      if (count($parts) == 2) {
        $key = trim(str_replace("*","",$parts[0]));
        $key = strtolower($key);
        $key = str_replace(" ","_",$key);
        $value = trim(str_replace("*","",$parts[1]));
        
        if (strpos($key, "/")) {
          $a_key = explode("/", $key);
          $a_val = explode(",", $value);
          if (strpos($value, "/")) {
            $a_val = explode("/", $value);
          }
          foreach($a_key as $a_idx => $sub_key) {
            $sub_val = $a_val[$a_idx];
            $parsedData[trim($sub_key)] = trim($sub_val);
          }
        } else {
          $parsedData[$key] = $value;
        }
      }
    }
    $info["total_duration"] = $responseData["total_duration"];
    $info["prompt_eval_duration"] = $responseData["prompt_eval_duration"];
    $info["eval_duration"] = $responseData["eval_duration"];
    $info["model"] = $responseData["model"];
    return ["status"=>"OK", "data" => $parsedData, "info"=> $info];
  }
}
