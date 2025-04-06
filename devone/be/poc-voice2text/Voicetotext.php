<?php
class Voicetotext extends MY_Controller
{

    var $db_scan_ktp;
    var $base_audio = "/home/one/project/one/one-media/voice2text/";

    public function index()
    {
        echo "AUTH API";
    }

    public function __construct()
    {
        parent::__construct();
        $this->db_scan_ktp = $this->load->database([
            'dsn'   => '',
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => 'sasone102938',
            'database' => 'scan_ktp',
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => (ENVIRONMENT !== 'production'),
            'cache_on' => FALSE,
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt' => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => array(),
            'save_queries' => TRUE
        ], TRUE);
    }

    // listRiwayatRekaman
    function listRiwayatRekaman()
    {
        try {
            // if (! $this->isLogin) {
            //     $this->sys_error("Invalid Token");
            //     exit;
            // }

            $prm = $this->sys_input;
            $userId = $prm['userId'];

            $sql_select = "SELECT 
                    Voice2text_ID,
                    IFNULL(Voice2text_Note, '') AS Voice2text_Note,
                    IFNULL(Voice2text_Url, '') AS Voice2text_Url,
                    IFNULL(Voice2text_Text, '') AS Voice2text_Text,
                    Voice2text_User_ID,
                    IFNULL(Voice2text_JsonData, '') AS Voice2text_JsonData,
                    Voice2text_Created,
                    Voice2text_Updated,
                    IFNULL(Voice2text_IsActive, 'Y') AS Voice2text_IsActive
                FROM voice2text
                    WHERE Voice2text_IsActive = 'Y'
                    AND Voice2text_User_ID = '$userId'
                ORDER BY Voice2text_ID DESC
                LIMIT 20";

            $qry = $this->db_scan_ktp->query($sql_select);
            if (!$qry) {
                $error = $this->db_scan_ktp->error();
                echo json_encode([
                    "status" => "Err",
                    "message" => "Error get data voice2text : " . $error['message'],
                    "data" => []
                ]);
            }

            $rows = $qry->result_array();

            if (count($rows) > 0) {
                echo json_encode([
                    "status" => "OK",
                    "message" => "Data Ditemukan",
                    "data" => $rows
                ]);
            } else {
                echo json_encode([
                    "status" => "OK",
                    "message" => "Data Tidak Ada",
                    "data" => []
                ]);
            }
        } catch (Exception $exc) {
            echo json_encode([
                "status" => "Err",
                "message" => "Error : " . $exc->getMessage(),
                "data" => []
            ]);
        }
    }

    // uploadRekaman
    function uploadRekaman()
    {
        $this->db_scan_ktp->trans_begin();
        $prm = $this->sys_input;
        $userId = $prm['userId'];
        $qrCodeStr = $prm['qrCodeStr'];
        $persons = $prm['speakerCounts'] ?? 2;
        $language = $prm['language'] ?? "id"; // ISO Standards

        if (isset($_FILES['audio'])) {
            $fileName = "voice-" . $qrCodeStr . ".mp3";
            // $targetFilePath = $targetDir . $fileName;

            $targetFilePath = "/home/one/project/one/one-media/voice2text/" . $fileName;

            if (move_uploaded_file($_FILES["audio"]["tmp_name"], $targetFilePath)) {
                // Transcript using whisper
                $this->load->library('Ai_whisper');
                $result = $this->ai_whisper->with_diarization($targetFilePath, $persons);

                if ($result['status'] != "OK") {
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Gagal melakukan transkripsi",
                        "error" => $result['message'] ?? "Pesan error tidak ditemukan",
                    ]);
                    exit;
                }

                // Convert Array to JSON
                $transcribedText = json_encode($result['data']['diarized']);
                $transcribedRaw = json_encode($result['data']['raw']);

                // insert sql
                $Voice2text_Note = "";
                $Voice2text_Url = "one-media/voice2text/" . $fileName;
                $Voice2text_Text = $transcribedText;
                $Voice2text_User_ID = $userId;
                $Voice2text_JsonData = $transcribedRaw;
                $Voice2text_IsActive = "Y";

                // * Use Query Binding to prevent SQL Injection
                $sql_insert = "INSERT INTO voice2text (
                    Voice2text_QrCode,
                    Voice2text_Note,
                    Voice2text_Url,
                    Voice2text_Text,
                    Voice2text_User_ID,
                    Voice2text_JsonData,
                    Voice2text_Created,
                    Voice2text_IsActive
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
                $qry_i = $this->db_scan_ktp->query($sql_insert, [
                    $qrCodeStr,
                    $Voice2text_Note,
                    $Voice2text_Url,
                    $Voice2text_Text,
                    $Voice2text_User_ID,
                    $Voice2text_JsonData,
                    $Voice2text_IsActive
                ]);

                if (!$qry_i) {
                    $this->db_scan_ktp->trans_rollback();
                    $error = $this->db_scan_ktp->error();
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Error insert data voice2text : " . $error['message'],
                        "data" => []
                    ]);
                }

                $Voice2text_ID = $this->db_scan_ktp->insert_id();
                $this->db_scan_ktp->trans_commit();

                $sql_select = "SELECT 
                        Voice2text_ID,
                        IFNULL(Voice2text_Note, '') AS Voice2text_Note,
                        IFNULL(Voice2text_Url, '') AS Voice2text_Url,
                        IFNULL(Voice2text_Text, '') AS Voice2text_Text,
                        Voice2text_User_ID,
                        IFNULL(Voice2text_JsonData, '') AS Voice2text_JsonData,
                        Voice2text_Created,
                        Voice2text_Updated,
                        IFNULL(Voice2text_IsActive, 'Y') AS Voice2text_IsActive
                    FROM voice2text
                        WHERE Voice2text_IsActive = 'Y'
                        AND Voice2text_User_ID = '$userId'
                        AND Voice2text_ID = '$Voice2text_ID'
                    ORDER BY Voice2text_ID DESC";

                $qry = $this->db_scan_ktp->query($sql_select);
                if (!$qry) {
                    $this->db_scan_ktp->trans_rollback();
                    $error = $this->db_scan_ktp->error();
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Error get data voice2text : " . $error['message'],
                        "data" => []
                    ]);
                }

                $rows = $qry->result_array();
                echo json_encode([
                    "status" => "OK",
                    "message" => "Upload sukses!",
                    "data" => $rows,
                ]);
            } else {
                echo json_encode([
                    "status" => "OK",
                    "message" => "Gagal mengunggah file",
                    "data" => [],
                ]);
            }
        } else {
            echo json_encode([
                "status" => "OK",
                "message" => "File tidak ditemukan",
                "data" => [],
            ]);
        }
    }

    // editRekaman
    function editRekaman()
    {
        $this->db_scan_ktp->trans_begin();
        $prm = $this->sys_input;
        $Voice2text_ID = $prm['Voice2text_ID'];
        $Voice2text_Note = $prm['Voice2text_Note'];
        $Voice2text_Text = $prm['Voice2text_Text'];

        $sql_u = "UPDATE voice2text SET 
                Voice2text_Note = '$Voice2text_Note',
                Voice2text_Text = '$Voice2text_Text',
                Voice2text_Updated = NOW()
            WHERE Voice2text_ID = '$Voice2text_ID'
        ";

        $qry_u = $this->db_scan_ktp->query($sql_u);
        if (!$qry_u) {
            $this->db_scan_ktp->trans_rollback();
            $error = $this->db_scan_ktp->error();
            echo json_encode([
                "status" => "Err",
                "message" => "Error update data voice2text : " . $error['message'],
                "data" => []
            ]);
        }

        $this->db_scan_ktp->trans_commit();

        echo json_encode([
            "status" => "OK",
            "message" => "Sukses Edit data",
            "data" => []
        ]);
    }

    // Check Transcript based on QR Code
    function cekTranskrip($qrcode)
    {
        $sql_select = "SELECT Voice2text_QrCode, Voice2text_Url, 
            Voice2text_JsonData 
        FROM voice2text 
        WHERE Voice2text_QrCode = ?";

        $qry = $this->db_scan_ktp->query($sql_select, [$qrcode]);
        if (!$qry) {
            $error = $this->db_scan_ktp->error();
            echo json_encode([
                "status" => "Err",
                "message" => "Error get data voice2text : " . $error['message'],
                "data" => []
            ]);
        }

        $rst = $qry->result_array();
        if (count($rst) > 0) {
            echo json_encode([
                "status" => "OK",
                "message" => "Data Ditemukan",
                "data" => $rst
            ]);
        } else {
            echo json_encode([
                "status" => "OK",
                "message" => "Data Tidak Ada",
                "data" => []
            ]);
        }
    }
}
