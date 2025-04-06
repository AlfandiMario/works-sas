<?php
class Samplingcall extends MY_Controller
{
	var $db_onedev;
	var $IP_SOCKET_IO = "127.0.0.1";
	public function __construct()
	{
		parent::__construct();
		$this->db_onedev = $this->load->database("onedev", true);
		$this->IP_SOCKET_IO = "127.0.0.1";
	}

	public function index()
	{
		echo "Samplingcall API";
		// Untuk FE: test/vuex/cpone-sample-all/
	}

	public function getstations()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query = "	SELECT T_SampleStationID as id, T_SampleStationName as name, T_SampleStationIsNonLab as isnonlab, T_SampleStationIsAdditionalFisik as isadditional
						FROM t_samplestation 
						WHERE	
							T_SampleStationIsActive = 'Y'
							ORDER BY T_SampleStationOrder ASC
					";
		//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		$rows['statuses'] = array(array('id' => 'NEW', 'name' => 'New'), array('id' => 'DONE', 'name' => 'Done'));

		$urls = [];
		$query = "	SELECT * FROM `s_menu` WHERE `S_MenuUrl` LIKE '%cpone-resultentry-other-fisik-mobile%' AND S_MenuIsActive = 'Y' LIMIT 1";
		$urls['url_fisik'] = $this->db_onedev->query($query)->row()->S_MenuUrl;

		$query = "	SELECT * FROM `s_menu` WHERE `S_MenuUrl` LIKE  '%test/vuex/one-resultentry-so-electromedis%'  AND S_MenuIsActive = 'Y' LIMIT 1";
		$urls['url_electromedis'] = $this->db_onedev->query($query)->row()->S_MenuUrl;

		$query = "	SELECT * FROM `s_menu` WHERE `S_MenuUrl` LIKE  '%test/vuex/one-resultentry-so-xray%'  AND S_MenuIsActive = 'Y' LIMIT 1";
		$urls['url_xray'] = $this->db_onedev->query($query)->row()->S_MenuUrl;

		$query = "	SELECT * FROM `s_menu` WHERE `S_MenuName` =  'Specimen Collection Mobile'  AND S_MenuIsActive = 'Y' LIMIT 1";
		$urls['url_sampling'] = $this->db_onedev->query($query)->row()->S_MenuUrl;


		$result = array(
			"total" => count($rows),
			"records" => $rows,
			"urls" => $urls
		);
		$this->sys_ok($result);
		exit;
	}

	public function listpatients()
	{
		try {
			if (!$this->isLogin) {
				$this->sys_error("Invalid Token");
				exit;
			}
			$prm = $this->sys_input;
			$name = $prm["name"];
			$nolab = $prm["nolab"];
			$stationid = $prm["stationid"];
			$xdate = $prm["xdate"];
			$statusid = $prm["statusid"];
			$corpID = isset($prm["corpID"]) ? $prm["corpID"] : 0;
			$search = isset($prm["searchx"]) ? $prm["searchx"] : '';

			$filter_corp = '';
			if ($corpID != 0 || $corpID != '0') {
				$filter_corp = " AND CorporateID = {$corpID} ";
			}

			$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND DATE(T_OrderHeaderDate) = '{$xdate}'";

			if ($name != "") {
				if ($sql_where != "") {
					$sql_where .= " AND ";
				}
				$sql_where .= " M_PatientName like '%$name%' ";
			}

			$filter_search = '';
			if ($nolab != "") {
				$filter_search = "WHERE (T_OrderHeaderLabNumber like '%$nolab%' OR M_PatientName like '%$nolab%')";
			}

			// Cek Station Type
			$sql = "SELECT * FROM t_samplestation WHERE T_SampleStationID = {$stationid}";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$this->sys_error_db("data t_samplestation");
				exit;
			}
			$data_station = $query->row_array();
			$stationtype = $data_station['T_SampleStationIsNonLab'];
			$stationadditional = $data_station['T_SampleStationIsAdditionalFisik'];

			$patients = [];
			$sql = "";
			if ($stationtype == '') {
				$sql = "SELECT * FROM ( 
				SELECT DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
					T_OrderHeaderID,
					T_OrderHeaderLabNumber,
					T_OrderHeaderM_PatientAge,
					T_OrderLocationT_SampleStationID as T_SampleStationID,
					IFNULL(T_OrderHeaderFoNote,'') as fo_note,
					M_PatientName as patient_fullname,
					M_PatientNoReg,
					IFNULL(M_PatientPhotoThumb,'') as M_PatientPhotoThumb,
					IF(M_PatientGender = 'male','Laki-laki','Perempuan') as M_PatientGender,
					DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
					M_PatientJob,
					M_PatientPosisi,
					IF(M_PatientDivisi = '','-',M_PatientDivisi) M_PatientDivisi,
					M_PatientHp,
					M_PatientNIP,
					M_PatientEmail,
					M_PatientPhoto,
					0 as testid,
					CorporateName,
					fn_sampling_queue_status_id(T_OrderHeaderID,T_OrderLocationT_SampleStationID)  as statusid,
					fn_sampling_reqs_status(T_OrderHeaderID) as fo_requirements_status,
					fn_sampling_queue_status_name(T_OrderHeaderID,T_OrderLocationT_SampleStationID) as status
				FROM t_orderheader
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN corporate ON T_OrderHeaderCorporateID = CorporateID $filter_corp
					JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
						T_OrderSampleT_SampleStationID = {$stationid} AND 
						T_OrderSampleIsActive = 'Y'
					JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderSampleT_OrderHeaderID AND 
					T_OrderLocationT_SampleStationID = T_OrderSampleT_SampleStationID AND
					T_OrderLocationIsActive = 'Y'
	
				$sql_where
				GROUP BY T_OrderHeaderID
					) x 
					$filter_search
				";
			} else {
				$sql = "SELECT * FROM ( 
				SELECT DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
				T_OrderHeaderID,
				T_OrderHeaderLabNumber,
				T_OrderHeaderM_PatientAge,
				T_OrderLocationT_SampleStationID as T_SampleStationID,
				IFNULL(T_OrderHeaderFoNote,'') as fo_note,
				M_PatientName as patient_fullname,
				M_PatientNoReg,
				IFNULL(M_PatientPhotoThumb,'') as M_PatientPhotoThumb,
				IF(M_PatientGender = 'male','Laki-laki','Perempuan') as M_PatientGender,
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
				M_PatientJob,
				M_PatientPosisi,
				IF(M_PatientDivisi = '','-',M_PatientDivisi) M_PatientDivisi,
				M_PatientHp,
				M_PatientNIP,
				M_PatientEmail,
				M_PatientPhoto,
				0 as testid,
				CorporateName,
				fn_sampling_queue_status_id(T_OrderHeaderID,T_OrderLocationT_SampleStationID)  as statusid,
				fn_sampling_reqs_status(T_OrderHeaderID) as fo_requirements_status,
				fn_sampling_queue_status_name(T_OrderHeaderID,T_OrderLocationT_SampleStationID) as status
				FROM t_orderheader
				JOIN corporate ON T_OrderHeaderCorporateID = CorporateID $filter_corp
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID 
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
				JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
					T_OrderLocationT_SampleStationID = T_SampleStationID AND
					T_OrderLocationIsActive = 'Y'
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND T_SamplingSoIsActive = 'Y'	
	
				$sql_where
				GROUP BY T_OrderHeaderID
				) x 
					$filter_search
				";
			}

			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$error = json_encode($this->db_onedev->error(), JSON_PRETTY_PRINT);
				$this->sys_error_db($error);
				exit;
			}
			$patients = $query->result_array();
			$this->sys_ok([
				"total" => count($patients),
				"records" => $patients
			]);
			exit;
		} catch (Exception $exc) {
			$msg = json_encode($exc->getMessage(), JSON_PRETTY_PRINT);
			$this->sys_error($msg);
		}
	}

	public function getsampletypes()
	{
		try {
			if (!$this->isLogin) {
				$this->sys_error("Invalid Token");
				exit;
			}
			$prm = $this->sys_input;
			$stationid = $prm['stationid'];

			// Get T_SampleStationIsNonLab as Category, if '' category is Lab 
			$sql = "SELECT T_SampleStationIsNonLab as category
					FROM t_samplestation
					WHERE T_SampleStationID = {$stationid} AND T_SampleStationIsActive = 'Y'";
			$qry = $this->db_onedev->query($sql)->row();
			if (!$qry) {
				$this->sys_error("Invalid Station ID");
				exit;
			}
			$stationCategory = $qry->category;
			if ($stationCategory == '') {
				$result = $this->getsampletypeslab($prm);
			} else {
				$result = $this->getsampletypesnonlab($prm);
			}

			$resp = [
				"total" => count($result),
				"records" => [
					"sampletypes" => $result
				]
			];

			$this->sys_ok($resp);
			exit;
		} catch (Exception $exception) {
			$msg = json_encode($exception->getMessage(), JSON_PRETTY_PRINT);
			$this->sys_error($msg);
		}
	}

	private function getsampletypeslab($prm)
	{
		$stationid = $prm['stationid'];
		$labnumber = $prm['labnumber'];

		$sql = "SELECT 
					'' as groupresult_name,
					T_SampleTypeName as sampletype_name,
					T_OrderSampleBarcode as barcode,
					IF(ISNULL(T_OrderSampleSamplingDate),'Belum diambil',DATE_FORMAT(T_OrderSampleSamplingDate,'%d-%m-%Y')) as sampling_date,
					IF(ISNULL(T_OrderSampleSamplingTime),'',T_OrderSampleSamplingTime) as sample_time,
					IF(ISNULL(T_OrderSampleReceiveDate),'Belum dilakukan',DATE_FORMAT(T_OrderSampleReceiveDate,'%d-%m-%Y')) as receive_date,
					IF(ISNULL(T_OrderSampleReceiveTime),'',DATE_FORMAT(T_OrderSampleReceiveTime,'%H:%i')) as receive_time,
					T_OrderSampleSampling as is_sampling,
					T_OrderSampleReceive as is_received
					FROM t_ordersample
					JOIN t_orderheader ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
					T_OrderSampleIsActive = 'Y' AND
					T_OrderHeaderLabNumber = '{$labnumber}'
					JOIN t_sampletype ON T_OrderSampleT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
					JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
						T_OrderLocationT_SampleStationID = T_SampleStationID AND
						T_OrderLocationIsActive = 'Y' 
						-- AND T_OrderLocationM_LocationID = {$prm['location_id']}
			";

		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$err = json_encode($this->db_onedev->error(), JSON_PRETTY_PRINT);
			$this->sys_error_db($err);
			exit;
		}
		$result = $query->result_array();

		return $result;
	}

	private function getsampletypesnonlab($prm)
	{
		$stationid = $prm['stationid'];
		$labnumber = $prm['labnumber'];

		$sql = "SELECT 
			Group_ResultName as groupresult_name,
			T_TestName as sampletype_name,
			T_OrderHeaderLabNumber as barcode,
			IF(ISNULL(T_SamplingSoProcessDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as sampling_date,
			IF(ISNULL(T_SamplingSoProcessTime),'',T_SamplingSoProcessTime) as sample_time,
			IF(ISNULL(T_SamplingSoDoneDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as receive_date,
			IF(ISNULL(T_SamplingSoDoneTime),'',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as receive_time,
			IF(T_SamplingSoFlag = 'P','Y','N') as is_sampling,
			IF(T_SamplingSoFlag = 'D','Y','N') as is_received,
			T_OrderDetailT_TestID as test_id
				FROM t_orderdetail
			JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND 
			T_OrderDetailIsActive = 'Y' AND
			T_OrderHeaderLabNumber = ?
			JOIN t_test ON T_OrderDetailT_TestID = T_TestID	
			JOIN group_resultdetail
			ON 	T_OrderDetailT_TestID  = Group_ResultDetailT_TestID
			AND Group_ResultDetailIsActive = 'Y'
			JOIN group_result 
			ON Group_ResultDetailGroup_ResultID = Group_ResultID
			AND Group_ResultIsActive = 'Y'			
			JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
			JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
			JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = ?
			JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
				T_OrderLocationT_SampleStationID = T_SampleStationID AND
				T_OrderLocationIsActive = 'Y'
			LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID =T_OrderHeaderID AND 
				T_SamplingSoT_TestID = T_OrderDetailT_TestID AND
				T_SamplingSoIsActive = 'Y'
			GROUP BY Group_ResultID";

		$query = $this->db_onedev->query($sql, [$labnumber, $stationid]);
		if (!$query) {
			$err = json_encode($this->db_onedev->error(), JSON_PRETTY_PRINT);
			$this->sys_error_db($err);
			exit;
		}
		$result = $query->result_array();

		return $result;
	}

	// Call Patient
	public function doaction()
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

		$sql = "SELECT '' AS queueNumber ,
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
				// Jika sample dari lab
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
				}
				// Jika sample dari non lab
				else {
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
			$sql = "SELECT T_OrderDetailID, T_OrderHeaderID,T_OrderDetailID as id,
				T_BarcodeLabID,
				T_BarcodeLabBarcode,
				T_OrderDetailT_TestCode, 
				T_OrderDetailT_TestName, 
				T_SampleTypeID,
				T_SampleTypeName, 
				T_BahanName
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_ordersample ON  
							T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND
							T_OrderSampleIsActive = 'Y' AND T_OrderSampleReceive = 'N'
					JOIN t_barcodelab ON T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_BarcodeLabIsActive = 'Y'
					JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$prm['stationid']}
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderHeaderID = T_OrderHeaderID AND 
								T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND
								T_OrderSampleReqNat_PositionID = 2 AND 
								T_OrderSampleReqT_SampleStationID = T_SampleStationID AND
								T_OrderSampleReqIsActive = 'Y'
				WHERE
					T_OrderHeaderID = {$prm['id']} AND (T_OrderSampleReceive = 'N' OR T_OrderSampleSampling = 'X') AND T_OrderHeaderIsActive = 'Y'
				GROUP BY T_BarcodeLabID";
			//echo $sql;
			$rows_all_sample = $this->db_onedev->query($sql)->result();
			if ($rows_all_sample) {
				foreach ($rows_all_sample as $k => $v) {
					$sql = "INSERT INTO t_ordersample (
						T_OrderSampleT_OrderHeaderID,
						T_OrderSampleT_SampleTypeID,
						T_OrderSampleT_BarcodeLabID,
						T_OrderSampleCreated,
						T_OrderSampleUserID
					)
					VALUES(
						{$prm['id']},
						{$v->T_SampleTypeID},
						{$v->T_BarcodeLabID},
						NOW(),
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_OrderSampleSampling = 'Y',
							T_OrderSampleSamplingDate = CURDATE(),
							T_OrderSampleSamplingTime = CURTIME(),
							T_OrderSampleSamplingUserID = {$userid},
							T_OrderSampleIsActive = 'Y',
							T_OrderSampleUserID = {$userid}";
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
								'SAMPLING.Sampling.Sampled',
								{$prm['id']},
								{$v->T_BarcodeLabID},
								'Y',
								'[]',
								{$userid},
								NOW()
							)";
					$this->db_onedev->query($sql);
					//echo $sql;
				}
				$this->broadcast("specimen-col-process");
			}
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
						T_SamplingQueueLastStatusUserID = {$userid}";
			//echo $query;
			$rows = $this->db_onedev->query($query);
		}

		if ($status_call['status'] == 'NOTCALL') {
			$rst_data = $status_call;
		}

		if ($prm['act'] == 'skip' || $status_call['status'] == 'NOTCALL') {
			$skip_time = date('Y-m-d H:i:s', strtotime($prm['skiptime']) + 10);
			$sql = "UPDATE antrian_samplestation SET AntrianSampleStationIsActive = 'N'
					WHERE
					AntrianSampleStationT_OrderLocationID = ?";
			$query = $this->db_onedev->query($sql, array($prm['orderlocationid']));

			$sql = "INSERT INTO antrian_samplestation(
						AntrianSampleStationT_OrderLocationID,
						AntrianSampleStationTime,
						AntrianSampleStationUserID,
						AntrianSampleStationCreated
					)
					VALUES(
						?,?,?,NOW()
					)";
			$query = $this->db_onedev->query($sql, array($prm['orderlocationid'], $skip_time, $userid));
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
			"records" => $rst_data,
			"nextstatus" => $next_status
		);
		$this->sys_ok($result);

		exit;
	}



	/* 
		--- MAYBE UNUSED ---
		--- DARI DEVONE ---
	*/

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
		$xdate = $prm["xdate"];
		// $statusid = 'NEW';
		$statusid = $prm["statusid"];
		$companyid = isset($prm["companyid"]) ? $prm["companyid"] : 0;
		$search = isset($prm["searchx"]) ? $prm["searchx"] : '';
		// $locationID = $prm['locationid'];
		$locationID = '';
		$filter_companyid = '';
		if ($companyid != 0 || $companyid != '0') {
			$filter_companyid = " AND M_CompanyID = {$companyid} ";
		}
		$where_status = '';
		$limit = '';
		$not_receive = '';
		if ($statusid === 'NEW') {
			$where_status = "AND T_OrderSampleReceive = 'N'";
		} else {
			$limit = 'limit 0,20';
			$where_status = "AND T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5";
		}
		// TODO: check ulang, jika T_OrderSampleReceive diperlukan
		$where_status = "";

		$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND ( DATE(T_OrderHeaderAddonIsComingDate) = '{$xdate}' OR DATE(T_OrderHeaderDate) = '{$xdate}' ) {$where_status}";

		if ($name != "") {
			if ($sql_where != "") {
				$sql_where .= " and ";
			}
			$sql_where .= " M_PatientName like '%$name%' ";
		}
		$filter_search = '';
		if ($nolab != "") {

			$filter_search = "WHERE ( T_OrderHeaderLabNumber like '%$nolab%' OR M_PatientName like '%$nolab%' OR T_OrderHeaderLabNumberExt like '%$nolab%'  )";
		}

		if ($search != '') {
			$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND T_OrderHeaderLabNumber = '{$search}'";
		}

		$rst = array();

		$sql = 	"SELECT * FROM (
					SELECT t_orderheader.*, 
					DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
					IFNULL(M_PatientPhotoThumb,'') as M_PatientPhotoThumb,
					M_PatientName as M_PatientName,
					M_SexName, 
					M_TitleName, 
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
					M_CompanyName,
					fn_sampling_queue_status_name(T_OrderHeaderID,T_SampleStationID) as status,
					DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
					fn_sampling_queue_status_id(T_OrderHeaderID,T_SampleStationID)  as statusid, 
					T_SampleStationID, T_SampleTypeID,
					-- T_SampleStationID as stationid,
					-- Tambahan
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
					T_OrderHeaderAddonIsComing as coming,
					T_OrderLocationID as order_location_id,
					IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as antri_time,
					IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as skip_time					

					FROM t_orderheader	
					JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID
					JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID $filter_companyid
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND T_OrderLocationT_SampleStationID = {$stationid}  AND T_OrderLocationIsActive = 'Y'
					
					LEFT JOIN antrian_samplestation ON AntrianSampleStationT_OrderLocationID =T_OrderLocationID AND AntrianSampleStationIsActive = 'Y'
					LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
					JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
					JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
					LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
					T_SamplingSoT_TestID = T_TestID AND 
					T_SamplingSoT_SampleStationID = T_SampleStationID AND
					T_SamplingSoIsActive = 'Y'

					$sql_where
					GROUP BY T_OrderHeaderID
					HAVING last_status_fo IN (3,5)
				)x
				$filter_search
				ORDER BY T_OrderHeaderIsCito DESC, antri_time ASC
				";

		$query = $this->db_onedev->query($sql);
		$rows = $query->result_array();

		if ($rows) {
			$count_arr = count($rows);
			foreach ($rows as $key => $value) {
				if ($key + 1 != $count_arr) {
					$rows[$key]['skip_time'] = $rows[$key + 1]['antri_time'];
				}
			}
		}
		$result = array("total" => count($rst), "records" => $rows, "sql" => $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
	}

	public function search_old()
	{
		$prm = $this->sys_input;
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$name = $prm["name"];
		$nolab = $prm["nolab"];
		$stationid = $prm["stationid"];
		$xdate = $prm["xdate"];
		$statusid = 'NEW';
		$companyid = isset($prm["companyid"]) ? $prm["companyid"] : 0;
		$search = isset($prm["searchx"]) ? $prm["searchx"] : '';
		$locationID = $prm['locationid'];
		$filter_companyid = '';
		if ($companyid != 0 || $companyid != '0') {
			$filter_companyid = " AND M_CompanyID = {$companyid} ";
		}
		$where_status = '';
		$limit = '';
		$not_receive = '';
		if ($statusid === 'NEW') {
			$where_status = "AND T_OrderSampleReceive = 'N'";
		} else {
			$limit = 'limit 0,20';
			$where_status = "AND T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5";
		}

		$sql_where_cito = "WHERE T_OrderHeaderIsCito = 'Y' AND T_OrderHeaderIsActive = 'Y' AND ( DATE(T_OrderHeaderAddonIsComingDate) = '{$xdate}' OR DATE(T_OrderHeaderDate) = '{$xdate}' ) {$where_status}";

		$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND ( DATE(T_OrderHeaderAddonIsComingDate) = '{$xdate}' OR DATE(T_OrderHeaderDate) = '{$xdate}' ) {$where_status}";

		if ($name != "") {
			if ($sql_where != "") {
				$sql_where .= " and ";
			}
			$sql_where .= " M_PatientName like '%$name%' ";
		}
		$filter_search = '';
		if ($nolab != "") {

			$filter_search = "WHERE ( T_OrderHeaderLabNumber like '%$nolab%' OR M_PatientName like '%$nolab%' OR T_OrderHeaderLabNumberExt like '%$nolab%'  )";
		}

		if ($search != '') {
			$sql_where = "WHERE T_OrderHeaderIsActive = 'Y' AND T_OrderHeaderLabNumber = '{$search}'";
		}

		$rst = array();

		$sql = 	"SELECT * FROM (
					SELECT t_orderheader.*, 
					IFNULL(M_PatientPhotoThumb,'') as M_PatientPhotoThumb,
					M_SexName as M_SexName, 
					M_TitleName as M_TitleName, 
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
					M_PatientName as M_PatientName,
					M_CompanyName,
					fn_sampling_queue_status_name(T_OrderHeaderID,T_SampleStationID) as status,
					DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
					fn_sampling_queue_status_id(T_OrderHeaderID,T_SampleStationID)  as statusid, T_SampleStationID, T_SampleTypeID,
					T_SampleStationID as stationid,
					fn_fo_get_laststatus(T_OrderHeaderID) as last_status_fo,
					T_OrderHeaderAddonIsComing as coming,
					T_OrderHeaderIsCito as iscito,
					IFNULL(T_OrderHeaderFoNote,'') as fo_note,
					IFNULL(T_OrderHeaderVerificationNote,'') as fo_ver_note,
					fn_sampling_reqs(T_OrderHeaderID) as fo_requirements,
					'' as htmlforeqs,
					fn_sampling_reqs_status(T_OrderHeaderID) as fo_requirements_status,
					IF(fn_fo_get_verification_status(T_OrderHeaderID) = 0, 'X','Y') as fo_verification_status,
					IFNULL(T_OrderHeaderSamplingNote,'') as sampling_note,
					fn_cek_status_sample_receive_not_yet(T_OrderHeaderID) as not_yet_receive,
					T_OrderLocationID as order_location_id,
					IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as antri_time,
					IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as skip_time
					FROM t_orderheader	
					JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID
					JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID $filter_companyid
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND T_OrderLocationT_SampleStationID = {$stationid}  AND T_OrderLocationIsActive = 'Y'
					LEFT JOIN antrian_samplestation ON AntrianSampleStationT_OrderLocationID =T_OrderLocationID AND AntrianSampleStationIsActive = 'Y'
					JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID AND M_LocationID = {$locationID}
					JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID AND ( Last_StatusM_StatusID > 3 OR Last_StatusM_StatusID NOT IN (4,6) )
					JOIN t_ordersample ON  
							T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND
							T_OrderSampleIsActive = 'Y'
					JOIN t_barcodelab ON T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_BarcodeLabIsActive = 'Y'
					JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} 
					$sql_where
					GROUP BY T_OrderHeaderID
				)x
				$filter_search
				ORDER BY T_OrderHeaderIsCito DESC, antri_time ASC
				";

		$query = $this->db_onedev->query($sql);
		$rows = $query->result_array();

		if ($rows) {
			$count_arr = count($rows);
			foreach ($rows as $key => $value) {
				if ($key + 1 != $count_arr) {
					$rows[$key]['skip_time'] = $rows[$key + 1]['antri_time'];
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
						T_SampleStationIsActive = 'Y' ";

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
				fn_global_check_is_cito(T_OrderHeaderID) as iscito
				FROM t_orderheader	
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_ordersample ON  
							T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND
							T_OrderSampleIsActive = 'Y'
				JOIN t_barcodelab ON T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_BarcodeLabIsActive = 'Y'
				JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid} AND T_SampleStationIsNonLab = ''
				JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID AND ( Last_StatusM_StatusID > 3 OR Last_StatusM_StatusID NOT IN (4,6) )
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

	function getrequirements($prm)
	{

		$rows = array();
		$query = "
                       SELECT Nat_RequirementID as id, 
                       Nat_RequirementName as name, 'P' as status,
                       T_OrderSampleReceive,
                       if(ISNULL(T_OrderSampleReqID),'N', if(json_contains(T_OrderSampleReqs,Nat_RequirementID),'Y','N') ) as chex, 
                       Nat_RequirementPositionNat_PositionID as positionid
                      FROM nat_requirement
                      JOIN nat_testrequirement ON Nat_TestRequirementNat_RequirementID = Nat_RequirementID
                      JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID AND Nat_RequirementPositionNat_PositionID = 2 AND 
                               Nat_RequirementPositionIsActive = 'Y'
                      JOIN t_test ON T_TestNat_TestID = Nat_TestRequirementNat_TestID
                      JOIN t_barcodelab ON T_barcodeLabT_OrderHeaderID = {$prm['orderid']} AND T_BarcodeLabT_SampleTypeID = {$prm['sampletypeid']}
                      JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = {$prm['orderid']} AND T_OrderSampleT_SampleTypeID = {$prm['sampletypeid']} AND 
                      T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleIsActive = 'Y'
                      LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND T_OrderSampleReqT_OrderHeaderID = {$prm['orderid']} AND
                                      T_OrderSampleReqNat_PositionID = Nat_RequirementPositionNat_PositionID
                      WHERE	
                              Nat_TestRequirementIsActive = 'Y'
                      GROUP BY nat_requirementID
                     union
                       SELECT Nat_RequirementID as id, 
                       Nat_RequirementName as name, 'P' as status,
                       T_OrderSampleReceive,
                       if(ISNULL(T_OrderSampleReqID),'N', if(json_contains(T_OrderSampleReqs,Nat_RequirementID),'Y','N') ) as chex, 
                       Nat_RequirementPositionNat_PositionID as positionid
                      FROM nat_requirement
                      JOIN nat_requirementposition ON Nat_RequirementPositionNat_RequirementID = Nat_RequirementID 
                           AND Nat_RequirementPositionNat_PositionID = 2 AND Nat_RequirementPositionIsActive = 'Y'
                           AND Nat_RequirementIsAllTest = 'Y'
                      JOIN t_barcodelab ON T_barcodeLabT_OrderHeaderID = {$prm['orderid']} AND T_BarcodeLabT_SampleTypeID = {$prm['sampletypeid']}
                      JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = {$prm['orderid']} AND T_OrderSampleT_SampleTypeID = {$prm['sampletypeid']} AND 
                      T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_OrderSampleIsActive = 'Y'
                      LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND T_OrderSampleReqT_OrderHeaderID = {$prm['orderid']} AND
                                      T_OrderSampleReqNat_PositionID = Nat_RequirementPositionNat_PositionID
                      GROUP BY nat_requirementID
		";
		//echo $query;
		$rows = $this->db_onedev->query($query)->result_array();


		return $rows;
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

	function doaction_old()
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
			$sql = "SELECT T_OrderDetailID, T_OrderHeaderID,T_OrderDetailID as id,
				T_BarcodeLabID,
				T_BarcodeLabBarcode,
				T_OrderDetailT_TestCode, 
				T_OrderDetailT_TestName, 
				T_SampleTypeID,
				T_SampleTypeName, 
				T_BahanName
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_ordersample ON  
							T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND
							T_OrderSampleIsActive = 'Y' AND T_OrderSampleReceive = 'N'
					JOIN t_barcodelab ON T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_BarcodeLabIsActive = 'Y'
					JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$prm['stationid']}
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderHeaderID = T_OrderHeaderID AND 
								T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND
								T_OrderSampleReqNat_PositionID = 2 AND 
								T_OrderSampleReqT_SampleStationID = T_SampleStationID AND
								T_OrderSampleReqIsActive = 'Y'
				WHERE
					T_OrderHeaderID = {$prm['id']} AND (T_OrderSampleReceive = 'N' OR T_OrderSampleSampling = 'X') AND T_OrderHeaderIsActive = 'Y'
				GROUP BY T_BarcodeLabID";
			//echo $sql;
			$rows_all_sample = $this->db_onedev->query($sql)->result();
			if ($rows_all_sample) {
				foreach ($rows_all_sample as $k => $v) {
					$sql = "INSERT INTO t_ordersample (
						T_OrderSampleT_OrderHeaderID,
						T_OrderSampleT_SampleTypeID,
						T_OrderSampleT_BarcodeLabID,
						T_OrderSampleCreated,
						T_OrderSampleUserID
					)
					VALUES(
						{$prm['id']},
						{$v->T_SampleTypeID},
						{$v->T_BarcodeLabID},
						NOW(),
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_OrderSampleSampling = 'Y',
							T_OrderSampleSamplingDate = CURDATE(),
							T_OrderSampleSamplingTime = CURTIME(),
							T_OrderSampleSamplingUserID = {$userid},
							T_OrderSampleIsActive = 'Y',
							T_OrderSampleUserID = {$userid}";
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
								'SAMPLING.Sampling.Sampled',
								{$prm['id']},
								{$v->T_BarcodeLabID},
								'Y',
								'[]',
								{$userid},
								NOW()
							)";
					$this->db_onedev->query($sql);
					//echo $sql;
				}
				$this->broadcast("specimen-col-process");
			}
		}

		if (!isset($prm['typeaction']) && $prm['act'] == 'samplingdone') {

			$sql = "INSERT INTO t_ordersample (
						T_OrderSampleT_OrderHeaderID,
						T_OrderSampleT_SampleTypeID,
						T_OrderSampleT_BarcodeLabID,
						T_OrderSampleCreated,
						T_OrderSampleUserID
					)
					VALUES(
						{$prm['sample']['T_OrderHeaderID']},
						{$prm['sample']['T_SampleTypeID']},
						{$prm['sample']['T_BarcodeLabID']},
						NOW(),
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_OrderSampleReceiveDate = CURDATE(), 
							T_OrderSampleReceiveTime = CURTIME(), 
							T_OrderSampleReceiveUserID = {$userid},
							T_OrderSampleReceive = 'Y',
							T_OrderSampleIsActive = 'Y',
							T_OrderSampleUserID = {$userid}";
			$this->db_onedev->query($sql);
			//echo $sql;
			$sql = "SELECT * FROM t_ordersample 
					WHERE T_OrderSampleT_BarcodeLabID = {$prm['sample']['T_BarcodeLabID']} AND T_OrderSampleIsActive = 'Y' 
					ORDER BY T_OrderSampleID DESC LIMIT 1";
			$dt_sampleorder = $this->db_onedev->query($sql)->row();

			$sql = "SELECT * FROM t_sampletype WHERE T_SampleTypeID = {$prm['sample']['T_SampleTypeID']}";
			$dt_sampletype = $this->db_onedev->query($sql)->row();
			//echo $dt_sampleorder->T_OrderSampleReceiveDate;
			//echo $dt_sampleorder->T_OrderSampleReceiveTime;
			$readytime = date('Y-m-d H:i:s', strtotime($dt_sampleorder->T_OrderSampleReceiveDate . ' ' . $dt_sampleorder->T_OrderSampleReceiveTime));
			//echo $readytime;
			if ($dt_sampletype->T_SampleTypeAgingOnHold == 'Y') {
				$readytime = date('Y-m-d H:i:s', strtotime("+{$dt_sampletype->T_SampleTypeAgingOnHoldTime} minutes", strtotime($dt_sampleorder->T_OrderSampleReceiveDate . ' ' . $dt_sampleorder->T_OrderSampleReceiveTime)));
				//echo $readytime;
			}
			//echo $readytime;
			$sql = "UPDATE t_ordersample 
					SET T_OrderSampleReadyToProcessDateTime = '{$readytime}'
					WHERE 
					T_OrderSampleT_BarcodeLabID = {$prm['sample']['T_BarcodeLabID']} AND T_OrderSampleIsActive = 'Y'  ";
			$this->db_onedev->query($sql);
			//echo $sql;
			$xreq = $prm['sample']['requirements'];
			$arr_requirements = array();
			foreach ($xreq as $k => $v) {
				if ($v['chex'] == 'Y')
					array_push($arr_requirements, $v['id']);
			}
			$requirements = '[' . join(',', $arr_requirements) . ']';

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
								'SAMPLING.Sampling.Received',
								{$prm['sample']['T_OrderHeaderID']},
								{$prm['sample']['T_BarcodeLabID']},
								'{$prm['sample']['requirement_status']}',
								'{$requirements}',
								{$userid},
								NOW()
							)";
			$this->db_onedev->query($sql);
			//echo $sql;
			$sql = "SELECT count(*) as xcount
				FROM (SELECT * 
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				LEFT JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
					T_OrderSampleIsActive = 'Y'
				LEFT JOIN t_barcodelab ON T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_BarcodeLabIsActive = 'Y'
				LEFT JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
				LEFT JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				LEFT JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
					T_SampleStationID = {$prm['stationid']}
				
				WHERE
					T_OrderHeaderID = {$prm['id']} AND 
					T_OrderSampleReceive = 'N' AND T_OrderHeaderIsActive = 'Y'
				GROUP BY T_BarcodeLabID ) xx";
			//echo $sql;
			$xcount = $this->db_onedev->query($sql)->row()->xcount;
			$rst_data = array('status' => 'PARTIAL');
			if ($xcount == 0) {
				$next_status = 5;
				$rst_data = array('status' => 'OK');
			}
			$this->broadcast("specimen-col-receive");
		}

		if (isset($prm['typeaction']) && $prm['typeaction'] == 'multi' && $prm['act'] == 'samplingdone') {
			$xx_samples = $prm['sample'];

			foreach ($xx_samples as $ks => $vs) {
				$sql = "INSERT INTO t_ordersample (
						T_OrderSampleT_OrderHeaderID,
						T_OrderSampleT_SampleTypeID,
						T_OrderSampleT_BarcodeLabID,
						T_OrderSampleCreated,
						T_OrderSampleUserID
					)
					VALUES(
						{$vs['T_OrderHeaderID']},
						{$vs['T_SampleTypeID']},
						{$vs['T_BarcodeLabID']},
						NOW(),
						{$userid}
					) ON DUPLICATE KEY UPDATE 
							T_OrderSampleReceiveDate = CURDATE(), 
							T_OrderSampleReceiveTime = CURTIME(), 
							T_OrderSampleReceiveUserID = {$userid},
							T_OrderSampleReceive = 'Y',
							T_OrderSampleIsActive = 'Y',
							T_OrderSampleUserID = {$userid}";
				$this->db_onedev->query($sql);

				$sql = "SELECT * FROM t_ordersample 
						WHERE T_OrderSampleT_BarcodeLabID = {$vs['T_BarcodeLabID']} AND T_OrderSampleIsActive = 'Y' 
						ORDER BY T_OrderSampleID DESC LIMIT 1";
				$dt_sampleorder = $this->db_onedev->query($sql)->row();

				$sql = "SELECT * FROM t_sampletype WHERE T_SampleTypeID = {$vs['T_SampleTypeID']}";
				$dt_sampletype = $this->db_onedev->query($sql)->row();
				//echo $dt_sampleorder->T_OrderSampleReceiveDate;
				//echo $dt_sampleorder->T_OrderSampleReceiveTime;
				$readytime = date('Y-m-d H:i:s', strtotime($dt_sampleorder->T_OrderSampleReceiveDate . ' ' . $dt_sampleorder->T_OrderSampleReceiveTime));
				//echo $readytime;
				if ($dt_sampletype->T_SampleTypeAgingOnHold == 'Y') {
					$readytime = date('Y-m-d H:i:s', strtotime("+{$dt_sampletype->T_SampleTypeAgingOnHoldTime} minutes", strtotime($dt_sampleorder->T_OrderSampleReceiveDate . ' ' . $dt_sampleorder->T_OrderSampleReceiveTime)));
					//echo $readytime;
				}
				//echo $readytime;
				$sql = "UPDATE t_ordersample 
						SET T_OrderSampleReadyToProcessDateTime = '{$readytime}'
						WHERE 
						T_OrderSampleT_BarcodeLabID = {$vs['T_BarcodeLabID']} AND T_OrderSampleIsActive = 'Y'  ";
				$this->db_onedev->query($sql);
				//echo $sql;
				$xreq = $vs['requirements'];
				$arr_requirements = array();
				foreach ($xreq as $k => $v) {
					if ($v['chex'] == 'Y')
						array_push($arr_requirements, $v['id']);
				}
				$requirements = '[' . join(',', $arr_requirements) . ']';

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
							{$vs['T_OrderHeaderID']},
							{$prm['stationid']},
							{$vs['T_OrderSampleID']},
							{$vs['requirements'][0]['positionid']},
							'{$vs['requirement_status']}',
							'{$requirements}',
							{$userid},
							NOW()
						)ON DUPLICATE KEY UPDATE 
								T_OrderSampleReqStatus = '{$vs['requirement_status']}',
								T_OrderSampleReqs = '{$requirements}',
								T_OrderSampleReqUserID = {$userid}";
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
								'SAMPLING.Sampling.Received',
								{$vs['T_OrderHeaderID']},
								{$vs['T_BarcodeLabID']},
								'{$vs['requirement_status']}',
								'{$requirements}',
								{$userid},
								NOW()
							)";
				$this->db_onedev->query($sql);
			}

			$sql = "SELECT count(*) as xcount
				FROM (SELECT * 
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID  AND T_SampleStationID = {$prm['stationid']}
				LEFT JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
										T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
										T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND
										T_OrderSampleIsActive = 'Y'
				WHERE
					T_OrderHeaderID = {$prm['id']} AND 
					T_OrderSampleReceive = 'N' AND T_OrderHeaderIsActive = 'Y'
				GROUP BY T_BarcodeLabID ) xx";
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
						T_SamplingQueueLastStatusUserID = {$userid}";
			//echo $query;
			$rows = $this->db_onedev->query($query);
		}



		if ($status_call['status'] == 'NOTCALL') {
			$rst_data = $status_call;
		}

		if ($prm['act'] == 'skip' || $status_call['status'] == 'NOTCALL') {
			$skip_time = date('Y-m-d H:i:s', strtotime($prm['skiptime']) + 10);
			$sql = "UPDATE antrian_samplestation SET AntrianSampleStationIsActive = 'N'
					WHERE
					AntrianSampleStationT_OrderLocationID = ?";
			$query = $this->db_onedev->query($sql, array($prm['orderlocationid']));
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
			$query = $this->db_onedev->query($sql, array($prm['orderlocationid'], $skip_time, $userid));
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
			"records" => $rst_data,
			"nextstatus" => $next_status
		);
		$this->sys_ok($result);

		exit;
	}

	function addnewlabel()
	{
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst_data = array('status' => 'OK');

		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$counter_barcode = substr($prm["sample"]["T_BarcodeLabBarcode"], -1, 1); //explode(".",$prm["sample"]["T_BarcodeLabBarcode"]);
		$new_counter = intval($counter_barcode) + 1;
		$new_label = substr($prm["sample"]["T_BarcodeLabBarcode"], 0, -1) . $new_counter;
		$sql = "INSERT INTO t_barcodelab (
					T_BarcodeLabT_OrderHeaderID	,
					T_BarcodeLabBarcode,
					T_BarcodeLabT_SampleTypeID,
					T_BarcodeLabUserID
				)
				VALUES(
					{$prm['sample']['T_OrderHeaderID']},
					'{$new_label}',
					{$prm['sample']['T_SampleTypeID']},
					{$userid}
				)";
		//echo $sql;
		$this->db_onedev->query($sql);

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
			"records" => $rst_data
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

		$sql = "UPDATE t_orderheader SET T_OrderHeaderSamplingNote = '{$prm['sampling_note']}' WHERE T_OrderHeaderID = {$prm['T_OrderHeaderID']}";
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
