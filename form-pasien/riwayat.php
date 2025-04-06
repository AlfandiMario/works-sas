<?php
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
$code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
$pid = isset($_GET['pid']) ? htmlspecialchars($_GET['pid']) : ''; // patien_id
$name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; //project_code
$dob = isset($_GET['dob']) ? htmlspecialchars($_GET['dob']) : ''; // dob
$phone = isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; //  

// Tambah form_code
// seperti template


// Output each parameter wrapped in <pre> tags
echo "<pre>Code: $code</pre>";
echo "<pre>Patient ID: $pid</pre>";
echo "<pre>Name: $name</pre>";
echo "<pre>Date of Birth: $dob</pre>";
echo "<pre>Phone: $phone</pre>";

// Test
// https://devcpone.temp.web.id/xform/riwayat?code=1&pid=2&name=3&dob=4&phone=5
// https://devcpone.temp.web.id/xform/riwayat/1/2/4/5

