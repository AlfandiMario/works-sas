<?php

// Test
// https://devcpone.temp.web.id/xform/test?re_id=867&T_SamplingSOID=1947

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
    'T_SamplingSOID' => $T_SamplingSOID,
];

// Manually format the JSON string to match the hardcoded version
$jsonPayload = json_encode($payload, JSON_PRETTY_PRINT);

echo "<pre>";
print_r($jsonPayload);
echo "</pre>";

$url = "https://devcpone.temp.web.id/one-api/xform/xform/getformtemplate_old";

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $jsonPayload, // Use the formatted JSON string
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
