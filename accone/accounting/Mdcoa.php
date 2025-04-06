<?php

class Mdcoa extends MY_Controller
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

    function search()
    {
        try {
            //# cek token valid
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

            $number_limit = 20;
            $number_offset = 0;
            if ($prm['current_page'] > 0) {
                $number_offset = ($prm['current_page'] - 1) * $number_limit;
            }

            $sql_filter = "SELECT count(*) as total 
                        FROM coa
                        WHERE coaIsActive = 'Y'
                        AND (coaDescription LIKE ? OR coaSubDescription LIKE ? OR coaAccountNo LIKE ?)";
            $qry_filter = $this->db->query($sql_filter, [$search, $search, $search]);
            $tot_count = 0;
            $tot_page = 0;
            if ($qry_filter) {
                $tot_count = $qry_filter->result_array()[0]["total"];
                $tot_page = ceil($tot_count / $number_limit);
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("coa count", $this->db);
                exit;
            }

            $sql = "SELECT 
                    coaID,
                    coaAccountNo,
                    coaDescription,
                    coaSubDescription,
                    coaAccountType,
                    coaIsInput,
                    coaReportSchedule,
                    coaCurrencyCode,
                    coaCashFlowCategory,
                    coaLevel
                    FROM coa
                    WHERE coaIsActive = 'Y'
                    AND (coaDescription LIKE ? OR coaSubDescription LIKE ? OR coaAccountNo LIKE ?)
                    ORDER BY coaAccountNo ASC
                    LIMIT ? OFFSET ?";
            $qry = $this->db->query($sql, [$search, $search, $search, $number_limit, $number_offset]);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select coa", $this->db);
                exit;
            }

            $result = array(
                "total" => $tot_page,
                "total_filter" => $tot_count,
                "records" => $rows,
                "sql" => $this->db->last_query()
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function addnewcoa()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $this->db->trans_begin();

            $prm = $this->sys_input;
            $accountno = $prm["accountno"];
            $description = $prm["description"];
            $subdescription = $prm["subdescription"];
            $accounttype = $prm["accounttype"];
            $currencycode = $prm["currencycode"];
            $cashflowcategory = $prm["cashflowcategory"];
            $reportschedule = $prm["reportschedule"];
            $level = $prm["level"];
            $isinput = $prm["isinput"];

            $query = "SELECT COUNT(*) as exist FROM coa WHERE coaIsActive = 'Y' AND coaAccountNo = ?";
            $exist_accountno = $this->db->query($query, [$accountno]);
            if ($exist_accountno) {
                $row = $exist_accountno->row()->exist;
            } else {
                $this->sys_error_db("exist error", $this->db);
                exit;
            }

            if ($row == 0) {
                $sql = "INSERT INTO coa(
                        coaAccountNo,
                        coaDescription,
                        coaSubDescription,
                        coaAccountType,
                        coaIsInput,
                        coaReportSchedule,
                        coaCurrencyCode,
                        coaCashFlowCategory,
                        coaCreated,
                        coaIsActive,
                        coaLevel
                        ) VALUES(?,?,?,?,?,?,?,?,NOW(),'Y',?)";
                $qry = $this->db->query($sql, [
                    $accountno,
                    $description,
                    $subdescription,
                    $accounttype,
                    $isinput,
                    $reportschedule,
                    $currencycode,
                    $cashflowcategory,
                    $level
                ]);

                if (!$qry) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("coa insert error", $this->db);
                    exit;
                }

                $this->db->trans_commit();
                $result = array("total" => 1, "records" => array("xid" => 0));
                $this->sys_ok($result);
            } else {
                $errors = array();
                if ($row != 0) {
                    array_push($errors, array('field' => 'account no', 'msg' => 'Account No sudah digunakan'));
                }

                $result = array("total" => -1, "errors" => $errors, "records" => array('status' => 'ERROR'));
                $this->sys_ok($result);
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function editcoa()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }
            $this->db->trans_begin();

            $prm = $this->sys_input;
            $coaid = $prm["coaid"];
            $accountno = $prm["accountno"];
            $description = $prm["description"];
            $subdescription = $prm["subdescription"];
            $accounttype = $prm["accounttype"];
            $currencycode = $prm["currencycode"];
            $cashflowcategory = $prm["cashflowcategory"];
            $reportschedule = $prm["reportschedule"];
            $level = $prm["level"];
            $isinput = $prm["isinput"];

            $sql = "UPDATE coa SET
                    coaAccountNo = ?,
                    coaDescription = ?,
                    coaSubDescription = ?,
                    coaAccountType = ?,
                    coaIsInput = ?,
                    coaReportSchedule = ?,
                    coaCurrencyCode = ?,
                    coaCashFlowCategory = ?,
                    coaLastUpdated = NOW(),
                    coaLevel = ?
                    WHERE coaID = ?";
            $qry = $this->db->query($sql, [
                $accountno,
                $description,
                $subdescription,
                $accounttype,
                $isinput,
                $reportschedule,
                $currencycode,
                $cashflowcategory,
                $level,
                $coaid
            ]);

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("coa update error", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $result = array("total" => 1, "records" => array("xid" => $coaid));
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function deletecoa()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();

            $prm = $this->sys_input;
            $coaid = $prm["coaid"];

            $sql = "UPDATE coa SET
                coaLastUpdated = NOW(),
                coaIsActive = 'N'
                WHERE coaID = ?";
            $qry = $this->db->query($sql, [$coaid]);

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("coa delete error", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $result = array("total" => 1, "records" => array("xid" => 0));
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
