<?php
session_start();

// Include the API handler
require_once 'api_handler.php';

// Check if the API response contains an error
if (isset($_SESSION['api_response']['status']) && $_SESSION['api_response']['status'] === 'error') {
    die("Error retrieving API data: " . $_SESSION['api_response']['message']);
}

// Proceed with the rest of the code
$apiResponse = $_SESSION['api_response'];

// Get the current section from the query string
$riwayat = $apiResponse['data']['records']['riwayats'];
$pajanan = $apiResponse['data']['records']['k3s'];

$sections = array_merge($riwayat, $pajanan);

$currentSectionKey = $_GET['section'] ?? array_key_first($sections);
$currentSection = $sections[$currentSectionKey];

// Load saved form data from the session
$savedData = $_SESSION['form_data'] ?? [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save form data to the session
    foreach ($currentSection['details'] as $detail) {
        $idCode = $detail['id_code'];
        $savedData[$idCode] = [
            'chx' => isset($_POST[$idCode . '_chx']),
            'value' => $_POST[$idCode . '_value'] ?? ''
        ];
    }
    $_SESSION['form_data'] = $savedData;

    // Log the submitted data to a file for debugging
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'post_data' => $_POST,
        'saved_data' => $savedData
    ];
    file_put_contents('form_debug.log', print_r($logData, true), FILE_APPEND);

    // Redirect to the next section
    $nextSectionKey = array_search($currentSectionKey, array_keys($sections)) + 1;
    if ($nextSectionKey < count($sections)) {
        $nextSection = array_keys($sections)[$nextSectionKey];
        header("Location: ?section=$nextSection");
        exit;
    } else {
        echo "<script>alert('Form submitted successfully!');</script>";
        echo json_encode($savedData);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Information Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-4">
        <h3 class="text-center mb-4">Medical Information Form</h3>
        <form method="POST" action="">
            <div class="card mb-4">
                <div class="card-header">
                    <h6><?php echo $currentSection['title']; ?></h2>
                </div>

                <!-- FORM RIWAYAT PENYAKIT -->
                <?php if ($currentSection['title'] == 'RIWAYAT PENYAKIT') { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6> <?= $detail['name'] ?> </h6>
                            </div>
                            <?php foreach ($detail['details'] as $inside_detail): ?>
                                <div class="mb-2">
                                    <label class="form-check-label"><?php echo $inside_detail['label']; ?></label>
                                    <input type="checkbox"
                                        name="<?php echo $inside_detail['id_code']; ?>_chx"
                                        class="form-check-input border-black"
                                        <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? 'checked' : ''; ?>>
                                    <input type="text" name="<?php echo $inside_detail['id_code']; ?>_value"
                                        class="form-control mt-2"
                                        placeholder="Keterangan..."
                                        <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? '' : 'disabled'; ?>><?php echo $savedData[$inside_detail['id_code']]['value'] ?? ''; ?>
                                    </input>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>

                    <!-- FORM RIWAYAT PENYAKIT KELUARGA -->
                <?php } elseif ($currentSection['title'] == 'RIWAYAT PENYAKIT KELUARGA') { ?>
                    <div class="card-body">
                        <small><?= $currentSection['subtitle'] ?></small>
                        <table>
                            <tr>
                                <th style="width: 70%;"></th>
                                <th style="width: 15%;">Ayah</th>
                                <th style="width: 15%;">Ibu</th>
                            </tr>
                            <?php foreach ($currentSection['details'] as $detail): ?>
                                <tr>
                                    <td><?= $detail['label'] ?></td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $detail['id_code']; ?>_chx"
                                            class="form-check-input border-black"
                                            <?php echo isset($savedData[$detail['id_code']]['options'][0]['selected']) && $savedData[$detail['id_code']]['options'][0]['selected'] ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $detail['id_code']; ?>_chx"
                                            class="form-check-input border-black"
                                            <?php echo isset($savedData[$detail['id_code']]['options'][1]['selected']) && $savedData[$detail['id_code']]['options'][1]['selected'] ? 'checked' : ''; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- FORM RIWAYAT KEBIASAAN HIDUP -->
                <?php } elseif ($currentSection['title'] == 'RIWAYAT KEBIASAAN HIDUP') { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6> <?= $detail['name'] ?> </h6>
                            </div>
                            <?php foreach ($detail['details'] as $inside_detail): ?>
                                <div class="mb-2">
                                    <!-- Form Olahraga -->
                                    <?php if ($inside_detail['id_code'] == 'fisik_kebiasaanhidup_4') { ?>
                                        <label class="form-check-label"><?php echo $inside_detail['label']; ?></label>
                                        <input type="radio"
                                            name="<?php echo $detail['id_code']; ?>_group" <!-- Group radio buttons by detail ID -->
                                        value="<?php echo $inside_detail['id_code']; ?>"
                                        class="form-check-input border-black"
                                        <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? 'checked' : ''; ?>>

                                        <div class="row d-flex justify-content-center">
                                            <div class="col-6">
                                                <input type="text" name="<?php echo $inside_detail['id_code']; ?>_value"
                                                    class="form-control mt-2"
                                                    placeholder="Contoh: 4"
                                                    <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? '' : 'disabled'; ?>>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-check-label" for="<?php echo $inside_detail['id_code']; ?>_value">
                                                    <?= $inside_detail['suffix'] ?>
                                                </label>
                                            </div>
                                        </div>

                                    <?php } elseif ($inside_detail['id_code'] == 'fisik_kebiasaanhidup_5') { ?>

                                        <label class="form-check-label"><?php echo $inside_detail['label']; ?></label>
                                        <input type="radio"
                                            name="<?php echo $detail['id_code']; ?>_group" <!-- Group radio buttons by detail ID -->
                                        value="<?php echo $inside_detail['id_code']; ?>"
                                        class="form-check-input border-black"
                                        <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? 'checked' : ''; ?>>
                                        <input type="text" name="<?php echo $inside_detail['id_code']; ?>_value"
                                            class="form-control mt-2"
                                            placeholder="Keterangan..."
                                            <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? '' : 'disabled'; ?>>
                                    <?php } else { ?>
                                        <!-- Form Minum Alkohol dan Merokok -->
                                        <label class="form-check-label"><?php echo $inside_detail['label']; ?></label>
                                        <input type="radio"
                                            name="<?php echo $detail['id_code']; ?>_group" <!-- Group radio buttons by detail ID -->
                                        value="<?php echo $inside_detail['id_code']; ?>"
                                        class="form-check-input border-black"
                                        <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? 'checked' : ''; ?>>
                                        <input type="text" name="<?php echo $inside_detail['id_code']; ?>_value"
                                            class="form-control mt-2"
                                            placeholder="Keterangan..."
                                            <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? '' : 'disabled'; ?>>
                                    <?php } ?>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>

                    <!-- FORM PAJANAN -->
                <?php } elseif (strpos($currentSection['title'], 'FAKTOR') !== false) { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="mb-2">
                                <input type="checkbox"
                                    name="<?php echo $detail['id_code']; ?>_chx"
                                    class="form-check-input border-black"
                                    <?php echo isset($savedData[$detail['id_code']]['chx']) && $savedData[$detail['id_code']]['chx'] ? 'checked' : ''; ?>>
                                <label class="form-check-label"><?php echo $detail['label']; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>


                    <!-- FORM FISIK UMUM -->
                <?php } else { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="mb-2">
                                <label class="form-check-label"><?php echo $detail['label']; ?></label>
                                <input type="checkbox"
                                    name="<?php echo $detail['id_code']; ?>_chx"
                                    class="form-check-input border-black"
                                    <?php echo isset($savedData[$detail['id_code']]['chx']) && $savedData[$detail['id_code']]['chx'] ? 'checked' : ''; ?>>
                                <input type="text" name="<?php echo $detail['id_code']; ?>_value"
                                    class="form-control mt-2"
                                    placeholder="Keterangan..."
                                    <?php echo isset($savedData[$detail['id_code']]['chx']) && $savedData[$detail['id_code']]['chx'] ? '' : 'disabled'; ?>><?php echo $savedData[$detail['id_code']]['value'] ?? ''; ?>
                                </input>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php } ?>

            </div>

            <div class="d-flex justify-content-between">
                <?php if (array_search($currentSectionKey, array_keys($sections)) > 0): ?>
                    <a href="?section=<?php echo array_keys($sections)[array_search($currentSectionKey, array_keys($sections)) - 1]; ?>" class="btn btn-secondary">Previous Page</a>
                <?php endif; ?>

                <?php if (array_search($currentSectionKey, array_keys($sections)) < count($sections) - 1): ?>
                    <button type="submit" class="btn btn-primary">Next Page</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-success">Submit</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable/disable text input based on checkbox selection
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const textInput = this.parentElement.querySelector('input[type="text"]');
                if (this.checked) {
                    textInput.disabled = false; // Enable text input
                } else {
                    textInput.disabled = true; // Disable text input
                    textInput.value = ''; // Clear text input value
                }
            });

            // Trigger change event on page load to set initial state
            checkbox.dispatchEvent(new Event('change'));
        });
    </script>

    <script>
        // Enable/disable text input based on radio button selection
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const textInput = this.parentElement.querySelector('input[type="text"]');
                if (this.checked) {
                    textInput.disabled = false; // Enable text input
                } else {
                    textInput.disabled = true; // Disable text input
                    textInput.value = ''; // Clear text input value
                }
            });

            // Trigger change event on page load to set initial state
            if (radio.checked) {
                radio.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>

</html>