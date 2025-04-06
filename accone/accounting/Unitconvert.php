<?php

class Unitconvert extends MY_Controller
{
    var $db;
    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        echo "Api: Training Playground";
    }

    function search()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            } else

                $prm = $this->sys_input;

            $search = "";
            if (isset($prm["search"])) {
                $search = trim($prm["search"]);
                if ($search != "") {
                    $search = "%" . $prm["search"] . "%";
                } else {
                    $search = "%%";
                }
            }

            $sortBy = $prm["sortBy"];
            $sortStatus = $prm["sortStatus"];
            if ($sortBy) {
                $q_sort = "ORDER BY " . $sortBy . " " . $sortStatus;
            }

            $number_offset = 0;
            $number_limit = 10;
            if ($prm["current_page"] > 0) {
                $number_offset = ($prm["current_page"] - 1) * $number_limit;
            }

            $sql_filter = "SELECT COUNT(*) as total 
            FROM (
                SELECT DISTINCT
                    UnitConvertID AS id
                    FROM unitconvert
                    JOIN itemunit AS fromitemunit ON UnitConvertFromItemUnitID = fromitemunit.ItemUnitID
                    JOIN itemunit AS toitemunit ON UnitConvertToItemUnitID = toitemunit.ItemUnitID 
                    WHERE
                    ( fromitemunit.ItemUnitName LIKE ? OR toitemunit.ItemUnitName LIKE ? ) AND UnitConvertIsActive = 'Y'
            ) x";

            $qry_filter = $this->db->query($sql_filter, [$search, $search]);

            $tot_count = 0;
            $tot_page = 0;
            if ($qry_filter) {
                $tot_count = $qry_filter->result_array()[0]["total"];
                $tot_page =  ceil($tot_count / $number_limit);
            } else {
                $this->sys_error_db("itemunitconvert count error", $this->db);
                exit;
            }

            $sql = "SELECT DISTINCT
            UnitConvertID AS id,
            fromitemunit.ItemUnitID AS FromItemUnitID,
            fromitemunit.ItemUnitName AS FromItemUnitName,
            toitemunit.ItemUnitID AS ToItemUnitID,
            toitemunit.ItemUnitName AS ToItemUnitName,
            UnitConvertAmount AS Amount
            FROM unitconvert
            JOIN itemunit AS fromitemunit ON UnitConvertFromItemUnitID = fromitemunit.ItemUnitID
            
            JOIN itemunit AS toitemunit ON UnitConvertToItemUnitID = toitemunit.ItemUnitID 
            WHERE
            ( fromitemunit.ItemUnitName LIKE ? OR toitemunit.ItemUnitName LIKE ? ) AND UnitConvertIsActive = 'Y'
            $q_sort
            LIMIT ? offset ?";

            $qry = $this->db->query($sql, array($search, $search, $number_limit, $number_offset));
            //echo $this->db->last_query();
            // print_r($rows = $qry->result_array());
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                //echo $this->db->last_query();
                $this->sys_error_db("Itemunitconvert select error", $this->db);
                exit;
            }

            $result = array(
                "total_page" => $tot_page,
                "total_filter" => $tot_count,
                "records" => $rows
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function searchitemx()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $sql = "SELECT DISTINCT ItemID as id_item,
            ItemName as name_item
            FROM item
            JOIN itemunitmap ON ItemID = itemUnitMapItemID
            WHERE ItemIsActive = 'Y'";

            $query = $this->db->query($sql);

            $rows = $query->result_array();
            if (!$query) {
                $this->db->trans_rollback();
                $error = array(
                    "message" => $this->db->error()["message"]
                );
                $this->sys_error_db($error);
                exit;
            }
            $result = array(
                "records" => $rows
            );
            $this->sys_ok($result);
            exit;
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    // konversi awal
    function fromitemunit()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $sql = "SELECT ItemUnitID as id,
            ItemUnitName as name
            FROM itemunit
            WHERE
            ItemUnitIsActive = 'Y'
            AND ItemUnitName LIKE CONCAT('%',?,'%')";

            $qry = $this->db->query($sql, array($prm['search']));
            //echo $this->db->last_query();
            if (!$qry) {
                $this->db->trans_rollback();
                $error = array(
                    "message" => $this->db->error()["message"]
                );
                $this->sys_error_db($error);
                exit;
            }
            $rows = $qry->result_array();

            $result = array(
                "records" => $rows
            );
            $this->sys_ok($result);
            exit;
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    // konversi akhir
    function toitemunit()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $id = "";

            $sql = "SELECT ItemUnitID as id,
            ItemUnitName as name
            FROM itemunit
            AND ItemUnitIsActive = 'Y'";

            $qry = $this->db->query($sql, array($id));
            if (!$qry) {
                $this->db->trans_rollback();
                $error = array(
                    "message" => $this->db->error()["message"]
                );
                $this->sys_error_db($error);
                exit;
            }
            $rows = $qry->result_array();

            $result = array(
                "records" => $rows
            );
            $this->sys_ok($result);
            exit;
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function save()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $prm = $this->sys_input;
            $userid = $this->sys_user["M_UserID"];

            // convert 
            $id_item = 0;
            $id_fromitemunit = 0;
            $id_toitemunit = 0;
            $amount = 0;

            if (isset($prm['id_fromitemunit'])) {
                $id_fromitemunit = trim($prm['id_fromitemunit']);
            }

            if (isset($prm['id_toitemunit'])) {
                $id_toitemunit = trim($prm['id_toitemunit']);
            }

            if (isset($prm['amount'])) {
                $amount = trim($prm['amount']);
            }

            // sql insert
            $sql = "INSERT INTO unitconvert(
                UnitConvertFromItemUnitID,
                UnitConvertToItemUnitID,
                UnitConvertAmount,
                UnitConvertCreated,
                UnitConvertLAstUpdated,
                UnitConvertUserID)
                VALUES ( ?, ?, ?, NOW(), NOW(), ?)";

            $qry = $this->db->query($sql, [
                $id_fromitemunit,
                $id_toitemunit,
                $amount,
                $userid
            ]);

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Itemunitconvert insert", $this->db);
                exit;
            }

            $insert_id = $this->db->insert_id();

            $sql_json_before = "SELECT *
            FROM unitconvert
            WHERE UnitConvertIsActive = 'Y'
            AND UnitConvertID = ?";

            $qry_json_before = $this->db->query($sql_json_before, [
                $insert_id
            ]);

            if (!$qry_json_before) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunitconvert select json", $this->db);
                exit;
            }

            $data_by_id = $qry_json_before->row();

            $json_after_log = json_encode($data_by_id);

            $sql_insert_log = "INSERT INTO acc_one_log.unitconvert_log(
                UnitConvertLogStatus,
                UnitConvertLogUnitConvertID,
                UnitConvertLogJSONBefore,
                UnitConvertLogJSONAfter,
                UnitConvertLogUserID,
                UnitConvertLogCreated    
            ) VALUES('ADD',?,NULL,?,?,NOW())";

            $qry_insert_log = $this->db->query($sql_insert_log, [
                $insert_id,
                $json_after_log,
                $userid
            ]);

            if (!$qry_insert_log) {
                $this->db->trans_rollback();
                $this->sys_error_db("insert log error", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $result = array(
                "total" => 1,
                "records" => array("xid" => 0)
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function edit()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;
            $userid = $this->sys_user['M_UserID'];
            $id = $prm["id"];

            $sql_data = "UPDATE unitconvert 
            SET UnitConvertFromItemUnitID  = ?,
            UnitConvertToItemUnitID = ?,
            UnitConvertAmount = ?,
            UnitConvertLAstUpdated = NOW(),
            UnitConvertUserID = ?
            WHERE UnitConvertID = ?";

            $qry_data = $this->db->query($sql_data, [
                $prm["id_fromitemunit"],
                $prm["id_toitemunit"],
                $prm["amount"],
                $userid,
                $id
            ]);

            if (!$qry_data) {
                $this->db->trans_rollback();
                $this->sys_error_db("Itemunitconvert update", $this->db);
                exit;
            }

            // json before
            $sql_json_before = "SELECT *
            FROM unitconvert
            WHERE UnitConvertIsActive = 'Y'
            AND UnitConvertID = ?";

            $qry_json_before = $this->db->query($sql_json_before, [
                $id
            ]);

            if (!$qry_json_before) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunitconvert select json before, $this->db");
                exit;
            }

            $data_before_by_id = $qry_json_before->row();

            $json_before = json_encode($data_before_by_id);

            // json after
            $sql_json_after = "SELECT *
            FROM unitconvert
            WHERE UnitConvertIsActive = 'Y'
            AND UnitConvertID = ?";

            $qry_json_after = $this->db->query($sql_json_after, [
                $id
            ]);

            if (!$qry_json_after) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunitconvert select json after, $this->db");
                exit;
            }

            $data_after_by_id = $qry_json_after->row();

            $json_after = json_encode($data_after_by_id);

            $sql_insert_log = "INSERT INTO acc_one_log.unitconvert_log(
                UnitConvertLogStatus,
                UnitConvertLogUnitConvertID,
                UnitConvertLogJSONBefore,
                UnitConvertLogJSONAfter,
                UnitConvertLogUserID,
                UnitConvertLogCreated    
            ) VALUES('EDIT',?,?,?,?,NOW())";

            $qry_insert_log = $this->db->query($sql_insert_log, [
                $id,
                $json_before,
                $json_after,
                $userid
            ]);

            if (!$qry_insert_log) {
                $this->db->trans_rollback();
                $this->sys_error_db("update log error, $this->db");
                exit;
            }

            $this->db->trans_commit();
            $result = array(
                "total" => 1,
                "records" => array("xid" => 0)
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function delete()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $prm = $this->sys_input;
            $userid = $this->sys_user['M_UserID'];
            $id = $prm["id"];

            $sql_data = "UPDATE unitconvert
            SET UnitConvertIsActive = 'N',
            UnitConvertLAstUpdated = NOW(),
            UnitConvertUserID = ?
            WHERE UnitConvertID = ?";

            $qry_data = $this->db->query($sql_data, [
                $userid,
                $id
            ]);

            if (!$qry_data) {
                $this->db->trans_commit();
                $this->sys_error_db("itemunitconvert delete", $this->db);
                exit;
            }

            // json before
            $sql_json_before = "SELECT *
            FROM unitconvert
            WHERE UnitConvertID = ?";

            $qry_json_before = $this->db->query($sql_json_before, [
                $id
            ]);

            if (!$qry_json_before) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunitconvert select json, $this->db");
                exit;
            }

            $data_before_by_id = $qry_json_before->row();

            $json_before = json_encode($data_before_by_id);

            $sql_insert_log = "INSERT INTO acc_one_log.unitconvert_log(
                UnitConvertLogStatus,
                UnitConvertLogUnitConvertID,
                UnitConvertLogJSONBefore,
                UnitConvertLogJSONAfter,
                UnitConvertLogUserID,
                UnitConvertLogCreated    
            ) VALUES('DELETE',?,NULL,?,?,NOW())";

            $qry_insert_log = $this->db->query($sql_insert_log, [
                $id,
                $json_before,
                $userid
            ]);

            if (!$qry_insert_log) {
                $this->db->trans_rollback();
                $this->sys_error_db("delete log error, $this->db");
                exit;
            }

            $this->db->trans_commit();
            $result = array(
                "total" => 1,
                "records" => array("xid" => 0)
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
