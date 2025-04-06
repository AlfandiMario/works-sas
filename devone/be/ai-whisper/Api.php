<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends MY_Controller
{

  public function __construct()
  {
    parent::__construct();
  }
  public function get_qrcode()
  {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    echo json_encode(["status" => "OK", "qrcode" => $uuid, "img" => "https://devone.aplikasi.web.id/one-api/voice_2text/api/qrcode/$uuid"]);
  }
  public function qrcode($uuid = "")
  {
    // Generate UUID v4
    if ($uuid == "") {
      $data = random_bytes(16);
      $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
      $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    $img_qrcode = $this->post("http://localhost/charts/qrtext.php", $uuid);
    header("Content-type: image/png");
    echo $img_qrcode;
  }
  public function transcribe($qrcode)
  {
    $sql = "SELECT 
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
                    AND Voice2text_QrCode= ?
    ";
    $this->db->query("use scan_ktp");
    $qry = $this->db->query($sql, [$qrcode]);
    if (!$qry) {
      echo json_encode([
        "status" => "ERR", "code" => "E00",
        "message" => $this->db->error()["message"]
      ]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      echo json_encode([
        "status" => "ERR",
        "code" => "E01", "messge" => "Transcribe not available"
      ]);
      exit;
    }
    $r = $rows[0];
    echo json_encode([
      "status" => "OK",
      "note" => $r["Voice2text_Note"],
      "voice" => "/" . $r["Voice2text_Url"],
      "transcribe" => $r["Voice2text_Text"],
      "detail" => json_decode($r["Voice2text_JsonData"])
    ], JSON_PRETTY_PRINT);
  }
  public function post($url, $data)
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: application/text",
      "Content-Length: " . strlen($data),
    ]);
    $result = curl_exec($ch);
    if (curl_error($ch) != "") {
      return "ERROR Accessing QrCode : " . curl_error($ch) . "\n";
    }
    curl_close($ch);
    return $result;
  }
}
