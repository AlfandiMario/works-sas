<?php

class TransferRequest extends MY_Controller
{
    var $db;

    public function index()
    {
        echo "Goods Transfer Request/Requester API";
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function getBranches()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $prm = $this->sys_input;
            $regID = $prm["S_RegionalID"];

            $sql = "SELECT M_BranchID, M_BranchCode, M_BranchName
                    FROM m_branch
                    WHERE M_BranchIsActive = 'Y'
                    AND M_BranchS_RegionalID = ?";
            $qry = $this->db->query($sql, [$regID]);
            if (!$qry) {
                $this->sys_error_db("Failed to get Branches from RegionalID: {$regID}", $this->db);
                exit;
            }
            $rst = $qry->result_array();

            $this->sys_ok(["records" => $rst]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function getStatus()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }

            $rst = [
                ["value" => 0, "title" => "All"],
                ["value" => 1, "title" => "Draft"],
                ["value" => 2, "title" => "Pending"],
                ["value" => 3, "title" => "Approved"],
                ["value" => 4, "title" => "Rejected"],
                ["value" => 5, "title" => "Paid"],
                ["value" => 6, "title" => "Completed"]
            ];

            $this->sys_ok(["records" => $rst]);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
            exit;
        }
    }

    public function search()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $payload = $this->sys_input;

            $startDate = $payload["startDate"];
            $endDate = $payload["endDate"];
            $branchCode = $payload["M_BranchCode"];
            $status = $payload["StatusName"];
            $search = "";

            if (isset($payload['search'])) {
                $search = trim($payload["search"]);
                if ($search != "") {
                    $search = '%' . $payload['search'] . '%';
                } else {
                    $search = '%%';
                }
            }

            $filterBranch = "";
            $filterStatus = "";
            if (isset($branchCode) && $branchCode != "") {
                $filterBranch .= " AND M_BranchCode = '{$branchCode}'";
            }
            if (isset($status) && $status != "") {
                if (strtolower($status) == "all") {
                    $filterStatus .= "";
                } else {
                    $filterStatus .= " AND T_GoodsTransferStatus = '{$status}'";
                }
            }

            $sql = "SELECT
                    t_goods_transfer.*,
                    S_RegionalID,
                    S_RegionalName,
                    M_BranchID,
                    M_BranchCode,
                    M_BranchName
                    FROM t_goods_transfer
                        JOIN s_regional ON T_GoodsTransferFromS_RegionalID = S_RegionalID AND S_RegionalIsActive = 'Y'
                        LEFT JOIN m_branch ON T_GoodsTransferToM_BranchCode = M_BranchCode AND  M_BranchIsActive = 'Y'
                    WHERE T_GoodsTransferIsActive = 'Y'
                    AND (T_GoodsTransferDate BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59')
                    AND (T_GoodsTransferNum LIKE '{$search}')
                    $filterBranch $filterStatus
                    ORDER BY T_GoodsTransferNum DESC";

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
                $this->sys_error_db("Error count total SQL Result", $this->db);;
                exit;
            }

            $sql_select = $sql . " LIMIT $number_limit OFFSET $number_offset";
            $qry = $this->db->query($sql_select);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->sys_error_db("Failed search Transfer Request List", $this->db);
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

    public function createTfRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }
            $userID = $this->sys_user["M_UserID"];
            $data = $this->sys_input;

            // Check if mandatory fields in $data are empty. And return field that are empty if any
            $mandatoryFields = ["S_RegionalID", "M_BranchCode", "GoodsTfNotes", "GoodsTfDate", "GoodsTfStatus"];
            $emptyFields = [];
            foreach ($mandatoryFields as $field) {
                if (empty($data[$field])) {
                    $emptyFields[] = $field;
                }
            }

            if (!empty($emptyFields)) {
                $this->sys_error("Mandatory fields are empty: " . implode(", ", $emptyFields));
                exit;
            }


            $this->db->trans_begin($data["isDebug"] == 'true');

            // Generate Goods Transfer Number
            $sql = "SELECT `fn_numbering`('TR') AS GoodsTfNum";
            $exec = $this->db->query($sql, []);
            $goodsTfNum = $exec->row()->GoodsTfNum;

            // Make sure branchCode is in a region
            $sql = "SELECT M_BranchCode, M_BranchName, S_RegionalName FROM m_branch 
                JOIN s_regional ON M_BranchS_RegionalID = S_RegionalID
            WHERE M_BranchCode = ? AND M_BranchS_RegionalID = ?";
            $exec = $this->db->query($sql, [$data["M_BranchCode"], $data["S_RegionalID"]]);
            if ($exec->num_rows() == 0 || $exec->row() == null) {
                $this->sys_error("Tidak bisa transfer ke cabang di luar regional");
                exit;
            }

            $sql = "INSERT INTO t_goods_transfer (
                        T_GoodsTransferFromS_RegionalID,
                        T_GoodsTransferToM_BranchCode,
                        T_GoodsTransferNum,
                        T_GoodsTransferNotes,
                        T_GoodsTransferDate,
                        T_GoodsTransferStatus,
                        T_GoodsTransferIsActive,
                        T_GoodsTransferCreatedAt,
                        T_GoodsTransferM_UserID
                    ) VALUES (?, ?, ?, ?, ?, ?, 'Y', NOW(), ?)";

            $exec = $this->db->query($sql, [
                $data["S_RegionalID"],
                $data["M_BranchCode"],
                $goodsTfNum,
                $data["GoodsTfNotes"],
                $data["GoodsTfDate"],
                $data["GoodsTfStatus"],
                $userID
            ]);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal insert Transfer Request (No. TF: {$goodsTfNum}). Error:", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $msg = "Berhasil membuat Transfer Request (No. TF: {$goodsTfNum}) pada ID : " . $this->db->insert_id();

            $newInsert = "SELECT * FROM t_goods_transfer WHERE T_GoodsTransferNum = '{$goodsTfNum}' AND T_GoodsTransferIsActive = 'Y'";
            $record = $this->db->query($newInsert, [])->result_array();

            $result = array("total" => 1, "msg" => $msg, "records" => $record);
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function updateTfRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }
            $userID = $this->sys_user["M_UserID"];
            $data = $this->sys_input;
            $this->db->trans_begin();

            $sql = "UPDATE t_goods_transfer SET 
                    T_GoodsTransferFromS_RegionalID = ?,
                    T_GoodsTransferToM_BranchCode = ?,
                    T_GoodsTransferNum = ?,
                    T_GoodsTransferNotes = ?,
                    T_GoodsTransferDate = ?,
                    T_GoodsTransferStatus = ?,
                    T_GoodsTransferLastUpdated = NOW(),
                    T_GoodsTransferM_UserID = ?
                WHERE T_GoodsTransferID = ?
                AND T_GoodsTransferIsActive = 'Y'
                ";

            $exec = $this->db->query($sql, [
                $data["S_RegionalID"],
                $data["M_BranchCode"],
                $data["GoodsTfNum"],
                $data["GoodsTfNotes"],
                $data["GoodsTfDate"],
                $data["GoodsTfStatus"],
                $userID,
                $data["GoodsTfID"]
            ]);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal menghapus Transfer Request (ID: {$data['GoodsTfID']}). Error:", $this->db);
                exit;
            }
            $this->db->trans_commit();

            $this->sys_ok("Berhasil mengupdate Transfer Request (ID: {$data['GoodsTfID']})");
            exit;
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function deleteTfRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }
            $userID = $this->sys_user["M_UserID"];
            $goodsTfID = $this->sys_input["GoodsTfID"];

            $this->db->trans_begin();

            $sql = "UPDATE t_goods_transfer SET 
                    T_GoodsTransferIsActive = 'N',
                    T_GoodsTransferDeleted = NOW(),
                    T_GoodsTransferM_UserID = ?
                WHERE T_GoodsTransferID = ?
                AND T_GoodsTransferIsActive = 'Y'";
            $qry = $this->db->query($sql, [$userID, $goodsTfID]);
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal menghapus Transfer Request (ID: {$goodsTfID}). Error:", $this->db);
                exit;
            }
            $this->db->trans_commit();

            $this->sys_ok("Berhasil menghapus Transfer Request (ID: {$goodsTfID})");
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function createDetailRequest()
    {
        try {
            if (!$this->isLogin) {
                $this->sys_error("Invalid Token");
            }
            $userID = $this->sys_user["M_UserID"];
            $data = $this->sys_input;

            $this->db->trans_begin();

            $sql = "INSERT INTO t_goods_transfer_detail (
                    T_GoodsTransferDetailIDT_GoodsTransferID,
                    T_GoodsTransferDetailT_PurchaseRequestFlagID,
                    T_GoodsTransferDetailQty,
                    T_GoodsTransferDetailQtyRest,
                    T_GoodsTransferDetailIsActive,
                    T_GoodsTransferDetailCreatedAt,
                    T_GoodsTransferDetailM_UserID
                ) VALUES (?, ?, ?, ?, 'Y', NOW(), ?)";

            $exec = $this->db->query($sql, [
                $data["GoodsTransferID"],
                $data["PurchaseRequestFlagID"],
                $data["Qty"],
                $data["QtyRest"],
                $userID
            ]);

            if (!$exec) {
                $this->db->trans_rollback();
                $this->sys_error_db("Gagal insert Transfer Request Detail (ID: {$data['GoodsTransferID']}). Error:", $this->db);
                exit;
            }

            $this->db->trans_commit();
            $insertedID = $this->db->insert_id();

            $this->sys_ok("Berhasil membuat Transfer Request Detail (ID: {$insertedID}) pada ID : " . $insertedID);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    public function getDetails()
    {
        /* 
            get from req flag join purchasereqdetail 
            join purchasereq untuk filter branch dan regional
        */
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
        }
        $pload = $this->sys_input;
        $branchCode = $pload['M_BranchCode'];
        $regionalID = $pload['S_RegionalID'];

        $sql = "SELECT 
                    PurchaseRequestDetailNat_ItemID as NatItemID,
                    ItemCode as NatItemCode,
                    Description as NatItemDesc,
                    SUM(PurchaseRequestFlagQty) as TotalFlagQty,
                    SUM(PurchaseRequestDetailQty) as TotalReqDetailQty,
                    PurchaseRequestDetailPrice, 
                    PurchaseRequestFlagIsClosed,
                    PurchaseRequestNumber
                FROM 
                    purchase_request_flag
                    JOIN purchase_request_detail ON PurchaseRequestFlagPurchaseRequestDetailID = PurchaseRequestDetailID 
                    JOIN nat_item ON PurchaseRequestDetailNat_ItemID = Nat_ItemID
                    JOIN purchase_request ON PurchaseRequestID = PurchaseRequestDetailPurchaseRequestID
                WHERE 
                    PurchaseRequestFlagIsActive = 'Y' 
                    AND PurchaseRequestDetailIsActive = 'Y'
                    AND PurchaseRequestIsActive = 'Y'
                    AND nat_item.IsActive = 'T'
                    AND PurchaseRequestS_RegionalID = ?
                    AND PurchaseRequestM_BranchCode = ?
                    AND PurchaseRequestStatus = 'Approved'
                    AND FIND_IN_SET('TF', PurchaseRequestFlagStatus) > 0
                GROUP BY 
                    PurchaseRequestDetailNat_ItemID,
                    ItemCode ";
        $exec = $this->db->query($sql, [$regionalID, $branchCode]);
        if (!$exec) {
            $this->sys_error_db("Failed to get Details from RegID {$regionalID} and BranchCode {$branchCode}", $this->db);
            exit;
        }
        $rst = $exec->result_array();
        $total = count($rst);
        $this->sys_ok(["total" => $total, "records" => $rst]);
    }
    /* 
        ===
        UNUSED YET
        ===
    */

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

    function search_old()
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
