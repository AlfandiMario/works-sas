<?php
class MY_Controller extends CI_Controller
{
   var $db_regional;
   var $sys_user;
   var $sys_input;
   var $isLogin;
   var $one_salt = '545';
   var $SECRET_KEY = "--one_api-secret-2019-04-01";

   var $group_lab = "1";
   var $lang_default_code = "ID";

   public function broadcast($prm)
   {
      file_get_contents('http://127.0.0.1:9090/broadcast/' . $prm);
   }
   public function __construct()
   {
      parent::__construct();
      //for preflight

      header('Access-Control-Allow-Origin: *');
      header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
      //for disable cached
      header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
      header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
      header('Pragma: no-cache');
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      global $_SERVER;
      if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "OPTIONS") {
         exit;
      }
      $this->sys_user = array(
         "isExists" => false,
         "user" => array(
            "userName" => "",
            "userLogin" => "",
            "userID" => 0
         )
      );
      error_reporting(0);

      $this->sys_input = json_decode($this->input->raw_input_stream, TRUE);
      if (! $this->sys_input) {
         if (count($this->input->post()) > 0) {
            $this->sys_input = $this->clean_input($this->input->post(NULL, TRUE)); //new
         } else {
            $this->sys_input = $this->clean_input($this->input->get(NULL, TRUE)); //new
         }
      } else {
         $this->sys_input = $this->clean_input($this->input->input_stream(NULL, TRUE)); //new
      }
      $this->load->library("Jwt");

      try {
         $prm  = $this->sys_input;
         if (! isset($prm["token"])) {
            $this->isLogin = false;
         } else {
            $user = JWT::decode($prm["token"], $this->SECRET_KEY, true);
            unset($this->sys_input["token"]);
            $user = json_decode(json_encode($user), true);
            $this->db_onelite = $this->load->database("onelite", true);
            $query = $this->db_onelite->query("select * FROM m_user WHERE M_UserID = ? LIMIT 1", array($user["M_UserID"]));
            $isActive = 'N';

            if (!$query) {
               $message = $this->db_onelite->error();
               $this->sys_error($message);
               exit;
            } else {
               $data_user = $query->row_array();
               $isActive = $data_user['M_UserIsActive'];
            }

            if ($isActive == 'Y') {
               if ($user["M_UserID"] > 0) {
                  $this->isLogin = true;
               }
               $this->sys_user = $user;
               $this->db_onelite = $this->load->database("onelite", true);
               $query = $this->db_onelite->query("update m_user SET  M_UserLastAccess = now() WHERE M_UserID = ?", array($user["M_UserID"]));
               if (!$query) {
                  $message = $this->db_onelite->error();
                  $this->sys_error($message);
                  exit;
               }

               //update last accessed 
               if (isset($prm["fcm"])) {
                  $sql = "SELECT COUNT(*) as xcount FROM fcm WHERE FCMToken = ? AND FCMUsername = ? LIMIT 1";
                  $row_fcm =  $this->db_onelite->query($sql, array($prm["fcm"], $user["M_UserUsername"]))->row_array();
                  if ($row_fcm['xcount'] == 0) {
                     $query = $this->db_onelite->query("INSERT INTO fcm (FCMToken,FCMUsername)VALUES(?,?)", array($prm["fcm"], $user["M_UserUsername"]));
                     if (!$query) {
                        $message = $this->db_onelite->error();
                        $this->sys_error($message);
                        exit;
                     }
                  }
               }
            }
         }
      } catch (Exception $e) {
         $this->isLogin = false;
      }
      $this->load->database();
   }
   public function sys_debug()
   {
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);
   }
   public function sys_error_db($message, $db = false)
   {
      if (! $db) {
         echo json_encode(
            array(
               "status" => "ERR",
               "message" => $message,
               "query" => $this->db->last_query(),
               "db_error" => $this->db->error()
            )
         );
      } else {
         echo json_encode(
            array(
               "status" => "ERR",
               "message" => $message,
               "query" => $db->last_query(),
               "db_error" => $db->error()
            )
         );
      }
   }
   public function sys_error($message)
   {
      echo json_encode(
         array(
            "status" => "ERR",
            "message" => $message
         )
      );
   }
   public function sys_ok($data)
   {
      echo json_encode(
         array(
            "status" => "OK",
            "data" => $data
         )
      );
   }

   public function clean_mysqli_connection($dbc)
   {
      while (mysqli_more_results($dbc)) {
         if (mysqli_next_result($dbc)) {
            $result = mysqli_use_result($dbc);

            unset($result);
         }
      }
   }

   public function clean_input($data)
   {
      if (is_array($data)) {
         $cleaned = array();
         foreach ($data as $key => $value) {
            $cleaned[$key] = $this->clean_input($value);
         }
         return $cleaned;
      }

      if (empty($data)) {
         return null; // Return null if data is empty
      }

      $data = trim($data); // Remove whitespace      
      $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
      $data = strip_tags($data); // Remove any HTML tags

      // Check for common SQL injection patterns
      $pattern = '/[^a-zA-Z0-9_\-\s\.,]/';
      $blacklist = [
         'SELECT',
         'INSERT',
         'UPDATE',
         'DELETE',
         'DROP',
         'UNION',
         'SLEEP',
         'BENCHMARK',
         'WAITFOR',
         '--',
         '/*',
         '*/',
         'AND',
         'OR',
         'XOR',
         'LIKE',
         '=',
         '<',
         '>'
      ];

      // Jika uppercase $data mengandung kata yang ada di array $blacklist, ubah $data=""
      if (preg_match($pattern, $data) || str_replace($blacklist, '', strtoupper($data)) != $data) {
         $data = "";
      }

      // Remove special characters
      $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
      // Remove null bytes
      $data = str_replace(chr(0), '', $data);

      // Escape special characters for SQL if database connection exists
      if ($this->db_onelite) {
         $data = $this->db_onelite->escape($data);
         echo "Esacped db";
      }

      return $data;
   }
}
