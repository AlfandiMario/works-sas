<?php

class Journalcash extends MY_Controller
{
    var $db;
    public function index()
    {
        echo "PENDAPATAN CASH API";
    }
    public function __construct()
    {
        parent::__construct();
    }

    function getPeriode()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $search = "";
            $number_limit = 10;
            $tot_count = 0;

            if (isset($prm['search'])) {
                $search = trim($prm["search"]);
                if ($search != "") {
                    $search = '%' . $prm['search'] . '%';
                } else {
                    $search = '%%';
                }
            }

            $sql = "SELECT 
                periodeID AS id,
                periodeYear,
                periodeMonth,
                CONCAT(periodeYear, ' - ',periodeMonth) as yearandmonth,
                periodeName,
                CONCAT(DATE_FORMAT(periodeStartDate, '%d %M %Y'), ' - ', DATE_FORMAT(periodeEndDate, '%d %M %Y')) as periode
                FROM periode 
                WHERE periodeIsActive = 'Y'
                AND periodeIsClosed = 'N'
                ORDER BY periodeMonth DESC";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->sys_error_db("select period", $this->db);
                exit;
            }
            $rst = $qry->result_array();
            $this->sys_ok($rst);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function search()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $periodeid = $prm["periodeid"];
            $xdate = $prm["xdate"];

            $sql = "SELECT 
                    jurnalID,
                    jurnalM_BranchCode,
                    jurnalNo,
                    jurnalTitle,
                    DATE_FORMAT(jurnalDate, '%d-%m-%Y') as jurnalDate,
                    jurnalTxID,
                    jurnalTxDebit,
                    jurnalTxCredit,
                    coaID,
                    coaAccountNo,
                    coaDescription,
                    coaSubDescription,
                    coaAccountType,
                    coaIsInput,
                    coaReportSchedule,
                    coaCurrencyCode,
                    coaCashFlowCategory,
                    periodeID,
                    periodeYear,
                    periodeMonth,
                    periodeName,
                    periodeStartDate,
                    periodeEndDate,
                    periodeIsClosed,
                    CASE 
                        WHEN coaAccountType = 'DB' THEN jurnalTxDebit
                        WHEN coaAccountType = 'CR' THEN jurnalTxCredit
                    END as value,
                    JurnalTypeID,
                    JurnalTypeCode,
                    JurnalTypeName,
                    JurnalTypeAccesRight,
                    JurnalTypeIsAuto
                    FROM jurnal 
                    JOIN jurnal_tx ON jurnalID = jurnalTxJurnalID
                    AND jurnalTxIsActive = 'Y'
                    JOIN coa ON jurnalTxCoaID = coaID
                    AND coaIsActive = 'Y'
                    JOIN periode ON jurnalperiodeID = periodeID
                    AND periodeIsActive = 'Y'
                    JOIN jurnal_type ON jurnalJurnalTypeID = JurnalTypeID AND JurnalTypeIsActive = 'Y' 
                    AND JurnalTypeIsAuto = 'Y' AND JurnalTypeCode = 'AUTODAILYSALES'
                    WHERE jurnalIsActive = 'Y'
                    AND jurnalperiodeID = ?
                    AND jurnalDate = ?";
            $qry = $this->db->query($sql, [$periodeid, $xdate]);

            // echo $this->db->last_query();
            // exit;
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->sys_error_db("select jurnal", $this->db);
                exit;
            }

            $totalDebit = 0;
            $totalCredit = 0;
            $totalBalance = 0;

            for ($i = 0; $i < count($rows); $i++) {
                // print_r($rows[$i]);
                // exit;
                $data = $rows[$i];
                if ($data['coaAccountType'] == 'DB') {
                    $totalDebit = $totalDebit + floatval($data['value']);
                }
                if ($data['coaAccountType'] == 'CR') {
                    $totalCredit = $totalCredit + floatval($data['value']);
                }
            }

            $totalBalance = $totalDebit - $totalCredit;
            $arrTotal = array('debit' => $totalDebit, 'credit' => $totalCredit, 'balance' => $totalBalance);

            $result = array(
                'records' => $rows,
                'total' => $arrTotal
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
