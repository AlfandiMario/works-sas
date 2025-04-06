<?php

class ApiException extends Exception {}

class ApiHandler
{
    private const TIMEOUT = 30;
    private const MAX_RETRIES = 3;
    // const GET_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/getumum";
    const GET_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/getformtemplate";
    const SUBMIT_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/savefisik";

    /**
     * Fetches the API response for building the form.
     *
     * @return array
     * @throws Exception
     */
    public static function getApiResponse($formCode)
    {
        $code = [
            'formCode' => $formCode
        ];
        $payload = json_encode($code, JSON_PRETTY_PRINT);

        return self::sendRequest(self::GET_FORM_URL, $payload);
    }

    /**
     * Sends the final submitted form data to the API.
     *
     * @param array $formData
     * @return array
     * @throws Exception
     */
    public static function submitFormData($formData)
    {
        return self::sendRequest(self::SUBMIT_FORM_URL, $formData);
    }

    /**
     * Sends a POST request to the specified URL with the given payload.
     *
     * @param string $url
     * @param array $payload
     * @return array
     * @throws Exception
     */
    private static function sendRequest($url, $payload, $attempt = 1)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://devcpone.temp.web.id/one-api/xform/xform/getformtemplate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "formCode": "REF_001"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        echo "<pre> Response: ";
        print_r($response);
        echo "</pre>";

        curl_close($curl);

        // $ch = curl_init();

        // curl_setopt_array($ch, [
        //     CURLOPT_URL => $url,
        //     CURLOPT_POST => true,
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_TIMEOUT => self::TIMEOUT,
        //     CURLOPT_HTTPHEADER => [
        //         'Content-Type: application/json',
        //         'X-Request-ID: ' . uniqid(), // Request tracking
        //     ],
        //     CURLOPT_POSTFIELDS => json_encode($payload),
        //     CURLOPT_SSL_VERIFYPEER => true,
        //     CURLOPT_SSL_VERIFYHOST => 2
        // ]);

        // $response = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // if ($response === false) {
        //     $error = curl_error($ch);
        //     curl_close($ch);

        //     if ($attempt < self::MAX_RETRIES) {
        //         sleep(pow(2, $attempt)); // Exponential backoff
        //         return self::sendRequest($url, $payload, $attempt + 1);
        //     }
        //     throw new ApiException("Request failed after {$attempt} attempts: {$error}");
        // }

        // curl_close($ch);

        // if ($httpCode >= 400) {
        //     throw new ApiException("API returned error code: {$httpCode}");
        // }

        // echo "<pre> Response: ";
        // print_r($response);
        // echo "</pre>";

        return json_decode($response, true);
    }
}
