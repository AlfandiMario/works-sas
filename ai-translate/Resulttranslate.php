<?php
class Resulttranslate extends MY_Controller
{
    var $db_onedev;
    var $load;
    var $kesimpulanfisik;

    public function index()
    {
        echo "CPONE RESUME INDIVIDU API";
    }

    public function __construct()
    {
        parent::__construct();
        $this->db_onedev = $this->load->database("onedev", true);
        // $this->load->library("SsPriceMou");
    }
    public function getsetup()
    {
        try {
            // if (!$this->isLogin) {
            // 	$this->sys_error("Invalid Token");
            // 	exit;
            // }
            $sql = "SELECT * FROM mgm_mcu WHERE Mgm_McuIsActive = 'Y'";
            $qry = $this->db_onedev->query($sql, []);
            if (!$qry) {
                $message = $this->db_onedev->error();
                $last_qry = $this->db_onedev->last_query();
                $message['last_qry'] = $last_qry;
                $this->sys_error($message);
                exit;
            }
            $data = $qry->result_array();
            $result = [
                "records" => $data,
            ];
            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
    function search()
    {
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $search = '%' . $prm['search'] . '%';
        $page = $prm["page"];
        $startDate = $prm["startDate"];
        $endDate = $prm["endDate"];
        $setupID = $prm["setupID"];
        $ROW_PER_PAGE = 20;
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
                COUNT(T_OrderHeaderID) AS total
                FROM t_orderheader
                JOIN m_patient
                ON T_OrderHeaderM_PatientID = M_PatientID
                JOIN corporate 
                ON T_OrderHeaderCorporateID = CorporateID
                JOIN m_branch ON T_OrderHeaderM_BranchID = M_BranchID
                LEFT JOIN m_title
                ON M_PatientM_TitleID = M_TitleID
                WHERE (DATE_FORMAT(T_OrderHeaderDate, '%Y-%m-%d') BETWEEN ? AND ?)
                AND (M_PatientName LIKE ? OR T_OrderHeaderLabNumber LIKE ?)
                AND T_OrderHeaderMgm_McuID = ?
                AND T_OrderHeaderIsActive = 'Y'
                ORDER BY T_OrderHeaderLabNumber
                ";
        $query = $this->db_onedev->query($sql, [$startDate, $endDate, $search, $search, $setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $totalPage = $query->row_array()['total'];
        $sql = "SELECT 
                T_OrderHeaderID AS orderID,
                IFNULL(Mcu_ResumeStatus, 'NEW') as status,
                T_OrderHeaderM_BranchID AS branchID,
                DATE_FORMAT(T_OrderHeaderDate, '%d-%m-%Y') AS orderDate,
                T_OrderHeaderLabNumber	AS labNumber,
                T_OrderHeaderM_PatientID AS patientID,
                T_OrderHeaderCorporateID AS corporateID,
                T_OrderHeaderMgm_McuID AS setupID,
                UPPER(T_OrderHeaderM_PatientAge) AS patientAge,
                M_PatientNoReg AS patientReg,
                M_PatientName AS patientName,
                UPPER(CONCAT(IF(ISNULL(M_TitleName),'',CONCAT(M_TitleName,'.')),
                ' ',
                IFNULL(M_PatientPrefix,''),
                ' ',
                M_PatientName,
                ' ',
                IFNULL(M_PatientSuffix,''))) AS patientFullname,
                CASE 
                    WHEN LOWER(M_PatientGender) = 'male' THEN 'LAKI-LAKI'
                    WHEN LOWER(M_PatientGender) = 'female' THEN 'PEREMPUAN'
                END patientGender,
                M_PatientPhoto AS patientFoto,
                M_PatientPhotoThumb AS patientFotoThumb,
                CorporateCode AS corporateCode,
                UPPER(CorporateName) AS corporateName,
                M_BranchCode AS branchID,		
                M_BranchName AS branchName
                FROM t_orderheader
                JOIN m_patient
                ON T_OrderHeaderM_PatientID = M_PatientID
                JOIN corporate 
                ON T_OrderHeaderCorporateID = CorporateID
                JOIN m_branch ON T_OrderHeaderM_BranchID = M_BranchID
                LEFT JOIN m_title
                ON M_PatientM_TitleID = M_TitleID
                LEFT JOIN mcu_resume
                ON T_OrderHeaderID = Mcu_ResumeT_OrderHeaderID
                AND Mcu_ResumeIsActive = 'Y'
                WHERE (DATE_FORMAT(T_OrderHeaderDate, '%Y-%m-%d') BETWEEN ? AND ?)
                AND (M_PatientName LIKE ? OR T_OrderHeaderLabNumber LIKE ?)
                AND T_OrderHeaderMgm_McuID = ?
                AND T_OrderHeaderIsActive = 'Y'
                ORDER BY T_OrderHeaderLabNumber
                LIMIT ? OFFSET ? ";
        $query = $this->db_onedev->query($sql, [$startDate, $endDate, $search, $search, $setupID, $ROW_PER_PAGE, $start_offset]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        // echo $this->db_onedev->last_query();
        $result = [
            "total" => ceil($totalPage / $ROW_PER_PAGE),
            "records" => $query->result_array()
        ];
        $this->sys_ok($result);
    }
    public function getDetail()
    {
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $orderid =  $prm['orderid'];
        $lang =  $prm['lang'];
        //GET LAB
        $sql = "SELECT
                T_OrderDetailT_OrderHeaderID AS orderID,
                T_OrderDetailID AS orderDetailID,
                T_OrderDetailT_TestID AS testID,
                T_OrderDetailT_TestCode AS testCode,
                T_OrderDetailT_TestSasCode AS testSasCode,
                T_OrderDetailNat_UnitID AS natUnitID,
                T_OrderDetailT_TestName AS testName,
                T_OrderDetailResult AS result,
                T_OrderDetailLangResult AS langResult, 
                T_OrderDetailLangAiResult AS aiResult,
                T_OrderDetailLangAiConfidence AS aiConfidence,
                Nat_SubSubGroupID AS subGroupID,
                Nat_SubSubGroupName AS groupName,
                T_TestNat_TestID AS natTestID,
                T_OrderDetailNat_NormalValueID AS natNormalvalueID,
                T_OrderDetailNormalValueNote as normalValueNote,
                T_OrderDetailResultFlag AS resultFlag,
                T_OrderDetailNote as resultNote,
                IFNULL(T_OrderDetailLangNat_UnitLangName, '') AS displayUnitName,
                T_OrderDetailNat_UnitName AS unitName,
                CASE 
                    WHEN T_OrderDetailLangID IS NULL THEN 'N'
                    WHEN T_OrderDetailLangID IS NOT NULL AND (T_OrderDetailLangResult IS NULL OR T_OrderDetailLangResult = '') THEN 'N'
                    WHEN T_OrderDetailLangID IS NOT NULL AND T_OrderDetailLangResult <> '' THEN 'Y'
                    ELSE 'N'
                END as status,
                IFNULL(T_OrderDetailLangIsEdited, 'N') as isEdited,
                CASE 
                    WHEN T_OrderDetailLangID IS NULL THEN T_OrderDetailLangAiResult 
                    WHEN T_OrderDetailLangID IS NOT NULL AND (T_OrderDetailLangResult IS NULL OR T_OrderDetailLangResult = '') THEN T_OrderDetailLangAiResult 
                    WHEN T_OrderDetailLangID IS NOT NULL AND T_OrderDetailLangResult <> '' THEN T_OrderDetailLangResult 
                    ELSE T_OrderDetailLangAiResult 
                END as displayResult
                FROM t_orderdetail
                JOIN group_resultdetail
                ON T_OrderDetailT_TestID = Group_ResultDetailT_TestID
                AND Group_ResultDetailIsActive ='Y'
                AND T_OrderDetailT_OrderHeaderID = ?
                AND T_OrderDetailIsActive = 'Y'
                AND T_OrderDetailT_TestIsResult = 'Y'
                JOIN group_result 
                ON Group_ResultID = Group_ResultDetailGroup_ResultID
                AND Group_ResultResumeMcu IN ('LAB')
                JOIN t_test 
                ON T_OrderDetailT_TestID = T_TestID
                JOIN nat_subsubgroup
                ON  T_TestNat_SubSubGroupID=Nat_SubSubGroupID
                LEFT JOIN t_orderdetaillang
                ON T_OrderDetailID = T_OrderDetailLangT_OrderDetailID
                AND T_OrderDetailLangIsActive = 'Y'
                AND T_OrderDetailLangNat_LangID = ?
                LEFT JOIN t_orderdetaillang_ai
                ON T_OrderDetailID = T_OrderDetailLangAiT_OrderDetailID
                AND T_OrderDetailLangAiNat_LangID = ?
                AND T_OrderDetailLangAiIsActive = 'Y'";
        $query = $this->db_onedev->query($sql, [$orderid, $lang, $lang]);
        if (!$query) {
            $this->sys_error_db("Error get detail",$db_onedev);
            exit;
        }
        $lab = $query->result_array();

        // GET NONLAB & FISIK
        $sql = "SELECT
                T_OrderDetailT_OrderHeaderID AS orderID,
                T_OrderDetailID AS orderDetailID,
                T_OrderDetailT_TestID AS testID,
                T_OrderDetailT_TestCode AS testCode,
                T_OrderDetailT_TestSasCode AS testSasCode,
                Group_ResultResumeMcu ,
                T_OrderDetailT_TestName AS testName,
                T_TestNat_TestID AS natTestID,
                So_ResultEntryID as resultEntryID,
                So_ResultEntryStatus  as resultEntryStatus,
                So_ResultEntryNonlab_TemplateID as templateID,
                So_ResultEntryNonlab_TemplateName as templateName
                FROM t_orderdetail
                JOIN group_resultdetail
                ON T_OrderDetailT_TestID = Group_ResultDetailT_TestID
                AND Group_ResultDetailIsActive ='Y'
                AND T_OrderDetailT_OrderHeaderID = ?
                AND T_OrderDetailIsActive = 'Y'
                AND T_OrderDetailT_TestIsResult = 'Y'
                JOIN group_result 
                ON Group_ResultID = Group_ResultDetailGroup_ResultID
                AND Group_ResultResumeMcu IN ('NONLAB','FISIK')
                JOIN t_test 
                ON T_OrderDetailT_TestID = T_TestID
                JOIN so_resultentry
                ON T_OrderDetailT_OrderHeaderID  = So_ResultEntryT_OrderHeaderID
                AND T_OrderDetailID  =So_ResultEntryT_OrderDetailID
                -- AND So_ResultEntryStatus = 'VAL1'
                AND So_ResultEntryIsActive = 'Y'
                GROUP BY T_TestID;";
        $query = $this->db_onedev->query($sql, [$orderid]);
        if (!$query) {
            $this->sys_error_db("Error get detail nonlab");
            exit;
        }
        $rstNonlab = $query->result_array();


        //pecah nonlab dan fisik
        $nonlab = array();
        $fisik = array();
        for ($i = 0; $i < count($rstNonlab); $i++) {
            $data = $rstNonlab[$i];
            if ($data['Group_ResultResumeMcu'] == 'NONLAB') {
                $sql = "SELECT 
                        So_ResultEntryDetailID AS resultEntryDetailID,
                        So_ResultEntryDetailSo_ResultEntryID as resultEntryID,
                        So_ResultEntryDetailNonlab_TemplateDetailID as templateDetailID,
                        So_ResultEntryDetailNonlab_TemplateDetailName as templateDetailName,
                        NonlabTemplateDetailLangID as templateDetailLangID,
                        NonlabTemplateDetailLangName as templateDetailLangName,
                        NonlabTemplateDetailCode as templateDetailCode,
                        NonlabTemplateDetailIsResult as templateDetailIsResult,
                        So_ResultEntryDetailResult as result,
                        IFNULL(So_ResultEntryDetailOtherResult, '') as langResult,
                        IFNULL(So_ResultEntryDetailOtherAiResult,'') as aiResult,
                        IFNULL(So_ResultEntryDetailOtherAiConfidence, '') as aiConfidence,
                        CASE
                            WHEN So_ResultEntryDetailOtherResult IS NULL OR So_ResultEntryDetailOtherResult = '' THEN IFNULL(So_ResultEntryDetailOtherAiResult,'')
                            WHEN So_ResultEntryDetailOtherResult IS NOT NULL OR So_ResultEntryDetailOtherResult <> '' THEN IFNULL(So_ResultEntryDetailOtherResult,'')
                            ELSE IFNULL(So_ResultEntryDetailOtherAiResult,'')
                        END as displayResult,
                        CASE
                            WHEN So_ResultEntryDetailOtherResult IS NULL OR So_ResultEntryDetailOtherResult = '' THEN 'N'
                            WHEN So_ResultEntryDetailOtherResult IS NOT NULL OR So_ResultEntryDetailOtherResult <> '' THEN 'Y'
                            ELSE 'N'
                        END as status
                        FROM so_resultentrydetail
                        JOIN nonlab_template_detail
                        ON So_ResultEntryDetailNonlab_TemplateDetailID = NonlabTemplateDetailID
                        AND So_ResultEntryDetailSo_ResultEntryID = ?
                        LEFT JOIN nonlab_template_detail_lang
                        ON So_ResultEntryDetailNonlab_TemplateDetailID  =NonlabTemplateDetailLangNonlabTemplateDetailID
                        AND NonlabTemplateDetailLangM_LangID = ?
                        LEFT JOIN so_resultentrydetail_other
                        ON So_ResultEntryDetailSo_ResultEntryID  =  So_ResultEntryDetailOtherSo_ResultEntryID
                        AND NonlabTemplateDetailLangID = So_ResultEntryDetailOtherSo_TemplateDetailID
                        AND So_ResultEntryDetailOtherM_LangID = ?
                        AND So_ResultEntryDetailOtherIsActive = 'Y'
                        LEFT JOIN so_resultentrydetail_other_ai 
                        ON So_ResultEntryDetailSo_ResultEntryID  =  So_ResultEntryDetailOtherAiSo_ResultEntryID
                        AND So_ResultEntryDetailNonlab_TemplateDetailID  = So_ResultEntryDetailOtherAiNonlab_TemplateDetailID
                        AND So_ResultEntryDetailOtherAiIsActive = 'Y'
                        AND So_ResultEntryDetailOtherM_LangAiID = ?
                        ORDER BY NonlabTemplateDetailCode
                ";
                $query = $this->db_onedev->query($sql, [$data['resultEntryID'], $lang, $lang, $lang]);
                if (!$query) {
                    $this->sys_error_db("Error get result nonlab");
                    exit;
                }
                $resultEntry = $query->result_array();
                $data['detail'] = $resultEntry;
                array_push($nonlab, $data);
            } else if ($data['Group_ResultResumeMcu'] == 'FISIK') {

                if (
                    $data['templateName'] == "Fisik Umum" ||
                    $data['templateName'] == "Fisik Umum K3" ||
                    $data['templateName'] == "Fisik Umum Konsul"
                ) {
                    $rst = $this->getFisikUmum($data['resultEntryID'], 'N', $lang);
                    $data['detail'] = $rst['finalResult'];
                    array_push($fisik, $data);
                }
            }
        }


        $result = array(
            'lab' => $lab,
            'nonlab' => $nonlab,
            'fisik' => $fisik,
            'rstNonlab' => $rstNonlab
        );
        $this->sys_ok($result);
    }
    function getFisikUmum($reID, $debug = 'N', $lang = '1')
    {
        $rst = [];
        $riwayats = [];
        $fisiks = [];
        $k3s = [];
        $sql = "SELECT *
				FROM so_resultentry_fisik_umum 
				JOIN fisik_template ON So_ResultEntryFisikUmumFisikTemplateID = FisikTemplateID
				WHERE
				So_ResultEntryFisikUmumSo_ResultEntryID = {$reID} AND So_ResultEntryFisikUmumIsActive = 'Y' 
				ORDER BY FisikTemplateCode ASC";
        $rows_data = $this->db_onedev->query($sql)->result_array();
        // print_r($rows_data);
        // echo $sql;
        if ($rows_data) {
            foreach ($rows_data as $key => $value) {
                $data = json_decode($value['So_ResultEntryFisikUmumDetails'],  TRUE);
                $data['reFisikUmumID'] = $value['So_ResultEntryFisikUmumID'];

                if ($value['FisikTemplateType'] == 'Riwayat')
                    $riwayats[] = $data;

                if ($value['FisikTemplateType'] == 'Fisik')
                    $fisiks[] = $data;

                if ($value['FisikTemplateType'] == 'K3')
                    $k3s[] = $data;
            }
        }
        // $reFisikUmumID = $rows_data['So_ResultEntryFisikUmumID'];
        $finalResult = [];
        foreach ($riwayats as $key => $data) {
            if ($data['type_form'] == 'XV') {
                foreach ($data['details'] as $key1 => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XO') {
                foreach ($data['details'] as $key2 => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XVS') {
                foreach ($data['details'] as $key3 => $detail) {
                    foreach ($detail['details'] as $key4 => $detailData) {
                        if ($detailData['value'] != '') {
                            $detailData['reFisikUmumID'] = $data['reFisikUmumID'];
                            $detailData['title'] = $data['title'];
                            $finalResult[] = $detailData;
                        }
                    }
                }
            }
            if ($data['type_form'] == 'XD') {
                foreach ($data['details'] as $key5 => $detail) {
                    foreach ($detail['details'] as $key6 => $detailData) {
                        if ($detailData['value'] != '') {
                            $detailData['reFisikUmumID'] = $data['reFisikUmumID'];
                            $detailData['title'] = $data['title'];
                            $finalResult[] = $detailData;
                        }
                    }
                }
            }
        }
        foreach ($fisiks as $key => $data) {
            if ($data['type_form'] == 'VXX+') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XXV') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XV') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XXVWL') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XVS') {
                foreach ($data['details'] as $key => $detail) {
                    foreach ($detail['details'] as $key => $detailData) {
                        if ($detailData['value'] != '') {
                            $detailData['reFisikUmumID'] = $data['reFisikUmumID'];
                            $detailData['title'] = $data['title'];
                            $finalResult[] = $detailData;
                        }
                    }
                }
            }
            if ($data['type_form'] == 'XVS3R') {
                foreach ($data['details'] as $key => $detail) {
                    foreach ($detail['details'] as $key => $detailData) {
                        if ($detailData['value'] != '') {
                            $detailData['reFisikUmumID'] = $data['reFisikUmumID'];
                            $detailData['title'] = $data['title'];
                            $finalResult[] = $detailData;
                        }
                    }
                }
            }
        }
        foreach ($k3s as $key => $data) {
            if ($data['type_form'] == 'XVV') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value_sumber'] != '' || $detail['value_sumber'] != '') {
                        $dtl = $detail;
                        $dtl['reFisikUmumID'] = $data['reFisikUmumID'];
                        $dtl['value'] = $detail['value_sumber'] . '|' . $detail['value_sumber'];
                        $dtl['title'] = $data['title'];
                        $finalResult[] = $dtl;
                    }
                }
            }
            if ($data['type_form'] == 'XXV') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XV') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XXVWL') {
                foreach ($data['details'] as $key => $detail) {
                    if ($detail['value'] != '') {
                        $detail['reFisikUmumID'] = $data['reFisikUmumID'];
                        $detail['title'] = $data['title'];
                        $finalResult[] = $detail;
                    }
                }
            }
            if ($data['type_form'] == 'XVS') {
                foreach ($data['details'] as $key => $detail) {
                    foreach ($detail['details'] as $key => $detailData) {
                        if ($detailData['value'] != '') {
                            $detailData['reFisikUmumID'] = $data['reFisikUmumID'];
                            $detailData['title'] = $data['title'];
                            $finalResult[] = $detailData;
                        }
                    }
                }
            }
            if ($data['type_form'] == 'XVS3R') {
                foreach ($data['details'] as $key => $detail) {
                    foreach ($detail['details'] as $key => $detailData) {
                        if ($detailData['value'] != '') {
                            $detailData['reFisikUmumID'] = $data['reFisikUmumID'];
                            $detailData['title'] = $data['title'];
                            $finalResult[] = $detailData;
                        }
                    }
                }
            }
        }
        foreach ($finalResult as $key => $value) {
            if (property_exists($value, 'segment_name')) {
                $sql = "SELECT 
                        So_ResultEntryFisikUmumAiID AS aiTranslateID,
                        So_ResultEntryFisikUmumAiTranslate AS aiResult,
                        So_ResultEntryFisikUmumAiConfidence AS aiConfidence
                        FROM so_resultentry_fisik_umum_ai
                        WHERE So_ResultEntryFisikUmumAiSo_ResultEntryFisikUmumID = ?
                        AND So_ResultEntryFisikUmumAiLangID = ?
                        AND So_ResultEntryFisikUmumAiTableName = ?
                        AND So_ResultEntryFisikUmumAiSegment = ?
                        AND So_ResultEntryFisikUmumAiCode = ?
                        AND So_ResultEntryFisikUmumAiIsActive = 'Y'";
                $query = $this->db_onedev->query($sql, [
                    $value['reFisikUmumID'],
                    $lang,
                    $value['table_name'],
                    $value['segment_name'],
                    $value['id_code'],
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error get ai fisik umum");
                    exit;
                }
                $aiTranslationID = "";
                $aiResult = "";
                $aiConfidence = "";
                $rst = $query->result_array();
                if (count($rst) != 0) {
                    $aiTranslationID = $rst[0]['aiTranslateID'];
                    $aiResult = $rst[0]['aiResult'];
                    $aiConfidence = $rst[0]['aiConfidence'];
                }
                $sql = "SELECT 
                        So_ResultEntryFisikUmumOtherID AS resultTranslateID,
                        So_ResultEntryFisikUmumOtherTranslate AS displayResult
                        FROM so_resultentry_fisik_umum_other
                        WHERE So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID = ?
                        AND So_ResultEntryFisikUmumOtherLangID = ?
                        AND So_ResultEntryFisikUmumOtherTableName = ?
                        AND So_ResultEntryFisikUmumOtherSegment = ?
                        AND So_ResultEntryFisikUmumOtherCode = ?
                        AND So_ResultEntryFisikUmumOtherIsActive = 'Y'";
                $query = $this->db_onedev->query($sql, [
                    $value['reFisikUmumID'],
                    $lang,
                    $value['table_name'],
                    $value['segment_name'],
                    $value['id_code'],
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error get ai fisik umum");
                    exit;
                }
                $resultTranslationID = "";
                $displayResult = "";
                $status = 'Y';
                $rst = $query->result_array();
                if (count($rst) != 0) {
                    $resultTranslationID = $rst[0]['resultTranslateID'];
                    $displayResult = $rst[0]['displayResult'];
                }
                if ($displayResult == '') {
                    $displayResult = $aiResult;
                    $status =  'N';
                }
                $finalResult[$key]['resultTranslateID'] = $resultTranslationID;
                $finalResult[$key]['displayResult'] = $displayResult;
                $finalResult[$key]['aiTranslateID'] = $aiTranslationID;
                $finalResult[$key]['aiResult'] = $aiResult;
                $finalResult[$key]['aiConfidence'] = $aiConfidence;
                $finalResult[$key]['status'] = $status;
            } else {
                $sql = "SELECT 
                        So_ResultEntryFisikUmumAiID AS aiTranslateID,
                        So_ResultEntryFisikUmumAiTranslate AS aiResult,
                        So_ResultEntryFisikUmumAiConfidence AS aiConfidence
                        FROM so_resultentry_fisik_umum_ai
                        WHERE So_ResultEntryFisikUmumAiSo_ResultEntryFisikUmumID = ?
                        AND So_ResultEntryFisikUmumAiLangID = ?
                        AND So_ResultEntryFisikUmumAiTableName = ?
                        AND So_ResultEntryFisikUmumAiCode = ?
                        AND So_ResultEntryFisikUmumAiIsActive = 'Y'";
                $query = $this->db_onedev->query($sql, [
                    $value['reFisikUmumID'],
                    $lang,
                    $value['table_name'],
                    $value['id_code'],
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error get translate resuly fisik umum");
                    exit;
                }
                $aiTranslationID = "";
                $aiResult = "";
                $aiConfidence = "";
                $rst = $query->result_array();
                if (count($rst) != 0) {
                    $aiTranslationID = $rst[0]['aiTranslateID'];
                    $aiResult = $rst[0]['aiResult'];
                    $aiConfidence = $rst[0]['aiConfidence'];
                }
                $sql = "SELECT 
                        So_ResultEntryFisikUmumOtherID AS resultTranslateID,
                        So_ResultEntryFisikUmumOtherTranslate AS displayResult
                        FROM so_resultentry_fisik_umum_other
                        WHERE So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID = ?
                        AND So_ResultEntryFisikUmumOtherLangID = ?
                        AND So_ResultEntryFisikUmumOtherTableName = ?
                        AND So_ResultEntryFisikUmumOtherCode = ?
                        AND So_ResultEntryFisikUmumOtherIsActive = 'Y'";
                $query = $this->db_onedev->query($sql, [
                    $value['reFisikUmumID'],
                    $lang,
                    $value['table_name'],
                    $value['id_code'],
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error get ai fisik umum");
                    exit;
                }
                $resultTranslationID = "";
                $displayResult = "";
                $status = 'Y';
                $rst = $query->result_array();
                if (count($rst) != 0) {
                    $resultTranslationID = $rst[0]['resultTranslateID'];
                    $displayResult = $rst[0]['displayResult'];
                }
                if ($displayResult == '') {
                    $displayResult = $aiResult;
                    $status = 'N';
                }
                $finalResult[$key]['resultTranslateID'] = $resultTranslationID;
                $finalResult[$key]['displayResult'] = $displayResult;
                $finalResult[$key]['aiTranslateID'] = $aiTranslationID;
                $finalResult[$key]['aiResult'] = $aiResult;
                $finalResult[$key]['aiConfidence'] = $aiConfidence;
                $finalResult[$key]['status'] = $status;
            }
        }


        $rst['riwayats'] = $riwayats;
        $rst['fisiks'] = $fisiks;
        $rst['k3s'] = $k3s;
        $rst['finalResult'] = $finalResult;

        if ($debug == 'Y') {
            $this->sys_ok($rst);
        } else {
            return $rst;
        }
    }
    public function save()
    {
        $userid = $this->sys_user["M_UserID"];
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $orderid =  $prm['orderid'];
        $userid = $this->sys_user["M_UserID"];
        $lang =  $prm['lang'];
        $lab = $prm['lab'];
        $sql = "SELECT *
                FROM `m_lang`
                WHERE `M_LangID` = ?
                LIMIT 1";
        $query = $this->db_onedev->query($sql, [$lang]);
        if (!$query) {
            $this->sys_error_db("Error get language");
            exit;
        }
        $language = $query->result_array();
        if (count($language) == 0) {
            $this->sys_error_db("Bahasa tidak ditemukan");
            exit;
        }
        $dataLang = $language[0];
        $logType = "";
        $jsonBefore = "";
        $sql = "SELECT * FROM t_orderdetaillang WHERE T_OrderDetailLangT_OrderHeaderID = ? AND T_OrderDetailLangNat_LangID = ?";
        $query = $this->db_onedev->query($sql, [$orderid, $lang]);
        if (!$query) {
            $this->sys_error_db("Error log data before");
            exit;
        }
        $rstJsonBefore = $query->result_array();
        if (count($rstJsonBefore) == 0) {
            $logType = "ADD";
        } else {
            $logType = "UPDATE";
        }
        $jsonBefore = json_encode($rstJsonBefore);
        for ($i = 0; $i < count($lab); $i++) {
            $data = $lab[$i];
            $sql = "SELECT *
                    FROM `nat_testlang`
                    WHERE `Nat_TestLangNat_TestID` = ? 
                    AND `Nat_testLangIsActive` = 'Y' 
                    AND Nat_TestLangLangID = ?
                    LIMIT 1";
            $query = $this->db_onedev->query($sql, [$data['natTestID'], $lang]);
            if (!$query) {
                $this->sys_error_db("Error get language");
                exit;
            }
            $natTestLang = $query->row_array();


            $sql = "SELECT *
                    FROM `nat_unitlang`
                    WHERE `Nat_UnitLangNat_UnitID` = ? 
                    AND `Nat_UnitLangIsActive` = 'Y' 
                    AND `Nat_UnitLangNat_LangID` = ?
                    LIMIT 1";
            $query = $this->db_onedev->query($sql, [$data['natUnitID'], $lang]);
            if (!$query) {
                $this->sys_error_db("Error get lang unit");
                exit;
            }
            $natUnitLang = $query->row_array();
            $unitID = '';
            $unitName = '';
            if ($data['natUnitID'] != '' && $data['natUnitID'] != null) {
                $unitID = $natUnitLang['Nat_UnitLangID'];
                $unitName = $natUnitLang['Nat_UnitLangName'];
            }

            // $sql = "SELECT *
            //         FROM `nat_unitlang`
            //         WHERE `Nat_UnitLangNat_UnitID` = ? 
            //         AND `Nat_UnitLangIsActive` = 'Y' 
            //         AND `Nat_UnitLangNat_LangID` = ?
            //         LIMIT 1";
            // $query = $this->db_onedev->query($sql, [$data['natUnitID'], $lang]);
            // if (!$query) {
            //     $this->sys_error_db("Error get lang unit");
            //     exit;
            // }
            // $natUnitLang = $query->row_array();
            // $unitID = '';
            // $unitName = '';
            // if ($data['natUnitID'] != '' && $data['natUnitID'] != null) {
            //     $unitID = $natUnitLang['Nat_UnitLangID'];
            //     $unitName = $natUnitLang['Nat_UnitLangName'];
            // }

            $isEdited = 'N';
            if (trim($data['displayResult'])  != trim($data['aiResult'])) {
                $isEdited = 'Y';
            }

            $sqlCek = "SELECT *
                        FROM t_orderdetaillang
                        WHERE T_OrderDetailLangT_OrderDetailID = ?
                        AND T_OrderDetailLangT_OrderHeaderID = ?
                        AND T_OrderDetailLangNat_LangID = ?
                        AND T_OrderDetailLangIsActive = 'Y';";
            $queryCek = $this->db_onedev->query($sqlCek, [$data['orderDetailID'], $data['orderID'], $lang]);
            if (!$queryCek) {
                $this->sys_error_db("Error cek data");
                exit;
            }
            $dataCek = $queryCek->result_array();
            // print_r($data);
            if (count($dataCek) == 1) {
                //UPDATE
                $sql = "UPDATE t_orderdetaillang
                        SET
                            T_OrderDetailLangIsSI = 'N',
                            T_OrderDetailLangNat_TestLangID = ?,
                            T_OrderDetailLangNat_TestLangName = ?,
                            T_OrderDetailLangNat_UnitLangID = ?,
                            T_OrderDetailLangNat_UnitLangName = ?,
                            T_OrderDetailLangResult = ?,
                            T_OrderDetailLangIsEdited = ?,
                            T_OrderDetailLangNote = ?,
                            T_OrderDetailLangFlag = ?,
                            T_OrderDetailLangNat_NormalValueLangID = ?,
                            T_OrderDetailLangNat_NormalValueLangNote = ?,
                            T_OrderDetailLangLastUpdated = CURRENT_TIMESTAMP,
                            T_OrderDetailLangUserID = ?
                        WHERE T_OrderDetailLangT_OrderDetailID = ?
                        AND T_OrderDetailLangT_OrderHeaderID = ?
                        AND T_OrderDetailLangNat_LangID = ?;";
                $query = $this->db_onedev->query($sql, [
                    $natTestLang['Nat_TestLangID'],
                    $natTestLang['Nat_TestLangName'],
                    $unitID,
                    $unitName,
                    $data['displayResult'],
                    $isEdited,
                    $data['resultNote'],
                    $data['resultFlag'],
                    $data['natNormalvalueID'],
                    $data['normalValueNote'],
                    $userid,
                    $data['orderDetailID'],
                    $data['orderID'],
                    $lang,
                ]);
                if (!$query) {
                    $this->sys_error_db("Error update data");
                    exit;
                }
            } else {
                $sql = "INSERT INTO t_orderdetaillang (
                        T_OrderDetailLangT_OrderHeaderID,
                        T_OrderDetailLangT_OrderDetailID,
                        T_OrderDetailLangNat_LangID,
                        T_OrderDetailLangIsSI,
                        T_OrderDetailLangNat_TestLangID,
                        T_OrderDetailLangNat_TestLangName,
                        T_OrderDetailLangNat_UnitLangID,
                        T_OrderDetailLangNat_UnitLangName,
                        T_OrderDetailLangResult,
                        T_OrderDetailLangIsEdited,
                        T_OrderDetailLangNote,
                        T_OrderDetailLangNat_NormalValueLangID,
                        T_OrderDetailLangNat_NormalValueLangNote,
                        T_OrderDetailLangFlag,
                        T_OrderDetailLangUserID
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )";

                $query = $this->db_onedev->query($sql, [
                    $data['orderID'],
                    $data['orderDetailID'],
                    $lang,
                    'N',
                    $natTestLang['Nat_TestLangID'],
                    $natTestLang['Nat_TestLangName'],
                    $unitID,
                    $unitName,
                    $data['displayResult'],
                    $isEdited,
                    $data['resultNote'],
                    $data['natNormalvalueID'],
                    $data['normalValueNote'],
                    $data['resultFlag'],
                    $userid
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error insert data");
                    exit;
                }
            }
        }
        $jsonAfter = "";
        $sql = "SELECT * FROM t_orderdetaillang WHERE T_OrderDetailLangT_OrderHeaderID = ? AND T_OrderDetailLangNat_LangID = ?";
        $query = $this->db_onedev->query($sql, [$orderid, $lang]);
        if (!$query) {
            $this->sys_error_db("Error log data After");
            exit;
        }
        $rstJsonAfter = $query->result_array();
        $jsonAfter = json_encode($rstJsonAfter);

        $sql = "INSERT INTO cpone_log.log_t_orderdetaillang (
                    Log_T_OrderDetailLangType,
                    Log_T_OrderDetailLangT_OrderHeaderID,
                    Log_T_OrderDetailLangLangID,
                    Log_T_OrderDetailLangPrm,
                    Log_T_OrderDetailLangJsonBefore,
                    Log_T_OrderDetailLangJsonAfter,
                    Log_T_OrderDetailLangUserID,
                    Log_T_OrderDetailLangCreated
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
                );";
        $query = $this->db_onedev->query($sql, [
            $logType,
            $orderid,
            $lang,
            json_encode($prm),
            $jsonBefore,
            $jsonAfter,
            $userid
        ]);
        if (!$query) {
            // echo $this->db_onedev->last_query();
            $this->sys_error_db("Error insert log ");
            exit;
        }
        $this->sys_ok("Success");
    }

    public function saveNonlab()
    {
        $userid = $this->sys_user["M_UserID"];
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $orderid =  $prm['orderid'];
        $userid = $this->sys_user["M_UserID"];
        $lang =  $prm['lang'];
        $nonlab = $prm['nonlab'];
        $sql = "SELECT *
                FROM `m_lang`
                WHERE `M_LangID` = ?
                LIMIT 1";
        $query = $this->db_onedev->query($sql, [$lang]);
        if (!$query) {
            $this->sys_error_db("Error get language");
            exit;
        }
        $language = $query->result_array();
        if (count($language) == 0) {
            $this->sys_error_db("Bahasa tidak ditemukan");
            exit;
        }
        // for ($i = 0; $i < count($nonlab); $i++) {
        //     $data = $nonlab[$i];
        //     # code...
        // }
        $jsonBefore = '';
        $sql = "SELECT *
                FROM `so_resultentrydetail_other`
                WHERE `So_ResultEntryDetailOtherSo_ResultEntryID` = ? AND `So_ResultEntryDetailOtherM_LangID` = ?
                ";
        $query = $this->db_onedev->query($sql, [$nonlab['resultEntryID'], $lang]);
        if (!$query) {
            $this->sys_error_db("Error log data before");
            exit;
        }
        $rstJsonBefore = $query->result_array();
        if (count($rstJsonBefore) == 0) {
            $logType = "ADD";
        } else {
            $logType = "UPDATE";
        }
        $jsonBefore = json_encode($rstJsonBefore);

        for ($i = 0; $i < count($nonlab['detail']); $i++) {
            $detail = $nonlab['detail'][$i];
            $isEdited = 'N';
            if (trim($detail['displayResult'])  != trim($detail['aiResult'])) {
                $isEdited = 'Y';
            }

            $sqlCek = "SELECT *
                        FROM so_resultentrydetail_other
                        WHERE So_ResultEntryDetailOtherSo_ResultEntryID = ?
                        AND So_ResultEntryDetailOtherSo_TemplateDetailID = ?
                        AND So_ResultEntryDetailOtherM_LangID = ?
                        AND So_ResultEntryDetailOtherIsActive = 'Y';";
            $queryCek = $this->db_onedev->query($sqlCek, [$detail['resultEntryID'], $detail['templateDetailLangID'], $lang]);
            if (!$queryCek) {
                $this->sys_error_db("Error cek data");
                exit;
            }
            $dataCek = $queryCek->result_array();
            if (count($dataCek) == 0) {
                $sql = "INSERT INTO so_resultentrydetail_other(
                        So_ResultEntryDetailOtherM_LangID,
                        So_ResultEntryDetailOtherSo_ResultEntryID,
                        So_ResultEntryDetailOtherSo_TemplateDetailID,
                        So_ResultEntryDetailOtherSo_TemplateDetailName,
                        So_ResultEntryDetailOtherSo_TemplateDetailCode,
                        So_ResultEntryDetailOtherIsEdited,
                        So_ResultEntryDetailOtherResult,
                        So_ResultEntryDetailOtherUserID,
                        So_ResultEntryDetailOtherCreated)
                        VALUES(?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $query = $this->db_onedev->query($sql, [
                    $lang,
                    $detail['resultEntryID'],
                    $detail['templateDetailLangID'],
                    $detail['templateDetailLangName'],
                    $detail['templateDetailCode'],
                    $isEdited,
                    $detail['displayResult'],
                    $userid
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error insert data ");
                    exit;
                }
            } else {
                $sql = "UPDATE so_resultentrydetail_other
                        SET So_ResultEntryDetailOtherResult = ?,
                        So_ResultEntryDetailOtherIsEdited = ?,
                        So_ResultEntryDetailOtherLastUpdated = NOW(),
                        So_ResultEntryDetailOtherUserID = ?
                        WHERE So_ResultEntryDetailOtherSo_ResultEntryID = ?
                        AND So_ResultEntryDetailOtherSo_TemplateDetailID = ?
                        AND So_ResultEntryDetailOtherM_LangID = ?";
                $query = $this->db_onedev->query($sql, [
                    $detail['displayResult'],
                    $isEdited,
                    $userid,
                    $detail['resultEntryID'],
                    $detail['templateDetailLangID'],
                    $lang
                ]);
                if (!$query) {
                    echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error update data ");
                    exit;
                }
            }
        }
        $jsonAfter = '';
        $sql = "SELECT *
                FROM `so_resultentrydetail_other`
                WHERE `So_ResultEntryDetailOtherSo_ResultEntryID` = ? AND `So_ResultEntryDetailOtherM_LangID` = ?
                ";
        $query = $this->db_onedev->query($sql, [$nonlab['resultEntryID'], $lang]);
        if (!$query) {
            $this->sys_error_db("Error log data After");
            exit;
        }
        $rstJsonAfter = $query->result_array();

        $jsonAfter = json_encode($rstJsonAfter);

        $sql = "INSERT INTO cpone_log.log_resultentrylang (
                    Log_ResultEntryLangSo_ResultEntryID,
                    Log_ResultEntryLangType,
                    Log_ResultEntryLangLangID,
                    Log_ResultEntryLangPrm,
                    Log_ResultEntryLangJsonBefore,
                    Log_ResultEntryLangJsonAfter,
                    Log_ResultEntryLangUserID,
                    Log_ResultEntryLangCreated
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
                );";
        $query = $this->db_onedev->query($sql, [
            $nonlab['resultEntryID'],
            $logType,
            $lang,
            json_encode($prm),
            $jsonBefore,
            $jsonAfter,
            $userid
        ]);
        if (!$query) {
            $this->sys_error_db("Error insert log data");
            exit;
        }
        $this->sys_ok('Success');
    }
    public function saveFisikUmum()
    {
        $userid = $this->sys_user["M_UserID"];
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $orderid =  $prm['orderid'];
        $userid = $this->sys_user["M_UserID"];
        $lang =  $prm['lang'];
        $fisik = $prm['fisik'];
        $sql = "SELECT *
                FROM `m_lang`
                WHERE `M_LangID` = ?
                LIMIT 1";
        $query = $this->db_onedev->query($sql, [$lang]);
        if (!$query) {
            $this->sys_error_db("Error get language");
            exit;
        }
        $language = $query->result_array();

        $sql = "SELECT 
                So_ResultEntryFisikUmumID,
                so_resultentry_fisik_umum_other.*
                FROM so_resultentry_fisik_umum
                JOIN so_resultentry_fisik_umum_other
                ON So_ResultEntryFisikUmumID = So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID
                AND So_ResultEntryFisikUmumOtherIsActive = 'Y'
                WHERE So_ResultEntryFisikUmumSo_ResultEntryID = ?
                AND So_ResultEntryFisikUmumOtherLangID = ?";
        $query = $this->db_onedev->query($sql, [
            $fisik['resultEntryID'],
            $lang
        ]);
        if (!$query) {
            $this->sys_error_db("Error get data log before");
            exit;
        }
        $jsonBefore = $query->result_array();
        if (count($jsonBefore) == 0) {
            $logType = "ADD";
        } else {
            $logType = "UPDATE";
        }

        foreach ($fisik['detail'] as $key => $value) {
            $segmentName = $value['segment_name'] ?? '';
            // echo $value['segment_name'];
            // if (property_exists($value, 'segment_name')) {
            //     $segmentName = $value['segment_name'];
            // }
            $sql = "SELECT 
                        *
                        FROM so_resultentry_fisik_umum_other
                        WHERE So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID = ?
                        AND So_ResultEntryFisikUmumOtherLangID = ?
                        AND So_ResultEntryFisikUmumOtherTableName = ?
                        AND So_ResultEntryFisikUmumOtherSegment = ?
                        AND So_ResultEntryFisikUmumOtherCode = ?
                        AND So_ResultEntryFisikUmumOtherIsActive = 'Y'";
            $query = $this->db_onedev->query($sql, [
                $value['reFisikUmumID'],
                $lang,
                $value['table_name'],
                $segmentName,
                $value['id_code'],
            ]);
            if (!$query) {
                // echo $this->db_onedev->last_query();
                $this->sys_error_db("Error cek result translate");
                exit;
            }

            $cek = $query->result_array();
            if (count($cek) > 0) {
                $sql = "UPDATE so_resultentry_fisik_umum_other
                        SET 
                            So_ResultEntryFisikUmumOtherTranslate = ?, -- Update teks terjemahan
                            So_ResultEntryFisikUmumOtherLastUpdated = NOW(), -- Update waktu terakhir diperbarui
                            So_ResultEntryFisikUmumOtherLastUpdatedUserID = $userid -- Update ID pengguna yang terakhir memperbarui
                        WHERE 
                            So_ResultEntryFisikUmumOtherID = ?; ";
                $query = $this->db_onedev->query($sql, [
                    $value['displayResult'],
                    $cek[0]['So_ResultEntryFisikUmumOtherID']
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error update translate fisik");
                    exit;
                }
            } else {
                $sql = "INSERT INTO so_resultentry_fisik_umum_other (
                        So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID, 
                        So_ResultEntryFisikUmumOtherLangID, 
                        So_ResultEntryFisikUmumOtherTableName, 
                        So_ResultEntryFisikUmumOtherSegment, 
                        So_ResultEntryFisikUmumOtherLabel, 
                        So_ResultEntryFisikUmumOtherCode, 
                        So_ResultEntryFisikUmumOtherTranslate, 
                        So_ResultEntryFisikUmumOtherCreated, 
                        So_ResultEntryFisikUmumOtherCreatedUserID
                    ) 
                    VALUES (
                        ?, -- So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID
                        ?, -- So_ResultEntryFisikUmumOtherLangID
                        ?, -- So_ResultEntryFisikUmumOtherTableName
                        ?, -- So_ResultEntryFisikUmumOtherSegment
                        ?, -- So_ResultEntryFisikUmumOtherLabel
                        ?, -- So_ResultEntryFisikUmumOtherCode
                        ?, -- So_ResultEntryFisikUmumOtherTranslate
                        NOW(),
                        $userid
                    );";
                $query = $this->db_onedev->query($sql, [
                    $value['reFisikUmumID'],
                    $lang,
                    $value['table_name'],
                    $segmentName,
                    $value['label'],
                    $value['id_code'],
                    $value['displayResult'],
                ]);
                if (!$query) {
                    // echo $this->db_onedev->last_query();
                    $this->sys_error_db("Error insert translate fisik");
                    exit;
                }
            }
        }
        $sql = "SELECT 
                So_ResultEntryFisikUmumID,
                so_resultentry_fisik_umum_other.*
                FROM so_resultentry_fisik_umum
                JOIN so_resultentry_fisik_umum_other
                ON So_ResultEntryFisikUmumID = So_ResultEntryFisikUmumOtherSo_ResultEntryFisikUmumID
                AND So_ResultEntryFisikUmumOtherIsActive = 'Y'
                WHERE So_ResultEntryFisikUmumSo_ResultEntryID = ?
                AND So_ResultEntryFisikUmumOtherLangID = ?";
        $query = $this->db_onedev->query($sql, [
            $fisik['resultEntryID'],
            $lang
        ]);
        if (!$query) {
            $this->sys_error_db("Error get data log before");
            exit;
        }
        $jsonAfter = $query->result_array();
        $sql = "INSERT INTO cpone_log.log_resultentrylang (
                    Log_ResultEntryLangSo_ResultEntryID,
                    Log_ResultEntryLangType,
                    Log_ResultEntryLangLangID,
                    Log_ResultEntryLangPrm,
                    Log_ResultEntryLangJsonBefore,
                    Log_ResultEntryLangJsonAfter,
                    Log_ResultEntryLangUserID,
                    Log_ResultEntryLangCreated
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
                );";
        $query = $this->db_onedev->query($sql, [
            $fisik['resultEntryID'],
            $logType,
            $lang,
            json_encode($prm),
            json_encode($jsonBefore),
            json_encode($jsonAfter),
            $userid
        ]);
        if (!$query) {
            $this->sys_error_db("Error insert log data");
            exit;
        }
        $this->sys_ok("OK");
    }
}
