<?php
class Mdperiode extends MY_Controller
{
    var $db;
    public function index()
    {
        echo "MD Periode API";
    }
    
    public function __construct()
    {
        parent::__construct();
    }

    function listperiode()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $sql = "SELECT
                periodeID,
                periodeYear,
                periodeMonth,
                periodeName,
                periodeStartDate,
                periodeEndDate,
                periodeIsActive,
                periodeIsClosed
                FROM periode
                WHERE periodeIsActive = 'Y'";
            $query = $this->db->query($sql);
            $rows = $query->result_array();

            $sql_tot = "SELECT count(periodeID) as total
                FROM periode
                WHERE periodeIsActive = 'Y'";

            $query_tot = $this->db->query($sql_tot);
            $total_list = 0;
            if ($query_tot) {
                $total_list = $query_tot->result_array()[0]['total'];
            } else {
            	$this->sys_error_db("periode count", $this->db);
            	exit;
            }

            $result = array(
                "total" => $total_list,
                "records" => $rows
            );
            $this->sys_ok($result);
            
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function deleteperiode() 
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;
            $periodeID = $prm['id'];

            $sql = "UPDATE periode SET 
            periodeIsActive = 'N',
            periodeLastUpdated = now()
            WHERE periodeID = ?";

            $query = $this->db->query(
                $sql,
                array($periodeID)
            );
            if (!$query) {
                $this->sys_error_db("update periode active");
                exit;
            }

            $result = array(
                "total" => 1,
                "data" => array('status' => 'OK')
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
			$message = $exc->getMessage();
			$this->sys_error($message);
        }
    }

    function addperiode()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $prm = $this->sys_input;
            $periodeyear = $prm["periodeYear"];
            $periodemonth = $prm["periodeMonth"];
            $periodename = $prm["periodeName"];
            $periodestart = $prm["periodeStartDate"];
            $periodeend = $prm["periodeEndDate"];

            $sql = "INSERT INTO `periode` (
            periodeYear,
            periodeMonth,
            periodeName,
            periodeStartDate,
            periodeEndDate
            ) VALUES (?,?,?,?,?)";

            $qry = $this->db->query($sql, [
                $periodeyear,
                $periodemonth,
                $periodename,
                $periodestart,
                $periodeend
            ]);

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("periode insert error", $this->db);
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

    function editperiode()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $prm = $this->sys_input;
            $periodeid = $prm["periodeID"];
            $periodeyear = $prm["periodeYear"];
            $periodemonth = $prm["periodeMonth"];
            $periodename = $prm["periodeName"];
            $periodestart = $prm["periodeStartDate"];
            $periodeend = $prm["periodeEndDate"];

            $sql = "UPDATE periode SET
            periodeYear = ?,
            periodeMonth = ?,
            periodeName = ?,
            periodeStartDate = ?,
            periodeEndDate = ?
            WHERE periodeID = ?";

            $qry = $this->db->query($sql, [
                $periodeyear,
                $periodemonth,
                $periodename,
                $periodestart,
                $periodeend,
                $periodeid
            ]);

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("periode error edit", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $result = array("total" => 1, "records" => array("periodeID" => $periodeid));
            $this->sys_ok($result);

        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}