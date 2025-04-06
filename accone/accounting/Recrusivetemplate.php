<?php

class Recrusivetemplate extends MY_Controller
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
    function searchCoa()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $sql = "SELECT 
                coaID as id,
                coaAccountNo as number,
                coaDescription as keterangan,
                CONCAT(coaAccountNo, '-' ,coaDescription) as display
                FROM coa
                WHERE 
                coaIsInput = 'Y'
                AND coaIsActive = 'Y'
                AND (CONCAT(coaAccountNo, '-' ,coaDescription) LIKE ?)
                ";
        $qry = $this->db->query($sql, [$search]);
        if (!$qry) {
            $this->sys_error_db("Error truncate", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
    function insertTemplate()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $userid = $this->sys_user["M_UserID"];
        $startDate = $prm['startDate'];
        $endDate = $prm['endDate'];
        $tenor = $prm['tenor'];
        $prePaid = $prm['prePaid'];
        $kredit = $prm['kredit'];
        $debet = $prm['debet'];
        $bulanan = $prm['bulanan'];
        $sql = "SELECT fn_numbering('RJT') as number
                ";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("Error get number", $this->db);
            exit;
        }
        $number = $qry->row_array()['number'];
        $sql = "INSERT INTO t_recrusivejurnaltemplate(
                T_RecrusiveJurnalTemplateNumber,
                T_RecrusiveJurnalTemplateStartDate,
                T_RecrusiveJurnalTemplateEndDate,
                T_RecrusiveJurnalTemplateTenor,
                T_RecrusiveJurnalTemplatePrePaid,
                T_RecrusiveJurnalTemplateCreditCoaID,
                T_RecrusiveJurnalTemplateDebetCoaID,
                T_RecrusiveJurnalTemplateMonthly,
                T_RecrusiveJurnalTemplateCreatedUserID,
                T_RecrusiveJurnalTemplateCreated
                )
                VALUES(?,
                ?,?,?,?,?,?,?,?,NOW()
                )";
        $qry = $this->db->query($sql, [
            $number,
            $startDate,
            $endDate,
            $tenor,
            $prePaid,
            $kredit,
            $debet,
            $bulanan,
            $userid
        ]);
        if (!$qry) {
            $this->sys_error_db("Error Insert Recrusive Jurnal Template", $this->db);
            exit;
        }
        $this->sys_ok("Success tambah data");
    }
    function updateTemplate()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;

        $userid = $this->sys_user["M_UserID"];
        $startDate = $prm['startDate'];
        $id = $prm['id'];
        $endDate = $prm['endDate'];
        $tenor = $prm['tenor'];
        $prePaid = $prm['prePaid'];
        $kredit = $prm['kredit'];
        $debet = $prm['debet'];
        $bulanan = $prm['bulanan'];

        $sql = "UPDATE t_recrusivejurnaltemplate SET 
                T_RecrusiveJurnalTemplateStartDate = ?,
                T_RecrusiveJurnalTemplateEndDate = ?,
                T_RecrusiveJurnalTemplateTenor = ?,
                T_RecrusiveJurnalTemplatePrePaid = ?,
                T_RecrusiveJurnalTemplateCreditCoaID = ?,
                T_RecrusiveJurnalTemplateDebetCoaID = ?,
                T_RecrusiveJurnalTemplateMonthly = ?,
                T_RecrusiveJurnalTemplateLastUpdatedUserID = '{$userid}',
                T_RecrusiveJurnalTemplateLastUpdated = NOW()
                WHERE T_RecrusiveJurnalTemplateID = ?";
        $qry = $this->db->query($sql, [
            $startDate,
            $endDate,
            $tenor,
            $prePaid,
            $kredit,
            $debet,
            $bulanan,
            $id
        ]);
        if (!$qry) {
            $this->sys_error_db("Error Update Recrusive Jurnal Template", $this->db);
            exit;
        }
        $this->sys_ok("Success update data");
    }
    function deleteTemplate()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;

        $userid = $this->sys_user["M_UserID"];

        $id = $prm['id'];

        $sql = "UPDATE t_recrusivejurnaltemplate SET 
                T_RecrusiveJurnalTemplateIsActive = 'N',
                T_RecrusiveJurnalTemplateDeletedUserID = '{$userid}',
                T_RecrusiveJurnalTemplateDeleted = NOW()
                WHERE T_RecrusiveJurnalTemplateID = ?";
        $qry = $this->db->query($sql, [
            $id
        ]);
        if (!$qry) {
            $this->sys_error_db("Error delete Recrusive Jurnal Template", $this->db);
            exit;
        }
        $this->sys_ok("Success delete data");
    }
    function search()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $userid = $this->sys_user["M_UserID"];
        $search = '%' . $prm['search'] . '%';
        $page = $prm["page"];

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
                COUNT(T_RecrusiveJurnalTemplateID) as total
                FROM t_recrusivejurnaltemplate
                JOIN coa c
                ON T_RecrusiveJurnalTemplateCreditCoaID = c.coaID
                JOIN coa d 
                ON T_RecrusiveJurnalTemplateDebetCoaID = d.coaID
                WHERE T_RecrusiveJurnalTemplateNumber LIKE ?
                AND T_RecrusiveJurnalTemplateIsActive = 'Y'
                ";
        $qry = $this->db->query($sql, [
            $search,
        ]);
        if (!$qry) {
            $this->sys_error_db("Error search", $this->db);
            exit;
        }
        $total = $qry->row_array()['total'];
        $sql = "SELECT
                T_RecrusiveJurnalTemplateID as id,
                c.coaID as creditCoaID,
                CONCAT(c.coaAccountNo, '-' ,c.coaDescription) as creditName,
                d.coaID as debitCoaID,
                CONCAT(d.coaAccountNo, '-' ,d.coaDescription) as debitName,
                T_RecrusiveJurnalTemplateNumber as number,
                T_RecrusiveJurnalTemplatePrePaid as prePaid,
                DATE_FORMAT(T_RecrusiveJurnalTemplateStartDate , '%d-%m-%Y') as startDate,
                T_RecrusiveJurnalTemplateStartDate as startDateVal, 
                DATE_FORMAT(T_RecrusiveJurnalTemplateEndDate, '%d-%m-%Y') as endDate,
                T_RecrusiveJurnalTemplateEndDate as endDateVal,
                T_RecrusiveJurnalTemplateTenor as tenor,
                T_RecrusiveJurnalTemplateMonthly as bulanan
                FROM t_recrusivejurnaltemplate
                JOIN coa c
                ON T_RecrusiveJurnalTemplateCreditCoaID = c.coaID
                JOIN coa d 
                ON T_RecrusiveJurnalTemplateDebetCoaID = d.coaID
                WHERE T_RecrusiveJurnalTemplateNumber LIKE ?
                AND T_RecrusiveJurnalTemplateIsActive = 'Y'
                LIMIT ? OFFSET ?";
        $qry = $this->db->query($sql, [
            $search,
            $ROW_PER_PAGE,
            $start_offset
        ]);
        if (!$qry) {
            $this->sys_error_db("Error search", $this->db);
            exit;
        }
        $rst = array(
            "total" => ceil($total / $ROW_PER_PAGE),
            "records" => $qry->result_array()
        );
        $this->sys_ok($rst);
    }
}
