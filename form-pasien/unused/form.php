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
        if ($detail['details']) {
            foreach ($detail['details'] as $inside_detail) {
                $idCode = $inside_detail['id_code'];

                $savedData[$idCode] = [
                    'chx' => isset($_POST[$idCode . '_chx']) ? true : false,
                    'value' => $_POST[$idCode . '_value'] ?? ''
                ];
                echo 'INSIDE DETAIL: ';
                echo '<pre>';
                print_r($savedData[$idCode]);
                echo '</pre>';
            }
        }

        // TODO: refactor jika options Ayah tidak selalu ada di index 0. Atau options != 2
        // Jika Penyakit Riwayat Keluarga
        if ($detail['table_name'] == 'fisik_penyakitkeluarga') {
            $idCode = $detail['id_code'];

            $savedData[$idCode]['options'][0] = [
                'label' => 'Ayah',
                'selected' => isset($_POST[$idCode . '_ayah_chx']) ? true : false,
            ];
            $savedData[$idCode]['options'][1] = [
                'label' => 'Ibu',
                'selected' => isset($_POST[$idCode . '_ibu_chx']) ? true : false,
            ];

            continue; // Agar tidak masuk ke bagian bawah
        }

        $idCode = $detail['id_code'];
        $savedData[$idCode] = [
            'chx' => isset($_POST[$idCode . '_chx']) ? true : false,
            'value' => $_POST[$idCode . '_value'] ?? ''
        ];
    }

    $_SESSION['form_data'] = $savedData;
    // die();

    // Determine the next action
    $isLastSection = array_search($currentSectionKey, array_keys($sections)) === count($sections) - 1;
    if (!$isLastSection) {
        // Redirect to the next section
        $nextSectionKey = array_keys($sections)[array_search($currentSectionKey, array_keys($sections)) + 1];
        header("Location: ?section=$nextSectionKey");
        exit;
    } else {
        // Transform the form data into the desired format
        $finalOutput = transformFormData($apiResponse, $savedData);

        // Output the final JSON
        header('Content-Type: application/json');
        echo json_encode($finalOutput, JSON_PRETTY_PRINT);
        // echo json_encode($savedData, JSON_PRETTY_PRINT);
        exit;
    }
}

// Function to transform form data into the desired format
function transformFormData($apiResponse, $formData)
{
    // Function to update the details with form data
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

    // Build the final output
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
        <form method="POST" action="" id="medicalForm">
            <div class="card mb-4">
                <div class="card-header">
                    <h6><?php echo $currentSection['title']; ?></h6>
                </div>

                <!-- FORM RIWAYAT PENYAKIT -->
                <?php if ($currentSection['title'] == 'RIWAYAT PENYAKIT') { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6><?php echo $detail['name']; ?></h6>
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
                                        <?php echo isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx'] ? '' : 'disabled'; ?>
                                        value="<?php echo $savedData[$inside_detail['id_code']]['value'] ?? ''; ?>">
                                </div>
                            <?php endforeach; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>

                <?php } elseif ($currentSection['title'] == 'RIWAYAT PENYAKIT KELUARGA') { ?>
                    <!-- FORM RIWAYAT PENYAKIT KELUARGA -->
                    <div class="card-body">
                        <small><?php echo $currentSection['subtitle']; ?></small>
                        <table>
                            <tr>
                                <th style="width: 70%;"></th>
                                <th style="width: 15%;">Ayah</th>
                                <th style="width: 15%;">Ibu</th>
                            </tr>
                            <?php foreach ($currentSection['details'] as $detail): ?>
                                <tr>
                                    <td><?php echo $detail['label']; ?></td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $detail['id_code']; ?>_ayah_chx"
                                            class="form-check-input border-black"
                                            <?php echo $savedData[$detail['id_code']]['options'][0]['selected'] ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $detail['id_code']; ?>_ibu_chx"
                                            class="form-check-input border-black"
                                            <?php echo $savedData[$detail['id_code']]['options'][1]['selected'] ? 'checked' : ''; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                <?php } elseif ($currentSection['title'] == 'RIWAYAT KEBIASAAN HIDUP') { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6><?= $detail['name'] ?></h6>
                            </div>
                            <?php foreach ($detail['details'] as $inside_detail): ?>
                                <div class="mb-2">
                                    <!-- Form Olahraga -->
                                    <?php if ($inside_detail['id_code'] == 'fisik_kebiasaanhidup_4'): ?>
                                        <label class="form-check-label"><?= $inside_detail['label'] ?></label>
                                        <input type="checkbox"
                                            name="<?= $inside_detail['id_code'] ?>_chx"
                                            class="form-check-input border-black"
                                            data-group="<?= $detail['name'] ?>"
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?>checked<?php endif; ?>>

                                        <div class="row d-flex justify-content-center">
                                            <div class="col-6">
                                                <input type="text" name="<?= $inside_detail['id_code'] ?>_value"
                                                    class="form-control mt-2"
                                                    placeholder="Contoh: 4"
                                                    <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?><?php else: ?>disabled<?php endif; ?>
                                                    value="<?= $savedData[$inside_detail['id_code']]['value'] ?? '' ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-check-label" for="<?= $inside_detail['id_code'] ?>_value">
                                                    <?= $inside_detail['suffix'] ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php elseif ($inside_detail['id_code'] == 'fisik_kebiasaanhidup_5'): ?>
                                        <label class="form-check-label"><?= $inside_detail['label'] ?></label>
                                        <input type="checkbox"
                                            name="<?= $inside_detail['id_code'] ?>_chx"
                                            class="form-check-input border-black"
                                            data-group="<?= $detail['name'] ?>"
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?>checked<?php endif; ?>>
                                        <input type="text" name="<?= $inside_detail['id_code'] ?>_value"
                                            class="form-control mt-2"
                                            placeholder="Keterangan..."
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?><?php else: ?>disabled<?php endif; ?>
                                            value="<?= $savedData[$inside_detail['id_code']]['value'] ?? '' ?>">
                                    <?php else: ?>
                                        <label class="form-check-label"><?= $inside_detail['label'] ?></label>
                                        <input type="checkbox"
                                            name="<?= $inside_detail['id_code'] ?>_chx"
                                            class="form-check-input border-black"
                                            data-group="<?= $detail['name'] ?>"
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?>checked<?php endif; ?>>
                                        <input type="text" name="<?= $inside_detail['id_code'] ?>_value"
                                            class="form-control mt-2"
                                            placeholder="Keterangan..."
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?><?php else: ?>disabled<?php endif; ?>
                                            value="<?= $savedData[$inside_detail['id_code']]['value'] ?? '' ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>

                    <!-- FORM PAJANAN RIWAYAT IMUNISASI -->
                <?php } elseif ($currentSection['title'] == 'RIWAYAT IMUNISASI' || $currentSection['type_form'] == "XD") { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6><?= $detail['label'] ?></h6>
                            </div>
                            <?php foreach ($detail['details'] as $inside_detail): ?>
                                <div class="mb-2 form-check form-check-inline">
                                    <input type="checkbox"
                                        name="<?= $inside_detail['id_code'] ?>_chx"
                                        class="form-check-input border-black"
                                        data-group="<?= $detail['label'] ?>"
                                        <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?>checked<?php endif; ?>>
                                    <label class="form-check-label"><?= $inside_detail['label'] ?></label>

                                    <!-- Jika Pernah -->
                                    <?php if ($inside_detail['show_date']) { ?>
                                        <input type="date" name="<?= $inside_detail['id_code'] ?>_value"
                                            class="form-control mt-2"
                                            placeholder="Lewati jika lupa tanggal"
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?><?php else: ?>disabled<?php endif; ?>
                                            value="<?= $savedData[$inside_detail['id_code']]['value'] ?? '' ?>">
                                    <?php } ?>

                                </div>
                            <?php endforeach; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>


                    <!-- FORM PAJANAN -->
                <?php } elseif (strpos($currentSection['title'], 'FAKTOR') !== false && $currentSection['type_form'] != "XD") { ?>
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

                <?php } else { ?>
                    <!-- FORM FISIK UMUM -->
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
                                    <?php echo isset($savedData[$detail['id_code']]['chx']) && $savedData[$detail['id_code']]['chx'] ? '' : 'disabled'; ?>
                                    value="<?php echo $savedData[$detail['id_code']]['value'] ?? ''; ?>">
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
    <script>
        document.getElementById('medicalForm').addEventListener('submit', function(event) {
            // Prevent the form from submitting immediately
            event.preventDefault();

            // Collect form data
            const formData = new FormData(this);
            const formObject = {};

            // Convert FormData to a plain object
            formData.forEach((value, key) => {
                formObject[key] = value;
            });

            // Log the form data to the console
            console.log("Form Data (POST):", formObject);

            // Submit the form after logging
            this.submit();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var checkboxes = document.querySelectorAll("input[type='checkbox'][data-group]");
            var groups = {};

            // Group checkboxes by their data-group
            checkboxes.forEach(function(checkbox) {
                var group = checkbox.getAttribute('data-group');
                if (!groups[group]) {
                    groups[group] = {
                        checkboxes: [],
                        textInputs: []
                    };
                }
                groups[group].checkboxes.push(checkbox);

                // Find the corresponding text input
                var textInputName = checkbox.name.replace('_chx', '_value');
                var textInput = document.querySelector("input[name='" + textInputName + "']");
                if (textInput) {
                    groups[group].textInputs.push({
                        checkbox: checkbox,
                        textInput: textInput
                    });
                }
            });

            // Add change event listener to each checkbox
            Object.keys(groups).forEach(function(group) {
                groups[group].checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        // Uncheck other checkboxes in the same group
                        groups[group].checkboxes.forEach(function(cb) {
                            if (cb !== checkbox) {
                                cb.checked = false;
                            }
                        });

                        // Disable all text inputs in the group
                        groups[group].textInputs.forEach(function(pair) {
                            pair.textInput.disabled = true;
                            pair.textInput.value = '';
                        });

                        // If the current checkbox is checked, enable its text input
                        if (checkbox.checked) {
                            var correspondingTextInput = groups[group].textInputs.find(function(pair) {
                                return pair.checkbox === checkbox;
                            });
                            if (correspondingTextInput) {
                                correspondingTextInput.textInput.disabled = false;
                            }
                        }
                    });
                });
            });

        });
    </script>
    <script>
        // Enable/disable text input based on checkbox selection
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const textInput = this.nextElementSibling;
                if (textInput && textInput.type === 'text') {
                    if (this.checked) {
                        textInput.disabled = false;
                    } else {
                        textInput.disabled = true;
                        textInput.value = '';
                    }
                }
            });

            // Trigger change event on page load to set initial state
            checkbox.dispatchEvent(new Event('change'));
        });
    </script>
</body>

</html>