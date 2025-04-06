<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

class FormHandler
{
    /**
     * Process the form data and save it in the session.
     *
     * @param array $sections The sections of the form.
     * @param mixed $apiResponse The API response.
     * @return void
     */
    public static function processForm($sections, $apiResponse)
    {
        if (!isset($_SESSION['form_data'])) {
            $_SESSION['form_data'] = [];
        }

        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);
        $currentSection = $sections[$currentSectionKey];
        $savedData = $_SESSION['form_data'];

        // Store current form data
        self::storeFormData($currentSection, $savedData);

        // Get action (next/previous)
        $action = $_POST['action'] ?? 'next';

        if ($action === 'previous') {
            self::redirectToPreviousSection($sections);
        } else {
            if (self::isLastSection($sections)) {
                self::finalizeSubmission($apiResponse, $savedData);
            } else {
                self::redirectToNextSection($sections);
            }
        }
    }

    private static function storeFormData($currentSection, &$savedData)
    {
        foreach ($currentSection['details'] as $detail) {
            // Handle nested details
            if ($detail['details']) {
                foreach ($detail['details'] as $inside_detail) {
                    $idCode = $inside_detail['id_code'];
                    $savedData[$idCode] = [
                        'chx' => isset($_POST[$idCode . '_chx']) ? true : false,
                        'value' => $_POST[$idCode . '_value'] ?? ''
                    ];
                }
                continue;
            }

            // Handle family disease history
            if ($detail['table_name'] == 'fisik_penyakitkeluarga') {
                $idCode = $detail['id_code'];
                $savedData[$idCode]['options'] = [
                    [
                        'label' => 'Ayah',
                        'selected' => isset($_POST[$idCode . '_ayah_chx']) ? true : false,
                    ],
                    [
                        'label' => 'Ibu',
                        'selected' => isset($_POST[$idCode . '_ibu_chx']) ? true : false,
                    ]
                ];
                continue;
            }

            // Handle regular form fields
            $idCode = $detail['id_code'];
            $savedData[$idCode] = [
                'chx' => isset($_POST[$idCode . '_chx']) ? true : false,
                'value' => $_POST[$idCode . '_value'] ?? ''
            ];
        }

        $_SESSION['form_data'] = $savedData;
    }

    public static function renderForm($sections)
    {
        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);

        $currentSection = $sections[$currentSectionKey];
        $savedData = $_SESSION['form_data'] ?? [];

        include 'templates/form_template.php';
    }

    private static function getFirstKey($array)
    {
        foreach ($array as $key => $value) {
            return $key;
        }
        return null;
    }

    private static function redirectToPreviousSection($sections)
    {
        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);
        $keys = array_keys($sections);
        $currentIndex = array_search($currentSectionKey, $keys);

        if ($currentIndex <= 0) {
            header("Location: ?section=" . self::getFirstKey($sections));
        } else {
            $prevSectionKey = $keys[$currentIndex - 1];
            $_SESSION['current_section'] = $prevSectionKey;
            session_write_close();
            header("Location: ?section=$prevSectionKey");
        }
        exit;
    }

    private static function isLastSection($sections)
    {
        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);
        return array_search($currentSectionKey, array_keys($sections)) === count($sections) - 1;
    }

    private static function finalizeSubmission($apiResponse, $savedData)
    {
        $finalOutput = self::transformFormData($apiResponse, $savedData);
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode($finalOutput, JSON_PRETTY_PRINT);
        exit;

        // Submit the final form data to the API
        try {
            $submitResponse = ApiHandler::submitFormData($finalOutput);
            if (isset($submitResponse['status']) && $submitResponse['status'] === 'OK') {
                echo "Form submitted successfully!";
            } else {
                echo "Form submission failed: " . ($submitResponse['message'] ?? 'Unknown error');
            }
        } catch (Exception $e) {
            echo "Error submitting form: " . $e->getMessage();
        }

        exit;
    }

    private static function redirectToNextSection($sections)
    {
        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);
        $keys = array_keys($sections);
        $currentIndex = array_search($currentSectionKey, $keys);

        if ($currentIndex === false) {
            // Handle invalid section
            header("Location: ?section=" . self::getFirstKey($sections));
            exit;
        }

        $nextSectionKey = $keys[$currentIndex + 1];

        // Save current section to session before redirect
        $_SESSION['current_section'] = $nextSectionKey;

        // Ensure session is written before redirect
        session_write_close();

        header("Location: ?section=$nextSectionKey");
        exit;
    }

    private static function transformFormData($apiResponse, $formData)
    {
        // Transform data sesuai dengan format response getumum()
        function updateDetailsWithFormData($details, $formData)
        {
            foreach ($details as &$detail) {
                $idCode = $detail['id_code'];
                if (isset($formData[$idCode])) {

                    // Jika Penyakit Riwayat Keluarga
                    // TODO: refactor jika options Ayah tidak selalu ada di index 0. Atau options != 2
                    if ($detail['table_name'] == 'fisik_penyakitkeluarga') {
                        $detail['options'][0]['selected'] = $formData[$idCode]['options'][0]['selected'];
                        $detail['options'][1]['selected'] = $formData[$idCode]['options'][1]['selected'];
                        continue; // Agar tidak masuk ke bagian bawah
                    }

                    // Jika Riwayat Imunisasi
                    if ($detail['table_name'] == 'fisik_riwayatimunisasi') {
                        foreach ($detail['details'] as &$inside_detail) {
                            $inside_detail['chx'] = $formData[$inside_detail['id_code']]['chx'];

                            if ($formData[$inside_detail['id_code']]['value'] != '') {
                                $inside_detail['value'] = date('d/m/y', strtotime($formData[$inside_detail['id_code']]['value']));
                            } else {
                                $inside_detail['value'] = '';
                            }
                        }
                        continue; // Agar tidak masuk ke bagian bawah
                    }

                    $detail['chx'] = $formData[$idCode]['chx'];
                    $detail['value'] = $formData[$idCode]['value'];
                } else {
                    $detail['chx'] = false;
                    $detail['value'] = '';
                }
            }
            return $details;
        }

        // Update riwayats
        foreach ($apiResponse['data']['records']['riwayats'] as &$riwayat) {
            $riwayat['details'] = updateDetailsWithFormData($riwayat['details'], $formData);
        }

        // Update k3s
        foreach ($apiResponse['data']['records']['k3s'] as &$k3) {
            $k3['details'] = updateDetailsWithFormData($k3['details'], $formData);
        }

        return [
            'status' => 'OK',
            'data' => [
                'total' => 1,
                'records' => [
                    'riwayats' => $apiResponse['data']['records']['riwayats'],
                    'fisiks' => $apiResponse['data']['records']['fisiks'],
                    'umum_saran' => $apiResponse['data']['records']['umum_saran'],
                    'k3s' => $apiResponse['data']['records']['k3s'],
                    'konsul' => $apiResponse['data']['records']['konsul']
                ],
                'translate' => $apiResponse['data']['translate']
            ]
        ];
    }
}
