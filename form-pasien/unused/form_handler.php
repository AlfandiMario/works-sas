<?php
// form_handler.php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST request
    $form_data = $_POST;
    $_SESSION['form_data'][] = $form_data; // Store the data in session
    echo json_encode($form_data);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request
    if (isset($_SESSION['form_data'])) {
        echo json_encode($_SESSION['form_data']);
    } else {
        echo json_encode(['message' => 'No session data available']);
    }
} else {
    // Handle other HTTP methods (optional)
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
