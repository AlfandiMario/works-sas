<?php
class Sampleadm extends MY_Controller
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
	  
      $name = isset($prm["name"])?$prm["name"]:'';
	  $nolab = $prm["nolab"];
	  $stationid = $prm["stationid"];
	  $statusid = $prm["statusid"];
	  $where_status = '';
	  $limit = '';
	  //echo $statusid;
	  if($statusid == 'C'){
		  $limit = 'LIMIT 50';
	  }
	  //echo $limit = 'LIMIT 5';
      $sql_where = "WHERE T_OrderHeaderIsActive = 'Y' {$where_status}";
	  $filter_search = '';
	  if ($nolab != "") {
         $filter_search = " AND (T_OrderHeaderLabNumber LIKE CONCAT('%','{$nolab}','%') OR T_OrderHeaderLabNumberExt LIKE CONCAT('%','{$nolab}','%') OR M_PatientName LIKE CONCAT('%','{$nolab}','%'))";
      }

		$sql = 	"SELECT T_OrderHeaderID,T_SamplingSoID as T_BarcodeLabID,T_SampleTypeName as T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						T_OrderHeaderLabNumberExt,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,T_TestID,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date, 
						T_OrderHeaderM_PatientAge as umur,
						T_SampleStationID, T_SampleTypeID,
						T_OrderDetailT_TestName as testname,
						{$stationid} as stationid,
						T_SamplingSoID,
						T_SamplingSoFlagSend as status,
						fn_result_so_status_by_sample(T_SamplingSoID) as status_result,
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements,
						'' as barcodes,
						IF(ISNULL(readdoctor.M_DoctorName),'-',CONCAT(readdoctor.M_DoctorPrefix,readdoctor.M_DoctorPrefix2,' ',readdoctor.M_DoctorName,' ',readdoctor.M_DoctorSufix,readdoctor.M_DoctorSufix2,readdoctor.M_DoctorSufix3)) as doctor_fullname,
						CONCAT(sender.M_DoctorPrefix,sender.M_DoctorPrefix2,' ',sender.M_DoctorName,' ',sender.M_DoctorSufix,sender.M_DoctorSufix2,sender.M_DoctorSufix3) as doctor_sender,
						T_SamplingSoFlagDoctorInOffice as flagdoctorinoffice,
						T_SamplingSoM_DoctorID,
						T_OrderDetailID
				FROM t_orderheader	
				JOIN m_doctor sender ON T_OrderHeaderSenderM_DoctorID = sender.M_DoctorID
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
									 (('{$statusid}' = 'R' AND T_SamplingSoFlagSend = 'R' AND T_SamplingSoM_DoctorID = 0 ) OR ('{$statusid}' = 'C' AND T_SamplingSoFlagSend = 'R' AND T_SamplingSoM_DoctorID <> 0 )) AND
									 T_SamplingSoIsActive = 'Y' 
				
				LEFT JOIN m_doctor readdoctor ON T_SamplingSoM_DoctorID = readdoctor.M_DoctorID
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
								T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
								T_SamplingSoRequirementNat_PositionID = 9 AND
								T_SamplingSoRequirementIsActive = 'Y'
				$sql_where $filter_search
				GROUP BY T_SamplingSoID
				ORDER BY iscito ASC,T_SamplingSoReceiveAdmDate ASC, T_SamplingSoReceiveAdmTime ASC,T_OrderHeaderID ASC
				$limit
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
				$zxprm['samplingso_id'] = $v['T_SamplingSoID'];
				$rows[$k]['requirements'] = $this->getrequirements($zxprm);
				$rows[$k]['barcodes'] = $this->getbarcodes($v['T_OrderHeaderID']);
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
	  
      $name = isset($prm["name"])?$prm["name"]:'';
	  $nolab = $prm["nolab"];
	  $stationid = $prm["stationid"];
	  $statusid = $prm["statusid"];
	  $where_status = '';
	  $limit = '';
	  //echo $statusid;
	  if($statusid == 'C'){
		  $limit = 'LIMIT 50';
	  }
	  //echo $limit = 'LIMIT 5';
      $sql_where = "WHERE T_OrderHeaderIsActive = 'Y' {$where_status}";
	  $filter_search = '';
	  if ($nolab != "") {
         $filter_search = " AND (T_OrderHeaderLabNumber LIKE CONCAT('%','{$nolab}','%') OR T_OrderHeaderLabNumberExt LIKE CONCAT('%','{$nolab}','%') OR M_PatientName LIKE CONCAT('%','{$nolab}','%'))";
      }

		$sql = 	"SELECT T_OrderHeaderID,T_SamplingSoID as T_BarcodeLabID,T_SampleTypeName as T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						T_OrderHeaderLabNumberExt,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,T_TestID,
						'' as M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date, 
						T_OrderHeaderM_PatientAge as umur,
						T_SampleStationID, T_SampleTypeID,
						T_TestName as testname,
						T_SamplingSoT_SampleStationID  as stationid,
						T_SamplingSoID,
						T_SamplingSoFlagSend as status,
						fn_result_so_status_by_sample(T_SamplingSoID) as status_result,
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements,
						'' as barcodes,
						IF(ISNULL(readdoctor.M_DoctorName),'-',CONCAT(readdoctor.M_DoctorPrefix,readdoctor.M_DoctorPrefix2,' ',readdoctor.M_DoctorName,' ',readdoctor.M_DoctorSufix,readdoctor.M_DoctorSufix2,readdoctor.M_DoctorSufix3)) as doctor_fullname,
						CONCAT(sender.M_DoctorPrefix,sender.M_DoctorPrefix2,' ',sender.M_DoctorName,' ',sender.M_DoctorSufix,sender.M_DoctorSufix2,sender.M_DoctorSufix3) as doctor_sender,
						T_SamplingSoFlagDoctorInOffice as flagdoctorinoffice,
						T_SamplingSoM_DoctorID,
						T_OrderDetailID
FROM t_samplingso
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID 
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN t_orderdetail ON T_SamplingSoT_TestID = T_OrderDetailT_TestID AND T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
JOIN m_doctor sender ON T_OrderHeaderSenderM_DoctorID = sender.M_DoctorID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				LEFT JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN t_samplestation ON T_SampleStationID = T_SamplingSoT_SampleStationID 
				LEFT JOIN m_doctor readdoctor ON T_SamplingSoM_DoctorID = readdoctor.M_DoctorID
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
								T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
								T_SamplingSoRequirementNat_PositionID = 9 AND
								T_SamplingSoRequirementIsActive = 'Y'
				WHERE
				T_OrderHeaderIsActive = 'Y' AND T_SamplingSoT_SampleStationID = {$stationid} AND T_SamplingSoFlag = 'V' AND 
				(( T_SamplingSoFlagSend = 'R' AND T_SamplingSoIsActive = 'Y' AND T_SamplingSoM_DoctorID = 0 AND  '{$statusid}' = 'R' ) OR
				('{$statusid}' = 'C' AND T_SamplingSoFlagSend = 'R' AND T_SamplingSoM_DoctorID <> 0 ))
				$filter_search
				ORDER BY iscito ASC,T_SamplingSoReceiveAdmDate ASC, T_SamplingSoReceiveAdmTime ASC,T_OrderHeaderID ASC
				$limit
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
				$zxprm['samplingso_id'] = $v['T_SamplingSoID'];
				$rows[$k]['requirements'] = $this->getrequirements($zxprm);
				$rows[$k]['barcodes'] = $this->getbarcodes($v['T_OrderHeaderID']);
			}
		}
		//$this->_add_address($rows);
		$result = array("total" => count($rows), "records" => $rows, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }
   
   function getbarcodes($orderid){
	   $sql = "	SELECT T_TestName,
				T_OrderHeaderLabNumber,
				M_PatientNoReg,
				M_PatientName,
				CONCAT(IFNULL(M_TitleName,''),' ',M_PatientName) as patient_fullname,
				DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y') as order_date,
				T_OrderHeaderM_PatientAge as umur,
				fn_get_patient_first_address(M_PatientID) as alamat,
				IF(ISNULL(M_DoctorName),'-',CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3)) as doctor_fullname,
				M_CompanyName
				FROM t_orderdetail
				JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				LEFT JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN documentation_group_detail ON DocumentationGroupDetailNat_SubGroupID = T_TestNat_SubGroupID 
				JOIN documentation_group ON DocumentationGroupDetailDocumentationGroupID = DocumentationGroupID AND 
					( DocumentationGroupName <> 'lab' AND DocumentationGroupName <> 'other' )
				WHERE
					T_OrderHeaderID =  {$orderid} AND T_OrderDetailIsActive = 'Y'
				GROUP BY T_TestID";
				//echo $sql;
		$rst = $this->db_onedev->query($sql)->result_array();
		return $rst;
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
		$prm = $this->sys_input;
		
		$rows = [];
		$query ="	SELECT T_SampleStationID as id, T_SampleStationName as name
					FROM t_samplestation 
					WHERE	
						T_SampleStationIsActive = 'Y' AND T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
				";
				//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		$rows['statuses'] = array(array('id'=>'R','name'=>'Sudah Terima'),array('id'=>'C','name'=>'Selesai'));
		$rows['doctors'] = array();
		
		  
		
		$result = array(
			"total" => count($rows) , 
			"records" => $rows, 
		); 
		$this->sys_ok($result);
		exit;
	}
	
	
	function getdoctorbystation(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$rows= [];
		$sql = "
                        SELECT M_DoctorID as id, M_DoctorName, CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as name, '' as address
                        FROM m_doctor
						JOIN m_doctorso ON M_DoctorSOM_DoctorID = M_DoctorID AND M_DoctorSOIsActive = 'Y'
						JOIN t_test ON T_TestNat_SubgroupID = M_DoctorSONat_SubGroupID AND T_TestIsActive = 'Y'
						JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID AND T_SampleTypeIsActive = 'Y'
						JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID AND T_BahanIsActive = 'Y'
						JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$prm['stationid']}					
                        WHERE
                        M_DoctorIsActive = 'Y'
						GROUP BY M_DoctorID
                        ORDER BY M_DoctorName DESC
                  ";
				 // echo $sql;
		$query = $this->db_onedev->query($sql);
		if ($query) {
			$rows = $query->result_array();
			foreach($rows as $k => $v){
				$rows[$k]['address'] = $this->db_onedev->query("SELECT * FROM m_doctoraddress WHERE M_DoctorAddressM_DoctorID = {$v['id']} AND M_DoctorAddressIsActive = 'Y'")->result_array();
			}
			$rows['doctors'] = $rows;
		}
		
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
		$statusid = $prm["statusid"];

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
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements,
						IF(ISNULL(M_DoctorName),'-',CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,'...')) as doctor_fullname,
						T_SamplingSoFlagDoctorInOffice as flagdoctorinoffice,
						T_SamplingSoM_DoctorID
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
									 (('{$statusid}' = 'R' AND T_SamplingSoFlagSend = 'R' AND T_SamplingSoM_DoctorID = 0 ) OR ('{$statusid}' = 'C' AND T_SamplingSoFlagSend = 'R' AND T_SamplingSoM_DoctorID <> 0 )) AND
									 T_SamplingSoIsActive = 'Y' 
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
				
			}
		}
		
		if($prm['act'] == 'cancel'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_samplingso SET 
							T_SamplingSoFlagSend = 'N', 
							T_SamplingSoReceiveAdmDate = NULL,
							T_SamplingSoReceiveAdmTime = NULL,
							T_SamplingSoReceiveAdmUserID = NULL,
							T_SamplingSoFlag = 'Z', 
							T_SamplingSoSendAdmDate = NULL,
							T_SamplingSoSendAdmTime = NULL,
							T_SamplingSoSendAdmUserID = NULL,
							T_SamplingSoVerifyDate = NULL, 
							T_SamplingSoVerifyTime = NULL,
							T_SamplingSoVerifyUserID = NULL,
							T_SamplingSoUserID = {$userid}
						WHERE
						T_SamplingSoID = {$v['T_SamplingSoID']}";
				$this->db_onedev->query($sql);
			}
		}
		
		if($prm['act'] == 'reject'){
			foreach($samples as $k=>$v){
				$sql = "UPDATE t_samplingso SET 
							T_SamplingSoFlagSend = 'D', 
							T_SamplingSoSendAdmDate = NULL,
							T_SamplingSoSendAdmTime = NULL,
							T_SamplingSoSendAdmUserID = NULL,
							T_SamplingSoVerifyDate = NULL, 
							T_SamplingSoVerifyTime = NULL,
							T_SamplingSoVerifyUserID = NULL,
							T_SamplingSoUserID = {$userid}
						WHERE
						T_SamplingSoID = {$v['T_SamplingSoID']}";
				$this->db_onedev->query($sql);
			}
		}
		
		if($prm['act'] == 'removedoctor'){
			foreach($samples as $k => $v){
				$query ="	UPDATE t_samplingso SET 
						T_SamplingSoFlagDoctorInOffice = 'N',
						T_SamplingSoM_DoctorID = 0,
						T_SamplingSoM_DoctorAddressID = 0
					WHERE
					T_SamplingSoID = {$v['T_SamplingSoID']}";
				//echo $query;
				$savedoctor = $this->db_onedev->query($query);
			}
		}
		
		
		
		
		$result = array(
			"total" => 1 , 
			"records" => array('status'=>'OK')
		); 
		$this->sys_ok($result);
		
		exit;
	}
	
	function searchdoctor(){
      if (! $this->isLogin) {
         $this->sys_error("Invalid Token");
         exit;
      }
      $prm = $this->sys_input;

      $max_rst = 12;
      $tot_count =0;

      $q = [
         'search'     => '%'
      ];

      if ($prm['search'] != '')
      {
         $q['search'] = "%{$prm['search']}%";
      }

      // QUERY TOTAL
      $sql = "SELECT count(*) as total
                        FROM (
							SELECT M_DoctorID as id, CONCAT(M_DoctorPrefix,M_DoctorPrefix2,M_DoctorName,M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as name, '' as address
                        FROM m_doctor
						JOIN m_doctorso ON M_DoctorSOM_DoctorID = M_DoctorID AND M_DoctorSOIsActive = 'Y'
						JOIN t_test ON T_TestNat_SubgroupID = M_DoctorSONat_SubGroupID AND T_TestIsActive = 'Y'
						JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID AND T_SampleTypeIsActive = 'Y'
						JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID AND T_BahanIsActive = 'Y'
						JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$prm['stationid']}					
                        WHERE
                        M_DoctorName like ?
                        AND M_DoctorIsActive = 'Y'
						GROUP BY M_DoctorID
						)xx";
      $query = $this->db_onedev->query($sql,$q['search']);
      //echo $query;
      if ($query) {
         $tot_count = $query->result_array()[0]["total"];
      }
      else {
         $this->sys_error_db("m_city count",$this->db_onedev);
         exit;
      }

      $sql = "
                        SELECT M_DoctorID as id, M_DoctorName, CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as name, '' as address
                        FROM m_doctor
						JOIN m_doctorso ON M_DoctorSOM_DoctorID = M_DoctorID AND M_DoctorSOIsActive = 'Y'
						JOIN t_test ON T_TestNat_SubgroupID = M_DoctorSONat_SubGroupID AND T_TestIsActive = 'Y'
						JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID AND T_SampleTypeIsActive = 'Y'
						JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID AND T_BahanIsActive = 'Y'
						JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$prm['stationid']}					
                        WHERE
                        CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) like ?
                        AND M_DoctorIsActive = 'Y'
						GROUP BY M_DoctorID
                        ORDER BY M_DoctorName DESC
                  ";
				  //echo $sql;
      $query = $this->db_onedev->query($sql, array($q['search']));

      if ($query) {
         $rows = $query->result_array();
		 foreach($rows as $k => $v){
			 $rows[$k]['address'] = $this->db_onedev->query("SELECT * FROM m_doctoraddress WHERE M_DoctorAddressM_DoctorID = {$v['id']} AND M_DoctorAddressIsActive = 'Y'")->result_array();
		 }
         //echo $this->db_onedev->last_query();
         $result = array("total" => $tot_count, "records" => $rows, "total_display" => sizeof($rows));
         $this->sys_ok($result);
      }
      else {
         $this->sys_error_db("m_city rows",$this->db_onedev);
         exit;
      }
   }
   
   function savedoctor(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$sel_patient = $prm['selected_patient'];
		$sel_doctor = $prm['selected_doctor'];
		$sel_doctor_address = $prm['selected_doctor_address'];
		$flagdoctorinoffice = $prm['flagdoctorinoffice'] == true ? 'Y':'N';
		foreach($sel_patient as $k => $v){
				$query ="	UPDATE t_samplingso SET 
						T_SamplingSoFlagDoctorInOffice = '{$flagdoctorinoffice}',
						T_SamplingSoM_DoctorID = {$sel_doctor['id']},
						T_SamplingSoM_DoctorAddressID = {$sel_doctor_address['M_DoctorAddressID']} 
					WHERE
					T_SamplingSoID = {$v['T_SamplingSoID']}";
				//echo $query;
				$savedoctor = $this->db_onedev->query($query);
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
								'SAMPLING.Handling.Process',
								NOW(),
								{$userid}
							)";
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
								'SAMPLING.Handling.To.Process',
								NOW(),
								{$userid}
							)";
				$this->db_onedev->query($sql);
		}
		
		if($savedoctor){
			$result = array(
				"total" => 1 , 
				"records" => array(), 
			); 
			$this->sys_ok($result);
			exit;
		}
		else{
			$this->sys_error_db("doctor update", $this->db_onedev);
			exit;
		}
		
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