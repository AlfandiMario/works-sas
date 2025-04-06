<?php
class Jurnalumum extends MY_Controller
{
    var $db;
    public function index()
    {
        echo "JURNAL API";
    }
    public function __construct()
    {
        parent::__construct();
    }

    function search()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

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

            $number_offset = 0;
            $number_limit = 20;

            if ($prm["current_page"] > 0) {
                $number_offset = ($prm["current_page"] - 1) * $number_limit;
            }

            $startdate = $prm["startdate"];
            $enddate = $prm["enddate"];

            $branchid = $prm["branchid"];
            $regionalid = $prm["regionalid"];

            $filter_regional = "";
            $filter_cabang = "";
            $join_regional = "";
            $join_cabang = "";
            if (intval($branchid) === 0) {
                $filter_regional = " AND S_RegionalID = {$regionalid}";
                $join_regional = " LEFT JOIN m_branch ON jurnalM_BranchCode = M_BranchCode AND M_BranchIsActive = 'Y' ";
            } else {
                $filter_cabang = " AND S_RegionalID = {$regionalid} AND M_BranchID = {$branchid}";
                $join_cabang = " JOIN m_branch ON jurnalM_BranchCode = M_BranchCode AND M_BranchIsActive = 'Y' ";
            }
            // print_r($branchid);
            // exit;

            $sql_filter = "SELECT count(*) as total
                    FROM jurnal
                    JOIN m_branch_company ON jurnalM_BranchCompanyID = M_BranchCompanyID AND M_BranchCompanyIsActive = 'Y'
                    JOIN s_regional ON JurnalS_RegionalID = S_RegionalID AND S_RegionalIsActive = 'Y'
                    $join_cabang
                    JOIN periode ON jurnalperiodeID = periodeID AND periodeIsActive = 'Y'
                    JOIN jurnal_type ON jurnalJurnalTypeID = JurnalTypeID AND JurnalTypeIsActive = 'Y'
                    $join_regional
                    LEFT JOIN jurnal_addon ON jurnalID = jurnalAddOnJurnalID AND jurnalAddOnIsActive = 'Y'
                    WHERE jurnalIsActive = 'Y'
                    AND DATE(jurnalDate) BETWEEN ? AND ?
                    AND (jurnalNo LIKE ? OR jurnalTitle LIKE ?)
                    $filter_regional  $filter_cabang
                    GROUP BY jurnalID
                    ORDER BY jurnalID ASC";
            $qry_filter = $this->db->query($sql_filter, array(
                $startdate,
                $enddate,
                $search,
                $search
            ));
            $totalCount = 0;
            $totalPage = 0;

            if ($qry_filter) {
                $totalCount = $qry_filter->result_array()[0]["total"];
                $totalPage = ceil($totalCount / $number_limit);
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select jurnal count error", $this->db);;
                exit;
            }

            $sql = "SELECT 
                    jurnalID as id,
                    jurnalM_BranchCompanyID as branchcompanyid,
                    JurnalS_RegionalID as regionalid,
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
                    jurnalAddOnCode,
                    jurnalAddOnValue,
                    '' as detail
                    FROM jurnal
                    JOIN m_branch_company ON jurnalM_BranchCompanyID = M_BranchCompanyID AND M_BranchCompanyIsActive = 'Y'
                    JOIN s_regional ON JurnalS_RegionalID = S_RegionalID AND S_RegionalIsActive = 'Y'
                    $join_cabang
                    JOIN periode ON jurnalperiodeID = periodeID AND periodeIsActive = 'Y'
                    JOIN jurnal_type ON jurnalJurnalTypeID = JurnalTypeID AND JurnalTypeIsActive = 'Y'
                    $join_regional
                    LEFT JOIN jurnal_addon ON jurnalID = jurnalAddOnJurnalID AND jurnalAddOnIsActive = 'Y'
                    WHERE jurnalIsActive = 'Y'
                    AND DATE(jurnalDate) BETWEEN ? AND ?
                    AND (jurnalNo LIKE ? OR jurnalTitle LIKE ?)
                    $filter_regional  $filter_cabang
                    GROUP BY jurnalID
                    ORDER BY jurnalID ASC
                    LIMIT ? OFFSET ?";
            $qry = $this->db->query($sql, array(
                $startdate,
                $enddate,
                $search,
                $search,
                $number_limit,
                $number_offset,
            ));
            // echo $this->db->last_query();
            // exit;
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select jurnal error", $this->db);
                exit;
            }

            foreach ($rows as $k => $v) {
                $sql_detail = "SELECT 
                                jurnalTxID,
                                jurnalTxJurnalID,
                                jurnalTxCoaID as coaid,
                                jurnalTxDescription as description,
                                jurnalTxDebit as debit,
                                jurnalTxCredit as credit,
                                '' as account
                                FROM jurnal_tx
                                WHERE jurnalTxIsActive = 'Y'
                                AND jurnalTxJurnalID = ?";
                $qry_detail = $this->db->query($sql_detail, array($v["id"]));
                if (!$qry_detail) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("select jurnal tx error", $this->db);
                    exit;
                }

                $detail_rows = $qry_detail->result_array();

                // Tambahkan account dari COA untuk setiap baris detail
                foreach ($detail_rows as $dk => $dv) {
                    $sql_coa = "SELECT 
                                coaID,
                                coaAccountNo as account
                               FROM coa
                               WHERE coaIsActive = 'Y'
                               AND coaID = ?";
                    $qry_coa = $this->db->query($sql_coa, array($dv["coaid"]));
                    if ($qry_coa->num_rows() > 0) {
                        $coa_row = $qry_coa->row_array();
                        $detail_rows[$dk]["account"] = $coa_row["account"];
                    } else {
                        $detail_rows[$dk]["account"] = "";
                    }
                }

                $rows[$k]["detail"] = $detail_rows;
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

    function getjurnaltype()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $sql = "SELECT JurnalTypeID,
                    JurnalTypeCode,
                    JurnalTypeName,
                    JurnalTypeAccesRight,
                    JurnalTypeIsActive
                    FROM jurnal_type
                    WHERE JurnalTypeIsActive = 'Y'";
            $qry = $this->db->query($sql);
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select jurnal type error", $this->db);
                exit;
            }
            $defaultju = [];
            foreach ($rows as $value) {
                if ($value["JurnalTypeCode"] == "GENERAL") {
                    $defaultju = $value;
                }
            }

            $result = array(
                "records" => $rows,
                "defaultju" => $defaultju,
                "sql" => $this->db->last_query()
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function getperiode()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

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

            $sql = "SELECT 
                periodeID,
                periodeYear,
                periodeMonth,
                CONCAT(periodeYear, ' - ',periodeMonth) as yearandmonth,
                periodeName,
                periodeStartDate,
                periodeEndDate,
                CONCAT(DATE_FORMAT(periodeStartDate, '%d %M %Y'), ' - ', DATE_FORMAT(periodeEndDate, '%d %M %Y')) as periode
                FROM periode 
                WHERE periodeIsActive = 'Y'
                AND (periodeYear LIKE ? OR periodeName LIKE ?)";
            $qry = $this->db->query($sql, array(
                $search,
                $search
            ));
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select periode error", $this->db);
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

    function searchcoa()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

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

            $number_limit = 10;

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
                    CONCAT(coaAccountNo, ' - ', coaDescription) as accountNoDescription
                    FROM coa
                    WHERE coaIsActive = 'Y'
                    AND coaIsInput = 'N'
                    AND (coaAccountNo LIKE ? OR coaDescription LIKE ? OR coaSubDescription LIKE ?)
                    ORDER BY coaAccountNo ASC
                    LIMIT ?";
            $qry = $this->db->query($sql, array($search, $search, $search, $number_limit));
            if ($qry) {
                $rows = $qry->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select coa error", $this->db);
                exit;
            }

            $result = array(
                "records" => $rows,
                "total_filter" => sizeof($rows)
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function getbranch()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;

            $branchid = $prm["branchid"];
            $sql = "SELECT 
                    M_BranchID,
                    M_BranchCode,
                    M_BranchName
                    FROM m_branch
                    WHERE M_BranchIsActive = 'Y'
                    AND M_BranchID = ?";
            $qry = $this->db->query($sql, array($branchid));
            if ($qry) {
                $row = $qry->row_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select branch error", $this->db);
                exit;
            }

            $result = array(
                "records" => $row,
            );
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function savejurnalumum()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $userid = $this->sys_user['M_UserID'];
            $prm = $this->sys_input;
            $branchid = $prm["branchid"];
            $branchcompanyid = $prm["branchcompanyid"];
            $date = $prm["date"];
            $description = $prm["description"];
            $periodeid = $prm["periodeid"];
            $regionalid = $prm["regionalid"];
            $title = $prm["title"];
            $typeid = $prm["typeid"];
            $detailjurnal = $prm["detailjurnal"];

            $sql_branch = "SELECT 
                    M_BranchID,
                    M_BranchCode,
                    M_BranchName
                    FROM m_branch
                    WHERE M_BranchIsActive = 'Y'
                    AND M_BranchID = ?";
            $qry_branch = $this->db->query($sql_branch, array($branchid));
            if ($qry_branch) {
                $branchcodex = $qry_branch->row()->M_BranchCode;
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select branch error", $this->db);
                exit;
            }

            $sql = "INSERT INTO jurnal(
                    jurnalM_BranchCompanyID,
                    JurnalS_RegionalID,
                    jurnalM_BranchCode,
                    jurnalperiodeID,
                    jurnalNo,
                    jurnalTitle,
                    jurnalDescription,
                    jurnalDate,
                    jurnalJurnalTypeID,
                    jurnalIsActive,
                    jurnalCreated,
                    jurnalM_UserID
                    ) VALUES(?,?,?,?,`fn_numbering`('J'),?,?,?,?,'Y',NOW(),?)";
            $qry = $this->db->query($sql, array(
                $branchcompanyid,
                $regionalid,
                $branchcodex,
                $periodeid,
                $title,
                $description,
                $date,
                $typeid,
                $userid
            ));
            $last_qry = $this->db->last_query();
            if (!$qry) {
                $this->db->trans_rollback();
                $error = array(
                    "message" => $this->db->error()["message"],
                    "sql" => $last_qry
                );
                $this->sys_error_db($error, $this->db);
                exit;
            }

            $last_id = $this->db->insert_id();

            foreach ($detailjurnal as $key => $value) {
                $sql_detail = "INSERT INTO jurnal_tx(
                                jurnalTxJurnalID,
                                jurnalTxCoaID,
                                jurnalTxDescription,
                                jurnalTxDebit,
                                jurnalTxCredit,
                                jurnalTxIsActive,
                                jurnalTxCreated,
                                jurnalTxM_UserID) VALUES(?,?,?,?,?,'Y',NOW(),?)";
                $qry_detail = $this->db->query($sql_detail, array(
                    $last_id,
                    $value["coaid"],
                    $value["description"],
                    $value["debit"],
                    $value["credit"],
                    $userid
                ));
                $last_qry = $this->db->last_query();
                if (!$qry_detail) {
                    $this->db->trans_rollback();
                    $error = array(
                        "message" => $this->db->error()["message"],
                        "sql" => $last_qry
                    );
                    $this->sys_error_db($error, $this->db);
                    exit;
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

    function editjurnalumum()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $userid = $this->sys_user['M_UserID'];
            $prm = $this->sys_input;
            $branchid = $prm["branchid"];
            $branchcompanyid = $prm["branchcompanyid"];
            $date = $prm["date"];
            $description = $prm["description"];
            $periodeid = $prm["periodeid"];
            $regionalid = $prm["regionalid"];
            $title = $prm["title"];
            $typeid = $prm["typeid"];
            $detailjurnal = $prm["detailjurnal"];
            $id = $prm["id"];

            $sql_branch = "SELECT 
                    M_BranchID,
                    M_BranchCode,
                    M_BranchName
                    FROM m_branch
                    WHERE M_BranchIsActive = 'Y'
                    AND M_BranchID = ?";
            $qry_branch = $this->db->query($sql_branch, array($branchid));
            if ($qry_branch) {
                $branchcodex = $qry_branch->row()->M_BranchCode;
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select branch error", $this->db);
                exit;
            }

            $sql = "UPDATE jurnal SET
                    jurnalM_BranchCompanyID = ?,
                    JurnalS_RegionalID = ?,
                    jurnalM_BranchCode = ?,
                    jurnalperiodeID = ?,
                    jurnalTitle = ?,
                    jurnalDescription = ?,
                    jurnalDate = ?,
                    jurnalJurnalTypeID = ?,
                    jurnalLastUpdated = NOW(),
                    jurnalM_UserID = ?
                    WHERE jurnalID = ?";
            $qry = $this->db->query($sql, array(
                $branchcompanyid,
                $regionalid,
                $branchcodex,
                $periodeid,
                $title,
                $description,
                $date,
                $typeid,
                $userid,
                $id
            ));

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("edit jurnal", $this->db);
                exit;
            }

            // Ambil data lama dari database
            $sql_select_jurnaltx = "SELECT * FROM jurnal_tx WHERE jurnalTxJurnalID = ? AND jurnalTxIsActive = 'Y'";
            $qry_select_jurnattx = $this->db->query($sql_select_jurnaltx, array($id));

            if ($qry_select_jurnattx) {
                $rows_jurnaltx = $qry_select_jurnattx->result_array();
            } else {
                $this->db->trans_rollback();
                $this->sys_error_db("select jurnal tx error", $this->db);
                exit;
            }

            // print_r($rows_jurnaltx);

            // Konversi existingData menjadi array dengan key jurnalTxID
            $existingDataAssoc = [];
            foreach ($rows_jurnaltx as $row) {
                $existingDataAssoc[$row['jurnalTxID']] = $row;
            }

            // print_r($existingDataAssoc);
            $toInsert = [];
            $toUpdate = [];
            $existingIDs = array_keys($existingDataAssoc);
            $newIDs = [];

            foreach ($detailjurnal as $item) {
                $newIDs[] = $item['jurnalTxID'] ?? null;

                // print_r($newIDs);
                // exit;
                if (!isset($existingDataAssoc[$item['jurnalTxID']])) {
                    // Data baru
                    $toInsert[] = $item;
                }
            }

            $toDelete = array_diff($existingIDs, $newIDs);

            // print_r($toInsert);
            // print_r($toDelete);
            // exit;

            // hapus data yang sudah ada
            foreach ($toDelete as  $value) {
                $sql_del = "UPDATE jurnal_tx SET
                            jurnalTxIsActive = 'N',
                            jurnalTxLastUpdated = NOW(),
                            jurnalTxM_UserID = ?
                            WHERE jurnalTxID = ?";
                $qry_del = $this->db->query($sql_del, array(
                    $userid,
                    $value
                ));
                if (!$qry_del) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("delete jurnal tx error", $this->db);
                    exit;
                }
                // $lastqry = $this->db->last_query();
                // echo $lastqry;
            }

            // tambah data baru
            foreach ($toInsert as $val) {
                $sql_detail = "INSERT INTO jurnal_tx(
                                jurnalTxJurnalID,
                                jurnalTxCoaID,
                                jurnalTxDescription,
                                jurnalTxDebit,
                                jurnalTxCredit,
                                jurnalTxIsActive,
                                jurnalTxCreated,
                                jurnalTxM_UserID) VALUES(?,?,?,?,?,'Y',NOW(),?)";
                $qry_detail = $this->db->query($sql_detail, array(
                    $id,
                    $val["coaid"],
                    $val["description"],
                    $val["debit"],
                    $val["credit"],
                    $userid
                ));
                $last_qry = $this->db->last_query();
                if (!$qry_detail) {
                    $this->db->trans_rollback();
                    $error = array(
                        "message" => $this->db->error()["message"],
                        "sql" => $last_qry
                    );
                    $this->sys_error_db($error, $this->db);
                    exit;
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

    function deletejurnalumum()
    {
        try {
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $this->db->trans_begin();
            $userid = $this->sys_user['M_UserID'];
            $prm = $this->sys_input;

            $id = $prm["id"];
            $detailjurnal = $prm["detailjurnal"];

            $sql = "UPDATE jurnal SET
                    jurnalIsActive = 'N',
                    jurnalLastUpdated = NOW(),
                    jurnalM_UserID = ?
                    WHERE jurnalID = ?";
            $qry = $this->db->query($sql, array(
                $userid,
                $id
            ));

            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("delete jurnal error", $this->db);
                exit;
            }

            foreach ($detailjurnal as $key => $value) {
                $sql_detail = "UPDATE jurnal_tx SET
                                jurnalTxIsActive = 'N',
                                jurnalTxLastUpdated = NOW(),
                                jurnalTxM_UserID = ?
                                WHERE jurnalTxID = ? AND jurnalTxJurnalID = ?";
                $qry_detail = $this->db->query($sql_detail, array(
                    $userid,
                    $value["jurnalTxID"],
                    $id
                ));
                if (!$qry_detail) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("delete jurnal tx error", $this->db);
                    exit;
                }
            }

            $addonSql = "UPDATE jurnal_addon 
                    SET jurnalAddOnIsActive = 'N', jurnalAddOnLastUpdated = NOW(), 
                    jurnalAddOnLastUpdatedUserID = ? 
                    WHERE jurnalAddOnJurnalID = ?";
            $addonqry = $this->db->query($addonSql, [$userid, $id]);
            if (!$addonqry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Failed to delete jurnal_addon", $this->db);
                exit;
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
