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
   
   
   public function search()
   {
      $prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
	  
    
	  $nolab = $prm["nolab"];
	  $sampletypeid = $prm["sampletypeid"];
	  $filter_sampletype = '';
	  if(intval($sampletypeid) > 0)
		  $filter_sampletype = " AND T_SampleTypeID = {$sampletypeid}";
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

		$sql = 	"SELECT T_OrderHeaderID,T_BarcodeLabID,T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, T_SampleTypeID,
						T_SampleStationID as stationid,
						T_OrderSampleID	,
						T_OrderSampleSendHandling as status,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						'X' as requirement_status,
						'' as requirements
				FROM t_orderheader	
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID $filter_sampletype
				JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID AND T_BarcodeLabIsActive = 'Y'
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationIsNonLab = ''
				JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
					T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleSendHandling = 'Y' AND T_OrderSampleReceiveHandling = 'N' AND T_OrderSampleIsActive = 'Y'
				$sql_where
				GROUP BY T_BarcodeLabID
				ORDER BY T_OrderSampleSendHandlingDate ASC, T_OrderSampleSendHandlingTime ASC, T_OrderHeaderID ASC
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
				$zxprm['sampletypeid'] = $v['T_SampleTypeID'];
				//$rows[$k]['requirements'] = $this->getrequirements($zxprm);
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
						T_SampleStationIsActive = 'Y' AND T_SampleStationIsNonLab = ''
				";
				//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		$rows['statuses'] = array(array('id'=>'Y','name'=>'Belum Terima'),array('id'=>'R','name'=>'Sudah Terima'));
		$sql = "SELECT T_SampleTypeID as id, T_SampletypeName as name
				FROM t_ordersample
				JOIN t_sampletype ON T_OrderSampleT_sampleTypeID = T_sampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationIsNonLab = ''
				WHERE
				T_OrderSampleSendHandling = 'Y' AND
				T_OrderSampleReceiveHandling = 'N' AND 
				T_OrderSampleIsActive = 'Y'
				GROUP BY T_SampleTypeID";
		$rows['sampletypes'] = $this->db_onedev->query($sql)->result_array();
		array_push($rows['sampletypes'],array('id'=> 0,'name'=> 'Semua'));
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
						T_OrderSampleID	,
						T_OrderSampleSendHandling as status,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						'X' as requirement_status,
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
				JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
					T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleVerification = 'Y' AND T_OrderSampleSendHandling = 'Y' AND T_OrderSampleIsActive = 'Y'
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
				$sql = "UPDATE t_ordersample SET 
							T_OrderSampleReceiveHandling = 'Y', 
							T_OrderSampleReceiveHandlingDate = CURDATE(),
							T_OrderSampleReceiveHandlingTime = CURTIME(),
							T_OrderSampleReceiveHandlingUserID = {$userid},
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleID = {$v['T_OrderSampleID']}";
				$this->db_onedev->query($sql);
				$sql = "INSERT INTO sample_by_step(
							SampleByStepM_StatusSampleCode,
							SampleByStepT_OrderHeaderID,
							SampleByStepT_BarcodeLabID,
							SampleByStepRequirementStatus,
							SampleByStepRequirements,
							SampleByStepUserID,
							SampleByStepDateTime
						)
						VALUES(
							'SAMPLING.Handling.From.Verification',
							{$v['T_OrderHeaderID']},
							{$v['T_BarcodeLabID']},
							'Y',
							'[]',
							{$userid},
							NOW()
						)";
				$this->db_onedev->query($sql);
			}
		}
		
		if($prm['act'] == 'cancel'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_ordersample SET 
							T_OrderSampleSendHandling = 'Y', 
							T_OrderSampleReceiveHandlingDate = NULL,
							T_OrderSampleReceiveHandlingTime = NULL,
							T_OrderSampleReceiveHandlingUserID = NULL,
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleID = {$v['T_OrderSampleID']}";
				$this->db_onedev->query($sql);
			}
		}
		
		if($prm['act'] == 'reject'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_ordersample SET 
							T_OrderSampleReceiveHandling = 'N', 
							T_OrderSampleReceiveHandlingDate = NULL,
							T_OrderSampleReceiveHandlingTime = NULL,
							T_OrderSampleReceiveHandlingUserID = NULL,
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleID = {$v['T_OrderSampleID']}";
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
