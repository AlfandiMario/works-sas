<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

class FormHandler
{
    public static function renderForm($sections, $patInfo, $unlisted_idcode)
    {
        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);

        $currentSection = $sections[$currentSectionKey];
        $savedData = $_SESSION['form_data'] ?? [];
        $patData = $patInfo['data'] ?? [];

        include 'templates/form_template.php';
    }

    public static function processForm($sections, $formTemplate, $patInfo)
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
                self::finalizeSubmission($formTemplate, $savedData, $patInfo);
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


    private static function isLastSection($sections)
    {
        $currentSectionKey = $_GET['section'] ?? self::getFirstKey($sections);
        return array_search($currentSectionKey, array_keys($sections)) === count($sections) - 1;
    }


    private static function finalizeSubmission($formTemplate, $savedData, $patInfo)
    {
        $output = self::transformFormData($formTemplate, $savedData, $patInfo);
        $finalOutput = json_encode($output, JSON_PRETTY_PRINT);

        session_destroy();

        try {
            $submitResponse = ApiHandler::submitFormData($finalOutput);
            if (isset($submitResponse['status']) && $submitResponse['status'] === 'OK') {
                // Redirect to the response page
                header('Location: /xform/v1/response');
                exit;
            } else {
                // Redirect to the response page with error message
                header('Location: /xform/v1/response?message=Form submission failed: ' . htmlspecialchars($submitResponse['message'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8'));
                exit;
            }
        } catch (Exception $e) {
            // Redirect to the response page with exception message
            header('Location: /xform/v1/response?message=Error submitting form: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            exit;
        }
    }


    private static function transformFormData($formTemplate, $formData, $patInfo)
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
        foreach ($formTemplate['data']['riwayats'] as &$riwayat) {
            $riwayat['details'] = updateDetailsWithFormData($riwayat['details'], $formData);
        }

        // Update k3s
        foreach ($formTemplate['data']['k3s'] as &$k3) {
            $k3['details'] = updateDetailsWithFormData($k3['details'], $formData);
        }

        return [
            'form_meta' => $patInfo['data'],
            'form_data' => [
                'riwayats' => $formTemplate['data']['riwayats'],
                'fisiks' => $formTemplate['data']['fisiks'],
                'umum_saran' => $formTemplate['data']['umum_saran'],
                'k3s' => $formTemplate['data']['k3s'],
                'konsul' => $formTemplate['data']['konsul']
            ]
        ];
    }
}
