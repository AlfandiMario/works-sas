<?php
// Catch the GET Parameter
$formCode = "REF_001";

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

session_start();

// Include necessary files
require_once 'ApiHandler.php';
require_once 'ErrorHandler.php';
require_once 'FormHandler.php';
require_once 'SecurityHelper.php';

// Initialize CSRF token
SecurityHelper::initCsrfToken();

// Fetch API data
try {
    $apiResponse = ApiHandler::getApiResponse($formCode);
} catch (Exception $e) {
    die("Error fetching API data: " . $e->getMessage());
}

echo "<pre>";
print_r($apiResponse);
echo "</pre>";

// Handle API errors
ErrorHandler::handleApiError($apiResponse);

// Get the current section from the query string
$riwayat = $apiResponse['data']['riwayats'];
$pajanan = $apiResponse['data']['k3s'];
$sections = array_merge($riwayat, $pajanan);

echo "<pre>";
print_r($sections);
echo "</pre>";
die();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '');
    FormHandler::processForm($sections, $apiResponse);
}

// Render the form
FormHandler::renderForm($sections);
