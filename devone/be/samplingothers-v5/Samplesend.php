<?php
class Samplesend extends MY_Controller
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
   
   
   public function search()
   {
      $prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
	  
      $name = $prm["name"];
	  $nolab = $prm["nolab"];
	  $stationid = $prm["stationid"];
	  $statusid = "V";
	  
      $sql_where = "WHERE T_OrderHeaderIsActive = 'Y'";
	  
      //$sql_param = array();
      if ($name != "") {
         if ($sql_where != "") {
            $sql_where .=" and ";
         }
         $sql_where .= " M_PatientName like '%$name%' ";
         //$sql_param[] = "%$nama%";
      }
	  
	  if ($nolab != "") {
         if ($sql_where != "") {
            $sql_where .=" and ";
         }
         $sql_where .= " T_OrderHeaderLabNumber like '%$nolab%' ";
         //$sql_param[] = "%$nama%";
      }

      $sql = "	SELECT count(*) as total
				FROM (
					SELECT T_BarcodeLabID
					FROM t_orderheader	
					JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
					JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
					JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_SampleTypeID = T_SampleTypeID AND 
										 T_SamplingSoFlag = '{$statusid}' AND T_SamplingSoIsActive = 'Y'
					$sql_where
					GROUP BY T_BarcodeLabID
				) a
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);

		$tot_count = 0;
		//$tot_page = 0;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
			//$tot_page = ceil($tot_count/$number_limit);
		} else {
			$this->sys_error_db("m_doctor count", $this->db_onedev);
			exit;
		}  
	  
		$sql = 	"SELECT T_OrderHeaderID,T_BarcodeLabID,T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, T_SampleTypeID,
						{$stationid} as stationid,
						T_SamplingSoID,
						T_SamplingSoFlagSend as status,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						'N' as chex
				FROM t_orderheader	
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID AND T_BarcodeLabIsActive = 'Y'
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_SampleTypeID = T_SampleTypeID AND 
									 T_SamplingSoT_BarcodeLabID = T_BarcodeLabID AND T_SamplingSoFlag = '{$statusid}' AND T_SamplingSoFlagSend = 'N' AND T_SamplingSoIsActive = 'Y'
				
				$sql_where
				GROUP BY T_BarcodeLabID
				ORDER BY iscito DESC,T_SamplingSoVerifyDate ASC,T_SamplingSoVerifyTime ASC, T_OrderHeaderID ASC
				limit 0,20";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		//echo $this->db_onedev->last_query();
		$rows = $query->result_array();
		if($rows){
			foreach($rows as $k => $v){
				if($v['chex'] == 'N'){
					$rows[$k]['chex'] = false;
				}else{
					$rows[$k]['chex'] = true;
				}
			}
		}
		//$this->_add_address($rows);
		$result = array("total" => $tot_count, "records" => $rows, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }
   
   function getrequirements($prm){
		
		
		$query ="	SELECT Nat_RequirementID as id, 
		Nat_RequirementName as name, '{$prm['status']}' as status,
		if(ISNULL(T_SamplingSoRequirementID),'N', if(json_contains(T_SamplingSoRequirementRequirements,Nat_RequirementID),'Y','N') ) as chex, 
		Nat_RequirementPositionNat_PositionID as positionid
					FROM nat_requirement
					JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
					JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND Nat_RequirementPositionNat_PositionID = 9  AND 
						 Nat_RequirementPositionIsActive = 'Y'
					JOIN t_test ON T_TestNat_TestID = Nat_TestRequirementNat_TestID
					LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = {$prm['orderid']} AND
							T_SamplingSoRequirementT_SampletypeID = {$prm['sampletypeid']} AND T_SamplingSoRequirementNat_PositionID = Nat_RequirementPositionNat_PositionID
					WHERE	
						Nat_TestRequirementIsActive = 'Y'
				";
				//echo $query;
		$rows = $this->db_onedev->query($query)->result_array();	
		

		return $rows;
	}
   
   function getstationstatus(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query ="	SELECT T_SampleStationID as id, T_SampleStationName as name
					FROM t_samplestation 
					WHERE	
						T_SampleStationIsActive = 'Y' AND T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
				";
				//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		$rows['statuses'] = array(array('id'=>'D','name'=>'Belum Verifikasi'),array('id'=>'V','name'=>'Sudah Verifikasi'));
		$result = array(
			"total" => count($rows) , 
			"records" => $rows, 
		); 
		$this->sys_ok($result);
		exit;
	}
	
	function search_patient(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$search = $prm["search"];
		$stationid = $prm["stationid"];

		$sql_where = "WHERE T_OrderHeaderLabNumber = '{$search}' AND T_OrderHeaderIsActive = 'Y'";
		$rows = [];
		$query = 	"SELECT T_OrderHeaderID,T_BarcodeLabID,T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, T_SampleTypeID,
						{$stationid} as stationid,
						T_SamplingSoID,
						T_SamplingSoFlagSend as status,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						'Y' as chex
				FROM t_orderheader	
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID AND T_BarcodeLabIsActive = 'Y'
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_SampleTypeID = T_SampleTypeID AND 
									 T_SamplingSoT_BarcodeLabID = T_BarcodeLabID AND T_SamplingSoFlag = 'V' AND T_SamplingSoFlagSend = 'N' AND T_SamplingSoIsActive = 'Y'
				
				$sql_where
				GROUP BY T_BarcodeLabID
				ORDER BY iscito DESC,T_OrderPromiseDateTime ASC, T_OrderHeaderID ASC, T_SamplingSoID ASC
				limit 0,20";
				
		$rows = $this->db_onedev->query($query)->result_array();
		if($rows){
			foreach($rows as $k => $v){
				$rows[$k]['chex'] = true;
			}
		}
		
		$result = array(
			"total" => count($rows) , 
			"records" => $rows, 
		); 
		$this->sys_ok($result);
		exit;
	}
	
	function doaction(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$samples = $prm['sample'];
		
		if($prm['act'] == 'send'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_samplingso SET 
							T_SamplingSoFlagSend = 'Y', 
							T_SamplingSoSendAdmDate = CURDATE(),
							T_SamplingSoSendAdmTime = CURTIME(),
							T_SamplingSoSendAdmUserID = {$userid},
							T_SamplingSoUserID = {$userid}
						WHERE
						T_SamplingSoID = {$v['T_SamplingSoID']}";
				$this->db_onedev->query($sql);
			}
		}
		
		
		
		
		$result = array(
			"total" => 1 , 
			"records" => array('status'=>'OK')
		); 
		$this->sys_ok($result);
		
		exit;
	}
	
	function deletephoto(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$query ="	UPDATE so_imageupload SET So_ImageUploadIsActive = 'N', So_ImageUploadUserID = {$userid} WHERE So_ImageUploadID = {$prm['id']}";
				//echo $query;
		$actdelete = $this->db_onedev->query($query);
		
		if($actdelete){
			$result = array(
				"total" => 1 , 
				"records" => array(), 
			); 
			$this->sys_ok($result);
			exit;
		}
		else{
			$this->sys_error_db("so_imageupload delete", $this->db_onedev);
			exit;
		}
		
	}
   
   
   
}