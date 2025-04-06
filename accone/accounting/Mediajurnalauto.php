<?php
class Mediajurnalauto extends MY_Controller
{
    var $db;

    public function index()
    {
        echo "INJECT AUTO COUNT MEDIA JURNAL CABANG PER TANGGAL";
    }

    // * DIAKSES LANGSUNG DARI LUAR
    // * Kalau error continue tapi masukkin ke jurnal_error

    public function insertSales($branchCode, $searchDate)
    {
        try {
            $jurnalNo = $this->input->get('jurnalno', true) ?? "";
            $isRegen = $this->input->get('isregen', true) ?? false;

            // Validasi tanggal
            $date = date('Y-m-d', strtotime($searchDate));
            if ($date != $searchDate) {
                $this->sys_error("Tanggal tidak valid. Format: YYYY-MM-DD");
                exit;
            }
            if ($date > date('Y-m-d')) {
                $this->sys_error("Tanggal tidak boleh lebih dari hari ini");
                exit;
            }

            // Cek kalau API insert di HIT 2x
            if (strlen($jurnalNo) == 0 || !$isRegen) {
                $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'SALES');
                if ($existJurnal) {
                    $noCab = $existJurnal->jurnalNo;
                    $noReg = substr($noCab, 0, -3);
                    $this->sys_error("Sudah ada jurnal di Jurnal Sales Cabang No: {$noCab} dan Jurnal Sales Regional No: {$noReg}");
                    exit;
                }
            }
            // Cek Panjang $jurnalNo. Jika == 16 maka Regional. Contoh: ACSL/202502/0002
            else if (strlen($jurnalNo) == 16 && $isRegen) {
                // Get BranchCode from JurnalNo
                $regBranchCode = $this->getBranchCodeRegenSalesReg($jurnalNo);
                if ($regBranchCode == null) {
                    $this->sys_error("Tidak ditemukan jurnal cabang untuk di regenerate dari Jurnal Regional Nomor {$jurnalNo}");
                    exit;
                }
                $branchCode = &$regBranchCode;
                $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'SALESREG', $jurnalNo);
            }
            // Regional generate cabang
            else if (strlen($jurnalNo) == 19 && $isRegen && $branchCode == "ZZ") {
                // Get BranchCode from JurnalNo
                $regBranchCode = substr($jurnalNo, -2);
                if ($regBranchCode == null) {
                    $this->sys_error("Tidak ditemukan jurnal cabang untuk di regenerate dari Jurnal Regional Nomor {$jurnalNo}");
                    exit;
                }
                $branchCode = &$regBranchCode;
                $jurnalNo = substr($jurnalNo, 0, -3);
                $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'SALESREG', $jurnalNo);
            }
            // Jika == 19 maka Cabang. Contoh: ACSL/202502/0002/BB. Cabang generate cabang
            else if (strlen($jurnalNo) == 19 && $isRegen && $branchCode != "ZZ") {
                $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'SALESCAB', $jurnalNo);
            } else {
                $this->sys_error("Jurnal No tidak valid. Jangan ngaco yaa..");
                exit;
            }

            // if isRegen true, but existJurnal is empty
            if ($isRegen && !$existJurnal) {
                $this->sys_error("Tidak ada jurnal yang bisa di regenerate");
                exit;
            }

            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            $this->db->trans_start($isDebug == 'true');

            if ($isRegen) {
                foreach ($existJurnal as $jurnal) {
                    $jurnalID = $jurnal->jurnalID;
                    $this->deleteJurnal($jurnalID);
                }
            }

            $ipBranch = $this->getIpBranch($branchCode);
            $url = "http://$ipBranch/one-api/keu/Acc_one/sales/{$searchDate}/json";

            // 1. Get JSON from cabang
            $raws = json_decode($this->getJsonCabang($url));
            if ($raws['status'] == 'ERROR') {
                $this->db->trans_rollback();
                $this->sys_error("Gagal untuk mendapat JSON Cabang {$branchCode} di {$ipBranch}" . $raws['message']);
                exit;
            }
            $rawsCopy = array_map(function ($obj) {
                return clone $obj;
            }, $raws);

            // 2. Mapping untuk Regional
            $detailsReg = [];
            $balancesReg = [
                'totalDebit' => 0,
                'totalKredit' => 0,
                'totalBalance' => 0
            ];
            [$detailsReg, $balancesReg, $errMapReg] = $this->mappingSalesRegional($raws, $branchCode);

            // 3. Compose info jurnal regional
            $jurnalInfoReg = $this->composeJurnalInfo($branchCode, $searchDate, 'SALESREG');
            $jurnalRegNo = $jurnalInfoReg['jurnalNo'];

            // 4. Insert Regional
            [$jurnalIDReg, $errInsertReg] = $this->injectJurnal($jurnalInfoReg, $detailsReg);

            // 8. Combines Errors allow duplicate
            $jurnalErrReg = array_merge($errMapReg, $errInsertReg);
            $this->injectErrors($jurnalErrReg, $jurnalIDReg);

            // 5. Mapping cabang
            $detailsCab = [];
            $balancesCab = [
                'totalDebit' => 0,
                'totalKredit' => 0,
                'totalBalance' => 0
            ];

            [$detailsCab, $balancesCab, $errMapCab] = $this->mappingSalesCabang($rawsCopy, $jurnalRegNo);

            // 6. Compose jurnal info
            $jurnalInfoCab = $this->composeJurnalInfo($branchCode, $searchDate, 'SALESCAB');
            $jurnalInfoCab['jurnalNo'] = $jurnalRegNo . "/{$branchCode}"; // * Khusus Auto Count Sales

            // 7. Inject Cabang
            [$jurnalIDCab, $errInsertCab] = $this->injectJurnal($jurnalInfoCab, $detailsCab);
            $jurnalInfoCab['jurnalID'] = $jurnalIDCab;

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed to insert {$jurnalInfoCab['jurnalTitle']} : ", $this->db);
                exit;
            }

            // 8. Combines Errors allow duplicate
            $jurnalErrCab = array_merge($errMapCab, $errInsertCab);
            $this->injectErrors($jurnalErrCab, $jurnalIDCab);

            $this->db->trans_complete();

            if ($isRegen) {
                $msg = "Berhasil regenerate jurnal {$jurnalInfoCab['jurnalTitle']}";
            } else {
                $msg = "Berhasil inject jurnal {$jurnalInfoCab['jurnalTitle']}";
            }

            $this->sys_ok([
                'msg' => $msg,
                'balancesReg' => $balancesReg,
                'balancesCab' => $balancesCab,
                'jurnalInfoReg' => $jurnalInfoReg,
                'jurnalInfoCab' => $jurnalInfoCab,
                'jurnalErrorsReg' => $jurnalErrReg,
                'jurnalErrorsCab' => $jurnalErrCab,
                'detailsReg' => $detailsReg,
                'detailsCab' => $detailsCab,
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }


    public function insertAr($branchCode, $searchDate, $isRegen = "", $jurnalID = "")
    {
        try {
            // Validasi tanggal
            $date = date('Y-m-d', strtotime($searchDate));
            if ($date != $searchDate) {
                $this->sys_error("Tanggal tidak valid. Format: YYYY-MM-DD");
                exit;
            }
            if ($date > date('Y-m-d')) {
                $this->sys_error("Tanggal tidak boleh lebih dari hari ini");
                exit;
            }

            // Check if there is a jurnal in the same date
            $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'AR');
            if ($existJurnal) {
                $isRegen = true;
                $jurnalID = $existJurnal->jurnalID;
            }

            $jurnalInfo = [];

            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            if ($isDebug == 'true') {
                $this->db->trans_start(true); // (true) jika debug
            } else {
                $this->db->trans_start();
            }

            if ($isRegen) {
                $this->deleteJurnal($jurnalID);
            }

            $ipBranch = $this->getIpBranch($branchCode);
            $url = "http://$ipBranch/one-api/keu/Acc_one/ar/{$searchDate}/json";

            // 1. Get JSON from cabang
            $raws = json_decode($this->getJsonCabang($url));
            if ($raws['status'] == 'ERROR') {
                $this->db->trans_rollback();
                $this->sys_error("Gagal untuk mendapat JSON Cabang {$branchCode} di {$ipBranch}" . $raws['message']);
                exit;
            }

            // 2. Mapping each item
            [$details, $balances, $errMap] = $this->mappingAr($raws);

            // 3. Compose jurnal info
            $jurnalInfo = $this->composeJurnalInfo($branchCode, $searchDate, 'AR');

            // 4. Inject to DB
            [$jurnalID, $errInsert] = $this->injectJurnal($jurnalInfo, $details);
            $jurnalInfo['jurnalID'] = $jurnalID;

            if ($this->db->trans_status() === FALSE) {
                var_dump($this->db);
                $this->sys_error_db("Failed to insert {$jurnalInfo['jurnalTitle']} : ", $this->db);
                exit;
            }

            // 5. Combines Errors allow duplicate
            $jurnalErr = array_merge($errMap, $errInsert);
            $this->injectErrors($jurnalErr, $jurnalID);

            $this->db->trans_complete();

            if ($isRegen) {
                $msg = "Berhasil regenerate jurnal {$jurnalInfo['jurnalTitle']}";
            } else {
                $msg = "Berhasil inject jurnal {$jurnalInfo['jurnalTitle']}";
            }

            $this->sys_ok([
                'msg' => $msg,
                'balances' => $balances,
                'jurnalErrors' => $jurnalErr,
                'jurnalInfo' => $jurnalInfo,
                'details' => $details,
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function insertArPayment($branchCode, $searchDate, $isRegen = "", $jurnalID = "")
    {
        try {
            // Validasi tanggal
            $date = date('Y-m-d', strtotime($searchDate));
            if ($date != $searchDate) {
                $this->sys_error("Tanggal tidak valid. Format: YYYY-MM-DD");
                exit;
            }
            if ($date > date('Y-m-d')) {
                $this->sys_error("Tanggal tidak boleh lebih dari hari ini");
                exit;
            }

            // Check if there is a jurnal in the same date
            $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'ARPAYMENT');
            if ($existJurnal) {
                $isRegen = true;
                $jurnalID = $existJurnal->jurnalID;
            }

            $jurnalInfo = [];

            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            if ($isDebug == 'true') {
                $this->db->trans_start(true); // (true) jika debug
            } else {
                $this->db->trans_start();
            }

            if ($isRegen) {
                $this->deleteJurnal($jurnalID);
            }

            $ipBranch = $this->getIpBranch($branchCode);
            $url = "http://$ipBranch/one-api/keu/Acc_one/arPayment/{$searchDate}/json";

            // 1. Get JSON from cabang
            $raws = json_decode($this->getJsonCabang($url));
            if ($raws['status'] == 'ERROR') {
                $this->db->trans_rollback();
                $this->sys_error("Gagal untuk mendapat JSON Cabang {$branchCode} di {$ipBranch}" . $raws['message']);
                exit;
            }

            // 2. Mapping each item
            [$details, $balances, $errMap] = $this->mappingArPayment($raws);

            // 3. Compose jurnal info
            $jurnalInfo = $this->composeJurnalInfo($branchCode, $searchDate, 'ARPAYMENT');

            // 4. Inject to DB
            [$jurnalID, $errInsert] = $this->injectJurnal($jurnalInfo, $details);
            $jurnalInfo['jurnalID'] = $jurnalID;

            if ($this->db->trans_status() === FALSE) {
                // Print last query
                var_dump($this->db->last_query());
                $this->sys_error_db("Failed to insert {$jurnalInfo['jurnalTitle']} : ", $this->db);
                exit;
            }

            // 5. Combines Errors allow duplicate
            $jurnalErr = array_merge($errMap, $errInsert);
            $this->injectErrors($jurnalErr, $jurnalID);

            $this->db->trans_complete();

            $lenRaws = count($raws);
            $lenMap = count($details);

            if ($isRegen) {
                $msg = "Berhasil regenerate jurnal {$jurnalInfo['jurnalTitle']}";
            } else {
                $msg = "Berhasil inject jurnal {$jurnalInfo['jurnalTitle']}";
            }

            $this->sys_ok([
                'msg' => $msg,
                'balances' => $balances,
                'jurnalErrors' => $jurnalErr,
                'jurnalInfo' => $jurnalInfo,
                'details' => $details,
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function insertRkTagihan($branchCode, $searchDate, $isRegen = "", $jurnalID = "")
    {
        try {
            // Validasi tanggal
            $date = date('Y-m-d', strtotime($searchDate));
            if ($date != $searchDate) {
                $this->sys_error("Tanggal tidak valid. Format: YYYY-MM-DD");
                exit;
            }
            if ($date > date('Y-m-d')) {
                $this->sys_error("Tanggal tidak boleh lebih dari hari ini");
                exit;
            }

            // Check if there is a jurnal in the same date
            $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'RKTAGIHAN');
            if ($existJurnal) {
                $isRegen = true;
                $jurnalID = $existJurnal->jurnalID;
            }

            $jurnalInfo = [];

            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            if ($isDebug == 'true') {
                $this->db->trans_start(true); // (true) jika debug
            } else {
                $this->db->trans_start();
            }

            if ($isRegen) {
                $this->deleteJurnal($jurnalID);
            }

            $ipBranch = $this->getIpBranch($branchCode);
            $url = "http://$ipBranch/one-api/keu/Acc_one/arPaymentRkTagihan/{$searchDate}/json";

            // 1. Get JSON from cabang
            $raws = json_decode($this->getJsonCabang($url));
            if ($raws['status'] == 'ERROR') {
                // Print last query
                var_dump($this->db->last_query());
                $this->sys_error("Gagal untuk mendapat JSON Cabang {$branchCode} di {$ipBranch}" . $raws['message']);
                exit;
            }

            // 2. Mapping each item
            [$details, $balances, $errMap] = $this->mappingArPayRkTagihan($raws);

            // 3. Compose jurnal info
            $jurnalInfo = $this->composeJurnalInfo($branchCode, $searchDate, 'RKTAGIHAN');

            // 4. Inject to DB
            [$jurnalID, $errInsert] = $this->injectJurnal($jurnalInfo, $details);
            $jurnalInfo['jurnalID'] = $jurnalID;

            if ($this->db->trans_status() === FALSE) {
                $this->sys_error_db("Failed to insert {$jurnalInfo['jurnalTitle']} : ", $this->db);
                exit;
            }

            // 5. Combines Errors allow duplicate
            $jurnalErr = array_merge($errMap, $errInsert);
            $this->injectErrors($jurnalErr, $jurnalID);

            $this->db->trans_complete();

            $lenRaws = count($raws);
            $lenMap = count($details);

            if ($isRegen) {
                $msg = "Berhasil regenerate jurnal {$jurnalInfo['jurnalTitle']}";
            } else {
                $msg = "Berhasil inject jurnal {$jurnalInfo['jurnalTitle']}";
            }

            $this->sys_ok([
                'msg' => $msg,
                'balances' => $balances,
                'jurnalErrors' => $jurnalErr,
                'jurnalInfo' => $jurnalInfo,
                'details' => $details,
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function insertRkPelunasan($branchCode, $searchDate, $isRegen = "", $jurnalID = "")
    {
        try {
            // Validasi tanggal
            $date = date('Y-m-d', strtotime($searchDate));
            if ($date != $searchDate) {
                $this->sys_error("Tanggal tidak valid. Format: YYYY-MM-DD");
                exit;
            }
            if ($date > date('Y-m-d')) {
                $this->sys_error("Tanggal tidak boleh lebih dari hari ini");
                exit;
            }

            // Check if there is a jurnal in the same date
            $existJurnal = $this->checkExistingJurnal($branchCode, $searchDate, 'RKPELUNASAN');
            if ($existJurnal) {
                $isRegen = true;
                $jurnalID = $existJurnal->jurnalID;
            }

            $jurnalInfo = [];

            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            if ($isDebug == 'true') {
                $this->db->trans_start(true); // (true) jika debug
            } else {
                $this->db->trans_start();
            }

            if ($isRegen) {
                $this->deleteJurnal($jurnalID);
            }

            $ipBranch = $this->getIpBranch($branchCode);
            $url = "http://$ipBranch/one-api/keu/Acc_one/arPaymentRkPelunasan/{$searchDate}/json";

            // 1. Get JSON from cabang
            $raws = json_decode($this->getJsonCabang($url));
            if ($raws['status'] == 'ERROR') {
                $this->db->trans_rollback();
                $this->sys_error("Gagal untuk mendapat JSON Cabang {$branchCode} di {$ipBranch}" . $raws['message']);
                exit;
            }

            // 2. Mapping each item
            [$details, $balances, $errMap] = $this->mappingArPayRkPelunasan($raws);

            // 3. Compose jurnal info
            $jurnalInfo = $this->composeJurnalInfo($branchCode, $searchDate, 'RKPELUNASAN');

            // 4. Inject to DB
            [$jurnalID, $errInsert] = $this->injectJurnal($jurnalInfo, $details);
            $jurnalInfo['jurnalID'] = $jurnalID;

            if ($this->db->trans_status() === FALSE) {
                $this->sys_error_db("Failed to insert {$jurnalInfo['jurnalTitle']} : ", $this->db);
                exit;
            }

            // 5. Combines Errors allow duplicate
            $jurnalErr = array_merge($errMap, $errInsert);
            $this->injectErrors($jurnalErr, $jurnalID);

            $this->db->trans_complete();

            $lenRaws = count($raws);
            $lenMap = count($details);

            if ($isRegen) {
                $msg = "Berhasil regenerate jurnal {$jurnalInfo['jurnalTitle']}";
            } else {
                $msg = "Berhasil inject jurnal {$jurnalInfo['jurnalTitle']}";
            }

            $this->sys_ok([
                'msg' => $msg,
                'balances' => $balances,
                'jurnalErrors' => $jurnalErr,
                'jurnalInfo' => $jurnalInfo,
                'details' => $details,
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function injectMapBank($regID)
    {
        try {
            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            if ($isDebug == 'true') {
                $this->db->trans_start(true);
            } else {
                $this->db->trans_start();
            }

            $sql = "SELECT M_BranchCode, M_BranchIPAddress, M_BranchName                
                FROM m_branch
                WHERE M_BranchIsActive = 'Y'
                AND M_BranchS_RegionalID = ? ORDER BY M_BranchCode ASC";
            $qry = $this->db->query($sql, [$regID]);
            if (!$qry) {
                $this->sys_error_db("Tidak ditemukan cabang yang aktif", $this->db);
                exit;
            }
            $rst = $qry->result();
            $errMap = [];
            $inserted = [];
            $existData = [];

            foreach ($rst as $row) {
                $ipBranch = $row->M_BranchIPAddress;
                $url = "http://$ipBranch/one-api/keu/Acc_one/getBanks";

                // 1. Get JSON from cabang
                $raws = json_decode($this->getJsonCabang($url));
                if ($raws['status'] == 'ERROR') {
                    $errMap[] = [
                        "err_type" => "Get JSON Cabang {$ipBranch}",
                        "err_msg" => $raws['message']
                    ];
                    continue;
                }

                // 2. Mapping each item
                [$uniqueRows, $err, $exist] = $this->insertMapBank($raws);
                $inserted = array_merge($inserted, $uniqueRows);
                $errMap = array_merge($errMap, $err);
                $existData = array_merge($existData, $exist);
            }

            $sql = "SELECT S_RegionalName FROM s_regional WHERE S_RegionalID = ?";
            $qry = $this->db->query($sql, [$regID]);
            $regName = $qry->row()->S_RegionalName;
            $this->db->trans_complete();

            $this->sys_ok([
                'msg' => "Berhasil inject mapping bank Regional {$regName}",
                'branches' => $rst,
                'exist_data' => $existData,
                'failed_row' => $errMap,
                'total_success_row' => count($inserted),
                'inserted_rows' => $inserted
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function injectMapBankCab($branchCode)
    {
        try {
            $isDebug = $this->sys_input['isDebug'] ? $this->sys_input['isDebug'] : 'false';
            if ($isDebug == 'true') {
                $this->db->trans_start(true);
            } else {
                $this->db->trans_start();
            }

            $sql = "SELECT M_BranchCode, M_BranchIPAddress, M_BranchName                
                FROM m_branch
                WHERE M_BranchIsActive = 'Y'
                AND M_BranchCode = ? ORDER BY M_BranchCode ASC";
            $qry = $this->db->query($sql, [$branchCode]);
            if (!$qry) {
                $this->sys_error_db("Tidak ditemukan cabang yang aktif", $this->db);
                exit;
            }
            $rst = $qry->row();
            $errMap = [];
            $inserted = [];
            $existData = [];


            $ipBranch = $rst->M_BranchIPAddress;
            $url = "http://$ipBranch/one-api/keu/Acc_one/getBanks";

            // 1. Get JSON from cabang
            $raws = json_decode($this->getJsonCabang($url));
            if ($raws['status'] == 'ERROR') {
                $errMap[] = [
                    "err_type" => "Get JSON Cabang {$ipBranch}",
                    "err_msg" => $raws['message']
                ];
            }

            // 2. Mapping each item
            [$uniqueRows, $err, $exist] = $this->insertMapBank($raws);
            $inserted = array_merge($inserted, $uniqueRows);
            $errMap = array_merge($errMap, $err);
            $existData = array_merge($existData, $exist);


            $branchName = $qry->row()->M_BranchName;
            $this->db->trans_complete();

            $this->sys_ok([
                'msg' => "Berhasil inject mapping bank Cabang {$branchName}",
                'branches' => $rst,
                'exist_data' => $existData,
                'failed_row' => $errMap,
                'total_success_row' => count($inserted),
                'inserted_rows' => $inserted
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function injectMapNatSubGroup()
    {
        // Goal: Insert CoaID and CoaDesc to map_nat_subgroup from coa table based MapNatSub_PendapatanCoaAccountNo
        $this->db->trans_start();

        $sql = "SELECT MapNatSub_ID, MapNatSub_PendapatanCoaAccountNo, coaID, coaDescription
            FROM map_nat_subgroup
            JOIN coa ON coaAccountNo = MapNatSub_PendapatanCoaAccountNo
            ORDER BY MapNatSub_ID ASC";
        $qry = $this->db->query($sql);
        if (!$qry) {
            $this->sys_error_db("Tidak ditemukan data map_nat_subgroup", $this->db);
            exit;
        }
        $rst = $qry->result();

        // sql for update
        $sql = "UPDATE map_nat_subgroup
            SET MapNatSub_PendapatanCoaID = ?, MapNatSub_PendapatanCoaDescription = ?
            WHERE MapNatSub_ID = ?";

        foreach ($rst as $item) {
            $qry = $this->db->query($sql, [$item->coaID, $item->coaDescription, $item->MapNatSub_ID]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal insert data map_nat_subgroup", $this->db);
                exit;
            }
        }

        // Jika tidak ada error return OK
        if ($this->db->trans_status() === FALSE) {
            $this->sys_error_db("Gagal insert data map_nat_subgroup", $this->db);
            exit;
        }

        $this->db->trans_complete();
        $this->sys_ok("Berhasil insert data map_nat_subgroup");
    }

    public function injectMapBankByCoaNo()
    {
        $this->db->trans_start();
        // NON EDC
        $sql = "SELECT MapBank_ID, MapBank_CoaAccountNo, coaID, coaDescription
            FROM map_bank_coa
            JOIN coa ON coaAccountNo = MapBank_CoaAccountNo
            ORDER BY MapBank_ID ASC";
        $qry = $this->db->query($sql);
        if (!$qry) {
            $this->sys_error_db("Tidak ditemukan data map_nat_subgroup", $this->db);
            exit;
        }
        $rst = $qry->result();

        $sql = "UPDATE map_bank_coa
            SET MapBank_CoaID = ?, MapBank_CoaDescription = ?
            WHERE MapBank_ID = ?";

        foreach ($rst as $item) {
            $qry = $this->db->query($sql, [$item->coaID, $item->coaDescription, $item->MapBank_ID]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal insert data mapBank", $this->db);
                exit;
            }
        }

        // EDC
        $sql = "SELECT MapBank_ID, MapBank_EDC_CoaAccountNo, coaID, coaDescription
            FROM map_bank_coa
            JOIN coa ON coaAccountNo = MapBank_EDC_CoaAccountNo
            ORDER BY MapBank_ID ASC";
        $qry = $this->db->query($sql);
        if (!$qry) {
            $this->sys_error_db("Tidak ditemukan data map_nat_subgroup", $this->db);
            exit;
        }
        $rst = $qry->result();

        $sql = "UPDATE map_bank_coa
            SET MapBank_EDC_CoaID = ?, MapBank_EDC_CoaDescription = ?
            WHERE MapBank_ID = ?";

        foreach ($rst as $item) {
            $qry = $this->db->query($sql, [$item->coaID, $item->coaDescription, $item->MapBank_ID]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal insert data mapBank EDC", $this->db);
                exit;
            }
        }
        // Jika tidak ada error return OK
        if ($this->db->trans_status() === FALSE) {
            $this->sys_error_db("Gagal insert data map bank", $this->db);
            exit;
        }

        $this->db->trans_complete();
        $this->sys_ok("Berhasil insert data map_nat_subgroup");
    }

    public function injectMapRkCabang()
    {
        $sql = "INSERT INTO map_rk_cabang 
        (Map_RkCabang_Rk_TypeID, Map_RkCabang_BranchCode, Map_RkCabang_SRegionalID, 
        Map_RkCabang_CoaID, Map_RkCabang_CoaAccountNo, Map_RkCabang_CoaDescription)
        VALUES 
        ('RUJ', 'BA', 8, 674,	'3160400001',	'R/K CAB. MATRAMAN - RUJUKAN'),
        ('KEU', 'BA', 8, 675,	'3160400002',	'R/K CAB. MATRAMAN - KEUANGAN'),
        ('STOK', 'BA', 8, 677,	'3160400004',	'R/K CAB. MATRAMAN - PERSEDIAAN'),
        ('AKTIVA', 'BA', 8, 678,	'3160400005',	'R/K CAB. MATRAMAN - AKTIVA,ASSET,PERALATAN & PERLENGKAPAN'),
        ('POINT', 'BA', 8, 679,	'3160400006',	'R/K CAB. MATRAMAN - POINT REWARD PELANGGAN'),

        ('RUJ', '', 8, 828,	'3169200001',	'R/K REGIONAL JAKARTA - RUJUKAN'),
        ('KEU', '', 8, 829,	'3169200002',	'R/K REGIONAL JAKARTA - KEUANGAN'),
        ('ARPAY', '', 8, 830,	'3169200003',	'R/K REGIONAL JAKARTA - PELUNASAN PIUTANG'),
        ('STOK', '', 8, 831,	'3169200004',	'R/K REGIONAL JAKARTA - PERSEDIAAN'),
        ('AKTIVA', '', 8, 832,	'3169200005',	'R/K REGIONAL JAKARTA - AKTIVA,PERALATAN & PERLENGKAPAN'),
        ('POINT', '', 8, 832,	'3169200005',	'R/K REGIONAL JAKARTA - AKTIVA,PERALATAN & PERLENGKAPAN')
        ";
    }

    /* 
        * ------------------------*
        * PRIVATE HELPER FUNCTION *
        * ------------------------*
    */

    private function getIpBranch($branchCode)
    {
        $sql = "SELECT M_BranchName, M_BranchIPAddress                
                FROM m_branch 
                WHERE M_BranchIsActive = 'Y' AND M_BranchCode = ? LIMIT 1";
        $qry = $this->db->query($sql, [$branchCode]);
        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Tidak ditemukan cabang dengan kode {$branchCode}. Tidak bisa membuat jurnal", $this->db);
            exit;
        }
        $rst = $qry->row();
        $ipBranch = $rst->M_BranchIPAddress;
        return $ipBranch;
    }

    private function getJsonCabang($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        // Cek resp
        if ($resp === false) {
            $message = "Curl error: " . curl_error($curl);
            $this->sys_error($message);
            exit;
        }

        $resp = json_decode($resp, true);

        if ($resp['status'] == 'OK') {
            $data = $resp['data'];
            return json_encode($data);
        } else {
            $message = $resp['message'];
            return [
                'status' => 'ERROR',
                'message' => $message
            ];
        }
    }

    private function mappingSalesRegional($raws, $branchCode)
    {
        $details = [];
        $balances = [
            'totalDebit' => 0,
            'totalKredit' => 0,
            'totalBalance' => 0
        ];
        $errMap = [];

        foreach ($raws as $r) {
            // 1. Hanya ambil yang Bank Non EDC untuk Debit dengan CoA Bank
            // ? Apakah perlu cek $r->NatBankIsEDC = 'N' ?
            if ($r->M_BankAccountNo != "" && $r->M_BankAccountNo != "EDC") {
                $sql = "SELECT MapBank_CoaID, MapBank_CoaAccountNo, MapBank_CoaDescription
                    FROM map_bank_coa 
                    WHERE MapBank_BankAccountNo LIKE CONCAT('%',$r->M_BankAccountNo, '%') 
                    AND MapBank_IsActive = 'Y'
                    AND MapBank_BranchCode	= ?
                    AND MapBank_NatBankIsEDC = 'N'
                    ORDER BY MapBank_ID DESC LIMIT 1";
                $qry = $this->db->query($sql, [$r->BranchCode]);
                if ($qry->row() == null) {
                    continue;
                }

                $coaID = $qry->row()->MapBank_CoaID;
                $coaAccNo = $qry->row()->MapBank_CoaAccountNo;
                $coaDescription = $qry->row()->MapBank_CoaDescription;

                if ($coaID == null || $coaAccNo == null || $coaID == '' || $coaAccNo == '') {
                    $errMap[] = [
                        "err_type" => "Mapping Sales",
                        "err_msg" => "Mapping Bank Account Number di Regional untuk Cabang {$r->BranchCode} tidak ditemukan. Norek: {$r->M_BankAccountNo} | Ref: {$r->Ref} || Debit: {$r->Debit} || Kredit: {$r->Kredit}"
                    ];
                    continue;
                }

                $r->coaID = $coaID;
                $r->coaAccNo = $coaAccNo;
                $r->coaDescription = $coaDescription;
                $details[] = $r;

                $balances['totalDebit'] += $r->Debit;
                $balances['totalKredit'] += $r->Kredit;
                $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
            }
        }

        // 2. Jika ada Debit maka Insert 1 row Credit dengan CoA R/K Cabang dan Kreditnya sesuai dengan total Debit
        if ($balances['totalDebit'] > 0 && count($details) > 0) {
            $sql = "SELECT Map_RkCabang_CoaID, Map_RkCabang_CoaAccountNo, Map_RkCabang_CoaDescription
                FROM map_rk_cabang
            WHERE Map_RkCabang_Rk_TypeID = 3 
            AND Map_RkCabang_BranchCode = ? AND Map_RkCabang_IsActive = 'Y' LIMIT 1";
            $qry = $this->db->query($sql, [$branchCode]);
            if ($qry->row() == null) {
                $errMap[] = [
                    "err_type" => "Mapping Sales",
                    "err_msg" => "Mapping R/K Cabang tidak ditemukan. BranchCode: {$branchCode}"
                ];
                return [$details, $balances, $errMap];
            }
            $kredit = $balances['totalDebit'] - $balances['totalKredit'];
            $item = (object) [
                'Ref' => 'Kredit R/K Cabang ' . $branchCode . ' di Regional',
                'Note' => 'Kredit R/K Cabang ' . $branchCode . ' di Regional',
                'coaID' => $qry->row()->Map_RkCabang_CoaID,
                'coaAccNo' => $qry->row()->Map_RkCabang_CoaAccountNo,
                'coaDescription' => $qry->row()->Map_RkCabang_CoaDescription,
                'Debit' => 0,
                'Kredit' => $kredit
            ];
            $details[] = $item;
        }
        return [$details, $balances, $errMap];
    }

    private function mappingSalesCabang($raws, $jurnalRegNo)
    {
        try {
            $details = [];
            $balances = [
                'totalDebit' => 0,
                'totalKredit' => 0,
                'totalBalance' => 0
            ];
            $errMap = [];

            foreach ($raws as $raw) {
                $raw->Debit = round($raw->Debit, 2);
                $raw->Kredit = round($raw->Kredit, 2);

                // Handle Array Bank Map. Masuk Debit
                if (
                    isset($raw->M_BankAccountNo) && $raw->M_BankAccountNo != "" &&
                    strtolower($raw->M_PaymentTypeCode) != "regonline"
                ) {
                    // Cek jika EDC atau Non EDC dari Tabel map_bank_coa
                    $sql = "SELECT MapBank_NatBankIsEDC,
                    MapBank_EDC_CoaID, MapBank_EDC_CoaAccountNo,MapBank_EDC_CoaDescription,
                    MapBank_CoaID, MapBank_CoaAccountNo, MapBank_CoaDescription
                            FROM map_bank_coa 
                            WHERE MapBank_BankAccountNo LIKE CONCAT('%',?, '%')
                            AND MapBank_NatBankID = ?
                            AND MapBank_BranchCode = ?
                            AND MapBank_IsActive = 'Y'
                            ORDER BY MapBank_ID DESC LIMIT 1";
                    $qry = $this->db->query($sql, [$raw->M_BankAccountNo, $raw->Nat_BankID, $raw->BranchCode]);
                    if ($qry->row() == null) {
                        $errMap[] = [
                            "err_type" => "Mapping Sales",
                            "err_msg" => "Mapping Bank di Cabang {$raw->BranchCode} tidak ditemukan. Ref: {$raw->Ref} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit}"
                        ];
                        continue;
                    }
                    $isEdc = $qry->row()->MapBank_NatBankIsEDC;

                    // Jika EDC, pakai mapping piutang EDC
                    if ($isEdc == 'Y' || $isEdc == 'y') {
                        $raw->coaID = $qry->row()->MapBank_EDC_CoaID;
                        $raw->coaAccNo = $qry->row()->MapBank_EDC_CoaAccountNo;
                        $raw->coaDescription = $qry->row()->MapBank_EDC_CoaDescription;

                        // Jika salah satu dari coaID atau CoaAccNo kosong atau null maka error
                        if ($raw->coaID == 0 || $raw->coaID == '' || $raw->coaAccNo == null || $raw->coaAccNo == '') {
                            $errMap[] = [
                                "err_type" => "Mapping Sales",
                                "err_msg" => "Mapping Bank EDC di Cabang {$raw->BranchCode} tidak ditemukan. Ref: {$raw->Ref} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit}"
                            ];
                            continue;
                        }

                        $details[] = $raw;
                    }
                    // Jika Non EDC, pakai mapping R/K Regional dan bawa AddOn jurnalRegNo
                    else {
                        $sql = "SELECT Map_RkCabang_CoaID, Map_RkCabang_CoaAccountNo, Map_RkCabang_CoaDescription
                            FROM m_branch 
                            JOIN map_rk_cabang ON Map_RkCabang_SRegionalID = M_BranchS_RegionalID
                            WHERE M_BranchCode = ?
                            AND Map_RkCabang_Rk_TypeID = 3
                            AND (Map_RkCabang_BranchCode = '' OR Map_RkCabang_BranchCode IS NULL) 
                            LIMIT 1";
                        $qry = $this->db->query($sql, [$raw->BranchCode]);
                        if ($qry->row() == null) {
                            $errMap[] = [
                                "err_type" => "Mapping Sales",
                                "err_msg" => "Mapping R/K Regional Keu tidak ditemukan. Cabang {$raw->BranchCode}| Ref: {$raw->Ref} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit}"
                            ];
                            continue;
                        }
                        $raw->coaID = $qry->row()->Map_RkCabang_CoaID;
                        $raw->coaAccNo = $qry->row()->Map_RkCabang_CoaAccountNo;
                        $raw->coaDescription = $qry->row()->Map_RkCabang_CoaDescription;
                        $raw->addOns = [
                            [
                                'jurnalAddOnCode' => 'NOJURNALREG',
                                'jurnalAddOnValue' => $jurnalRegNo
                            ]
                        ];
                        $details[] = $raw;
                    }
                }

                // Handle regonline
                else if (stripos(strtolower($raw->M_PaymentTypeCode), 'regonline') !== false) {
                    $raw->coaAccNo = "2140000001";
                    $raw->coaID = 613;
                    $raw->coaDescription = "PENDAPATAN DITERIMA DIMUKA";
                    $details[] = $raw;
                }

                // Handle Cash/Tunai. Masuk Debit
                else if (stripos(strtolower($raw->M_PaymentTypeCode), 'cash') !== false || stripos($raw->Ref, 'tunai') !== false) {
                    $raw->coaAccNo = "1110100001";
                    $raw->coaID = 5;
                    $raw->coaDescription = "TUNAI";
                    $details[] = $raw;
                }

                // Handle Array Sales Nat Map Pendapatan (ada natgroup and natsubgroupID). Pasti Kredit
                else if (isset($raw->NatGroupID) && isset($raw->NatSubGroupID)) {
                    $natGroupID = $raw->NatGroupID;
                    $natSubGroupID = $raw->NatSubGroupID;
                    // Mapping Nat SubGroup -> pendapatan
                    $sql = "SELECT MapNatSub_PendapatanCoaAccountNo, coaID, coaDescription
                        FROM map_nat_subgroup 
                        JOIN coa ON coaAccountNo = MapNatSub_PendapatanCoaAccountNo  
                        WHERE MapNatSub_NatGroupID = ? AND MapNatSub_NatSubGroupID = ? 
                        AND MapNatSub_IsActive = 'Y' LIMIT 1";
                    $qry = $this->db->query($sql, [$natGroupID, $natSubGroupID]);
                    if ($qry->row() == null) {
                        $errMap[] = [
                            "err_type" => "Mapping",
                            "err_msg" => "Mapping NatSubGroup tidak ditemukan. NatGroupID: {$natGroupID} | NatSubGroupID: {$natSubGroupID} | Ref: {$raw->Ref} || Debit: {$raw->Debit} || Kredit: {$raw->Kredit}"
                        ];
                        continue;
                    }
                    $raw->coaAccNo = $qry->row()->MapNatSub_PendapatanCoaAccountNo;
                    $raw->coaID = $qry->row()->coaID;
                    $raw->coaDescription = $qry->row()->coaDescription;

                    // Cek if kredit >= debit valid
                    if ($raw->Kredit >= $raw->Debit) {
                        $raw->Debit = 0;
                        $raw->addOns = [
                            [
                                'jurnalAddOnCode' => 'OMZTYPE',
                                'jurnalAddOnValue' => $raw->M_OmzetTypeID
                            ],
                            [
                                'jurnalAddOnCode' => 'OMZNAME',
                                'jurnalAddOnValue' => $raw->M_OmzetTypeName
                            ]
                        ];
                    } else {
                        $errMap[] = [
                            "err_type" => "Mapping",
                            "err_msg" => "Kredit Sales lebih kecil dari Debit. Ref: {$raw->Ref} || Debit: {$raw->Debit} || Kredit: {$raw->Kredit}"
                        ];
                        continue;
                    }

                    $details[] = $raw;
                }

                // Handle Mapping Nat Group -> diskon. Hanya ada NatGroupID tanpa NatSubGroupID. Pasti Debit
                else if (stripos($raw->Ref, 'diskon') !== false) {
                    $sql = "SELECT MapNatGroup_Disc_coaID,
                            MapNatGroup_Disc_coaAccNo,
                            MapNatGroup_Disc_coaDesc
                    FROM map_nat_group 
                        WHERE MapNatGroup_NatGroupID = ? 
                        AND MapNatGroup_IsActive = 'Y'";
                    $qry = $this->db->query($sql, [$raw->NatGroupID]);
                    if ($qry->row() == null) {
                        $errMap[] = [
                            "err_type" => "Mapping",
                            "err_msg" => "Mapping NatGroup Diskon tidak ditemukan. NatGroupID: {$raw->NatGroupID} | Ref: {$raw->Ref} || Debit: {$raw->Debit} || Kredit: {$raw->Kredit}"
                        ];
                        continue;
                    }
                    $raw->coaAccNo = $qry->row()->MapNatGroup_Disc_coaAccNo;
                    $raw->coaID = $qry->row()->MapNatGroup_Disc_coaID;
                    $raw->coaDescription = $qry->row()->MapNatGroup_Disc_coaDesc;

                    $details[] = $raw;
                }

                // Handle Mapping Nat Group -> retur. Masuk Debit
                else if (stripos($raw->Ref, 'retur') !== false) {
                    $raw->Ref = substr($raw->Ref, strpos($raw->Ref, ' ') + 1);
                    $sql = "SELECT MapNatGroup_Retur_coaID,
                            MapNatGroup_Retur_coaAccNo,
                            MapNatGroup_Retur_coaDesc
                    FROM map_nat_group 
                        WHERE MapNatGroup_NatGroupID = ? 
                        AND MapNatGroup_IsActive = 'Y'";
                    $qry = $this->db->query($sql, [$raw->Ref]);
                    if ($qry->row() == null) {
                        $errMap[] = [
                            "err_type" => "Mapping",
                            "err_msg" => "Mapping NatGroup Retur tidak ditemukan. Ref: {$raw->Ref}"
                        ];
                        continue;
                    }
                    $raw->coaAccNo = $qry->row()->MapNatGroup_Disc_coaAccNo;
                    $raw->coaID = $qry->row()->MapNatGroup_Disc_coaID;
                    $raw->coaDescription = $qry->row()->MapNatGroup_Disc_coaDesc;

                    $details[] = $raw;
                }

                // Handle Mapping round
                else if (stripos($raw->Ref, 'round') !== false) {
                    $sql = "SELECT coaID, coaDescription FROM coa 
                    WHERE coaAccountNo = '1141100002' AND coaIsActive = 'Y'";
                    $qry = $this->db->query($sql);
                    if ($qry->row() == null) {
                        $errMap[] = [
                            "err_type" => "Mapping",
                            "err_msg" => "Mapping Round tidak ditemukan. Ref: {$raw->Ref}"
                        ];
                        continue;
                    }
                    $raw->coaAccNo = "1141100002";
                    $raw->coaID = $qry->row()->coaID;
                    $raw->coaDescription = $qry->row()->coaDescription;
                    $details[] = $raw;
                } else {
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Tidak bisa mapping, di luar dugaan. Ref: {$raw->Ref}. Debit: {$raw->Debit}. Kredit: {$raw->Kredit}"
                    ];
                    continue;
                }
                $balances['totalDebit'] += $raw->Debit;
                $balances['totalKredit'] += $raw->Kredit;
                $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
            }


            return [$details, $balances, $errMap];
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    private function mappingAr($raws)
    {
        $details = [];
        $balances = [
            'totalDebit' => 0,
            'totalKredit' => 0,
            'totalBalance' => 0
        ];
        $errMap = [];

        // 1. Foreach sampai len-1 karena yang terakhir (len-1) adalah total
        $i = 0;
        $lastIdx = count($raws) - 1;
        foreach ($raws as $raw) {
            if ($i == $lastIdx) {
                break;
            }
            $i++;
            // Parse string to decimal then round 2 decimal
            $raw->Debit = round($raw->Debit, 2);
            $raw->Kredit = round($raw->Kredit, 2);

            // Total debit, kredit, dan balance. Di atas biar selalu diexecute walau ada continue
            $balances['totalDebit'] += $raw->Debit;
            $balances['totalKredit'] += $raw->Kredit;
            $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];

            // 2. Kalau is_parent = 1, maka CoA pakai Piutang
            if (isset($raw->is_parent) && $raw->is_parent == 1) {
                // 2a. Kalau M_CompanyID 1235 (PASIEN KLINISI), pakai coaNo 1120100002
                if ($raw->M_CompanyID == 1235) {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120100002' AND coaIsActive = 'Y'";
                }
                // 2b. Kalau M_CompanyID 1222 (PASIEN MANDIRI), pakai coaNo 1120100001
                else if ($raw->M_CompanyID == 1222) {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120100001' AND coaIsActive = 'Y'";
                }
                // 2c. Selain itu, pakai coaNo 1120200001 9PIUTANG USAHA REKANAN)
                else {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120200001' AND coaIsActive = 'Y'";
                }
                $qry = $this->db->query($sql);
                if ($qry->row() == null) {
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "CoA AR Company tidak ditemukan. Ref: {$raw->Ref}"
                    ];
                    continue;
                }
                // * Untuk Parent: dibuat menjadi 2 jurnal_tx
                // * 1. Debit Pakai coaCompany (Piutang). rawDbt
                // * 2. Kredit Pakai mapping NatSubGroup. rawCr 
                $rawDbt = clone $raw;
                $rawDbt->coaID = $qry->row()->coaID;
                $rawDbt->coaDescription = $qry->row()->coaDescription;
                $rawDbt->coaAccNo = $qry->row()->coaAccountNo;
                $rawDbt->Kredit = 0;

                $rawCr = clone $raw;
                $rawCr->Debit = 0;
                $natGroupID = $rawCr->NatGroupID;
                $natSubGroupID = $rawCr->NatSubGroupID;

                $sql = "SELECT MapNatSub_PendapatanCoaAccountNo, coaID, coaDescription 
                        FROM map_nat_subgroup 
                        JOIN coa ON MapNatSub_PendapatanCoaAccountNo = coaAccountNo
                        WHERE MapNatSub_NatGroupID = ? AND MapNatSub_NatSubGroupID = ? 
                        AND MapNatSub_IsActive = 'Y' LIMIT 1";
                $qry = $this->db->query($sql, [$natGroupID, $natSubGroupID]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Ref: {$raw->Ref} | Error DB: {$error} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit} | Deskripsi: {$raw->Deskripsi}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Mapping NatSubGroup tidak ditemukan. NatGroupID: {$natGroupID} | NatSubGroupID: {$natSubGroupID} | Ref: {$raw->Ref} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit} | Deskripsi: {$raw->Deskripsi}"
                    ];
                    continue;
                }
                $rawCr->coaAccNo = $qry->row()->MapNatSub_PendapatanCoaAccountNo;
                $rawCr->coaID = $qry->row()->coaID;
                $rawCr->coaDescription = $qry->row()->coaDescription;

                $addOns = [
                    [
                        'jurnalAddOnCode' => 'OMZTYPE',
                        'jurnalAddOnValue' => $raw->M_OmzetTypeID
                    ],
                    [
                        'jurnalAddOnCode' => 'OMZNAME',
                        'jurnalAddOnValue' => $raw->M_OmzetTypeName
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYID',
                        'jurnalAddOnValue' => $raw->M_CompanyID
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYNAME',
                        'jurnalAddOnValue' => $raw->M_CompanyName
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYNUM',
                        'jurnalAddOnValue' => $raw->M_CompanyNumber
                    ]
                ];
                $rawDbt->addOns = $addOns;
                $rawCr->addOns = $addOns;

                $details[] = $rawDbt;
                $details[] = $rawCr;
                continue; // Agar tidak execute luar if else dan $raw tidak masuk ke details lagi
            }

            // 3. Kalau !is_parent dan !is_diskon , maka CoA pakai mapping NatSubGroup (Product)
            else if (!isset($raw->is_parent) && !isset($raw->is_diskon)) {
                $sql = "SELECT MapNatSub_PendapatanCoaAccountNo, coaID, coaDescription 
                        FROM map_nat_subgroup 
                        JOIN coa ON MapNatSub_PendapatanCoaAccountNo = coaAccountNo
                        WHERE MapNatSub_NatGroupID = ? AND MapNatSub_NatSubGroupID = ? 
                        AND MapNatSub_IsActive = 'Y' LIMIT 1";
                $qry = $this->db->query($sql, [$raw->NatGroupID, $raw->NatSubGroupID]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Failed mapping natsubgroup AR. Ref: {$raw->Ref} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Mapping NatSubGroup tidak ditemukan. NatGroupID: {$raw->NatGroupID} | NatSubGroupID: {$raw->NatSubGroupID} | Ref: {$raw->Ref}"
                    ];
                    continue;
                }

                $raw->coaAccNo = $qry->row()->MapNatSub_PendapatanCoaAccountNo;
                $raw->coaID = $qry->row()->coaID;
                $raw->coaDescription = $qry->row()->coaDescription;

                // Kalau kredit negatif, jadikan debit
                if ($raw->Kredit < 0) {
                    $raw->Debit = abs($raw->Kredit);
                    $raw->Kredit = 0;
                }
            }
            // 4. Kalau is_diskon = 1, maka CoA pakai mapping NatGroup
            else if (isset($raw->is_diskon) && $raw->is_diskon == 1) {
                $sql = "SELECT MapNatGroup_Disc_coaID,
                            MapNatGroup_Disc_coaAccNo,
                            MapNatGroup_Disc_coaDesc
                    FROM map_nat_group 
                        WHERE MapNatGroup_NatGroupID = ? 
                        AND MapNatGroup_IsActive = 'Y'";
                $qry = $this->db->query($sql, [$raw->NatGroupID]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Failed mapping diskon natgroup AR. Ref: {$raw->Ref} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        "err_type" => "Mapping",
                        "err_msg" => "Mapping NatGroup Diskon tidak ditemukan. NatGroupID: {$raw->NatGroupID} | Ref: {$raw->Ref}"
                    ];
                    continue;
                }
                $raw->coaAccNo = $qry->row()->MapNatGroup_Disc_coaAccNo;
                $raw->coaID = $qry->row()->MapNatGroup_Disc_coaID;
                $raw->coaDescription = $qry->row()->MapNatGroup_Disc_coaDesc;

                // Kalau kredit negatif, jadikan debit
                if ($raw->Kredit < 0) {
                    $raw->Debit = abs($raw->Kredit);
                    $raw->Kredit = 0;
                }
            } else {
                $errMap[] = [
                    "err_type" => "Mapping",
                    "err_msg" => "Tidak bisa mapping, di luar dugaan. Ref: {$raw->Ref}. Deskripsi: {$raw->Deskripsi} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit}"
                ];
                continue;
            }

            // Add addOns untuk yang selain is_parent = 1
            $addOns = [
                [
                    'jurnalAddOnCode' => 'OMZTYPE',
                    'jurnalAddOnValue' => $raw->M_OmzetTypeID
                ],
                [
                    'jurnalAddOnCode' => 'OMZNAME',
                    'jurnalAddOnValue' => $raw->M_OmzetTypeName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYID',
                    'jurnalAddOnValue' => $raw->M_CompanyID
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNAME',
                    'jurnalAddOnValue' => $raw->M_CompanyName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNUM',
                    'jurnalAddOnValue' => $raw->M_CompanyNumber
                ]
            ];
            $raw->addOns = $addOns;
            $details[] = $raw;
        }

        return [$details, $balances, $errMap];
    }

    private function mappingArPayment($raws)
    {
        $details = [];
        $balances = [
            'totalDebit' => 0,
            'totalKredit' => 0,
            'totalBalance' => 0
        ];
        $errMap = [];

        $i = 0;
        /* 
            Setiap item di $raws jadi 2 jurnal_tx dan 7 addOn: 
            1. Debit dari Bank
            2. Kredit ke Piutang Company
        */
        foreach ($raws as $raw) {
            $i++;
            //! Kalau Tipe Bayar RK skip dulu karena jurnal terpisah
            if ($raw->Tipe_Bayar == "RK") {
                continue;
            }

            $raw->Debit = round($raw->Debit, 2);
            $raw->Kredit = round($raw->Debit, 2); //* Karena ini payment, maka Kredit dibuat = Debit

            // 1a. Debit dari Bank
            if ($raw->IsBank) {
                if ($raw->M_BankAccountNo == "") {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "No Rekening Bank tidak ditemukan. Ref: {$raw->Ref} || Desc: {$raw->Deskripsi} || TipeBayar: {$raw->Tipe_Bayar} || Debit: {$raw->Debit} || Kredit: {$raw->Kredit}"
                    ];
                    continue;
                }

                $norek = str_replace(['-', '.'], '', $raw->M_BankAccountNo);

                $sql = "SELECT MapBank_CoaAccountNo, MapBank_CoaDescription, 
                                MapBank_BankAccountNo, MapBank_BranchCode,
                                coaID, coaDescription
                        FROM map_bank_coa
                        JOIN coa ON coaAccountNo = MapBank_CoaAccountNo
                        WHERE MapBank_BankAccountNo = ? 
                        AND MapBank_IsActive = 'Y'
                        LIMIT 1";
                $qry = $this->db->query($sql, [$norek]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Failed mapping bank Ar Payment. Ref: {$raw->Ref} | Error DB: {$error}"
                    ];
                    continue;
                }

                $rst = $qry->row();

                if ($rst == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Mapping Bank tidak ditemukan. Ref: {$raw->Ref} || Desc: {$raw->Deskripsi} || TipeBayar: {$raw->Tipe_Bayar} || Debit: {$raw->Debit} || Kredit: {$raw->Kredit}"
                    ];
                    continue;
                }

                $rawDbt = clone $raw;
                $rawDbt->coaAccNo = $rst->MapBank_CoaAccountNo;
                $rawDbt->coaID = $qry->row()->coaID;
                $rawDbt->coaDescription = $qry->row()->coaDescription;

                if ($rawDbt->coaID == "" || $rawDbt->coaID == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Mapping CoA Bank tidak ditemukan. Ref: {$rawDbt->Ref} || Desc: {$rawDbt->Deskripsi} || TipeBayar: {$rawDbt->Tipe_Bayar} || Debit: {$rawDbt->Debit} || Kredit: {$rawDbt->Kredit}"
                    ];
                    continue;
                }
            }

            // 1b. Debit dari Cash
            if (!$raw->IsBank) {
                if ($raw->Tipe_Bayar !== 'Cash') {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Belum punya mapping untuk selain Bank dan Cash. Ref: {$raw->Ref} || Desc: {$raw->Deskripsi} || TipeBayar: {$raw->Tipe_Bayar} || Debit: {$raw->Debit} || Kredit: {$raw->Kredit}"
                    ];
                    continue;
                }

                $rawDbt = clone $raw;
                $rawDbt->coaAccNo = "1110100001";
                $rawDbt->coaID = 5;
                $rawDbt->coaDescription = "TUNAI";
            }

            // 2. Kredit ke Piutang Company.
            if ($raw->M_CompanyID == 1235) {
                $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                WHERE coaAccountNo = '1120100002' AND coaIsActive = 'Y'";
            }
            // 2b. Kalau M_CompanyID 1222 (PASIEN MANDIRI), pakai coaNo 1120100001
            else if ($raw->M_CompanyID == 1222) {
                $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                WHERE coaAccountNo = '1120100001' AND coaIsActive = 'Y'";
            }
            // 2c. Selain itu, pakai coaNo 1120200001 9PIUTANG USAHA REKANAN)
            else {
                $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                WHERE coaAccountNo = '1120200001' AND coaIsActive = 'Y'";
            }

            $qry = $this->db->query($sql);
            if (!$qry) {
                $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                $errMap[] = [
                    'err_type' => 'Mapping',
                    'err_msg' => "Failed mapping AR Company Ar Payment. Ref: {$raw->Ref} | Error DB: {$error}"
                ];
                continue;
            }
            if ($qry->row() == null) {
                $errMap[] = [
                    'err_type' => 'Mapping',
                    'err_msg' => "CoA AR Company tidak ditemukan. Ref: {$raw->Ref}. Deskripsi: {$raw->Deskripsi} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit}"
                ];
                continue;
            }

            $rawCr = clone $raw;
            $rawCr->coaID = $qry->row()->coaID;
            $rawCr->coaDescription = $qry->row()->coaDescription;
            $rawCr->coaAccNo = $qry->row()->coaAccountNo;


            $addOns = [
                [
                    "jurnalAddOnCode" => "ARDATE",
                    "jurnalAddOnValue" => $raw->AR_Date
                ],
                [
                    "jurnalAddOnCode" => "PAYTYPE",
                    "jurnalAddOnValue" => $raw->Tipe_Bayar
                ],
                [
                    'jurnalAddOnCode' => 'OMZTYPE',
                    'jurnalAddOnValue' => $raw->M_OmzetTypeID
                ],
                [
                    'jurnalAddOnCode' => 'OMZNAME',
                    'jurnalAddOnValue' => $raw->M_OmzetTypeName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYID',
                    'jurnalAddOnValue' => $raw->M_CompanyID
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNAME',
                    'jurnalAddOnValue' => $raw->M_CompanyName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNUM',
                    'jurnalAddOnValue' => $raw->M_CompanyNumber
                ]
            ];

            $rawCr->Debit = 0;
            $rawDbt->Kredit = 0;
            $rawDbt->addOns = $addOns;
            $rawCr->addOns = $addOns;

            $details[] = $rawDbt;
            $details[] = $rawCr;

            $balances['totalDebit'] += $rawDbt->Debit;
            $balances['totalKredit'] += $rawCr->Kredit;
            $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
        }

        return [$details, $balances, $errMap];
    }

    private function mappingArPayRkTagihan($raws)
    {
        $details = [];
        $balances = [
            'totalDebit' => 0,
            'totalKredit' => 0,
            'totalBalance' => 0
        ];
        $errMap = [];
        /* 
            Tiap item di $raws jadi 2 jurnal_tx dan 7 addOn: 
            1. Debit pakai coa RK 
            2. Kredit pakai coa Piutang Company
        */
        foreach ($raws as $raw) {
            $rawDbt = clone $raw;
            $rawCr = clone $raw;
            $rawDbt->Debit = round(abs($rawDbt->Total_RkAmount), 2);
            $rawCr->Kredit = round(abs($rawDbt->Total_RkAmount), 2);

            //* Jika Total_RkAmount < 0 (nitip tagih)
            if ($rawDbt->Total_RkAmount < 0) {
                // 1. Debit pakai coa RK
                $sql = "SELECT Map_RkCabang_CoaAccountNo, Map_RkCabang_CoaDescription, Map_RkCabang_BranchCode, coaID
                FROM map_rk_cabang
                JOIN coa ON Map_RkCabang_CoaAccountNo = coaAccountNo 
                    WHERE Map_RkCabang_BranchCode = ? 
                    AND Map_RkCabang_Rk_TypeID = 1
                    AND Map_RkCabang_IsActive = 'Y'";
                $qry = $this->db->query($sql, [$raw->BranchCode]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Failed mapping coa RK Ar Payment. Ref: {$raw->Ref} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "CoA RK tidak ditemukan. Ref: {$raw->Ref}. Deskripsi: {$raw->Deskripsi} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit} | RK_BranchCode: {$raw->RK_BranchCode}"
                    ];
                    continue;
                }
                $rawDbt->Kredit = 0;
                $rawDbt->coaID = $qry->row()->coaID;
                $rawDbt->coaDescription = $qry->row()->Map_RkCabang_CoaDesc;
                $rawDbt->coaAccNo = $qry->row()->Map_RkCabang_CoaAccountNo;

                // 2. Kredit pakai coa Piutang Company
                // Kalau M_CompanyID 1235 (PASIEN DOKTER/KLINIS), pakai coaNo 1120100002
                if ($raw->M_CompanyID == 1235) {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120100002' AND coaIsActive = 'Y'";
                }
                // Kalau M_CompanyID 1222 (PASIEN MANDIRI), pakai coaNo 1120100001
                else if ($raw->M_CompanyID == 1222) {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120100001' AND coaIsActive = 'Y'";
                }
                // Selain itu, pakai coaNo 1120200001 PIUTANG USAHA REKANAN)
                else {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120200001' AND coaIsActive = 'Y'";
                }
                $qry = $this->db->query($sql);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Failed mapping AR Company Ar Payment. Ref: {$raw->Ref} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "CoA AR Company tidak ditemukan. Ref: {$raw->Ref} Deskripsi: {$raw->Deskripsi} | Debit: {$raw->Debit} | Kredit: {$raw->Kredit} | RK_BranchCode: {$raw->RK_BranchCode}"
                    ];
                    continue;
                }

                $rawCr->Debit = 0;
                $rawCr->coaID = $qry->row()->coaID;
                $rawCr->coaDescription = $qry->row()->coaDescription;
                $rawCr->coaAccNo = $qry->row()->coaAccountNo;
            } else {
                $errMap[] = [
                    'err_type' => 'Mapping',
                    'err_msg' => "Total_RkAmount >= 0 tidak termasuk nitip tagih (tagihan). Ref: {$raw->Ref}"
                ];
                continue;
            }

            $addOns = [
                [
                    "jurnalAddOnCode" => "ARDATE",
                    "jurnalAddOnValue" => $raw->AR_Date
                ],
                [
                    "jurnalAddOnCode" => "PAYTYPE",
                    "jurnalAddOnValue" => $raw->Tipe_Bayar
                ],
                [
                    'jurnalAddOnCode' => 'OMZTYPE',
                    'jurnalAddOnValue' => $raw->M_OmzetTypeID
                ],
                [
                    'jurnalAddOnCode' => 'OMZNAME',
                    'jurnalAddOnValue' => $raw->M_OmzetTypeName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYID',
                    'jurnalAddOnValue' => $raw->M_CompanyID
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNAME',
                    'jurnalAddOnValue' => $raw->M_CompanyName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNUM',
                    'jurnalAddOnValue' => $raw->M_CompanyNumber
                ]
            ];

            $rawDbt->addOns = $addOns;
            $rawCr->addOns = $addOns;

            $details[] = $rawDbt;
            $details[] = $rawCr;

            $balances['totalDebit'] += $rawDbt->Debit;
            $balances['totalKredit'] += $rawCr->Kredit;
            $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
        }
        return [$details, $balances, $errMap];
    }

    private function mappingArPayRkPelunasan($raws)
    {
        $details = [];
        $balances = [
            'totalDebit' => 0,
            'totalKredit' => 0,
            'totalBalance' => 0
        ];

        /*  --------- BRIEF: ------------
            Setiap item di $raws masing-masing menjadi 1 jurnal_tx dan berbeda AddOn
            - Debit dari Client 
                - Coa: coa Bank
                - Addon: companyid, companynum, companyname, paytypeID, paymentpusatDate
            - Kredit ke R/K cabang lain
                - CoA : pakai coa R/K cabang lain
                - Addon: companyid, companynum, companyname, paytypeID, paymentpusatDate
            - Kredit ke A/R cabang sendiri
                - CoA : piutang perusahaan
                - AddOn : ARdate, paytypeID, omzettypeid, omzettypename, companyid, companynum, companyname
        */

        // 1. Debit dari Client -> Mapping Bank CoA
        // * Pisah foreach, agar kalau continue tidak menganggu foreach kredit yang levelnya lebih rendah
        foreach ($raws as $raw) {
            $rawDbt = clone $raw->Debit;
            $norek = preg_replace('/[^0-9]/', '', $rawDbt->M_BankAccountNo);
            $sql = "SELECT MapBank_CoaAccountNo, MapBank_CoaDescription, MapBank_BankAccountNo, MapBank_BranchCode, coaID
                FROM map_bank_coa
                JOIN coa ON MapBank_CoaAccountNo = coaAccountNo
                WHERE MapBank_BankAccountNo = ? AND MapBank_IsActive = 'Y' AND coaIsActive = 'Y'";
            $qry = $this->db->query($sql, [$norek]);
            if (!$qry) {
                $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                $errMap[] = [
                    'err_type' => 'Mapping',
                    'err_msg' => "Failed mapping coa RK Ar Payment. Ref: {$rawDbt->Desc} | Error DB: {$error}"
                ];
                continue;
            }
            if ($qry->row() == null) {
                $errMap[] = [
                    'err_type' => 'Mapping',
                    'err_msg' => "CoA RK tidak ditemukan. Desc: {$rawDbt->Desc}"
                ];
                continue;
            }
            $rawDbt->Debit = $rawDbt->F_BillPaymentPusatAmount;
            $rawDbt->Kredit = 0;
            $rawDbt->coaID = $qry->row()->coaID;
            $rawDbt->coaDescription = $qry->row()->MapBank_CoaDescription;
            $rawDbt->coaAccNo = $qry->row()->MapBank_CoaAccountNo;
            $rawDbt->addOns = [
                [
                    'jurnalAddOnCode' => 'CMPNYID',
                    'jurnalAddOnValue' => $rawDbt->M_CompanyID
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNAME',
                    'jurnalAddOnValue' => $rawDbt->M_CompanyName
                ],
                [
                    'jurnalAddOnCode' => 'CMPNYNUM',
                    'jurnalAddOnValue' => $rawDbt->M_CompanyNumber
                ],
                [
                    'jurnalAddOnCode' => 'PAYTYPE',
                    'jurnalAddOnValue' => $rawDbt->M_PaymentTypeID
                ],
                [
                    'jurnalAddOnCode' => 'PAYMENTPUSATDATE',
                    'jurnalAddOnValue' => $rawDbt->F_BillPaymentPusatDate
                ]
            ];

            $details[] = $rawDbt;
            $balances['totalDebit'] += $rawDbt->Debit;
            $balances['totalKredit'] += $rawDbt->Kredit;
            $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
        }

        // 2 dan 3 untuk Kredit AR R/K cabang lain dan A/R cabang sendiri
        foreach ($raws as $raw) {
            $rawCabLain = $raw->AR_RK_Cabang_Lain;
            $rawCabIni = $raw->AR_Cabang_Ini;

            // 2. Kredit ke R/K cabang lain -> mapping coa R/K cabang lain
            // TODO: Reconfirm Map_RkCabang_Rk_TypeID = 'ARPAY' or 'RK'
            foreach ($rawCabLain as &$rawCr) {
                $sql = "SELECT Map_RkCabang_CoaAccountNo, Map_RkCabang_CoaDescription, Map_RkCabang_BranchCode, coaID
                FROM map_rk_cabang
                JOIN coa ON Map_RkCabang_CoaAccountNo = coaAccountNo 
                    WHERE Map_RkCabang_BranchCode = ? 
                    AND Map_RkCabang_Rk_TypeID = 1
                    AND Map_RkCabang_IsActive = 'Y'";
                $qry = $this->db->query($sql, [$rawCr->RK_BranchCode]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Failed mapping coa RK Ar Payment. Desc: {$rawCr->Desc} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "CoA RK tidak ditemukan. Desc: {$rawCr->Desc}"
                    ];
                    continue;
                }
                $rawCr->Debit = 0;
                $rawCr->Kredit = $rawCr->F_BillIssuePusatDetailTotal;
                $rawCr->coaID = $qry->row()->coaID;
                $rawCr->coaDescription = $qry->row()->Map_RkCabang_CoaDesc;
                $rawCr->coaAccNo = $qry->row()->Map_RkCabang_CoaAccountNo;
                $rawCr->addOns = [
                    [
                        'jurnalAddOnCode' => 'CMPNYID',
                        'jurnalAddOnValue' => $rawCr->M_CompanyID
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYNAME',
                        'jurnalAddOnValue' => $rawCr->M_CompanyName
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYNUM',
                        'jurnalAddOnValue' => $rawCr->M_CompanyNumber
                    ],
                    [
                        'jurnalAddOnCode' => 'PAYTYPE',
                        'jurnalAddOnValue' => $rawCr->M_PaymentTypeID
                    ]
                ];

                $details[] = $rawCr;

                $balances['totalDebit'] += $rawCr->Debit;
                $balances['totalKredit'] += $rawCr->Kredit;
                $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
            }
            unset($rawCr);

            // 3. Kredit ke A/R cabang sendiri -> mapping coa A/R cabang sendiri
            foreach ($rawCabIni as &$rawCr2) {
                // PASIEN DOKTER/KLINISI
                if ($raw->M_CompanyID == 1235) {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120100002' AND coaIsActive = 'Y'";
                }
                // Kalau M_CompanyID 1222 (PASIEN MANDIRI), pakai coaNo 1120100001
                else if ($raw->M_CompanyID == 1222) {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120100001' AND coaIsActive = 'Y'";
                }
                //Selain itu, pakai coaNo 1120200001 9PIUTANG USAHA REKANAN)
                else {
                    $sql = "SELECT coaID, coaDescription, coaAccountNo FROM coa 
                    WHERE coaAccountNo = '1120200001' AND coaIsActive = 'Y'";
                }

                $qry = $this->db->query($sql);

                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Failed mapping coa RK Ar Payment. Desc: {$rawCr2->Desc} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "CoA RK tidak ditemukan. Desc: {$rawCr2->Desc}"
                    ];
                    continue;
                }
                $rawCr2->Debit = 0;
                $rawCr2->Kredit = $rawCr2->SumBillDetailTotal;
                $rawCr2->coaID = $qry->row()->coaID;
                $rawCr2->coaDescription = $qry->row()->coaDescription;
                $rawCr2->coaAccNo = $qry->row()->coaAccountNo;
                $rawCr2->addOns = [
                    [
                        'jurnalAddOnCode' => 'CMPNYID',
                        'jurnalAddOnValue' => $rawCr2->M_CompanyID
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYNAME',
                        'jurnalAddOnValue' => $rawCr2->M_CompanyName
                    ],
                    [
                        'jurnalAddOnCode' => 'CMPNYNUM',
                        'jurnalAddOnValue' => $rawCr2->M_CompanyNumber
                    ],
                    [
                        'jurnalAddOnCode' => 'PAYTYPE',
                        'jurnalAddOnValue' => $rawCr2->M_PaymentTypeID
                    ],
                    [
                        'jurnalAddOnCode' => 'ARDATE',
                        'jurnalAddOnValue' => $rawCr2->ArDate
                    ],
                    [
                        'jurnalAddOnCode' => 'OMZTYPE',
                        'jurnalAddOnValue' => $rawCr2->M_OmzetTypeID
                    ],
                    [
                        'jurnalAddOnCode' => 'OMZNAME',
                        'jurnalAddOnValue' => $rawCr2->M_OmzetTypeName
                    ]
                ];

                $details[] = $rawCr2;

                $balances['totalDebit'] += $rawCr2->Debit;
                $balances['totalKredit'] += $rawCr2->Kredit;
                $balances['totalBalance'] = $balances['totalDebit'] - $balances['totalKredit'];
            }
            unset($rawCr2);
        }

        return [$details, $balances, $errMap];
    }

    private function insertMapBank($raws)
    {
        $sqlCheck = "SELECT MapBank_BankAccountNo, MapBank_NatBankID, MapBank_BranchCode 
        FROM map_bank_coa 
        WHERE MapBank_BankAccountNo LIKE CONCAT ('%', ?, '%') AND MapBank_NatBankID = ? AND MapBank_BranchCode = ?
        AND MapBank_IsActive = 'Y'";

        $sql = "INSERT INTO map_bank_coa
            (MapBank_BranchCode, MapBank_NatBankName, MapBank_BankAccountNo, 
            MapBank_NatBankID, MapBank_NatBankCode, MapBank_NatBankIsEDC, 
            MapBank_IsActive, MapBank_CreatedAt, MapBank_LastUpdatedAt) 
            VALUES (?, ?, ?, ?, ?, ?, 'Y', NOW(), NOW())";
        $errMap = [];
        $seenAcc = [];
        $seenNatBank = [];
        $seenBranch = [];
        $uniqueRows = [];
        $existingRows = [];

        foreach ($raws as $raw) {
            // * Check existing rows
            $qryCheck = $this->db->query($sqlCheck, [
                $raw->M_BankAccountNo,
                $raw->Nat_BankID,
                $raw->M_BranchCode
            ]);
            if ($qryCheck->num_rows() > 0 || $qryCheck->row() != null) {
                $existingRows[] = [
                    'BankAccountNo' => $raw->M_BankAccountNo,
                    'NatBankID' => $raw->Nat_BankID,
                    'BranchCode' => $raw->M_BranchCode,
                ];
                continue;
            }

            //* AVOID DUPLICATION * Skip jika ada row dengan norek dan natbankID yang sama
            if (in_array($raw->M_BankAccountNo, $seenAcc)) {
                if (in_array($raw->Nat_BankID, $seenNatBank)) {
                    if (in_array($raw->M_BranchCode, $seenBranch)) {
                        continue;
                    }
                }
            }
            $seenAcc[] = $raw->M_BankAccountNo;
            $seenNatBank[] = $raw->Nat_BankID;
            $seenBranch[] = $raw->M_BranchCode;
            $uniqueRows[] = $raw;

            $qry = $this->db->query($sql, [
                $raw->M_BranchCode,
                $raw->Nat_BankName,
                $raw->M_BankAccountNo,
                $raw->Nat_BankID,
                $raw->Nat_BankCode,
                $raw->Nat_BankIsEDC
            ]);
            if (!$qry) {
                $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                $errMap[] = [
                    'err_type' => 'Inject',
                    'err_msg' => "Failed Inject mapping bank. BranchCode: {$raw->_BranchCode} | BankAccountNo: {$raw->M_BankAccountNo} | NatBankID: {$raw->Nat_BankID} | Error DB: {$error}"
                ];
                continue;
            }
        }
        return [$uniqueRows, $errMap, $existingRows];
    }

    private function composeJurnalInfo($branchCode, $searchDate, $jurnalType)
    {
        $jurnalInfo = [];
        // * Sudah pasti success karena di awal sudah ada yang mirip (tidak perlu cek eror)
        $sql = "SELECT M_BranchID, M_BranchM_CompanyID, M_BranchS_RegionalID, 
            M_BranchName, S_RegionalName
                FROM m_branch 
                JOIN s_regional ON M_BranchS_RegionalID = S_RegionalID
                WHERE M_BranchCode = ? ";
        $qry = $this->db->query($sql, [$branchCode]);

        $regID = $qry->row()->M_BranchS_RegionalID;
        $regName = $qry->row()->S_RegionalName;
        $branchName = $qry->row()->M_BranchName;
        $jurnalInfo['S_RegionalID'] = $regID;
        $jurnalInfo['M_BranchID'] = $qry->row()->M_BranchID;
        $jurnalInfo['M_BranchName'] = $branchName;
        $jurnalInfo['M_BranchCode'] = $branchCode;
        $jurnalInfo['jurnalDate'] = $searchDate;

        $jurnalInfo['M_UserID'] = 500; //* 500 untuk autocount

        $sqlcom = "SELECT M_BranchCompanyDetailM_BranchCompanyID, M_BranchCompanyDetailM_BranchCode
        FROM m_branch_companydetail
        WHERE M_BranchCompanyDetailM_BranchCode = ?
        AND M_BranchCompanyDetailIsActive = 'Y' LIMIT 1";
        $qrycom = $this->db->query($sqlcom, [$branchCode]);
        if (!$qrycom) {
            $this->sys_error_db("Tidak menemukan M_BranchCompany ID untuk branch {$branchCode}", $this->db);
            exit;
        }
        $jurnalInfo['M_BranchCompanyID'] = $qrycom->row()->M_BranchCompanyDetailM_BranchCompanyID;

        // * Get Periode by Inserted Date
        $sql = "SELECT periodeID FROM periode WHERE ? BETWEEN periodeStartDate AND periodeEndDate";
        $qry = $this->db->query($sql, [$searchDate]);
        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Tidak menemukan periode jurnal untuk tanggal {$searchDate}", $this->db);
            exit;
        }
        $jurnalInfo['periodeID'] = $qry->row()->periodeID;

        $sqlJurnalNo = "SELECT `fn_numbering`('J') as jurnalNo";

        switch ($jurnalType) {
            case 'SALESREG':
                $jurnalInfo['M_BranchCode'] = '';
                $jurnalInfo['jurnalTitle'] = "Auto Daily Sales {$searchDate} Regional: {$regName} Cabang: {$branchName}";
                $jurnalInfo['jurnalDescription'] = "Auto Daily Sales Regional {$regName} pada {$searchDate} untuk Cabang {$branchName}";
                $jurnalInfo['jurnalTypeID'] = 7; //AUTODAILYSALES
                $sqlJurnalNo = "SELECT `fn_numbering`('ACSL') as jurnalNo";
                $jurnalNoSuffix = '';
                break;
            case 'SALESCAB':
                $jurnalInfo['jurnalTitle'] = "{$branchCode} - Auto Daily Sales {$searchDate}";
                $jurnalInfo['jurnalDescription'] = "Jurnal Auto Daily Sales Cabang {$jurnalInfo['M_BranchName']} Tanggal {$searchDate}";
                $jurnalInfo['jurnalTypeID'] = 7; //AUTODAILYSALES
                $jurnalInfo['jurnalNo'] = ''; //* Jurnal No Cabang akan dioverwrite dengan jurnal regional
                return $jurnalInfo;
                break;
            case 'AR':
                $jurnalInfo['jurnalTitle'] = "{$branchCode} - Auto Daily AR {$searchDate}";
                $jurnalInfo['jurnalDescription'] = "Jurnal Auto Daily AR Cabang {$jurnalInfo['M_BranchName']} Tanggal {$searchDate}";
                $jurnalInfo['jurnalTypeID'] = 10; //AUTODAILYAR
                $jurnalNoSuffix = "/{$branchCode}";
                break;
            case 'ARPAYMENT':
                $jurnalInfo['jurnalTitle'] = "{$branchCode} - Auto Daily AR Payment {$searchDate}";
                $jurnalInfo['jurnalDescription'] = "Jurnal Auto Daily AR Payment Cabang {$jurnalInfo['M_BranchName']} Tanggal {$searchDate}";
                $jurnalInfo['jurnalTypeID'] = 11; //AUTODAILYARPAYMENT
                $jurnalNoSuffix = "/{$branchCode}";
                break;
            case 'RKTAGIHAN':
                $jurnalInfo['jurnalTitle'] = "{$branchCode} - Auto Daily RK Tagihan Terpusat {$searchDate}";
                $jurnalInfo['jurnalDescription'] = "Jurnal Auto Daily RK Tagihan Cabang Terpusat {$jurnalInfo['M_BranchName']} Tanggal {$searchDate}";
                $jurnalInfo['jurnalTypeID'] = 12; //AUTODAILYRKTAGIHAN
                $jurnalNoSuffix = "/{$branchCode}";
                break;
            case 'RKPELUNASAN':
                $jurnalInfo['jurnalTitle'] = "{$branchCode} - Auto Daily RK Pelunasan Terpusat {$searchDate}";
                $jurnalInfo['jurnalDescription'] = "Jurnal Auto Daily RK Pelunasan Cabang Terpusat {$jurnalInfo['M_BranchName']} Tanggal {$searchDate}";
                $jurnalInfo['jurnalTypeID'] = 13; //AUTODAILYRKPELUNASAN
                $jurnalNoSuffix = "/{$branchCode}";
                break;

            default:
                $jurnalInfo['jurnalTitle'] = "{$branchCode} - Auto Jurnal {$searchDate}";
                $jurnalInfo['jurnalDescription'] = "Jurnal Auto Jurnal Cabang {$jurnalInfo['M_BranchName']} Tanggal {$searchDate}";
                $jurnalNoSuffix = "/{$branchCode}";
                break;
        }

        $qry = $this->db->query($sqlJurnalNo, []);
        if (!$qry) {
            $this->sys_error_db("Gagal generate nomor jurnal", $this->db);
            exit;
        }
        $rst = $qry->row();
        $jurnalInfo['jurnalNo'] = $rst->jurnalNo . $jurnalNoSuffix;

        return $jurnalInfo;
    }

    private function injectJurnal($jurnalInfo, $details)
    {
        $sqlHeader = "INSERT INTO jurnal 
                (jurnalM_BranchCompanyID, JurnalS_RegionalID, jurnalM_BranchCode, 
                jurnalperiodeID, jurnalNo, jurnalTitle, 
                jurnalDescription, jurnalDate, jurnalJurnalTypeID, 
                jurnalCreated, jurnalM_UserID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $qryHead = $this->db->query($sqlHeader, [
            $jurnalInfo['M_BranchCompanyID'],
            $jurnalInfo['S_RegionalID'],
            $jurnalInfo['M_BranchCode'],
            $jurnalInfo['periodeID'],
            $jurnalInfo['jurnalNo'],
            $jurnalInfo['jurnalTitle'],
            $jurnalInfo['jurnalDescription'],
            $jurnalInfo['jurnalDate'],
            $jurnalInfo['jurnalTypeID'],
            $jurnalInfo['M_UserID']
        ]);
        if (!$qryHead) {
            $this->db->trans_rollback();
            $this->sys_error_db("Failed Insert Jurnal: ", $this->db);
            exit;
        }

        $jurnalID = $this->db->insert_id();
        $errInsert = [];

        foreach ($details as $detail) {
            // Cek If coaID kosong, skip
            if ($detail->coaID == "" || !isset($detail->coaID)) {
                $errInsert[] = [
                    'err_type' => 'Mapping',
                    'err_msg' => "CoA ID untuk {$detail->Ref} tidak ditemukan. Debit: {$detail->Debit} || Kredit: {$detail->Kredit}"
                ];
                continue;
            }

            [$status, $errDetail] = $this->injectDetailJurnal($detail, $jurnalID); // Child Transaction
            if (!$status) {
                $errInsert = array_merge($errInsert, $errDetail);
                continue;
            }
        }

        return [$jurnalID, $errInsert];
    }

    private function injectDetailJurnal($prm, $jurnalID)
    {
        $coaID = $prm->coaID;
        $jurnalTxDescription = $prm->coaDescription;
        $jurnalTxDebit = $prm->Debit;
        $jurnalTxCredit = $prm->Kredit;
        $addOns = $prm->addOns; // array
        $errDetail = [];

        // * Debit atau Kredit bisa masuk ke Add On (karena AR Payment Debitnya perlu addOn)

        // ? Kalau debit dan kredit kosong, apakah di skip?
        // if ($jurnalTxDebit == 0 && $jurnalTxCredit == 0) {
        //     return;
        // }
        $insertTx = "INSERT INTO jurnal_tx 
                    (jurnalTxJurnalID, jurnalTxCoaID, jurnalTxDescription, jurnalTxCredit,
                    jurnalTxDebit, jurnalTxIsActive, jurnalTxCreated, jurnalTxLastUpdated, jurnalTxM_UserID)
                    VALUES (?, ?, ?, ?, ?, 'Y', NOW(), NOW(), 500)";

        $insertAddOn = "INSERT INTO jurnal_addon 
                    (jurnalAddOnJurnalID, jurnalAddOnJurnalTxID, 
                    jurnalAddOnCode,
                    jurnalAddOnValue,
                    jurnalAddOnIsActive, jurnalAddOnCreated,
                    jurnalAddOnLastUpdated, jurnalAddOnCreatedUserID)
                    VALUES (?, ?, ?, ?, 'Y', NOW(), NOW(), 500)";

        $insertTxCr =  $this->db->query($insertTx, [$jurnalID, $coaID, $jurnalTxDescription, $jurnalTxCredit, $jurnalTxDebit]);
        if (!$insertTxCr) {
            $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
            $errDetail[] = [
                'err_type' => 'Insert',
                'err_msg' => "Gagal Insert Jurnal Tx. Error: $error"
            ];
            return [false, $errDetail];
        } else {
            $getTxId = $this->db->insert_id();

            // Jika punya addOn
            if (isset($addOns)) {
                foreach ($addOns as $item) {
                    $addOnState = $this->db->query($insertAddOn, [$jurnalID, $getTxId, $item['jurnalAddOnCode'], $item['jurnalAddOnValue']]);

                    if (!$addOnState) {
                        $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                        $errDetail[] = [
                            'err_type' => 'Insert',
                            'err_msg' => "Gagal Insert Jurnal Tx. Error: $error"
                        ];
                        continue;
                    }
                }
            }
            return [true, $errDetail];
        }
    }

    private function injectErrors($jurnalErr, $jurnalID)
    {
        $sql = "INSERT INTO jurnal_errors 
        (JurnalErr_JurnalID, JurnalErr_Msg, JurnalErr_CreatedAt, JurnalErr_LastUpdatedAt) 
        VALUES (?, ?, NOW(), NOW())"; // NOW() is handled by MySQL, so no placeholder is needed

        foreach ($jurnalErr as $err) {
            $errEncoded = json_encode($err);
            $qry = $this->db->query($sql, [$jurnalID, $errEncoded]);
            if (!$qry) {
                $this->sys_error_db("Insert Error saja gagal. Coba dicek lagi deh", $this->db);
                exit;
            }
        }
    }

    private function getBranchCodeRegenSalesReg($jurnalNo)
    {
        $sql = "SELECT jurnalID, jurnalNo FROM jurnal 
        WHERE jurnalNo LIKE CONCAT('%', ?, '%')
        AND jurnalJurnalTypeID = 7  
        AND jurnalIsActive = 'Y' ORDER BY jurnalNo DESC"; //! Harus DESC agar nomor jurnal Cabang ada di index 0 dan diquery row() langsung dapat";
        $qry = $this->db->query($sql, [$jurnalNo]);
        if (!$qry) {
            $this->sys_error_db("Failed to get branch code for regen sales reg", $this->db);
            exit;
        }
        $rst = $qry->row();
        if ($rst == null || !$rst) {
            return null;
        }
        // Ambil 2 digit terakhir dari $rst->jurnalNo
        $branchCode = substr($rst->jurnalNo, -2);
        return $branchCode;
    }

    private function checkExistingJurnal($branchCode, $searchDate, $jurnalType, $jurnalNo = "")
    {
        $sql = "SELECT jurnalID, jurnalNo FROM jurnal 
                WHERE jurnalM_BranchCode = ?
                AND jurnalDate = ? AND jurnalJurnalTypeID = ? 
                AND jurnalIsActive = 'Y' ORDER BY jurnalID DESC";

        switch ($jurnalType) {
            case 'SALES':
                $jurnalTypeID = 7; //AUTODAILYSALES
                break;
            case 'SALESCAB':
                $jurnalTypeID = 7; //AUTODAILYSALES
                $regenFromCab = true;
                $noJurCab = $jurnalNo;
                $noJurReg = substr($noJurCab, 0, -3); // $noJurReg = $noJur dengan hapus 3 digit terakhir
                break;
            case 'SALESREG':
                $jurnalTypeID = 7; //AUTODAILYSALES
                $regenFromReg = true;
                $noJurReg = $jurnalNo;
                $noJurCab = $jurnalNo . "/{$branchCode}";
                break;
            case 'AR':
                $jurnalTypeID = 10; //AUTODAILYAR
                break;
            case 'ARPAYMENT':
                $jurnalTypeID = 11; //AUTODAILYARPAYMENT
                break;
            case 'RKTAGIHAN':
                $jurnalTypeID = 12; //AUTODAILYRKTAGIHAN
                break;
            case 'RKPELUNASAN':
                $jurnalTypeID = 13; //AUTODAILYRKPELUNASAN
                break;
            default:
                break;
        }

        // * Sementara khusus regen SALES karena harus 2 jurnalID
        if ($regenFromCab || $regenFromReg) {
            $sql_2 = "SELECT jurnalID, jurnalNo FROM jurnal 
                WHERE jurnalNo LIKE CONCAT(?, '%')
                AND jurnalJurnalTypeID = ?
                AND jurnalIsActive = 'Y' AND jurnalIsPosted = 'N' ORDER BY jurnalNo ASC"; // ASC Nomor Regional di Atas
            $qry_2 = $this->db->query($sql_2, [$noJurReg, $jurnalTypeID]); // Cari dari no jurRegional agar keluar semua
            if (!$qry_2) {
                $this->sys_error_db("Failed to check existing jurnal", $this->db);
                exit;
            }

            $rst_2 = $qry_2->result();
            if (!$rst_2 || $rst_2 == null) {
                return null;
            }
            return $rst_2;
        }

        $qry = $this->db->query($sql, [$branchCode, $searchDate, $jurnalTypeID]);
        if (!$qry) {
            $this->sys_error_db("Failed to check existing jurnal", $this->db);
            exit;
        }
        return $qry->row();
    }

    private function jurnalErrors($jurnalID, $type, $msg)
    {
        // Log jurnal error by insert it to jurnal_errors with jurnalId and Err Message
        $fullmsg = [
            'err_type' => $type,
            'err_msg' => $msg
        ];
        $fullmsg = json_encode($fullmsg, JSON_PRETTY_PRINT);

        $sql = "INSERT INTO jurnal_errors 
        (JurnalErr_JurnalID, JurnalErr_Msg, JurnalErr_CreatedAt, JurnalErr_LastUpdatedAt) 
            VALUES (?, ?, NOW(), NOW())";
        $qry = $this->db->query($sql, [$jurnalID, $fullmsg]);

        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Failed to insert jurnal error", $this->db);
            exit;
        }
        $jurnalErrID = $this->db->insert_id();
        return $jurnalErrID;
    }

    private function deleteJurnal($jurnalID)
    {
        $userID = $this->sys_user["M_UserID"];

        $sql = "SELECT jurnalIsPosted FROM jurnal WHERE jurnalID = ?";
        $isPosted = $this->db->query($sql, [$jurnalID])->row()->jurnalIsPosted ?? null;
        if (!$isPosted) {
            $this->sys_error_db("Failed to get jurnalIsPosted", $this->db);
            exit;
        }
        if ($isPosted == 'Y') {
            $this->sys_error("Jurnal sudah diposting, tidak bisa dihapus");
            exit;
        }

        // 1. Update jurnal where jurnalID = ? 
        $this->db->set('jurnalIsActive', 'N');
        $this->db->set('jurnalLastUpdated', 'NOW()', FALSE);
        $this->db->set('jurnalM_UserID', $userID);
        $this->db->where('jurnalID', $jurnalID);
        $this->db->update('jurnal');

        // 2. Update jurnal_tx where jurnalTxJurnalID = ? 
        $this->db->set('jurnalTxIsActive', 'N');
        $this->db->set('jurnalTxLastUpdated', 'NOW()', FALSE);
        $this->db->set('jurnalTxM_UserID', $userID);
        $this->db->where('jurnalTxJurnalID', $jurnalID);
        $this->db->update('jurnal_tx');

        // 3. Update jurnal_addon where jurnalAddOnJurnalID = ?
        $this->db->set('jurnalAddOnIsActive', 'N');
        $this->db->set('jurnalAddOnLastUpdated', 'NOW()', FALSE);
        $this->db->set('jurnalAddOnLastUpdatedUserID', $userID);
        $this->db->where('jurnalAddOnJurnalID', $jurnalID);
        $this->db->update('jurnal_addon');
    }

    // Hard Delete (Hanya untuk debugging)
    public function deleteDummyData()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $prm = $this->sys_input;
            $jurnalID = $prm['jurnalID'];
            $userID = $this->sys_user["M_UserID"];

            $sql = "SELECT jurnalIsPosted FROM jurnal WHERE jurnalID = ?";
            $isPosted = $this->db->query($sql, [$jurnalID])->row()->jurnalIsPosted ?? null;
            if (!$isPosted) {
                $this->sys_error_db("Failed to get jurnalIsPosted", $this->db);
                exit;
            }
            if ($isPosted == 'Y') {
                $this->sys_error("Jurnal sudah diposting, tidak bisa dihapus");
                exit;
            }

            // Start Transaction
            $this->db->trans_start();

            // 1. Delete from jurnal where jurnalID = ?
            $this->db->where('jurnalID', $jurnalID);
            $this->db->delete('jurnal');

            // 2. Delete from jurnal_tx where jurnalTxJurnalID = ?
            $this->db->where('jurnalTxJurnalID', $jurnalID);
            $this->db->delete('jurnal_tx');

            // 3. Delete from jurnal_addon where jurnalAddOnJurnalID = ?
            $this->db->where('jurnalAddOnJurnalID', $jurnalID);
            $this->db->delete('jurnal_addon');

            // 4. Delete from jurnal_errors where JurnalErr_JurnalID = ?
            $this->db->where('JurnalErr_JurnalID', $jurnalID);
            $this->db->delete('jurnal_errors');

            // Complete Transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->sys_error_db('Failed to delete jurnal: ', $this->db);
                exit;
            } else {
                $this->sys_ok("Berhasil hapus jurnal");
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    private function injectSalesManual()
    {
        $jurnal = "INSERT INTO `jurnal` (`jurnalM_BranchCompanyID`, `JurnalS_RegionalID`, `jurnalM_BranchCode`, `jurnalperiodeID`, `jurnalNo`, `jurnalTitle`, `jurnalDescription`, `jurnalDate`, `jurnalIsPosted`, `jurnalJurnalTypeID`, `jurnalIsActive`, `jurnalCreated`, `jurnalLastUpdated`, `jurnalM_UserID`) VALUES (1,	8,	'BA',	4,	'autosales001',	'Media Jurnal Penjualan ',	'description',	'2025-01-09',	'N',	7,	'Y',NOW(), NOW(),345)";

        $tx = "INSERT INTO `jurnal_tx` (`jurnalTxJurnalID`, `jurnalTxCoaID`, `jurnalTxDescription`,
         `jurnalTxDebit`, `jurnalTxCredit`, `jurnalTxIsActive`, `jurnalTxCreated`, 
         `jurnalTxLastUpdated`, `jurnalTxM_UserID`) VALUES
        (96, 5,	'TUNAI',	1850000,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 29,	'BCA PT PRAMITTA (5060)',	4393950,	0,	'Y',	NOW(),	NOW(),	345),
        (96, 29,	'BCA PT PRAMITTA (5060)',	2336050,	0,	'Y',	NOW(),	NOW(),	345),
        (96, 80,	'MANDIRI PT PRAMITA (6455)',	255000,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 29,	'BCA PT PRAMITTA (5060)',	10827650,	0,	'Y',	NOW(),	NOW(),	345),
        (96, 29,	'BCA PT PRAMITTA (5060)',	38022900,	0,	'Y',	NOW(),	NOW(),	345),
        (96, 42,	'BNI PT PRAMITA (5688)',	5766950,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 80,	'MANDIRI PT PRAMITA (6455)',	6458400,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 29,	'BCA PT PRAMITTA (5060)',	420000,	0,	'Y',	NOW(),	NOW(),	345),
        (96, 29,	'BCA PT PRAMITTA (5060)',	1512100,	0,	'Y',	NOW(),	NOW(),	345),
        (96, 826,	'PENDAPATAN KIMIA KLINIK',	0,	4294000,	'Y',	NOW(),	NOW(),	345),
        (96, 827,	'PENDAPATAN IMMUNOLOGI',	0,	    2189000,  'Y', 	NOW(),	NOW(),	345),
        (96, 822,	'PENDAPATAN HEMATOLOGI',	0,	    975000,  'Y', 	NOW(),	NOW(),	345),
        (96, 823,	'PENDAPATAN URINALIS',	0,	    560000,  'Y', 	NOW(),	NOW(),	345),
        (96, 846,	'PEND. RAD. FOTO POLOS',	0,	    367000,  'Y', 	NOW(),	NOW(),	345),
        (96, 856,	'PENDAPATAN LAYANAN KLINIK',	0,	    236000,  'Y', 	NOW(),	NOW(),	345),
        (96, 825,	'PENDAPATAN ANALISA KLINIK RUTIN',	0,	    564000,  'Y', 	NOW(),	NOW(),	345),
        (96, 826,	'PENDAPATAN KIMIA KLINIK',	0,	    18812000,  'Y', 	NOW(),	NOW(),	345),
        (96, 827,	'PENDAPATAN IMMUNOLOGI',	0,	    25948000,  'Y', 	NOW(),	NOW(),	345),
        (96, 822,	'PENDAPATAN HEMATOLOGI',	0,	    5000000,  'Y', 	NOW(),	NOW(),	345),
        (96, 823,	'PENDAPATAN URINALIS',	0,	    776000,  'Y', 	NOW(),	NOW(),	345),
        (96, 846,	'PEND. RAD. FOTO POLOS',	0,	    1896000,  'Y', 	NOW(),	NOW(),	345),
        (96, 847,	'PEND. RAD. PANORAMIC & DENTAL', 0,	    7090000,  'Y', 	NOW(),	NOW(),	345),
        (96, 848,	'PEND. RAD. FOTO KONTRAS', 0,	    1748000,  'Y', 	NOW(),	NOW(),	345),
        (96, 856,	'PENDAPATAN LAYANAN KLINIK',	0,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 865,	'PEND. LAYANAN JASA MEDIS LAINNYA',	0,	    103000,  'Y', 	NOW(),	NOW(),	345),
        (96, 826,	'PENDAPATAN KIMIA KLINIK',	0,	    840000,  'Y', 	NOW(),	NOW(),	345),
        (96, 827,	'PENDAPATAN IMMUNOLOGI',	0,	    687000,  'Y', 	NOW(),	NOW(),	345),
        (96, 830,	'PENDAPATAN LAB PATHOLOGI ANATOMI',	0,	    503500,  'Y', 	NOW(),	NOW(),	345),
        (96, 822,	'PENDAPATAN HEMATOLOGI',	0,	    538000,  'Y', 	NOW(),	NOW(),	345),
        (96, 826,	'PENDAPATAN KIMIA KLINIK',	0,	    420000,  'Y', 	NOW(),	NOW(),	345),
        (96, 828,	'PENDAPATAN MIKROBIOLOGI',	0,	    1618000,  'Y', 	NOW(),	NOW(),	345),
        (96, 847,	'PEND. RAD. PANORAMIC & DENTAL',	0,	    1547000,  'Y', 	NOW(),	NOW(),	345),
        (96, 847,	'DISCOUNT PENDAPATAN LABORATORIUM',	4623550,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 854,	'DISC PEND. RADIODIAGNOSTIK',	243550,	    0,  'Y', 	NOW(),	NOW(),	345),
        -- diskon layanan medis lainnya pakai disc layanan klinik
        (96, 867,	'DISC PEND. LAYANAN KLINIK',	0,	    0,  'Y', 	NOW(),	NOW(),	345),
        (96, 195,	'PEMBULATAN',	1400,	    0,  'Y', 	NOW(),	NOW(),	345),
        ";

        $addOn = "INSERT INTO `jurnal_addon` (`jurnalAddOnJurnalID`, `jurnalAddOnJurnalTxID`,
         `jurnalAddOnCode`, `jurnalAddOnValue`, `jurnalAddOnIsActive`, `jurnalAddOnCreated`,
          `jurnalAddOnCreatedUserID`, `jurnalAddOnDeleted`, `jurnalAddOnDeletedUserID`,
           `jurnalAddOnLastUpdatedUserID`, `jurnalAddOnLastUpdated`) VALUES 
        (96, 316,'OMZTYPE',	'1',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 316,'OMZNAME',	'APS',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 317,'OMZTYPE',	'1',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 317,'OMZNAME',	'APS',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 318,'OMZTYPE',	'1',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 318,'OMZNAME',	'APS',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 319,'OMZTYPE',	'1',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 319,'OMZNAME',	'APS',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 320,'OMZTYPE',	'1',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 320,'OMZNAME',	'APS',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 321,'OMZTYPE',	'1',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 321,'OMZNAME',	'APS',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),

        (96, 322,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 322,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 323,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 323,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 324,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 324,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 325,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 325,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 326,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 326,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 327,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 327,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 328,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 328,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 329,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 329,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 330,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 330,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 331,'OMZTYPE',	'2',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 331,'OMZNAME',	'Dokter',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),

        (96, 332,'OMZTYPE',	'3',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 332,'OMZNAME',	'Perusahaan',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 333,'OMZTYPE',	'3',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 333,'OMZNAME',	'Perusahaan',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 334,'OMZTYPE',	'3',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 334,'OMZNAME',	'Perusahaan',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 335,'OMZTYPE',	'3',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 335,'OMZNAME',	'Perusahaan',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),

        (96, 336,'OMZTYPE',	'4',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 336,'OMZNAME',	'Rujukan',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 337,'OMZTYPE',	'4',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 337,'OMZNAME',	'Rujukan',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 338,'OMZTYPE',	'4',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (96, 338,'OMZNAME',	'Rujukan',	'Y', NOW(),	345, NOW(),	0,	345, NOW())
        ";
    }

    private function injectArManual()
    {
        $jurnal = "INSERT INTO `jurnal` (`jurnalM_BranchCompanyID`, `JurnalS_RegionalID`,
            `jurnalM_BranchCode`, `jurnalperiodeID`, `jurnalNo`, `jurnalTitle`, `jurnalDescription`,
            `jurnalDate`, `jurnalIsPosted`, `jurnalJurnalTypeID`, `jurnalIsActive`, `jurnalCreated`,
            `jurnalLastUpdated`, `jurnalM_UserID`) VALUES (1,	8,	'BA',	4,	'autoAR001',	
            'Media Jurnal AR',	'inject manual AR belum balance',	'2025-01-09',	'N',	10,	'Y', 
            NOW(), NOW(),345)";

        $jurnalID = 144;

        $tx = "INSERT INTO `jurnal_tx` (`jurnalTxJurnalID`, `jurnalTxCoaID`, `jurnalTxDescription`,
         `jurnalTxDebit`, `jurnalTxCredit`, `jurnalTxIsActive`, `jurnalTxCreated`, 
         `jurnalTxLastUpdated`, `jurnalTxM_UserID`) VALUES
        (144, 199,	'PASIEN DOKTER / KLINISI',	585600,	    0,  'Y', 	NOW(),	NOW(),	345),
        (144, 847,	'PEND. RAD. PANORAMIC & DENTAL',	0, 732000,  'Y', 	NOW(),	NOW(),	345),
        (144, 854,	'DISC PEND. RADIODIAGNOSTIK',   0, -146400,	   'Y', 	NOW(),	NOW(),	345),

        (144, 595,	'HUTANG BPJS KESEHATAN',	24520000,	    0,  'Y', 	NOW(),	NOW(),	345),
        (144, 823,	'PENDAPATAN URINALISIS	',	0,	   10856000,  'Y', 	NOW(),	NOW(),	345),
        (144, 826,	'PENDAPATAN KIMIA KLINIK',	0,	    37806000,  'Y', 	NOW(),	NOW(),	345),
        (144, 847,	'DISCOUNT PENDAPATAN LABORATORIUM',	0, -24142000,  'Y', 	NOW(),	NOW(),	345),

        ";

        $addOn = "INSERT INTO `jurnal_addon` (`jurnalAddOnJurnalID`, `jurnalAddOnJurnalTxID`,
            `jurnalAddOnCode`, `jurnalAddOnValue`, `jurnalAddOnIsActive`, `jurnalAddOnCreated`,
            `jurnalAddOnCreatedUserID`, `jurnalAddOnDeleted`, `jurnalAddOnDeletedUserID`,
            `jurnalAddOnLastUpdatedUserID`, `jurnalAddOnLastUpdated`) VALUES 
        (144, 913,'CMPNYNUM',	'PRAMITA00013',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 913,'CMPNYID',	'1235',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 913,'CMPNYNAME',	'PASIEN KLINISI',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 914,'CMPNYNUM',	'PRAMITA00013',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 914,'CMPNYID',	'1235',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 914,'CMPNYNAME',	'PASIEN KLINISI',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 915,'CMPNYNUM',	'PRAMITA00013',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 915,'CMPNYID',	'1235',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 915,'CMPNYNAME',	'PASIEN KLINISI',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),

        (144, 916,'CMPNYNUM',	'0210100',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 916,'CMPNYID',	'2354',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 916,'CMPNYNAME',	'BPJS CABANG JAKARTA TIMUR',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 917,'CMPNYNUM',	'0210100',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 917,'CMPNYID',	'2354',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 917,'CMPNYNAME',	'BPJS CABANG JAKARTA TIMUR',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 918,'CMPNYNUM',	'0210100',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 918,'CMPNYID',	'2354',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 918,'CMPNYNAME',	'BPJS CABANG JAKARTA TIMUR',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 919,'CMPNYNUM',	'0210100',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 919,'CMPNYID',	'2354',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        (144, 919,'CMPNYNAME',	'BPJS CABANG JAKARTA TIMUR',	'Y', NOW(),	345, NOW(),	0,	345, NOW()),
        ";
    }
}
