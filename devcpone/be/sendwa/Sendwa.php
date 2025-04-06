<?php
class Sendwa extends MY_Controller
{
    var $db_onedev;
    var $load;
    var $sspricemou;
    var $hostname;

    public function index()
    {
        echo "CPONE SENDWA API";
    }

    public function __construct()
    {
        parent::__construct();
        $this->db_onedev = $this->load->database("onedev", true);
        // $this->hostname = 'cpone.aplikasi.web.id';
        $this->hostname = 'devcpone.aplikasi.web.id';
        // $this->load->library("SsPriceMou");
    }
    function getsetup()
    {
        try {
            // if (!$this->isLogin) {
            // 	$this->sys_error("Invalid Token");
            // 	exit;
            // }
            $sql = "SELECT * 
					FROM mgm_mcu 
					WHERE Mgm_McuIsActive = 'Y'
					ORDER BY Mgm_McuStartDate ASC, Mgm_McuEndDate ASC";
            $qry = $this->db_onedev->query($sql, []);
            $last_qry = $this->db_onedev->last_query();
            if (!$qry) {
                $message = $this->db_onedev->error();
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
                count(T_ResultHandoverID) as total
                FROM 
                t_resulthandover
                LEFT JOIN m_user ON T_ResultHandoverUserID= M_UserID
                LEFT JOIN m_staff ON M_UserM_StaffID = M_StaffID 
                WHERE T_ResultHandoverIsActive = 'Y'
                AND T_ResultHandoverDate BETWEEN ? AND ?
                AND T_ResultHandoverMgm_McuID =? ";
        $query = $this->db_onedev->query($sql, [$startDate, $endDate, $setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $total = $query->row_array()['total'];
        $sql = "SELECT 
                T_ResultHandoverID as handoverID,
                T_ResultHandoverCode as handoverCode,
                T_ResultHandoverReceivedBy as handoverReceivedBy,
                T_ResultHandoverNote as handovernote,
                DATE_FORMAT(T_ResultHandoverDate, '%d-%m-%Y %H:%i') as handoverDate,
                T_ResultHandoverMgm_McuID as hanoverSetupID,
                M_StaffName as handoverStaff
                FROM 
                t_resulthandover
                LEFT JOIN m_user ON T_ResultHandoverUserID= M_UserID
                LEFT JOIN m_staff ON M_UserM_StaffID = M_StaffID 
                WHERE T_ResultHandoverIsActive = 'Y'
                AND T_ResultHandoverDate BETWEEN ? AND ?
                AND T_ResultHandoverMgm_McuID = ? 
                LIMIT ? OFFSET ? ";
        $query = $this->db_onedev->query($sql, [$startDate, $endDate, $setupID, $ROW_PER_PAGE, $start_offset]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $result = [
            "total" => ceil($total / $ROW_PER_PAGE),
            "records" => $query->result_array()
        ];
        $this->sys_ok($result);
    }

    function getdetail()
    {
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }
        $prm = $this->sys_input;
        $page = $prm["page"];
        $startDate = $prm["startDate"];
        $endDate = $prm["endDate"];
        $setupID = $prm["setupID"];
        $handoverID = $prm["handoverID"];
        $search = '%' . $prm['search'] . '%';
        $status = $prm["status"];
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
        $whereStatus = "";
        if ($status == "Y") {
            $whereStatus = "AND XWaOutboxID IS NOT NULL AND XWaOutboxIsSent = 'Y'";
        }
        if ($status == "NO") {
            $whereStatus = "AND (XWaOutboxID IS NULL)";
        }
        if ($status == "N") {
            //prosess
            $whereStatus = "AND (XWaOutboxID IS NOT NULL AND (XWaOutboxIsSent = 'N' || XWaOutboxIsSent = 'R'))";
        }
        if ($status == "E") {
            $whereStatus = "AND (XWaOutboxID IS NOT NULL AND XWaOutboxIsSent = 'E')";
        }

        /* Query Total Semua Pesan di Outbox */
        $sql = "SELECT 
                COUNT(T_OrderHeaderID) as totalAll
                FROM t_orderheader
                LEFT JOIN x_wa_outbox
                ON T_OrderHeaderID = XWaOutboxRefID 
                AND XWaOutboxIsActive = 'Y'
                AND XWaOutboxType = 'RESULT'
                JOIN m_patient 
                ON T_OrderHeaderM_PatientID = M_PatientID
                LEFT JOIN m_title 
                ON M_PatientM_TitleID = M_TitleID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                AND T_OrderHeaderMgm_McuID = ?
                ";
        $query = $this->db_onedev->query($sql, [$setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $totalALL = $query->row_array()['totalAll'];
        // $this->sys_ok($totalALL);

        /* Query Total Pesan Terkirim */
        $sql = "SELECT 
                COUNT(T_OrderHeaderID) as totalSend
                FROM t_orderheader
                LEFT JOIN x_wa_outbox
                ON T_OrderHeaderID = XWaOutboxRefID 
                AND XWaOutboxIsActive = 'Y'
                AND XWaOutboxType = 'RESULT'
                JOIN m_patient 
                ON T_OrderHeaderM_PatientID = M_PatientID
                LEFT JOIN m_title 
                ON M_PatientM_TitleID = M_TitleID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                AND XWaOutboxID IS NOT NULL 
                AND XWaOutboxIsSent = 'Y'
                AND T_OrderHeaderMgm_McuID = ?
                ";
        $query = $this->db_onedev->query($sql, [$setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $totalSend = $query->row_array()['totalSend'];
        // $this->sys_ok($totalSend);

        // /* Query Total Pesan yg Error */
        $sql = "SELECT 
                COUNT(T_OrderHeaderID) as totalError
                FROM t_orderheader
                LEFT JOIN x_wa_outbox
                ON T_OrderHeaderID = XWaOutboxRefID 
                AND XWaOutboxIsActive = 'Y'
                AND XWaOutboxType = 'RESULT'
                JOIN m_patient 
                ON T_OrderHeaderM_PatientID = M_PatientID
                LEFT JOIN m_title 
                ON M_PatientM_TitleID = M_TitleID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                AND XWaOutboxID IS NOT NULL 
                AND XWaOutboxIsSent = 'E'
                AND T_OrderHeaderMgm_McuID = ?
                ";
        $query = $this->db_onedev->query($sql, [$setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $totalError = $query->row_array()['totalError'];
        // $this->sys_ok($totalError);

        // /* Query Total Pesan Belum Terkirim */
        $sql = "SELECT 
                COUNT(T_OrderHeaderID) as totalNotSend
                FROM t_orderheader
                LEFT JOIN x_wa_outbox
                ON T_OrderHeaderID = XWaOutboxRefID 
                AND XWaOutboxIsActive = 'Y'
                AND XWaOutboxType = 'RESULT'
                JOIN m_patient 
                ON T_OrderHeaderM_PatientID = M_PatientID
                LEFT JOIN m_title 
                ON M_PatientM_TitleID = M_TitleID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                AND (XWaOutboxID IS NULL OR XWaOutboxIsSent = 'N')
                AND T_OrderHeaderMgm_McuID = ?
                ";
        $query = $this->db_onedev->query($sql, [$setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $totalNotSend = $query->row_array()['totalNotSend'];
        // $this->sys_ok($totalNotSend);

        // /* Query Semua Pesan yang Berhasil Terkirim */
        $sql = "SELECT 
                COUNT(T_OrderHeaderID) as total
                FROM t_orderheader
                LEFT JOIN x_wa_outbox
                ON T_OrderHeaderID = XWaOutboxRefID 
                AND XWaOutboxIsActive = 'Y'
                AND XWaOutboxType = 'RESULT'
                JOIN m_patient 
                ON T_OrderHeaderM_PatientID = M_PatientID
                LEFT JOIN m_title 
                ON M_PatientM_TitleID = M_TitleID
                LEFT JOIN corporate ON T_OrderHeaderCorporateID= CorporateID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                {$whereStatus}
                AND DATE(T_OrderHeaderDate) BETWEEN ? AND ?
                AND (M_PatientName LIKE ? OR T_OrderHeaderLabNumber LIKE ?)
                AND T_OrderHeaderMgm_McuID = ?
                ";
        $query = $this->db_onedev->query($sql, [$startDate, $endDate, $search, $search, $setupID]);
        if (!$query) {
            $message = $this->db_onedev->error()['message'];
            $this->sys_error("Error Search");
            exit;
        }
        $total = $query->row_array()['total'];
        // $this->sys_ok($total);

        /* Query Data Yang Ditampilkan di UI */
        $sql = "SELECT 
                T_OrderHeaderID as orderID,
                T_OrderHeaderLabNumber as orderNumber,
                DATE_FORMAT(T_OrderHeaderDate, '%d-%m-%Y') as orderDate,
                T_OrderHeaderM_PatientID as patientID,
                DATE_FORMAT(M_PatientDOB, '%d%m%Y') as patientDOB,
                M_PatientDOB,
                CONCAT(IF(ISNULL(M_TitleName),'',CONCAT(M_TitleName,'.')),
                        ' ',
                        IFNULL(M_PatientPrefix,''),
                        ' ',
                        M_PatientName,
                        ' ',
                        IFNULL(M_PatientSuffix,''))  as patientName,
                M_PatientNIP,
                M_PatientName as plainName,
                M_PatientHp as patientHp,
                M_PatientHp as patientHpOld,
                XWaOutboxID as sendWaID,
                CorporateName,
                XWaOutboxIsSent,
                IFNULL(XWaOutboxRetry , 0) as retry,
                DATE_FORMAT(XWaOutboxSentDate, '%d-%m-%Y %H:%i') as sentDate,
                XWaOutboxType as sendEmailType,
                XWaOutboxResultFilename,
                XWaOutboxLocalUrl,
                XWaOutboxCdnUrl,
                XWaOutboxJsonQontak,
                -- Kalau sudah ada maka pakai status yg ada, kalau belum maka NO
                CASE 
                    WHEN XWaOutboxID IS NULL THEN 'NO'
                    WHEN XWaOutboxID IS NOT NULL THEN XWaOutboxIsSent
                END as status
                FROM t_orderheader
                    LEFT JOIN x_wa_outbox
                    ON T_OrderHeaderID = XWaOutboxRefID 
                    AND XWaOutboxIsActive = 'Y'
                    AND XWaOutboxType = 'RESULT'
                    JOIN m_patient 
                    ON T_OrderHeaderM_PatientID = M_PatientID
                    LEFT JOIN m_title 
                    ON M_PatientM_TitleID = M_TitleID
                    LEFT JOIN corporate ON T_OrderHeaderCorporateID = CorporateID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                {$whereStatus}
                AND DATE(T_OrderHeaderDate) BETWEEN ? AND ?
                AND (M_PatientName LIKE ? OR T_OrderHeaderLabNumber LIKE ?)
                AND T_OrderHeaderMgm_McuID = ?
                GROUP BY T_OrderHeaderID
                LIMIT ? OFFSET ? 
                ";
        $query = $this->db_onedev->query($sql, [$startDate, $endDate, $search, $search, $setupID, $ROW_PER_PAGE, $start_offset]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error("Error search data pasien");
            exit;
        }
        // $this->sys_ok($query->result_array());

        $result = [
            "total" => ceil($total / $ROW_PER_PAGE),
            "totalAll" => $totalALL,
            "totalSend" => $totalSend,
            "totalNotSend" => $totalNotSend,
            "totalError" => $totalError,
            "records" => $query->result_array()
        ];
        $this->sys_ok($result);
    }

    /* 
        Untuk menyimpan data pesan yang akan dikirim.
        Hanya sampai menyimpan di x_wa_outbox, tidak sampai mengirim WA dengan Qontak
     */
    function sendMsg()
    {
        $this->db_onedev->trans_begin();
        if (!$this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
        }

        $prm = $this->sys_input;
        $dataPayload = $prm["data"];
        $setupID = $prm["setupID"];
        $userid = $this->sys_user["M_UserID"];

        $orderID = $dataPayload[0]['orderID'];
        $orderNumber = $dataPayload[0]['orderNumber'];
        $orderDate = $dataPayload[0]['orderDate'];
        $patientID = $dataPayload[0]['patientID'];
        $patientDOB = $dataPayload[0]['patientDOB'];
        $patientName = $dataPayload[0]["patientName"];
        $patientHp = $dataPayload[0]["patientHp"];
        $patientHpOld = $dataPayload[0]["patientHpOld"];
        $corpName = $dataPayload[0]["CorporateName"];
        $orderDate = $dataPayload[0]["orderDate"];
        $retry = $dataPayload[0]["retry"];
        $sendWaID = $dataPayload[0]["sendWaID"];
        $status = $dataPayload[0]["status"];
        $MPatientNIP = $dataPayload[0]["M_PatientNIP"];
        $plainName = $dataPayload[0]["plainName"];

        /* Jika tidak ada payload Req */
        if (count($dataPayload) == 0) {
            $this->sys_error("Tidak ada yang bisa dikirimkan ");
            exit;
        }

        // /* Apakah perlu config WA? */
        $sql = "SELECT * FROM m_emailconfig 
                WHERE M_EmailConfigType = 'R'";
        $query = $this->db_onedev->query($sql, []);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->db_onedev->trans_rollback();
            $this->sys_error("Error get config");
            exit;
        }
        $config = $query->row_array();
        $msg = $config['M_EmailConfigResultFormatAPS']; // Template Msg
        $msg = str_replace('{PASIEN}', $patientName, $msg);
        $msg = str_replace('{TANGGAL}', $orderDate, $msg);

        /* Query Mgm MCU Id based on setupID */
        $sql = "SELECT `Mgm_McuReportHasil`, `Mgm_McuID` FROM `mgm_mcu` WHERE `Mgm_McuID` = ?";
        $query = $this->db_onedev->query($sql, [$setupID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->db_onedev->trans_rollback();
            $this->sys_error("Error get config");
            exit;
        }
        $reportType = $query->row_array()['Mgm_McuReportHasil'];


        /* MAIN ACTION */
        /* Update Nomor HP Patient kalau diubah pas Input */
        if ($patientHp != $patientHpOld || $patientHpOld == null || $patientHpOld == "") {
            $sql = "UPDATE m_patient
                    SET  M_PatientHp = ?,
                    M_PatientLastUpdatedUserID = {$userid},
                    M_PatientLastUpdated = NOW()
                    WHERE M_PatientID = ?
                    ";
            $query = $this->db_onedev->query($sql, [$patientHp, $patientID]);
            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->db_onedev->trans_rollback();
                $this->sys_error("Error update patient");
                exit;
            }
        }
        /* 
            Jika nomor HP sama 
        */
        /* Cek Outbox */
        $sql = "SELECT XWaOutboxID
                    FROM x_wa_outbox
                    WHERE XWaOutboxRefID = ?
                    AND XWaOutboxIsActive = 'Y'
                    AND XWaOutboxType = 'RESULT'";
        $query = $this->db_onedev->query($sql, [$orderID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->db_onedev->trans_rollback();
            $this->sys_error("Error cek outbox");
            exit;
        }
        $dataCek = $query->num_rows();

        // Cek MCU 2 Tahunan atau Tidak
        $sql = "SELECT * from mgm_mcuduatahunan where Mgm_McuDuaTahunanMgm_McuID = ? AND Mgm_McuDuaTahunanIsActive = 'Y'";
        $query = $this->db_onedev->query($sql, [$setupID]);

        if ($query->num_rows() < 1) {
            // Mgm MCU bukan 2 tahunan
            $pathDir = "/one-api/tools/listrptpatientportal/get_list_patient_rpt_email/" .  $orderID . "/" . $patientDOB;
        } else {
            // Mgm MCU termasuk 2 tahunan
            $pathDir = "/one-api/tools/listrptpatienttahunanportal/get_list_patient_rpt_email/" . $orderID . "/" . $patientDOB;
        }

        /* Invalid char Qontak: !&$@=;\/:+, ?%[]<>\\~^*#|?()"' */
        /* Invalid Char Qontak: (!&$@=;\/:+, ?%[]<>\\~^*#|?() */
        $invalidChars = array('!', '&', '$', '@', '=', ';', '/', ':', '+', ',', ' ', '?', '%', '[', ']', '<', '>', '\\', '~', '^', '*', '#', '|', '(', ')', '?', "'", '"');
        $fileName = str_replace($invalidChars, '_', $MPatientNIP) . "_" . str_replace($invalidChars, "_", $plainName) . ".pdf";
        $localUrl = "http://" . $_SERVER['SERVER_NAME'] . $pathDir;

        /* Jika Belum ada di outbox, maka insert 
            Jika sudah maka update*/
        if ($dataCek == 0) {
            $sql = "INSERT INTO x_wa_outbox(
                        XWaOutboxSubject,
                        XWaOutboxRecipientsNumber,
                        XWaOutboxRecipientsM_PatientID,
                        XWaOutboxResultFilename,
                        XWaOutboxResultDate,
                        XWaOutboxLocalUrl,
                        XWaOutboxBody,
                        XWaOutboxType,
                        XWaOutboxRefID,
                        XWaOutboxCreated)
                        VALUES(?,?,?,?,?,?,?,?,?,NOW())";
            $query = $this->db_onedev->query($sql, [
                'Hasil WA',
                $patientHp,
                $patientID,
                $fileName,
                $orderDate,
                $localUrl,
                $msg,
                'RESULT',
                $orderID
            ]);
            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->db_onedev->trans_rollback();
                $this->sys_error($message);
                // $this->sys_error("Error insert outbox");
                exit;
            }
        } else {
            if ($status == 'Y' || ($status == 'E' && intval($retry) >= 5)) {
                $sql = "UPDATE x_wa_outbox SET 
                        XWaOutboxRecipientsNumber = ?,
                        XWaOutboxRecipientsM_PatientID = ?,
                        XWaOutboxIsSent = 'R',
                        XWaOutboxRetry = 0,
                        XWaOutboxCdnUrl = NULL,
                        XWaOutboxLocalUrl = ?,
                        XWaOutboxResultFilename = ?,
                        XWaOutboxLastUpdated = NOW()
                        WHERE 
                        XWaOutboxID = ?
                        ";
                $query = $this->db_onedev->query($sql, [
                    $patientHp,
                    $patientID,
                    $localUrl,
                    $fileName,
                    $sendWaID
                ]);
                if (!$query) {
                    $message = $this->db_onedev->error();
                    $message['qry'] = $this->db_onedev->last_query();
                    $this->db_onedev->trans_rollback();
                    $this->sys_error($message);
                    exit;
                }
            }
        }
        $this->db_onedev->trans_commit();
        $this->sys_ok('OK');
    }

    /* Untuk list data yang diambil gateway */
    function listoutbox()
    {
        $prm = $this->sys_input;
        $status = $prm["statusOutbox"];
        $startDate = $prm["startDate"];
        $endDate = $prm["endDate"];

        $query = "SELECT 
                T_OrderHeaderID as orderID,
                T_OrderHeaderLabNumber as orderNumber,
                DATE_FORMAT(T_OrderHeaderDate, '%d-%m-%Y') as orderDate,
                T_OrderHeaderM_PatientID as patientID,
                DATE_FORMAT(M_PatientDOB, '%d%m%Y') as patientDOB,
                M_PatientDOB,
                CONCAT(IF(ISNULL(M_TitleName),'',CONCAT(M_TitleName,'.')),
                        ' ',
                        IFNULL(M_PatientPrefix,''),
                        ' ',
                        M_PatientName,
                        ' ',
                        IFNULL(M_PatientSuffix,''))  as patientName,
                M_PatientHp as patientHp,
                M_PatientHp as patientHpOld,
                CorporateName,
                XWaOutboxID as sendWaID,
                XWaOutboxIsSent,
                IFNULL(XWaOutboxRetry , 0) as XWaOutboxIsRetry,
                XWaOutboxCdnUrl as fileUrl,
                XWaOutboxLocalUrl as localUrl,
                XWaOutboxResultFilename as fileName,
                DATE_FORMAT(XWaOutboxSentDate, '%d-%m-%Y %H:%i') as sentDate,
                XWaOutboxType as sentType
                FROM t_orderheader
                    JOIN x_wa_outbox
                    ON T_OrderHeaderID = XWaOutboxRefID 
                        AND XWaOutboxID IS NOT NULL
                        AND XWaOutboxIsSent = ?
                        AND XWaOutboxIsActive = 'Y'
                        AND XWaOutboxType = 'RESULT'
                    JOIN m_patient 
                        ON T_OrderHeaderM_PatientID = M_PatientID
                    JOIN m_title 
                        ON M_PatientM_TitleID = M_TitleID
                    JOIN corporate ON T_OrderHeaderCorporateID = CorporateID
                WHERE 
                T_OrderHeaderIsActive = 'Y'
                AND DATE(T_OrderHeaderDate) BETWEEN ? AND ?
        ";
        $query = $this->db_onedev->query($query, [$status, $startDate, $endDate]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error("Error get outbox");
            exit;
        }
        $result = $query->result_array();
        $this->sys_ok($result);
    }

    /* Up file pdf dari local ke CDN agar bisa di-WA */
    function uploadfile()
    {
        try {
            $url = "https://service-chat.qontak.com/api/open/v1/file_uploader";
            $fileName = $this->sys_input["fileName"];
            $rpt_url = $this->sys_input["rptUrl"];
            $mimeType = $this->sys_input["mime"]; //application/pdf

            $fileContents = file_get_contents($rpt_url);

            if ($fileContents === false || strlen($fileContents) === 0) {
                // Return an error or handle it as needed
                $resp = "Error: Gagal upload file ke CDN karena file local kosong atau tidak bisa diakses. Cek file di URL File Local: " . $rpt_url;
                $sql = "UPDATE x_wa_outbox SET 
                    XWaOutboxLastUpdated = NOW(),
                    XWaOutboxJsonQontak = ?
                    WHERE 
                    XWaOutboxLocalUrl = ?
                    ";
                $query = $this->db_onedev->query($sql, [$resp, $rpt_url]);
                if (!$query) {
                    $message = $this->db_onedev->error();
                    $message['qry'] = $this->db_onedev->last_query();
                    $this->sys_error([
                        "msg" => "Error change JSONQontak when upload file",
                        "error" => $message
                    ]);
                    exit;
                }
                $this->sys_error($resp);
                exit;
            }

            $boundary = uniqid();

            $body = "--$boundary\r\n" .
                "Content-Disposition: form-data; name=\"file\"; filename=\"$fileName\"\r\n" .
                "Content-Type: $mimeType\r\n\r\n" .
                $fileContents . "\r\n" .
                "--$boundary--\r\n";

            $query = "
            SELECT * FROM x_qontak_api
            ORDER BY XQontakApiLastUpdated DESC LIMIT 1";
            $configwa = $this->db_onedev->query($query)->result_array();
            $token = $configwa[0]["XQontakApiToken"];

            // Set cURL options
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$token}",
                    "Content-Type: multipart/form-data; boundary=$boundary"
                ],
                CURLOPT_POSTFIELDS => $body
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);
            $respArray = json_decode($response, true);

            if ($respArray['status'] == "success") {
                $respArray = json_decode($response, true);
                // Check if decoding was successful and access the "url"
                if (isset($respArray['data']['url'])) {
                    $url = $respArray['data']['url'];
                    // echo "URL: " . $url;

                    $sql = "UPDATE x_wa_outbox SET 
                    XWaOutboxCdnUrl = ?,
                    XWaOutboxLastUpdated = NOW()
                    WHERE 
                    XWaOutboxID = ?
                    ";
                    $query = $this->db_onedev->query($sql, [$url, $this->sys_input["XWaOutboxID"]]);
                    if (!$query) {
                        $message = $this->db_onedev->error();
                        $this->sys_error("Error update CDN URL outbox");
                        exit;
                    }
                    $this->sys_ok([
                        "msg" => "Berhasil upload file dan update CDN",
                        "url" => $url
                    ]);
                } else {
                    $this->sys_error("URL not found in response.");
                }
            }
            if ($error) {
                echo json_encode(["status" => "ERR", "message" => $error]);
                $this->sys_error($error);
                exit;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /* Ditrigger dari gateway */
    function QontakSendMsg()
    {
        $url = "https://service-chat.qontak.com/api/open/v1/broadcasts/whatsapp/direct";
        $query = "
            SELECT * FROM x_qontak_api
            ORDER BY XQontakApiLastUpdated DESC LIMIT 1";
        $configwa = $this->db_onedev->query($query)->result_array();
        $token = $configwa[0]["XQontakApiToken"];
        $wa_integration_id = $configwa[0]["XQontakApiWaIntegrationID"];
        $template_id = $configwa[0]["XQontakApiTemplateID"];


        $prm = $this->sys_input;
        $orderID = $prm["orderID"];
        $orderDate = $prm["orderDate"];
        $patientName = $prm["patientName"];
        $patientHp = $prm["patientHp"];
        if (substr($patientHp, 0, 1) === "0") {
            $patientHp = "62" . substr($patientHp, 1);
        }
        $corpName = $prm["corpName"];
        $fileName = $prm["fileName"];

        $statusOutbox = $prm["statusOutbox"];
        $retryOutbox = $prm["retryOutbox"];
        $outboxID = $prm["sendWaID"];
        // $uploaded_url_doc = $prm['fileUrl'];

        /* Ambil CDN Url */
        $sql = "SELECT XWaOutboxCdnUrl as fileUrl FROM x_wa_outbox WHERE XWaOutboxID = ?";
        $query = $this->db_onedev->query($sql, [$outboxID]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error("Error get cdn file url");
            exit;
        }
        $uploaded_url_doc = $query->row_array()['fileUrl'];

        // Kirim WA
        $param = [
            "to_name" => $patientName,
            "to_number" => $patientHp,
            "message_template_id" => $template_id,
            "channel_integration_id" => $wa_integration_id,
            "language" => [
                "code" => "id"
            ],
            "parameters" => [
                "header" => [
                    "format" => "DOCUMENT",
                    "params" => [
                        [
                            "key" => "url",
                            "value" => $uploaded_url_doc
                        ],
                        [
                            "key" => "filename",
                            "value" => $fileName
                        ]
                    ]
                ],
                "body" => [
                    [
                        "key" => 1,
                        "value" => "nama",
                        "value_text" => $patientName
                    ],
                    [
                        "key" => 2,
                        "value" => "tipe",
                        "value_text" => $patientName
                    ],
                    [
                        "key" => 3,
                        "value" => "berlaku",
                        "value_text" => $orderDate
                    ],
                ]
            ]
        ];
        $json_param = json_encode($param);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json_param,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        $respArray = json_decode($response, true);

        if ($respArray['status'] == "success") {
            $sql = "UPDATE x_wa_outbox SET 
                    XWaOutboxIsSent = 'Y',
                    XWaOutboxRetry = 0,
                    XWaOutboxSentDate = NOW(),
                    XWaOutboxLastUpdated = NOW(),
                    XWaOutboxJsonQontak = ?
                    WHERE 
                    XWaOutboxID = ?
                    ";
            $query = $this->db_onedev->query($sql, [json_encode($respArray), $outboxID]);
            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->sys_error([
                    "msg" => "Error update outbox",
                    "error" => $message
                ]);
                exit;
            }
            $this->sys_ok("Berhasil kirim wa dan update outbox");
            // $this->sys_ok(json_encode($respArray));
            exit;
        } else {
            $sql = "UPDATE x_wa_outbox SET 
                    XWaOutboxIsSent = 'E',
                    XWaOutboxRetry = ?,
                    XWaOutboxSentDate = NOW(),
                    XWaOutboxLastUpdated = NOW(),
                    XWaOutboxJsonQontak = ?
                    WHERE 
                    XWaOutboxID = ?
                    ";
            $query = $this->db_onedev->query($sql, [$retryOutbox, json_encode($respArray), $outboxID]);
            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->sys_error([
                    "msg" => "Error update outbox",
                    "error" => $message
                ]);
                exit;
            }
            $this->sys_error($respArray);
        }
    }

    function changeStatusOutbox()
    {
        $this->db_onedev->trans_begin();

        $prm = $this->sys_input;

        $sql = "UPDATE x_wa_outbox SET 
                XWaOutboxIsSent = ?,
                XWaOutboxRetry = ?,
                XWaOutboxLastUpdated = NOW()
                WHERE 
                XWaOutboxID = ?
                ";
        $query = $this->db_onedev->query($sql, [$prm["toStatus"], $prm["retry"], $prm["XWaOutboxID"]]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
        } else {
            $this->db_onedev->trans_commit();
            $this->sys_ok("Berhasil update status outbox");
        }
    }
}
