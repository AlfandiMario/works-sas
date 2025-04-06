<?php
class Samplingcall extends MY_Controller
{
	var $db_onedev;
	public function index()
	{
		echo "Samplingcall API";
	}

	public function __construct()
	{
		parent::__construct();
		$this->db_onedev = $this->load->database("onedev", true);
		// $this->IP_SOCKET_IO = "devone.aplikasi.web.id";
		$this->IP_SOCKET_IO = "localhost";
	}

	function getsampletypes()
	{

		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$orderid = $prm['orderid'];
		$stationid = $prm['stationid'];
		$statusid = $prm['statusid'];
		$rows = array();

		$sql = "SELECT SUM(xcount) as cnt
						FROM (
							SELECT COUNT(*) as xcount
							FROM t_orderdetail 
							JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
							JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
							JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
							JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
								T_SampleStationID = {$stationid} AND 
								T_SampleStationIsNonLab = 'ELEKTROMEDIS'
							LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
								T_SamplingSoT_TestID = T_TestID AND 
								T_SamplingSoT_SampleStationID = T_SampleStationID AND
								T_SamplingSoIsActive = 'Y'
							WHERE
							T_OrderDetailT_OrderHeaderID = {$orderid} AND T_OrderDetailIsActive = 'Y' AND
							ISNULL(T_SamplingSoDoneDate)
							
						) x";
		//echo $sql;
		$x_exist = $this->db_onedev->query($sql)->row_array();
		if (intval($x_exist['cnt']) != 0) {
			$sql = "UPDATE t_sampling_queue_last_status 
					SET 
						T_SamplingQueueLastStatusT_SamplingQueueStatusID = 2
					WHERE
						T_SamplingQueueLastStatusT_OrderHeaderID = '{$orderid}' AND
						T_SamplingQueueLastStatusT_SampleStationID = {$stationid} AND 
						T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5";
			//echo $sql;
			$this->db_onedev->query($sql);
		}

		$sql = "SELECT T_OrderDetailID as id, 
				T_OrderHeaderID as orderid, 
				T_TestCode as test_code,
				T_TestName as test_name,
				T_TestID as test_id,
				T_BahanName,
				IFNULL(T_SamplingSoID,0) as T_SamplingSoID, 
				IF(ISNULL(T_SamplingSoID),'N',T_SamplingSoFlag) as status, 
				IF(ISNULL(T_SamplingSoProcessDate),'00-00-0000',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as process_date,
				IF(ISNULL(T_SamplingSoProcessTime),'00:00',DATE_FORMAT(T_SamplingSoProcessTime,'%H:%i')) as process_time,
				IF(ISNULL(T_SamplingSoDoneDate) OR T_SamplingSoFlag = 'P','00-00-0000',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as done_date,
				IF(ISNULL(T_SamplingSoDoneTime) OR T_SamplingSoFlag = 'P','00:00',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as done_time,
				IF(ISNULL(T_SamplingSoRequirementID),'X',T_SamplingSoRequirementStatus) as requirement_status,
				'' as requirements,
				DocumentationGroupName as type,
				IF(DocumentationGroupName = 'xray',IF(ISNULL(T_SamplingSoFormExpose),'N','Y'),'Y') as fill_form,
				IFNULL(T_SamplingSoFormKv,'') as form_kv,
				IFNULL(T_SamplingSoFormMa,'') as form_ma,
				IFNULL(T_SamplingSoFormSecond,'') as form_second,
				IFNULL(T_SamplingSoFormExpose,'') as form_expose
				FROM t_orderheader	
				JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderAddOnIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
						T_SampleStationID = {$stationid} AND 
						T_SampleStationIsNonLab = 'ELEKTROMEDIS'
				JOIN documentation_group_detail ON DocumentationGroupDetailNat_SubGroupID = T_TestNat_SubGroupID 
				JOIN documentation_group ON DocumentationGroupDetailDocumentationGroupID = DocumentationGroupID
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
				T_SamplingSoT_TestID = T_TestID AND 
				T_SamplingSoT_SampleStationID = T_SampleStationID AND
						T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND 
						T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
						T_SamplingSoRequirementNat_PositionID = 8 AND
						T_SamplingSoRequirementIsActive = 'Y'
				LEFT JOIN t_samplingso_form ON T_SamplingSoFormT_SamplingSOID = T_SamplingSOID AND T_SamplingSoFormIsActive = 'Y'
				WHERE
					T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
				GROUP BY T_OrderDetailID";
		//echo $sql;
		$rows['sampletypes'] = $this->db_onedev->query($sql)->result_array();
		if ($rows) {
			foreach ($rows['sampletypes'] as $k => $v) {
				$zxprm = array();
				$zxprm['status'] = $v['status'];
				$zxprm['orderdetailid'] = $v['id'];
				$zxprm['orderid'] = $v['orderid'];
				$zxprm['sampletypeid'] = $v['test_id'];
				$rows['sampletypes'][$k]['requirements'] = $this->getrequirements($zxprm);
			}
		}

		$sql = "SELECT  T_BahanID as id, T_BahanName,
				IF(T_TestIsNonLab = '',fn_sampling_receive_status_by_bahan_lab({$orderid},T_SampleTypeID),fn_sampling_receive_status_by_bahan_so({$orderid},T_TestID)) as status_bahan
				FROM t_orderdetail
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				WHERE
				T_OrderDetailT_OrderHeaderID = {$orderid} 
				GROUP BY T_BahanID";
		//echo $sql;
		$rows['information_bahan'] = $this->db_onedev->query($sql)->result_array();
		$result = array("total" => count($rows), "records" => $rows, "sql" => $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
	}

	function fillform()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
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
						{$prm['T_SamplingSoID']},
						{$prm['form_kv']},
						{$prm['form_ma']},
						{$prm['form_second']},
						'{$prm['form_expose']}',
						{$userid},
						NOW(),
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_SamplingSoFormKv = {$prm['form_kv']},
							T_SamplingSoFormMa = {$prm['form_ma']},
							T_SamplingSoFormSecond = {$prm['form_second']},
							T_SamplingSoFormExpose = {$prm['form_expose']},
							T_SamplingSoFormUserID = {$userid}";
		//echo $sql;
		$this->db_onedev->query($sql);

		$result = array("status" => "OK");
		$this->sys_ok($result);
		exit;
	}

	public function search()
	{
		$prm = $this->sys_input;
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$name = $prm["name"];
		$nolab = $prm["nolab"];
		$stationid = $prm["stationid"];
		$statusid = $prm["statusid"];
		$xdate = $prm["xdate"];
		$companyid = isset($prm["companyid"]) ? $prm["companyid"] : 0;
		$search = isset($prm["searchx"]) ? $prm["searchx"] : '';
		$locationID = $prm['locationid'];
		$filter_companyid = '';
		if ($companyid != 0 || $companyid != '0') {
			$filter_companyid = " AND M_CompanyID = {$companyid} ";
		}

		$where_status = " AND ( ISNULL(T_SamplingSoID) OR T_SamplingSoFlag = 'P' OR T_SamplingSoFlag = 'X')";
		$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND ( DATE(T_OrderHeaderAddonIsComingDate) = '{$xdate}' OR DATE(T_OrderHeaderDate) = '{$xdate}' ) {$where_status}";

		if ($nolab != "") {
			if ($sql_where != "") {
				$sql_where .= " and ";
			}
			$sql_where .= "( T_OrderHeaderLabNumber like '%$nolab%' OR M_PatientName like '%$nolab%' OR T_OrderHeaderLabNumberExt like '%$nolab%'  )";
		}

		if ($search != '') {
			$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND T_OrderHeaderLabNumber = '{$search}'";
		}

		$sql = 	"SELECT * FROM (
				SELECT DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
				t_orderheader.*,
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
				m_patient.*, 
				M_SexName, 
				M_TitleName, 
				CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
				M_CompanyName,
				fn_sampling_queue_status_name(T_OrderHeaderID,T_SampleStationID) as status, 
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
				fn_sampling_queue_status_id(T_OrderHeaderID,T_SampleStationID)  as statusid,
				T_SampleStationID, 
				T_TestID,
				{$stationid} as stationid,
				T_OrderPromiseDateTime,
				T_OrderHeaderIsCito as iscito,
				IFNULL(T_OrderHeaderFoNote,'') as fo_note,
				IFNULL(T_OrderHeaderVerificationNote,'') as fo_ver_note,
				fn_sampling_reqs(T_OrderHeaderID) as fo_requirements,
				'' as htmlforeqs,
				IF(fn_fo_ver_have_reqs(T_OrderHeaderID) = 0,'Y','N') as fo_ver_status_req,
				fn_fo_reg_have_reqs(T_OrderHeaderID) as fo_reg_status_req,
				fn_sampling_reqs_status(T_OrderHeaderID) as fo_requirements_status,
				IF(fn_fo_get_verification_status(T_OrderHeaderID) = 0, 'X','Y') as fo_verification_status,
				IFNULL(T_OrderHeaderSamplingNote,'') as sampling_note,
				fn_sampling_queue_status_confirm(T_OrderHeaderID,T_SampleStationID) as status_confirm,
				CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as doctor_sender,
				fn_fo_get_laststatus(T_OrderHeaderID) as last_status_fo,
				T_OrderHeaderAddonIsComing as status_coming,
				T_OrderLocationID as order_location_id,
					IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as antri_time,
					IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as skip_time
				FROM t_orderheader	
				JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID
				JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID $filter_companyid
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND T_OrderLocationT_SampleStationID = {$stationid}  AND T_OrderLocationIsActive = 'Y'
				LEFT JOIN antrian_samplestation ON AntrianSampleStationT_OrderLocationID =T_OrderLocationID AND AntrianSampleStationIsActive = 'Y'
				JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID AND M_LocationID = {$locationID}
				LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = 'ELEKTROMEDIS'
				JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
				T_SamplingSoT_SampleStationID = T_SampleStationID AND
				T_SamplingSoT_TestID = T_TestID AND 
				T_SamplingSoIsActive = 'Y'
				
				$sql_where
				GROUP BY T_OrderHeaderID
				HAVING last_status_fo IN (3,5)
				) x
				ORDER BY T_OrderHeaderIsCito DESC, antri_time ASC
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		//echo $this->db_onedev->last_query();
		$rows = $query->result_array();
		if($rows){
			$count_arr = count($rows);
			foreach ($rows as $key => $value) {
				if($key+1 != $count_arr){
					$rows[$key]['skip_time'] = $rows[$key+1]['antri_time'];
				}
			}
		}
		$result = array("total" => count($rst), "records" => $rows, "sql" => $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
	}

	function searchcompany()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;

		$max_rst = 12;
		$tot_count = 0;

		$q = [
			'search'     => '%'
		];

		if ($prm['search'] != '') {
			$q['search'] = "%{$prm['search']}%";
		}

		// QUERY TOTAL
		$sql = "SELECT count(*) as total
				FROM m_company
				WHERE 
				M_CompanyName like ?
				AND M_CompanyIsActive = 'Y'";
		$query = $this->db_onedev->query($sql, $q['search']);
		//echo $query;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
		} else {
			$this->sys_error_db("m_city count", $this->db_onedev);
			exit;
		}
		$rows = array('id' => 0, 'name' => 'Semua');
		$sql = "
			SELECT M_CompanyID as id, M_CompanyName as name
			FROM m_company
			WHERE 
			M_CompanyName like ?
			AND M_CompanyIsActive	 = 'Y'
			ORDER BY M_CompanyName DESC
		  ";
		$query = $this->db_onedev->query($sql, array($q['search']));

		if ($query) {
			$rows = $query->result_array();
			array_push($rows, array('id' => 0, 'name' => 'Semua'));
			//echo $this->db_onedev->last_query();
			$result = array("total" => $tot_count, "records" => $rows, "total_display" => sizeof($rows));
			$this->sys_ok($result);
		} else {
			$this->sys_error_db("m_company rows", $this->db_onedev);
			exit;
		}
	}

	function getstationstatus()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query = "	SELECT T_SampleStationID as id, T_SampleStationName as name
					FROM t_samplestation 
					WHERE	
						T_SampleStationIsActive = 'Y' AND T_SampleStationIsNonLab = 'ELEKTROMEDIS'
				";
		//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		$rows['statuses'] = array(array('id' => 'NEW', 'name' => 'New'), array('id' => 'DONE', 'name' => 'Done'));

		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function search_staff()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$rows = [];
		$query = "	SELECT M_StaffID as id, M_StaffName as name, M_StaffCode as code, M_UserID as userid
					FROM m_staff
					JOIN m_user ON M_UserM_StaffID = M_StaffID AND M_UserIsActive = 'Y'
					WHERE	
						M_StaffIsActive = 'Y' AND M_StaffCode = '{$prm['search']}' LIMIT 1
				";
		//echo $query;
		$rows = $this->db_onedev->query($query)->row_array();

		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function search_patient()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$search = '%' . $prm["search"] . '%';
		$stationid = $prm["stationid"];
		$statusid = $prm["statusid"];
		$patients = $prm["patients"];
		$where_status = '';
		if ($statusid === 'NEW') {
			$where_status = "AND (ISNULL(T_SamplingQueueLastStatusID) OR T_SamplingQueueLastStatusT_SamplingQueueStatusID <> 5 )";
		} else {
			$where_status = "AND T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5";
		}

		// echo $norm;
		//$where_status = " AND {$where_status}";

		$sql_where = "WHERE T_OrderHeaderLabNumber LIKE '{$search}' AND T_OrderHeaderIsActive = 'Y' {$where_status}";
		$rows = [];
		$query = 	"SELECT t_orderheader.*,m_patient.*, IFNULL(M_PatientPhoto,'') as M_PatientPhotoThumb,
				M_SexName, M_TitleName, CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, M_CompanyName,
				IF(ISNULL(T_SamplingQueueLastStatusID), 'New',T_SamplingQueueStatusName) as status, DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
				IF(ISNULL(T_SamplingQueueLastStatusID), 0,T_SamplingQueueLastStatusT_SamplingQueueStatusID) as statusid, T_SampleStationID, T_SampleTypeID,
				{$stationid} as stationid,
				fn_global_check_is_cito(T_OrderHeaderID) as iscito,
				T_SamplingQueueLastStatusConfirm as status_confirm
				FROM t_orderheader	
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = 'ELEKTROMEDIS'
				JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID AND ( Last_StatusM_StatusID = 3 OR Last_StatusM_StatusID = 5 )
				LEFT JOIN t_sampling_queue_last_status ON 
						T_SamplingQueueLastStatusT_SampleStationID = T_SampleStationID AND 
						T_SamplingQueueLastStatusT_OrderHeaderID = T_OrderHeaderID
				LEFT JOIN t_sampling_queue_status ON T_SamplingQueueLastStatusT_SamplingQueueStatusID = T_SamplingQueueStatusID
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_SampleStationID = T_SampleStationID AND T_OrderSampleReqT_OrderSampleID
				$sql_where
				GROUP BY T_OrderHeaderID
				ORDER BY T_OrderHeaderID DESC
				limit 1";
		//echo $query;
		$rows = $this->db_onedev->query($query)->row();

		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	/*function getrequirements($prm){
		
		
		$query ="	SELECT Nat_RequirementID as id, 
		Nat_RequirementName as name, '{$prm['status']}' as status,
		if(ISNULL(T_SamplingSoRequirementID),'N', if(json_contains(T_SamplingSoRequirementRequirements,Nat_RequirementID),'Y','N') ) as chex, 
		Nat_RequirementPositionNat_PositionID as positionid
					FROM nat_requirement
					JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
					JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND Nat_RequirementPositionNat_PositionID = 8 AND 
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
	}*/

	function getrequirements($prm)
	{

		$rows = array();
		$query = "
				SELECT Nat_RequirementID as id, 
				Nat_RequirementName as name, 'P' as status,
				if(ISNULL(T_SamplingSoRequirementID),'N', 
				if(json_contains(T_SamplingSoRequirementRequirements,Nat_RequirementID),'Y','N')) as chex, 
				Nat_RequirementPositionNat_PositionID as positionid
				FROM nat_requirement
				JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
				JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND 
					Nat_RequirementPositionNat_PositionID = 8 AND 
					Nat_RequirementPositionIsActive = 'Y'
				JOIN t_test ON T_TestNat_TestID = Nat_TestRequirementNat_TestID
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = {$prm['orderid']} AND 
					T_SamplingSoT_TestID = {$prm['sampletypeid']} AND 
					T_TestID = T_SamplingSoT_TestID AND
					T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND 
					T_SamplingSoRequirementT_OrderHeaderID = {$prm['orderid']} AND
					T_SamplingSoRequirementNat_PositionID = Nat_RequirementPositionNat_PositionID
				WHERE	
					Nat_TestRequirementIsActive = 'Y'
				GROUP BY nat_requirementID
				UNION
                SELECT Nat_RequirementID as id, 
				Nat_RequirementName as name, 'P' as status,
				if(ISNULL(T_SamplingSoRequirementID),'N', 
				if(json_contains(T_SamplingSoRequirementRequirements,Nat_RequirementID),'Y','N')) as chex, 
				Nat_RequirementPositionNat_PositionID as positionid
				FROM nat_requirement
				JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
				JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND 
					Nat_RequirementPositionNat_PositionID = 8 AND 
					Nat_RequirementPositionIsActive = 'Y' AND 
					Nat_RequirementIsAllTest = 'Y'
				JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = {$prm['orderid']} AND 
					T_SamplingSoT_TestID = {$prm['sampletypeid']} AND 
					T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND 
					T_SamplingSoRequirementT_OrderHeaderID = {$prm['orderid']} AND
					T_SamplingSoRequirementNat_PositionID = Nat_RequirementPositionNat_PositionID
                GROUP BY nat_requirementID
		";
		//echo $query;
		$rows = $this->db_onedev->query($query)->result_array();


		return $rows;
	}

	function doconfirm()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst_data = array();
		$prm = $this->sys_input;
		$sql = "UPDATE t_sampling_queue_last_status SET 
					T_SamplingQueueLastStatusConfirm = 'Y' 
					WHERE T_SamplingQueueLastStatusT_OrderHeaderID = '{$prm['id']}' AND 
						T_SamplingQueueLastStatusT_SampleStationID = '{$prm['stationid']}'";
		//echo $sql;
		$this->db_onedev->query($sql);

		$result = array(
			"total" => 1,
			"records" => $rst_data
		);
		$this->sys_ok($result);

		exit;
	}




	function saverequirement()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$query = "	INSERT INTO t_samplingso_requirement (
						T_SamplingSoRequirementT_OrderHeaderID,
						T_SamplingSoRequirementT_SampleStationID,
						T_SamplingSoRequirementT_SampletypeID,
						T_SamplingSoRequirementStatus,
						T_SamplingSoRequirementRequirements,
						T_SamplingSoRequirementNote,
						T_SamplingSoRequirementNat_PositionID,
						T_SamplingSoRequirementUserID,
						T_SamplingSoRequirementCreated
					)VALUES(
						{$prm['T_OrderHeaderID']},
						{$prm['stationid']},
						{$prm['sample']['T_SampleTypeID']},
						'N',
						
					)";
		//echo $query;
		$rows = $this->db_onedev->query($query)->result_array();


		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function doaction()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst_data = array('status' => 'OK');
		$status_call = array('status' => 'OK', 'data' => array());
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		if ($prm['staff']['id'] != 0 || $prm['staff']['id'] != '0') {
			$userid = $prm['staff']['userid'];
		}

		$sql = "SELECT T_OrderHeaderQueue AS queueNumber ,
				M_LocationID AS locationID, 
				M_LocationName AS locationName FROM t_orderheader 
				JOIN t_order_location ON T_OrderHeaderID = T_OrderLocationT_OrderHeaderID
				JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID
				AND T_OrderLocationT_SampleStationID = {$prm['stationid']}
				WHERE  T_OrderHeaderID={$prm['id']}";
		$location = $this->db_onedev->query($sql)->row_array();
		$locationID = $location['locationID'];
		$locationName = $location['locationName'];
		$queueNumber = $location['queueNumber'];
		$splitedLocationName = explode(" ", $locationName);
		$locationName = $splitedLocationName[0];

		if ($prm['act'] == 'call') {
			$sql = "SELECT T_SamplingQueueLastStatusID, T_SamplingQueueStatusName,  T_SampleStationName, T_SampleStationID, T_SampleStationIsNonLab
					FROM t_sampling_queue_last_status
					JOIN t_sampling_queue_status ON T_SamplingQueueLastStatusT_SamplingQueueStatusID = T_SamplingQueueStatusID 
					JOIN t_samplestation ON T_SampleStationID = T_SamplingQueueLastStatusT_SampleStationID 
					WHERE
					T_SamplingQueueLastStatusT_OrderHeaderID = {$prm['id']} AND 
					T_SamplingQueueLastStatusT_SampleStationID <>  {$prm['stationid']} AND 
					T_SamplingQueueLastStatusT_SamplingQueueStatusID IN (1,3) LIMIT 1";
			$data_status_call = $this->db_onedev->query($sql)->row_array();
			if ($data_status_call) {
				$status_call = array('status' => 'NOTCALL', 'data' => $data_status_call);
				$check_valid = false;
				if ($data_status_call['T_SampleStationIsNonLab'] == '') {
					$sql = "SELECT COUNT(*) as xcount
							FROM t_ordersample
							WHERE
								T_OrderSampleReceive <> 'Y' AND
								T_OrderSampleT_OrderHeaderID = {$prm['id']} AND 
								T_OrderSampleT_SampleStationID = {$data_status_call['T_SampleStationID']} AND 
								T_OrderSampleIsActive = 'Y'
								
							";
					$not_sampled = $this->db_onedev->query($sql)->row_array();
				} else {
					$sql = "SELECT SUM(countx) as xcount
								FROM (
									SELECT COUNT(*) as countx
									FROM t_orderdetail 
									JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
									JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
									JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
									JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
										T_SampleStationID = {$data_status_call['T_SampleStationID']} 
									LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
										T_SamplingSoT_TestID = T_TestID AND 
										T_SamplingSoT_SampleStationID = T_SampleStationID AND
										T_SamplingSoIsActive = 'Y'
									WHERE
									T_OrderDetailT_OrderHeaderID = {$prm['id']} AND T_OrderDetailIsActive = 'Y' AND
									ISNULL(T_SamplingSoDoneDate)
									
								) x";
					$not_sampled = $this->db_onedev->query($sql)->row_array();
				}
				if (intval($not_sampled['xcount']) == 0) {
					$sql = "UPDATE t_sampling_queue_last_status
							SET T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5
							WHERE
								T_SamplingQueueLastStatusT_OrderHeaderID = {$prm['id']} AND 
								T_SamplingQueueLastStatusT_SampleStationID = {$data_status_call['T_SampleStationID']}";
					$this->db_onedev->query($sql);
					$status_call = array('status' => 'OK', 'data' => array());
				}
			}
		}
		$next_status = $prm['statusnextid'];
		if ($prm['act'] == 'process') {
			$sql = "SELECT
						T_OrderHeaderID,
						T_OrderDetailID as id,
						T_OrderDetailT_TestCode, 
						T_OrderDetailT_TestName, 
						T_TestID as test_id,
						T_BahanName
					FROM t_orderheader	
					JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$prm['stationid']}
					LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
						T_SamplingSoT_TestID = T_TestID AND 
						T_SamplingSoT_SampleStationID = T_SampleStationID AND
						T_SamplingSoIsActive = 'Y'
					LEFT JOIN t_samplingso_requirement ON T_SamplingSoRequirementT_OrderHeaderID = T_OrderHeaderID AND 
						T_SamplingSoRequirementT_SamplingSoID = T_SamplingSoID AND
						T_SamplingSoRequirementT_SampleStationID = T_SampleStationID AND
						T_SamplingSoRequirementIsActive = 'Y'
					WHERE
						T_OrderHeaderID = {$prm['id']} AND T_OrderHeaderIsActive = 'Y' AND 
						(ISNULL(T_SamplingSoFlag) OR T_SamplingSoFlag = 'P' OR T_SamplingSoFlag = 'X')
					GROUP BY T_TestID ";
			//echo $sql;
			$rows_all_sample = $this->db_onedev->query($sql)->result();
			if ($rows_all_sample) {
				foreach ($rows_all_sample as $k => $v) {
					$sql = "INSERT INTO t_samplingso (
						T_SamplingSoT_SampleStationID,
						T_SamplingSoT_OrderHeaderID,
						T_SamplingSoT_TestID,
						T_SamplingSoProcessDate,
						T_SamplingSoProcessTime,
						T_SamplingSoProcessUserID,
						T_SamplingSoCreated,
						T_SamplingSoUserID
					)
					VALUES(
						{$prm['stationid']},
						{$prm['id']},
						{$v->test_id},
						CURDATE(),
						CURTIME(),
						{$userid},
						NOW(),
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_SamplingSoProcessDate = CURDATE(), 
							T_SamplingSoProcessTime = CURTIME(), 
							T_SamplingSoFlag = 'P',
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoProcessUserID = {$userid},
							T_SamplingSoUserID = {$userid}";
					//echo $sql;
					$this->db_onedev->query($sql);

					$sql = "INSERT INTO sample_so_by_step (
								SampleSoByStepT_OrderHeaderID,
								SampleSoByStepT_TestID,
								SampleSoByStepCode,
								SampleSoByStepDateTime,
								SampleSoByStepUserID
							)
							VALUES(
								{$prm['id']},
								{$v->test_id},
								'SAMPLING.Sampling.Sampled',
								NOW(),
								{$userid}
							)";
					$this->db_onedev->query($sql);
				}
			}
			$this->broadcast("specimen-col-process");
		}

		if ($prm['act'] == 'samplingdone') {
			$sql = "INSERT INTO t_samplingso (
						T_SamplingSoT_SampleStationID,
						T_SamplingSoT_OrderHeaderID,
						T_SamplingSoT_TestID,
						T_SamplingSoDoneDate,
						T_SamplingSoDoneTime,
						T_SamplingSoDoneUserID,
						T_SamplingSoCreated,
						T_SamplingSoUserID
					)
					VALUES(
						{$prm['stationid']},
						{$prm['id']},
						{$prm['sample']['test_id']},
						CURDATE(),
						CURTIME(),
						{$userid},
						NOW(),
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_SamplingSoDoneDate = CURDATE(), 
							T_SamplingSoDoneTime = CURTIME(), 
							T_SamplingSoFlag = 'D',
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoDoneUserID = {$userid},
							T_SamplingSoUserID = {$userid}";
			$this->db_onedev->query($sql);
			//echo $sql;
			$xreq = $prm['sample']['requirements'];
			$arr_requirements = array();
			foreach ($xreq as $k => $v) {
				if ($v['chex'] == 'Y')
					array_push($arr_requirements, $v['id']);
			}
			$requirements = '[' . join(',', $arr_requirements) . ']';

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
						{$prm['id']},
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
								{$prm['id']},
								{$prm['sample']['test_id']},
								'SAMPLING.Sampling.Received',
								'{$prm['sample']['requirement_status']}',
								'{$requirements}',
								NOW(),
								{$userid}
							)";
			$this->db_onedev->query($sql);

			if ($prm['sample']['type'] === 'xray') {
				$sql = "INSERT INTO t_samplingso_form(
						T_SamplingSoFormT_SampleStationID,
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
						{$prm['stationid']},
						{$prm['sample']['T_SamplingSoID']},
						{$prm['sample']['form_kv']},
						{$prm['sample']['form_ma']},
						{$prm['sample']['form_second']},
						{$prm['sample']['form_expose']},
						{$userid},
						NOW(),
						NOW()
					)ON DUPLICATE KEY UPDATE 
							T_SamplingSoFormT_SampleStationID = {$prm['stationid']},
							T_SamplingSoFormKv = {$prm['sample']['form_kv']},
							T_SamplingSoFormMa = {$prm['sample']['form_ma']},
							T_SamplingSoFormSecond = {$prm['sample']['form_second']},
							T_SamplingSoFormExpose = {$prm['sample']['form_expose']},
							T_SamplingSoFormUserID = {$userid}";
				//echo $sql;
				$this->db_onedev->query($sql);
			}

			$sql = "SELECT COUNT(*) as xcount
					FROM (
						SELECT T_SamplingSoID, T_SamplingSoFlag ,T_OrderDetailT_TestID, T_OrderDetailID 
						FROM t_orderdetail
						JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
						JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
						JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
						JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID  AND 
						T_SampleStationID = {$prm['stationid']}
						LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
											T_SamplingSoT_TestID = T_TestID AND 
											T_SamplingSoT_SampleStationID = T_SampleStationID AND
											T_SamplingSoIsActive = 'Y'
						WHERE
						T_OrderDetailT_OrderHeaderID = {$prm['id']} AND T_OrderDetailIsActive = 'Y' 
						GROUP BY T_TestID
						HAVING ISNULL(T_SamplingSoID) OR T_SamplingSoFlag = 'P' OR T_SamplingSoFlag = 'X'
					) xx";
			//echo $sql;
			$xcount = $this->db_onedev->query($sql)->row()->xcount;
			$rst_data = array('status' => 'PARTIAL');
			if ($xcount == 0) {
				$next_status = 5;
				$rst_data = array('status' => 'OK');
			}
			$this->broadcast("specimen-col-receive");
		}

		if ($prm['act'] !== 'samplingprocess' && $status_call['status'] == 'OK') {
			$dt_json = json_encode(array('T_SampleStationID' => $prm['stationid'], 'T_OrderHeaderID' => $prm['id'], 'T_SamplingQueueStatusID' => $next_status));
			$query = "INSERT INTO  one_log.log_sampling_queue (Log_SamplingQueueDate,Log_SamplingQueueJSON,Log_SamplingQueueUserID)
						VALUES(NOW(),'{$dt_json}',{$userid})";
			//echo $query;
			$rows = $this->db_onedev->query($query);
			$sql = "SELECT * 
					FROM t_sampling_queue_last_status
					WHERE 
					T_SamplingQueueLastStatusT_SampleStationID = {$prm['stationid']} AND
					T_SamplingQueueLastStatusT_OrderHeaderID = {$prm['id']} AND 
					T_SamplingQueueLastStatusIsActive = 'Y'";
			$data_last = $this->db_onedev->query($sql)->row();

			$query = "INSERT INTO  t_sampling_queue_last_status (
					T_SamplingQueueLastStatusT_SampleStationID,
					T_SamplingQueueLastStatusT_OrderHeaderID,
					T_SamplingQueueLastStatusT_SamplingQueueStatusID,
					T_SamplingQueueLastStatusUserID)
				VALUES(
					{$prm['stationid']},
					{$prm['id']},
					{$next_status},
					{$userid}) ON DUPLICATE KEY UPDATE 
						T_SamplingQueueLastStatusT_SamplingQueueStatusID = {$next_status},
						T_SamplingQueueLastStatusUserID = {$userid}
				";
			//echo $query;
			$rows = $this->db_onedev->query($query);
		}

		if ($status_call['status'] == 'NOTCALL') {
			$rst_data = $status_call;
		}

		if ($prm['act'] == 'skip' || $status_call['status'] == 'NOTCALL') {
			$skip_time = date('Y-m-d H:i:s', strtotime($prm['skiptime'])+10);
			$sql = "UPDATE antrian_samplestation SET AntrianSampleStationIsActive = 'N'
					WHERE
					AntrianSampleStationT_OrderLocationID = ?";
			$query = $this->db_onedev->query($sql,array($prm['orderlocationid']));
			/* start dipaggil 3 kali skpi ururtan jd ke bawah */
			/*$sql = "SELECT COUNT(*) as x_count
					FROM antrian_samplestation 
					WHERE AntrianSampleStationT_OrderLocationID = ? AND 
					AntrianSampleStationIsActive = 'N' AND 
					DATE(AntrianSampleStationTime) = DATE(NOW())";
			$query = $this->db_onedev->query($sql,array($prm['orderlocationid']));
			$xcount_skip = $query->row()->x_count;
			
			$modby3 = $xcount_skip % 2;
			if($xcount_skip > 0 && $modby3 == 0){
				$skip_time = date('Y-m-d H:i:s', strtotime($prm['last_skiptime'])+1);
			}*/
			/* end dipaggil 3 kali skpi ururtan jd ke bawah */
			$sql = "INSERT INTO antrian_samplestation(
						AntrianSampleStationT_OrderLocationID,
						AntrianSampleStationTime,
						AntrianSampleStationUserID,
						AntrianSampleStationCreated
					)
					VALUES(
						?,?,?,NOW()
					)";
			$query = $this->db_onedev->query($sql,array($prm['orderlocationid'],$skip_time,$userid));
			
		}

		if (intval($next_status) == 1) {
			if ($status_call['status'] == 'OK') {
				file_get_contents("http://" . $this->IP_SOCKET_IO . ":9099/broadcast/call.sm.{$locationID}.{$queueNumber}.{$locationName}"); //CALL NODE
			}
		} else if (intval($next_status) == 2) {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9099/broadcast/skip.sm.{$locationID}");
		} else if (intval($next_status) == 3) {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9099/broadcast/serve.sm.{$locationID}");
		} else if (intval($next_status) == 5) {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9099/broadcast/done.sm.{$locationID}");
		} else {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9099/broadcast/done.sm.{$locationID}");
		}



		$result = array(
			"total" => 1,
			"records" => $rst_data
		);
		$this->sys_ok($result);

		exit;
	}


	function getdatanoterequirement()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst_data = array();
		$prm = $this->sys_input;

		$sql = "SELECT IFNULL(T_OrderHeaderFoNote,'') as fo_note, 
				fn_getstaffname(T_OrderHeaderFoNoteM_UserID) as fo_note_user,
				IFNULL(T_OrderHeaderVerificationNote,'') as fo_ver_note, 
				fn_getstaffname(T_OrderHeaderVerificationNoteM_UserID) as fo_ver_note_user,
				IFNULL(T_OrderHeaderSamplingNote,'') as sampling_note, 
				fn_getstaffname(T_OrderHeaderSamplingNoteM_UserID) as sampling_note_user
				FROM t_orderheader
				WHERE
					T_OrderHeaderID = {$prm['T_OrderHeaderID']}";
		$notes = $this->db_onedev->query($sql)->row_array();

		$sql = "SELECT 'fo registration' as position,GROUP_CONCAT(DISTINCT Nat_RequirementName separator ',') as requirements
				FROM t_orderheader
				JOIN t_orderreq ON T_OrderReqT_OrderHeaderID = T_OrderHeaderID
				JOIN nat_requirement ON json_contains(T_OrderReqs,Nat_RequirementID)
				WHERE T_OrderHeaderID = {$prm['T_OrderHeaderID']}
				GROUP BY T_OrderHeaderID";
		//echo $sql;
		$query = $this->db_onedev->query($sql)->row_array();
		if ($query) {
			array_push($rst_data, $query);
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
		if ($query) {
			array_push($rst_data, $query);
		}

		$result = array(
			"total" => 1,
			"records" => array('notes' => $notes, 'reqs' => $rst_data)
		);
		$this->sys_ok($result);

		exit;
	}

	function savenotesampling()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst_data = array();
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];

		$sql = "UPDATE t_orderheader SET 
					T_OrderHeaderSamplingNote = '{$prm['sampling_note']}',
					T_OrderHeaderSamplingNoteM_UserID = {$userid}
				WHERE 
					T_OrderHeaderID = {$prm['T_OrderHeaderID']}";
		//echo $sql;
		$query = $this->db_onedev->query($sql);

		$result = array(
			"total" => 1,
			"records" => $rst_data
		);
		$this->sys_ok($result);

		exit;
	}

	function getlocation()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$stationID = $prm['station_id'];
		$rows = [];
		$query = "SELECT M_LocationID AS locationID,
					M_LocationName AS locationName
					FROM m_location
					WHERE M_LocationT_SampleStationID= ? 
					AND M_LocationIsActive = 'Y'
				";
		//echo $query;
		$qry = $this->db_onedev->query($query, [$stationID]);
		if (!$qry) {
			$error = array(
				"message" => $this->db_onedev->error()["message"],

			);
			$this->sys_error($error);
			exit;
		}
		$rows = $qry->result_array();


		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}
}
