<?php
class Scanktp extends MY_Controller {

    var $db_scan_ktp;
    var $base_img = "/home/one/project/one/one-media/scan-ktp/";

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
    
    

    // listRiwayatScan
    function listRiwayatScan()
    {
        try {
            // if (! $this->isLogin) {
            //     $this->sys_error("Invalid Token");
            //     exit;
            // }
            
            $prm = $this->sys_input;
            $userId = $prm['userId'];

            $sql_select = "SELECT 
                Person_ID,
                IFNULL(Person_NIK, '') as Person_NIK,
                IFNULL(Person_Name, '') as Person_Name,
                IFNULL(Person_Dob, '') as Person_Dob,
                IFNULL(Person_Sex, '') as Person_Sex,
                IFNULL(Person_Url, '') as Person_Url,
                IFNULL(m_sexname, '') as m_sexname
                FROM person
                    join m_sex
                    ON Person_Sex = M_SexID
                    AND M_SexIsActive = 'Y'
                    AND Person_IsActive = 'Y'
                    AND Person_UserID = '$userId'
                ORDER BY Person_ID desc
                LIMIT 20";
            
            $qry = $this->db_scan_ktp->query($sql_select);
            if (!$qry) {
                $error = $this->db_scan_ktp->error(); 
                echo json_encode([
                    "status" => "Err",
                    "message" => "Error get data person : " . $error['message'],
                    "data" => [] 
                ]);
            }

            $rows = $qry->result_array();

            if(count($rows) > 0) {
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
                "message" => "Error : ".$exc->getMessage(),
                "data" => [] 
            ]);
        }
    }

    // getSex
    public function getSex()
    {
        $sql_j = "SELECT * FROM m_sex where M_SexIsActive = 'Y'";

        $qry_j = $this->db_scan_ktp->query($sql_j);
        if(!$qry_j){
            $error = $this->db_scan_ktp->error(); 
            echo json_encode([
                "status" => "Err",
                "message" => "Error get data jenis kelamin : " . $error['message'],
                "data" => [] 
            ]);
        }

        $rows_j = $qry_j->result_array();

        if(count($rows_j) > 0){
            echo json_encode([
                "status" => "OK",
                "message" => "Data ditemukan",
                "data" => $rows_j
            ]);
        }

        else{
            echo json_encode([
                "status" => "OK",
                "message" => "Data tidak ada",
                "data" => [[
                    "M_SexID"=> "0",
                    "M_SexCode"=> "",
                    "m_sexname"=> "Data Belum Ada",
                    "M_SexNameLang"=> "",
                    "M_SexCreated"=> "",
                    "M_SexLastUpdated"=> "",
                    "M_SexIsActive"=> "N",
                ]]
            ]);
        }
    }

    // proses_scan
    function proses_scan()
    {
        $this->db_scan_ktp->trans_begin();
        //$this->load->library('Ocr_llama');
        $this->load->library('Ocr_alibaba');
        $prm = $this->sys_input;
        $fileDataBase64 = $prm['base64File'];
        $userId = $prm['userId'];
        $newFilename = "";

        if (!empty($fileDataBase64)) {
            try {
                // Hapus prefix Base64
                // $fileDataBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $fileDataBase64);
                $file = base64_decode($fileDataBase64);

                if ($file === false) {
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Base64 decode gagal",
                        "data" => []
                    ]);
                    exit;
                }
                
                if (strlen($file) < 100) {
                    echo json_encode([
                        "status" => "Err",
                        "message" => "File hasil decode terlalu kecil",
                        "data" => []
                    ]);
                    exit;
                }

                $date = date('YmdHis');
                $newFilename = "ktp-" . $date . ".jpg";
                $filePath = "/home/one/project/one/one-media/scan-ktp/" . $newFilename;
                // $filePath = "/home/one/project/one/one-media/scan-ktp/kris-test.jpg";

                // resize

                $x = file_put_contents($filePath, $file);

                if ($x === false) {
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Gagal menyimpan file",
                        "data" => []
                    ]);
                    exit;
                }

                // OCR 
                $image_path = $this->base_img . $newFilename;
                //$result = $this->ocr_llama->extract_ocr($image_path);
                $result = $this->ocr_alibaba->extract_ocr($image_path);

                $ocr_nik = isset($result['data']['nik']) ? $result['data']['nik'] : "";
                $ocr_nama = isset($result['data']['nama']) ? $result['data']['nama'] : "";
                $ocr_tempat = isset($result['data']['tempat']) ? $result['data']['tempat'] : "";
                $ocr_tgl_lahir = isset($result['data']['tgl_lahir']) ? $result['data']['tgl_lahir'] : date('Y-m-d');
                $ocr_jenis_kelamin = isset($result['data']['jenis_kelamin']) ? $result['data']['jenis_kelamin'] : "Other";
                $ocr_alamat = isset($result['data']['alamat']) ? $result['data']['alamat'] : "";
                $ocr_rt = isset($result['data']['rt']) ? $result['data']['rt'] : "";
                $ocr_rw = isset($result['data']['rw']) ? $result['data']['rw'] : "";
                $ocr_kel = isset($result['data']['kel']) ? $result['data']['kel'] : "";
                $ocr_desa = isset($result['data']['desa']) ? $result['data']['desa'] : "";
                $ocr_kecamatan = isset($result['data']['kecamatan']) ? $result['data']['kecamatan'] : "";
                $ocr_agama = isset($result['data']['agama']) ? $result['data']['agama'] : "";
                $ocr_status_perkawinan = isset($result['data']['status_perkawinan']) ? $result['data']['status_perkawinan'] : "";
                $ocr_pekerjaan = isset($result['data']['pekerjaan']) ? $result['data']['pekerjaan'] : "";
                $ocr_kewarganegaraan = isset($result['data']['kewarganegaraan']) ? $result['data']['kewarganegaraan'] : "";
                $ocr_berlaku_hingga = isset($result['data']['berlaku_hingga']) ? $result['data']['berlaku_hingga'] : "";
                

                $url = "one-media/scan-ktp/".$newFilename;
                // $url = "/home/one/project/one/one-media/scan-ktp/kris-test.jpg";

                // jenis kelamin
                $first_char = strtoupper(substr($ocr_jenis_kelamin, 0, 1));
                $sql_j = "SELECT * FROM m_sex 
                WHERE M_SexIsActive = 'Y' 
                AND m_sexname LIKE '$first_char%'";

                $qry_j = $this->db_scan_ktp->query($sql_j);
                if(!$qry_j){
                    $this->db_scan_ktp->trans_rollback();
                    $error = $this->db_scan_ktp->error(); 
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Error get data jenis kelamin : " . $error['message'],
                        "data" => [] 
                    ]);
                }

                $M_SexID = 3;

                $rows_j = $qry_j->result_array();

                if(count($rows_j) > 0){
                    $M_SexID = $rows_j[0]['M_SexID'];
                }

                // proses insert
                // $tgl_lahir = date_format($ocr_tgl_lahir,'Y-m-d');

                $tgl_lahir = date('Y-m-d');

                if (!empty($ocr_tgl_lahir)) {
                    $tgl_lahirObj = DateTime::createFromFormat('d-m-Y', $ocr_tgl_lahir);
                
                    if ($tgl_lahirObj) {
                        $tgl_lahir = $tgl_lahirObj->format('Y-m-d');
                    } else {
                        $tgl_lahir = date('Y-m-d');
                    }
                } else {
                    $tgl_lahir = date('Y-m-d');
                }                

                // echo json_encode([
                //     "ocr_tgl_lahir" => $ocr_tgl_lahir,
                //     "tgl_lahir" => $tgl_lahir,
                // ]);
                // exit;

                $Person_Ocr = json_encode($result, true);

                $sql_i = "INSERT INTO person (
                        Person_NIK, 
                        Person_Name, 
                        Person_Dob, 
                        Person_Sex, 
                        Person_Url, 
                        Person_IsActive, 
                        Person_Created, 
                        Person_LastUpdated, 
                        Person_UserID,
                        Person_Ocr
                    ) 
                VALUES (
                    '$ocr_nik', 
                    '$ocr_nama',
                    '$tgl_lahir', 
                    $M_SexID, 
                    '$url', 
                    'Y', 
                    NOW(), 
                    NOW(), 
                    '$userId',
                    '$Person_Ocr'
                )";

                $qry_i = $this->db_scan_ktp->query($sql_i);

                if(!$qry_i){
                    $this->db_scan_ktp->trans_rollback();
                    $error = $this->db_scan_ktp->error(); 
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Error insert data : " . $error['message'],
                        "data" => [] 
                    ]);
                }

                $this->db_scan_ktp->trans_commit();
                
                echo json_encode([
                    "status" => "OK",
                    "message" => "Sukses upload file",
                    "data" => [
                        "filename" => $newFilename,
                        "path" => $filePath
                    ]
                ]);
            } catch (Exception $e) {
                $this->db_scan_ktp->trans_rollback();
                echo json_encode([
                    "status" => "Err",
                    "message" => "Gagal upload file: " . $e->getMessage(),
                    "data" => []
                ]);
            }
        } else {
            echo json_encode([
                "status" => "Err",
                "message" => "File kosong",
                "data" => []
            ]);
        }
    }

    // get by id
    public function getById()
    {
        try {
            // if (! $this->isLogin) {
            //     $this->sys_error("Invalid Token");
            //     exit;
            // }
            
            $prm = $this->sys_input;
            $personId = $prm['personId'];

            $sql_select = "SELECT 
                Person_ID,
                IFNULL(Person_NIK, '') as Person_NIK,
                IFNULL(Person_Name, '') as Person_Name,
                IFNULL(Person_Dob, '') as Person_Dob,
                CASE 
                    WHEN Person_Dob IS NULL OR Person_Dob = '0000-00-00' 
                    THEN DATE_FORMAT(NOW(), '%Y-%m-%d') 
                    ELSE DATE_FORMAT(Person_Dob, '%Y-%m-%d') 
                END AS Person_Dob,
                IFNULL(Person_Url, '') as Person_Url,
                IFNULL(m_sexname, '') as m_sexname
                FROM person
                    join m_sex
                    ON Person_Sex = M_SexID
                    AND M_SexIsActive = 'Y'
                    AND Person_IsActive = 'Y'
                    AND Person_ID = '$personId'
                ORDER BY Person_ID desc
                LIMIT 20";
            
            $qry = $this->db_scan_ktp->query($sql_select);
            if (!$qry) {
                $error = $this->db_scan_ktp->error(); 
                echo json_encode([
                    "status" => "Err",
                    "message" => "Error get data person : " . $error['message'],
                    "data" => [] 
                ]);
            }

            $rows = $qry->result_array();

            if(count($rows) > 0) {
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
                "message" => "Error : ".$exc->getMessage(),
                "data" => [] 
            ]);
        }
    }

    // {Person_ID: 37, Person_NIK:sasaa, Person_Name: hshshsbshs, Person_Dob: 28-02-2025, Person_Sex: 1}
    // proses edit
    public function proses_edit()
    {
        $this->db_scan_ktp->trans_begin();
        try{
                $prm = $this->sys_input;
                $Person_ID = $prm['Person_ID'];
                $Person_NIK = $prm['Person_NIK'];
                $Person_Name = $prm['Person_Name'];
                $userId = $prm['userId'];
                // $Person_Dob = date_format($prm['Person_Dob'] ,date('Y-m-d'));

                $Person_Dob = date('Y-m-d');
                $Person_Dob_Obj = DateTime::createFromFormat('d-m-Y', $prm['Person_Dob']);
                if (!$Person_Dob_Obj) {
                    $Person_Dob = date('Y-m-d');
                }

                $Person_Dob = $Person_Dob_Obj->format('Y-m-d');
                $Person_Sex = $prm['Person_Sex'];

                // update
                $sql_u = "UPDATE person set 
                    Person_NIK = '$Person_NIK',
                    Person_Name = '$Person_Name',
                    Person_Dob = '$Person_Dob',
                    Person_Sex = '$Person_Sex',
                    Person_UserID = '$userId'
                    WHERE Person_ID = '$Person_ID'
                ";

                $qry_u = $this->db_scan_ktp->query($sql_u);
                if(!$qry_u){
                    $this->db_scan_ktp->trans_rollback();
                    $error = $this->db_scan_ktp->error(); 
                    echo json_encode([
                        "status" => "Err",
                        "message" => "Error update data : " . $error['message'],
                        "data" => [] 
                    ]);
                }
                
                $this->db_scan_ktp->trans_commit();
                        
                echo json_encode([
                    "status" => "OK",
                    "message" => "Sukses Edit data",
                    "data" => []
                ]);
        } catch (Exception $e) {
            $this->db_scan_ktp->trans_rollback();
            echo json_encode([
                "status" => "Err",
                "message" => "Gagal edit: " . $e->getMessage(),
                "data" => []
            ]);
        }
    }

}

