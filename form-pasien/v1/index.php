<?php
/* 
    * INDEX.PHP AKAN RELOAD SETIAP KALI FORM DILIHAT
    * PASTIKAN UNTUK MENGATUR SESSION  DAN VARIABEL
*/

// HTTP test url
// https://devcpone.temp.web.id/xform/v1?form=REF_001&reg=CP2412190006&mcu=MGM240700009
// OR
// https://devcpone.temp.web.id/xform/v1?form=REF_001&reg=CP2407080015&mcu=MGM240700012
// OR
// https://devcpone.temp.web.id/xform/v1/REF_001/CP2407080015/MGM240700012

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Catch the URL Parameter
$formCode = "REF_001";

$formCode = isset($_GET['form']) ? htmlspecialchars($_GET['form']) : $formCode;
$reg = isset($_GET['reg']) ? htmlspecialchars($_GET['reg']) : ''; // M_PatientNoReg
$mcuNum = isset($_GET['mcu']) ? htmlspecialchars($_GET['mcu']) : ''; // Mgm_McuNumber

session_start();

// Include necessary files
require_once 'ApiHandler.php';
require_once 'ErrorHandler.php';
require_once 'FormHandler.php';
require_once 'SecurityHelper.php';

// Initialize CSRF token
SecurityHelper::initCsrfToken();

// Cek ke DB apakah sudah ada data dengan reg dan mcuNum tersebut. Jika sudah ada, maka redirect response
if (!empty($reg) && !empty($mcuNum)) {
    $data = ApiHandler::checkPatientForm($reg, $mcuNum);

    $hasFilled = $data['data'];

    if ($hasFilled) {
        header('Location: /xform/v1/response?message=Anda sudah mengisi');
        exit;
    }
}

$flagForm = false;
$flagPatInfo = false;
$flagUnlisted = false;

// Fetch API data
try {
    // Hanya fetch ketika session form_template tidak ada dan formCode tidak kosong
    if (!isset($_SESSION['form_template']) && !empty($formCode) && !$flagForm) {
        unset($_SESSION['form_template']);
        $formTemplate = ApiHandler::getApiResponse($formCode);
        $_SESSION['form_template'] = $formTemplate;
        $flagForm = true;
    } else {
        $formTemplate = $_SESSION['form_template'];
    }

    // Hanya fetch ketika session patInfo tidak ada dan reg serta mcuNum tidak kosong
    if (!empty($reg) && !empty($mcuNum) && !$flagPatInfo) {
        unset($_SESSION['patInfo']);
        $patInfo = ApiHandler::getPatientInfo($reg, $mcuNum);
        $_SESSION['patInfo'] = $patInfo;
        $flagPatInfo = true;
    } else {
        $patInfo = $_SESSION['patInfo'];
    }

    // Hanya fetch ketika session unlisted_idcode tidak ada
    if (!isset($_SESSION['unlisted_idcode']) && !$flagUnlisted) {
        unset($_SESSION['unlisted_idcode']);
        $unlisted_idcode = ApiHandler::getUnlistedFormIdCode($formCode);
        $_SESSION['unlisted_idcode'] = $unlisted_idcode;
        $flagUnlisted = true;
    } else {
        $unlisted_idcode = $_SESSION['unlisted_idcode'];
    }
} catch (Exception $e) {
    die("Error fetching API data: " . $e->getMessage());
}

// Handle API errors
ErrorHandler::handleApiError($formTemplate);

// Get the current section from the query string
$riwayat = $formTemplate['data']['riwayats'];
$pajanan = $formTemplate['data']['k3s'];
$sections = array_merge($riwayat, $pajanan);

// Mencegah isi ulang setelah submit. Jika form sudah di submit, maka session_destroy
// Jika tidak ada session patInfo tetapi flagPatInfo true, maka redirect 404
if (!isset($_SESSION['patInfo']) && $flagPatInfo) {
    header("Location: /404");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '');
    FormHandler::processForm($sections, $formTemplate, $patInfo);
}

// Render the form
FormHandler::renderForm($sections, $patInfo, $unlisted_idcode);
