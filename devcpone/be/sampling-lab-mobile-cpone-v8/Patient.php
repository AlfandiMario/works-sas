<?php
class Patient extends MY_Controller
{
	var $db_onedev;
	var $IP_SOCKET_IO = "127.0.0.1";
	public function index()
	{
		echo "Patient API";
	}
	public function __construct()
	{
		parent::__construct();
		$this->db_onedev = $this->load->database("onedev", true);
		$this->load->library('Nonlabtemplate');
		$this->IP_SOCKET_IO = "127.0.0.1";
	}

	function getstations()
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

	function scan_patient()
	{
		// $this->db->trans_begin();
		// $this->db->trans_rollback();
		// $this->db->trans_commit();
		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		//# ambil parameter input
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$stationid = $prm['station_id'];


		$sql = "SELECT * FROM t_samplestation WHERE T_SampleStationID = {$stationid}";
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data t_samplestation");
			exit;
		}
		$data_station = $query->row_array();
		$stationtype = $data_station['T_SampleStationIsNonLab'];
		$stationadditional = $data_station['T_SampleStationIsAdditionalFisik'];

		$data_patient = [];
		$sql = "";
		if ($stationtype == '') {
			$sql = "
				SELECT DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
				T_OrderHeaderLabNumber as labnumber,
				T_OrderHeaderM_PatientAge as patient_age,
				M_PatientName as patient_name,
				M_PatientNoReg as noreg,
				IF(M_PatientGender = 'male','Laki-laki','Perempuan') as gender,
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
				M_PatientJob as job,
				M_PatientPosisi as posisi,
				IF(M_PatientDivisi = '','-',M_PatientDivisi) as divisi,
				M_PatientHp as hp,
				M_PatientNIP as nip,
				M_PatientEmail as email,
				M_PatientPhoto as photo,
				T_OrderHeaderID as xid,
				0 as testid,
				CorporateName as corporate_name
				FROM t_orderheader
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID AND T_OrderHeaderLabNumber = '{$prm['labnumber']}'
				JOIN corporate ON T_OrderHeaderCorporateID = CorporateID
				JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
					T_OrderSampleT_SampleStationID = {$stationid} AND 
					T_OrderSampleIsActive = 'Y'
				JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderSampleT_OrderHeaderID AND 
					T_OrderLocationT_SampleStationID = T_OrderSampleT_SampleStationID AND
					T_OrderLocationIsActive = 'Y' AND T_OrderLocationM_LocationID = {$prm['location_id']}
				LIMIT 1
			";
		} else {
			$sql = "
			SELECT DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
			T_OrderHeaderLabNumber as labnumber,
			T_OrderHeaderM_PatientAge as patient_age,
			M_PatientName as patient_name,
			M_PatientNoReg as noreg,
			IF(M_PatientGender = 'male','Laki-laki','Perempuan') as gender,
			DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
			M_PatientJob as job,
			M_PatientPosisi as posisi,
			IF(M_PatientDivisi = '','-',M_PatientDivisi) as divisi,
			M_PatientHp as hp,
			M_PatientNIP as nip,
			M_PatientEmail as email,
			M_PatientPhoto as photo,
			T_OrderHeaderID as xid,
			0 as testid,
			CorporateName as corporate_name
			FROM t_orderheader
			JOIN corporate ON T_OrderHeaderCorporateID = CorporateID AND T_OrderHeaderLabNumber = '{$prm['labnumber']}'
			JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID 
			JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
			JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
			JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
			JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
			JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
			JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
				T_OrderLocationT_SampleStationID = T_SampleStationID AND
				T_OrderLocationIsActive = 'Y' AND T_OrderLocationM_LocationID = {$prm['location_id']}	
			LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND T_SamplingSoIsActive = 'Y'	
			LIMIT 1
			";
		}


		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data patient");
			exit;
		}

		$data_patient = $query->row_array();
		if (intval($stationid) == 11 || intval($stationid) == 35) {
			$sql = "SELECT 
					T_SamplingAdditionalFisikBBTBID,
					T_SamplingAdditionalFisikBBTBValueBB as bb,
					T_SamplingAdditionalFisikBBTBValueTB AS tb
					FROM t_orderheader
					JOIN `t_samplingso_additional_fisik_bbtb` ON T_OrderHeaderID = T_SamplingAdditionalFisikBBTBT_OrderHeaderID
					AND  T_SamplingAdditionalFisikBBTBIsActive = 'Y'
					WHERE T_OrderHeaderLabNumber = '{$prm['labnumber']}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$this->sys_error($message);
				exit;
			}
			$tbbb = $query->result_array();
			if (count($tbbb) > 0) {
				$data_patient['patientTB'] = $tbbb[0]['tb'];
				$data_patient['patientBB'] = $tbbb[0]['bb'];
				$data_patient['isTBBB'] = 'Y';
			} else {
				$data_patient['patientTB'] = 0;
				$data_patient['patientBB'] = 0;
				$data_patient['isTBBB'] = 'N';
			}
		}

		$data_sample_lab = [];
		if ($stationtype == '') {
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
					T_OrderHeaderLabNumber = '{$prm['labnumber']}'
					JOIN t_sampletype ON T_OrderSampleT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
					JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
						T_OrderLocationT_SampleStationID = T_SampleStationID AND
						T_OrderLocationIsActive = 'Y' AND 
						T_OrderLocationM_LocationID = {$prm['location_id']}
					
			";
		} else {
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
					T_OrderHeaderLabNumber = '{$prm['labnumber']}'
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID	
					JOIN group_resultdetail
					ON 	T_OrderDetailT_TestID  = Group_ResultDetailT_TestID
					AND Group_ResultDetailIsActive = 'Y'
					JOIN group_result 
					ON Group_ResultDetailGroup_ResultID = Group_ResultID
					AND Group_ResultIsActive = 'Y'			
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
					JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
						T_OrderLocationT_SampleStationID = T_SampleStationID AND
						T_OrderLocationIsActive = 'Y' AND 
						T_OrderLocationM_LocationID = {$prm['location_id']}
					LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID =T_OrderHeaderID AND 
						T_SamplingSoT_TestID = T_OrderDetailT_TestID AND
						T_SamplingSoIsActive = 'Y'
					
					GROUP BY Group_ResultID";
		}

		/*if($stationadditional == 'Y'){
			$sql = "SELECT T_TestAdditionalFisikName as sampletype_name,
					T_OrderHeaderLabNumber as barcode,
					IF(ISNULL(T_SamplingSoProcessDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as sampling_date,
					IF(ISNULL(T_SamplingSoProcessTime),'',T_SamplingSoProcessTime) as sample_time,
					IF(ISNULL(T_SamplingSoDoneDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as receive_date,
					IF(ISNULL(T_SamplingSoDoneTime),'',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as receive_time,
					IF(T_SamplingSoFlag = 'P','Y','N') as is_sampling,
					IF(T_SamplingSoFlag = 'D','Y','N') as is_received
					FROM t_orderdetail
					JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderLabNumber = '{$prm['labnumber']}'
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' 
					JOIN nat_testfisik ON T_TestNat_TestID = Nat_TestFisikNat_TestID AND Nat_TestFisikIsActive = 'Y'
					JOIN t_testadditionalfisik ON Nat_TestFisikT_TestAdditionalFisikID = T_TestAdditionalFisikID AND T_TestAdditionalFisikIsActive = 'Y'
					JOIN t_samplestation ON T_TestAdditionalFisikT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
					LEFT JOIN t_samplingso_additional_fisik ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestAdditionalFisikID AND T_SamplingSoIsActive = 'Y'
					WHERE
					T_OrderDetailIsActive = 'Y'";
		}*/

		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data sample lab");
			exit;
		}
		// echo $this->db_onedev->last_query();

		$data_sample_lab = $query->result_array();

		$data_sample_lab_undone = [];
		$data_sample_lab_done = [];
		foreach ($data_sample_lab as $k_sampling => $v_sampling) {
			//print_r($v_sampling);
			if ($v_sampling['is_received'] == 'Y') {
				$data_sample_lab_done[] = $v_sampling;
			} else {
				//echo $k_sampling;
				$data_sample_lab_undone[] = $v_sampling;
			}
		}
		//print_r($data_sample_lab);
		//echo 'print_r';
		//print_r($data_sample_lab_undone);

		$dt_glucoses = [];
		$sql = "SELECT T_OrderDetailID as xid, T_OrderDetailT_TestID as test_id, T_OrderDetailT_TestName as test_name, '' as result, T_TestNat_TestID as nat_testid
					FROM t_orderdetail
					JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND 
					T_OrderDetailIsActive = 'Y' AND
					T_OrderHeaderLabNumber = '{$prm['labnumber']}'
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID
					JOIN nat_testglucose ON T_TestNat_TestID  = Nat_TestGlucoseNat_TestID AND Nat_TestGlucoseIsActive = 'Y'
					JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID AND T_BahanT_SampleStationID = {$stationid}
					WHERE
					T_OrderDetailIsActive = 'Y'";
		$query = $this->db_onedev->query($sql);
		if ($query) {
			$dt_glucoses = $query->result_array();
		}


		$data_packet = [];
		$sql = "
					SELECT T_PacketName as packet_name,
					T_PacketID as packet_id,
					'' as active,
					'' as details
					FROM t_orderdetailorder
					JOIN t_orderheader ON T_OrderDetailOrderT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderLabNumber = '{$prm['labnumber']}'
					JOIN t_packet ON T_OrderDetailOrderT_PacketID = T_PacketID
					WHERE
					T_OrderDetailOrderIsPacket = 'Y' AND
					T_OrderDetailOrderIsActive = 'Y'
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data packet");
			exit;
		}

		$data_packet = $query->result_array();
		if ($data_packet) {
			foreach ($data_packet as $key => $value) {
				$data_packet[$key]['active'] = false;
				$sql = "SELECT T_TestName as test_name
						FROM t_packetdetail 
						JOIN t_test ON T_PacketDetailT_TestID = T_TestID
						WHERE T_PacketDetailT_PacketID = {$value['packet_id']} AND T_PacketDetailIsActive = 'Y'";
				$query = $this->db_onedev->query($sql);
				if (!$query) {
					$this->sys_error_db("data packet detail");
					exit;
				}

				$data_packet_details = $query->result_array();
				if (count($data_packet_details) > 0)
					$data_packet[$key]['details'] = $data_packet_details;
				else
					$data_packet[$key]['details'] = [];
			}
		}

		$data_tests = [];
		$sql = "
					SELECT T_TestName as test_name
					FROM t_orderdetailorder
					JOIN t_orderheader ON T_OrderDetailOrderT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderLabNumber = '{$prm['labnumber']}'
					JOIN t_test ON T_OrderDetailOrderT_TestID = T_TestID
					WHERE
					T_OrderDetailOrderIsPacket = 'N' AND
					T_OrderDetailOrderIsActive = 'Y'
				";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data tests");
			exit;
		}

		$data_tests = $query->result_array();
		//echo count($data_sample_lab_done);
		//echo count($data_sample_lab);
		//echo count($data_sample_lab_undone);
		if (count($data_sample_lab_done) < count($data_sample_lab) && count($data_sample_lab_undone) > 0) {
			$act = "call";
			$statusnextid = 1;
			$orderid = $data_patient['xid'];
			$sampletypeid = 0;
			$barcodelabid = 0;
			$requirements = [];
			if ($stationadditional == 'N') {
				if ($stationtype == '')
					$doaction_call = $this->doaction($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
				else
					$doaction_call = $this->doaction_nonlab($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
			} else {
				$doaction_call = $this->doaction_nonlab($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
			}


			if ($doaction_call['records']['status'] == 'OK') {
				$act = "process";
				//echo $act;
				$statusnextid = 3;
				$orderid = $data_patient['xid'];
				$sampletypeid = 0;
				$barcodelabid = 0;
				$requirements = [];
				if ($stationadditional == 'N') {
					if ($stationtype == '')
						$doaction_process = $this->doaction($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
					else
						$doaction_process = $this->doaction_nonlab($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
				} else {
					$doaction_process = $this->doaction_nonlab($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
				}
			} else {
				$rtn = array("status" => "NOTCAL", "data" => $doaction_call['records']['data']);
				$this->sys_ok($rtn);
				exit;
			}
		}

		//get req
		$sql = "SELECT 
				T_OrderReqs 
				FROM t_orderheader 
				JOIN t_orderreq 
				ON T_OrderHeaderID = T_OrderReqT_OrderHeaderID
				AND T_OrderReqIsActive = 'Y'
				WHERE T_OrderHeaderLabNumber = ?
				LIMIT 1";
		$query = $this->db_onedev->query($sql, [$prm['labnumber']]);
		if (!$query) {
			$this->sys_error_db("data reqs");
			exit;
		}
		$arrReq = $query->row_array()['T_OrderReqs'];
		if ($arrReq != null) {
			$arrReq = json_decode($arrReq);
		}
		$req = [];
		$sql = "SELECT 
				Nat_RequirementID as reqID,
				Nat_RequirementName as reqName
				FROM nat_requirement 
				WHERE Nat_RequirementID IN ?";
		$query = $this->db_onedev->query($sql, [$arrReq]);
		if (!$query) {
			$this->sys_error_db("data reqs");
			exit;
		}
		$req = $query->result_array();


		$result = array(
			"status" => "OK",
			"data_patient" => $data_patient ? $data_patient : [],
			"data_sample_lab" => $data_sample_lab ? $data_sample_lab : [],
			"data_sample_lab_done" => $data_sample_lab_done ? $data_sample_lab_done : [],
			"data_sample_lab_undone" => $data_sample_lab_undone ? $data_sample_lab_undone : [],
			"data_packet" => $data_packet ? $data_packet : [],
			"data_requirement" => $req ?  $req : [],
			"data_tests" => $data_tests ? $data_tests : [],
			"data_glucoses" => $dt_glucoses
		);
		$this->sys_ok($result);
		exit;
	}

	function skipaction()
	{

		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$act = "skip";
		$statusnextid = 2;
		$stationid = $prm['station']['id'];
		$stationtype = $prm['station']['isnonlab'];
		$orderid = $prm['order_id'];
		$sampletypeid = 0;
		$barcodelabid = 0;
		$requirements = [];
		if ($stationtype == '') {
			$sql = "SELECT count(*) as xcount
				FROM (SELECT * 
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_barcodelab ON T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID AND T_BarcodeLabT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID  AND T_SampleStationID = {$stationid}
				JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
										T_OrderSampleT_SampleTypeID = T_SampleTypeID AND 
										T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND
										T_OrderSampleIsActive = 'Y' AND
										T_OrderSampleReceive = 'N'
				GROUP BY T_BarcodeLabID ) xx";
		} else {
			$sql = "SELECT count(*) as xcount
				FROM (SELECT * 
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID  AND T_SampleStationID = {$stationid}
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
										T_SamplingSoT_TestID = T_TestID AND 
										T_SamplingSoIsActive = 'Y'
				WHERE
					( ISNULL(T_SamplingSoID) OR T_SamplingSoFlag <> 'D' ) 
				GROUP BY T_OrderDetailT_TestID ) xx";
		}

		//echo $sql;
		$xcount = $this->db_onedev->query($sql)->row()->xcount;
		if ($xcount > 0)
			$doaction_call = $this->doaction($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
		else {
			$statusnextid = 5;
			$query = "INSERT INTO  t_sampling_queue_last_status (
					T_SamplingQueueLastStatusT_SampleStationID,
					T_SamplingQueueLastStatusT_OrderHeaderID,
					T_SamplingQueueLastStatusT_SamplingQueueStatusID,
					T_SamplingQueueLastStatusUserID)
				VALUES(
					{$stationid},
					{$orderid},
					{$statusnextid},
					{$userid}) ON DUPLICATE KEY UPDATE 
						T_SamplingQueueLastStatusT_SamplingQueueStatusID = {$statusnextid},
						T_SamplingQueueLastStatusUserID = {$userid}";
			//echo $query;
			$rows = $this->db_onedev->query($query);
		}

		$result = array(
			"order_id" => $orderid
		);

		$this->sys_ok($result);
		exit;
	}

	function scanbarcode()
	{

		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$act = "samplingdone";
		$statusnextid = 3;
		$stationid = $prm['station']['id'];
		$stationtype = $prm['station']['isnonlab'];
		$orderid = $prm['patient']['xid'];


		$sql = "SELECT * 
				FROM t_ordersample 
				JOIN t_orderheader ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
				T_OrderHeaderLabNumber = '{$prm['barcode']}'
				WHERE
				T_OrderSampleT_OrderHeaderID = {$orderid} AND
				T_OrderSampleT_SampleStationID = {$stationid} AND
				T_OrderSampleIsActive = 'Y' AND T_OrderSampleReceive = 'N'";

		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("t_ordersample");
			exit;
		}

		$ordersamples = $query->result_array();

		if ($ordersamples) {
			foreach ($ordersamples as $key => $ordersample) {
				$sampletypeid = $ordersample['T_OrderSampleT_SampleTypeID'];
				$barcodelabid = $ordersample['T_OrderSampleT_BarcodeLabID'];
				$requirements = [];
				$doaction_call = $this->doaction($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
			}

			$dt_glucoses = $prm['glucoses'];
			if ($dt_glucoses && count($dt_glucoses) > 0) {
				foreach ($dt_glucoses as $key => $value) {
					$sql = "INSERT INTO order_glucose (OrderGlucoseT_OrderHeaderID,OrderGlucoseNat_TestID,OrderGlucoseResult,OrderGlucoseCreated,OrderGlucoseCreatedUserID) 
								VALUES({$orderid},{$value['nat_testid']},'{$value['result']}',NOW(),{$userid})";
					$query = $this->db_onedev->query($sql);

					$sql = "UPDATE t_orderdetail SET T_OrderDetailResult = '{$value['result']}' 
							WHERE
							T_OrderDetailID = {$value['xid']}";
					$query = $this->db_onedev->query($sql);
				}
			}


			$result = array(
				"status_log" => "Y",
				"order_id" => $orderid,
				"isdone" => "Y"
			);
			$this->sys_ok($result);
			exit;
		} else {
			$result = array(
				"status_log" => "N",
				"order_id" => $orderid
			);

			$this->sys_ok($result);
			exit;
		}
	}

	function doaction($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid)
	{

		$rst_data = array('status' => 'OK');
		$status_call = array('status' => 'OK', 'data' => array());


		$sql = "SELECT '' AS queueNumber ,
				M_LocationID AS locationID, 
				M_LocationName AS locationName FROM t_orderheader 
				JOIN t_order_location ON T_OrderHeaderID = T_OrderLocationT_OrderHeaderID
				JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID
				AND T_OrderLocationT_SampleStationID = ?
				WHERE  T_OrderHeaderID=?";
		$location = $this->db_onedev->query($sql, array($stationid, $orderid))->row_array();
		$locationID = $location['locationID'];
		$locationName = $location['locationName'];
		$queueNumber = $location['queueNumber'];
		$splitedLocationName = explode(" ", $locationName);
		$locationName = $splitedLocationName[0];



		if ($act == 'call') {
			$sql = "SELECT T_SamplingQueueLastStatusID, T_SamplingQueueStatusName,  T_SampleStationName, T_SampleStationID, T_SampleStationIsNonLab
					FROM t_sampling_queue_last_status
					JOIN t_sampling_queue_status ON T_SamplingQueueLastStatusT_SamplingQueueStatusID = T_SamplingQueueStatusID 
					JOIN t_samplestation ON T_SampleStationID = T_SamplingQueueLastStatusT_SampleStationID 
					WHERE
					T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
					T_SamplingQueueLastStatusT_SampleStationID <>  {$stationid} AND 
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
								T_OrderSampleT_OrderHeaderID = {$orderid} AND 
								T_OrderSampleT_SampleStationID = {$data_status_call['T_SampleStationID']} AND 
								T_OrderSampleIsActive = 'Y'
							";
					$not_sampled = $this->db_onedev->query($sql)->row_array();
				} else {
					$sql = "SELECT SUM(countx) as xcount
								FROM (
									SELECT COUNT(*) as countx
									FROM t_orderdetail 
									JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' AND
									T_OrderDetailT_OrderHeaderID = {$orderid} AND T_OrderDetailIsActive = 'Y'
									JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
									JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
									JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
										T_SampleStationID = {$data_status_call['T_SampleStationID']} 
									LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
										T_SamplingSoT_TestID = T_TestID AND 
										T_SamplingSoIsActive = 'Y'
									WHERE
									ISNULL(T_SamplingSoDoneDate)
									
								) x";
					$not_sampled = $this->db_onedev->query($sql)->row_array();
				}
				if (intval($not_sampled['xcount']) == 0) {
					$sql = "UPDATE t_sampling_queue_last_status
							SET T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5
							WHERE
								T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
								T_SamplingQueueLastStatusT_SampleStationID = {$data_status_call['T_SampleStationID']}";
					$this->db_onedev->query($sql);
					$status_call = array('status' => 'OK', 'data' => array());
				}
			}
		}

		$next_status = $statusnextid;
		if ($act == 'process') {
			$sql = "SELECT T_OrderDetailID, T_OrderHeaderID,T_OrderDetailID as id,
				T_BarcodeLabID,
				T_BarcodeLabBarcode,
				T_OrderDetailT_TestCode, 
				T_OrderDetailT_TestName, 
				T_SampleTypeID,
				T_SampleTypeName, 
				T_BahanName
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				JOIN t_ordersample ON  
							T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND
							T_OrderSampleIsActive = 'Y' AND T_OrderSampleReceive = 'N'
					JOIN t_barcodelab ON T_OrderSampleT_BarcodeLabID = T_BarcodeLabID AND T_BarcodeLabIsActive = 'Y'
					JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
				LEFT JOIN t_ordersamplereq ON T_OrderSampleReqT_OrderHeaderID = T_OrderHeaderID AND 
								T_OrderSampleReqT_OrderSampleID = T_OrderSampleID AND
								T_OrderSampleReqNat_PositionID = 2 AND 
								T_OrderSampleReqT_SampleStationID = T_SampleStationID AND
								T_OrderSampleReqIsActive = 'Y'
				WHERE
					(T_OrderSampleReceive = 'N' OR T_OrderSampleSampling = 'X') 
				GROUP BY T_BarcodeLabID";
			//echo $sql;
			$rows_all_sample = $this->db_onedev->query($sql)->result();
			if ($rows_all_sample) {
				foreach ($rows_all_sample as $k => $v) {
					$sql = "UPDATE t_ordersample SET
							T_OrderSampleSampling = 'Y',
							T_OrderSampleSamplingDate = CURDATE(),
							T_OrderSampleSamplingTime = CURTIME(),
							T_OrderSampleSamplingUserID = {$userid},
							T_OrderSampleIsActive = 'Y',
							T_OrderSampleUserID = {$userid}
							WHERE
							T_OrderSampleT_OrderHeaderID = {$orderid} AND 
							T_OrderSampleT_SampleTypeID = {$v->T_SampleTypeID} AND
							T_OrderSampleT_BarcodeLabID = {$v->T_BarcodeLabID} 
						
						";
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
								{$orderid},
								{$v->T_BarcodeLabID},
								'Y',
								'[]',
								{$userid},
								NOW()
							)";
					$this->db_onedev->query($sql);
					//echo $sql;
				}
				//$this->broadcast("specimen-col-process");
			}
		}

		$isdone = "X";
		if ($act == 'samplingdone') {

			$sql = "UPDATE t_ordersample SET
							T_OrderSampleReceiveDate = CURDATE(), 
							T_OrderSampleReceiveTime = CURTIME(), 
							T_OrderSampleReceiveUserID = {$userid},
							T_OrderSampleReceive = 'Y',
							T_OrderSampleIsActive = 'Y',
							T_OrderSampleUserID = {$userid}
						WHERE
						T_OrderSampleT_OrderHeaderID = {$orderid} AND 
						T_OrderSampleT_SampleTypeID = {$sampletypeid} AND
						T_OrderSampleT_BarcodeLabID = {$barcodelabid}";
			$this->db_onedev->query($sql);
			//echo $sql;
			$sql = "SELECT * FROM t_ordersample 
					WHERE T_OrderSampleT_BarcodeLabID = {$barcodelabid} AND T_OrderSampleIsActive = 'Y' 
					ORDER BY T_OrderSampleID DESC LIMIT 1";
			$dt_sampleorder = $this->db_onedev->query($sql)->row();

			$sql = "SELECT * FROM t_sampletype WHERE T_SampleTypeID = {$sampletypeid}";
			$dt_sampletype = $this->db_onedev->query($sql)->row();

			$xreq = $requirements;
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
						{$orderid},
						{$stationid},
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
								{$orderid},
								{$barcodelabid},
								'{$prm['sample']['requirement_status']}',
								'{$requirements}',
								{$userid},
								NOW()
							)";
			$this->db_onedev->query($sql);
			//echo $sql;

			$sql = "SELECT COUNT(*) as xcount
					FROM t_ordersample
					JOIN t_orderheader ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderID =  {$orderid}
					JOIN t_sampletype ON T_OrderSampleT_SampleTypeID = T_SampleTypeID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
					
					WHERE
					T_OrderSampleIsActive = 'Y' AND T_OrderSampleReceive = 'N'";
			$xcount = $this->db_onedev->query($sql)->row()->xcount;
			$rst_data = array('status' => 'PARTIAL', 'isdone' => "N");
			$isdone = "N";
			if ($xcount == 0) {
				$isdone = "Y";
				$next_status = 5;
				$rst_data = array('status' => 'OK', 'isdone' => "Y");
			}
			//$this->broadcast("specimen-col-receive");
		}

		if ($act !== 'samplingprocess' && $status_call['status'] == 'OK') {
			$dt_json = json_encode(array('T_SampleStationID' => $stationid, 'T_OrderHeaderID' => $orderid, 'T_SamplingQueueStatusID' => $next_status));
			$query = "INSERT INTO  one_log.log_sampling_queue (Log_SamplingQueueDate,Log_SamplingQueueJSON,Log_SamplingQueueUserID)
						VALUES(NOW(),'{$dt_json}',{$userid})";
			//echo $query;
			//$rows = $this->db_onedev->query($query);
			$sql = "SELECT * 
					FROM t_sampling_queue_last_status 
					WHERE 
					T_SamplingQueueLastStatusT_SampleStationID = {$stationid} AND
					T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
					T_SamplingQueueLastStatusIsActive = 'Y'";
			//echo $sql;
			$data_last = $this->db_onedev->query($sql)->row();

			$query = "INSERT INTO  t_sampling_queue_last_status (
					T_SamplingQueueLastStatusT_SampleStationID,
					T_SamplingQueueLastStatusT_OrderHeaderID,
					T_SamplingQueueLastStatusT_SamplingQueueStatusID,
					T_SamplingQueueLastStatusUserID)
				VALUES(
					{$stationid},
					{$orderid},
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

		if ($act == 'skip' || $status_call['status'] == 'NOTCALL') {
			$skip_time = date('Y-m-d H:i:s', strtotime($prm['skiptime']) + 10);
			$sql = "UPDATE antrian_samplestation SET AntrianSampleStationIsActive = 'N'
					WHERE
					AntrianSampleStationT_OrderLocationID = ?";
			//$query = $this->db_onedev->query($sql,array($prm['orderlocationid']));
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
			//$query = $this->db_onedev->query($sql,array($prm['orderlocationid'],$skip_time,$userid));

		}

		$result = array(
			"total" => 1,
			"records" => $rst_data,
			"nextstatus" => $next_status,
			"isdone" => $isdone
		);

		$sql = "SELECT 
				T_OrderHeaderM_BranchID as branchID,
				T_OrderHeaderMgm_McuID as mcuID
				FROM t_orderheader
				WHERE T_OrderHeaderID = $orderid";
		$qry = $this->db_onedev->query(
			$sql
		);

		if (!$qry) {
			$this->sys_error_db("Error get broadcast data", $this->db_onedev);
			exit;
		}
		$dataBroadcast = $qry->row_array();
		if ($act ==  'call') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.call." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'process') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.process." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'samplingdone') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.done." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'skip') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.skip." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		}
		return $result;
	}


	function scanbarcode_nonlab()
	{

		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$act = "samplingdone";
		$statusnextid = 3;
		$stationid = $prm['station']['id'];
		$locationid = $prm['location_id'];
		$stationtype = $prm['station']['isnonlab'];
		$orderid = $prm['patient']['xid'];

		$sql = "SELECT T_OrderDetailID, T_OrderHeaderID,T_OrderDetailID as id,
			IFNULL(T_SamplingSoID,0) as T_BarcodeLabID,
			T_TestName as T_BarcodeLabBarcode,
			T_OrderDetailT_TestCode, 
			T_OrderDetailT_TestName, 
			T_TestID as test_id,
			T_SampleTypeName, 
			T_BahanName,
			IFNULL(T_SamplingSoID,0) as T_SamplingSoID, 
			IF(ISNULL(T_SamplingSoID),'N',T_SamplingSoFlag) as status, 
			IF(ISNULL(T_SamplingSoProcessDate),'00-00-0000',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as process_date,
			IF(ISNULL(T_SamplingSoProcessTime),'00:00',DATE_FORMAT(T_SamplingSoProcessTime,'%H:%i')) as process_time,
			IF(ISNULL(T_SamplingSoDoneDate) OR T_SamplingSoFlag = 'P','00-00-0000',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as done_date,
			IF(ISNULL(T_SamplingSoDoneTime) OR T_SamplingSoFlag = 'P','00:00',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as done_time,
			'Y' as requirement_status,
			'' as requirements
			FROM t_orderheader	
			JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND 
				T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderLabNumber = '{$prm['barcode']}' AND T_OrderHeaderIsActive = 'Y'
			JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
			JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
			JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
			JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
			JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND 
					T_OrderLocationT_SampleStationID = T_SampleStationID AND
					T_OrderLocationIsActive = 'Y' AND 
					T_OrderLocationM_LocationID = {$locationid}
			LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND T_SamplingSoIsActive = 'Y'
			GROUP BY T_TestID ";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("t_samplingso");
			exit;
		}

		$ordersamples = $query->result_array();

		//echo $this->db_onedev->last_query();

		if ($ordersamples) {
			foreach ($ordersamples as $key => $ordersample) {
				//print_r($ordersample);


				if (intval($ordersample['T_SamplingSoID']) > 0) {
					if (intval($stationid) == 17) {
						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikBBTBID,0) as id
								FROM t_samplingso_additional_fisik_bbtb
								WHERE
								T_SamplingAdditionalFisikBBTBT_OrderHeaderID = {$orderid}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso count");
							exit;
						}

						$data_exist_bbtb = $query->row_array();
						if (intval($data_exist_bbtb['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_bbtb (
								T_SamplingAdditionalFisikBBTBT_OrderHeaderID,
								T_SamplingAdditionalFisikBBTBValueBB,
								T_SamplingAdditionalFisikBBTBValueTB,
								T_SamplingAdditionalFisikBBTBCreated,
								T_SamplingAdditionalFisikBBTBCreatedUserID
							) VALUES(
								{$orderid},
								{$prm['value_bb']},
								{$prm['value_tb']},
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								echo $this->db_onedev->last_query();
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_bbtb SET 
										T_SamplingAdditionalFisikBBTBT_OrderHeaderID = {$orderid},
										T_SamplingAdditionalFisikBBTBValueBB = {$prm['value_bb']},
										T_SamplingAdditionalFisikBBTBValueTB = {$prm['value_tb']},
										T_SamplingAdditionalFisikBBTBLastUpdated = NOW(),
										T_SamplingAdditionalFisikBBTBLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikBBTBID = {$data_exist_bbtb['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb update");
								exit;
							}
						}
					}
					if (intval($stationid) == 35) {
						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikBBTBID,0) as id
								FROM t_samplingso_additional_fisik_bbtb
								WHERE
								T_SamplingAdditionalFisikBBTBT_OrderHeaderID = {$orderid}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso count");
							exit;
						}

						$data_exist_bbtb = $query->row_array();
						if (intval($data_exist_bbtb['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_bbtb (
								T_SamplingAdditionalFisikBBTBT_OrderHeaderID,
								T_SamplingAdditionalFisikBBTBBodyFat,
								T_SamplingAdditionalFisikBBTBCreated,
								T_SamplingAdditionalFisikBBTBCreatedUserID
							) VALUES(
								{$orderid},
								{$prm['value_bf']},
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								echo $this->db_onedev->last_query();
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_bbtb SET 
										T_SamplingAdditionalFisikBBTBT_OrderHeaderID = {$orderid},
										T_SamplingAdditionalFisikBBTBBodyFat = {$prm['value_bf']},
										T_SamplingAdditionalFisikBBTBLastUpdated = NOW(),
										T_SamplingAdditionalFisikBBTBLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikBBTBID = {$data_exist_bbtb['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb update");
								exit;
							}
						}
					}
					if (intval($stationid) == 33) {
						$withCorection = $prm['withCorection'];
						$visusAdd = $prm['visusAdd'];
						$odSph = $prm['odSph'];
						$odCyl = $prm['odCyl'];
						$odX = $prm['odX'];
						$osSph = $prm['osSph'];
						$osCyl = $prm['osCyl'];
						$osX = $prm['osX'];
						$colorBlindNumber = $prm['colorBlindNumber'];
						$btwrn = $prm['btwrn'];

						if ($withCorection == 'N') {
							$odSph = '';
							$odCyl = '';
							$odX = '';
							$osSph = '';
							$osCyl = '';
							$osX = '';
						}
						if ($btwrn == 'N') {
							$colorBlindNumber = '';
						}


						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikVisusID,0) as id
								FROM t_samplingso_additional_fisik_visus
								WHERE
								T_SamplingAdditionalFisikVisusT_OrderHeaderID = {$orderid}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso count");
							exit;
						}

						$data_exist_visus = $query->row_array();
						if (intval($data_exist_visus['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_visus (
								T_SamplingAdditionalFisikVisusT_OrderHeaderID,
								T_SamplingAdditionalFisikVisusTKODV,
								T_SamplingAdditionalFisikVisusTKOSV,
								T_SamplingAdditionalFisikVisusDKODV,
								T_SamplingAdditionalFisikVisusDKOSV,
								T_SamplingAdditionalFisikVisusODSPH,
								T_SamplingAdditionalFisikVisusODCYL,
								T_SamplingAdditionalFisikVisusODX,
								T_SamplingAdditionalFisikVisusOSSPH,
								T_SamplingAdditionalFisikVisusOSCYL,
								T_SamplingAdditionalFisikVisusOSX,
								T_SamplingAdditionalFisikVisusADD,
								T_SamplingAdditionalFisikVisusCreated,
								T_SamplingAdditionalFisikVisusCreatedUserID
							) VALUES(
								{$orderid},
								'{$prm['tkod']}',
								'{$prm['tkos']}',
								'{$prm['dkod']}',
								'{$prm['dkos']}',
								'{$odSph}',
								'{$odCyl}',
								'{$odX}',
								'{$osSph}',
								'{$osCyl}',
								'{$osX}',
								'{$visusAdd}',
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_visus insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_visus SET 
										T_SamplingAdditionalFisikVisusT_OrderHeaderID = {$orderid},
										T_SamplingAdditionalFisikVisusTKODV = '{$prm['tkod']}',
										T_SamplingAdditionalFisikVisusTKOSV = '{$prm['tkos']}',
										T_SamplingAdditionalFisikVisusDKODV = '{$prm['dkod']}',
										T_SamplingAdditionalFisikVisusDKOSV = '{$prm['dkos']}',
										T_SamplingAdditionalFisikVisusODSPH = '{$odSph}',
										T_SamplingAdditionalFisikVisusODCYL = '{$odCyl}',
										T_SamplingAdditionalFisikVisusODX = '{$odX}',
										T_SamplingAdditionalFisikVisusOSSPH = '{$osSph}',
										T_SamplingAdditionalFisikVisusOSCYL = '{$osCyl}',
										T_SamplingAdditionalFisikVisusOSX = '{$osX}',
										T_SamplingAdditionalFisikVisusADD = '{$visusAdd}',
										T_SamplingAdditionalFisikVisusLastUpdated = NOW(),
										T_SamplingAdditionalFisikVisusLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikVisusID = {$data_exist_visus['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								// print_r($this->db_onedev->last_query());
								$this->sys_error_db("t_samplingso_additional_fisik_visus update");
								exit;
							}
						}

						//Buta warna
						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikBWID,0) as id
								FROM t_samplingso_additional_fisik_bw
								WHERE
								T_SamplingAdditionalFisikBWT_OrderHeaderID = {$orderid}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso count");
							exit;
						}

						$dataExistBtWrn = $query->row_array();
						if (intval($dataExistBtWrn['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_bw (
								T_SamplingAdditionalFisikBWT_OrderHeaderID,
								T_SamplingAdditionalFisikBWPWValue,
								T_SamplingAdditionalFisikBWPWVAngka,
								T_SamplingAdditionalFisikBWCreated,
								T_SamplingAdditionalFisikBWCreatedUserID
							) VALUES(
								{$orderid},
								'{$btwrn}',
								'{$colorBlindNumber}',
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bw insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_bw SET 
										T_SamplingAdditionalFisikBWT_OrderHeaderID = {$orderid},
										T_SamplingAdditionalFisikBWPWValue = '{$btwrn}',
										T_SamplingAdditionalFisikBWPWVAngka = '{$colorBlindNumber}',
										T_SamplingAdditionalFisikBWLastUpdated = NOW(),
										T_SamplingAdditionalFisikBWLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikBWID = {$dataExistBtWrn['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								// print_r($this->db_onedev->last_query());
								$this->sys_error_db("t_samplingso_additional_fisik_bw update");
								exit;
							}
						}
					}

					/*if (intval($stationid) == 33) {
						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikVBWID,0) as id
								FROM t_samplingso_additional_fisik_vbw
								WHERE
								T_SamplingAdditionalFisikVBWT_SamplingSoID = {$ordersample['T_SamplingSoID']}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso vbw count");
							exit;
						}

						$data_exist_vbw = $query->row_array();
						if (intval($data_exist_vbw['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_vbw (
								T_SamplingAdditionalFisikVBWT_SamplingSoID,
								T_SamplingAdditionalFisikVBWTKODV,
								T_SamplingAdditionalFisikVBWTKOSV,
								T_SamplingAdditionalFisikVBWDKODV,
								T_SamplingAdditionalFisikVBWDKOSV,
								T_SamplingAdditionalFisikVBWPWValue,
								T_SamplingAdditionalFisikVBWCreated,
								T_SamplingAdditionalFisikVBWCreatedUserID
							) VALUES(
								{$ordersample['T_SamplingSoID']},
								'{$prm['tkod']}',
								'{$prm['tkos']}',
								'{$prm['dkod']}',
								'{$prm['dkos']}',
								'{$prm['btwrn']}',
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								//echo $this->db_onedev->last_query();
								$this->sys_error_db("t_samplingso_additional_fisik_vbw insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_vbw SET 
										T_SamplingAdditionalFisikVBWT_SamplingSoID = {$ordersample['T_SamplingSoID']},
										T_SamplingAdditionalFisikVBWTKODV = '{$prm['tkod']}',
										T_SamplingAdditionalFisikVBWTKOSV = '{$prm['tkos']}',
										T_SamplingAdditionalFisikVBWDKODV = '{$prm['dkod']}',
										T_SamplingAdditionalFisikVBWDKOSV = '{$prm['dkos']}',
										T_SamplingAdditionalFisikVBWPWValue = '{$prm['btwrn']}',
										T_SamplingAdditionalFisikVBWLastUpdated = NOW(),
										T_SamplingAdditionalFisikVBWLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikVBWID = {$data_exist_vbw['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb update");
								exit;
							}
						}
					}*/
				}

				$sampletypeid = $ordersample['test_id'];
				$barcodelabid = 0;
				$requirements = [];
				$doaction_call = $this->doaction_nonlab($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
			}

			$result = array(
				"status_log" => "Y",
				"order_id" => $orderid,
				"isdone" => "Y"
			);
			$this->sys_ok($result);
			exit;
		} else {
			$result = array(
				"status_log" => "N",
				"order_id" => $orderid
			);

			$this->sys_ok($result);
			exit;
		}
	}


	function scanbarcode_additional_fisik()
	{

		if (!$this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$act = "samplingdone";
		$statusnextid = 3;
		$stationid = $prm['station']['id'];
		$stationtype = $prm['station']['isnonlab'];
		$orderid = $prm['patient']['xid'];

		$sql = "SELECT T_OrderDetailID, T_OrderHeaderID,T_OrderDetailID as id,
			IFNULL(T_SamplingSoID,0) as T_BarcodeLabID,
			T_TestAdditionalFisikName as T_BarcodeLabBarcode,
			T_TestAdditionalFisikCode as T_OrderDetailT_TestCode, 
			T_TestAdditionalFisikName as T_OrderDetailT_TestName, 
			T_TestAdditionalFisikID as test_id,
			T_TestAdditionalFisikName  as T_SampleTypeName, 
			T_TestAdditionalFisikName as T_BahanName,
			IFNULL(T_SamplingSoID,0) as T_SamplingSoID, 
			IF(ISNULL(T_SamplingSoID),'N',T_SamplingSoFlag) as status, 
			IF(ISNULL(T_SamplingSoProcessDate),'00-00-0000',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as process_date,
			IF(ISNULL(T_SamplingSoProcessTime),'00:00',DATE_FORMAT(T_SamplingSoProcessTime,'%H:%i')) as process_time,
			IF(ISNULL(T_SamplingSoDoneDate) OR T_SamplingSoFlag = 'P','00-00-0000',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as done_date,
			IF(ISNULL(T_SamplingSoDoneTime) OR T_SamplingSoFlag = 'P','00:00',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as done_time,
			'Y' as requirement_status,
			'' as requirements
			FROM t_orderheader	
			JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND 
				T_OrderHeaderLabNumber = '{$prm['barcode']}' AND T_OrderHeaderIsActive = 'Y'
			JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' 
			JOIN nat_testfisik ON T_TestNat_TestID = Nat_TestFisikNat_TestID AND Nat_TestFisikIsActive = 'Y'
			JOIN t_testadditionalfisik ON Nat_TestFisikT_TestAdditionalFisikID = T_TestAdditionalFisikID AND T_TestAdditionalFisikIsActive = 'Y'
			JOIN t_samplestation ON T_TestAdditionalFisikT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
			LEFT JOIN t_samplingso_additional_fisik ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestAdditionalFisikID AND T_SamplingSoIsActive = 'Y'
			GROUP BY T_TestAdditionalFisikID ";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("t_samplingso");
			exit;
		}

		$ordersamples = $query->result_array();

		//echo $this->db_onedev->last_query();

		if ($ordersamples) {
			foreach ($ordersamples as $key => $ordersample) {
				if (intval($ordersample['T_SamplingSoID']) > 0) {
					if (intval($stationid) == 17) {
						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikBBTBID,0) as id
								FROM t_samplingso_additional_fisik_bbtb
								WHERE
								T_SamplingAdditionalFisikBBTBT_SamplingSoID = {$ordersample['T_SamplingSoID']}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso count");
							exit;
						}

						$data_exist_bbtb = $query->row_array();
						if (intval($data_exist_bbtb['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_bbtb (
								T_SamplingAdditionalFisikBBTBT_SamplingSoID,
								T_SamplingAdditionalFisikBBTBValueBB,
								T_SamplingAdditionalFisikBBTBValueTB,
								T_SamplingAdditionalFisikBBTBCreated,
								T_SamplingAdditionalFisikBBTBCreatedUserID
							) VALUES(
								{$ordersample['T_SamplingSoID']},
								{$prm['value_bb']},
								{$prm['value_tb']},
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_bbtb SET 
										T_SamplingAdditionalFisikBBTBT_SamplingSoID = {$ordersample['T_SamplingSoID']},
										T_SamplingAdditionalFisikBBTBValueBB = {$prm['value_bb']},
										T_SamplingAdditionalFisikBBTBValueTB = {$prm['value_tb']},
										T_SamplingAdditionalFisikBBTBLastUpdated = NOW(),
										T_SamplingAdditionalFisikBBTBLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikBBTBID = {$data_exist_bbtb['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb update");
								exit;
							}
						}
					}

					if (intval($stationid) == 33) {
						$sql = "SELECT COUNT(*) as count, IFNULL(T_SamplingAdditionalFisikVBWID,0) as id
								FROM t_samplingso_additional_fisik_vbw
								WHERE
								T_SamplingAdditionalFisikVBWT_SamplingSoID = {$ordersample['T_SamplingSoID']}";
						$query = $this->db_onedev->query($sql);
						if (!$query) {
							$this->sys_error_db("t_samplingso vbw count");
							exit;
						}

						$data_exist_vbw = $query->row_array();
						if (intval($data_exist_vbw['count']) == 0) {
							$sql = "INSERT INTO t_samplingso_additional_fisik_vbw (
								T_SamplingAdditionalFisikVBWT_SamplingSoID,
								T_SamplingAdditionalFisikVBWTKODV,
								T_SamplingAdditionalFisikVBWTKOSV,
								T_SamplingAdditionalFisikVBWDKODV,
								T_SamplingAdditionalFisikVBWDKOSV,
								T_SamplingAdditionalFisikVBWPWValue,
								T_SamplingAdditionalFisikVBWCreated,
								T_SamplingAdditionalFisikVBWCreatedUserID
							) VALUES(
								{$ordersample['T_SamplingSoID']},
								'{$prm['tkod']}',
								'{$prm['tkos']}',
								'{$prm['dkod']}',
								'{$prm['dkos']}',
								'{$prm['btwrn']}',
								NOW(),
								{$userid}
							)";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								//echo $this->db_onedev->last_query();
								$this->sys_error_db("t_samplingso_additional_fisik_vbw insert");
								exit;
							}
						} else {
							$sql = "UPDATE t_samplingso_additional_fisik_vbw SET 
										T_SamplingAdditionalFisikVBWT_SamplingSoID = {$ordersample['T_SamplingSoID']},
										T_SamplingAdditionalFisikVBWTKODV = '{$prm['tkod']}',
										T_SamplingAdditionalFisikVBWTKOSV = '{$prm['tkos']}',
										T_SamplingAdditionalFisikVBWDKODV = '{$prm['dkod']}',
										T_SamplingAdditionalFisikVBWDKOSV = '{$prm['dkos']}',
										T_SamplingAdditionalFisikVBWPWValue = '{$prm['btwrn']}',
										T_SamplingAdditionalFisikVBWLastUpdated = NOW(),
										T_SamplingAdditionalFisikVBWLastUpdatedUserID = {$userid}
									WHERE
										T_SamplingAdditionalFisikVBWID = {$data_exist_vbw['id']}
							";
							$query = $this->db_onedev->query($sql);
							if (!$query) {
								$this->sys_error_db("t_samplingso_additional_fisik_bbtb update");
								exit;
							}
						}
					}
				}


				//print_r($ordersample);
				$sampletypeid = $ordersample['test_id'];
				$barcodelabid = 0;
				$requirements = [];
				$doaction_call = $this->doaction_additional_fisik($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid);
			}

			$result = array(
				"status_log" => "Y",
				"order_id" => $orderid,
				"isdone" => "Y"
			);
			$this->sys_ok($result);
			exit;
		} else {
			$result = array(
				"status_log" => "N",
				"order_id" => $orderid
			);

			$this->sys_ok($result);
			exit;
		}
	}


	function doaction_nonlab($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid)
	{

		$rst_data = array('status' => 'OK');
		$status_call = array('status' => 'OK', 'data' => array());

		//echo $stationid;
		//echo $orderid;

		$sql = "SELECT '' AS queueNumber ,
				M_LocationID AS locationID, 
				M_LocationName AS locationName FROM t_orderheader 
				JOIN t_order_location ON T_OrderHeaderID = T_OrderLocationT_OrderHeaderID
				JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID
				AND T_OrderLocationT_SampleStationID = ?
				WHERE  T_OrderHeaderID=?";
		$location = $this->db_onedev->query($sql, array($stationid, $orderid))->row_array();
		$locationID = $location['locationID'];
		$locationName = $location['locationName'];
		$queueNumber = $location['queueNumber'];
		$splitedLocationName = explode(" ", $locationName);
		$locationName = $splitedLocationName[0];



		if ($act == 'call') {
			$sql = "SELECT T_SamplingQueueLastStatusID, T_SamplingQueueStatusName,  T_SampleStationName, T_SampleStationID, T_SampleStationIsNonLab
					FROM t_sampling_queue_last_status
					JOIN t_sampling_queue_status ON T_SamplingQueueLastStatusT_SamplingQueueStatusID = T_SamplingQueueStatusID 
					JOIN t_samplestation ON T_SampleStationID = T_SamplingQueueLastStatusT_SampleStationID 
					WHERE
					T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
					T_SamplingQueueLastStatusT_SampleStationID <>  {$stationid} AND 
					T_SamplingQueueLastStatusT_SamplingQueueStatusID IN (1,3) LIMIT 1";
			//echo $sql;
			$data_status_call = $this->db_onedev->query($sql)->row_array();
			if ($data_status_call) {
				$status_call = array('status' => 'NOTCALL', 'data' => $data_status_call);
				$check_valid = false;

				$sql = "SELECT SUM(countx) as xcount
								FROM (
									SELECT COUNT(*) as countx
									FROM t_orderdetail 
									JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' AND
										T_OrderDetailT_OrderHeaderID = {$orderid} AND T_OrderDetailIsActive = 'Y' 
									JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
									JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
									JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
										T_SampleStationID = {$data_status_call['T_SampleStationID']} 
									LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
										T_SamplingSoT_TestID = T_TestID AND 
										T_SamplingSoIsActive = 'Y'
									WHERE
									ISNULL(T_SamplingSoDoneDate)
									
								) x";
				$not_sampled = $this->db_onedev->query($sql)->row_array();

				if (intval($not_sampled['xcount']) == 0) {
					$sql = "UPDATE t_sampling_queue_last_status
							SET T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5
							WHERE
								T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
								T_SamplingQueueLastStatusT_SampleStationID = {$data_status_call['T_SampleStationID']}";
					$this->db_onedev->query($sql);
					$status_call = array('status' => 'OK', 'data' => array());
				}
			}
		}

		$next_status = $statusnextid;
		if ($act == 'process') {
			$sql = "SELECT
			T_OrderHeaderID,
			T_OrderDetailID as id,
			T_OrderDetailT_TestCode, 
			T_OrderDetailT_TestName, 
			T_TestID as test_id,
			IFNULL(T_SamplingSoID,0) as samplingso_id,
			T_BahanName
			FROM t_orderheader	
			JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
			T_OrderHeaderID = {$orderid} AND 
			T_OrderHeaderIsActive = 'Y'
			JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
			JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
			JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
			JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
			LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
				T_SamplingSoT_TestID = T_TestID AND 
				T_SamplingSoIsActive = 'Y'
			WHERE
				(ISNULL(T_SamplingSoFlag) OR T_SamplingSoFlag = 'P' OR T_SamplingSoFlag = 'X')
			GROUP BY T_TestID";
			/*$sql = "SELECT T_OrderDetailID, T_OrderHeaderID,T_OrderDetailID as id,
				0 T_BarcodeLabID,
				'' T_BarcodeLabBarcode,
				T_OrderDetailT_TestCode, 
				T_OrderDetailT_TestName, 
				T_TestID,
				T_SampleTypeID,
				T_SampleTypeName, 
				T_BahanName
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				LEFT JOIN t_samplingso ON  
							T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND
							T_SamplingSoIsActive = 'Y' AND T_SamplingSoFlag = 'N'
					JOIN t_sampletype ON T_SampleTypeID = T_SamplingSoT_TestID
					JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
					JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
				WHERE
					T_OrderHeaderID = {$orderid} AND (T_SamplingSoFlag = 'N' OR T_SamplingSoFlag = 'X') AND T_OrderHeaderIsActive = 'Y'
			GROUP BY T_TestID";*/
			//echo $sql;
			$rows_all_sample = $this->db_onedev->query($sql)->result();
			if ($rows_all_sample) {
				foreach ($rows_all_sample as $k => $v) {
					/*$sql = "UPDATE t_samplingso SET
							T_SamplingSoFlag = 'P',
							T_SamplingSoFlagDate = CURDATE(),
							T_SamplingSoFlagTime = CURTIME(),
							T_SamplingSoFlagUserID = {$userid},
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoUserID = {$userid}
							WHERE
							T_SamplingSoT_OrderHeaderID = {$orderid} AND 
							T_SamplingSoT_TestID = {$v->T_TestID} 
						
						";
					//echo $sql;
					$this->db_onedev->query($sql);*/
					if ($v->samplingso_id == 0) {
						$sql = "INSERT INTO t_samplingso (
							T_SamplingSoT_SampleStationID,
							T_SamplingSoT_OrderHeaderID,
							T_SamplingSoT_TestID,
							T_SamplingSoProcessDate,
							T_SamplingSoProcessTime,
							T_SamplingSoProcessUserID,
							T_SamplingSoCreated,
							T_SamplingSoCreatedUserID
						)
						VALUES(
							{$stationid},
							{$orderid},
							{$v->test_id},
							CURDATE(),
							CURTIME(),
							{$userid},
							NOW(),
							{$userid}
						) ";
						//echo $sql;

					} else {
						$sql = "UPDATE t_samplingso SET 
								T_SamplingSoProcessDate = CURDATE(), 
								T_SamplingSoProcessTime = CURTIME(), 
								T_SamplingSoFlag = 'P',
								T_SamplingSoIsActive = 'Y',
								T_SamplingSoProcessUserID = {$userid},
								T_SamplingSoLastUpdatedUserID = {$userid},
								T_SamplingSoLastUpdated = NOW()
								WHERE
								T_SamplingSoID = {$v->samplingso_id}";
						//echo $sql;
					}
					$this->db_onedev->query($sql);
				}
				//$this->broadcast("specimen-col-process");
			}
		}

		$isdone = "X";
		if ($act == 'samplingdone') {
			// echo "insert samplingdone";
			$sql = "UPDATE t_samplingso SET
							T_SamplingSoDoneDate = CURDATE(), 
							T_SamplingSoDoneTime = CURTIME(), 
							T_SamplingSoDoneUserID = {$userid},
							T_SamplingSoFlag = 'D',
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoLastUpdatedUserID = {$userid},
							T_SamplingSoLastUpdated = NOW()
						WHERE
						T_SamplingSoT_OrderHeaderID = {$orderid} AND 
						T_SamplingSoT_TestID = {$sampletypeid}";
			$this->db_onedev->query($sql);
			//echo $sql;
			$sql = "SELECT T_SamplingSoID
					FROM t_samplingso
					WHERE T_SamplingSoT_OrderHeaderID ={$orderid}
					AND T_SamplingSoT_TestID = {$sampletypeid}
					AND T_SamplingSoIsActive = 'Y'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$message['asl'] = "get sampling so error";
				$this->sys_error($message);
				exit;
			}
			$samplingso =  $query->result();
			//print_r($samplingso);
			//echo $samplingso[0]->T_SamplingSoID;
			// print_r($samplingso['T_SamplingSoID']);
			//print_r($samplingso);
			//echo $samplingso->T_SamplingSOID;

			$insert_so = $this->nonlabtemplate->generate($samplingso[0]->T_SamplingSoID);
			// if (!$insert_so) {
			// 	$message = $this->db_onedev->error();
			// 	$message['qry'] = $this->db_onedev->last_query();
			// 	$message['inserts0'] = $insert_so;
			// 	$this->sys_error($message);
			// 	exit;
			// }
			// print_r($insert_so);



			$sql = "SELECT t_sampletype.* FROM t_test
			JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID WHERE T_TestID = {$sampletypeid}";
			$dt_sampletype = $this->db_onedev->query($sql)->row();

			$xreq = $requirements;
			$arr_requirements = array();
			foreach ($xreq as $k => $v) {
				if ($v['chex'] == 'Y')
					array_push($arr_requirements, $v['id']);
			}
			$requirements = '[' . join(',', $arr_requirements) . ']';



			$sql = "SELECT count(*) as xcount
				FROM (SELECT * 
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
					T_SamplingSoIsActive = 'Y'
				LEFT JOIN t_sampletype ON T_SampleTypeID = T_SamplingSoT_TestID
				LEFT JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				LEFT JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
					T_SampleStationID = {$stationid}
				WHERE
					T_SamplingSoFlag <> 'D' 
				GROUP BY T_TestID ) xx";
			//echo $sql;
			$xcount = $this->db_onedev->query($sql)->row()->xcount;
			$rst_data = array('status' => 'PARTIAL', 'isdone' => "N");
			$isdone = "N";
			//echo $this->db_onedev->last_query();
			if ($xcount == 0) {
				$isdone = "Y";
				$next_status = 5;
				$rst_data = array('status' => 'OK', 'isdone' => "Y");
			}
			//$this->broadcast("specimen-col-receive");
		}

		if ($act !== 'samplingprocess' && $status_call['status'] == 'OK') {
			$dt_json = json_encode(array('T_SampleStationID' => $stationid, 'T_OrderHeaderID' => $orderid, 'T_SamplingQueueStatusID' => $next_status));
			$query = "INSERT INTO  one_log.log_sampling_queue (Log_SamplingQueueDate,Log_SamplingQueueJSON,Log_SamplingQueueUserID)
						VALUES(NOW(),'{$dt_json}',{$userid})";
			//echo $query;
			//$rows = $this->db_onedev->query($query);
			$sql = "SELECT * 
					FROM t_sampling_queue_last_status 
					WHERE 
					T_SamplingQueueLastStatusT_SampleStationID = {$stationid} AND
					T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
					T_SamplingQueueLastStatusIsActive = 'Y'";
			//echo $sql;
			$data_last = $this->db_onedev->query($sql)->row();

			$query = "INSERT INTO  t_sampling_queue_last_status (
					T_SamplingQueueLastStatusT_SampleStationID,
					T_SamplingQueueLastStatusT_OrderHeaderID,
					T_SamplingQueueLastStatusT_SamplingQueueStatusID,
					T_SamplingQueueLastStatusUserID)
				VALUES(
					{$stationid},
					{$orderid},
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

		if ($act == 'skip' || $status_call['status'] == 'NOTCALL') {
			$skip_time = date('Y-m-d H:i:s', strtotime($prm['skiptime']) + 10);
			$sql = "UPDATE antrian_samplestation SET AntrianSampleStationIsActive = 'N'
					WHERE
					AntrianSampleStationT_OrderLocationID = ?";
			//$query = $this->db_onedev->query($sql,array($prm['orderlocationid']));
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
			//$query = $this->db_onedev->query($sql,array($prm['orderlocationid'],$skip_time,$userid));

		}

		$result = array(
			"total" => 1,
			"records" => $rst_data,
			"nextstatus" => $next_status,
			"isdone" => $isdone
		);

		$sql = "SELECT 
				T_OrderHeaderM_BranchID as branchID,
				T_OrderHeaderMgm_McuID as mcuID
				FROM t_orderheader
				WHERE T_OrderHeaderID = $orderid";
		$qry = $this->db_onedev->query(
			$sql
		);

		if (!$qry) {
			$this->sys_error_db("Error get broadcast data", $this->db_onedev);
			exit;
		}
		$dataBroadcast = $qry->row_array();
		if ($act ==  'call') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.call." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'process') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.process." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'samplingdone') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.done." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'skip') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.skip." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		}
		return $result;
	}



	function doaction_additional_fisik($act, $userid, $stationid, $orderid, $sampletypeid, $barcodelabid, $requirements, $statusnextid)
	{

		$rst_data = array('status' => 'OK');
		$status_call = array('status' => 'OK', 'data' => array());

		//echo $stationid;
		//echo $orderid;

		$sql = "SELECT '' AS queueNumber ,
				M_LocationID AS locationID, 
				M_LocationName AS locationName FROM t_orderheader 
				JOIN t_order_location ON T_OrderHeaderID = T_OrderLocationT_OrderHeaderID
				JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID
				AND T_OrderLocationT_SampleStationID = ?
				WHERE  T_OrderHeaderID=?";
		$location = $this->db_onedev->query($sql, array($stationid, $orderid))->row_array();
		$locationID = $location['locationID'];
		$locationName = $location['locationName'];
		$queueNumber = $location['queueNumber'];
		$splitedLocationName = explode(" ", $locationName);
		$locationName = $splitedLocationName[0];



		if ($act == 'call') {
			$sql = "SELECT T_SamplingQueueLastStatusID, T_SamplingQueueStatusName,  T_SampleStationName, T_SampleStationID, T_SampleStationIsNonLab
					FROM t_sampling_queue_last_status
					JOIN t_sampling_queue_status ON T_SamplingQueueLastStatusT_SamplingQueueStatusID = T_SamplingQueueStatusID 
					JOIN t_samplestation ON T_SampleStationID = T_SamplingQueueLastStatusT_SampleStationID 
					WHERE
					T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
					T_SamplingQueueLastStatusT_SampleStationID <>  {$stationid} AND 
					T_SamplingQueueLastStatusT_SamplingQueueStatusID IN (1,3) LIMIT 1";
			//echo $sql;
			$data_status_call = $this->db_onedev->query($sql)->row_array();
			if ($data_status_call) {
				$status_call = array('status' => 'NOTCALL', 'data' => $data_status_call);
				$check_valid = false;

				$sql = "SELECT SUM(countx) as xcount
								FROM (
									SELECT COUNT(*) as countx
									FROM t_orderdetail
									JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND 
									T_OrderDetailT_OrderHeaderID = {$orderid} AND T_OrderDetailIsActive = 'Y'
									JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' 
									JOIN nat_testfisik ON T_TestNat_TestID = Nat_TestFisikNat_TestID AND Nat_TestFisikIsActive = 'Y'
									JOIN t_testadditionalfisik ON Nat_TestFisikT_TestAdditionalFisikID = T_TestAdditionalFisikID AND T_TestAdditionalFisikIsActive = 'Y'
									JOIN t_samplestation ON T_TestAdditionalFisikT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
									LEFT JOIN t_samplingso_additional_fisik ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestAdditionalFisikID AND T_SamplingSoIsActive = 'Y'
									WHERE
									ISNULL(T_SamplingSoDoneDate)
								) x";
				$not_sampled = $this->db_onedev->query($sql)->row_array();

				if (intval($not_sampled['xcount']) == 0) {
					$sql = "UPDATE t_sampling_queue_last_status
							SET T_SamplingQueueLastStatusT_SamplingQueueStatusID = 5
							WHERE
								T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
								T_SamplingQueueLastStatusT_SampleStationID = {$data_status_call['T_SampleStationID']}";
					$this->db_onedev->query($sql);
					$status_call = array('status' => 'OK', 'data' => array());
				}
			}
		}

		$next_status = $statusnextid;
		if ($act == 'process') {
			$sql = "SELECT
			T_OrderHeaderID,
			T_OrderDetailID as id,
			T_OrderDetailT_TestCode, 
			T_TestAdditionalFisikName as T_OrderDetailT_TestName, 
			T_TestAdditionalFisikID as test_id,
			IFNULL(T_SamplingSoID,0) as samplingso_id,
			T_TestAdditionalFisikName as T_BahanName
			FROM t_orderheader	
			JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
			JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' 
			JOIN nat_testfisik ON T_TestNat_TestID = Nat_TestFisikNat_TestID AND Nat_TestFisikIsActive = 'Y'
			JOIN t_testadditionalfisik ON Nat_TestFisikT_TestAdditionalFisikID = T_TestAdditionalFisikID AND T_TestAdditionalFisikIsActive = 'Y'
			JOIN t_samplestation ON T_TestAdditionalFisikT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
			LEFT JOIN t_samplingso_additional_fisik ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestAdditionalFisikID AND T_SamplingSoIsActive = 'Y'
			WHERE
				(ISNULL(T_SamplingSoFlag) OR T_SamplingSoFlag = 'P' OR T_SamplingSoFlag = 'X')
			GROUP BY T_TestID";

			//echo $sql;
			$rows_all_sample = $this->db_onedev->query($sql)->result();
			if ($rows_all_sample) {
				foreach ($rows_all_sample as $k => $v) {

					if ($v->samplingso_id == 0) {
						$sql = "INSERT INTO t_samplingso_additional_fisik (
							T_SamplingSoT_SampleStationID,
							T_SamplingSoT_OrderHeaderID,
							T_SamplingSoT_TestID,
							T_SamplingSoProcessDate,
							T_SamplingSoProcessTime,
							T_SamplingSoProcessUserID,
							T_SamplingSoCreated,
							T_SamplingSoCreatedUserID
						)
						VALUES(
							{$stationid},
							{$orderid},
							{$v->test_id},
							CURDATE(),
							CURTIME(),
							{$userid},
							NOW(),
							{$userid}
						) ";
						//echo $sql;

					} else {
						$sql = "UPDATE t_samplingso_additional_fisik SET 
								T_SamplingSoProcessDate = CURDATE(), 
								T_SamplingSoProcessTime = CURTIME(), 
								T_SamplingSoFlag = 'P',
								T_SamplingSoIsActive = 'Y',
								T_SamplingSoProcessUserID = {$userid},
								T_SamplingSoLastUpdatedUserID = {$userid},
								T_SamplingSoLastUpdated = NOW()
								WHERE
								T_SamplingSoID = {$v->samplingso_id}";
						//echo $sql;
					}
					$this->db_onedev->query($sql);
				}
				//$this->broadcast("specimen-col-process");
			}
		}

		$isdone = "X";
		if ($act == 'samplingdone') {
			//echo "insert samplingdone";
			$sql = "UPDATE t_samplingso_additional_fisik SET
							T_SamplingSoDoneDate = CURDATE(), 
							T_SamplingSoDoneTime = CURTIME(), 
							T_SamplingSoDoneUserID = {$userid},
							T_SamplingSoFlag = 'D',
							T_SamplingSoIsActive = 'Y',
							T_SamplingSoLastUpdatedUserID = {$userid},
							T_SamplingSoLastUpdated = NOW()
						WHERE
						T_SamplingSoT_OrderHeaderID = {$orderid} AND 
						T_SamplingSoT_TestID = {$sampletypeid}";
			$this->db_onedev->query($sql);
			//echo $sql;



			$sql = "SELECT count(*) as xcount
				FROM (SELECT * 
				FROM t_orderheader	
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' AND
				T_OrderHeaderID = {$orderid} AND T_OrderHeaderIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y' 
				JOIN nat_testfisik ON T_TestNat_TestID = Nat_TestFisikNat_TestID AND Nat_TestFisikIsActive = 'Y'
				JOIN t_testadditionalfisik ON Nat_TestFisikT_TestAdditionalFisikID = T_TestAdditionalFisikID AND T_TestAdditionalFisikIsActive = 'Y'
				JOIN t_samplestation ON T_TestAdditionalFisikT_SampleStationID = T_SampleStationID AND T_SampleStationID = {$stationid}
				LEFT JOIN t_samplingso_additional_fisik ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
				T_SamplingSoT_TestID = T_TestAdditionalFisikID AND 
				T_SamplingSoIsActive = 'Y'
				WHERE
					T_SamplingSoFlag <> 'D' 
					
				GROUP BY T_TestAdditionalFisikID ) xx";
			//echo $sql;
			$xcount = $this->db_onedev->query($sql)->row()->xcount;
			$rst_data = array('status' => 'PARTIAL', 'isdone' => "N");
			$isdone = "N";
			//echo $this->db_onedev->last_query();
			if ($xcount == 0) {
				$isdone = "Y";
				$next_status = 5;
				$rst_data = array('status' => 'OK', 'isdone' => "Y");
			}
			//$this->broadcast("specimen-col-receive");
		}

		if ($act !== 'samplingprocess' && $status_call['status'] == 'OK') {
			$dt_json = json_encode(array('T_SampleStationID' => $stationid, 'T_OrderHeaderID' => $orderid, 'T_SamplingQueueStatusID' => $next_status));
			$query = "INSERT INTO  one_log.log_sampling_queue (Log_SamplingQueueDate,Log_SamplingQueueJSON,Log_SamplingQueueUserID)
						VALUES(NOW(),'{$dt_json}',{$userid})";
			//echo $query;
			//$rows = $this->db_onedev->query($query);
			$sql = "SELECT * 
					FROM t_sampling_queue_last_status 
					WHERE 
					T_SamplingQueueLastStatusT_SampleStationID = {$stationid} AND
					T_SamplingQueueLastStatusT_OrderHeaderID = {$orderid} AND 
					T_SamplingQueueLastStatusIsActive = 'Y'";
			//echo $sql;
			$data_last = $this->db_onedev->query($sql)->row();

			$query = "INSERT INTO  t_sampling_queue_last_status (
					T_SamplingQueueLastStatusT_SampleStationID,
					T_SamplingQueueLastStatusT_OrderHeaderID,
					T_SamplingQueueLastStatusT_SamplingQueueStatusID,
					T_SamplingQueueLastStatusUserID)
				VALUES(
					{$stationid},
					{$orderid},
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



		$result = array(
			"total" => 1,
			"records" => $rst_data,
			"nextstatus" => $next_status,
			"isdone" => $isdone
		);

		$sql = "SELECT 
				T_OrderHeaderM_BranchID as branchID,
				T_OrderHeaderMgm_McuID as mcuID
				FROM t_orderheader
				WHERE T_OrderHeaderID = $orderid";
		$qry = $this->db_onedev->query(
			$sql
		);

		if (!$qry) {
			$this->sys_error_db("Error get broadcast data", $this->db_onedev);
			exit;
		}
		$dataBroadcast = $qry->row_array();
		if ($act ==  'call') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.call." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'process') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.process." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'samplingdone') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.done." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		} else if ($act == 'skip') {
			file_get_contents("http://" . $this->IP_SOCKET_IO . ":9088/broadcast/sm.skip." . $stationid . "." . $dataBroadcast['mcuID'] . "." . $dataBroadcast['branchID']);
		}
		return $result;
	}


	function search_patient()
	{

		//# ambil parameter input
		$prm = $this->sys_input;

		$data_patient = [];
		$sql = "
				SELECT DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
				T_OrderHeaderLabNumber as labnumber,
				T_OrderHeaderM_PatientAge as patient_age,
				M_PatientName as patient_name,
				M_PatientNoReg as noreg,
				IF(M_PatientGender = 'male','Laki-laki','Perempuan') as gender,
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
				M_PatientJob as job,
				M_PatientPosisi as posisi,
				IF(M_PatientDivisi = '','-',M_PatientDivisi) as divisi,
				M_PatientHp as hp,
				M_PatientEmail as email,
				M_PatientPhoto as photo
				FROM t_orderheader
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID AND 
				T_OrderHeaderID = {$prm['order_id']} AND T_OrderHeaderLabNumber = '{$prm['noreg']}' AND
				T_OrderHeaderIsActive = 'Y'
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data patient");
			exit;
		}

		$data_patient = $query->row_array();

		$data_packet = [];
		$sql = "
				SELECT T_PacketName as packet_name,
				T_PacketID as packet_id,
				'' as active,
				'' as details
				FROM t_orderdetailorder
				JOIN t_packet ON T_OrderDetailOrderT_PacketID = T_PacketID
				WHERE
				T_OrderDetailOrderT_OrderHeaderID = {$prm['order_id']} AND 
				T_OrderDetailOrderIsPacket = 'Y' AND
				T_OrderDetailOrderIsActive = 'Y'
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data packet");
			exit;
		}

		$data_packet = $query->result_array();
		if ($data_packet) {
			foreach ($data_packet as $key => $value) {
				$data_packet[$key]['active'] = false;
				$sql = "SELECT T_TestName as test_name
					FROM t_packetdetail 
					JOIN t_test ON T_PacketDetailT_TestID = T_TestID
					WHERE T_PacketDetailT_PacketID = {$value['packet_id']} AND T_PacketDetailIsActive = 'Y'";
				$query = $this->db_onedev->query($sql);
				if (!$query) {
					$this->sys_error_db("data packet detail");
					exit;
				}

				$data_packet_details = $query->result_array();
				if (count($data_packet_details) > 0)
					$data_packet[$key]['details'] = $data_packet_details;
				else
					$data_packet[$key]['details'] = [];
			}
		}

		$data_tests = [];
		$sql = "
				SELECT T_TestName as test_name
				FROM t_orderdetailorder
				JOIN t_test ON T_OrderDetailOrderT_TestID = T_TestID
				WHERE
				T_OrderDetailOrderT_OrderHeaderID = {$prm['order_id']} AND 
				T_OrderDetailOrderIsPacket = 'N' AND
				T_OrderDetailOrderIsActive = 'Y'
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data tests");
			exit;
		}

		$data_tests = $query->result_array();

		$data_sample_lab = [];
		$sql = "SELECT T_SampleTypeName as sampletype_name,
				T_OrderSampleBarcode as barcode,
				IF(ISNULL(T_OrderSampleSamplingDate),'Belum diambil',DATE_FORMAT(T_OrderSampleSamplingDate,'%d-%m-%Y')) as sampling_date,
				IF(ISNULL(T_OrderSampleSamplingTime),'',T_OrderSampleSamplingTime) as sample_time,
				IF(ISNULL(T_OrderSampleReceiveDate),'Belum dilakukan',DATE_FORMAT(T_OrderSampleReceiveDate,'%d-%m-%Y')) as receive_date,
				IF(ISNULL(T_OrderSampleReceiveTime),'',DATE_FORMAT(T_OrderSampleReceiveTime,'%H:%i')) as receive_time,
				T_OrderSampleSampling as is_sampling,
				T_OrderSampleReceive as is_received
				FROM t_ordersample
				JOIN t_sampletype ON T_OrderSampleT_SampleTypeID = T_SampleTypeID AND
				T_OrderSampleT_OrderHeaderID = {$prm['order_id']} AND 
				T_OrderSampleIsActive = 'Y'
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data sample lab");
			exit;
		}

		$data_sample_lab = $query->result_array();

		$data_sample_radiodiagnostic  = [];
		$sql = "SELECT T_TestName as sampletype_name,
				T_OrderHeaderLabNumber as barcode,
				IF(ISNULL(T_SamplingSoProcessDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as sampling_date,
				IF(ISNULL(T_SamplingSoProcessTime),'',T_SamplingSoProcessTime) as sample_time,
				IF(ISNULL(T_SamplingSoDoneDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as receive_date,
				IF(ISNULL(T_SamplingSoDoneTime),'',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as receive_time,
				IF(ISNULL(T_SamplingSoFlag),'N','Y') as is_sampling,
				IF(ISNULL(T_SamplingSoFlag) OR T_SamplingSoFlag <> 'D','N','Y') as is_received
				FROM t_orderdetail
				JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND
				T_OrderDetailT_OrderHeaderID = {$prm['order_id']} AND 
				T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID 
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID 
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
				T_SampleStationIsNonLab = 'RADIODIAGNOSTIC'
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND
				T_SamplingSoT_TestID = T_TestID

				
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data sample radiodiagnostic");
			exit;
		}

		$data_sample_radiodiagnostic = $query->result_array();

		$data_sample_electromedic = [];
		$sql = "SELECT T_TestName as sampletype_name,
				T_OrderHeaderLabNumber as barcode,
				IF(ISNULL(T_SamplingSoProcessDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as sampling_date,
				IF(ISNULL(T_SamplingSoProcessTime),'',T_SamplingSoProcessTime) as sample_time,
				IF(ISNULL(T_SamplingSoDoneDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as receive_date,
				IF(ISNULL(T_SamplingSoDoneTime),'',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as receive_time,
				IF(ISNULL(T_SamplingSoFlag),'N','Y') as is_sampling,
				IF(ISNULL(T_SamplingSoFlag) OR T_SamplingSoFlag <> 'D','N','Y') as is_received
				FROM t_orderdetail
				JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND
				T_OrderDetailT_OrderHeaderID = {$prm['order_id']} AND 
				T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID 
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID 
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
				T_SampleStationIsNonLab = 'ELEKTROMEDIS'
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND
				T_SamplingSoT_TestID = T_TestID				
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data sample electromedis");
			exit;
		}

		$data_sample_electromedic = $query->result_array();

		$data_sample_other = [];
		$sql = "SELECT T_TestName as sampletype_name,
				T_OrderHeaderLabNumber as barcode,
				IF(ISNULL(T_SamplingSoProcessDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoProcessDate,'%d-%m-%Y')) as sampling_date,
				IF(ISNULL(T_SamplingSoProcessTime),'',T_SamplingSoProcessTime) as sample_time,
				IF(ISNULL(T_SamplingSoDoneDate),'Belum dilakukan',DATE_FORMAT(T_SamplingSoDoneDate,'%d-%m-%Y')) as receive_date,
				IF(ISNULL(T_SamplingSoDoneTime),'',DATE_FORMAT(T_SamplingSoDoneTime,'%H:%i')) as receive_time,
				IF(ISNULL(T_SamplingSoFlag),'N','Y') as is_sampling,
				IF(ISNULL(T_SamplingSoFlag) OR T_SamplingSoFlag <> 'D','N','Y') as is_received
				FROM t_orderdetail
				JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND
				T_OrderDetailT_OrderHeaderID = {$prm['order_id']} AND 
				T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID 
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID 
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND 
				T_SampleStationIsNonLab = 'OTHERS'
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND
				T_SamplingSoT_TestID = T_TestID
			";
		//echo $sql;
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("data sample other");
			exit;
		}

		$data_sample_other = $query->result_array();



		$result = array(
			"data_patient" => $data_patient ? $data_patient : [],
			"data_packet" => $data_packet ? $data_packet : [],
			"data_tests" => $data_tests ? $data_tests : [],
			"data_sample_lab" => $data_sample_lab ? $data_sample_lab : [],
			"data_sample_radiodiagnostic" => $data_sample_radiodiagnostic ? $data_sample_radiodiagnostic : [],
			"data_sample_electromedic" => $data_sample_electromedic ? $data_sample_electromedic : [],
			"data_sample_other" => $data_sample_other ? $data_sample_other : []
		);
		$this->sys_ok($result);
		exit;
	}
}
