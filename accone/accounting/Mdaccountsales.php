<?php

class Mdaccountsales extends MY_Controller
{
    var $db;

    public function index()
    {
        echo "Account Sales API";
    }

    public function __construct()
    {
        parent::__construct();
    }

    function search()
    {
        try {
            // Validasi token
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;

            $searchSubGroupName = "";
            $searchOmzet = "";

            if (isset($payload['searchSubGroupName']) || isset($payload['searchOmzet'])) {
                $searchSubGroupName = trim($payload["searchSubGroupName"]);
                $searchOmzet = trim($payload["searchOmzet"]);
                if ($searchSubGroupName !== "") {
                    $searchSubGroupName = "%" . $payload["searchSubGroupName"] . "%";
                } else {
                    $searchSubGroupName = "%%";
                }

                if ($searchOmzet !== "") {
                    $searchOmzet = $payload["searchOmzet"];
                } else {
                    $searchOmzet = "%%";
                }
            }

            $queryFilter = "SELECT count(*) as total 
                            FROM map_AccSales
                            WHERE map_AccSalesNat_SubGroupIsActive = 'Y'
                            AND map_AccSalesNat_SubGroupName LIKE ?
                            AND map_AccSalesM_OmzetTypeID LIKE ?";
            $exec = $this->db->query($queryFilter, [$searchSubGroupName, $searchOmzet]);

            $numberLimit = 20;
            $numberOffset = 0;
            if ($payload["currentPage"] > 0) {
                $numberOffset = ($payload["currentPage"] - 1) * $numberLimit;
            }

            $totalCount = 0;
            $totalPage = 0;

            if ($exec) {
                $totalCount = $exec->result_array()[0]["total"];
                $totalPage = ceil($totalCount / $numberLimit);
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select account sales", $this->db);;
                exit;
            }

            $query = "SELECT
                      map_AccSalesNat_SubGroupID,
                      coaID,
                      coaAccountNo,
                      coaDescription,
                      coaSubDescription,
                      map_AccSalesNat_SubGroupCode,
                      map_AccSalesNat_SubGroupName,
                      map_AccSalesM_OmzetTypeID,
                      map_AccSalesM_OmzetTypeName
                      FROM map_AccSales
                      LEFT JOIN coa ON map_AccSales.map_AccSalesNat_SubGroupCoaID=coa.coaID
                      INNER JOIN m_omzettype ON map_AccSales.map_AccSalesM_OmzetTypeID=m_omzettype.M_OmzetTypeID
                      WHERE map_AccSalesNat_SubGroupIsActive = 'Y'
                      AND M_OmzetTypeIsActive = 'Y'
                      AND map_AccSalesNat_SubGroupName LIKE ? 
                      AND map_AccSalesM_OmzetTypeID LIKE ?
                      ORDER BY map_AccSalesNat_SubGroupID ASC 
                      LIMIT ? OFFSET ?";
            $exec = $this->db->query($query, [$searchSubGroupName, $searchOmzet, $numberLimit, $numberOffset]);

            if ($exec) {
                $rows = $exec->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select account sales", $this->db);
                exit;
            }

            $result = array(
                "total" => $totalPage,
                "totalFilter" => $totalCount,
                "records" => $rows,
                "sql" => $this->db->last_query()
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function subGroupName()
    {
        try {
            $query = "SELECT DISTINCT
                          map_AccSalesNat_SubGroupName
                          FROM map_AccSales
                          WHERE map_AccSalesNat_SubGroupIsActive = 'Y'
                          ORDER BY map_AccSalesNat_SubGroupName ASC";
            $exec = $this->db->query($query, []);
            if ($exec) {
                $rows = $exec->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select sub group name", $this->db);
                exit;
            }

            $result = array(
                "records" => $rows,
                "sql" => $this->db->last_query()
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function coa()
    {
        try {
            $query = "SELECT DISTINCT
                      coaID,
                      coaAccountNo,
                      coaDescription,
                      coaSubDescription
                      FROM coa
                      WHERE coaIsActive = 'Y'
                      ORDER BY coaAccountNo ASC";
            $exec = $this->db->query($query, []);
            if ($exec) {
                $rows = $exec->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select coa", $this->db);
                exit;
            }

            $result = array(
                "records" => $rows,
                "sql" => $this->db->last_query()
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function omzet()
    {
        try {
            $query = "SELECT DISTINCT
                      M_OmzetTypeID,
                      M_OmzetTypeName
                      FROM m_omzettype
                      WHERE M_OmzetTypeIsActive = 'Y'
                      ORDER BY M_OmzetTypeID ASC";
            $exec = $this->db->query($query, []);
            if ($exec) {
                $rows = $exec->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select omzet", $this->db);
                exit;
            }

            $result = array(
                "records" => $rows,
                "sql" => $this->db->last_query()
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function addNewAccountSales()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userid = $this->sys_user["M_UserID"];
            $coaId = $payload["coaId"];
            $subGroupCode = $payload["subGroupCode"];
            $subGroupName = $payload["subGroupName"];
            $omzetId = $payload["omzetId"];

            $query = "SELECT COUNT(*) as exist FROM map_AccSales WHERE map_AccSalesNat_SubGroupIsActive = 'Y' AND map_AccSalesNat_SubGroupCode = ? AND map_AccSalesNat_SubGroupName = ? AND map_AccSalesNat_SubGroupCoaID = ? AND map_AccSalesM_OmzetTypeID = ?";
            $exist = $this->db->query($query, [$subGroupCode, $subGroupName, $coaId, $omzetId]);
            if ($exist) {
                $row = $exist->row()->exist;
            } else {
                $this->sys_error_db("exist error", $this->db);
                exit;
            }

            if ($row == 0) {
                $queryOmzet = "SELECT M_OmzetTypeName FROM m_omzettype WHERE M_OmzetTypeID = ?";
                $exec = $this->db->query($queryOmzet, [$omzetId]);
                $omzetName = $exec->result_array()[0]["M_OmzetTypeName"];

                $sql = "INSERT INTO map_AccSales(
                        map_AccSalesNat_SubGroupCoaID,
                        map_AccSalesNat_SubGroupCode,
                        map_AccSalesNat_SubGroupName,
                        map_AccSalesM_OmzetTypeID,
                        map_AccSalesM_OmzetTypeName,
                        map_AccSalesNat_SubGroupCreated,
                        map_AccSalesNat_SubGroupLastUpdated,
                        map_AccSalesNat_SubGroupUserID,
                        map_AccSalesNat_SubGroupIsActive
                        ) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, 'Y')";
                $exec = $this->db->query($sql, [
                    $coaId,
                    $subGroupCode,
                    $subGroupName,
                    $omzetId,
                    $omzetName,
                    $userid
                ]);

                if (!$exec) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("account sales insert error", $this->db);
                    exit;
                }

                $this->db->trans_commit();
                $result = array("total" => 1, "records" => array("xId" => 0));
                $this->sys_ok($result);
            } else {
                $errors = array();
                if ($row != 0) {
                    array_push($errors, array('msg' => 'Data sudah ada'));
                }

                $result = array("total" => -1, "errors" => $errors, "records" => array('status' => 'ERROR'));
                $this->sys_ok($result);
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function editAccountSales()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $subGroupId = $payload["subGroupId"];
            $coaId = $payload["coaId"];
            $subGroupCode = $payload["subGroupCode"];
            $subGroupName = $payload["subGroupName"];
            $omzetId = $payload["omzetId"];

            $queryOmzet = "SELECT M_OmzetTypeName FROM m_omzettype WHERE M_OmzetTypeID = ?";
            $exec = $this->db->query($queryOmzet, [$omzetId]);
            $omzetName = $exec->result_array()[0]["M_OmzetTypeName"];

            $sql = "UPDATE map_AccSales SET
                    map_AccSalesNat_SubGroupCoaID = ?,
                    map_AccSalesNat_SubGroupCode = ?,
                    map_AccSalesNat_SubGroupName = ?,
                    map_AccSalesM_OmzetTypeID = ?,
                    map_AccSalesM_OmzetTypeName = ?,
                    map_AccSalesNat_SubGroupLastUpdated = NOW()
                    WHERE map_AccSalesNat_SubGroupID = ?";
            $exec = $this->db->query($sql, [$coaId, $subGroupCode, $subGroupName, $omzetId, $omzetName, $subGroupId]);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("account sales update error", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $result = array("total" => 1, "records" => array("xId" => $subGroupId));
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function softDelete()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $subGroupId = $payload["subGroupId"];

            $sql = "UPDATE map_AccSales SET
                    map_AccSalesNat_SubGroupLastUpdated = NOW(),
                    map_AccSalesNat_SubGroupIsActive = 'N'
                    WHERE map_AccSalesNat_SubGroupID = ?";
            $exec = $this->db->query($sql, [$subGroupId]);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("account sales delete error", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $result = array("total" => 1, "records" => array("xId" => 0));
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
