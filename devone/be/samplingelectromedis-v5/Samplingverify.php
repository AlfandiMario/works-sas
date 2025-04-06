<?php
class Samplingverify extends MY_Controller
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
	  
      $name = $prm["name"];
	  $nolab = $prm["nolab"];
	  $stationid = $prm["stationid"];
	  $statusid = $prm["statusid"];
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
	  $filter_search = '';
	  if ($nolab != "") {
         $filter_search = " AND ( M_PatientName like '%$nolab%' OR T_OrderHeaderLabNumber LIKE '%$nolab%' OR T_OrderHeaderLabNumberExt LIKE '%$nolab%')";
      }

		$sql_where_cito = "WHERE T_OrderHeaderIsCito = 'Y' AND T_OrderHeaderIsActive = 'Y' {$where_status} {$filter_search}";
		$sql_where = "WHERE T_OrderHeaderIsCito <> 'Y' AND T_OrderHeaderIsActive = 'Y' {$where_status} {$filter_search}";
	  
		$sql = 	"SELECT T_OrderHeaderID,T_TestName as T_SampleTypeName,T_OrderHeaderLabNumber,
						T_OrderHeaderLabNumberExt,
						CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as doctor_fullname,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						T_OrderHeaderM_PatientAge as umur,
						DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
						M_CompanyName,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, 
						T_TestID as T_SampleTypeID,
						{$stationid} as stationid,
						T_TestName as testname,
						T_SampleTypeName as T_BarcodeLabBarcode,
						T_SamplingSoID,
						T_SamplingSoFlag as status,
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements
				FROM t_orderheader	
				JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
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
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
					T_SamplingSoT_TestID = T_TestID AND 
					(T_SamplingSoFlag = 'D' OR T_SamplingSoFlag = 'Z') AND 
					T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
								T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
								T_SamplingSoRequirementNat_PositionID = 9 AND
								T_SamplingSoRequirementIsActive = 'Y'
				$sql_where_cito
				GROUP BY T_SamplingSoID
				ORDER BY T_SamplingSoDoneDate ASC, T_SamplingSoDoneTime ASC, T_OrderHeaderID ASC
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
				$zxprm['sampletypeid'] = $v['T_TestID'];
				$zxprm['samplingso_id'] = $v['T_SamplingSoID'];
				$rows_cito[$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}
		
		$sql = 	"SELECT T_OrderHeaderID,
					T_OrderHeaderLabNumberExt, T_TestName as T_SampleTypeName,T_OrderHeaderLabNumber,
						IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
						M_SexName, M_TitleName, 
						CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as doctor_fullname,
						CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
						M_CompanyName,
						T_OrderHeaderM_PatientAge as umur,
						T_SampleTypeName as T_BarcodeLabBarcode,
						DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob, 
						T_SampleStationID, 
						T_TestID as T_SampleTypeID,
						{$stationid} as stationid,
						T_TestName as testname,
						T_SamplingSoID,
						T_SamplingSoFlag as status,
						T_OrderHeaderIsCito as iscito,
						IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
						'' as requirements
				FROM t_orderheader	
				JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
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
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
					T_SamplingSoT_TestID = T_TestID AND 
					(T_SamplingSoFlag = 'D' OR T_SamplingSoFlag = 'Z') AND 
					T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
								T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
								T_SamplingSoRequirementNat_PositionID = 9 AND
								T_SamplingSoRequirementIsActive = 'Y'
				$sql_where
				GROUP BY T_SamplingSoID
				ORDER BY T_SamplingSoDoneDate ASC, T_SamplingSoDoneTime ASC, T_OrderHeaderID ASC
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
				$zxprm['sampletypeid'] = $v['T_TestID'];
				$zxprm['samplingso_id'] = $v['T_SamplingSoID'];
				$rows_not_cito[$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}
		$rst = array_merge($rows_cito,$rows_not_cito);
		//$this->_add_address($rows);
		$result = array("total" => count($rst), "records" => $rst, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }
   
   function getrequirements($prm){
		
		
		
	$sql = "
			SELECT Nat_RequirementID as id, 
				Nat_RequirementName as name, '{$prm['status']}' as status,
				if(ISNULL(T_SamplingSoRequirementID),'N', 
				if(json_contains(T_SamplingSoRequirementRequirements,Nat_RequirementID),'Y','N')) as chex, 
				Nat_RequirementPositionNat_PositionID as positionid
				FROM nat_requirement
				JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
				JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND 
					Nat_RequirementPositionNat_PositionID = 9 AND 
					Nat_RequirementPositionIsActive = 'Y'
				JOIN t_test ON T_TestNat_TestID = Nat_TestRequirementNat_TestID
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_SamplingSoID = {$prm['samplingso_id']} AND 
					T_SamplingSoRequirementT_OrderHeaderID = {$prm['orderid']} AND
					T_SamplingSoRequirementNat_PositionID = Nat_RequirementPositionNat_PositionID
				WHERE	
					Nat_TestRequirementIsActive = 'Y'
				GROUP BY nat_requirementID
				UNION
                SELECT Nat_RequirementID as id, 
				Nat_RequirementName as name, '{$prm['status']}' as status,
				if(ISNULL(T_SamplingSoRequirementID),'N', 
				if(json_contains(T_SamplingSoRequirementRequirements,Nat_RequirementID),'Y','N')) as chex, 
				Nat_RequirementPositionNat_PositionID as positionid
				FROM nat_requirement
				JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
				JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND 
					Nat_RequirementPositionNat_PositionID = 9 AND 
					Nat_RequirementPositionIsActive = 'Y' AND 
					Nat_RequirementIsAllTest = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_SamplingSoID = {$prm['samplingso_id']} AND 
					T_SamplingSoRequirementT_OrderHeaderID = {$prm['orderid']} AND
					T_SamplingSoRequirementNat_PositionID = Nat_RequirementPositionNat_PositionID
                GROUP BY nat_requirementID
		";
				
				//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();	
		

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

		//$next_status = $prm['statusnextid'];
		
		if($prm['act'] == 'reject'){
			$sql = "INSERT INTO t_samplingso (
						T_SamplingSoT_OrderHeaderID,
						T_SamplingSoT_TestID,
						T_SamplingSoT_SampleStationID,
						T_SamplingSoUserID
					)
					VALUES(
						{$prm['orderid']},
						{$prm['sampleid']},
						{$prm['stationid']},
						{$userid}
					) ON DUPLICATE KEY UPDATE 
						T_SamplingSoDoneDate = NULL, 
						T_SamplingSoDoneTime = NULL,
						T_SamplingSoDoneUserID = NULL,
						T_SamplingSoProcessDate = NULL, 
						T_SamplingSoProcessTime = NULL,
						T_SamplingSoProcessUserID = NULL,
						T_SamplingSoFlag = 'X',
						T_SamplingSoIsActive = 'Y',
						T_SamplingSoUserID = {$userid}";
					//	echo $sql;
			$this->db_onedev->query($sql);
			
			$sql = "INSERT INTO t_samplingso_form(
						T_SamplingSoFormT_SamplingSOID,
						T_SamplingSoFormKv,
						T_SamplingSoFormMa,
						T_SamplingSoFormSecond,
						T_SamplingSoFormExpose,
						T_SamplingSoFormUserID,
						T_SamplingSoFormCreated,
						T_SamplingSoFormLastUpdated
					)
					VALUES(
						{$prm['sample']['T_SamplingSoID']},
						{$prm['sample']['form_kv']},
						{$prm['sample']['form_ma']},
						{$prm['sample']['form_second']},
						{$prm['sample']['form_expose']},
						{$userid},
						NOW(),
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_SamplingSoFormT_SampleStationID = NULL,
							T_SamplingSoFormKv = NULL,
							T_SamplingSoFormMa = NULL,
							T_SamplingSoFormSecond = NULL,
							T_SamplingSoFormExpose = NULL,
							T_SamplingSoFormUserID = {$userid}";
					//echo $sql;
			$this->db_onedev->query($sql);
			
			$xreq = $prm['sample']['requirements'];
			$arr_requirements = array();
			foreach($xreq as $k=>$v){
				if($v['chex'] == 'Y')
					array_push($arr_requirements,$v['id']);
			}
			
			$requirements = '['.join(',',$arr_requirements).']';
			
			$sql = "INSERT INTO t_samplingso_requirement(
						T_SamplingSoRequirementT_OrderHeaderID,
						T_SamplingSoRequirementT_SampleStationID,
						T_SamplingSoRequirementT_SamplingSoID,
						T_SamplingSoRequirementNat_PositionID,
						T_SamplingSoRequirementStatus,
						T_SamplingSoRequirementRequirements,
						T_SamplingSoRequirementUserID,
						T_SamplingSoRequirementCreated
					)
					VALUES(
						{$prm['sample']['T_OrderHeaderID']},
						{$prm['stationid']},
						{$prm['sample']['T_SamplingSoID']},
						{$prm['sample']['requirements'][0]['positionid']},
						'{$prm['sample']['requirement_status']}',
						'{$requirements}',
						{$userid},
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_SamplingSoRequirementStatus = '{$prm['sample']['requirement_status']}',
							T_SamplingSoRequirementRequirements = '{$requirements}',
							T_SamplingSoRequirementUserID = {$userid}";
					//echo $sql;
			$this->db_onedev->query($sql);
			
			$sql = "INSERT INTO sample_so_by_step (
								SampleSoByStepT_OrderHeaderID,
								SampleSoByStepT_TestID,
								SampleSoByStepCode,
								SampleSoByStepRequirementStatus,
								SampleSoByStepReuirements,
								SampleSoByStepDateTime,
								SampleSoByStepUserID
							)
							VALUES(
								{$prm['orderid']},
								{$prm['sampleid']},
								'SAMPLING.Verification.Reject',
								'{$prm['sample']['requirement_status']}',
								'{$requirements}',
								NOW(),
								{$userid}
							)";
			$this->db_onedev->query($sql);
			
			$next_status = 2;
			$dt_json = json_encode(array('T_SampleStationID'=>$prm['stationid'],'T_OrderHeaderID'=>$prm['orderid'],'T_SamplingQueueStatusID'=>$next_status));
			$query = "INSERT INTO  one_log.log_sampling_queue (Log_SamplingQueueDate,Log_SamplingQueueJSON,Log_SamplingQueueUserID)
						VALUES(NOW(),'{$dt_json}',{$userid})";
					//echo $query;
			$rows = $this->db_onedev->query($query);
			$sql = "SELECT * 
					FROM t_sampling_queue_last_status 
					WHERE 
					T_SamplingQueueLastStatusT_SampleStationID = {$prm['stationid']} AND
					T_SamplingQueueLastStatusT_OrderHeaderID = {$prm['orderid']} AND 
					T_SamplingQueueLastStatusIsActive = 'Y'";
			$data_last = $this->db_onedev->query($sql)->row();
		
			$query = "INSERT INTO  t_sampling_queue_last_status (
					T_SamplingQueueLastStatusT_SampleStationID,
					T_SamplingQueueLastStatusT_OrderHeaderID,
					T_SamplingQueueLastStatusT_SamplingQueueStatusID,
					T_SamplingQueueLastStatusUserID)
				VALUES(
					{$prm['stationid']},
					{$prm['orderid']},
					{$next_status},
					{$userid}) ON DUPLICATE KEY UPDATE T_SamplingQueueLastStatusT_SamplingQueueStatusID = {$next_status}";
				//echo $query;
			$rows = $this->db_onedev->query($query);
		
		}

		if($prm['act'] == 'verify'){
			$sql = "INSERT INTO t_samplingso (
						T_SamplingSoT_OrderHeaderID,
						T_SamplingSoT_TestID,
						T_SamplingSoT_SampleStationID,
						T_SamplingSoUserID
					)
					VALUES(
						{$prm['orderid']},
						{$prm['sampleid']},
						{$prm['stationid']},
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_SamplingSoVerifyDate = CURDATE(), 
							T_SamplingSoVerifyTime = CURTIME(),
							T_SamplingSoVerifyUserID = {$userid},
							T_SamplingSoFlag = 'V',
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoUserID = {$userid}";
			$this->db_onedev->query($sql);
			
			$xreq = $prm['sample']['requirements'];
			$arr_requirements = array();
			foreach($xreq as $k=>$v){
				if($v['chex'] == 'Y')
					array_push($arr_requirements,$v['id']);
			}
			$requirements = '['.join(',',$arr_requirements).']';
			
			$sql = "INSERT INTO t_samplingso_requirement(
						T_SamplingSoRequirementT_SamplingSoID,
						T_SamplingSoRequirementT_OrderHeaderID,
						T_SamplingSoRequirementT_SampleStationID,
						T_SamplingSoRequirementNat_PositionID,
						T_SamplingSoRequirementStatus,
						T_SamplingSoRequirementRequirements,
						T_SamplingSoRequirementUserID,
						T_SamplingSoRequirementCreated
					)
					VALUES(
						{$prm['sample']['T_SamplingSoID']},
						{$prm['sample']['T_OrderHeaderID']},
						{$prm['stationid']},
						{$prm['sample']['requirements'][0]['positionid']},
						'{$prm['sample']['requirement_status']}',
						'{$requirements}',
						{$userid},
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_SamplingSoRequirementStatus = '{$prm['sample']['requirement_status']}',
							T_SamplingSoRequirementRequirements = '{$requirements}',
							T_SamplingSoRequirementUserID = {$userid}";
					//echo $sql;
			$this->db_onedev->query($sql);
			$sql = "INSERT INTO sample_so_by_step (
								SampleSoByStepT_OrderHeaderID,
								SampleSoByStepT_TestID,
								SampleSoByStepCode,
								SampleSoByStepRequirementStatus,
								SampleSoByStepReuirements,
								SampleSoByStepDateTime,
								SampleSoByStepUserID
							)
							VALUES(
								{$prm['orderid']},
								{$prm['sampleid']},
								'SAMPLING.Verification.Verify',
								'{$prm['sample']['requirement_status']}',
								'{$requirements}',
								NOW(),
								{$userid}
							)";
			$this->db_onedev->query($sql);
			
		}
		
		if($prm['act'] == 'unverify'){
			$sql = "INSERT INTO t_samplingso (
						T_SamplingSoT_OrderHeaderID,
						T_SamplingSoT_TestID,
						T_SamplingSoUserID
					)
					VALUES(
						{$prm['orderid']},
						{$prm['sampleid']},
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_SamplingSoVerifyDate = NULL, 
							T_SamplingSoVerifyTime = NULL,
							T_SamplingSoVerifyUserID = NULL,
							T_SamplingSoFlag = 'D',
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoUserID = {$userid}";
			$this->db_onedev->query($sql);
			
			$sql = "INSERT INTO t_samplingso_requirement(
						T_SamplingSoRequirementT_SamplingSoID,
						T_SamplingSoRequirementT_OrderHeaderID,
						T_SamplingSoRequirementT_SampleStationID,
						T_SamplingSoRequirementNat_PositionID,
						T_SamplingSoRequirementStatus,
						T_SamplingSoRequirementRequirements,
						T_SamplingSoRequirementUserID,
						T_SamplingSoRequirementCreated
					)
					VALUES(
						{$prm['sample']['T_SamplingSoID']},
						{$prm['sample']['T_OrderHeaderID']},
						{$prm['stationid']},
						{$prm['sample']['requirements'][0]['positionid']},
						'{$prm['sample']['requirement_status']}',
						'{$requirements}',
						{$userid},
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_SamplingSoRequirementStatus = 'X',
							T_SamplingSoRequirementRequirements = '[]',
							T_SamplingSoRequirementUserID = {$userid}";
					//echo $sql;
			$this->db_onedev->query($sql);
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