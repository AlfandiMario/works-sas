<?php
class Comment extends MY_Controller
{
   var $db_smartone;
   public function index()
   {
      echo "API";
   }
   public function __construct()
   {
      parent::__construct();
      $this->db_smartone = $this->load->database("onedev", true);
   }
   public function save()
   {
      if (! $this->isLogin) {
         $this->sys_error("Invalid Token");
         exit;
      }      
      $prm = $this->sys_input;
      $note = $prm["T_OrderHeaderSamplingNote"];
      $id = $prm["T_OrderHeaderID"];
      $userid = $this->sys_user["M_UserID"];

      $sql = "update t_orderheader
         set T_OrderHeaderSamplingNote = ? ,
         T_OrderHeaderSamplingNoteM_UserID = ?
         where T_OrderHeaderID = ?";
      $query = $this->db_smartone->query($sql, array($note, $userid, $id) );
      if (! $query) { 
         $this->sys_error_db("", $this->db_smartone);
         exit;
      }

      $result = array("status" => "OK" );
      $this->sys_ok($result);
   }
   public function load()
   {
      if (! $this->isLogin) {
         $this->sys_error("Invalid Token");
         exit;
      }      
      $prm = $this->sys_input;
      $id = $prm["T_OrderHeaderID"];
      
      $sql = "select T_OrderHeaderDate,
         fos.M_StaffName foStaffName, 
         T_OrderHeaderFoNote ,
         Fo_StatusCreated verDate,
         T_OrderHeaderVerificationNote verNote,
         vers.M_StaffName verStaffName
         from t_orderheader
         join fo_status on T_OrderHeaderID = ?
            and T_OrderHeaderID = Fo_StatusT_OrderHeaderID
            and Fo_StatusM_StatusID = 3
         join m_user fo on T_OrderHeaderFoNoteM_UserID = fo.M_UserID 
         join m_staff fos on M_UserM_StaffID = fos.M_StaffID  
         join m_user ver on Fo_StatusM_UserID = ver.M_UserID 
         join m_staff vers on ver.M_UserM_StaffID = vers.M_StaffID";
      $qry = $this->db_smartone->query($sql,array($id));
      //echo $this->db_smartone->last_query();
      $info = array();
      if ($qry) {
         $rows = $qry->result_array();
         if (count($rows) > 0 ) $info = $rows[0]; 
      }
      $result = array("status" => "OK" , "data" => $info );
      $this->sys_ok($result);
   }
}
