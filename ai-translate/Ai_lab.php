<?php
class Ai_lab extends MY_Controller
{
    var $db_onedev;
    var $load;
    var $hostname;

    public function index()
    {
        // Ini di devone
        echo "BE untuk AI Lab";
    }

    public function __construct()
    {
        parent::__construct();
        // $this->db_onedev = $this->load->database('one_aditya', TRUE);
        $this->hostname = "devone.aplikasi.web.id";
    }

    public function sendToAi()
    {
        // $inputs = $_POST['data'];
        $input = file_get_contents('php://input');

        /* Coba CURL ke /translate-array */

        $curl = curl_init();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.9.10.205:4321/translate',
            // CURLOPT_URL => 'http://10.9.10.205:4321/nonlab',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['input' => $input]), // JSON-encode the string with "data" key
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json', // Set content type to JSON
            ),
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            // An error occurred during the cURL request
            $error = curl_error($curl);
            $this->sys_error($error);
            exit;
        } else {
            $result = json_decode($response, true);
            $this->sys_ok($result);
        }
    }

    public function sendToAiArr()
    {
        $inputs = $_POST['data'];
        // $inputs = ["reaktif", "proaktif", "kamu harus makan obat"];

        /* Coba CURL ke /translate-array */
        $payload = json_encode(['input' => array_values($inputs)]);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://10.9.10.204:4321/translate-array',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            // An error occurred during the cURL request
            $error = curl_error($curl);
            $this->sys_error($error);
            exit;
        } else {
            $result = json_decode($response, true);
            $this->sys_ok($result);
        }
    }
}
