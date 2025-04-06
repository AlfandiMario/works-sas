<?php

class PurchaseRequestPo extends MY_Controller
{
    var $db;

    public function index()
    {
        echo "Purchase Request/Requester API";
    }

    public function __construct()
    {
        parent::__construct();
    }

    function getVendor()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $search = '%%';
            if (isset($payload['search'])) {
                $search = trim($payload["search"]);
                if ($search != "") {
                    $search = '%' . $search . '%';
                } else {
                    $search = '%%';
                }
            }

            $sql = "SELECT SupplierID,
                    SupplierCode,
                    SupplierName
                    FROM supplier
                    WHERE SupplierIsActive = 'Y'
                    AND SupplierName LIKE '{$search}'
                    ORDER BY SupplierID ASC ";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("select supplier", $this->db);;
                exit;
            }

            $rows = $qry->result_array();

            if ($rows) {
                array_push($rows, ["SupplierID" => "0", "SupplierCode" => "All", "SupplierName" => "All"]);
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

    function getVendorForm()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $sql = "SELECT SupplierID,
                    SupplierCode,
                    SupplierName
                    FROM supplier
                    WHERE SupplierIsActive = 'Y'
                    ORDER BY SupplierID ASC ";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("select supplier", $this->db);;
                exit;
            }

            $rows = $qry->result_array();

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

    function getItemType()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $search = '%%';
            if (isset($payload['search'])) {
                $search = trim($payload["search"]);
                if ($search != "") {
                    $search = '%' . $search . '%';
                } else {
                    $search = '%%';
                }
            }

            $sql = "SELECT ItemTypeID,
                    ItemTypeName
                    FROM item_type
                    WHERE ItemTypeIsActive = 'Y'
                    AND ItemTypeName LIKE '{$search}'
                    ORDER BY ItemTypeID ASC";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("select supplier", $this->db);;
                exit;
            }

            $rows = $qry->result_array();

            if ($rows) {
                array_push($rows, ["ItemTypeID" => "0", "ItemTypeName" => "All"]);
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

    function getItemTypeForm()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $sql = "SELECT ItemTypeID,
                    ItemTypeName
                    FROM item_type
                    WHERE ItemTypeIsActive = 'Y'
                    ORDER BY ItemTypeID ASC";
            $qry = $this->db->query($sql, []);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("select supplier", $this->db);;
                exit;
            }

            $rows = $qry->result_array();
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

    function search()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $startDate = $payload["startDate"];
            $endDate = $payload["endDate"];
            $supplierId = $payload["supplierId"];
            $typeId = $payload["typeId"];
            $search = "";
            if (isset($payload['search'])) {
                $search = trim($payload["search"]);
                if ($search != "") {
                    $search = '%' . $payload['search'] . '%';
                } else {
                    $search = '%%';
                }
            }

            $filterSuplier = "";
            $filterType = "";
            if (intval($supplierId) > 0) {
                $filterSuplier .= " AND SupplierID = {$supplierId}";
            }
            if (intval($typeId) > 0) {
                $filterType .= " AND ItemTypeID = {$typeId}";
            }

            $sql = "SELECT purchase_request.*,
                    SupplierID,
                    SupplierCode,
                    SupplierName,
                    ItemTypeID,
                    ItemTypeName,
                    S_RegionalID,
                    S_RegionalName,
                    M_BranchID,
                    M_BranchCode,
                    M_BranchName
                    FROM purchase_request
                    JOIN supplier ON PurchaseRequestSupplierID = SupplierID AND SupplierIsActive = 'Y'
                    JOIN item_type ON PurchaseRequestItemTypeID = ItemTypeID AND ItemTypeIsActive = 'Y'
                    JOIN s_regional ON PurchaseRequestS_RegionalID = S_RegionalID AND S_RegionalIsActive = 'Y'
                    LEFT JOIN m_branch ON PurchaseRequestM_BranchCode = M_BranchCode AND  M_BranchIsActive = 'Y'
                    WHERE PurchaseRequestIsActive = 'Y'
                    AND (PurchaseRequestDate BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59')
                    AND (PurchaseRequestNumber LIKE '{$search}')
                    $filterSuplier $filterType
                    ORDER BY PurchaseRequestNumber DESC";

            $sqlTotal = "SELECT count(*)  as total FROM ($sql) as x";
            $qryTotal = $this->db->query($sqlTotal);

            $number_offset = 0;
            $number_limit = 10;

            if ($payload["current_page"] > 0) {
                $number_offset = ($payload["current_page"] - 1) * $number_limit;
            }

            $totalCount = 0;
            $totalPage = 0;
            if ($qryTotal) {
                $totalCount = $qryTotal->result_array()[0]["total"];
                $totalPage = ceil($totalCount / $number_limit);
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select purcahse request count error", $this->db);;
                exit;
            }

            $sql_select = $sql . " LIMIT $number_limit OFFSET $number_offset";
            $qry = $this->db->query($sql_select);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select purcahse request error", $this->db);
                exit;
            }

            $result = array(
                "totalPage" => $totalPage,
                "totalFilter" => $totalCount,
                "records" => $rows
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function getRegional()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $sql = "SELECT S_RegionalID,
                S_RegionalName
                FROM s_regional
                WHERE S_RegionalIsActive = 'Y'
                ORDER BY S_RegionalName ASC";
            $qry = $this->db->query($sql, []);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select regional", $this->db);
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

    function getBranch()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }
            $payload = $this->sys_input;

            $regionalId = $payload["regionalId"];
            $query = "SELECT DISTINCT
                      M_BranchCode,
                      M_BranchName
                      FROM m_branch
                      WHERE M_BranchIsActive = 'Y'
                      AND M_BranchS_RegionalID = ?
                      ORDER BY M_BranchName ASC";
            $exec = $this->db->query($query, [$regionalId]);
            if ($exec) {
                $rows = $exec->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select branch", $this->db);
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

    function searchDetail()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;

            $queryCount = "SELECT count(*) as total
                           FROM purchase_request_direct_detail
                           WHERE PurchaseRequestDirectDetailIsActive = 'Y' 
                           AND PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload['PRID']}";
            $exec = $this->db->query($queryCount, []);

            $totalCount = 0;

            if ($exec) {
                $totalCount = $exec->result_array()[0]["total"];
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select purchase request detail", $this->db);;
                exit;
            }

            $query = "SELECT *, 
                      ROW_NUMBER() OVER(ORDER BY PurchaseRequestDirectDetailID) RowNumber 
                      FROM purchase_request_direct_detail 
                      WHERE PurchaseRequestDirectDetailIsActive = 'Y' 
                      AND PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload['PRID']}
                      ORDER BY PurchaseRequestDirectDetailStatus ASC, 
                      PurchaseRequestDirectDetailID ASC";
            $exec = $this->db->query($query, []);

            if ($exec) {
                $rows = $exec->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select purchase request detail", $this->db);
                exit;
            }

            $result = array(
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

    function saveRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $pdSql = "SELECT `fn_numbering`('PD') AS PD";
            $exec = $this->db->query($pdSql, []);
            $pd = "";
            $dateNow = date('Y-m-d');

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("purchase request insert error", $this->db);
                exit;
            } else {
                $pd = $exec->result_array()[0]["PD"];
            }

            $sql = "INSERT INTO purchase_request_direct(
                    PurchaseRequestDirectNumber,
                    PurchaseRequestDirectDate,
                    PurchaseRequestDirectDateUse,
                    PurchaseRequestDirectM_BranchCode,
                    PurchaseRequestDirectDescription,
                    PurchaseRequestDirectNote,
                    PurchaseRequestDirectTotalEstimation,
                    PurchaseRequestDirectTotalPaid,
                    PurchaseRequestDirectTotalRealitation,
                    PurchaseRequestDirectStatus,
                    PurchaseRequestApprovedDate,
                    PurchaseRequestApprovedBy,
                    PurchaseRequestConfirmedDate,
                    PurchaseRequestConfirmedBy,
                    PurchaseRequestPaidDate,
                    PurchaseRequestPaidBy,
                    PurchaseRequestDirectIsActive,
                    PurchaseRequestCreated,
                    PurchaseRequestLastUpdated,
                    PurchaseRequestDeleted,
                    PurchaseRequestCreatedUserID,
                    PurchaseRequestLastUpdatedUserID,
                    PurchaseRequestDeletedUserID
                    ) VALUES ('{$pd}', '{$dateNow}', '{$payload['PRDateUse']}', '{$payload['PRBranch']}', '{$payload['PRDescription']}', NULL, 0, 0, 0, 'Draft', NULL, NULL, NULL, NULL, NULL, NULL, 'Y', NOW(), NULL, NULL, {$userId}, NULL, NULL)";
            $exec = $this->db->query($sql, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("purchase request insert error", $this->db);
                exit;
            }

            $this->db->trans_commit();

            $newInsert = "SELECT * FROM purchase_request_direct WHERE PurchaseRequestDirectNumber = '{$pd}' AND PurchaseRequestDirectIsActive = 'Y'";
            $records = $this->db->query($newInsert, [])->result_array();

            $result = array("total" => 1, "records" => $records);
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function updateRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $sql = "UPDATE purchase_request_direct SET
                    PurchaseRequestDirectDateUse = '{$payload['PRDateUse']}',
                    PurchaseRequestDirectM_BranchCode = '{$payload['PRBranch']}',
                    PurchaseRequestDirectDescription = '{$payload['PRDescription']}',
                    PurchaseRequestLastUpdated = NOW(),
                    PurchaseRequestLastUpdatedUserID = {$userId}
                    WHERE PurchaseRequestDirectID = {$payload['PRID']}";
            $exec = $this->db->query($sql, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("purchase request update error", $this->db);
                exit;
            }

            $this->db->trans_commit();

            $newUpdate = "SELECT * FROM purchase_request_direct WHERE PurchaseRequestDirectID = {$payload['PRID']} AND PurchaseRequestDirectIsActive = 'Y'";
            $records = $this->db->query($newUpdate, [])->result_array();

            $result = array("total" => 1, "records" => $records);
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function deleteRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $sql = "UPDATE purchase_request_direct SET
                    PurchaseRequestDeleted = NOW(),
                    PurchaseRequestDeletedUserID = {$userId},
                    PurchaseRequestDirectIsActive = 'N'
                    WHERE PurchaseRequestDirectID = {$payload['PRID']}";
            $exec = $this->db->query($sql, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("purchase request delete error", $this->db);
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

    function saveDetail()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];
            $PRDTotalPrice = intval($payload['PRDAmountRequest']) * intval($payload['PRDEstimationPrice']);

            $query = "SELECT COUNT(*) as exist 
                      FROM purchase_request_direct_detail 
                      WHERE PurchaseRequestDirectDetailIsActive = 'Y' 
                      AND PurchaseRequestDirectDescription = '{$payload['PRDDescriptionDetail']}' 
                      AND PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload['PRID']}";
            $exist = $this->db->query($query, []);
            if ($exist) {
                $row = $exist->row()->exist;
            } else {
                $this->sys_error_db("exist error", $this->db);
                exit;
            }

            if ($row == 0) {
                $sql = "INSERT INTO purchase_request_direct_detail(
                        PurchaseRequestDirectDetailPurchaseRequestDirectID,
                        PurchaseRequestDirectDescription,
                        PurchaseRequestDirectDetailAmountRequest,
                        PurchaseRequestDirectDetailAmount,
                        PurchaseRequestDirectDetailEstimationPrice,
                        PurchaseRequestDirectDetailTotalEstimationPrice,
                        PurchaseRequestDirectDetailTotalRealitationPrice,
                        PurchaseRequestDirectDetailStatus,
                        PurchaseRequestDirectDetailIsActive,
                        PurchaseRequestDirectDetailCreated,
                        PurchaseRequestDirectDetailLastUpdated,
                        PurchaseRequestDirectDetailDeleted,
                        PurchaseRequestDirectDetailCreatedUserID,
                        PurchaseRequestDirectDetailLastUpdatedUserID,
                        PurchaseRequestDirectDetailDeletedUserID
                        ) VALUES ({$payload['PRID']}, '{$payload['PRDDescriptionDetail']}', {$payload['PRDAmountRequest']}, NULL, {$payload['PRDEstimationPrice']}, {$PRDTotalPrice}, NULL, 'Pending', 'Y', NOW(), NULL, NULL, {$userId}, NULL, NULL)";
                $exec = $this->db->query($sql, []);

                if (!$exec) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("request insert error", $this->db);
                    exit;
                } else {
                    $sqlTotalPrice = "SELECT SUM(PurchaseRequestDirectDetailTotalEstimationPrice) AS Total
                                      FROM purchase_request_direct_detail
                                      WHERE PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload['PRID']}
                                      AND PurchaseRequestDirectDetailIsActive = 'Y'";
                    $exec = $this->db->query($sqlTotalPrice, []);
                    $total = 0;

                    if (!$exec) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("order purchase request error", $this->db);
                        exit;
                    } else {
                        $total = $exec->result_array()[0]["Total"];
                    }

                    $sql = "UPDATE purchase_request_direct SET
                    PurchaseRequestDirectTotalEstimation = {$total},
                    PurchaseRequestLastUpdated = NOW(),
                    PurchaseRequestLastUpdatedUserID = {$userId}
                    WHERE PurchaseRequestDirectID = {$payload['PRID']}
                    AND PurchaseRequestDirectIsActive = 'Y'";
                    $exec = $this->db->query($sql, []);
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

    function updateDetail()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];
            $PRDTotalPrice = intval($payload["PRDAmountRequest"]) * intval($payload["PRDEstimationPrice"]);


            $sql = "UPDATE purchase_request_direct_detail SET
                    PurchaseRequestDirectDescription = '{$payload["PRDDescriptionDetail"]}',
                    PurchaseRequestDirectDetailAmountRequest = {$payload["PRDAmountRequest"]},
                    PurchaseRequestDirectDetailEstimationPrice = {$payload["PRDEstimationPrice"]},
                    PurchaseRequestDirectDetailTotalEstimationPrice = {$PRDTotalPrice},
                    PurchaseRequestDirectDetailLastUpdated = NOW(),
                    PurchaseRequestDirectDetailLastUpdatedUserID = {$userId}
                    WHERE PurchaseRequestDirectDetailID = {$payload["PRDID"]}";
            $exec = $this->db->query($sql, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("request update error", $this->db);
                exit;
            } else {
                $sqlTotalPrice = "SELECT SUM(PurchaseRequestDirectDetailTotalEstimationPrice) AS Total
                                  FROM purchase_request_direct_detail
                                  WHERE PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload["PRID"]}
                                  AND PurchaseRequestDirectDetailIsActive = 'Y'";
                $exec = $this->db->query($sqlTotalPrice, []);
                $total = 0;

                if (!$exec) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("order purchase request error", $this->db);
                    exit;
                } else {
                    $total = $exec->result_array()[0]["Total"];
                }

                $sql = "UPDATE purchase_request_direct SET
                        PurchaseRequestDirectTotalEstimation = {$total},
                        PurchaseRequestLastUpdated = NOW(),
                        PurchaseRequestLastUpdatedUserID = {$userId}
                        WHERE PurchaseRequestDirectID = {$payload["PRID"]}
                        AND PurchaseRequestDirectIsActive = 'Y'";
                $exec = $this->db->query($sql, []);
            }

            $this->db->trans_commit();

            $result = array("total" => 1, "records" => array("xId" => $payload["PRDID"]));
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function deleteDetail()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $sql = "UPDATE purchase_request_direct_detail SET
                    PurchaseRequestDirectDetailDeleted = NOW(),
                    PurchaseRequestDirectDetailDeletedUserID = {$userId},
                    PurchaseRequestDirectDetailIsActive = 'N'
                    WHERE PurchaseRequestDirectDetailID = {$payload["PRDID"]}";
            $exec = $this->db->query($sql, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("request delete error", $this->db);
                exit;
            } else {
                $sqlTotalPrice = "SELECT SUM(PurchaseRequestDirectDetailTotalEstimationPrice) AS Total
                                  FROM purchase_request_direct_detail
                                  WHERE PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload["PRID"]}
                                  AND PurchaseRequestDirectDetailIsActive = 'Y'";
                $exec = $this->db->query($sqlTotalPrice, []);
                $total = 0;

                if (!$exec) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("order purchase request error", $this->db);
                    exit;
                } else {
                    $total = $exec->result_array()[0]["Total"] ?? 0;
                }

                $sql = "UPDATE purchase_request_direct SET
                        PurchaseRequestDirectTotalEstimation = {$total},
                        PurchaseRequestLastUpdated = NOW(),
                        PurchaseRequestLastUpdatedUserID = {$userId}
                        WHERE PurchaseRequestDirectID = {$payload["PRID"]}
                        AND PurchaseRequestDirectIsActive = 'Y'";
                $exec = $this->db->query($sql, []);
            }

            $this->db->trans_commit();
            $result = array("total" => 1, "records" => array("xId" => 0));
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function orderRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $this->db->trans_begin();

            $payload = $this->sys_input;
            $userId = $this->sys_user["M_UserID"];

            $sqlDetail = "UPDATE purchase_request_direct_detail SET
                          PurchaseRequestDirectDetailLastUpdated = NOW(),
                          PurchaseRequestDirectDetailLastUpdatedUserID = {$userId}
                          WHERE PurchaseRequestDirectDetailPurchaseRequestDirectID = {$payload["PRID"]}
                          AND PurchaseRequestDirectDetailIsActive = 'Y'
                          AND PurchaseRequestDirectDetailStatus = 'Pending'";
            $exec = $this->db->query($sqlDetail, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("order purchase request error", $this->db);
                exit;
            }

            $sql = "UPDATE purchase_request_direct SET
                PurchaseRequestDirectStatus = 'Pending',
                PurchaseRequestLastUpdated = NOW(),
                PurchaseRequestLastUpdatedUserID = {$userId}
                WHERE PurchaseRequestDirectID = {$payload["PRID"]}
                AND PurchaseRequestDirectIsActive = 'Y'";
            $exec = $this->db->query($sql, []);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("order purchase request error", $this->db);
                exit;
            }

            $this->db->trans_commit();

            $sql = "SELECT *, 
                    ROW_NUMBER() OVER(ORDER BY PurchaseRequestDirectNumber) RowNumber 
                    FROM purchase_request_direct 
                    WHERE PurchaseRequestDirectIsActive = 'Y' 
                    AND PurchaseRequestCreatedUserID = {$userId}
                    AND PurchaseRequestDirectID = {$payload["PRID"]}";
            $exec = $this->db->query($sql, []);

            $row = [];
            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("order purchase request error", $this->db);
                exit;
            } else {
                $row = $exec->result_array();
            }
            $result = array("total" => 1, "records" => $row);
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
