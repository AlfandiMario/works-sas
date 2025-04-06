<?php

class Jurnalgaji extends MY_Controller
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
    function getuser()
    {
        echo json_encode($this->sys_user);
    }
    function saveJurnalGaji()
    {
        $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();

        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $detail = $prm['detail'];
        $jurnalRegional = $prm['jurnalRegional'];
        $sallary = $prm['sallary'];
        $user = $this->sys_user;
        $userid = $user["M_UserID"];




        $kreditTotal = 0;
        foreach ($detail as $i => $value) {
            if ($value['type'] == 'D') {
                $kredit = 0;
                $debet = doubleval($value['debet']);
                foreach ($detail as $j => $dtl) {
                    if ($dtl['type'] == 'K' && $value['branchID'] == $dtl['branchID']) {
                        $kredit = $kredit + doubleval($dtl['kredit']);
                    }
                }
                if ($debet != $kredit) {
                    $this->sys_error("Jumlah debet dan kredit tidak balance di cabang " . $value['branchName'] . "Debet " . strval($debet) . " ,Kredit " . strval($kredit));
                    exit;
                }
            } else if ($value['type'] == 'K') {
                $kreditTotal = $kreditTotal + doubleval($value['kredit']);
            }
            # code...
        }
        if (doubleval($sallary) != $kreditTotal) {
            $this->sys_error("Jumlah debet dan kredit secara total tidak balance" . "Total Gaji Per PT " . strval($sallary) . " ,Kredit " . strval($kreditTotal));
            exit;
        }

        $sql = "INSERT INTO jurnal_gaji_header 
                (JurnalGajiHeaderCreatedUserID,
                JurnalGajiHeaderBranchPercentID
                )
                VALUES(?, ?)";
        $qry = $this->db->query($sql, array($userid, $prm['branchPrecenID']));

        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Error insert jurnal gaji header");
            exit;
        }
        $insertedJurnalGajiHeader = $this->db->insert_id();
        // echo ($insertedJurnalGajiHeader);
        // exit;

        //insert jurnal regional
        $sql = "SELECT `fn_numbering`('GJ') as number";
        $qry = $this->db->query($sql, array());

        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Error get number");
            exit;
        }
        $numberingCounter = $qry->row_array()['number'];
        $currentMonth = date('m'); // Mendapatkan bulan saat ini dalam format angka dua digit
        $currentYear = date('Y'); // Mendapatkan tahun saat ini


        $numbering = 'GJ/' . $currentYear . "/" . $currentMonth . "/" . strval($numberingCounter);

        $sql = "INSERT INTO jurnal (
                        jurnalM_BranchCompanyID,
                        JurnalS_RegionalID,
                        jurnalM_BranchCode,
                        jurnalperiodeID,
                        jurnalNo,
                        jurnalTitle,
                        jurnalDescription,
                        jurnalDate,
                        jurnalJurnalTypeID,
                        jurnalM_UserID)
                        VALUES(?,?,?,?,?,?,?,?,?,?)";
        $qry = $this->db->query($sql, array(
            $user['M_BranchCompanyID'],
            $user['S_RegionalID'],
            '',
            $prm['periodeID'],
            $numbering,
            $prm['title'],
            $prm['description'],
            $prm['date'],
            $prm['jurnalTypeID'],
            $userid
        ));
        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Error insert jurnal");
            exit;
        }
        $insertedJurnalRegID = $this->db->insert_id();
        $sql = "INSERT INTO jurnal_gaji_detail(
                        JurnalGajiDetailJurnalGajiHeaderID,
                        JurnalGajiDetailJurnalID,
                        JurnalGajiDetailCreatedUserID,
                        JurnalGajiDetailType
                        )VALUES ({$insertedJurnalGajiHeader},{$insertedJurnalRegID},{$userid}, 'R')";
        $qry = $this->db->query($sql, array());
        if (!$qry) {
            // print_r($this->db->last_query());
            $this->db->trans_rollback();
            $this->sys_error_db("Error insert jurnal gaji detail");
            exit;
        }

        $sql = "INSERT INTO jurnal_addon(
                        jurnalAddOnJurnalID,
                        jurnalAddOnCode,
                        jurnalAddOnValue,
                        jurnalAddOnCreatedUserID)
                        VALUES({$insertedJurnalRegID},'ACSALREG',{$value['coaNo']},{$userid});";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Error insert jurnal gaji addon regional");
            exit;
        }
        $sql = "INSERT INTO jurnal_addon(
                        jurnalAddOnJurnalID,
                        jurnalAddOnCode,
                        jurnalAddOnValue,
                        jurnalAddOnCreatedUserID)
                        VALUES(?,?,?,?)
                ";
        $qry = $this->db->query($sql, array(
            $insertedJurnalRegID,
            'AMSALREG',
            $prm['sallary'],
            $userid
        ));
        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Error insert jurnal gaji addon amsal regional");
            exit;
        }

        //insert jurnal regional
        foreach ($jurnalRegional as $i => $value) {
            $kredit = 0;
            $debet = doubleval($value['debet']);
            $sql = 'INSERT INTO jurnal_tx(
                        jurnalTxJurnalID,
                        jurnalTxCoaID,
                        jurnalTxDescription,
                        jurnalTxDebit,
                        jurnalTxCredit,
                        jurnalTxM_UserID)
                        VALUES(?,?,?,?,?,?)';
            $qry = $this->db->query($sql, array(
                $insertedJurnalRegID,
                $value['coaID'],
                $value['coaName'],
                $value['debet'],
                $value['kredit'],
                $userid
            ));
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Error insert jurnal tx regional");
                exit;
            }
            $insertedJurnalTxRegID = $this->db->insert_id();
            $sql = "INSERT INTO jurnal_addon(
                        jurnalAddOnJurnalID,
                        jurnalAddOnJurnalTxID,
                        jurnalAddOnCode,
                        jurnalAddOnValue,
                        jurnalAddOnCreatedUserID)
                        VALUES(?,?,?,?,?)
                ";
            $qry = $this->db->query($sql, array(
                $insertedJurnalRegID,
                $insertedJurnalTxRegID,
                'GJREGBR',
                $value['branchID'],
                $userid
            ));
            if (!$qry) {
                $this->db->trans_rollback();
                $this->sys_error_db("Error insert jurnal gaji detail");
                exit;
            }
        }
        //insert jurnal branch
        foreach ($detail as $i => $value) {
            if ($value['type'] == 'D') {
                $kredit = 0;
                $debet = doubleval($value['debet']);
                $sql = "SELECT 
                        M_BranchID as branchID,
                        M_BranchS_RegionalID as branchRegionalID,
                        M_BranchCode as branchCode ,
                        M_BranchName as branchName,
                        M_BranchCompanyDetailM_BranchCompanyID branchCompanyID
                        FROM m_branch
                        JOIN m_branch_companydetail
                        ON M_BranchCode  = M_BranchCompanyDetailM_BranchCode
                        WHERE M_BranchID = ?";
                $qry = $this->db->query($sql, array($value['branchID']));

                if (!$qry) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("Error get branch");
                    exit;
                }
                $dataBranch = $qry->row_array();

                $sql = "SELECT `fn_numbering`('GJ') as number";
                $qry = $this->db->query($sql, array());

                if (!$qry) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("Error get number");
                    exit;
                }
                $numberingCounter = $qry->row_array()['number'];
                $currentMonth = date('m'); // Mendapatkan bulan saat ini dalam format string
                $currentYear = date('Y'); // Mendapatkan tahun saat ini


                $numbering = 'GJ/' . $currentYear . "/" . $currentMonth . "/" . strval($numberingCounter) . "/" . $dataBranch['branchCode'];

                $sql = "INSERT INTO jurnal (
                        jurnalM_BranchCompanyID,
                        JurnalS_RegionalID,
                        jurnalM_BranchCode,
                        jurnalperiodeID,
                        jurnalNo,
                        jurnalTitle,
                        jurnalDescription,
                        jurnalDate,
                        jurnalJurnalTypeID,
                        jurnalM_UserID)
                        VALUES(?,?,?,?,?,?,?,?,?,?)";
                $qry = $this->db->query($sql, array(
                    $user['M_BranchCompanyID'],
                    $user['S_RegionalID'],
                    $dataBranch['branchCode'],
                    $prm['periodeID'],
                    $numbering,
                    $prm['title'],
                    $prm['description'],
                    $prm['date'],
                    $prm['jurnalTypeID'],
                    $userid
                ));
                if (!$qry) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("Error insert jurnal");
                    exit;
                }
                $insertedJurnalID = $this->db->insert_id();

                $sql = 'INSERT INTO jurnal_tx(
                        jurnalTxJurnalID,
                        jurnalTxCoaID,
                        jurnalTxDescription,
                        jurnalTxDebit,
                        jurnalTxCredit,
                        jurnalTxM_UserID)
                        VALUES(?,?,?,?,?,?)';
                $qry = $this->db->query($sql, array(
                    $insertedJurnalID,
                    $value['coaID'],
                    $value['coaName'],
                    $value['debet'],
                    $value['kredit'],
                    $userid
                ));
                if (!$qry) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("Error insert jurnal tx debet");
                    exit;
                }
                $sql = "INSERT INTO jurnal_gaji_detail(
                        JurnalGajiDetailJurnalGajiHeaderID,
                        JurnalGajiDetailJurnalID,
                        JurnalGajiDetailCreatedUserID,
                        JurnalGajiDetailType
                        )VALUES ({$insertedJurnalGajiHeader},{$insertedJurnalID},{$userid}, 'B')";
                $qry = $this->db->query($sql, array());
                if (!$qry) {
                    // print_r($this->db->last_query());
                    $this->db->trans_rollback();
                    $this->sys_error_db("Error insert jurnal gaji detail");
                    exit;
                }

                $sql = "INSERT INTO jurnal_addon(
                        jurnalAddOnJurnalID,
                        jurnalAddOnCode,
                        jurnalAddOnValue,
                        jurnalAddOnCreatedUserID)
                        VALUES(?,?,?,?)
                ";
                $qry = $this->db->query($sql, array(
                    $insertedJurnalID,
                    'ACSALREG',
                    $value['coaNo'],
                    $userid
                ));
                if (!$qry) {
                    // $this->db->trans_rollback();
                    $this->sys_error_db("Error insert jurnal gaji detail");
                    exit;
                }
                $sql = "INSERT INTO jurnal_addon(
                        jurnalAddOnJurnalID,
                        jurnalAddOnCode,
                        jurnalAddOnValue,
                        jurnalAddOnCreatedUserID)
                        VALUES(?,?,?,?)
                ";
                $qry = $this->db->query($sql, array(
                    $insertedJurnalID,
                    'AMSALREG',
                    $prm['sallary'],
                    $userid
                ));
                if (!$qry) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("Error insert jurnal gaji detail");
                    exit;
                }

                foreach ($detail as $j => $dtl) {
                    if ($dtl['type'] == 'K' && $value['branchID'] == $dtl['branchID']) {
                        $sql = 'INSERT INTO jurnal_tx(
                        jurnalTxJurnalID,
                        jurnalTxCoaID,
                        jurnalTxDescription,
                        jurnalTxDebit,
                        jurnalTxCredit,
                        jurnalTxM_UserID)
                        VALUES(?,?,?,?,?,?)';
                        $qry = $this->db->query($sql, array(
                            $insertedJurnalID,
                            $dtl['coaID'],
                            $dtl['coaName'],
                            $dtl['debet'],
                            $dtl['kredit'],
                            $userid
                        ));
                        if (!$qry) {
                            $this->db->trans_rollback();
                            $this->sys_error_db("Error insert jurnal tx kredit");
                            exit;
                        }
                    }
                }
            }
        }
        $this->db->trans_commit();
        $this->sys_ok('OK');
    }
    function getDetail()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $id =  $prm['id'];

        $sql = "SELECT * 
                FROM jurnal_gaji_header
                JOIN jurnal_gaji_detail
                ON JurnalGajiHeaderID = JurnalGajiDetailJurnalGajiHeaderID
                AND JurnalGajiDetailIsActive = 'Y'
                AND 
                JurnalGajiHeaderID IN 
                (SELECT JurnalGajiDetailJurnalGajiHeaderID FROM jurnal_gaji_detail WHERE JurnalGajiDetailJurnalID = ?)
                AND JurnalGajiHeaderIsActive = 'Y'
                JOIN jurnal 
                ON JurnalGajiDetailJurnalID = jurnalID
                AND jurnalIsActive = 'Y'";
        $qry = $this->db->query($sql, array($id));
        if (!$qry) {
            $this->db->trans_rollback();
            $this->sys_error_db("Error insert jurnal tx kredit");
            exit;
        }
        $jurnalHeader = $qry->result_array();
        $result = array();
        $type = '';
        $dataHeaderBranch = array();
        foreach ($jurnalHeader as $key => $value) {
            if ($value['JurnalGajiDetailJurnalID'] == $id) {
                $type = $value['JurnalGajiDetailType'];
                $dataHeaderBranch = $value;
            }
        }
        $sql = "SELECT * 
                FROM branch_percent 
                WHERE BranchPercentID = ?
                ";
        $qry = $this->db->query($sql, [$dataHeaderBranch['JurnalGajiHeaderBranchPercentID']]);
        if (!$qry) {
            $this->sys_error_db("Error get branch_percent", $this->db);
            exit;
        }
        $branchPercent = $qry->row_array();
        //     "companyID": "1",
        // "regionalID": "1",
        // "jurnalTypeID": "1",
        // "date": "2025-01-15",
        // "periodeID": "4",
        // "title": "JURNAL GAJI REGIONAL 2025 COBA",
        // "coaID": "881",
        // "coaName": "BIAYA GAJI TENAGA TEHNIS - REGIONAL",
        // "coaNo": "51102",
        // "coaKasID": "6",
        // "coaKasName": "BANK",
        // "coaKasNo": "11102",
        // "sallary": "100000000",
        // "description": "",
        // "branchPrecenID": "1",
        $sql = "SELECT * FROM jurnal_type WHERE JurnalTypeIsActive = 'Y' AND JurnalTypeID = ?
                ";
        $qry = $this->db->query($sql, [$jurnalHeader[0]['jurnalJurnalTypeID']]);
        if (!$qry) {
            $this->sys_error_db("Error get jurnal Type");
            exit;
        }
        $jurnalType = $qry->row_array();
        $date = $jurnalHeader[0]['jurnalDate'];
        $title = $jurnalHeader[0]['jurnalTitle'];
        $description = $jurnalHeader[0]['jurnalDescription'];
        $sallary = 0;
        $coaBank = (object) [];
        $coa = (object) [];
        $jurnalRegional = [];
        $jurnalBranch = [];

        $sql = "SELECT 
                CONCAT(periodeYear, ' - ', periodeName) as display,
                periode.* 
                FROM periode
                WHERE periodeIsActive = 'Y'
                AND periodeID = ?
                LIMIT 70
                ";
        $qry = $this->db->query($sql, [$jurnalHeader[0]['jurnalperiodeID']]);
        if (!$qry) {
            $this->sys_error_db("Error get coa", $this->db);
            exit;
        }
        $jurnalPeriode = $qry->row_array();
        if ($type == 'R') {
            $dataJurnalRegional = array();
            $arrJurnalBranchID = array();
            foreach ($jurnalHeader as $key => $value) {
                if ($value['JurnalGajiDetailType'] == 'R') {
                    $dataJurnalRegional = $value;
                }
                if ($value['JurnalGajiDetailType'] == 'B') {
                    $arrJurnalBranchID[] = $value['JurnalGajiDetailJurnalID'];
                }
            }

            $sql = "SELECT 
                    jurnalTxID as id,
                    IFNULL(M_BranchID, '') as branchID,
                    IFNULL(M_BranchName, '') as branchName,
                    coaDescription as description,
                    jurnalTxDebit as debet,
                    jurnalTxCredit as kredit,
                    0 as precentage,
                    CASE 
                        WHEN jurnalTxDebit > 0 THEN 'D'
                        WHEN jurnalTxCredit > 0 THEN 'K'
                        else ''
                    END as type,
                    'Y' as dataType,
                    coaID,
                    coaDescription as coaName,
                    coaAccountNo as coaNo,
                    jurnalID 
                    FROM jurnal_tx
                    JOIN coa
                    ON jurnalTxCoaID = coaID 
                    AND jurnalTxJurnalID  = ?
                    LEFT JOIN jurnal 
                    ON JurnalID = jurnalTxJurnalID
                    LEFT JOIN jurnal_addon
                    ON jurnalAddOnJurnalID = jurnalID
                    AND jurnalAddOnJurnalTxID = jurnalTxID
                    AND jurnalAddOnCode = 'GJREGBR'
                    LEFT JOIN m_branch 
                    ON jurnalAddOnValue = M_BranchID
                    AND M_BranchIsActive = 'Y'
                    ";
            $qry = $this->db->query($sql, [$dataJurnalRegional['JurnalGajiDetailJurnalID']]);
            if (!$qry) {
                $this->sys_error_db("Error get jurnal tx regional");
                exit;
            }
            $jurnalRegional = $qry->result_array();

            foreach ($jurnalRegional as $key => $value) {
                if (intval($value['kredit']) > 0) {
                    $sql = "SELECT 
                            coaID as id,
                            coaAccountNo as number,
                            coaDescription as keterangan,
                            CONCAT(coaAccountNo, ' - ' ,coaDescription) as display
                            FROM coa
                            WHERE
                            coaIsActive = 'Y'
                            -- AND coaIsInput = 'Y'
                            AND coaID = ?
                            ";
                    $qry = $this->db->query($sql, [$value['coaID']]);
                    if (!$qry) {
                        $this->sys_error_db("Error get coa");
                        exit;
                    }
                    $coaBank = $qry->row_array();
                }
            }
            $sql = "SELECT
                    jurnalTxID as id,
                    IFNULL(M_BranchID, '') as branchID,
                    IFNULL(M_BranchName, '') as branchName,
                    coaDescription as description,
                    jurnalTxDebit as debet,
                    jurnalTxCredit as kredit,
                    0 as precentage,
                    CASE 
                        WHEN jurnalTxDebit > 0 THEN 'D'
                        WHEN jurnalTxCredit > 0 THEN 'K'
                        else ''
                    END as type,
                    'Y' as dataType,
                    coaID,
                    coaDescription as coaName,
                    coaAccountNo as coaNo,
                    jurnalID 
                    FROM jurnal_tx
                    JOIN coa
                    ON jurnalTxCoaID = coaID
                    AND jurnalTxJurnalID  IN ?
                    LEFT JOIN jurnal 
                    ON JurnalID = jurnalTxJurnalID
                    LEFT JOIN m_branch 
                    ON jurnalM_BranchCode = M_BranchCode
                    AND M_BranchIsActive = 'Y'
                    ";
            $qry = $this->db->query($sql, [$arrJurnalBranchID]);
            if (!$qry) {
                $this->sys_error_db("Error get jurnal tx cabang");
                exit;
            }
            $jurnalBranch = $qry->result_array();

            foreach ($jurnalBranch as $key => $value) {
                if (intval($value['debet']) > 0) {
                    $sql = "SELECT 
                            coaID as id,
                            coaAccountNo as number,
                            coaDescription as keterangan,
                            CONCAT(coaAccountNo, ' - ' ,coaDescription) as display
                            FROM coa
                            WHERE
                            coaIsActive = 'Y'
                            -- AND coaIsInput = 'Y'
                            AND coaID = ?
                            ";
                    $qry = $this->db->query($sql, [$value['coaID']]);
                    if (!$qry) {
                        $this->sys_error_db("Error get coa");
                        exit;
                    }
                    $coa = $qry->row_array();
                    break;
                }
            }

            $sql = "SELECT jurnalAddOnJurnalID,
                    jurnalAddOnValue
                    FROM jurnal_addon
                    WHERE jurnalAddOnJurnalID = ?
                    AND jurnalAddOnCode = 'AMSALREG'
                    AND jurnalAddOnIsActive = 'Y';";
            $qry = $this->db->query($sql, [$dataJurnalRegional['JurnalGajiDetailJurnalID']]);
            if (!$qry) {
                $this->sys_error_db("Error get jurnal tx", $this->db);
                exit;
            }
            $sallary = $qry->row_array()['jurnalAddOnValue'];
        } else if ($type == 'B') {
            $jurnalRegional = [];
            $sql = "SELECT 
                    jurnalTxID as id,
                    IFNULL(M_BranchID, '') as branchID,
                    IFNULL(M_BranchName, '') as branchName,
                    coaDescription as description,
                    jurnalTxDebit as debet,
                    jurnalTxCredit as kredit,
                    0 as precentage,
                    CASE 
                        WHEN jurnalTxDebit > 0 THEN 'D'
                        WHEN jurnalTxCredit > 0 THEN 'K'
                        else ''
                    END as type,
                    'Y' as dataType,
                    coaID,
                    coaDescription as coaName,
                    coaAccountNo as coaNo,
                    jurnalID 
                    FROM jurnal_tx
                    JOIN coa
                    ON jurnalTxCoaID = coaID
                    AND jurnalTxJurnalID  = ?
                    LEFT JOIN jurnal 
                    ON JurnalID = jurnalTxJurnalID
                    LEFT JOIN m_branch 
                    ON jurnalM_BranchCode = M_BranchCode
                    AND M_BranchIsActive = 'Y'
                    ";
            $qry = $this->db->query($sql, [$dataHeaderBranch['JurnalGajiDetailJurnalID']]);
            if (!$qry) {
                $this->sys_error_db("Error get jurnal tx cabang");
                exit;
            }
            $jurnalBranch = $qry->result_array();
            foreach ($jurnalBranch as $key => $value) {
                if (intval($value['debet']) > 0) {
                    $sql = "SELECT 
                            coaID as id,
                            coaAccountNo as number,
                            coaDescription as keterangan,
                            CONCAT(coaAccountNo, ' - ' ,coaDescription) as display
                            FROM coa
                            WHERE
                            coaIsActive = 'Y'
                            -- AND coaIsInput = 'Y'
                            AND coaID = ?
                            ";
                    $qry = $this->db->query($sql, [$value['coaID']]);
                    if (!$qry) {
                        $this->sys_error_db("Error get coa");
                        exit;
                    }
                    $coa = $qry->row_array();
                    break;
                }
            }
            $coaBank = (object) [];
        }
        $result = array(
            'dataHeader' => $dataHeaderBranch,
            "type" => $type,
            "date" => $date,
            "periode" => $jurnalPeriode,
            "title" => $title,
            "coa" => $coa,
            "coaKas" => $coaBank,
            "sallary" => $sallary,
            "description" => $description,
            "branchPrecent" => $branchPercent,
            "jurnalDetail" => $jurnalBranch,
            "jurnalDetailRegional" => $jurnalRegional,
        );
        $this->sys_ok($result);
    }
    function deleteJurnalgaji()
    {
        $this->db->trans_begin();
        // $this->db->trans_rollback();
        // $this->db->trans_commit();
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $id =  $prm['id'];
        $user = $this->sys_user;
        $userid = $user["M_UserID"];

        $sql = "SELECT * 
                FROM jurnal_gaji_header
                JOIN jurnal_gaji_detail
                ON JurnalGajiHeaderID = JurnalGajiDetailJurnalGajiHeaderID
                AND JurnalGajiDetailIsActive = 'Y'
                AND 
                JurnalGajiHeaderID IN 
                (SELECT JurnalGajiDetailJurnalGajiHeaderID FROM jurnal_gaji_detail WHERE JurnalGajiDetailJurnalID = ?)
                AND JurnalGajiHeaderIsActive = 'Y'
                JOIN jurnal 
                ON JurnalGajiDetailJurnalID = jurnalID
                AND jurnalIsActive = 'Y'";
        $qry = $this->db->query($sql, array($id));
        if (!$qry) {
            $this->sys_error_db("Error insert jurnal tx kredit");
            $this->db->trans_rollback();
            exit;
        }
        $jurnalHeader = $qry->result_array();
        $result = array();
        $type = '';
        $dataHeaderBranch = array();
        $arrJurnalID = array();
        foreach ($jurnalHeader as $key => $value) {
            if ($value['JurnalGajiDetailJurnalID'] == $id) {
                $type = $value['JurnalGajiDetailType'];
                $dataHeaderBranch = $value;
            }
            $arrJurnalID[] = $value['jurnalID'];
            if ($value['jurnalIsPosted'] == 'Y') {
                $this->sys_error('Jurnal yang sudah di post tidak bisa di hapus');
                exit;
            }
        }
        // echo json_encode($jurnalHeader);
        // exit;

        if ($type == 'B') {
            $this->sys_error('Menghapus data harus melalui jurnal regionalnya');
            exit;
        }

        $sql = "UPDATE jurnal_gaji_header
                SET JurnalGajiHeaderIsActive = 'N',
                JurnalGajiHeaderDeletedUserID = ?
                WHERE 
                JurnalGajiHeaderID  = ?";
        $qry = $this->db->query($sql, array($userid, $dataHeaderBranch['JurnalGajiHeaderID']));
        if (!$qry) {
            $this->sys_error_db("Error delet jurnal gaji header");
            $this->db->trans_rollback();
            exit;
        }
        $sql = "UPDATE jurnal_gaji_detail
                SET JurnalGajiDetailIsActive = 'N',
                JurnalGajiDetailDeletedUserID = ?
                WHERE 
                JurnalGajiDetailJurnalGajiHeaderID  =  ?";
        $qry = $this->db->query($sql, array($userid, $dataHeaderBranch['JurnalGajiHeaderID']));
        if (!$qry) {
            $this->sys_error_db("Error delet jurnal gaji detail");
            $this->db->trans_rollback();
            exit;
        }
        $sql = "UPDATE jurnal
                SET jurnalIsActive = 'N',
                jurnalM_UserID = ?
                WHERE 
                jurnalID  IN  ?";
        $qry = $this->db->query($sql, array($userid, $arrJurnalID));
        if (!$qry) {
            $this->sys_error_db("Error delet jurnal gaji");
            $this->db->trans_rollback();
            exit;
        }
        $sql = "UPDATE jurnal_tx
                SET jurnalTxIsActive = 'N',
                jurnalTxM_UserID = ?
                WHERE 
                jurnalTxJurnalID  IN  ?";
        $qry = $this->db->query($sql, array($userid, $arrJurnalID));
        if (!$qry) {
            $this->sys_error_db("Error delet jurnal gaji tx");
            $this->db->trans_rollback();
            exit;
        }
        $sql = "UPDATE jurnal_addon
                SET jurnalAddOnIsActive = 'N',
                jurnalAddOnDeletedUserID = ?
                WHERE 
                jurnalAddOnJurnalID  IN  ?";
        $qry = $this->db->query($sql, array($userid, $arrJurnalID));
        if (!$qry) {
            $this->sys_error_db("Error delet jurnal gaji tx");
            $this->db->trans_rollback();
            exit;
        }
        $this->db->trans_commit();
        $this->sys_ok('Success delete jurnal gaji');
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
                CONCAT(coaAccountNo, ' - ' ,coaDescription) as display
                FROM coa
                WHERE
                coaIsActive = 'Y'
                AND coaIsInput = 'Y'
                AND (CONCAT(coaAccountNo, ' - ' ,coaDescription) LIKE ?)
                LIMIT 70
                 
                ";
        $qry = $this->db->query($sql, [$search]);
        if (!$qry) {
            $this->sys_error_db("Error get coa", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
    function searchCoaDetail()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $regional = $prm['regional'];
        $branch = $prm['branch'];
        $company = $prm['company'];
        $sql = "SELECT 
                coaID as id,
                coaAccountNo as number,
                coaDescription as keterangan,
                CONCAT(coaAccountNo, ' - ' ,coaDescription) as display
                FROM coa
                JOIN coa_branch
                ON coaID = CoaBranchCoaID
                AND CoaBranchIsActive = 'Y'
                JOIN m_branch
                ON CoaBranchM_BranchID = M_BranchID
                JOIN m_branch_companydetail
                ON M_BranchCode  = M_BranchCompanyDetailM_BranchCode
                WHERE M_BranchS_RegionalID =  ?
                AND M_BranchCompanyDetailIsActive = 'Y'
                AND M_BranchCompanyDetailM_BranchCompanyID = ?
                AND M_BranchIsActive = 'Y'
                AND M_BranchID = ?
                AND coaIsInput = 'Y'
                AND coaIsActive = 'Y'
                AND (CONCAT(coaAccountNo, ' - ' ,coaDescription) LIKE ?)
                LIMIT 70
                 
                ";
        $qry = $this->db->query($sql, [$regional, $company, $branch, $search]);
        if (!$qry) {
            $this->sys_error_db("Error get coa", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
    function searchBranchPercent()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $regional = $prm['regional'];
        $sql = "SELECT * 
                FROM branch_percent 
                JOIN branch_percent_detail
                ON BranchPercentID = BranchPercentDetailBranchPercentID
                AND BranchPercentIsActive = 'Y'
                AND BranchPercentDetailIsActive = 'Y'
                AND BranchPercentDetailS_RegionalID = ?
                JOIN coa
                ON BranchPercentDetailAccountNumber = coaAccountNo 
                AND coaIsActive = 'Y'
                ";
        $qry = $this->db->query($sql, [$regional]);
        if (!$qry) {
            $this->sys_error_db("Error get branch_percent", $this->db);
            exit;
        }
        $data = $qry->result_array();
        // echo (json_encode($data));

        $tmpID = array();
        $rst = array();
        foreach ($data as $key => $value) {
            if (!in_array($value['BranchPercentID'], $tmpID)) {
                $tmpID[] = $value['BranchPercentID'];
                $rst[] = $value;
            }
        }
        // echo (json_encode($rst));
        $tmpDetail = array();
        foreach ($rst as $i => $value) {
            foreach ($data as $j => $detail) {
                if (
                    !in_array($detail['BranchPercentDetailID'], $tmpDetail)
                    && $detail['BranchPercentDetailBranchPercentID'] == $value['BranchPercentID']
                ) {
                    $tmpDetail[] = $detail['BranchPercentDetailID'];
                    $rst[$i]['detail'][] = $detail;
                    # code...
                }
            }
        }
        $this->sys_ok($rst);
    }
    function searchPeriode()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $sql = "SELECT 
                CONCAT(periodeYear, ' - ', periodeName) as display,
                periode.* 
                FROM periode
                WHERE periodeIsActive = 'Y'
                AND periodeIsClosed = 'N'
                AND CONCAT(periodeYear, ' - ', periodeName) LIKE ?
                LIMIT 70
                ";
        $qry = $this->db->query($sql, [$search]);
        if (!$qry) {
            $this->sys_error_db("Error get coa", $this->db);
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
    function getBranch()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $sql = "SELECT 
                M_BranchID as branchID,
                M_BranchS_RegionalID as branchRegionalID,
                M_BranchCode as branchCode ,
                M_BranchName as branchName,
                M_BranchCompanyDetailM_BranchCompanyID branchCompanyID
                FROM m_branch
                JOIN m_branch_companydetail
                ON M_BranchCode  = M_BranchCompanyDetailM_BranchCode
                WHERE M_BranchS_RegionalID =  ?
                AND M_BranchCompanyDetailIsActive = 'Y'
                AND M_BranchCompanyDetailM_BranchCompanyID = ?
                AND M_BranchIsActive = 'Y'";
        $qry = $this->db->query($sql, array($prm['regional'], $prm['company']));

        if (!$qry) {
            // $this->db->trans_rollback();
            $this->sys_error_db("Error get branch");
            exit;
        }
        $data = $qry->result_array();
        $this->sys_ok($data);
    }
    function getJurnalType()
    {
        if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $sql = "SELECT * FROM jurnal_type WHERE JurnalTypeIsActive = 'Y'
                ";
        $qry = $this->db->query($sql, []);
        if (!$qry) {
            $this->sys_error_db("Error truncate", $this->db);
            exit;
        }
        $default = array();
        $data = $qry->result_array();
        foreach ($data as $key => $value) {
            if ($value['JurnalTypeCode'] == 'SALARYREG') {
                $default = $value;
            }
        }
        $this->sys_ok([
            "records" => $data,
            "default" => $default
        ]);
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
