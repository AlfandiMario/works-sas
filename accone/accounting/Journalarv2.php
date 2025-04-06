<?php

class Journalarv2 extends MY_Controller
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
            //nambah filter login
            $loginLevel = $this->sys_user["loginLevel"];
            $w_level_filter = "";
            if ($loginLevel == "branch") {
                $w_level_filter = " and jurnalM_BranchCode = '"
                    . $this->sys_user["M_BranchCode"] . "'";
            }
            if ($loginLevel == "regional") {
                $w_level_filter = " and jurnalS_RegionalID= '"
                    . $this->sys_user["S_RegionalID"] . "'";
            }
            //eof filter login

            $prm = $this->sys_input;

            $periodeid = $prm["periodeid"];
            $xdate = $prm["xdate"];

            $sql = "SELECT 
                    jurnalID,
                    jurnalNo,
                    jurnalTitle,
                    jurnalDescription,
                    jurnalM_BranchCode,
                    DATE_FORMAT(jurnalDate, '%d-%m-%Y') as jurnalDate,
                    jurnalIsPosted,
                    M_BranchCompanyName,
                    S_RegionalID,
                    S_RegionalName,
                    M_BranchID,
                    M_BranchName,
                    periodeID,
                    periodeYear,
                    periodeMonth,
                    periodeName,
                    periodeStartDate,
                    periodeEndDate,
                    JurnalTypeID,
                    JurnalTypeCode,
                    JurnalTypeName,
                    JurnalTypeAccesRight,
                    '' as ErrStatus,
                    '' as ErrMsg,
                    '' as detailtx
                    FROM jurnal
                    JOIN m_branch_company ON jurnalM_BranchCompanyID = M_BranchCompanyID AND M_BranchCompanyIsActive = 'Y'
                    JOIN s_regional ON JurnalS_RegionalID = S_RegionalID AND S_RegionalIsActive = 'Y'
                    JOIN periode ON jurnalperiodeID = periodeID AND periodeIsActive = 'Y'
                    JOIN jurnal_type ON jurnalJurnalTypeID = JurnalTypeID AND JurnalTypeIsActive = 'Y' AND JurnalTypeIsAuto = 'Y'
                    AND JurnalTypeCode = 'AUTODAILYAR'
                    LEFT JOIN m_branch ON jurnalM_BranchCode = M_BranchCode AND M_BranchIsActive = 'Y'
                    WHERE jurnalIsActive = 'Y'
                    AND jurnalperiodeID = {$periodeid}
                    AND DATE(jurnalDate) = '{$xdate}'
                    $w_level_filter
                    GROUP BY jurnalID
                    ORDER BY jurnalID ASC";

            $sql_total = "SELECT count(*) as total FROM ($sql) as x";
            $qry_total = $this->db->query($sql_total);

            $number_offset = 0;
            $number_limit = 10;

            if ($prm["current_page"] > 0) {
                $number_offset = ($prm["current_page"] - 1) * $number_limit;
            }

            $totalCount = 0;
            $totalPage = 0;
            if ($qry_total) {
                $totalCount = $qry_total->result_array()[0]["total"];
                $totalPage = ceil($totalCount / $number_limit);
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select jurnal count error", $this->db);;
                exit;
            }

            $sql_select = $sql . " LIMIT $number_limit OFFSET $number_offset";
            $qry = $this->db->query($sql_select);

            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select jurnal error", $this->db);
                exit;
            }

            foreach ($rows as $key => $value) {
                $sql_err = "SELECT JurnalErr_ID,
                            JurnalErr_Msg
                            FROM jurnal_errors 
                            WHERE JurnalErr_IsActive = 'Y'
                            AND JurnalErr_JurnalID = ?";
                $qry_err = $this->db->query($sql_err, array($value["jurnalID"]));
                if (!$qry_err) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("select jurnal msg error", $this->db);
                    exit;
                }

                $rows_err = $qry_err->result_array();

                // Decode JSON di dalam kolom JurnalErr_Msg
                foreach ($rows_err as &$row) {
                    $row['JurnalErr_Msg'] = json_decode($row['JurnalErr_Msg'], true);
                }
                if (count($rows_err) > 0) {
                    $rows[$key]["ErrStatus"] = 'Y';
                    $rows[$key]["ErrMsg"] = $rows_err;
                } else {
                    $rows[$key]["ErrStatus"] = 'N';
                    $rows[$key]["ErrMsg"] = [];
                }

                $sql_detail = "SELECT 
                                jurnalTxID,
                                jurnalTxJurnalID,
                                jurnalTxCoaID,
                                jurnalTxDescription,
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
                                GROUP_CONCAT(jurnalAddOnCode SEPARATOR ', ') as jurnalAddOnCode,
                                GROUP_CONCAT(jurnalAddOnValue SEPARATOR ' | ') as jurnalAddOnValue
                                FROM jurnal_tx
                                JOIN coa ON jurnalTxCoaID = coaID AND coaIsActive = 'Y'
                                LEFT JOIN jurnal_addon ON jurnalTxID = jurnalAddOnJurnalTxID AND jurnalAddOnIsActive = 'Y'
                                AND (jurnalAddOnCode = 'CMPNYNAME' OR jurnalAddOnCode = 'CMPNYNUM')
                                WHERE jurnalTxIsActive = 'Y'
                                AND jurnalTxJurnalID = ?
                                GROUP BY jurnalTxID";
                $qry_detail = $this->db->query($sql_detail, array($value["jurnalID"]));
                if (!$qry_detail) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("select jurnal tx error", $this->db);
                    exit;
                }

                // echo $this->db->last_query();
                // exit;

                $rows_detail = $qry_detail->result_array();
                if (count($rows_detail) > 0) {
                    $rows[$key]["detailtx"] = $rows_detail;
                } else {
                    $rows[$key]["detailtx"] = [];
                }
            }

            $result = array(
                'total' => $totalPage,
                'totalfilter' => $totalCount,
                "records" => $rows
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
