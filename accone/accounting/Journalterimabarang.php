<?php

class Journalterimabarang extends MY_Controller
{
    var $db;
    public function index()
    {
        echo "API JURNAL PENERIMAAN BARANG";
    }
    public function __construct()
    {
        parent::__construct();
    }

    /* DIPAKAI FE */
    public function getTipeJurnal()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $sql = "SELECT 
                    *
                    FROM jurnal_type 
                    WHERE JurnalTypeIsActive = 'Y'";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->sys_error_db("getTipeJurnal: ", $this->db);
                exit;
            }
            $rst = $qry->result_array();
            foreach ($rst as $key => $value) {
                if ($value['JurnalTypeCode'] == 'GOODRECEIVE') {
                    $default = $value;
                }
            }
            $this->sys_ok([
                "default" => $default,
                "records" => $rst
            ]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function getPeriode()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $prm = $this->sys_input;
            $search = '%' . $prm['search'] . '%';
            $sql = "SELECT 
                    CONCAT(periodeYear, ' - ', periodeName) as displayPeriode,
                    CONCAT(DATE_FORMAT(periodeStartDate, '%d %M %Y'), ' - ', DATE_FORMAT(periodeEndDate, '%d %M %Y')) as periode,
                    periode.* 
                    FROM periode
                    WHERE periodeIsActive = 'Y'
                    AND periodeIsClosed = 'N'
                    AND CONCAT(periodeYear, ' - ', periodeName) LIKE ?
                    LIMIT 70
                    ";
            $qry = $this->db->query($sql, [$search]);
            if (!$qry) {
                $this->sys_error_db("Error get periode", $this->db);
                exit;
            }
            $data = $qry->result_array();
            $this->sys_ok($data);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function getSupplier()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $search = '%%';
            if (isset($prm['search'])) {
                $search = trim($prm["search"]);
                if ($search != "") {
                    $search = '%' . $search . '%';
                } else {
                    $search = '%%';
                }
            }

            $sql = "SELECT
                    CONCAT(SupplierCode, ' - ', SupplierName) as displaySupplier, 
                    supplier.*
                    FROM supplier 
                    WHERE SupplierIsActive = 'Y'
                    AND CONCAT(SupplierCode, ' - ', SupplierName) LIKE ?";

            $qry = $this->db->query($sql, [$search]);
            if (!$qry) {
                $this->sys_error_db("select supplier", $this->db);
                exit;
            }

            $rst = $qry->result_array();
            $this->sys_ok($rst);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function getCoa()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $search = "";
            if (isset($prm['search'])) {
                $search = trim($prm["search"]);
                if ($search != "") {
                    $search = '%' . $prm['search'] . '%';
                } else {
                    $search = '%%';
                }
            }

            $limit = 70; // dihapus jika perlu tampil semua

            $sql = "SELECT 
                    *
                    FROM coa
                    WHERE coaIsActive = 'Y'
                    -- AND coaIsInput = 'Y' -- TODO: uncomment setelah diinject
                    AND coaAccountNo != ''
                    AND (coaDescription LIKE ? OR coaSubDescription LIKE ? OR coaAccountNo LIKE ?)
                    ORDER BY coaAccountNo ASC
                    LIMIT ?";
            $qry = $this->db->query($sql, [$search, $search, $search, $limit]);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select coa", $this->db);
                exit;
            }

            $result = array(
                "records" => $rows,
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function createJurnal()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            // Get Parameter
            $userID = $this->sys_user["M_UserID"];
            $prm = $this->sys_input;
            $jurnalTypeID = $prm['jurnalType'] ?? 2; // Harusnya 2 : GOODRECEIVE
            $details = $prm['details'];

            // TODO: Dihapus jika sudah ada M_BranchCode di parameter
            $sql = "SELECT M_BranchCode FROM m_branch WHERE M_BranchID = ?";
            $qry = $this->db->query($sql, [$prm['M_BranchID']]);
            if (!$qry) {
                $this->sys_error_db("Failed to get branch code", $this->db);
                exit;
            }
            $rst = $qry->row();
            $branchCode = $rst->M_BranchCode;

            $sql = "SELECT `fn_numbering`('J') as jurnalNo";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->sys_error_db("Failed to get jurnal number", $this->db);
                exit;
            }
            $rst = $qry->row();
            $jurnalNo = $rst->jurnalNo;

            // Start Transaction
            $this->db->trans_start();

            $sqlHeader = "INSERT INTO jurnal 
                    (jurnalM_BranchCompanyID, JurnalS_RegionalID, jurnalM_BranchCode, 
                    jurnalperiodeID, jurnalNo, jurnalTitle, 
                    jurnalDescription, jurnalDate, jurnalJurnalTypeID, 
                    jurnalM_UserID, jurnalCreated)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $this->db->query($sqlHeader, [
                $prm['M_BranchCompanyID'],
                $prm['S_RegionalID'],
                $branchCode,
                $prm['periodeID'],
                $jurnalNo,
                $prm['jurnalTitle'],
                $prm['jurnalDescription'],
                $prm['jurnalDate'],
                $jurnalTypeID,
                $userID
            ]);

            $jurnalID = $this->db->insert_id();

            foreach ($details as $detail) {
                $this->createFormDetail($detail, $jurnalID); // Child Transaction
            }
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->sys_error_db('Failed to create jurnal: ', $this->db);
                exit;
            } else {
                $this->sys_ok("Berhasil simpan jurnal");
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function deleteJurnal()
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

    // TODO: Mungkin ini nanti bisa lebih efektif untuk addOns 
    public function loadEditDialog()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $prm = $this->sys_input;

            // Get jurnal, jurnal_tx, and jurnal_addon by jurnalID
            $sql = "SELECT jurnalID, jurnalperiodeID, jurnalNo, jurnalTitle, jurnalDescription, 
                DATE_FORMAT(jurnalDate, '%Y-%m-%d') as jurnalDate, jurnalIsPosted,
                M_BranchCompanyName, S_RegionalName, M_BranchName,
                M_BranchCompanyID, S_RegionalID, M_BranchID,
                JurnalTypeID,
                JurnalTypeCode,
                JurnalTypeName,
                JurnalTypeAccesRight
                FROM jurnal
                    JOIN m_branch ON jurnalM_BranchCode = M_BranchCode AND M_BranchIsActive = 'Y'
                    JOIN s_regional ON JurnalS_RegionalID = S_RegionalID AND S_RegionalIsActive = 'Y'
                    JOIN m_branch_company ON jurnalM_BranchCompanyID = M_BranchCompanyID AND M_BranchCompanyIsActive = 'Y'
                    JOIN jurnal_type ON jurnalJurnalTypeID = JurnalTypeID AND JurnalTypeIsActive = 'Y'
                WHERE jurnalID = ? AND jurnalIsActive = 'Y'";

            $jurnal = $this->db->query($sql, [$prm['jurnalID']])->row_array();
            if (!$jurnal) {
                $this->sys_error_db("Failed to get jurnal", $this->db);
                exit;
            }
            if ($jurnal == null) {
                $this->sys_error("JurnalID: " . $prm['jurnalID'] . " tidak ditemukan");
                exit;
            }
            $result['jurnalHead'] = $jurnal;

            // Get Selected Periode
            $sql = "SELECT 
                    CONCAT(periodeYear, ' - ', periodeName) as displayPeriode,
                    CONCAT(DATE_FORMAT(periodeStartDate, '%d %M %Y'), ' - ', DATE_FORMAT(periodeEndDate, '%d %M %Y')) as periode,
                    periode.* 
                    FROM jurnal
                    JOIN periode ON jurnalperiodeID = periodeID AND periodeIsActive = 'Y' AND periodeIsClosed = 'N'
                    WHERE jurnalID = ? AND jurnalIsActive = 'Y'";
            $periode = $this->db->query($sql, [$prm['jurnalID']])->row_array();
            if (!$periode) {
                $this->sys_error_db("Failed to get periode", $this->db);
                exit;
            }
            $result['periode'] = $periode;

            // Get suppliercode and grni/invoice code result object
            $sql = "SELECT * FROM jurnal_addon WHERE jurnalAddOnJurnalID = ? AND jurnalAddOnIsActive = 'Y'";
            $addon = $this->db->query($sql, [$prm['jurnalID']])->result_array();
            if (!$addon) {
                $this->sys_error_db("Failed to get jurnal_addon", $this->db);
                exit;
            }
            foreach ($addon as $key => $value) {
                $result['jurnalHead'][$value['jurnalAddOnCode']] = $value['jurnalAddOnValue'];
                if ($value['jurnalAddOnCode'] == 'SUPCD') {
                    $supcd = $value['jurnalAddOnValue'];
                }
            }

            // Get supplier
            $sql = "SELECT 
                    CONCAT(SupplierCode, ' - ', SupplierName) as displaySupplier, 
                    supplier.* 
                    FROM supplier WHERE SupplierCode = ? AND SupplierIsActive = 'Y'";
            $supplier = $this->db->query($sql, [$supcd])->row_array();
            if (!$supplier) {
                $this->sys_error_db("Failed to get supplier", $this->db);
                exit;
            }
            $result['supplier'] = $supplier;

            $txsql = "SELECT jurnalTxID, jurnalTxCoaID as coaID, jurnalTxDescription as coaDescription, 
                        jurnalTxDebit, jurnalTxCredit, coaAccountNo 
                    FROM jurnal_tx 
                    JOIN coa ON jurnalTxCoaID = coaID
                    WHERE jurnalTxJurnalID = ? AND jurnalTxIsActive = 'Y'";
            $tx = $this->db->query($txsql, [$prm['jurnalID']])->result_array();

            $result['details'] = $tx;

            foreach ($result['details'] as $key => $value) {
                if ($value['jurnalTxCredit'] > 0) {
                    $addonsql = "SELECT jurnalAddOnID, jurnalAddOnCode, jurnalAddOnValue FROM jurnal_addon WHERE jurnalAddOnJurnalTxID = ? AND jurnalAddOnIsActive = 'Y'";
                    $addon = $this->db->query($addonsql, [$value['jurnalTxID']])->result_array();
                    if (!$addon) {
                        $this->sys_error_db("Failed to get jurnal_addon", $this->db);
                        exit;
                    }
                    $result['details'][$key]['addOns'] = $addon;
                }
            }

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function updateJurnal()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $prm = $this->sys_input;
            $userID = $this->sys_user["M_UserID"];
            $jurnalID = $prm['jurnalID'];
            $details = $prm['details'];

            $sql = "SELECT jurnalIsPosted FROM jurnal WHERE jurnalID = ?";
            $isPosted = $this->db->query($sql, [$jurnalID])->row()->jurnalIsPosted ?? null;
            if (!$isPosted) {
                $this->sys_error_db("Failed to get jurnalIsPosted", $this->db);
                exit;
            }
            if ($isPosted == 'Y') {
                $this->sys_error("Jurnal sudah diposting, tidak bisa diubah");
                exit;
            }

            // Start Transaction
            $this->db->trans_start();

            // 1. Update jurnal
            $sql = "UPDATE jurnal 
                    SET jurnalDate = ?, jurnalperiodeID = ?, 
                        jurnalTitle = ?, jurnalDescription = ?, 
                        jurnalLastUpdated = NOW(), jurnalM_UserID = ?
                    WHERE jurnalID = ?";
            $qry = $this->db->query($sql, [$prm['jurnalDate'], $prm['periodeID'], $prm['jurnalTitle'], $prm['jurnalDescription'], $userID, $jurnalID]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed to update jurnal", $this->db);
                exit;
            }

            // 2. Soft delete semua existing jurnal_tx and jurnal_addon where jurnalID = ?
            $tx = "UPDATE jurnal_tx SET jurnalTxIsActive = 'N' WHERE jurnalTxJurnalID = ?";
            $qrytx = $this->db->query($tx, [$jurnalID]);
            if (!$qrytx) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed to update jurnal", $this->db);
                exit;
            }

            $addon = "UPDATE jurnal_addon SET jurnalAddOnIsActive = 'N' WHERE jurnalAddOnJurnalID = ?";
            $qryaddon = $this->db->query($addon, [$jurnalID]);
            if (!$qryaddon) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed to update jurnal", $this->db);
                exit;
            }

            // 3. Insert new jurnal_tx and jurnal_addon records
            foreach ($details as $detail) {
                $this->createFormDetail($detail, $jurnalID);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->sys_error_db('Failed to update jurnal: ', $this->db);
                exit;
            } else {
                $this->sys_ok("Berhasil update jurnal");
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    //*  --- bandingkan dulu baru hapus --- 
    public function updateJurnalV2()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $prm = $this->sys_input;
            $userID = $this->sys_user["M_UserID"];
            $jurnalID = $prm['jurnalID'];
            $details = $prm['details'];

            $sql = "SELECT jurnalIsPosted FROM jurnal WHERE jurnalID = ?";
            $isPosted = $this->db->query($sql, [$jurnalID])->row()->jurnalIsPosted ?? null;
            if (!$isPosted) {
                $this->sys_error_db("Failed to get jurnalIsPosted", $this->db);
                exit;
            }
            if ($isPosted == 'Y') {
                $this->sys_error("Jurnal sudah diposting, tidak bisa diubah");
                exit;
            }

            // Start Transaction
            $this->db->trans_start();

            // 1. Update jurnal
            $sql = "UPDATE jurnal 
                    SET jurnalDate = ?, jurnalperiodeID = ?, 
                        jurnalTitle = ?, jurnalDescription = ?, 
                        jurnalLastUpdated = NOW(), jurnalM_UserID = ?
                    WHERE jurnalID = ?";
            $qry = $this->db->query($sql, [$prm['jurnalDate'], $prm['periodeID'], $prm['jurnalTitle'], $prm['jurnalDescription'], $userID, $jurnalID]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed to update jurnal", $this->db);
                exit;
            }

            // 2. Ambil data existing jurnal_tx and jurnal_addon where jurnalID = ?
            $tx = "SELECT * FROM jurnal_tx WHERE jurnalTxJurnalID = ? AND jurnalTxIsActive = 'Y'";
            $oldTx = $this->db->query($tx, [$jurnalID])->result_array() ?? null;
            if (!$oldTx) {
                $this->sys_error_db("Failed to get jurnalTx", $this->db);
                exit;
            }

            // 3. Bandingin data jurnalTx: coaAccountNo, jurnalTxDebit, jurnalTxCredit

        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function diffJurnalTx()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $prm = $this->sys_input;
            $jurnalID = $prm['jurnalID'];
            $details = $prm['details'];

            $tx = "SELECT * FROM jurnal_tx WHERE jurnalTxJurnalID = ? AND jurnalTxIsActive = 'Y'";
            $oldTx = $this->db->query($tx, [$jurnalID])->result_array() ?? null;
            if (!$oldTx) {
                $this->sys_error_db("Failed to get jurnalTx", $this->db);
                exit;
            }

            var_dump($oldTx);

            var_dump($details);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }


    /* 
    * --- HELPER FUNCTION --- * 
    */
    private function createFormDetail($prm, $jurnalID)
    {
        $userid = $this->sys_user["M_UserID"];

        $coaID = $prm['coaID'];
        $jurnalTxDescription = $prm['coaDescription'];
        $jurnalTxDebit = $prm['jurnalTxDebit'];
        $jurnalTxCredit = $prm['jurnalTxCredit'];
        $addOns = $prm['addOns']; // array

        // Validasi debit dan kredit
        if ($jurnalTxCredit == 0 && $jurnalTxDebit > 0) {
            $isDebit = true;
            $isCredit = false;
        } else if ($jurnalTxDebit == 0 && $jurnalTxCredit > 0) {
            $isDebit = false;
            $isCredit = true;
        } else {
            $isDebit = false;
            $isCredit = false;
            $this->sys_error("Debit atau Kredit harus diisi");
            exit;
        }


        if ($isDebit && $isCredit) {
            $this->sys_error("Debit dan Kredit tidak boleh diisi bersamaan");
            exit;
        }
        // Jika kredit, maka masuk ke jurnal_tx dan jurnal_addon
        else if ($isCredit && !$isDebit) {
            $insertTx = "INSERT INTO jurnal_tx 
                    (jurnalTxJurnalID, jurnalTxCoaID, jurnalTxDescription, jurnalTxCredit,
                    jurnalTxIsActive, jurnalTxCreated, jurnalTxLastUpdated, jurnalTxM_UserID)
                    VALUES (?, ?, ?, ?, 'Y', NOW(), NOW(), ?)";

            $insertAddOn = "INSERT INTO jurnal_addon 
                    (jurnalAddOnJurnalID, jurnalAddOnJurnalTxID, 
                    jurnalAddOnCode,
                    jurnalAddOnValue,
                    jurnalAddOnIsActive, jurnalAddOnCreated,
                    jurnalAddOnCreatedUserID, jurnalAddOnLastUpdated, jurnalAddOnLastUpdatedUserID)
                    VALUES (?, ?, ?, ?, 'Y', NOW(), ?, NOW(), ?)";

            // Ikut parents transaction agar rollbacknya bisa langsung semua
            $insertTxCr =  $this->db->query($insertTx, [$jurnalID, $coaID, $jurnalTxDescription, $jurnalTxCredit, $userid]);
            if (!$insertTxCr) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed Insert JurnalTx Credit", $this->db);
                exit;
            }

            $getTxId = $this->db->insert_id();

            // Insert jurnalAddOn 1 kredit bisa ada 2-3 addOn
            foreach ($addOns as $item) {

                $addOnState = $this->db->query($insertAddOn, [$jurnalID, $getTxId, $item['jurnalAddOnCode'], $item['jurnalAddOnValue'], $userid, $userid]);

                if (!$addOnState) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("Failed Insert JurnalAddOn Credit", $this->db);
                    exit;
                }
            }
        }

        // Jika debit maka hanya masuk ke jurnal_tx
        else if ($isDebit && !$isCredit) {
            $sql = "INSERT INTO jurnal_tx 
                    (jurnalTxJurnalID, jurnalTxCoaID, jurnalTxDescription, jurnalTxDebit,
                    jurnalTxIsActive, jurnalTxCreated, jurnalTxLastUpdated, jurnalTxM_UserID)
                    VALUES (?, ?, ?, ?, 'Y', NOW(), NOW(), ?)";
            $qry = $this->db->query($sql, [$jurnalID, $coaID, $jurnalTxDescription, $jurnalTxDebit, $userid]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed Insert JurnalTx Debit", $this->db);
                exit;
            }
        } else {
            $this->sys_error("Debit atau Kredit harus diisi");
            exit;
        }
    }


    public function getListFormDetail()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $jurnalID = $this->sys_input['jurnalID'];

            $sql = "SELECT * FROM jurnal_tx
                 WHERE jurnalTxJurnalID = ?
                 AND jurnalTxIsActive = 'Y'";
            $qry = $this->db->query($sql, [$jurnalID]);
            if (!$qry) {
                $this->sys_error_db("select jurnal_tx", $this->db);
                exit;
            }

            $rst = $qry->result_array();
            $this->sys_ok($rst);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
