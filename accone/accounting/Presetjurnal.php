<?php

class Presetjurnal extends MY_Controller
{
    var $db;
    public function index()
    {
        echo "COA API";
        // $cek = $this->db->query("select database() as current_db")->result();
        // print_r($cek);
    }
    public function __construct()
    {
        parent::__construct();
    }
    function getPeriode()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $sql = "SELECT 
                periodeID AS id,
                periodeName as name,
                CONCAT(DATE_FORMAT(periodeStartDate, '%d-%m-%Y'), ' - ',DATE_FORMAT(periodeEndDate, '%d-%m-%Y')) as periode
                FROM periode 
                WHERE periodeIsActive = 'Y'
                AND periodeIsClosed = 'N'";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("select coa", $this->db);
            exit;
        }
        $rst = $qry->result_array();
        // $data = array(
        //     array("id" => "1", "name" => "Periode Januari", "periode" => '01-01-2024 - 31-01-2024'),
        //     array("id" => "2", "name" => "Periode Februari", "periode" => '01-02-2024 - 31-02-2024'),
        //     array("id" => "3", "name" => "Periode Maret", "periode" => '01-03-2024 - 31-03-2024'),
        // );
        $this->sys_ok($rst);
    }
    function getBranch()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $sql = "SELECT 
                M_BranchID branchID,
                M_BranchCode branchCode,
                M_BranchName branchName
                FROM m_branch WHERE M_BranchIsActive = 'Y'";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("get branch", $this->db);
            exit;
        }
        $rst = $qry->result_array();

        $this->sys_ok($rst);
    }
    function addJurnal()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $userid = $this->sys_user["M_UserID"];

        $sql = "SELECT fn_numbering('PJ') as number";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("get numbering", $this->db);
            exit;
        }
        $numbering = $qry->result_array()[0]['number'];
        $sql = "INSERT INTO t_presetjurnal(
                    jurnalM_BranchCode,
                    jurnalperiodeID,
                    jurnalNo,
                    jurnalTitle,
                    jurnalType,
                    jurnalCreated,
                    jurnalM_UserID)
                    VALUES (?,?,?,?,?,NOW(),?)";
        $qry = $this->db->query($sql, [
            $prm['branch'],
            $prm['periode'],
            $numbering,
            $prm['name'],
            $prm['type'],
            $userid
        ]);
        if (!$qry) {
            $this->sys_error_db("Error insert preset jurnal header", $this->db);
            exit;
        }

        $this->sys_ok($numbering);
    }
    function editJurnal()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $userid = $this->sys_user["M_UserID"];

        $sql = "UPDATE t_presetjurnal SET 
                    jurnalM_BranchCode = ?,
                    jurnalperiodeID = ?,
                    jurnalTitle = ?,
                    jurnalType = ?,
                    jurnalLastUpdated = NOW(),
                    jurnalM_UserID = ?
                    WHERE jurnalID = ?
                    ";
        $qry = $this->db->query($sql, [
            $prm['branch'],
            $prm['periode'],
            $prm['name'],
            $prm['type'],
            $userid,
            $prm['id'],
        ]);
        if (!$qry) {
            $this->sys_error_db("Error update preset jurnal header", $this->db);
            exit;
        }

        $this->sys_ok('OK');
    }
    function deleteJurnal()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $userid = $this->sys_user["M_UserID"];

        $sql = "UPDATE t_presetjurnal SET 
                    jurnalIsActive = 'N',
                    jurnalLastUpdated = NOW(),
                    jurnalM_UserID = ?
                    WHERE jurnalID = ?
                    ";
        $qry = $this->db->query($sql, [
            $userid,
            $prm['id'],
        ]);
        if (!$qry) {
            $this->sys_error_db("Error delete jurnal header", $this->db);
            exit;
        }
        $sql = "UPDATE t_presetjurnaldetail SET 
                    jurnalTxIsActive = 'N',
                    jurnalTxLastUpdated = NOW(),
                    jurnalTxM_UserID = ?
                    WHERE jurnalTxJurnalID = ?
                    ";
        $qry = $this->db->query($sql, [
            $userid,
            $prm['id'],
        ]);
        if (!$qry) {
            $this->sys_error_db("Error delete jurnal header", $this->db);
            exit;
        }

        $this->sys_ok('OK');
    }
    function searchHeader()
    {
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $page = $prm["page"];
        $periode = $prm["periode"];
        $ROW_PER_PAGE = 5;
        $start_offset = 0;
        // print_r($prm);

        if (isset($prm["page"])) {
            if (
                is_numeric($prm["page"]) && $prm["page"] > 0
            ) {
                $start_offset = ($page - 1) * $ROW_PER_PAGE;
            }
        }
        $sql = "SELECT 
                COUNT(jurnalID) as total
                FROM t_presetjurnal
                JOIN periode
                ON jurnalperiodeID = periodeID
                AND periodeIsActive = 'Y'
                JOIN m_branch
                ON jurnalM_BranchCode = M_BranchCode
                AND M_BranchIsActive = 'Y'
                WHERE (jurnalNo LIKE ? OR jurnalTitle LIKE ?)
                AND jurnalperiodeID = ?
                AND jurnalIsActive = 'Y'";
        $qry = $this->db->query($sql, [
            $search,
            $search,
            $periode

        ]);
        if (!$qry) {
            $this->sys_error_db("Error get total", $this->db);
            exit;
        }
        $total = $qry->result_array()[0]['total'];
        $sql = "SELECT 
                    jurnalID id,
                    jurnalM_BranchCode as branch,
                    jurnalperiodeID periode,
                    periodeName,
                    CONCAT(DATE_FORMAT(periodeStartDate, '%d/%m/%Y'), ' - ',DATE_FORMAT(periodeEndDate, '%d/%m/%Y')) as periodeDate,
                    M_BranchName branchName,
                    jurnalNo number,
                    jurnalTitle name,
                    jurnalType as type,
                    CASE 
                        WHEN jurnalStatus = 0 THEN 'NEW'
                        WHEN jurnalStatus <> 0 THEN 'POST'
                    END as status
                FROM t_presetjurnal
                JOIN periode
                    ON jurnalperiodeID = periodeID
                    AND periodeIsActive = 'Y'
                JOIN m_branch
                    ON jurnalM_BranchCode = M_BranchCode
                    AND M_BranchIsActive = 'Y'
                WHERE (jurnalNo LIKE ? OR jurnalTitle LIKE ?)
                AND jurnalperiodeID = ?
                AND jurnalIsActive = 'Y'
                LIMIT ? OFFSET ?";
        $qry = $this->db->query($sql, [
            $search,
            $search,
            $periode,
            $ROW_PER_PAGE,
            $start_offset
        ]);
        if (!$qry) {
            $this->sys_error_db("Error get total", $this->db);
            exit;
        }
        $rst = array(
            "total" => ceil($total / $ROW_PER_PAGE),
            "records" => $qry->result_array()
        );
        $this->sys_ok($rst);
    }
    function cek()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $sql = "SELECT COUNT(*) as total FROM t_beginningbalance";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("select coa", $this->db);
            exit;
        }
        $rst = $qry->result_array()[0]['total'];
        $this->sys_ok($rst);
    }
    function save()
    {
        // $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $data = $prm['data'];
        $jurnal = $prm['jurnal'];
        $userid = $this->sys_user["M_UserID"];
        $sql = "SELECT COUNT(*) as total FROM t_beginningbalance";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("select coa", $this->db);
            $this->db->trans_rollback();
            exit;
        }
        $total = $qry->result_array()[0]['total'];
        if (intval($total) > 0) {
            $sql = "DELETE FROM t_presetjurnaldetail WHERE jurnalTxJurnalID = ?";
            $qry = $this->db->query($sql, [$jurnal['id']]);
            if (!$qry) {
                $this->sys_error_db("Error truncate", $this->db);
                $this->db->trans_rollback();
                exit;
            }
        }

        for ($i = 0; $i < count($data); $i++) {
            $cekData = $data[$i];
            if (!array_key_exists('Number', $cekData)) {
                $this->sys_error("Kolom Number tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (!array_key_exists('Keterangan', $cekData)) {
                $this->sys_error("Kolom Keterangan tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (!array_key_exists('Debit', $cekData)) {
                $this->sys_error("Kolom Debit tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (!array_key_exists('Kredit', $cekData)) {
                $this->sys_error("Kolom Kredit tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (floatval($cekData['Debit']) > 0 && floatval($cekData['Kredit']) > 0) {
                $this->sys_error("Jumlah debit dan credit keduanya lebih besar dari 0");
                $this->db->trans_rollback();
                exit;
            }
            $sql = "SELECT coaID FROM coa
                    WHERE coaAccountNo = ?
                    AND coaIsInput = 'Y'
                    AND coaIsActive = 'Y'";
            $qry = $this->db->query($sql, [$cekData['Number']]);
            if (!$qry) {
                $this->sys_error_db("cek coa", $this->db);
                $this->db->trans_rollback();
                exit;
            }
            $cek = $qry->result_array();
            if (count($cek) == 0) {
                $this->sys_error_db("{$cekData['Number']} tidak ada di coa", $this->db);
                $this->db->trans_rollback();
                exit;
            }
            $data[$i]['coaID'] = $cek[0]['coaID'];
        }

        for ($i = 0; $i < count($data); $i++) {
            $dataCoa = $data[$i];
            $debit = $dataCoa['Debit'];
            $credit = $dataCoa['Kredit'];
            $type = 'DB';
            if (floatval($dataCoa['Kredit']) > 0) {
                $type = 'CR';
            }

            $sql = "INSERT INTO t_presetjurnaldetail(
                    jurnalTxJurnalID,
                    jurnalTxCoaID,
                    junalTxDescription,
                    jurnalTxDebit,
                    jurnalTxCredit,
                    jurnalTxCreated,
                    jurnalTxM_UserID)
                    VALUES(?,?,?,?,?,NOW(),?)";
            $qry = $this->db->query($sql, [
                $jurnal['id'],
                $dataCoa['coaID'],
                $dataCoa['Keterangan'],
                $debit,
                $credit,
                $userid
            ]);
            if (!$qry) {
                $this->sys_error_db("Error insert beginning balance", $this->db);
                $this->db->trans_rollback();
                exit;
            }
        }
        $this->sys_ok("OK");
        $this->db->trans_commit();
    }
    function addData()
    {
        // $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $data = $prm['data'];
        $jurnal = $prm['jurnal'];
        $userid = $this->sys_user["M_UserID"];
        $sql = "SELECT COUNT(*) as total FROM t_beginningbalance";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("select coa", $this->db);
            $this->db->trans_rollback();
            exit;
        }
        $total = $qry->result_array()[0]['total'];
        // if (intval($total) > 0) {
        //     $sql = "DELETE FROM t_presetjurnaldetail";
        //     $qry = $this->db->query($sql, []);
        //     if (!$qry) {
        //         $this->sys_error_db("Error truncate", $this->db);
        //         $this->db->trans_rollback();
        //         exit;
        //     }
        // }

        for ($i = 0; $i < count($data); $i++) {
            $cekData = $data[$i];
            if (!array_key_exists('Number', $cekData)) {
                $this->sys_error("Pilih coa terlebih dahulu");
                $this->db->trans_rollback();
                exit;
            }
            if (!array_key_exists('Keterangan', $cekData)) {
                $this->sys_error("Kolom Keterangan tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (trim($cekData['Keterangan']) == '') {
                $this->sys_error("Keterangan tidak boleh kosong");
                $this->db->trans_rollback();
                exit;
            }
            if (!array_key_exists('Debit', $cekData)) {
                $this->sys_error("Kolom Debit tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (!array_key_exists('Kredit', $cekData)) {
                $this->sys_error("Kolom Kredit tidak ditemukan");
                $this->db->trans_rollback();
                exit;
            }
            if (floatval($cekData['Debit']) > 0 && floatval($cekData['Kredit']) > 0) {
                $this->sys_error("Jumlah debit dan credit keduanya lebih besar dari 0");
                $this->db->trans_rollback();
                exit;
            }
            $sql = "SELECT coaID FROM coa
                    WHERE coaAccountNo = ?
                    AND coaIsInput = 'Y'
                    AND coaIsActive = 'Y'";
            $qry = $this->db->query($sql, [$cekData['Number']]);
            if (!$qry) {
                $this->sys_error_db("cek coa", $this->db);
                $this->db->trans_rollback();
                exit;
            }
            $cek = $qry->result_array();
            if (count($cek) == 0) {
                $this->sys_error_db("{$cekData['Number']} tidak ada di coa", $this->db);
                $this->db->trans_rollback();
                exit;
            }
            $data[$i]['coaID'] = $cek[0]['coaID'];
        }

        for ($i = 0; $i < count($data); $i++) {
            $dataCoa = $data[$i];
            $debit = $dataCoa['Debit'];
            $credit = $dataCoa['Kredit'];
            $type = 'DB';
            if (floatval($dataCoa['Kredit']) > 0) {
                $type = 'CR';
            }

            $sql = "INSERT INTO t_presetjurnaldetail(
                    jurnalTxJurnalID,
                    jurnalTxCoaID,
                    junalTxDescription,
                    jurnalTxDebit,
                    jurnalTxCredit,
                    jurnalTxCreated,
                    jurnalTxM_UserID)
                    VALUES(?,?,?,?,?,NOW(),?)";
            $qry = $this->db->query($sql, [
                $jurnal['id'],
                $dataCoa['coaID'],
                $dataCoa['Keterangan'],
                $debit,
                $credit,
                $userid
            ]);
            if (!$qry) {
                $this->sys_error_db("Error insert beginning balance", $this->db);
                $this->db->trans_rollback();
                exit;
            }
        }
        $this->sys_ok("OK");
        $this->db->trans_commit();
    }


    function searchDetail()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $page = $prm["page"];
        $jurnal = $prm["jurnal"];
        $ROW_PER_PAGE = 20;
        $start_offset = 0;
        // print_r($prm);

        if (isset($prm["page"])) {
            if (
                is_numeric($prm["page"]) && $prm["page"] > 0
            ) {
                $start_offset = ($page - 1) * $ROW_PER_PAGE;
            }
        }
        $sql = "SELECT 
                count(jurnalTxID) as total
                FROM t_presetjurnaldetail
                JOIN coa 
                ON jurnalTxCoaID = coaID
                AND coaIsActive = 'Y'
                WHERE (coaAccountNo LIKE ? OR junalTxDescription LIKE ?)
                AND jurnalTxIsActive = 'Y'
                AND jurnalTxJurnalID = ?";
        $qry = $this->db->query($sql, [
            $search,
            $search,
            $jurnal['id']
        ]);
        if (!$qry) {
            $this->sys_error_db("Error get total", $this->db);
            exit;
        }
        $total = $qry->result_array()[0]['total'];
        $sql = "SELECT 
                jurnalTxID id,
                coaAccountNo number,
                jurnalTxCoaID coaid,
                CONCAT(coaAccountNo, ' ' ,coaDescription) as searchCoa,
                coaDescription,
                junalTxDescription keterangan,
                jurnalTxDebit,
                jurnalTxCredit,
                CASE
                    WHEN jurnalTxDebit <> 0 AND jurnalTxCredit = 0 THEN jurnalTxDebit
                    WHEN jurnalTxCredit <> 0 AND jurnalTxDebit = 0  THEN jurnalTxCredit
                    WHEN jurnalTxCredit = 0 AND jurnalTxDebit = 0  THEN jurnalTxDebit
                    ELSE jurnalTxDebit
                END as value,
                CASE
                    WHEN jurnalTxDebit <> 0 AND jurnalTxCredit = 0 THEN 'DB'
                    WHEN jurnalTxCredit <> 0 AND jurnalTxDebit = 0  THEN 'CR'
                    WHEN jurnalTxCredit = 0 AND jurnalTxDebit = 0  THEN 'DB'
                    ELSE 'ERROR'
                END as type
                FROM t_presetjurnaldetail
                JOIN coa 
                ON jurnalTxCoaID = coaID
                AND coaIsActive = 'Y'
                WHERE (coaAccountNo LIKE ? OR junalTxDescription LIKE ?)
                AND jurnalTxIsActive = 'Y'
                AND jurnalTxJurnalID = ?
                LIMIT ? OFFSET ?";
        $qry = $this->db->query($sql, [$search, $search, $jurnal['id'], $ROW_PER_PAGE, $start_offset]);
        if (!$qry) {
            $this->sys_error_db("search detail", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $totalDebit = 0;
        $totalCredit = 0;
        $totalBalance = 0;
        $status = 'N';

        $sql = "SELECT 
                IFNULL(SUM(jurnalTxDebit), 0) as debit,
                IFNULL(SUM(jurnalTxCredit), 0) as credit
                FROM t_presetjurnaldetail
                JOIN coa 
                ON jurnalTxCoaID = coaID
                AND coaIsActive = 'Y'
                AND jurnalTxIsActive = 'Y'
                AND jurnalTxJurnalID = ?
                ";
        $qry = $this->db->query($sql, [$jurnal['id'],]);
        if (!$qry) {
            $this->sys_error_db("Error sql count balance", $this->db);
            exit;
        }
        $totalSum = $qry->row_array();
        $totalDebit = $totalSum['debit'];
        $totalCredit = $totalSum['credit'];

        // for ($i = 0; $i < count($data); $i++) {
        //     $dataCek = $data[$i];
        //     if ($dataCek['type'] == 'DB') {
        //         $totalDebit = $totalDebit + floatval($dataCek['value']);
        //     }
        //     if ($dataCek['type'] == 'CR') {
        //         $totalCredit = $totalCredit + floatval($dataCek['value']);
        //     }
        // }
        $totalBalance =  $totalDebit - $totalCredit;
        $periode = array();


        $rst = array(
            'data' => $data,
            "total" => ceil($total / $ROW_PER_PAGE),
            'summary' => array(
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'balance' => $totalBalance
            )
        );
        $this->sys_ok($rst);
    }
    function updateData()
    {
        // $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $data = $prm['data'];
        $coa = $prm['coa'];
        $userid = $this->sys_user["M_UserID"];
        $debit = 0;
        $credit = 0;
        $type = $data['type'];
        if ($type == 'DB') {
            $debit = $data['value'];
            $credit = 0;
        }
        if ($type == 'CR') {
            $credit = $data['value'];
            $debit = 0;
        }

        $sql = "UPDATE t_presetjurnaldetail
                SET 
                jurnalTxCoaID = ?,
                jurnalTxDebit = ?,
                jurnalTxCredit = ?,
                junalTxDescription = ?,
                jurnalTxM_UserID = ?
                WHERE jurnalTxID = ?";
        $qry = $this->db->query($sql, [$coa['coaID'], $debit, $credit, $data['keterangan'], $userid, $data['id']]);
        if (!$qry) {
            $this->sys_error_db("Error update preset jurnal", $this->db);
            exit;
        }
        $retval = array(
            "debit" => $debit,
            "type" => $type,
            "credit" => $credit,
            "last_qry" => $this->db->last_query()
        );
        $this->sys_ok($retval);
    }
    function postData()
    {
        $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $id = $prm['id'];
        $userid = $this->sys_user["M_UserID"];
        $sql = "SELECT 
                COUNT(jurnalTxCoaID) as total
                FROM t_presetjurnaldetail
                JOIN coa 
                ON jurnalTxCoaID = coaID
                AND coaIsActive = 'Y'
                AND jurnalTxIsActive = 'Y'
                AND jurnalTxJurnalID = ?
                ";
        $qry = $this->db->query($sql, [$id]);
        if (!$qry) {
            $this->sys_error_db("Error sql count balance", $this->db);
            exit;
        }
        $totalData = $qry->row_array()['total'];
        if (intval($totalData) == 0) {
            $this->sys_error_db("Belum memiliki jurnal detail", $this->db);
            exit;
        }
        $sql = "SELECT 
                IFNULL(SUM(jurnalTxDebit), 0) as debit,
                IFNULL(SUM(jurnalTxCredit), 0) as credit
                FROM t_presetjurnaldetail
                JOIN coa 
                ON jurnalTxCoaID = coaID
                AND coaIsActive = 'Y'
                AND jurnalTxIsActive = 'Y'
                AND jurnalTxJurnalID = ?
                ";
        $qry = $this->db->query($sql, [$id]);
        if (!$qry) {
            $this->sys_error_db("Error sql count balance", $this->db);
            exit;
        }
        $totalSum = $qry->row_array();
        $totalDebit = $totalSum['debit'];
        $totalCredit = $totalSum['credit'];
        if ((floatval($totalDebit) - floatval($totalCredit)) != 0) {
            $this->sys_error_db(" GAGAL, Jurnal tidak balance !", $this->db);
            exit;
        }


        $sql = "INSERT INTO jurnal
                (jurnalM_BranchCode,
                jurnalperiodeID,
                jurnalNo,
                jurnalTitle,
                jurnalDate,
                jurnalType,
                jurnalM_UserID)
                SELECT 
                    jurnalM_BranchCode,
                    jurnalperiodeID,
                    fn_numbering('J'),
                    jurnalTitle,
                    NOW(),
                    jurnalType,
                    {$userid}
                FROM t_presetjurnal
                WHERE jurnalID = ?
                ";
        $qry = $this->db->query($sql, [$id]);
        if (!$qry) {
            $this->sys_error_db("Insert jurnal", $this->db);
            $this->db->trans_rollback();
            exit;
        }
        $insertedID = $this->db->insert_id();
        $sql = "UPDATE t_presetjurnal SET 
                    jurnalStatus = ?,
                    jurnalLastUpdated = NOW(),
                    jurnalM_UserID = ?
                    WHERE jurnalID = ?
                    ";
        $qry = $this->db->query($sql, [
            $insertedID,
            $userid,
            $id
        ]);
        if (!$qry) {
            $this->sys_error_db("Error update preset jurnal header", $this->db);
            $this->db->trans_rollback();

            exit;
        }

        $sql = "INSERT INTO jurnal_tx(
                    jurnalTxJurnalID,
                    jurnalTxCoaID,
                    junalTxDescription,
                    jurnalTxDebit,
                    jurnalTxCredit,
                    jurnalTxM_UserID)
                SELECT 
                    {$insertedID},
                    jurnalTxCoaID,
                    junalTxDescription,
                    jurnalTxDebit,
                    jurnalTxCredit,
                    {$userid}
                FROM t_presetjurnaldetail
                JOIN coa 
                ON jurnalTxCoaID = coaID
                AND coaIsActive = 'Y'
                AND coaIsInput = 'Y'
                WHERE jurnalTxIsActive = 'Y'
                AND jurnalTxJurnalID = ?";
        $qry = $this->db->query($sql, [
            $id
        ]);
        if (!$qry) {
            $this->sys_error_db("Error update preset jurnal detail", $this->db);
            $this->db->trans_rollback();
            exit;
        }
        $this->db->trans_commit();
        $this->sys_ok('OK');
    }
    function delete()
    {
        $this->db->trans_begin();
        // $this->db->trans_rollback();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $sql = "DELETE FROM t_presetjurnaldetail WHERE jurnalTxID = ?";
        $qry = $this->db->query($sql, [$prm['id']]);
        if (!$qry) {
            $this->sys_error_db("Error delete", $this->db);
            $this->db->trans_rollback();
            exit;
        }
        $this->db->trans_commit();
        $this->sys_ok('OK');
    }
    function searchCoa()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $jurnal = $prm['jurnal'];
        $sql = "SELECT 
                coaID,
                jurnalTxID ,
                coaAccountNo as number,
                coaDescription as keterangan,
                CONCAT(coaAccountNo, ' ' ,coaDescription) as display
                FROM coa
                LEFT JOIN t_presetjurnaldetail
                ON coaID = jurnalTxCoaID
                AND jurnalTxJurnalID = ?
                WHERE 
                coaIsInput = 'Y'
                AND coaIsActive = 'Y'
                AND (coaAccountNo LIKE ? OR coaDescription LIKE ? OR CONCAT(coaAccountNo, ' ' ,coaDescription) LIKE ? )
                ";
        // AND jurnalTxID IS NULL
        $qry = $this->db->query($sql, [$jurnal['id'], $search, $search, $search]);
        if (!$qry) {
            $this->sys_error_db("Error truncate", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
}
