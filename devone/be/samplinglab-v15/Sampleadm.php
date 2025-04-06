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
   
   
   public function search()
   {
      $prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
	  
      $name = isset($prm["name"])?$prm["name"]:'';
	  $nolab = $prm["nolab"];
	  $sampletypeid = $prm["sampletypeid"];
	  $filter_sampletype = '';
	  if(intval($sampletypeid) > 0)
		  $filter_sampletype = " AND T_SampleTypeID = {$sampletypeid}";
	  $where_status = '';
	  $limit = '';
	
	  
      $sql_where_cito = "WHERE T_OrderHeaderIsCito = 'Y' AND T_OrderHeaderIsActive = 'Y'";
	  $sql_where = "WHERE T_OrderHeaderIsCito <> 'Y' AND T_OrderHeaderIsActive = 'Y'";
	  
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
						T_OrderSampleID as stationid,
						T_OrderSampleID,
						T_OrderSampleReadyToProcessDateTime,
						T_OrderSampleReceiveDate,
						T_OrderSampleReceiveTime,
						CASE
							WHEN T_OrderSampleProcessing = 'N' THEN 'X'
							WHEN T_OrderSampleProcessing = 'Y' AND T_OrderSampleHandling = 'N' THEN 'N'
							WHEN T_OrderSampleProcessing = 'Y' AND T_OrderSampleHandling = 'Y' THEN 'Y'
						END as status,
						'' as ready_text,
						'N' as status_ready,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						IF(ISNULL(T_OrderSampleReqID),'X',T_OrderSampleReqStatus) as requirement_status,
						'' as requirements,
						IF(fn_fo_ver_have_reqs(T_OrderHeaderID) = 0,'Y','N') as fo_ver_status_req,
						fn_fo_reg_have_reqs(T_OrderHeaderID) as fo_reg_status_req,
						fn_sampling_have_reqs(T_OrderHeaderID,T_SampleStationID,T_OrderSampleID,2) as sampling_status_req,
						fn_sampling_have_reqs(T_OrderHeaderID,T_SampleStationID,T_OrderSampleID,3) as sampling_ver_status_req
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
							T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND 
							T_OrderSampleReceiveHandling = 'Y' AND T_OrderSampleHandling = 'N' AND T_OrderSampleIsActive = 'Y'
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderHeaderID = T_OrderHeaderID AND 
								T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND
								T_OrderSampleReqNat_PositionID = 4 AND 
								T_OrderSampleReqT_SampleStationID = T_SampleStationID AND
								T_OrderSampleReqIsActive = 'Y'
				$sql_where_cito
				GROUP BY T_BarcodeLabID
				ORDER BY iscito DESC,T_OrderSampleReceiveHandlingDate ASC,T_OrderSampleReceiveHandlingTime ASC, T_OrderHeaderID ASC
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		//echo $this->db_onedev->last_query();
		$rows_cito = $query->result_array();
		if($rows_cito){
			foreach($rows_cito as $k => $v){
				$zxprm = array();
				$zxprm['status'] = $v['status'];
				$zxprm['orderid'] = $v['T_OrderHeaderID'];
				$zxprm['sampletypeid'] = $v['T_SampleTypeID'];
				$rows_cito[$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}
	  
		$sql = 	"SELECT T_OrderHeaderID,T_BarcodeLabID,T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, T_SampleTypeID,
						T_SampleStationID as stationid,
						T_OrderSampleID,
						T_OrderSampleReadyToProcessDateTime,
						T_OrderSampleReceiveDate,
						T_OrderSampleReceiveTime,
						CASE
							WHEN T_OrderSampleProcessing = 'N' THEN 'X'
							WHEN T_OrderSampleProcessing = 'Y' AND T_OrderSampleHandling = 'N' THEN 'N'
							WHEN T_OrderSampleProcessing = 'Y' AND T_OrderSampleHandling = 'Y' THEN 'Y'
						END as status,
						'' as ready_text,
						'N' as status_ready,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						IF(ISNULL(T_OrderSampleReqID),'X',T_OrderSampleReqStatus) as requirement_status,
						'' as requirements,
						IF(fn_fo_ver_have_reqs(T_OrderHeaderID) = 0,'Y','N') as fo_ver_status_req,
						fn_fo_reg_have_reqs(T_OrderHeaderID) as fo_reg_status_req,
						fn_sampling_have_reqs(T_OrderHeaderID,T_SampleStationID,T_OrderSampleID,2) as sampling_status_req,
						fn_sampling_have_reqs(T_OrderHeaderID,T_SampleStationID,T_OrderSampleID,3) as sampling_ver_status_req
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
							T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND 
							T_OrderSampleReceiveHandling = 'Y' AND T_OrderSampleHandling = 'N' AND T_OrderSampleIsActive = 'Y'
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderHeaderID = T_OrderHeaderID AND 
								T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND
								T_OrderSampleReqNat_PositionID = 4 AND 
								T_OrderSampleReqT_SampleStationID = T_SampleStationID AND
								T_OrderSampleReqIsActive = 'Y'
				$sql_where
				GROUP BY T_BarcodeLabID
				ORDER BY T_OrderSampleReceiveHandlingDate ASC,T_OrderSampleReceiveHandlingTime ASC, T_OrderHeaderID ASC
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		//echo $this->db_onedev->last_query();
		$rows_not_cito = $query->result_array();
		if($rows_not_cito){
			foreach($rows_not_cito as $k => $v){
				$zxprm = array();
				$zxprm['status'] = $v['status'];
				$zxprm['orderid'] = $v['T_OrderHeaderID'];
				$zxprm['sampletypeid'] = $v['T_SampleTypeID'];
				$rows_not_cito[$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}
		//$this->_add_address($rows);
		$rst = array_merge($rows_cito,$rows_not_cito);
		$result = array("total" => count($rst), "records" => $rst, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }
   
   function getrequirements($prm){
		
		
		$query = "
				SELECT Nat_RequirementID as id, 
				Nat_RequirementName as name, '{$prm['status']}' as status,
				if(ISNULL(T_OrderSampleReqID),'N', if(json_contains(T_OrderSampleReqs,Nat_RequirementID),'Y','N') ) as chex, 
				Nat_RequirementPositionNat_PositionID as positionid
					FROM nat_requirement
					JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
					JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND Nat_RequirementPositionNat_PositionID = 4 AND 
						 Nat_RequirementPositionIsActive = 'Y'
					JOIN t_test ON T_TestNat_TestID = Nat_TestRequirementNat_TestID
					JOIN t_barcodelab ON T_barcodeLabT_OrderHeaderID = {$prm['orderid']} AND T_BarcodeLabT_SampleTypeID = {$prm['sampletypeid']}
					JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_barcodeLabT_OrderHeaderID AND T_OrderSampleT_SampleTypeID = T_BarcodeLabT_SampleTypeID AND 
					T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleIsActive = 'Y'
					LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND T_OrderSampleReqT_OrderHeaderID = T_barcodeLabT_OrderHeaderID AND
							T_OrderSampleReqNat_PositionID = Nat_RequirementPositionNat_PositionID
					WHERE	
						Nat_TestRequirementIsActive = 'Y'
					GROUP BY nat_requirementID
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
		$rows['statuses'] = array(array('id'=>'N','name'=>'Sudah Terima'),array('id'=>'Y','name'=>'Selesai'));
		$sql = "SELECT T_SampleTypeID as id, T_SampletypeName as name
				FROM t_ordersample
				JOIN t_sampletype ON T_OrderSampleT_sampleTypeID = T_sampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationIsNonLab = ''
				WHERE
				T_OrderSampleReceiveHandling = 'Y' AND 
				T_OrderSampleHandling = 'N' AND 
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
		$statusid = $prm["statusid"];

		$sql_where = "WHERE T_OrderHeaderLabNumber = '{$search}' AND T_OrderHeaderIsActive = 'Y'";
		$rows = [];
		$sql = 	"SELECT T_OrderHeaderID,T_BarcodeLabID,T_BarcodeLabBarcode,T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, T_SampleTypeID,
						{$stationid} as stationid,
						T_OrderSampleID,
						T_OrderSampleSendHandling as status,
						fn_global_check_is_cito(T_OrderHeaderID) as iscito,
						IF(ISNULL(T_OrderSampleReqID),'X',T_OrderSampleReqStatus) as requirement_status,
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
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = ''
				JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
							T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND 
							T_OrderSampleSendHandling = 'R' AND T_OrderSampleHandling = '{$statusid}' AND T_OrderSampleIsActive = 'Y'
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderHeaderID = T_OrderHeaderID AND 
								T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND
								T_OrderSampleReqNat_PositionID = 4 AND 
								T_OrderSampleReqT_SampleStationID = T_SampleStationID AND
								T_OrderSampleReqIsActive = 'Y'
				$sql_where
				GROUP BY T_BarcodeLabID
				ORDER BY iscito DESC,T_OrderPromiseDateTime ASC, T_OrderHeaderID ASC, T_OrderSampleID ASC
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
		
		if($prm['act'] == 'reject'){
			$sql = "UPDATE t_ordersample SET 
							T_OrderSampleReceiveHandling = 'N', 
							T_OrderSampleReceiveHandlingDate = NULL,
							T_OrderSampleReceiveHandlingTime = NULL,
							T_OrderSampleReceiveHandlingUserID = NULL,
							T_OrderSampleSendHandling = 'N', 
							T_OrderSampleSendHandlingDate = NULL,
							T_OrderSampleSendHandlingTime = NULL,
							T_OrderSampleSendHandlingUserID = NULL,
							T_OrderSampleVerificationDate = NULL, 
							T_OrderSampleVerificationTime = NULL,
							T_OrderSampleVerificationUserID = NULL,
							T_OrderSampleVerification = 'X',
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleID = {$prm['sample']['T_OrderSampleID']}";
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
							'SAMPLING.Handling.Reject',
							{$prm['sample']['T_OrderHeaderID']},
							{$prm['sample']['T_BarcodeLabID']},
							'Y',
							'[]',
							{$userid},
							NOW()
						)";
				$this->db_onedev->query($sql);
		}
		
		if($prm['act'] == 'process'){
			$sql = "UPDATE t_ordersample SET 
							T_OrderSampleProcessing = 'Y', 
							T_OrderSampleProcessingDate = CURDATE(),
							T_OrderSampleProcessingTime = CURTIME(),
							T_OrderSampleProcessingUserID = {$userid},
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleID = {$prm['sample']['T_OrderSampleID']}";
			//echo $sql;
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
							'SAMPLING.Handling.Process',
							{$prm['sample']['T_OrderHeaderID']},
							{$prm['sample']['T_BarcodeLabID']},
							'Y',
							'[]',
							{$userid},
							NOW()
						)";
				$this->db_onedev->query($sql);
		}
		
		
		if($prm['act'] == 'handling'){
			$sql = "UPDATE t_ordersample SET 
							T_OrderSampleHandling = 'Y', 
							T_OrderSampleHandlingDate = CURDATE(),
							T_OrderSampleHandlingTime = CURTIME(),
							T_OrderSampleHandlingUserID = {$userid},
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleID = {$prm['sample']['T_OrderSampleID']}";
			$this->db_onedev->query($sql);
			
			$xreq = $prm['sample']['requirements'];
			$arr_requirements = array();
			foreach($xreq as $k=>$v){
				if($v['chex'] == 'Y')
					array_push($arr_requirements,$v['id']);
			}
			$requirements = '['.join(',',$arr_requirements).']';
			
			$sql = "INSERT INTO t_ordersamplereq(
						T_OrderSampleReqT_OrderHeaderID,
						T_OrderSampleReqT_SampleStationID,
						T_OrderSampleReqT_OrderSampleID,
						T_OrderSampleReqNat_PositionID,
						T_OrderSampleReqStatus,
						T_OrderSampleReqs,
						T_OrderSampleReqUserID,
						T_OrderSampleReqCreated
					)
					VALUES(
						{$prm['sample']['T_OrderHeaderID']},
						{$prm['stationid']},
						{$prm['sample']['T_OrderSampleID']},
						{$prm['sample']['requirements'][0]['positionid']},
						'{$prm['sample']['requirement_status']}',
						'{$requirements}',
						{$userid},
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_OrderSampleReqStatus = '{$prm['sample']['requirement_status']}',
							T_OrderSampleReqs = '{$requirements}',
							T_OrderSampleReqUserID = {$userid}";
					//echo $sql;
                        $this->db_onedev->query($sql);
                        $this->broadcast("sample-handling-done");
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
							'SAMPLING.Handling.To.Process',
							{$prm['sample']['T_OrderHeaderID']},
							{$prm['sample']['T_BarcodeLabID']},
							'{$prm['sample']['requirement_status']}',
							'{$requirements}',
							{$userid},
							NOW()
						)";
			$this->db_onedev->query($sql);
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
	
	
	function getdatanoterequirement(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst_data = array();	
		$prm = $this->sys_input;
		
		
		
		$sql = "SELECT 'fo registration' as position,GROUP_CONCAT(DISTINCT Nat_RequirementName separator ',') as requirements
				FROM t_orderheader
				JOIN t_orderreq ON T_OrderReqT_OrderHeaderID = T_OrderHeaderID
				JOIN nat_requirement ON json_contains(T_OrderReqs,Nat_RequirementID)
				WHERE T_OrderHeaderID = {$prm['T_OrderHeaderID']}
				GROUP BY T_OrderHeaderID";
		//echo $sql;
		$query = $this->db_onedev->query($sql)->row_array();
		if($query){
			array_push($rst_data,$query);
		}
		
		$sql = "SELECT  'fo verifikasi' as position, GROUP_CONCAT(DISTINCT Fo_VerificationsLabelName separator ',') as requirements
				FROM fo_verificationsvalue
				JOIN fo_verificationslabel ON Fo_VerificationsValueFo_VerificationsLabelID = Fo_VerificationsLabelID
				WHERE
				Fo_VerificationsValueCheck = 'N' AND 
				Fo_VerificationsValueT_OrderHeaderID = {$prm['T_OrderHeaderID']}
				GROUP BY Fo_VerificationsValueT_OrderHeaderID
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql)->row_array();
		if($query){
			array_push($rst_data,$query);
		}
		
		$sql = "SELECT  
				Nat_PositionName as position, GROUP_CONCAT(DISTINCT Nat_RequirementName separator ',') as requirements
				FROM t_ordersamplereq
				JOIN nat_requirement ON json_contains(T_OrderSampleReqs,Nat_RequirementID)
				JOIN nat_position ON Nat_PositionID = 2
				WHERE T_OrderSampleReqT_OrderHeaderID = {$prm['T_OrderHeaderID']} AND T_OrderSampleReqT_OrderSampleID = {$prm['T_OrderSampleID']} AND 
				T_OrderSampleReqNat_PositionID = 2
				
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql)->result_array();
		if($query){
			foreach($query as $k => $v){
				if($v['requirements'] != null)
				array_push($rst_data,$v);
			}
		}
		
		$sql = "SELECT  
				Nat_PositionName as position, GROUP_CONCAT(DISTINCT Nat_RequirementName separator ',') as requirements
				FROM t_ordersamplereq
				JOIN nat_requirement ON json_contains(T_OrderSampleReqs,Nat_RequirementID)
				JOIN nat_position ON Nat_PositionID = 3
				WHERE T_OrderSampleReqT_OrderHeaderID = {$prm['T_OrderHeaderID']} AND T_OrderSampleReqT_OrderSampleID = {$prm['T_OrderSampleID']} AND 
				T_OrderSampleReqNat_PositionID = 3
				
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql)->result_array();
		if($query){
			foreach($query as $k => $v){
				if($v['requirements'] != null)
				array_push($rst_data,$v);
			}
		}
		
		$result = array(
			"total" => 1 , 
			"records" => $rst_data
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
