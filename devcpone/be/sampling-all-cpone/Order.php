<?php
class Order extends MY_Controller
{
   var $db_onedev;
   public function index()
   {
      echo "Samplingverify API";
   }

   public function __construct()
   {
      parent::__construct();
      $this->db_onedev = $this->load->database("onedev", true);
      $this->load->helper(array('form', 'url'));
   }

   function info()
   {
      $prm = $this->sys_input;
      $orderHeaderID = $prm["id"];
      $sql = "SELECT T_OrderDetailT_TestName name
            from 
          t_orderdetail
          join t_test on T_OrderDetailT_OrderHeaderID = ?
          and T_OrderDetailT_TestID = T_TestID 
         and T_OrderDetailIsActive = 'Y'
         and T_TestIsPrice = 'Y' and T_TestIsPrintNota = 'Y'";
      $qry = $this->db_onedev->query($sql, array($orderHeaderID));
      $rows = $qry->result_array();
      $this->sys_ok($rows);
   }
}
