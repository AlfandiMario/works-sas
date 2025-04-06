<?php
class Reverse extends MY_Controller
{
    var $db;
    public function index()
    {
        echo "REVERSE JURNAL API";
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
                ORDER BY periodeMonth DESC
                ";
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
            $journalno = $prm["journalno"];
            // $prm['current_page'] = isset($prm['current_page']) ? $prm['current_page'] : 1;

            if (intval($periodeid) == 0) {
                $this->sys_error("Periode belum dipilih, silahkan dipilih dulu.");
                $this->db->trans_rollback();
                exit;
            }

            $sql_where = "WHERE jurnalIsActive = 'Y'";
            $sql_param = array();
            if ($periodeid != "") {
                if ($sql_where != "") {
                    $sql_where .= " AND ";
                }
                $sql_where .= " jurnalperiodeID = ?";
                $sql_param[] = $periodeid;
            }
            if ($journalno != "") {
                if ($sql_where != "") {
                    $sql_where .= " AND ";
                }
                $sql_where .= " jurnalNo LIKE ? ";
                $sql_param[] = "%$journalno%";
            }

            $sql = "SELECT 
                    jurnalID,
                    jurnalM_BranchCode,
                    jurnalNo,
                    jurnalTitle,
                    DATE_FORMAT(jurnalDate, '%d-%m-%Y') as jurnalDate,
                    jurnalType,
                    jurnalTxID,
                    jurnalTxCoaID,
                    junalTxDescription,
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
                    END as type,
                    jurnalArID,
                    jurnalArJurnalTxID,
                    jurnalArM_CompanyID,
                    jurnalArRefNo,
                    jurnalArM_CompanyCode,
                    jurnalArM_CompanyName
                    FROM jurnal 
                    JOIN jurnal_tx ON jurnalID = jurnalTxJurnalID
                    AND jurnalTxIsActive = 'Y'
                    LEFT JOIN jurnal_ar ON jurnalTxID = jurnalArJurnalTxID
                        AND jurnalArIsActive = 'Y'
                    JOIN coa ON jurnalTxCoaID = coaID
                    AND coaIsActive = 'Y'
                    JOIN periode ON jurnalperiodeID = periodeID
                    AND periodeIsActive = 'Y'
                    $sql_where";
            $qry = $this->db->query($sql, $sql_param);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->sys_error_db("select jurnal error", $this->db);
                exit;
            }

            // $sql_count = "SELECT 
            //         jurnalID,
            //         IFNULL(SUM(jurnalTxDebit),0) as debit,
            //         IFNULL(SUM(jurnalTxCredit),0) as credit
            //         FROM jurnal 
            //         JOIN jurnal_tx ON jurnalID = jurnalTxJurnalID
            //         AND jurnalTxIsActive = 'Y'
            //         LEFT JOIN jurnal_ar ON jurnalTxID = jurnalArJurnalTxID
            //             AND jurnalArIsActive = 'Y'
            //         JOIN coa ON jurnalTxCoaID = coaID
            //         AND coaIsActive = 'Y'
            //         JOIN periode ON jurnalperiodeID = periodeID
            //         AND periodeIsActive = 'Y'
            //         $sql_where";
            // $qry_count = $this->db->query($sql_count, $sql_param);
            // if ($qry_count) {
            //     $sumtotal = $qry_count->row_array();
            // } else {
            //     $this->sys_error_db("select sum jurnal error", $this->db);
            //     exit;
            // }
            // $totalDebit = $sumtotal['debit'];
            // $totalCredit = $sumtotal['credit'];
            // $totalBalance = 0;


            // $totalBalance = $totalDebit - $totalCredit;
            // $arrTotal = array('debit' => $totalDebit, 'credit' => $totalCredit, 'balance' => $totalBalance);


            // $jurnalNumbers = [];
            // $description = [];
            // if ($rows) {
            //     $coaAccountNoCheck = "";

            //     foreach ($rows as $key => $value) {

            //         if ($journalno != "") {

            //             if (!in_array($value['jurnalNo'], $jurnalNumbers)) {
            //                 $jurnalNumbers[] = $value['jurnalNo'];
            //             }

            //             if ($coaAccountNoCheck != $value['coaAccountNo']) {
            //                 $description[] = $value['junalTxDescription'];
            //                 $coaAccountNoCheck = $value['coaAccountNo'];
            //             }
            //         }
            //     }
            // }

            // $selectjournal = array('jurnalnumber' => $jurnalNumbers, 'description' => $description);

            $result = array("records" => $rows, "sql" => $this->db->last_query());
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function numbering($jurnalno)
    {
        $numbers = 'R' . '-' . $jurnalno;
        return $numbers;
    }

    function savereverse()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $userid = $this->sys_user['M_UserID'];
            $prm = $this->sys_input;
            $reverse = $prm['reversejournal'];
            // print_r($reverse[0]);
            // exit;

            $sqlj = "INSERT INTO jurnal(
                        jurnalM_BranchCode,
                        jurnalperiodeID,
                        jurnalNo,
                        jurnalTitle,
                        jurnalDate,
                        jurnalType,
                        jurnalM_UserID)
                        SELECT 
                            jurnalM_BranchCode,
                            jurnalperiodeID,
                            '{$reverse[0]['jurnalNo']}',
                            '{$reverse[0]['jurnalTitle']}',
                            NOW(),
                            jurnalType,
                            {$userid}
                        FROM jurnal
                        WHERE jurnalID = {$reverse[0]['jurnalID']}";
            $qryj = $this->db->query($sqlj);
            if (!$qryj) {
                $this->sys_error_db("Insert jurnal", $this->db);
                $this->db->trans_rollback();
                exit;
            }

            $insertedID = $this->db->insert_id();

            foreach ($reverse as $k => $v) {
                // print_r($v[0]);
                // exit;

                $sql_tx = "INSERT INTO jurnal_tx(
                            jurnalTxJurnalID,
                            jurnalTxCoaID,
                            junalTxDescription,
                            jurnalTxDebit,
                            jurnalTxCredit,
                            jurnalTxCreated,
                            jurnalTxM_UserID) VALUES(?,?,?,?,?,NOW(),?)";
                $qry_tx = $this->db->query($sql_tx, [
                    $insertedID,
                    $v['jurnalTxCoaID'],
                    $v['junalTxDescription'],
                    $v['jurnalTxDebit'],
                    $v['jurnalTxCredit'],
                    $userid
                ]);
                if (!$qry_tx) {
                    $this->sys_error_db("Insert jurnal tx", $this->db);
                    $this->db->trans_rollback();
                    exit;
                }
                $insertedtxID = $this->db->insert_id();

                if ($v['jurnalType'] == 'AR') {
                    $sql_ar = "INSERT INTO jurnal_ar(
                        jurnalArJurnalTxID,
                        jurnalArM_CompanyID,
                        jurnalArRefNo,
                        jurnalArM_CompanyCode,
                        jurnalArM_CompanyName,
                        jurnalArCreated,
                        jurnalArM_UserID
                        ) VALUES(?,?,?,?,?,NOW(),?)";
                    $qry_ar = $this->db->query($sql_ar, [
                        $insertedtxID,
                        $v['jurnalArM_CompanyID'],
                        $v['jurnalArRefNo'],
                        $v['jurnalArM_CompanyCode'],
                        $v['jurnalArM_CompanyName'],
                        $userid
                    ]);
                    if (!$qry_ar) {
                        $this->sys_error_db("Insert jurnal ar", $this->db);
                        $this->db->trans_rollback();
                        exit;
                    }
                }
            }

            $this->db->trans_commit();
            $result = array("total" => 1);
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
