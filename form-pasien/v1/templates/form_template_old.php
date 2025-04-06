<!--  
  * Form Template
  * dicocokan currentSectionn title
  -->

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
        <h3 class="text-center mb-4">Form Pemeriksaan Fisik</h3>
        <small class="mb-2">
            * Isikan sesuai dengan kondisi anda sebenar-benarnya <br>
            * Jika tidak memiliki keluhan/penyakit silakan kosongi pilihan tersebut <br>
            * Jika ada, isi <i>Keterangan</i> dengan informasi tambahan seperti 'sudah 2 hari', 'sudah pengobatan', 'penyakit keturunan', dll
        </small>
        <form method="POST" action="" id="medicalForm" class="mt-2">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="my-0"><?php echo $currentSection['title']; ?></h6>
                </div>

                <!-- FORM RIWAYAT PENYAKIT type_form XVS-->
                <?php if ($currentSection['title'] == 'RIWAYAT PENYAKIT') { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6><?php echo $detail['name']; ?></h6>
                                <!-- <small style="color: red;"> <?php // echo $detail['caption'] 
                                                                    ?></small> -->
                            </div>
                            <?php foreach ($detail['details'] as $inside_detail): ?>
                                <!-- Lewati dulu jika color='red' -->
                                <?php if (isset($inside_detail['color']) && $inside_detail['color'] == 'red') {
                                    continue;
                                } ?>

                                <div class="mb-2">
                                    <label class="form-check-label" <?php if (isset($inside_detail['color'])): ?>
                                        style="color: <?php echo $inside_detail['color']; ?>"
                                        <?php endif; ?>>
                                        <?php echo $inside_detail['label']; ?>
                                    </label>
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

                    <!-- type_form XO -->
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
                                            <?php if (isset($savedData[$detail['id_code']]['options'][0]['selected']) && $savedData[$detail['id_code']]['options'][0]['selected']): ?>checked<?php endif; ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $detail['id_code']; ?>_ibu_chx"
                                            class="form-check-input border-black"
                                            <?php if (isset($savedData[$detail['id_code']]['options'][1]['selected']) && $savedData[$detail['id_code']]['options'][1]['selected']): ?>checked<?php endif; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- type_form XVS -->
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

                    <!-- FORM PAJANAN RIWAYAT IMUNISASI type_form XD-->
                <?php } elseif ($currentSection['title'] == 'RIWAYAT IMUNISASI' || $currentSection['type_form'] == "XD") { ?>
                    <div class="card-body">
                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <div class="row mt-1 text-center">
                                <h6><?= $detail['label'] ?></h6>
                            </div>
                            <?php foreach ($detail['details'] as $inside_detail): ?>
                                <div class="mb-2 row d-flex flex-row justify-content-between">
                                    <div class="col-auto">
                                        <input type="checkbox"
                                            name="<?= $inside_detail['id_code'] ?>_chx"
                                            class="form-check-input border-black"
                                            data-group="<?= $detail['label'] ?>"
                                            <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?>checked<?php endif; ?>>
                                        <label class="form-check-label"><?= $inside_detail['label'] ?></label>
                                    </div>

                                    <!-- Jika Pernah -->
                                    <?php if ($inside_detail['show_date']) { ?>
                                        <div class="col-md-4">
                                            <input type="date" name="<?= $inside_detail['id_code'] ?>_value"
                                                class="form-control mt-2"
                                                placeholder="Lewati jika lupa tanggal"
                                                <?php if (isset($savedData[$inside_detail['id_code']]['chx']) && $savedData[$inside_detail['id_code']]['chx']): ?><?php else: ?>disabled<?php endif; ?>
                                                value="<?= $savedData[$inside_detail['id_code']]['value'] ?? '' ?>">
                                        </div>
                                    <?php } ?>

                                </div>
                            <?php endforeach; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>


                    <!-- FORM PAJANAN type form XVV-->
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
                    <!-- FORM FISIK UMUM type_form XV-->
                    <div class="card-body">
                        <?php if (isset($currentSection['subtitle'])): ?>
                            <!-- <small style="color: red;"><?php //echo $currentSection['subtitle']; 
                                                            ?></small> -->
                        <?php endif; ?>

                        <?php foreach ($currentSection['details'] as $detail): ?>
                            <!-- Jika ada color red (diisi dokter, lewati dulu) -->
                            <?php if (isset($detail['color']) && $detail['color'] == 'red') {
                                continue;
                            } ?>

                            <div class="mb-2">
                                <label class="form-check-label" <?php if (isset($detail['color'])): ?>
                                    style="color: <?php echo $detail['color']; ?>"
                                    <?php endif; ?>>
                                    <?php echo $detail['label']; ?>
                                </label>
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
                    <button type="submit" name="action" value="previous" class="btn btn-secondary">Previous Page</button>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if (array_search($currentSectionKey, array_keys($sections)) < count($sections) - 1): ?>
                    <button type="submit" name="action" value="next" class="btn btn-primary">Next Page</button>
                <?php else: ?>
                    <button type="submit" name="action" value="submit" class="btn btn-success">Submit</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <script src="assets/scripts.js"></script>
    <script>
        console.log("Session Data:", <?php echo json_encode($_SESSION ?? []); ?>);
    </script>
</body>

</html>