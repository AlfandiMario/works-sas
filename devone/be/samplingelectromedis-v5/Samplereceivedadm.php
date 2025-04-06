<?php
class Samplereceivedadm extends MY_Controller
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
   
   
   function getphotos($orderid,$sampletypeid){
		$rows  = [];
		//print_r($_SERVER);
		$urlbase = 'http://'.$_SERVER['SERVER_NAME']."/one-media/one-image-nonlab/";
		$sql = "SELECT So_ImageUploadID as id,
				So_ImageUploadOldName as oldname,
				CONCAT('{$urlbase}',So_ImageUploadNewName) as newname
				FROM so_imageupload	
				WHERE
					So_ImageUploadT_OrderHeaderID = {$orderid} AND So_ImageUploadT_SampleTypeID = {$sampletypeid}  AND So_ImageUploadIsActive = 'Y'";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();
		return $rows;
		
   }
   
   function getdoctors($sampletypeid){
		$rows  = [];

		$sql = "SELECT M_DoctorID as id,
				CONCAT(M_DoctorPrefix,' ',M_DoctorName,' ',M_DoctorSufix) as name
				FROM m_doctorso
				JOIN m_doctor ON M_DoctorSOM_DoctorID = M_DoctorID
				JOIN t_test ON T_TestT_SampleTypeID = {$sampletypeid} AND M_DoctorSONat_SubGroupID = T_TestNat_SubgroupID
				WHERE
					M_DoctorSOIsActive = 'Y'";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();
		return $rows;
		
   }
   
   function getsetdoctoraddress($doctorid){
		$rows  = [];

		$sql = "SELECT M_DoctorAddressID as id, M_DoctorAddressDescription as name
					FROM m_doctoraddress 
					WHERE	
						M_DoctorAddressM_DoctorID = {$doctorid} AND M_DoctorAddressIsActive = 'Y'";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();
		return $rows;
		
   }
   
   
   public function search_old()
   {
      $prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
	  
      $name = $prm["name"];
	  $nolab = $prm["nolab"];
	  $stationid = $prm["stationid"];
	  $statusid = 'Y';
	  $where_status = '';
	  
      $sql_where = "WHERE T_OrderHeaderIsActive = 'Y' {$where_status}";
	  
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
	  
		$sql = 	"SELECT T_OrderHeaderID,T_SamplingSoID as T_BarcodeLabID,T_SampleTypeName as T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,T_TestID,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						T_OrderHeaderM_PatientAge as umur,
						T_OrderHeaderLabNumberExt,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date, 
						T_SampleStationID, T_SampleTypeID,
						{$stationid} as stationid,
						T_SamplingSoID,
						T_TestName as test_name,
						T_SamplingSoFlagSend as status,
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements
				FROM t_orderheader	
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND 
									 T_SamplingSoFlag = 'V' AND 
									 T_SamplingSoFlagSend = 'Y' AND T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
								T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
								T_SamplingSoRequirementNat_PositionID = 9 AND
								T_SamplingSoRequirementIsActive = 'Y'
				$sql_where
				GROUP BY T_SamplingSoID
				ORDER BY iscito DESC,T_SamplingSoSendAdmDate ASC, T_SamplingSoSendAdmTime ASC, T_OrderHeaderID ASC
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		//echo $this->db_onedev->last_query();
		$rows = $query->result_array();
		if($rows){
			foreach($rows as $k => $v){
				$zxprm = array();
				$zxprm['status'] = $v['status'];
				$zxprm['orderid'] = $v['T_OrderHeaderID'];
				$zxprm['sampletypeid'] = $v['T_TestID'];
				$zxprm['samplingso_id'] = $v['T_SamplingSoID'];
				$rows[$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}
		//$this->_add_address($rows);
		$result = array("total" => count($rows), "records" => $rows, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
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
	  $statusid = 'Y';
	  $where_status = '';
	  
      $sql_where = "WHERE T_OrderHeaderIsActive = 'Y' {$where_status}";
	  
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
		$sql = "SELECT T_OrderHeaderID,T_SamplingSoID as T_BarcodeLabID,T_SampleTypeName as T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,T_TestID,
						'' as M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						'' as M_CompanyName,
						T_OrderHeaderM_PatientAge as umur,
						T_OrderHeaderLabNumberExt,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date, 
						T_SampleStationID, T_SampleTypeID,
						2 as stationid,
						T_SamplingSoID,
						T_TestName as test_name,
						T_SamplingSoFlagSend as status,
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements
				FROM t_samplingso
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID 
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID
				JOIN t_samplestation ON T_SampleStationID = T_SamplingSoT_SampleStationID 
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				LEFT JOIN m_title ON M_PatientM_TitleID = M_TitleID
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
												T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
												T_SamplingSoRequirementNat_PositionID = 9 AND
												T_SamplingSoRequirementIsActive = 'Y'
				WHERE
				T_SamplingSoT_SampleStationID = {$stationid} AND T_SamplingSoFlag = 'V' AND T_SamplingSoFlagSend = 'Y' AND T_SamplingSoIsActive = 'Y'";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		//echo $this->db_onedev->last_query();
		$rows = $query->result_array();
		if($rows){
			foreach($rows as $k => $v){
				$zxprm = array();
				$zxprm['status'] = $v['status'];
				$zxprm['orderid'] = $v['T_OrderHeaderID'];
				$zxprm['sampletypeid'] = $v['T_TestID'];
				$zxprm['samplingso_id'] = $v['T_SamplingSoID'];
				$rows[$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}
		//$this->_add_address($rows);
		$result = array("total" => count($rows), "records" => $rows, "sql"=> $this->db_onedev->last_query());
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
							T_SamplingSoRequirementT_SamplingSoID = {$prm['samplingso_id']} AND T_SamplingSoRequirementNat_PositionID = Nat_RequirementPositionNat_PositionID
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
		$rows['statuses'] = array(array('id'=>'Y','name'=>'Belum Terima'),array('id'=>'R','name'=>'Sudah Terima'));
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
		$statusid = 'Y';

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
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements
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
									 T_SamplingSoT_BarcodeLabID = T_BarcodeLabID AND T_SamplingSoFlag = 'V' AND 
									 T_SamplingSoFlagSend = '{$statusid}' AND T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
								T_SamplingSoRequirementT_SampletypeID = T_SampletypeID AND
								T_SamplingSoRequirementNat_PositionID = 9 AND
								T_SamplingSoRequirementIsActive = 'Y'
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
	
	function getdoctoraddress(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$query ="	SELECT M_DoctorAddressID as id, M_DoctorAddressDescription as name
					FROM m_doctoraddress 
					WHERE	
						M_DoctorAddressM_DoctorID = {$prm['id']} AND M_DoctorAddressIsActive = 'Y'
				";
				//echo $query;
		$rows = $this->db_onedev->query($query)->result_array();

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
		
		if($prm['act'] == 'receive'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_samplingso SET 
							T_SamplingSoFlagSend = 'R', 
							T_SamplingSoReceiveAdmDate = CURDATE(),
							T_SamplingSoReceiveAdmTime = CURTIME(),
							T_SamplingSoReceiveAdmUserID = {$userid},
							T_SamplingSoUserID = {$userid}
						WHERE
						T_SamplingSoID = {$v['T_SamplingSoID']}";
				$this->db_onedev->query($sql);
				$sql = "INSERT INTO sample_so_by_step (
								SampleSoByStepT_OrderHeaderID,
								SampleSoByStepT_TestID,
								SampleSoByStepCode,
								SampleSoByStepDateTime,
								SampleSoByStepUserID
							)
							VALUES(
								{$v['T_OrderHeaderID']},
								{$v['T_TestID']},
								'SAMPLING.Handling.From.Verification',
								NOW(),
								{$userid}
							)";
					$this->db_onedev->query($sql);
			}
		}
		
		if($prm['act'] == 'cancel'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_samplingso SET 
							T_SamplingSoFlagSend = 'Y', 
							T_SamplingSoReceiveAdmDate = NULL,
							T_SamplingSoReceiveAdmTime = NULL,
							T_SamplingSoReceiveAdmUserID = NULL,
							T_SamplingSoUserID = {$userid}
						WHERE
						T_SamplingSoID = {$v['T_SamplingSoID']}";
				$this->db_onedev->query($sql);
			}
		}
		
		if($prm['act'] == 'reject'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_samplingso SET 
							T_SamplingSoFlagSend = 'N', 
							T_SamplingSoSendAdmDate = NULL,
							T_SamplingSoSendAdmTime = NULL,
							T_SamplingSoSendAdmUserID = NULL,
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