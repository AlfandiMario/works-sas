<?php
class MY_Controller extends CI_Controller {
   var $db_onedev;
   var $sys_user;
   var $sys_input;
   var $isLogin;
   var $one_salt = '545';
   var $SECRET_KEY = "--one_api-secret-2019-04-01";

   var $group_lab = "1";
   var $lang_default_code = "ID";
   
   public function broadcast($prm){
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
      if ( isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "OPTIONS") {
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
      $this->sys_input = json_decode($this->input->raw_input_stream,true);
      if (! $this->sys_input ) {
         if ( count($this->input->post()) > 0 ) {
            $this->sys_input = $this->input->post();
         } else {
            $this->sys_input = $this->input->get();
         }
      }
      $this->load->library("Jwt");
      try {
         $prm  = $this->sys_input;
         if (! isset($prm["token"])) {
            $this->isLogin = false;
         } else {
            $user = JWT::decode($prm["token"],$this->SECRET_KEY,true);
            unset($this->sys_input["token"]);
            $user = json_decode(json_encode($user),true);
            if ($user["M_UserID"] > 0 ) {
               $this->isLogin = true;
            }
            $this->sys_user = $user;
            $this->db_onedev = $this->load->database("onedev", true);
            $query = $this->db_onedev->query("update m_user SET  M_UserLastAccess = now() WHERE M_UserID = ?",array($user["M_UserID"]));
            if (!$query) {
              $message = $this->db_onedev->error();
              $this->sys_error($message);
              exit;
            }
            //update last accessed 
         }
      } catch(Exception $e) {
         $this->isLogin = false;
      }
      $this->load->database();

   }
   public function sys_debug() {
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);
   }
   public function sys_error_db($message,$db = false) {
      if (! $db )  { 
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
   public function sys_error($message) {
      echo json_encode(
         array(
            "status" => "ERR",
            "message" => $message
         )
      );
   }
   public function sys_ok($data) {
      echo json_encode(
         array(
            "status" => "OK",
            "data" => $data
         )
      );
   }

   public function clean_mysqli_connection( $dbc )
    {
        while( mysqli_more_results($dbc) )
        {
            if(mysqli_next_result($dbc))
            {
                $result = mysqli_use_result($dbc);

                unset($result);
            }
        }
    }
}
?>
