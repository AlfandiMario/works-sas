<?php
// api_handler.php

function getApiResponse()
{
    $url = "https://devcpone.temp.web.id/one-api/xform/xform/getumum";
    $payload = [
        "trx_id" => "529",
        "re_id" => "529",
        "orderid" => "322",
        "sampletypeid" => "219",
        "T_SamplingSoID" => "423",
        "test_name" => "PEMERIKSAAN FISIK",
        "group_name" => "Pemeriksaan Fisik",
        "group_resume_mcu" => "FISIK",
        "test_id" => "2562",
        "nat_testid" => "6236",
        "language_id" => "1",
        "template_id" => "27",
        "template_name" => "Fisik Umum K3",
        "template_flag_other" => "Y",
        "status_result" => [],
        "status_result_arr" => [
            [
                "id" => "1",
                "name" => "Normal",
                "isNormal" => "Y"
            ],
            [
                "id" => "2",
                "name" => "Tidak Normal",
                "isNormal" => "N"
            ]
        ],
        "status_name" => "VALIDASI 1",
        "note" => "",
        "status" => "VAL1",
        "language_name" => "Bahasa Indonesia",
        "doctors" => "",
        "doctor_id" => "65",
        "doctor_fullname" => "dr. Alessandra Nidia",
        "details" => [],
        "langs" => "",
        "photos" => [],
        "act" => "Fisik Umum K3"
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: " . $error_msg);
    }

    // Close the cURL session
    curl_close($ch);

    // Handle the response
    if ($response === false) {
        throw new Exception("API request failed.");
    }

    return json_decode($response, true);
}

// Cache the API response in the session
session_start();
if (!isset($_SESSION['api_response'])) {
    $_SESSION['api_response'] = getApiResponse();
}

return $_SESSION['api_response'];
