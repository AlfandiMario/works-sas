<?php
// class ErrorHandler
// {
//     public static function handleApiError($apiResponse)
//     {
//         if (isset($apiResponse['status']) && $apiResponse['status'] === 'error') {
//             die("Error retrieving API data: " . $apiResponse['message']);
//         }
//         if (!isset($apiResponse['data'])) {
//             die("Invalid API response format.");
//         }
//     }

//     public static function validateFormData($formData)
//     {
//         $errors = [];
//         foreach ($formData as $key => $value) {
//             if (empty($value) && $value !== false) {
//                 $errors[] = "Field $key is required.";
//             }
//         }
//         return $errors;
//     }
// }

class ErrorHandler
{
    public static function handleApiError($apiResponse)
    {
        if (!isset($apiResponse['status'])) {
            throw new ApiException('Missing status in API response');
        }

        if ($apiResponse['status'] === 'error') {
            $message = $apiResponse['message'] ?? 'Unknown error';
            throw new ApiException($message);
        }

        if (!isset($apiResponse['data']['records'])) {
            throw new ApiException('Invalid API response structure');
        }
    }

    public static function validateFormData($formData)
    {
        $errors = [];

        foreach ($formData as $key => $value) {
            if (is_array($value)) {
                if (isset($value['required']) && $value['required'] && empty($value['value'])) {
                    $errors[] = "Field {$key} is required";
                }

                if (!empty($value['value'])) {
                    // TODO: Add validation rules based on field type
                    // Example: date validation, numeric validation, etc.
                }
            }
        }

        return $errors;
    }
}
