<?php

class Beginingbalance extends MY_Controller
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
        $periode = $prm['periode'];
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
            $sql = "DELETE FROM t_beginningbalance";
            $qry = $this->db->query($sql, []);
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

            $sql = "INSERT INTO t_beginningbalance(
                    T_BeginningBalancePeriodeID,
                    T_BeginningBalanceCoaID,
                    T_BeginningBalanceCoaAccountNo,
                    T_BeginningBalanceDescription,
                    T_BeginningBalanceType,
                    T_BeginningBalanceStatus,
                    T_BeginningBalanceDebit,
                    T_BeginningBalanceCredit,
                    T_BeginningBalanceCreatedUserID,
                    T_BeginningBalanceCreated)
                    VALUES(?,?,?,?,?,?,?,?,?, NOW())";
            $qry = $this->db->query($sql, [
                $periode['id'],
                $dataCoa['coaID'],
                $dataCoa['Number'],
                $dataCoa['Keterangan'],
                $type,
                'N',
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
        // $this->sys_error_db("error msg");
        // $this->sys_error("error msg");
        // exit;
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
        $periode = $prm['periode'];
        $userid = $this->sys_user["M_UserID"];


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

            $sql = "INSERT INTO t_beginningbalance(
                    T_BeginningBalancePeriodeID,
                    T_BeginningBalanceCoaID,
                    T_BeginningBalanceCoaAccountNo,
                    T_BeginningBalanceDescription,
                    T_BeginningBalanceType,
                    T_BeginningBalanceStatus,
                    T_BeginningBalanceDebit,
                    T_BeginningBalanceCredit,
                    T_BeginningBalanceCreatedUserID,
                    T_BeginningBalanceCreated)
                    VALUES(?,?,?,?,?,?,?,?,?, NOW())";
            $qry = $this->db->query($sql, [
                $periode['id'],
                $dataCoa['coaID'],
                $dataCoa['Number'],
                $dataCoa['Keterangan'],
                $type,
                'N',
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

    function search()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;

        $sql = "SELECT 
                T_BeginningBalanceID as id,
                T_BeginningBalanceCoaID as coaID,
                T_BeginningBalancePeriodeID  as periodeID,
                T_BeginningBalanceCoaAccountNo as number,
                T_BeginningBalanceDescription as keterangan,
                T_BeginningBalanceType as type,
                T_BeginningBalanceStatus as status,
                CASE
                WHEN T_BeginningBalanceType = 'DB' THEN T_BeginningBalanceDebit
                WHEN T_BeginningBalanceType = 'CR' THEN T_BeginningBalanceCredit
                END as value
                FROM t_beginningbalance";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("select coa", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $totalDebit = 0;
        $totalCredit = 0;
        $totalBalance = 0;
        $status = 'N';

        for ($i = 0; $i < count($data); $i++) {
            $dataCek = $data[$i];
            if ($dataCek['type'] == 'DB') {
                $totalDebit = $totalDebit + floatval($dataCek['value']);
            }
            if ($dataCek['type'] == 'CR') {
                $totalCredit = $totalCredit + floatval($dataCek['value']);
            }
        }
        $totalBalance =  $totalDebit - $totalCredit;
        $periode = array();
        if (count($data) > 0) {
            if ($data[0]['status'] == 'P') {
                $status = 'P';
            }
            $sql = "SELECT 
                periodeID AS id,
                periodeName as name,
                CONCAT(DATE_FORMAT(periodeStartDate, '%d-%m-%Y'), ' - ',DATE_FORMAT(periodeEndDate, '%d-%m-%Y')) as periode
                FROM periode 
                WHERE periodeID = ?";
            $qry = $this->db->query($sql, [$data[0]['periodeID']]);
            if (!$qry) {
                $this->sys_error_db("select coa", $this->db);
                exit;
            }
            $periode = $qry->result_array()[0];
        }

        $rst = array(
            'data' => $data,
            'periode' => $periode,
            'status' => $status,
            'total' => array(
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

        $sql = "UPDATE t_beginningbalance
                SET T_BeginningBalanceDebit = ?,
                T_BeginningBalanceCredit = ?,
                T_BeginningBalanceType = ?,
                T_BeginningBalanceLastUpdatedUserID = ?
                WHERE T_BeginningBalanceID = ?";
        $qry = $this->db->query($sql, [$debit, $credit, $type, $userid, $data['id']]);
        if (!$qry) {
            $this->sys_error_db("update coa", $this->db);
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
        // $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }


        $userid = $this->sys_user["M_UserID"];


        $sql = "UPDATE t_beginningbalance
                SET T_BeginningBalanceStatus = 'P',
                T_BeginningBalanceLastUpdatedUserID = ?
                ";
        $qry = $this->db->query($sql, [$userid]);
        if (!$qry) {
            $this->sys_error_db("update coa", $this->db);
            exit;
        }

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
        $sql = "DELETE FROM t_beginningbalance WHERE T_BeginningBalanceID = ?";
        $qry = $this->db->query($sql, [$prm['id']]);
        if (!$qry) {
            $this->sys_error_db("Error truncate", $this->db);
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
        $sql = "SELECT 
                T_BeginningBalanceID ,
                coaAccountNo as number,
                coaDescription as keterangan,
                CONCAT(coaAccountNo, '-' ,coaDescription) as display
                FROM coa
                LEFT JOIN t_beginningbalance
                ON coaID = T_BeginningBalanceCoaID
                WHERE 
                coaIsInput = 'Y'
                AND coaIsActive = 'Y'
                AND T_BeginningBalanceID IS NULL
                AND (coaAccountNo LIKE ? OR coaDescription LIKE ?)
                ";
        $qry = $this->db->query($sql, [$search, $search]);
        if (!$qry) {
            $this->sys_error_db("Error truncate", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
}
