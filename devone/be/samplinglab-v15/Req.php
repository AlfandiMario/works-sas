<?php
class Req extends MY_Controller
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
   public function info()
   {
      if (! $this->isLogin) {
         $this->sys_error("Invalid Token");
         exit;
      }      
      $prm = $this->sys_input;
      $id = $prm["T_OrderHeaderID"];
      $sql = "select 
         T_OrderHeaderFoNote , sf.M_StaffName Fo_User,
         T_OrderHeaderVerificationNote, sv.M_StaffName Ver_User 
         from t_orderheader 
         left join m_user f  on T_OrderHeaderFoNoteM_UserID = f.M_UserID
         left join  m_user v on T_OrderHeaderVerificationNoteM_UserID = v.M_UserID
         left join m_staff sf  on f.M_UserM_StaffID = sf.M_StaffID 
         left join m_staff sv on v.M_UserM_StaffID = sv.M_StaffID 
         where T_OrderHeaderID = ?";
      $qry = $this->db_smartone->query($sql, array($id));
      $note = array();
      if ($qry) {
         $rows = $qry->result_array();
         if (count($rows) > 0 ) {
            $note = $rows[0];
         }
      } 

      $sql = "select Nat_PositionName, group_concat(Nat_RequirementName) Requirement
         from t_orderreq 
         join nat_position on T_OrderReqNat_PositionID = Nat_PositionID 
         join nat_requirement on json_contains(T_OrderReqs, Nat_RequirementID )
         where T_OrderReqT_OrderHeaderID = ?
         group by Nat_PositionName";
      $query = $this->db_smartone->query($sql, array($id) );
      if (! $query) { 
         $this->sys_error_db("", $this->db_smartone);
         exit;
      }
      $rows = $query->result_array();
      $result = array("status" => "OK", "data" => $rows , "note" => $note );
      $this->sys_ok($result);
   }
}
