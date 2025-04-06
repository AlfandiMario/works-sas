<?php


echo "<pre>";

// Check if REDIRECT_URL is set
if (isset($_SERVER['REDIRECT_URL'])) {
    echo "REDIRECT_URL: " . htmlspecialchars($_SERVER['REDIRECT_URL']) . "\n";

    // Parse the REDIRECT_URL into components
    $path = parse_url($_SERVER['REDIRECT_URL'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));

    echo "Parsed Segments:\n";
    print_r($segments);
} else {
    echo "REDIRECT_URL is not set.\n";
}

// Display other server parameters for debugging
echo "\nServer Parameters:\n";
print_r($_SERVER);

echo "</pre>";
// Access the query parameters
$re_id = isset($_GET['re_id']) ? htmlspecialchars($_GET['re_id']) : '';
$T_SamplingSOID = isset($_GET['T_SamplingSOID']) ? htmlspecialchars($_GET['T_SamplingSOID']) : '';

// Tambah form_code
// seperti template

$payload = [
    're_id' => $re_id,
    'T_SamplingSoID' => $T_SamplingSOID,
];

echo "<pre>";
print_r($payload);
echo "</pre>";

$url = "https://devcpone.temp.web.id/one-api/xform/xform/getformtemplate";
$attempt = 1;

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => self::TIMEOUT,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Request-ID: ' . uniqid(), // Request tracking
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    if ($attempt < self::MAX_RETRIES) {
        sleep(pow(2, $attempt)); // Exponential backoff
        return self::sendRequest($url, $payload, $attempt + 1);
    }
    throw new ApiException("Request failed after {$attempt} attempts: {$error}");
}

curl_close($ch);

if ($httpCode >= 400) {
    throw new ApiException("API returned error code: {$httpCode}");
}
echo "<pre>";
print_r($response['data']);
echo "</pre>";
