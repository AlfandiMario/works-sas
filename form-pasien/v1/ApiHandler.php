<?php

class ApiException extends Exception {}

class ApiHandler
{
    private const TIMEOUT = 30;
    private const MAX_RETRIES = 3;
    const GET_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/getformtemplate";
    const GET_PATIENT_INFO_URL = "https://devcpone.temp.web.id/one-api/xform/xform/getpatientinfo";
    const GET_UNLISTED_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/getunlistedidcode";
    const SUBMIT_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/saveform";
    const HAS_FILLED_FORM_URL = "https://devcpone.temp.web.id/one-api/xform/xform/hasfilledform";


    public static function getApiResponse($formCode)
    {
        $arr = [
            'formCode' => $formCode
        ];
        $payload = json_encode($arr, JSON_PRETTY_PRINT);

        return self::sendRequest(self::GET_FORM_URL, $payload);
    }


    public static function submitFormData($formData)
    {
        /* $formData sudah JSON_PRETTY_PRINT */
        return self::sendRequest(self::SUBMIT_FORM_URL, $formData);
    }


    public static function getPatientInfo($noreg, $mcuNum)
    {
        // Check if noreg and mcuNum are empty string or null
        if (empty($noreg) || empty($mcuNum)) {
            $message = [
                'status' => 'ERROR',
                'message' => 'NoReg and McuNum cannot be empty'
            ];
            echo json_encode($message, JSON_PRETTY_PRINT);
            exit;
        }

        $arr = [
            'noreg' => $noreg,
            'mcu_num' => $mcuNum
        ];
        $payload = json_encode($arr, JSON_PRETTY_PRINT);

        return self::sendRequest(self::GET_PATIENT_INFO_URL, $payload);
    }

    public static function getUnlistedFormIdCode($formCode)
    {
        $arr = [
            'formCode' => $formCode
        ];
        $payload = json_encode($arr, JSON_PRETTY_PRINT);

        $json = self::sendRequest(self::GET_UNLISTED_FORM_URL, $payload);

        $arr = $json['data'];

        return $arr;
    }

    public static function checkPatientForm($noreg, $mcuNum)
    {
        $arr = [
            'noreg' => $noreg,
            'mcu_num' => $mcuNum
        ];
        $payload = json_encode($arr, JSON_PRETTY_PRINT);

        return self::sendRequest(self::HAS_FILLED_FORM_URL, $payload);
    }


    private static function sendRequest($url, $payload)
    {
        try {
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
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            // Check for cURL errors
            if ($response === false) {
                $errorMessage = curl_error($curl);
                $errorCode = curl_errno($curl);
                curl_close($curl);
                die("cURL Error ($errorCode): $errorMessage");
            }

            // Check HTTP status code
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($httpCode >= 400) {
                curl_close($curl);
                die("HTTP Error: Status code $httpCode");
            }

            curl_close($curl);

            // Decode the JSON response
            $decodedResponse = json_decode($response, true);

            return $decodedResponse;
        } catch (\Throwable $th) {
            die("Unexpected Error: " . $th->getMessage());
        }
    }
}
