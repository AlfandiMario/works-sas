<?php
class Preanalitic extends MY_Controller
{
   var $db_onedev;
   public function index()
   {
      echo "Resultentry API";
   }
   
   public function __construct()
   {
      parent::__construct();
      $this->db_onedev = $this->load->database("onedev", true);
	  $this->load->helper(array('form', 'url'));
   }
   
   function getdetails($id){
		$rows  = [];

		$sql = "SELECT so_walklettercourierdetail.*, M_SexName, 
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname,
					T_SampleTypeName as samplename,
					T_OrderHeaderLabNumber as labnumber, 
					'Y' as active, 
					So_WalkLetterCourierDetailFlagImage as flag_image,
					So_WalkLetterCourierDetailFlagReceiveImage as flag_image_receive,
					So_WalkLetterCourierDetailFlagReceiveResult as flag_result_receive,
					So_WalkLetterCourierDetailID as idx,
					T_OrderHeaderID as orderid,
					T_SampleTypeID as sampleid
					FROM so_walklettercourierdetail 
					JOIN t_orderheader ON So_WalkLetterCourierDetailT_OrderHeaderID = T_OrderHeaderID
					JOIN t_sampletype ON So_WalkLetterCourierDetailT_SampleTypeID = T_SampleTypeID
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					WHERE	
						So_WalkLetterCourierDetailSo_WalkLetterCourierID = {$id} AND So_WalkLetterCourierDetailIsActive = 'Y'";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();
		return $rows;
		
   }
   
   function getdeliveries($orderid){
		$query ="	SELECT T_OrderDeliveryID as id, 
					IFNULL(Fo_VerificationDeliveryID,0) as xid,
					M_DeliveryTypeCode as code,
					IF(ISNULL(Fo_VerificationDeliveryID),'N',Fo_VerificationDeliveryIsOK) as chex,
					M_DeliveryID as deliveryid,
					M_DeliveryTypeID as typedeliveryid,
					T_OrderDeliveryM_KelurahanID as vilageid,
					IF(ISNULL(Fo_VerificationDeliveryID),'',Fo_VerificationDeliveryReason) as note,
					'reguler' as type,
					CASE
						WHEN T_OrderDeliveryM_DeliveryID = 1 THEN M_DeliveryName
						WHEN T_OrderDeliveryM_DeliveryID = 4 THEN CONCAT(M_DeliveryName)
						WHEN T_OrderDeliveryM_DeliveryID = 2 THEN CONCAT(M_DeliveryName)
						WHEN ( T_OrderDeliveryM_DeliveryID = 7 OR T_OrderDeliveryM_DeliveryID = 9 ) THEN CONCAT(M_DeliveryName)
						WHEN ( T_OrderDeliveryM_DeliveryID = 6 OR T_OrderDeliveryM_DeliveryID = 8 ) THEN CONCAT(M_DeliveryName)
						ELSE 
							CONCAT(M_DeliveryName)
					END as label,
					CASE
						WHEN T_OrderDeliveryM_DeliveryID = 1 THEN ''
						WHEN T_OrderDeliveryM_DeliveryID = 4 THEN M_DoctorAddressDescription
						WHEN T_OrderDeliveryM_DeliveryID = 2 THEN M_PatientAddressDescription
						WHEN ( T_OrderDeliveryM_DeliveryID = 7 OR T_OrderDeliveryM_DeliveryID = 9 ) THEN M_DoctorHP
						WHEN ( T_OrderDeliveryM_DeliveryID = 6 OR T_OrderDeliveryM_DeliveryID = 8 ) THEN M_PatientHP
						ELSE 
							T_OrderDeliveryDestination
					END as destination,
					CASE
						WHEN T_OrderDeliveryM_DeliveryID = 4 THEN M_DoctorAddressID
						WHEN T_OrderDeliveryM_DeliveryID = 2 THEN M_PatientAddressID
						ELSE 
							0
					END as addressid
					FROM t_orderdelivery
					JOIN t_orderheader ON T_OrderDeliveryT_OrderHeaderID = T_OrderHeaderID
					JOIN m_delivery ON T_OrderDeliveryM_DeliveryID = M_DeliveryID
					JOIN m_deliverytype ON T_OrderDeliveryM_DeliveryTypeID = M_DeliveryTypeID
					LEFT JOIN m_doctoraddress ON T_OrderDeliveryAddressID = M_DoctorAddressID AND T_OrderDeliveryM_DeliveryID = 4
					LEFT JOIN m_patientaddress ON T_OrderDeliveryAddressID = M_PatientAddressID AND T_OrderDeliveryM_DeliveryID = 2
					LEFT JOIN fo_verification_delivery ON Fo_VerificationDeliveryT_OrderHeaderID =  T_OrderDeliveryT_OrderHeaderID  AND Fo_VerificationDeliveryIsActive = 'Y'
					LEFT JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID AND ( T_OrderDeliveryM_DeliveryID = 7 OR T_OrderDeliveryM_DeliveryID = 9 )
					LEFT JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID AND ( T_OrderDeliveryM_DeliveryID = 6 OR T_OrderDeliveryM_DeliveryID = 8 )
					WHERE
					T_OrderDeliveryT_OrderHeaderID =  {$orderid} AND T_OrderDeliveryIsActive = 'Y' 
					GROUP BY T_OrderDeliveryID
					UNION
					SELECT 0 as id, 
					IFNULL(Fo_VerificationDeliveryAddID,0) as xid,
					M_DeliveryTypeCode as code,
					IF(ISNULL(Fo_VerificationDeliveryAddID),'N',Fo_VerificationDeliveryAddOK) as chex,
					Fo_VerificationDeliveryAddM_DeliveryID as deliveryid,
					Fo_VerificationDeliveryAddM_DeliveryTypeID as typedeliveryid,
					Fo_VerificationDeliveryAddM_KelurahanID as vilageid,
					IF(ISNULL(Fo_VerificationDeliveryAddID),'',Fo_VerificationDeliveryAddReason) as note,
					'reguler' as type,
					CASE
						WHEN Fo_VerificationDeliveryAddM_DeliveryID = 1 THEN 'Ambil Sendiri'
						WHEN Fo_VerificationDeliveryAddM_DeliveryID = 4 THEN CONCAT(M_DeliveryName)
						WHEN Fo_VerificationDeliveryAddM_DeliveryID = 2 THEN CONCAT(M_DeliveryName)
						WHEN ( Fo_VerificationDeliveryAddM_DeliveryID = 7 OR Fo_VerificationDeliveryAddM_DeliveryID = 9 ) THEN CONCAT(M_DeliveryName)
						WHEN ( Fo_VerificationDeliveryAddM_DeliveryID = 6 OR Fo_VerificationDeliveryAddM_DeliveryID = 8 ) THEN CONCAT(M_DeliveryName)
						ELSE 
							CONCAT(M_DeliveryName)
					END as label,
					CASE
						WHEN Fo_VerificationDeliveryAddM_DeliveryID = 1 THEN ''
						WHEN Fo_VerificationDeliveryAddM_DeliveryID = 4 THEN M_DoctorAddressDescription
						WHEN Fo_VerificationDeliveryAddM_DeliveryID = 2 THEN M_PatientAddressDescription
						WHEN ( Fo_VerificationDeliveryAddM_DeliveryID = 7 OR Fo_VerificationDeliveryAddM_DeliveryID = 9 ) THEN M_DoctorHP
						WHEN ( Fo_VerificationDeliveryAddM_DeliveryID = 6 OR Fo_VerificationDeliveryAddM_DeliveryID = 8 ) THEN M_PatientHP
						ELSE 
							Fo_VerificationDeliveryAddDestination
					END as destination,
					CASE
						WHEN Fo_VerificationDeliveryAddM_DeliveryID  = 4 THEN M_DoctorAddressID
						WHEN Fo_VerificationDeliveryAddM_DeliveryID  = 2 THEN M_PatientAddressID
						ELSE 
							0
					END as addressid
					FROM fo_verification_delivery_add
					JOIN t_orderheader ON Fo_VerificationDeliveryAddT_OrderHeaderID = T_OrderHeaderID
					JOIN m_delivery ON Fo_VerificationDeliveryAddM_DeliveryID = M_DeliveryID
					JOIN m_deliverytype ON Fo_VerificationDeliveryAddM_DeliveryTypeID = M_DeliveryTypeID
					LEFT JOIN m_doctoraddress ON Fo_VerificationDeliveryAddAddressID = M_DoctorAddressID AND Fo_VerificationDeliveryAddM_DeliveryID  = 4
					LEFT JOIN m_patientaddress ON Fo_VerificationDeliveryAddAddressID = M_PatientAddressID AND Fo_VerificationDeliveryAddM_DeliveryID  = 2
					LEFT JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID AND ( Fo_VerificationDeliveryAddM_DeliveryID = 7 OR Fo_VerificationDeliveryAddM_DeliveryID = 9 )
					LEFT JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID AND ( Fo_VerificationDeliveryAddM_DeliveryID = 6 OR Fo_VerificationDeliveryAddM_DeliveryID = 8 )
					WHERE
					Fo_VerificationDeliveryAddT_OrderHeaderID = {$orderid} AND Fo_VerificationDeliveryAddIsActive = 'Y' 
				";
				//echo $query ;
		$rows = $this->db_onedev->query($query)->result_array();
		//echo $this->db_onedev->last_query();
		$rst = '';
		foreach($rows as $k => $v){
			if($rst != ''){
				$rst = $rst.' , '.$v['label'];
			}
			else{
				$rst = $v['label'];
			}
		}
		return $rst;
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
   
   public function search()
   {
      $prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

	  $status = $prm["stationid"];
	  $startdate = $prm["startdate"];
	 
	  if(!isset($prm['current_page']))
		  $prm['current_page'] = 1;
	  
	  $number_limit = 10;
	  $number_offset = ($prm['current_page'] - 1) * $number_limit ;
      

      $sql = "	SELECT count(*) as total
				FROM (
					SELECT ids,
						testname,
						handlingdatetime,
						IFNULL(Sampling_PreanaliticID,0) as xid,
						GROUP_CONCAT(DISTINCT Nat_RequirementName separator ',') as requirements,
						IFNULL(Sampling_PreanaliticRequirementStatus,'X') as status,
						CONCAT(DATE_FORMAT(Sampling_PreanaliticDate,'%d-%m-%Y'),' ',DATE_FORMAT(Sampling_PreanaliticTime,'%H:%i')) as verifiedtime,
						IFNULL(M_UserUsername,'') as verifiedby,
						'' as htmlreqs
					FROM (
						SELECT 
							GROUP_CONCAT(DISTINCT Nat_TestID separator ',') as ids, 
							IF(Nat_TestWorklistName = '' OR ISNULL(Nat_TestName),Nat_TestName,Nat_TestWorklistName) as testname,
							CONCAT(T_OrderSampleHandlingDate,' ',T_OrderSampleHandlingTime) as handlingdatetime
						FROM t_orderdetail
						JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND DATE(T_OrderHeaderDate) = '{$startdate}'
						JOIN t_test ON T_TestID = T_OrderDetailT_TestID AND T_TestIsNonLab = ''
						JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
						JOIN t_barcodelab ON T_BarcodeLabT_SampleTypeID = T_SampleTypeID AND T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID
						JOIN nat_test ON T_TestNat_TestID = Nat_TestID
						JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID =  T_OrderHeaderID AND T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
						T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleHandling = 'Y'
						WHERE
						T_OrderDetailT_TestIsResult = 'Y' AND T_OrderDetailIsActive = 'Y'
						GROUP BY testname 
						ORDER BY ids ASC
					) x
					LEFT JOIN sampling_preanalitic ON json_contains(Sampling_PreanaliticNat_TestID,x.ids) AND Sampling_PreanaliticDate = '{$startdate}'
					LEFT JOIN m_user ON Sampling_PreanaliticUserID = M_UserID
					LEFT JOIN nat_requirement ON json_contains(Sampling_PreanaliticRequirements,Nat_RequirementID)
					WHERE
					(('{$status}' = 'N' AND ISNULL(Sampling_PreanaliticRequirementStatus)) OR ('{$status}' = 'Y' AND Sampling_PreanaliticRequirementStatus IS NOT NULL) OR ('{$status}' = 'A'))
					GROUP BY testname
				) cx
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);

		$tot_count = 0;
		$tot_page = 0;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
			$tot_page = ceil($tot_count/$number_limit);
		} else {
			$this->sys_error_db("t_samplestorageout count", $this->db_onedev);
			exit;
		}  
	  
		$sql = 	"SELECT ids,
					testname,
					handlingdatetime,
					IFNULL(Sampling_PreanaliticID,0) as xid,
					GROUP_CONCAT(DISTINCT Nat_RequirementName separator ',') as requirements,
					IFNULL(Sampling_PreanaliticRequirementStatus,'X') as status,
					CONCAT(DATE_FORMAT(Sampling_PreanaliticDate,'%d-%m-%Y'),' ',DATE_FORMAT(Sampling_PreanaliticTime,'%H:%i')) as verifiedtime,
					IFNULL(M_UserUsername,'') as verifiedby,
					'' as htmlreqs
				FROM (
					SELECT 
						GROUP_CONCAT(DISTINCT Nat_TestID separator ',') as ids, 
						IF(Nat_TestWorklistName = '' OR ISNULL(Nat_TestName),Nat_TestName,Nat_TestWorklistName) as testname,
						CONCAT(T_OrderSampleHandlingDate,' ',T_OrderSampleHandlingTime) as handlingdatetime
					FROM t_orderdetail
					JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND DATE(T_OrderHeaderDate) = '{$startdate}'
					JOIN t_test ON T_TestID = T_OrderDetailT_TestID AND T_TestIsNonLab = ''
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_barcodelab ON T_BarcodeLabT_SampleTypeID = T_SampleTypeID AND T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID
					JOIN nat_test ON T_TestNat_TestID = Nat_TestID
					JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID =  T_OrderHeaderID AND T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
					T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleHandling = 'Y'
					WHERE
					T_OrderDetailT_TestIsResult = 'Y' AND T_OrderDetailIsActive = 'Y'
					GROUP BY testname 
					ORDER BY ids ASC
				) x
				LEFT JOIN sampling_preanalitic ON json_contains(Sampling_PreanaliticNat_TestID,x.ids) AND Sampling_PreanaliticDate = '{$startdate}'
				LEFT JOIN m_user ON Sampling_PreanaliticUserID = M_UserID
				LEFT JOIN nat_requirement ON json_contains(Sampling_PreanaliticRequirements,Nat_RequirementID)
				WHERE
					(('{$status}' = 'N' AND ISNULL(Sampling_PreanaliticRequirementStatus)) OR ('{$status}' = 'Y' AND Sampling_PreanaliticRequirementStatus IS NOT NULL) OR ('{$status}' = 'A'))
				
				GROUP BY testname
				ORDER BY ids ASC
				limit $number_limit offset $number_offset";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		$rows = $query->result_array();
		if($rows){
			foreach($rows as $k=>$v){
				$xplode_reqs = array();
				if($v['status'] == 'N'){
					$xplode_reqs = explode(',',$v['requirements']);
				}
				$rows[$k]['htmlreqs'] = $xplode_reqs;
			}
		}
		
		//echo $this->db_onedev->last_query();

		$result = array("total" => $tot_page, "records" => $rows, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }
   
   function getordersamples(){
	   $prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$doctorid = $prm['doctorid'];
		$doctoraddressid = $prm['doctoraddressid'];
	   $sql = "SELECT 
					0 as idx,
					M_SexName, 
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname,
					T_SampleTypeName as samplename, 
					T_OrderHeaderLabNumber as labnumber, 
					T_OrderHeaderID as orderid,
					T_SampleTypeID as sampleid,
					'Y' as active, 
					'N' as flag_image
					FROM t_samplingso
					JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID
					JOIN t_sampletype ON T_SamplingSoT_SampleTypeID = T_SampleTypeID
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					WHERE	
						T_SamplingSoM_DoctorID = {$doctorid} AND T_SamplingSoM_DoctorAddressID = {$doctoraddressid} AND T_SamplingSoVerifyFlagWL = 'N' AND T_SamplingSoIsActive = 'Y'";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();
		$result = array(
			"total" => count($rows) , 
			"records" => $rows, 
		); 
		$this->sys_ok($result);
		exit;
	   
   }
   
	function getgroups(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query ="	SELECT Nat_GroupID as id, Nat_GroupName as title, CONCAT('GROUP : ',Nat_GroupName) as fulltitle, '' as childrens
					FROM nat_group 
					WHERE	
						Nat_GroupIsActive = 'Y' AND ( Nat_GroupCode = 2 OR Nat_GroupCode = 3 )
				";
				//echo $query;
		$rows['groups'] = $this->db_onedev->query($query)->result_array();
		if($rows['groups']){
			foreach($rows['groups'] as $k => $v){	
				$childrens = array(array('id'=>0, 'title'=>'Semua', 'fulltitle'=>'Subgroub : Semua'));
				$query ="	SELECT Nat_SubGroupID as id, Nat_SubGroupName as title, CONCAT('SUBGROUP : ',Nat_SubGroupName) as fulltitle
					FROM nat_subgroup 
					WHERE	
						Nat_SubGroupNat_GroupID = {$v['id']} AND Nat_SubGroupIsActive = 'Y'
				";
				//echo $query;
				$xrst = $this->db_onedev->query($query)->result_array();
				if($xrst){
					foreach($xrst as $ki => $vi){				
						array_push($childrens,$vi);
					}
				}
				$rows['groups'][$k]['childrens'] = $childrens ;
			}
		}
		$query ="	SELECT M_LangID as id, M_LangCode as name
					FROM m_lang 
					WHERE	
						M_LangIsActive = 'Y' 
				";
				//echo $query;
		$rows['langs'] = $this->db_onedev->query($query)->result_array();
		$result = array(
			"total" => count($rows) , 
			"records" => $rows, 
		); 
		$this->sys_ok($result);
		exit;
	}
	
	function getrequirements(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		
		$prm = $this->sys_input;
		$rows = array();
		$query = "
				SELECT 	Nat_RequirementID as id, 
						Nat_RequirementName as name,
						'N' as chex
					FROM nat_requirement
					JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND Nat_RequirementPositionNat_PositionID = 5 AND 
						 Nat_RequirementPositionIsActive = 'Y'
					WHERE	
						Nat_RequirementIsActive = 'Y'
					GROUP BY nat_requirementID
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
	
	function getsubgroups(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$rows = array(array('id'=>0, 'title'=>'Semua', 'fulltitle'=>'Subgroub : Semua'));
		$query ="	SELECT Nat_SubGroupID as id, Nat_SubGroupName as title, CONCAT('SUBGROUP : ',Nat_SubGroupName) as fulltitle
					FROM nat_subgroup 
					WHERE	
						Nat_SubGroupNat_GroupID = {$prm['id']} AND Nat_SubGroupIsActive = 'Y'
				";
				//echo $query;
		$rst = $this->db_onedev->query($query)->result_array();
		if($rst){
			foreach($rst as $k => $v){				
				array_push($rows,$v);
			}
		}
		$result = array(
			"total" => count($rows) , 
			"records" => $rows, 
		); 
		$this->sys_ok($result);
		exit;
	}
	
	function save(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$requirements  = '[]';
		$arr_requirements = array();
		if($prm['status'] === 'N'){
			foreach($prm['requirements'] as $k=>$v){
				if($v['chex'] == 'Y')
					array_push($arr_requirements,$v['id']);
				}
			$requirements = '['.join(',',$arr_requirements).']';
		}
		$ids = '['.$prm['ids'].']';
			
		$sql = "INSERT INTO sampling_preanalitic(
					Sampling_PreanaliticDate,
					Sampling_PreanaliticTime,
					Sampling_PreanaliticNat_TestID,
					Sampling_PreanaliticWorklistName,
					Sampling_PreanaliticRequirementStatus,
					Sampling_PreanaliticRequirements,
					Sampling_PreanaliticUserID,
					Sampling_PreanaliticCreated
				)
				VALUES(
					CURDATE(),
					CURTIME(),
					'{$ids}',
					'{$prm['testname']}',
					'{$prm['status']}',
					'{$requirements}',
					{$userid},
					NOW()
				)";
				//echo $sql;
			$this->db_onedev->query($sql);
		

		$result = array(
			"total" => 1, 
			"records" => array('status'=>'OK')
		); 
		$this->sys_ok($result);
		exit;
	}
   
   function getstation(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query ="	SELECT T_SampleStationID as id, T_SampleStationName as name
					FROM t_samplestation 
					WHERE	
						T_SampleStationIsActive = 'Y'
				";
				//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		//print_r($statuses);
		foreach($statuses as $k=>$v){
			array_push($rows['statuses'],$v);
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
	
	
   function saveresult(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		
		if($prm['act'] === 'val1'){
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation1 = 'Y', So_ResultEntryStatus = 'VAL1', So_ResultEntryUserID = {$userid} WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
			//echo $this->db_onedev->last_query();
		}
		
		if($prm['act'] === 'unval1'){
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation1 = 'N', So_ResultEntryStatus = 'NEW', So_ResultEntryUserID = {$userid} WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
		}
		
		if($prm['act'] === 'val2'){
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation2 = 'Y', So_ResultEntryStatus = 'VAL2', So_ResultEntryUserID = {$userid} WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
			$sql = "SELECT t_samplingso.* 
					FROM t_samplingso 
					JOIN so_resultentry ON So_ResultEntryT_OrderHeaderID = T_SamplingSoT_OrderHeaderID AND So_ResultEntryID = {$prm['trx']['trx_id']}
					JOIN t_orderdetail ON So_ResultEntryT_OrderDetailID = T_OrderDetailID
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID
					WHERE T_SamplingSoIsActive = 'Y'
					GROUP BY T_SamplingSoID";
			$data_sampling = $this->db_onedev->query($sql)->result_array();
			if($data_sampling){
				foreach($data_sampling as $k => $v){
					$sql = "UPDATE t_samplingso SET T_SamplingSoIsDone = 'Y' WHERE T_SamplingSoID = {$v['T_SamplingSoID']}";
					$this->db_onedev->query($sql);
				}
			}
		}
		
		if($prm['act'] === 'unval2'){
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation2 = 'N', So_ResultEntryStatus = 'VAL1', So_ResultEntryUserID = {$userid} WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
			$sql = "SELECT t_samplingso.* 
					FROM t_samplingso 
					JOIN so_resultentry ON So_ResultEntryT_OrderHeaderID = T_SamplingSoT_OrderHeaderID AND So_ResultEntryID = {$prm['trx']['trx_id']}
					JOIN t_orderdetail ON So_ResultEntryT_OrderDetailID = T_OrderDetailID
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID
					WHERE T_SamplingSoIsActive = 'Y'
					GROUP BY T_SamplingSoID";
			$data_sampling = $this->db_onedev->query($sql)->result_array();
			if($data_sampling){
				foreach($data_sampling as $k => $v){
					$sql = "UPDATE t_samplingso SET T_SamplingSoIsDone = 'N' WHERE T_SamplingSoID = {$v['T_SamplingSoID']}";
					$this->db_onedev->query($sql);
				}
			}
		}
		
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID  = '{$prm['trx']['trx_id']}'";
		//echo $sql;
		$re_langid = $this->db_onedev->query($sql)->row()->So_ResultEntryM_LangID;
		if($re_langid == $prm['trx']['language_id']){
			foreach($prm['trx']['details'] as $k => $v){
				$sql = "UPDATE so_resultentrydetail SET 
							So_ResultEntryDetailResult = '{$v['result_value']}', 
							So_ResultEntryDetailUserID = {$userid}
						WHERE So_ResultEntryDetailID = {$v['trx_id']}";
				$this->db_onedev->query($sql);
				//echo $this->db_onedev->last_query();
			}
		}
		else{
			foreach($prm['trx']['details'] as $k => $v){
				if($v['trx_id'] == 0 || $v['trx_id'] === '0'){
					$sql = "
							INSERT so_resultentrydetail_other(
								So_ResultEntryDetailOtherM_LangID,
								So_ResultEntryDetailOtherSo_ResultEntryID,
								So_ResultEntryDetailOtherSo_TemplateDetailID,
								So_ResultEntryDetailOtherResult,
								So_ResultEntryDetailOtherCreated,
								So_ResultEntryDetailOtherUserID
							)
							VALUES(
								{$prm['trx']['language_id']},
								{$prm['trx']['trx_id']},
								{$v['template_detail_id']},
								'{$v['result_value']}',
								NOW(),
								{$userid}
							)
					";
				}
				else{
					$sql = "UPDATE so_resultentrydetail_other SET 
								So_ResultEntryDetailOtherResult = '{$v['result_value']}',
								So_ResultEntryDetailOtherUserID = {$userid}
							WHERE So_ResultEntryDetailOtherID = {$v['trx_id']}";
				}
				$this->db_onedev->query($sql);
				//echo $this->db_onedev->last_query();
			}
		}
		
		
		$last_id = $prm['trx']['trx_id'];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID = {$last_id}";
		$data_log_header = $this->db_onedev->query($sql)->result();
		$sql = "SELECT * FROM so_resultentrydetail WHERE So_ResultEntryDetailSo_ResultEntryID = {$last_id}";
		$data_log_details = $this->db_onedev->query($sql)->result();
		$sql = "SELECT * FROM so_resultentrydetail_other WHERE So_ResultEntryDetailOtherSo_ResultEntryID = {$last_id}";
		$data_log_other_details = $this->db_onedev->query($sql)->result();
		
		$data_log = json_encode(array('header'=>$data_log_header,'details'=>$data_log_details,'details_other'=>$data_log_other_details));
		$sql = "INSERT INTO one_log.log_resultentry_so (
					Log_ResultEntrySoDate,
					Log_ResultEntrySoJSON,
					Log_ResultEntrySoUserID
				)
				VALUES(
					NOW(),
					'{$data_log}',
					{$userid}
				)";
		//echo $sql;
		$this->db_onedev->query($sql);
		
		$result = array(
			"total" => 1 , 
			"records" => array('status'=>'OK')
		); 
		$this->sys_ok($result);
		exit;
	}
	
	
	
   function deletetrx(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		
		$query ="UPDATE so_walklettercourier SET
					So_WalkLetterCourierIsActive = 'N',
					So_WalkLetterCourierUserID = '{$userid}'
				WHERE
					So_WalkLetterCourierID = {$prm['trx_id']}
			";
		//echo $query;
		$saveheader = $this->db_onedev->query($query);
		$last_id = $prm['trx_id'];
		
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID = {$last_id}";
		$data_log_header = $this->db_onedev->query($sql)->result();
		$sql = "SELECT * FROM so_resultentrydetail WHERE So_ResultEntryDetailSo_ResultEntryID = {$last_id}";
		$data_log_details = $this->db_onedev->query($sql)->result();
		
		$data_log = json_encode(array('header'=>$data_log_header,'details'=>$data_log_details));
		$sql = "INSERT INTO one_log.log_resultentry_so (
					Log_ResultEntrySoDate,
					Log_ResultEntrySoJSON,
					Log_ResultEntrySoUserID
				)
				VALUES(
					NOW(),
					'{$data_log}',
					{$userid}
				)";
		//echo $sql;
		$this->db_onedev->query($sql);
		
		$result = array(
			"total" => 1 , 
			"records" => array('status'=>'OK'),
			"numbering" => $prm['trx_numbering'],
			"id" => $prm['trx_id']
		); 
		$this->sys_ok($result);
		exit;
	}
	
	
	function gettemplate(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$rst = array();
		$sql = "SELECT * 
				FROM so_templatevalue
				WHERE
					So_TemplateValueM_DoctorID = '{$prm['doctor_id']}' AND 
					So_TemplateDetailM_LangID = '{$prm['language_id']}' AND
					So_TemplateValueT_TestID = '{$prm['test_id']}' AND 
					So_TemplateValueSo_TemplateDetailID = '{$prm['template_detail_id']}' AND 
					So_TemplateValueIsActive = 'Y'";
		$rst = $this->db_onedev->query($sql)->row();
		
		$result = array(
			"total" => 1 , 
			"records" => $rst
		); 
		$this->sys_ok($result);
		exit;
	}
	
	function printcount(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$trx_id = $prm["trx_id"];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID = '{$trx_id}'";
		$orderdetail_id = $this->db_onedev->query($sql)->row()->So_ResultEntryT_OrderDetailID;
		
		$sql = "UPDATE t_orderdetail SET T_OrderDetailPrintCount = T_OrderDetailPrintCount + 1 WHERE T_OrderDetailID = '{$orderdetail_id}'";
		$this->db_onedev->query($sql);
		$result = array(
			"total" => 1 , 
			"records" => $prm
		); 
		$this->sys_ok($result);
		exit;
	}
	
	function getrstbylang(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$x_langid = $prm['lang']['id'];
		$x_reid = $prm['detail']['trx_id'];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID  = '{$x_reid}'";
		//echo $sql;
		$re_langid = $this->db_onedev->query($sql)->row()->So_ResultEntryM_LangID;
		if($x_langid == $re_langid){
			$sql = 	"SELECT 
					So_ResultEntryDetailID as trx_id,
					So_ResultEntryDetailSo_TemplateDetailID as template_detail_id,
					So_TemplateDetailName as result_label,
					IFNULL(So_ResultEntryDetailResult,'') as result_value
				FROM so_resultentrydetail
				JOIN so_templatedetail ON So_TemplateDetailID = So_ResultEntryDetailSo_TemplateDetailID
				JOIN so_resultentry ON So_ResultEntryDetailSo_ResultEntryID = So_ResultEntryID AND So_ResultEntryM_LangID = {$x_langid}
				WHERE
				So_ResultEntryDetailSo_ResultEntryID = {$x_reid} AND So_ResultEntryDetailisActive = 'Y'
				";
		}else{
			$sql = 	"
				SELECT 
					IFNULL(So_ResultEntryDetailOtherID,0) as trx_id,
					So_TemplateDetailID as template_detail_id,
					So_TemplateDetailName as result_label,
					So_ResultEntryDetailOtherResult as result_value
				FROM so_resultentry
				JOIN t_orderdetail ON T_OrderDetailID = So_ResultEntryT_OrderDetailID
				JOIN so_testtemplate ON So_TestTemplateT_TestID = T_OrderDetailT_TestID
				JOIN so_templatedetail ON So_TemplateDetailSo_TemplateID = So_TestTemplateSo_TemplateID AND So_TemplateDetailM_LangID = {$x_langid}
				LEFT JOIN so_resultentrydetail_other ON So_ResultEntryDetailOtherM_LangID = So_TemplateDetailM_LangID AND
					So_ResultEntryDetailOtherSo_ResultEntryID = So_ResultEntryID AND
					So_ResultEntryDetailOtherSo_TemplateDetailID = So_TemplateDetailID AND
					So_ResultEntryDetailOtherIsActive = 'Y'
				WHERE
				So_ResultEntryID = {$x_reid} AND So_ResultEntryIsActive = 'Y'
				";
		}
		
		//echo $sql;
		$rst = $this->db_onedev->query($sql)->result_array();
		$result = array(
			"total" => 1 , 
			"records" => $rst
		); 
		$this->sys_ok($result);
		exit;
	}
	
   
   
}