<?php
class Resultentry extends MY_Controller
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
		$this->load->library('Nonlabtemplate');
		$this->load->library('Kesimpulanfisik');
		$this->load->library("Soresultlog");
	}


	function searchcompany()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;

		$max_rst = 12;
		$tot_count = 0;

		$q = [
			'search'     => '%'
		];

		if ($prm['search'] == '') {

			$rows = array(array('id' => 0, 'name' => 'Semua'));
			$result = array("total" => 1, "records" => $rows, "total_display" => sizeof($rows));
			$this->sys_ok($result);
		} else {
			$q['search'] = "%{$prm['search']}%";
			$sql = "
				SELECT CorporateID as id, CorporateName as name
				FROM corporate
				WHERE 
				CorporateName like ?
				AND CorporateIsActive	 = 'Y'
				ORDER BY CorporateName DESC
			  ";
			$query = $this->db_onedev->query($sql, array($q['search']));

			if ($query) {
				$rows = $query->result_array();
				array_push($rows, array('id' => 0, 'name' => 'Semua'));
				//echo $this->db_onedev->last_query();
				$result = array("total" => $tot_count, "records" => $rows, "total_display" => sizeof($rows));
				$this->sys_ok($result);
			} else {
				$this->sys_error_db("corporate rows", $this->db_onedev);
				exit;
			}
		}
	}


	function getdetails($id)
	{
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

	function getdeliveries($orderid, $re_id)
	{
		$query = "	SELECT T_OrderDeliveryID as id, 
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
					END as addressid,
					'brown' as color,
					'' as status_payment,
					'' as url
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
					END as addressid,
					'brown' as color,
					'' as status_payment,
					'' as url
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
		foreach ($rows as $k => $v) {
			if ($rst != '') {
				$rst = $rst . ' , ' . $v['label'];
			}
			$rows[$k]['url'] = array();
			if ($v['typedeliveryid'] == '3' || $v['typedeliveryid'] == '4' || $v['typedeliveryid'] == '5') {
				$sql = "SELECT IF(M_MouIsBill = 'Y','Y',Last_StatusPaymentIsLunas) as xpayment
					FROM last_statuspayment
					join t_orderheader ON Last_StatusPaymentT_OrderHeaderID = T_OrderHeaderID 
					JOIN m_mou ON T_OrderHeaderM_MouID = M_MouID 
					WHERE
						Last_StatusPaymentT_OrderHeaderID = {$orderid}";
				//echo $sql;
				$status_payment = $this->db_onedev->query($sql)->row()->xpayment;
				$rows[$k]['status_payment'] = $status_payment;
				if ($status_payment == 'Y') {
					$sql = "SELECT * 
						FROM t_email_nonlab
						JOIN so_resultentry ON T_EmailNonLabResultEntryID = So_ResultEntryID
						WHERE
							T_EmailNonLabT_OrderHeaderID = {$orderid} AND T_EmailNonLabResultEntryID = {$re_id}";
					$row_format = $this->db_onedev->query($sql)->row_array();
					if ($row_format) {
						$format = array();
						$url = array();
						$rows[$k]['color'] = 'teal lighten-2';
						$rows[$k]['label'] = $v["label"] . " : " . $row_format['T_EmailNonLabFormat'];
						$url = array(array('test' => '', 'url' => $row_format['T_EmailNonLabUrl']));
						array_push($format, $row_format['T_EmailNonLabFormat']);
						if ($row_format['So_ResultEntrySo_TemplateOther'] == 'UMUM' || $row_format['So_ResultEntrySo_TemplateOther'] == 'UMUM_KONSUL') {
							$url = array();
							$ex_url = explode('|^|', $row_format['T_EmailNonLabUrl']);
							//print_r($ex_url);
							foreach ($ex_url as $k_url => $v_url) {
								if ($k_url == 0)
									$testname = 'Riwayat';
								else
									$testname = 'Fisik';
								$xurl = array('test' => $testname, 'url' => $v_url);
								array_push($url, $xurl);
							}
						}
						if ($row_format['So_ResultEntrySo_TemplateOther'] == 'UMUM_K3') {
							$url = array();
							$ex_url = explode('|^|', $row_format['T_EmailNonLabUrl']);
							//print_r($ex_url);
							foreach ($ex_url as $k_url => $v_url) {
								if ($k_url == 0)
									$testname = 'Riwayat';
								else if ($k_url == 1)
									$testname = 'Fisik';
								else
									$testname = 'Pajanan';
								$xurl = array('test' => $testname, 'url' => $v_url);
								array_push($url, $xurl);
							}
						}
						$join_format = join(",", $format);
						$rows[$k]['url'] = $url;
						$rows[$k]['label'] = $v["label"] . " : " . $join_format;
					} else {
						$rows[$k]['label'] = $v["label"] . " : Belum Pilih Format";
					}
				} else {
					$rows[$k]['color'] = 'orange lighten-2';
					$rows[$k]['url'] = '';
				}
			}
			$rst .= $v['label'];
		}
		return $rows;
	}

	function getphotos($orderid, $sampletypeid)
	{
		$rows  = [];
		//print_r($_SERVER);
		$urlbase = 'http://' . $_SERVER['SERVER_NAME'] . "/one-media/one-image-nonlab/";
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

	function getdoctors($sampletypeid)
	{
		$rows  = [];

		$sql = "SELECT M_DoctorID as doctor_id,
				CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,'...') as doctor_fullname
				FROM m_doctor
				JOIN m_doctor ON M_DoctorSOM_DoctorID = M_DoctorID
				WHERE
					M_DoctorSOIsActive = 'Y'
				GROUP BY M_DoctorSOID";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->result_array();
		return $rows;
	}

	function search_old_lama()
	{
		$prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$userid = $this->sys_user["M_UserID"];
		$group_results = array();
		$sql = " SELECT *
			   FROM group_result_entry
			    WHERE
				GroupResultEntryCode = '04' AND GroupResultEntryIsActive = 'Y'";
		$xgresult = $this->db_onedev->query($sql)->result_array();
		if ($xgresult) {
			foreach ($xgresult as $kgr => $vgr) {
				array_push($group_results, $vgr['GroupResultEntryGroup_ResultID']);
			}
		}
		$group_results = join(",", $group_results);

		$search = $prm["search"];
		$status = $prm["stationid"];
		$startdate = $prm["startdate"];
		$enddate = $prm["enddate"];
		$groupid = $prm["groupid"];
		$subgroupid = $prm["subgroupid"];
		$companyid = $prm['companyid'];
		$filter_company = '';
		$filter_company_exclude = "";
		if ($companyid) {
			if (($companyid != 0 || $companyid != '0') && $prm["switch_exclude"])
				$filter_company_exclude = "WHERE company_id <> {$companyid}";
			if (($companyid != 0 || $companyid != '0') && !$prm["switch_exclude"]) {
				$filter_company = " AND T_OrderHeaderCorporateID = {$companyid}";
			}
		}
		$join_group = '';
		if ($groupid != 0) {
			$join_group = "JOIN nat_group ON T_TestNat_GroupID = Nat_GroupID AND Nat_GroupID = {$groupid}";
		}
		$join_subgroup = '';
		if ($subgroupid != 0) {
			$join_group = "JOIN nat_subgroup ON T_TestNat_SubgroupID = Nat_SubgroupID AND Nat_SubgroupID = {$subgroupid}";
		}

		if (!isset($prm['current_page']))
			$prm['current_page'] = 1;

		$sql_where = "WHERE ( ( T_SamplingSoDoneDate BETWEEN '{$startdate} 00:00:00' AND '{$enddate} 23:59:59' ) OR ( T_OrderHeaderDate BETWEEN '{$startdate} 00:00:00' AND '{$enddate} 23:59:59' ) ) AND T_SamplingSoIsActive = 'Y'";
		$number_limit = 10;
		$number_offset = ($prm['current_page'] - 1) * $number_limit;
		//$sql_param = array();
		if ($search != "") {
			$sql_where .= " AND ( T_OrderHeaderLabNumber like '%$search%' OR M_PatientName like '%$search%' ) ";
			// $prm['current_page'] = 1;
		}


		$sql = "	SELECT count(*) as total
				FROM (
					SELECT T_SamplingSOID
					FROM t_samplingso
					JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_SamplingSOT_TestID AND Group_ResultDetailIsActive = 'Y'
					JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
					JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  $filter_company
					$sql_where
					GROUP BY T_SamplingSOID
				) x
				$filter_company_exclude
				";
		//echo $sql;

		$query = $this->db_onedev->query($sql);

		$tot_count = 0;
		$tot_page = 0;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
			$tot_page = ceil($tot_count / $number_limit);
		} else {
			$this->sys_error_db("t_samplestorageout count", $this->db_onedev);
			exit;
		}

		$sql = 	"SELECT * FROM (
					SELECT 
					T_OrderHeaderID as trx_id,
					IFNULL(So_ResultEntryID,0) as re_id,
					T_OrderHeaderLabNumber as ordernumber,
					'' as ordernumber_ext,
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname,
					IF(M_PatientGender = 'male','LAKI-LAKI','PEREMPUAN') as sexname,
					IF(M_PatientGender = 'male','L','P') as sexcode,
					DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y') as orderdate,
					DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
					T_OrderHeaderM_PatientAge as age,
					UPPER(T_OrderHeaderM_PatientAge) as umur,
					'' as languange_name,
					T_TestName as test_name,
					Group_ResultName as group_name,
					Group_ResultResumeMcu as group_resume_mcu,
					'' as details,
					CASE
						WHEN So_ResultEntryStatus = 'NEW' THEN 'BARU'
						WHEN So_ResultEntryStatus = 'VAL1'  THEN 'VALIDASI'
						WHEN So_ResultEntryStatus = 'VAL2'  THEN 'VERIFIKASI'
						WHEN So_ResultEntryStatus IS NULL THEN 'NO TEMPLATE'
					END as status_name,
					'' as deliveries,
					'N' as iscito,
					'' as doctor_fullname,
					IFNULL(T_OrderHeaderFoNote,'') as fo_note,
					fn_getstaffname(T_OrderHeaderFoNoteM_UserID) as fo_note_user,
					'' as fo_ver_note, 
					'' as fo_ver_note_user,
					'' as sampling_note,
					'' as sampling_note_user,
					UPPER(CorporateName) as company_name,
					CorporateID as company_id,
					T_SamplingSOID,
					IFNULL(So_SignatureUrl,'') as image_signature,
					T_OrderHeaderID,
					T_TestID
				FROM t_samplingso
				JOIN t_orderdetail ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
				T_OrderDetailT_TestID = T_SamplingSoT_TestID AND T_OrderDEtailIsActive = 'Y'
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID
				JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_SamplingSoT_TestID AND Group_ResultDetailIsActive = 'Y'
				JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
				JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID
				JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  $filter_company
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				LEFT JOIN m_title ON M_PatientM_TitleID = M_TitleID
				LEFT JOIN so_resultentry ON T_OrderDetailID =  So_ResultEntryT_OrderDetailID AND So_ResultEntryIsActive = 'Y'
				
				LEFT JOIN so_signature ON So_SignatureT_OrderHeaderID = T_OrderHeaderID AND So_SignatureIsActive = 'Y'
				$sql_where
				GROUP BY T_OrderHeaderID, T_TestID
				ORDER BY T_OrderHeaderID ASC
				limit $number_limit offset $number_offset) x
				$filter_company_exclude";
		//echo $sql;

		$query = $this->db_onedev->query($sql);
		$rows = $query->result_array();
		//echo $this->db_onedev->last_query();

		if ($rows) {
			foreach ($rows as $k => $v) {
				$arr_status = array();
				//echo $v['re_id'] ;
				if (intval($v['re_id']) == 0) {
					//echo 'IN';
					$insert_so = $this->nonlabtemplate->generate($v['T_SamplingSOID']);
					//print_r($insert_so);
					if ($insert_so) {
						$sql = "SELECT IFNULL(M_DoctorID,0) as M_DoctorID
								FROM m_staff
								JOIN m_user ON M_UserM_StaffID = M_StaffID AND M_UserID = {$userid}
								LEFT JOIN m_doctor ON M_StaffM_DoctorID = M_DoctorID
								WHERE
								M_StaffIsActive = 'Y'
								LIMIT 1";
						$row_doctor_staff = $this->db_onedev->query($sql)->row_array();

						if (intval($row_doctor_staff['M_DoctorID']) > 0) {
							$sql = "UPDATE so_resultentry SET So_ResultEntryM_DoctorID = {$row_doctor_staff['M_DoctorID']} 
									WHERE So_ResultEntryID = {$v['re_id']}
									";
							$this->db_onedev->query($sql);
						}
						$v['re_id'] = $insert_so['So_ResultEntryID'];
						$rows[$k]['re_id'] = $v['re_id'];
						$v['status_name'] = 'BARU';
						$rows[$k]['status_name'] = $v['status_name'];
					}
				}

				if ($v['image_signature'] != '') {
					$rows[$k]['image_signature'] = $v['image_signature'] . "?=" . date("YmdHis");
				}
				$sql = 	"SELECT 
					IFNULL(So_ResultEntryID,0) as trx_id,
					IFNULL(So_ResultEntryID,0) as re_id, 
					T_SamplingSoT_OrderHeaderID as orderid,
					T_TestT_SampleTypeID as sampletypeid,
					T_SamplingSoID,
					UPPER(T_TestName) as test_name,
					Group_ResultName as group_name,
					Group_ResultResumeMcu as group_resume_mcu,
					T_TestID as test_id,
					T_TestNat_TestID as nat_testid,
					0 as language_id,
					So_ResultEntryNonlab_TemplateID as template_id,
					So_ResultEntryNonlab_TemplateName as template_name,
					NonlabTemplateFlagOther as template_flag_other,
					'' as status_result,
					'' as status_result_arr,
					CASE
						WHEN So_ResultEntryStatus = 'NEW' THEN 'BARU'
						WHEN So_ResultEntryStatus = 'VAL1'  THEN 'VALIDASI 1'
						WHEN So_ResultEntryStatus = 'VAL2'  THEN 'VALIDASI 2'
						WHEN So_ResultEntryStatus IS NULL THEN 'NO TEMPLATE'
					END as status_name,
					'' as note,
					So_ResultEntryStatus as status,
					'Bahasa Indonesia' as language_name,
					'' as doctors,
					IFNULL(M_DoctorID,0) as doctor_id,
					IF(ISNULL(M_DoctorID),'-',CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,M_DoctorSuffix,M_DoctorSuffix)) as doctor_fullname,
					'' as details,
					'' as langs,
					'' as photos
				FROM t_samplingso
				LEFT JOIN so_resultentry ON T_SamplingSoT_OrderHeaderID = So_ResultEntryT_OrderHeaderID AND So_ResultEntryID = {$v['re_id']} 
				LEFT JOIN nonlab_template ON So_ResultEntryNonlab_TemplateID = NonlabTemplateID AND NonlabTemplateIsActive = 'Y'
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID
				LEFT JOIN m_doctor ON So_ResultEntryM_DoctorID = M_DoctorID 
				JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_TestID AND Group_ResultDetailIsActive = 'Y'
				JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
				WHERE
				T_SamplingSoID = {$v['T_SamplingSOID']} AND T_SamplingSoIsActive = 'Y' 
				GROUP BY orderid, test_id";
				//echo $sql;
				//print_r($v);
				$rst_details = $this->db_onedev->query($sql)->result_array();
				foreach ($rst_details as $ki => $vi) {
					array_push($arr_status, $vi['status_header']);
					$xstatus_result = array();
					$sql = "SELECT NonlabConclusionDetailID as id, NonlabConclusionDetailName as name, NonlabConclusionDetailIsNormal AS isNormal
							FROM so_resultentry_category_result
							JOIN nonlab_conclusion_detail ON So_ResultEntryCategoryNonlabConclusionDetailID = NonlabConclusionDetailID AND 
							NonlabConclusionDetailIsActive = 'Y'
							WHERE
							So_ResultEntryCategoryResultSo_ResultEntryID = {$vi['trx_id']} AND 
							So_ResultEntryCategoryResultIsActive = 'Y'
					";
					$get_status_result = $this->db_onedev->query($sql)->result_array();
					if ($get_status_result) {
						$xstatus_result = $get_status_result;
					}

					$rst_details[$ki]['status_result'] = $xstatus_result;

					$sql = "SELECT NonlabConclusionDetailID AS id, NonlabConclusionDetailName as name, NonlabConclusionDetailIsNormal AS isNormal
							FROM nonlab_conclusion_detail d1
							JOIN (SELECT * FROM nonlab_conclusion WHERE NonlabConclusionIsActive = 'Y' LIMIT 1) d2
							ON d1.NonlabConclusionDetailNonlabConclusionID = d2.NonlabConclusionID";
					$data_status_result_array = $this->db_onedev->query($sql)->result_array();
					$sql = "SELECT NonlabConclusionDetailID AS id , NonlabConclusionDetailName as name, NonlabConclusionDetailIsNormal AS isNormal
							FROM nonlab_conclusion_mapping
							JOIN nonlab_conclusion_detail ON NonlabConclusionMappingNonlabConclusionID = NonlabConclusionDetailNonlabConclusionID AND NonlabConclusionDetailIsActive ='Y'
							JOIN nonlab_conclusion ON NonlabConclusionMappingNonlabConclusionID  = NonlabConclusionID AND NonlabConclusionIsActive = 'Y'
							WHERE NonlabConclusionMappingNat_TestID = {$vi['nat_testid']}";
					$data_status_result_array_other = $this->db_onedev->query($sql)->result_array();
					if ($data_status_result_array_other) {
						$data_status_result_array = $data_status_result_array_other;
					}

					$rst_details[$ki]['status_result_arr'] = $data_status_result_array;
					$rst_details[$ki]['status_result'] = $xstatus_result;

					$sql = 	"SELECT 
								So_ResultEntryDetailID as trx_id,
								So_ResultEntryDetailNonlab_TemplateDetailID as template_detail_id,
								So_ResultEntryDetailNonlab_TemplateDetailName as result_label,
								So_ResultEntryDetailResult as result_value,
								'N' as flag_print,
								So_ResultEntryDetailResult as result_value_before
							FROM so_resultentrydetail
							
							WHERE
							So_ResultEntryDetailSo_ResultEntryID = {$vi['trx_id']} AND So_ResultEntryDetailisActive = 'Y'";
					//echo $sql;
					$rst_details[$ki]['details'] = $this->db_onedev->query($sql)->result_array();
					//$rst_details[$ki]['langs'] = $this->getlangs($vi['orderid']);
					$rst_details[$ki]['photos'] = $this->getphotos($vi['orderid'], $vi['sampletypeid']);
					//$rst_details[$ki]['doctors'] = $this->getdoctors($vi['sampletypeid']);
				}
				$rows[$k]['details'] = $rst_details;
				//$rows[$k]['deliveries'] = $this->getdeliveries($v['trx_id'],$v['re_id']);
			}
		}

		$sql = "	
					SELECT UPPER(CorporateName) as name,
					CorporateID as id
					FROM so_resultentry	
					JOIN t_orderdetail ON So_ResultEntryT_OrderDetailID = T_OrderDetailID
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID
					JOIN t_orderheader ON So_ResultEntryT_OrderHeaderID = T_OrderHeaderID
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = So_ResultEntryT_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND T_SamplingSoIsActive = 'Y'
					JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  
					JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_TestID AND Group_ResultDetailIsActive = 'Y'
					JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
					$sql_where
					GROUP BY T_OrderHeaderCorporateID
	
				";
		//echo $sql;
		$companies = $this->db_onedev->query($sql)->result_array();


		//$this->_add_address($rows);
		$result = array("total" => $tot_page, "companies" => $companies, "records" => $rows);
		$this->sys_ok($result);
		exit;
	}

	function search()
	{
		$prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$userid = $this->sys_user["M_UserID"];
		$filter_branch = '';
		$sql = "SELECT *
				FROM m_user 
				WHERE 
				M_UserID = {$userid}";
		$query = $this->db_onedev->query($sql);
		$data_user = $query->row_array();
		if (intval($data_user['M_UserLoginM_BranchID']) > 0) {
			$filter_branch = " AND T_OrderHeaderM_BranchID  = {$data_user['M_UserLoginM_BranchID']}";
		}
		$group_results = array();
		$sql = " SELECT *
			   FROM group_result_entry
			    WHERE
				GroupResultEntryCode = '04' AND GroupResultEntryIsActive = 'Y'";
		$xgresult = $this->db_onedev->query($sql)->result_array();
		if ($xgresult) {
			foreach ($xgresult as $kgr => $vgr) {
				array_push($group_results, $vgr['GroupResultEntryGroup_ResultID']);
			}
		}
		$group_results = join(",", $group_results);

		$search = $prm["search"];
		$status = $prm["stationid"];
		$startdate = $prm["startdate"];
		$enddate = $prm["enddate"];
		$groupid = $prm["groupid"];
		$subgroupid = $prm["subgroupid"];
		$companyid = $prm['companyid'];
		$filter_company = '';
		$filter_company_exclude = "";
		if ($companyid) {
			if (($companyid != 0 || $companyid != '0') && $prm["switch_exclude"])
				$filter_company_exclude = "WHERE company_id <> {$companyid}";
			if (($companyid != 0 || $companyid != '0') && !$prm["switch_exclude"]) {
				$filter_company = " AND T_OrderHeaderCorporateID = {$companyid}";
			}
		}
		$join_group = '';
		if ($groupid != 0) {
			$join_group = "JOIN nat_group ON T_TestNat_GroupID = Nat_GroupID AND Nat_GroupID = {$groupid}";
		}
		$join_subgroup = '';
		if ($subgroupid != 0) {
			$join_group = "JOIN nat_subgroup ON T_TestNat_SubgroupID = Nat_SubgroupID AND Nat_SubgroupID = {$subgroupid}";
		}

		if (!isset($prm['current_page']))
			$prm['current_page'] = 1;

		$sql_where = "WHERE ( ( T_SamplingSoDoneDate BETWEEN '{$startdate} 00:00:00' AND '{$enddate} 23:59:59' ) OR ( T_OrderHeaderDate BETWEEN '{$startdate} 00:00:00' AND '{$enddate} 23:59:59' ) ) AND T_SamplingSoIsActive = 'Y'";
		$number_limit = 10;
		$number_offset = ($prm['current_page'] - 1) * $number_limit;
		//$sql_param = array();
		if ($search != "") {
			$sql_where .= " AND ( T_OrderHeaderLabNumber like '%$search%' OR M_PatientName like '%$search%' ) ";
			// $prm['current_page'] = 1;
		}


		$sql = "SELECT count(*) as total
				FROM (
					SELECT T_SamplingSOID
					FROM t_samplingso
					JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_SamplingSOT_TestID AND Group_ResultDetailIsActive = 'Y'
					JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
					JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID $filter_branch
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  $filter_company
					$sql_where
					GROUP BY T_SamplingSOID
				) x
				$filter_company_exclude
				";
		//echo $sql;

		$query = $this->db_onedev->query($sql);

		$tot_count = 0;
		$tot_page = 0;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
			$tot_page = ceil($tot_count / $number_limit);
		} else {
			$this->sys_error_db("t_samplestorageout count", $this->db_onedev);
			exit;
		}

		$sql = 	"SELECT * FROM (
					SELECT 
					T_OrderHeaderID as trx_id,
					IFNULL(So_ResultEntryID,0) as re_id,
					T_OrderHeaderLabNumber as ordernumber,
					'' as ordernumber_ext,
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname,
					IF(M_PatientGender = 'male','LAKI-LAKI','PEREMPUAN') as sexname,
					IF(M_PatientGender = 'male','L','P') as sexcode,
					DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y') as orderdate,
					DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
					T_OrderHeaderM_PatientAge as age,
					UPPER(T_OrderHeaderM_PatientAge) as umur,
					'' as languange_name,
					T_TestName as test_name,
					Group_ResultName as group_name,
					Group_ResultResumeMcu as group_resume_mcu,
					'' as details,
					CASE
						WHEN So_ResultEntryStatus = 'NEW' THEN 'BARU'
						WHEN So_ResultEntryStatus = 'VAL1'  THEN 'VALIDASI'
						WHEN So_ResultEntryStatus = 'VAL2'  THEN 'VERIFIKASI'
						WHEN So_ResultEntryStatus IS NULL THEN 'NO TEMPLATE'
					END as status_name,
					'' as deliveries,
					'N' as iscito,
					'' as doctor_fullname,
					IFNULL(T_OrderHeaderFoNote,'') as fo_note,
					fn_getstaffname(T_OrderHeaderFoNoteM_UserID) as fo_note_user,
					'' as fo_ver_note, 
					'' as fo_ver_note_user,
					'' as sampling_note,
					'' as sampling_note_user,
					UPPER(CorporateName) as company_name,
					CorporateID as company_id,
					T_SamplingSOID,
					IFNULL(So_SignatureUrl,'') as image_signature,
					T_OrderHeaderID,
					T_TestID
				FROM t_samplingso
				JOIN t_orderdetail ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND 
				T_OrderDetailT_TestID = T_SamplingSoT_TestID AND T_OrderDEtailIsActive = 'Y'
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID
				JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_SamplingSoT_TestID AND Group_ResultDetailIsActive = 'Y'
				JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
				JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID $filter_branch
				JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  $filter_company
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				LEFT JOIN m_title ON M_PatientM_TitleID = M_TitleID
				LEFT JOIN so_resultentry ON T_OrderDetailID =  So_ResultEntryT_OrderDetailID AND So_ResultEntryIsActive = 'Y'
				
				LEFT JOIN so_signature ON So_SignatureT_OrderHeaderID = T_OrderHeaderID AND So_SignatureIsActive = 'Y'
				$sql_where
				GROUP BY T_OrderHeaderID, T_TestID
				ORDER BY T_OrderHeaderID ASC
				limit $number_limit offset $number_offset) x
				$filter_company_exclude";
		//echo $sql;

		$query = $this->db_onedev->query($sql);
		$rows = $query->result_array();
		//echo $this->db_onedev->last_query();


		$sql = "	
					SELECT UPPER(CorporateName) as name,
					CorporateID as id
					FROM so_resultentry	
					JOIN t_orderdetail ON So_ResultEntryT_OrderDetailID = T_OrderDetailID
					JOIN t_test ON T_OrderDetailT_TestID = T_TestID
					JOIN t_orderheader ON So_ResultEntryT_OrderHeaderID = T_OrderHeaderID
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = So_ResultEntryT_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND T_SamplingSoIsActive = 'Y'
					JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  
					JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_TestID AND Group_ResultDetailIsActive = 'Y'
					JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
					$sql_where
					GROUP BY T_OrderHeaderCorporateID
	
				";
		//echo $sql;
		$companies = $this->db_onedev->query($sql)->result_array();


		//$this->_add_address($rows);
		$result = array("total" => $tot_page, "companies" => $companies, "records" => $rows);
		$this->sys_ok($result);
		exit;
	}

	function get_details()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$group_results = array();
		$sql = " SELECT *
			   FROM group_result_entry
			    WHERE
				GroupResultEntryCode = '04' AND GroupResultEntryIsActive = 'Y'";
		$xgresult = $this->db_onedev->query($sql)->result_array();
		if ($xgresult) {
			foreach ($xgresult as $kgr => $vgr) {
				array_push($group_results, $vgr['GroupResultEntryGroup_ResultID']);
			}
		}
		$group_results = join(",", $group_results);
		$v = $prm;
		$arr_status = array();
		//echo $v['re_id'] ;
		if (intval($v['re_id']) == 0) {
			//echo 'IN';
			$insert_so = $this->nonlabtemplate->generate($v['T_SamplingSOID']);
			//print_r($insert_so);
			if ($insert_so) {
				$sql = "SELECT IFNULL(M_DoctorID,0) as M_DoctorID
							FROM m_staff
							JOIN m_user ON M_UserM_StaffID = M_StaffID AND M_UserID = {$userid}
							LEFT JOIN m_doctor ON M_StaffM_DoctorID = M_DoctorID
							WHERE
							M_StaffIsActive = 'Y'
							LIMIT 1";
				$row_doctor_staff = $this->db_onedev->query($sql)->row_array();

				if (intval($row_doctor_staff['M_DoctorID']) > 0) {
					$sql = "UPDATE so_resultentry SET So_ResultEntryM_DoctorID = {$row_doctor_staff['M_DoctorID']} 
								WHERE So_ResultEntryID = {$v['re_id']}
								";
					$this->db_onedev->query($sql);
				}
				$v['re_id'] = $insert_so['So_ResultEntryID'];
				//$v['re_id'] = $v['re_id'];
				$v['status_name'] = 'BARU';
				//$rows[$k]['status_name'] = $v['status_name'];
			}
		}

		//if ($v['image_signature'] != '') {
		//	$rows[$k]['image_signature'] = $v['image_signature'] . "?=" . date("YmdHis");
		//}
		$sql = 	"SELECT 
				IFNULL(So_ResultEntryID,0) as trx_id,
				IFNULL(So_ResultEntryID,0) as re_id, 
				T_SamplingSoT_OrderHeaderID as orderid,
				T_TestT_SampleTypeID as sampletypeid,
				T_SamplingSoID,
				UPPER(T_TestName) as test_name,
				Group_ResultName as group_name,
				Group_ResultResumeMcu as group_resume_mcu,
				T_TestID as test_id,
				T_TestNat_TestID as nat_testid,
				0 as language_id,
				So_ResultEntryNonlab_TemplateID as template_id,
				So_ResultEntryNonlab_TemplateName as template_name,
				NonlabTemplateFlagOther as template_flag_other,
				'' as status_result,
				'' as status_result_arr,
				CASE
					WHEN So_ResultEntryStatus = 'NEW' THEN 'BARU'
					WHEN So_ResultEntryStatus = 'VAL1'  THEN 'VALIDASI 1'
					WHEN So_ResultEntryStatus = 'VAL2'  THEN 'VALIDASI 2'
					WHEN So_ResultEntryStatus IS NULL THEN 'NO TEMPLATE'
				END as status_name,
				'' as note,
				So_ResultEntryStatus as status,
				'Bahasa Indonesia' as language_name,
				'' as doctors,
				IFNULL(M_DoctorID,0) as doctor_id,
				IF(ISNULL(M_DoctorID),'-',CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,M_DoctorSuffix,M_DoctorSuffix)) as doctor_fullname,
				'' as details,
				'' as langs,
				'' as photos
			FROM t_samplingso
			JOIN t_test ON T_SamplingSoT_TestID = T_TestID AND T_SamplingSoID = {$v['T_SamplingSOID']} AND T_SamplingSoIsActive = 'Y' 
			JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_TestID AND Group_ResultDetailIsActive = 'Y'
			JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
			LEFT JOIN so_resultentry ON T_SamplingSoT_OrderHeaderID = So_ResultEntryT_OrderHeaderID AND So_ResultEntryID = {$v['re_id']} 
			LEFT JOIN nonlab_template ON So_ResultEntryNonlab_TemplateID = NonlabTemplateID AND NonlabTemplateIsActive = 'Y'
			LEFT JOIN m_doctor ON So_ResultEntryM_DoctorID = M_DoctorID 
			GROUP BY orderid, test_id";
		//echo $sql;
		//print_r($v);
		$rst_details = $this->db_onedev->query($sql)->result_array();
		foreach ($rst_details as $ki => $vi) {
			array_push($arr_status, $vi['status_header']);
			$xstatus_result = array();
			$sql = "SELECT NonlabConclusionDetailID as id, NonlabConclusionDetailName as name, NonlabConclusionDetailIsNormal AS isNormal
						FROM so_resultentry_category_result
						JOIN nonlab_conclusion_detail ON So_ResultEntryCategoryNonlabConclusionDetailID = NonlabConclusionDetailID AND 
						NonlabConclusionDetailIsActive = 'Y'
						WHERE
						So_ResultEntryCategoryResultSo_ResultEntryID = {$vi['trx_id']} AND 
						So_ResultEntryCategoryResultIsActive = 'Y'
				";
			$get_status_result = $this->db_onedev->query($sql)->result_array();
			if ($get_status_result) {
				$xstatus_result = $get_status_result;
			}

			$rst_details[$ki]['status_result'] = $xstatus_result;

			$sql = "SELECT NonlabConclusionDetailID AS id, NonlabConclusionDetailName as name, NonlabConclusionDetailIsNormal AS isNormal
						FROM nonlab_conclusion_detail d1
						JOIN (SELECT * FROM nonlab_conclusion WHERE NonlabConclusionIsActive = 'Y' LIMIT 1) d2
						ON d1.NonlabConclusionDetailNonlabConclusionID = d2.NonlabConclusionID";
			$data_status_result_array = $this->db_onedev->query($sql)->result_array();
			$sql = "SELECT NonlabConclusionDetailID AS id , NonlabConclusionDetailName as name, NonlabConclusionDetailIsNormal AS isNormal
						FROM nonlab_conclusion_mapping
						JOIN nonlab_conclusion_detail ON NonlabConclusionMappingNonlabConclusionID = NonlabConclusionDetailNonlabConclusionID AND NonlabConclusionDetailIsActive ='Y'
						JOIN nonlab_conclusion ON NonlabConclusionMappingNonlabConclusionID  = NonlabConclusionID AND NonlabConclusionIsActive = 'Y'
						WHERE NonlabConclusionMappingNat_TestID = {$vi['nat_testid']}";
			$data_status_result_array_other = $this->db_onedev->query($sql)->result_array();
			if ($data_status_result_array_other) {
				$data_status_result_array = $data_status_result_array_other;
			}

			$rst_details[$ki]['status_result_arr'] = $data_status_result_array;
			$rst_details[$ki]['status_result'] = $xstatus_result;

			$sql = 	"SELECT 
							So_ResultEntryDetailID as trx_id,
							So_ResultEntryDetailNonlab_TemplateDetailID as template_detail_id,
							So_ResultEntryDetailNonlab_TemplateDetailName as result_label,
							So_ResultEntryDetailResult as result_value,
							'N' as flag_print,
							So_ResultEntryDetailResult as result_value_before
						FROM so_resultentrydetail
						
						WHERE
						So_ResultEntryDetailSo_ResultEntryID = {$vi['trx_id']} AND So_ResultEntryDetailisActive = 'Y'";
			//echo $sql;
			$rst_details[$ki]['details'] = $this->db_onedev->query($sql)->result_array();
			//$rst_details[$ki]['langs'] = $this->getlangs($vi['orderid']);
			$rst_details[$ki]['photos'] = $this->getphotos($vi['orderid'], $vi['sampletypeid']);
			//$rst_details[$ki]['doctors'] = $this->getdoctors($vi['sampletypeid']);
		}
		//$rows[$k]['details'] = $rst_details;
		//$rows[$k]['deliveries'] = $this->getdeliveries($v['trx_id'],$v['re_id']);

		$result = array("records" => $rst_details);
		$this->sys_ok($result);
		exit;
	}

	function search_bynolab()
	{
		$prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$search = $prm["nolab"];
		$type = $prm["type"];

		$userid = $this->sys_user["M_UserID"];

		$group_results = array();
		$sql = "SELECT *
			   FROM group_result_entry
			   JOIN group_result ON GroupResultEntryGroup_ResultID = Group_ResultID AND Group_ResultResumeMcu = '{$type}'
			    WHERE
				GroupResultEntryCode = '04' AND GroupResultEntryIsActive = 'Y'";
		$xgresult = $this->db_onedev->query($sql)->result_array();
		if ($xgresult) {
			foreach ($xgresult as $kgr => $vgr) {
				array_push($group_results, $vgr['GroupResultEntryGroup_ResultID']);
			}
		}

		$group_results = join(",", $group_results);

		$sql = 	"   SELECT 
					T_OrderHeaderID as trx_id,
					IFNULL(So_ResultEntryID,0) as re_id,
					T_OrderHeaderLabNumber as ordernumber,
					'' as ordernumber_ext,
					CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname,
					IF(M_PatientGender = 'male','LAKI-LAKI','PEREMPUAN') as sexname,
					IF(M_PatientGender = 'male','L','P') as sexcode,
					DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y') as orderdate,
					DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
					T_OrderHeaderM_PatientAge as age,
					UPPER(T_OrderHeaderM_PatientAge) as umur,
					'' as languange_name,
					T_TestName as test_name,
					Group_ResultName as group_name,
					Group_ResultResumeMcu as group_resume_mcu,
					'' as details,
					CASE
						WHEN So_ResultEntryStatus = 'NEW' THEN 'BARU'
						WHEN So_ResultEntryStatus = 'VAL1'  THEN 'VALIDASI'
						WHEN So_ResultEntryStatus = 'VAL2'  THEN 'VERIFIKASI'
						WHEN So_ResultEntryStatus IS NULL THEN 'NO TEMPLATE'
					END as status_name,
					'' as deliveries,
					'N' as iscito,
					'' as doctor_fullname,
					IFNULL(T_OrderHeaderFoNote,'') as fo_note,
					fn_getstaffname(T_OrderHeaderFoNoteM_UserID) as fo_note_user,
					'' as fo_ver_note, 
					'' as fo_ver_note_user,
					'' as sampling_note,
					'' as sampling_note_user,
					UPPER(CorporateName) as company_name,
					CorporateID as company_id,
					T_SamplingSOID,
					IFNULL(So_SignatureUrl,'') as image_signature
				FROM t_samplingso
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID
				JOIN t_orderheader ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderLabNumber = '{$search}'
				JOIN t_orderdetail ON T_SamplingSoT_OrderHeaderID = T_OrderDetailT_OrderHeaderID AND T_OrderDetailT_TestID = T_SamplingSoT_TestID AND T_OrderDetailIsActive = 'Y'
				LEFT JOIN so_resultentry ON T_OrderDetailID =  So_ResultEntryT_OrderDetailID AND So_ResultEntryIsActive = 'Y'
				JOIN corporate ON T_OrderHeaderCorporateID = CorporateID  $filter_company
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				LEFT JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_TestID AND Group_ResultDetailIsActive = 'Y'
				JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
				LEFT JOIN so_signature ON So_SignatureT_OrderHeaderID = T_OrderHeaderID AND So_SignatureIsActive = 'Y'
				WHERE
				T_SamplingSoIsActive = 'Y' 
				GROUP BY T_SamplingSOID
				ORDER BY T_SamplingSOID ASC";
		//echo $sql;

		$query = $this->db_onedev->query($sql);
		$rows = $query->result_array();
		//echo $this->db_onedev->last_query();

		if ($rows) {
			foreach ($rows as $k => $v) {

				//echo $v['re_id'] ;
				if (intval($v['re_id']) == 0) {
					//echo 'IN';
					$insert_so = $this->nonlabtemplate->generate($v['T_SamplingSOID']);
					//print_r($insert_so);
					if ($insert_so) {
						$v['re_id'] = $insert_so['So_ResultEntryID'];

						$rows[$k]['re_id'] = $v['re_id'];
						$v['status_name'] = 'BARU';
						$rows[$k]['status_name'] = $v['status_name'];
					}
				}

				$sql = "SELECT IFNULL(M_DoctorID,0) as M_DoctorID
						FROM m_staff
						JOIN m_user ON M_UserM_StaffID = M_StaffID AND M_UserID = {$userid}
						LEFT JOIN m_doctor ON M_StaffM_DoctorID = M_DoctorID
						WHERE
						M_StaffIsActive = 'Y'
						LIMIT 1";
				$row_doctor_staff = $this->db_onedev->query($sql)->row_array();
				//echo $sql;

				if (intval($row_doctor_staff['M_DoctorID']) > 0) {
					$sql = "UPDATE so_resultentry SET So_ResultEntryM_DoctorID = {$row_doctor_staff['M_DoctorID']} 
							WHERE So_ResultEntryID = {$v['re_id']} AND So_ResultEntryM_DoctorID = '0'
							";
					$this->db_onedev->query($sql);
					//echo $sql;
				}

				if ($v['image_signature'] != '') {
					$rows[$k]['image_signature'] = $v['image_signature'] . "?=" . date("YmdHis");
				}
				$sql = 	"SELECT 
					IFNULL(So_ResultEntryID,0) as trx_id,
					IFNULL(So_ResultEntryID,0) as re_id, 
					T_SamplingSoT_OrderHeaderID as orderid,
					T_TestT_SampleTypeID as sampletypeid,
					T_SamplingSoID,
					UPPER(T_TestName) as test_name,
					Group_ResultName as group_name,
					Group_ResultResumeMcu as group_resume_mcu,
					T_TestID as test_id,
					0 as language_id,
					So_ResultEntryNonlab_TemplateID as template_id,
					So_ResultEntryNonlab_TemplateName as template_name,
					NonlabTemplateFlagOther as template_flag_other,
					CASE
						WHEN So_ResultEntryStatus = 'NEW' THEN 'BARU'
						WHEN So_ResultEntryStatus = 'VAL1'  THEN 'VALIDASI 1'
						WHEN So_ResultEntryStatus = 'VAL2'  THEN 'VALIDASI 2'
						WHEN So_ResultEntryStatus IS NULL THEN 'NO TEMPLATE'
					END as status_name,
					'' as note,
					So_ResultEntryStatus as status,
					'Bahasa Indonesia' as language_name,
					'' as doctors,
					IFNULL(M_DoctorID,0) as doctor_id,
					IF(ISNULL(M_DoctorID),'-',CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,M_DoctorSuffix,M_DoctorSuffix)) as doctor_fullname,
					'' as details,
					'' as langs,
					'' as photos
				FROM t_samplingso
				LEFT JOIN so_resultentry ON T_SamplingSoT_OrderHeaderID = So_ResultEntryT_OrderHeaderID AND So_ResultEntryID = {$v['re_id']} 
				LEFT JOIN nonlab_template ON So_ResultEntryNonlab_TemplateID = NonlabTemplateID AND NonlabTemplateIsActive = 'Y'
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID
				LEFT JOIN m_doctor ON So_ResultEntryM_DoctorID = M_DoctorID 
				JOIN group_resultdetail ON Group_ResultDetailT_TestID = T_TestID AND Group_ResultDetailIsActive = 'Y'
				JOIN group_result ON Group_ResultDetailGroup_ResultID = Group_ResultID AND Group_ResultID IN ({$group_results})
				WHERE
				T_SamplingSoID = {$v['T_SamplingSOID']} AND T_SamplingSoIsActive = 'Y' 
				GROUP BY T_SamplingSOID";
				$rst_details = $this->db_onedev->query($sql)->result_array();
				foreach ($rst_details as $ki => $vi) {
					$sql = 	"SELECT 
								So_ResultEntryDetailID as trx_id,
								So_ResultEntryDetailNonlab_TemplateDetailID as template_detail_id,
								So_ResultEntryDetailNonlab_TemplateDetailName as result_label,
								So_ResultEntryDetailResult as result_value,
								'N' as flag_print,
								So_ResultEntryDetailResult as result_value_before
							FROM so_resultentrydetail
							
							WHERE
							So_ResultEntryDetailSo_ResultEntryID = {$vi['trx_id']} AND So_ResultEntryDetailisActive = 'Y'";
					//echo $sql;
					$rst_details[$ki]['details'] = $this->db_onedev->query($sql)->result_array();
					$rst_details[$ki]['photos'] = $this->getphotos($vi['orderid'], $vi['sampletypeid']);
				}
				$rows[$k]['details'] = $rst_details;
			}
		}

		$sql = "SELECT * FROM `s_menu` WHERE `S_MenuName` = 'Specimen Collection Mobile'";
		$query = $this->db_onedev->query($sql)->row();
		$row_url_sample = $query->S_MenuUrl;

		$result = array("records" => $rows, "next_url" => $row_url_sample);
		$this->sys_ok($result);
		exit;
	}

	function getlangs($orderid)
	{
		$sql = "
					SELECT M_LangID as id, M_LangCode as code, M_LangName as name, 'N' as chex
					FROM t_orderheader
					JOIN m_lang ON T_OrderHeaderM_LangID = M_LangID
					WHERE
					T_OrderHeaderID = {$orderid}
					UNION
					SELECT M_LangID as id, M_LangCode as code, M_LangName as name, 'N' as chex
					FROM t_orderheader
					JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID
					JOIN m_lang ON T_OrderHeaderAddOnSecondM_LangID = M_LangID
					WHERE
					T_OrderHeaderID = {$orderid}
		";
		$rst = $this->db_onedev->query($sql)->result_array();
		return $rst;
	}

	function getordersamples()
	{
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
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function get6mwt()
	{
		$prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rst = array();
		$prm = $this->sys_input;
		$sql = "SELECT * 
				FROM so_resultentry_smwt
				WHERE
					So_ResultentrySmwtSo_ResultentryID = {$prm['re_id']} AND So_ResultentrySmwtIsActive = 'Y'";
		//echo $sql;
		$rows = $this->db_onedev->query($sql)->row_array();
		if ($rows) {
			$sql = "SELECT So_ResultentrySmwtDetailsID as id,
						So_ResultentrySmwtDetailsWaktu as waktu,
						So_ResultentrySmwtDetailsSPO2 as spo2,
						So_ResultentrySmwtDetailsNadi as nadi
					FROM so_resultentry_smwt_details
					WHERE
						So_ResultentrySmwtDetailsSo_ResultentrySmwtID = {$rows['So_ResultentrySmwtID']} AND
						So_ResultentrySmwtDetailsIsActive = 'Y'";
			$details_6mwt =  $this->db_onedev->query($sql)->result_array();
			$rst = array(
				'id' => $rows['So_ResultentrySmwtID'],
				'bb' => $rows['So_ResultentrySmwtWeight'],
				'tb' => $rows['So_ResultentrySmwtHeight'],
				'bmi' => $rows['So_ResultentrySmwtBMI'],
				'distance' => $rows['So_ResultentrySmwtJarakPutaran'],
				'rounds' => $rows['So_ResultentrySmwtJumlahPutaran'],
				'pretest' => array(
					'tensi' => $rows['So_ResultentrySmwtPreTensi'],
					'spo2' => $rows['So_ResultentrySmwtPreSPO2'],
					'nadi' => $rows['So_ResultentrySmwtPreNadi'],
					'dyspnea' => $rows['So_ResultentrySmwtPreDyspnea'],
					'fatigue' => $rows['So_ResultentrySmwtPreFatigue']
				),
				'posttest' => array(
					'tensi' => $rows['So_ResultentrySmwtPostTensi'],
					'spo2' => $rows['So_ResultentrySmwtPostSPO2'],
					'nadi' => $rows['So_ResultentrySmwtPostNadi'],
					'dyspnea' => $rows['So_ResultentrySmwtPostDyspnea'],
					'fatigue' => $rows['So_ResultentrySmwtPostFatigue']
				),
				'details_6mwt' => $details_6mwt
			);
		}
		if (!$rows) {
			$rst = array(
				'id' => '0',
				'bb' => '',
				'tb' => '',
				'bmi' => '',
				'distance' => '',
				'rounds' => '',
				'pretest' => array('tensi' => '', 'spo2' => '', 'nadi' => '', 'dyspnea' => '', 'fatigue' => ''),
				'posttest' => array('tensi' => '', 'spo2' => '', 'nadi' => '', 'dyspnea' => '', 'fatigue' => ''),
				'details_6mwt' => array(
					array(
						'id' => '0',
						'waktu' => 'MENIT KE 1',
						'spo2' => '',
						'nadi' => ''
					),
					array(
						'id' => '0',
						'waktu' => 'MENIT KE 2',
						'spo2' => '',
						'nadi' => ''
					),
					array(
						'id' => '0',
						'waktu' => 'MENIT KE 3',
						'spo2' => '',
						'nadi' => ''
					),
					array(
						'id' => '0',
						'waktu' => 'MENIT KE 4',
						'spo2' => '',
						'nadi' => ''
					),
					array(
						'id' => '0',
						'waktu' => 'MENIT KE 5',
						'spo2' => '',
						'nadi' => ''
					),
					array(
						'id' => '0',
						'waktu' => 'MENIT KE 6',
						'spo2' => '',
						'nadi' => ''
					)
				)
			);
		}
		$result = array(
			"total" => count($rst),
			"records" => $rst,
		);
		$this->sys_ok($result);
		exit;
	}



	function save6mwt()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];

		$trx = $prm['trx'];
		$data_6mwt = $prm['data_6mwt'];
		$pretest = $data_6mwt['pretest'];
		$posttest = $data_6mwt['posttest'];
		$details_6mwt = $data_6mwt['details_6mwt'];
		$prm['vomax'] = str_replace('VO MAX', 'VO2 MAX', $prm['vomax']);
		if ($prm['action'] === 'unval1') {
			$sql = "SELECT IFNULL(Mcu_ResumeValidation, 'N') as status, COUNT(Mcu_ResumeID)
			FROM mcu_resume
			WHERE Mcu_ResumeT_OrderHeaderID = {$prm['trx']['orderid']}
			AND Mcu_ResumeIsActive = 'Y'";
			$qry = $this->db_onedev->query($sql);
			if (!$qry) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$this->sys_error("Error cek resume individu");
				exit;
			}
			$cek = $qry->row_array();
			if ($cek['status'] == 'Y') {
				$this->sys_error("Resume individu sudah di validasi, unvalidasi resume individu terlebih dahulu ....");
				exit;
			}
		}
		if ($prm['action'] === 'unval1') {
			$sql = "SELECT IFNULL(Mcu_ResumeValidation, 'N') as status, COUNT(Mcu_ResumeID)
			FROM mcu_resume
			WHERE Mcu_ResumeT_OrderHeaderID = {$prm['trx']['orderid']}
			AND Mcu_ResumeIsActive = 'Y'";
			$qry = $this->db_onedev->query($sql);
			if (!$qry) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$this->sys_error("Error cek resume individu");
				exit;
			}
			$cek = $qry->row_array();
			if ($cek['status'] == 'Y') {
				$this->sys_error("Resume individu sudah di validasi, unvalidasi resume individu terlebih dahulu ....");
				exit;
			}
		}
		$sql = "SELECT * 
			 FROM so_resultentry
			 WHERE
				 So_ResultEntryID = {$trx['re_id']} 
			 LIMIT 1";
		//echo $sql;
		$data_re = $this->db_onedev->query($sql)->row_array();
		if (intval($data_6mwt['id']) == 0) {
			$sql = "SELECT count(*) as cnt 
				 FROM so_resultentry_smwt 
				 WHERE 
					 So_ResultentrySmwtSo_ResultentryID = {$trx['re_id']} AND
					 So_ResultentrySmwtM_LangID = {$trx['language_id']} AND
					 So_ResultentrySmwtIsActive = 'Y'
					 ";

			$dt = $this->db_onedev->query($sql)->row_array();
			if ($dt['cnt'] == 0) {
				$data_insert = array(
					'So_ResultentrySmwtSo_ResultentryID' => $trx['re_id'],
					'So_ResultentrySmwtM_LangID' => $trx['language_id'],
					'So_ResultentrySmwtWeight' => $data_6mwt['bb'],
					'So_ResultentrySmwtHeight' => $data_6mwt['tb'],
					'So_ResultentrySmwtBMI' => $data_6mwt['bmi'],
					'So_ResultentrySmwtJarakPutaran' => $data_6mwt['distance'],
					'So_ResultentrySmwtJumlahPutaran' => $data_6mwt['rounds'],
					'So_ResultentrySmwtPreTensi' => $pretest['tensi'],
					'So_ResultentrySmwtPreSPO2' => $pretest['spo2'],
					'So_ResultentrySmwtPreNadi' => $pretest['nadi'],
					'So_ResultentrySmwtPreDyspnea' => $pretest['dyspnea'],
					'So_ResultentrySmwtPreFatigue' => $pretest['fatigue'],
					'So_ResultentrySmwtPostTensi' => $posttest['tensi'],
					'So_ResultentrySmwtPostSPO2' => $posttest['spo2'],
					'So_ResultentrySmwtPostNadi' => $posttest['nadi'],
					'So_ResultentrySmwtPostDyspnea' => $posttest['dyspnea'],
					'So_ResultentrySmwtPostFatigue' => $posttest['fatigue'],
					'So_ResultentrySmwtVOMax' => $prm['vomax'],
					'So_ResultentrySmwtKategoriKebugaran' => $prm['category'],
					'So_ResultentrySmwtUserID' => $userid
				);
				$this->db_onedev->insert('so_resultentry_smwt', $data_insert);
				$last_id = $this->db_onedev->insert_id();
			}

			//echo $last_id;
		} else {
			$idx_other = 0;
			$sql = "SELECT So_ResultentrySmwtID as idx
				 FROM so_resultentry_smwt 
				 WHERE 
					 So_ResultentrySmwtSo_ResultentryID = {$trx['re_id']} AND
					 So_ResultentrySmwtM_LangID = {$trx['language_id']} AND
					 So_ResultentrySmwtIsActive = 'Y' AND
					 So_ResultentrySmwtID <> {$data_6mwt['id']}
					 ";

			$data_other = $this->db_onedev->query($sql)->row_array();
			if ($data_other) {
				$idx_other = intval($data_other['idx']);
			}

			if ($idx_other > 0) {
				$sql = "UPDATE so_resultentry_smwt SET So_ResultentrySmwtIsActive = 'N'
					 WHERE So_ResultentrySmwtID = $idx_other";
				$this->db_onedev->query($sql);
			}

			$data_update = array(
				'So_ResultentrySmwtSo_ResultentryID' => $trx['re_id'],
				'So_ResultentrySmwtM_LangID' => $trx['language_id'],
				'So_ResultentrySmwtWeight' => $data_6mwt['bb'],
				'So_ResultentrySmwtHeight' => $data_6mwt['tb'],
				'So_ResultentrySmwtBMI' => $data_6mwt['bmi'],
				'So_ResultentrySmwtJarakPutaran' => $data_6mwt['distance'],
				'So_ResultentrySmwtJumlahPutaran' => $data_6mwt['rounds'],
				'So_ResultentrySmwtPreTensi' => $pretest['tensi'],
				'So_ResultentrySmwtPreSPO2' => $pretest['spo2'],
				'So_ResultentrySmwtPreNadi' => $pretest['nadi'],
				'So_ResultentrySmwtPreDyspnea' => $pretest['dyspnea'],
				'So_ResultentrySmwtPreFatigue' => $pretest['fatigue'],
				'So_ResultentrySmwtPostTensi' => $posttest['tensi'],
				'So_ResultentrySmwtPostSPO2' => $posttest['spo2'],
				'So_ResultentrySmwtPostNadi' => $posttest['nadi'],
				'So_ResultentrySmwtPostDyspnea' => $posttest['dyspnea'],
				'So_ResultentrySmwtPostFatigue' => $posttest['fatigue'],
				'So_ResultentrySmwtVOMax' => $prm['vomax'],
				'So_ResultentrySmwtKategoriKebugaran' => $prm['category'],
				'So_ResultentrySmwtLastUpdated' =>  date('Y-m-d H:i:s'),
				'So_ResultentrySmwtUserID' => $userid
			);
			$this->db_onedev->where('So_ResultentrySmwtID', $data_6mwt['id']);
			$this->db_onedev->update('so_resultentry_smwt', $data_update);
			$last_id = $data_6mwt['id'];
		}

		if ($idx_other > 0) {
			$sql = "UPDATE so_resultentry_smwt_details SET So_ResultentrySmwtDetailsIsActive = 'N'
				 WHERE So_ResultentrySmwtDetailsSo_ResultentrySmwtID = $idx_other";
			$this->db_onedev->query($sql);
		}

		if ($details_6mwt) {
			$sql = "UPDATE so_resultentry_smwt_details SET So_ResultentrySmwtDetailsIsActive = 'N'
						WHERE So_ResultentrySmwtDetailsSo_ResultentrySmwtID = $last_id";
			$this->db_onedev->query($sql);
			foreach ($details_6mwt as $k => $v) {
				$detail_insert = array(
					'So_ResultentrySmwtDetailsSo_ResultentrySmwtID' => $last_id,
					'So_ResultentrySmwtDetailsWaktu' => $v['waktu'],
					'So_ResultentrySmwtDetailsSPO2' => $v['spo2'],
					'So_ResultentrySmwtDetailsNadi' => $v['nadi'],
					'So_ResultentrySmwtDetailsUserID' => $userid
				);
				$this->db_onedev->insert('so_resultentry_smwt_details', $detail_insert);
				/*if(intval($v['id']) == 0){
				 $detail_insert = array(
					 'So_ResultentrySmwtDetailsSo_ResultentrySmwtID' => $last_id,
					 'So_ResultentrySmwtDetailsWaktu' => $v['waktu'],
					 'So_ResultentrySmwtDetailsSPO2' => $v['spo2'],
					 'So_ResultentrySmwtDetailsNadi' => $v['nadi'],
					 'So_ResultentrySmwtDetailsUserID' => $userid
				 );
				 $this->db_onedev->insert('so_resultentry_smwt_details', $detail_insert);
			 }
			 else{
				 $detail_update = array(
					 'So_ResultentrySmwtDetailsSo_ResultentrySmwtID' => $last_id,
					 'So_ResultentrySmwtDetailsWaktu' => $v['waktu'],
					 'So_ResultentrySmwtDetailsSPO2' => $v['spo2'],
					 'So_ResultentrySmwtDetailsNadi' => $v['nadi'],
					 'So_ResultentrySmwtDetailsLastUpdated'=>  date('Y-m-d H:i:s'),
					 'So_ResultentrySmwtDetailsUserID' => $userid
				 );
				 $this->db_onedev->where('So_ResultentrySmwtDetailsID', $v['id']);
				 $this->db_onedev->update('so_resultentry_smwt_details', $detail_update);
			 }*/
			}
		}


		if ($prm['action'] === 'val1' && $data_re['So_ResultEntryValidation1'] == 'N' && $data_re['So_ResultEntryValidation2'] == 'N') {
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation1 = 'Y', So_ResultEntryStatus = 'VAL1', So_ResultEntryUserID = {$userid} WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
			//echo $this->db_onedev->last_query();
		}

		if ($prm['action'] === 'unval1' && $data_re['So_ResultEntryValidation1'] == 'Y' && $data_re['So_ResultEntryValidation2'] == 'N') {
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation1 = 'N', So_ResultEntryStatus = 'NEW', So_ResultEntryUserID = {$userid} WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
		}

		$result = array(
			"total" => 1,
			"records" => array('status' => 'OK')
		);
		$this->sys_ok($result);
		exit;
	}

	function savetypesds()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}


		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$type = '30';
		if ($prm['type'] == 'SRQ20') {
			$type = '20';
		}

		$sql = "INSERT INTO so_resultentrysds_type (
		           	So_ResultEntrySDSTypeSo_ResultEntryID,
					So_ResultEntrySDSTypeValue,
					So_ResultEntrySDSTypeCreated,
					So_ResultEntrySDSTypeCreatedUserID
				) VALUES ({$prm['reid']},'{$type}',NOW(),{$userid}) 
				ON DUPLICATE KEY UPDATE 
					So_ResultEntrySDSTypeValue = VALUES(So_ResultEntrySDSTypeValue), 
					So_ResultEntrySDSTypeLastUpdated = NOW(),
					So_ResultEntrySDSTypeLastUpdatedUserID = VALUES(So_ResultEntrySDSTypeCreatedUserID)
				";
		$query = $this->db_onedev->query($sql);

		if (!$query) {
			$this->sys_error_db("error save sds type");
			// echo $this->db_onedev->last_query();
			exit;
		}

		// $sql = "UPDATE ";

		$result = array(
			"total" => 1,
			"records" => array('status' => 'OK')
		);
		$this->sys_ok($result);
		exit;
	}

	function saveSDS()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];

		$trx = $prm['trx'];
		$data_sds = $prm['data_sds'];
		$identitas = $data_sds['identitas'];
		$sds30 = $data_sds['sds30'];
		$srq20 = $data_sds['srq20'];
		$prm_type = isset($prm['type']) ? $prm['type'] : 'SDS30';
		$type = '30';
		if ($prm_type == 'SRQ20') {
			$type = '20';
		}



		$sql = "INSERT INTO so_resultentrysds_type (
				So_ResultEntrySDSTypeSo_ResultEntryID,
					So_ResultEntrySDSTypeValue,
					So_ResultEntrySDSTypeCreated,
					So_ResultEntrySDSTypeCreatedUserID
				) VALUES ({$trx['re_id']},'{$type}',NOW(),{$userid}) 
				ON DUPLICATE KEY UPDATE 
					So_ResultEntrySDSTypeValue = VALUES(So_ResultEntrySDSTypeValue), 
					So_ResultEntrySDSTypeLastUpdated = NOW(),
					So_ResultEntrySDSTypeLastUpdatedUserID = VALUES(So_ResultEntrySDSTypeCreatedUserID)
			";
		$query = $this->db_onedev->query($sql);


		//cek sudah ada tipe belum
		$sql = "SELECT * FROM so_resultentrysds_type
				WHERE So_ResultEntrySDSTypeSo_ResultEntryID = ?";
		$qry = $this->db_onedev->query($sql, [$trx['re_id']]);
		if (!$qry) {
			$message = $this->db_onedev->error();
			$message['qry'] = $this->db_onedev->last_query();
			$this->sys_error("Error cek type");
			exit;
		}
		$cekType = $qry->result_array();

		$typeForm = $cekType[0]['So_ResultEntrySDSTypeValue'];

		if ($typeForm == '30') {
			$srq20 = [
				"questions" => [],
				"interpretation" => []
			];
		}
		if ($typeForm == '20') {
			$sds30 = [
				"questions" => [],
				"interpretation" => []
			];
		}

		if ($prm['action'] === 'unval1') {
			$sql = "SELECT IFNULL(Mcu_ResumeValidation, 'N') as status, COUNT(Mcu_ResumeID)
			FROM mcu_resume
			WHERE Mcu_ResumeT_OrderHeaderID = {$prm['trx']['orderid']}
			AND Mcu_ResumeIsActive = 'Y'";
			$qry = $this->db_onedev->query($sql);
			if (!$qry) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$this->sys_error("Error cek resume individu");
				exit;
			}
			$cek = $qry->row_array();
			if ($cek['status'] == 'Y') {
				$this->sys_error("Resume individu sudah di validasi, unvalidasi resume individu terlebih dahulu ....");
				exit;
			}
		}
		$sql = "SELECT * 
			 FROM so_resultentry
			 WHERE
				 So_ResultEntryID = {$trx['re_id']} 
			 LIMIT 1";
		//echo $sql;
		$data_re = $this->db_onedev->query($sql)->row_array();

		// print_r($data_re);

		//identitas
		//cek identitas
		$sql = "SELECT 
				So_ResultEntrySDSIdentityID
				FROM so_resultentrysdsidentity WHERE So_ResultEntrySDSIdentitySo_ResultEntryID = {$trx['re_id']}
				AND So_ResultEntrySDSIdentityIsActive = 'Y'";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error cek identitas");
			exit;
		}
		$cekIdentitas = $query->result_array();
		$dataIdentitas =  array(
			"So_ResultEntrySDSIdentitySo_ResultEntryID" => $trx['re_id'],
			"So_ResultEntrySDSIdentityName" => $identitas['nama'],
			"So_ResultEntrySDSIdentityAge" => $identitas['usia'],
			"So_ResultEntrySDSIdentityGender" => $identitas['jenis_kelamin'],
			"So_ResultEntrySDSIdentityWorkingTime" => $identitas['masa_kerja'],
			"So_ResultEntrySDSIdentityDepartement" => $identitas['departement'],
			"So_ResultEntrySDSIdentityJobStatus" => $identitas['status_pekerja'],
			"So_ResultEntrySDSIdentityJobType" => $identitas['jenis_pekerjaan'],
			"So_ResultEntrySDSIdentityPosition" => $identitas['level_jabatan'],
			"So_ResultEntrySDSIdentityEducation" => $identitas['pendidikan'],
			"So_ResultEntrySDSIdentityMaritalStatus" => $identitas['status_perkawinan'],
			"So_ResultEntrySDSIdentityCreatedUserID" => $userid,
			"So_ResultEntrySDSIdentityUpdatedUserID" => 0
		);
		// insert identitas
		if (count($cekIdentitas) == 0) {
			$dataIdentitas['So_ResultEntrySDSIdentityCreated'] =  date("Y-m-d h:i:sa");
			$query = $this->db_onedev->insert('so_resultentrysdsidentity', $dataIdentitas);
			if (!$query) {
				$this->sys_error_db("error insert identitas");
				exit;
			}
		} else {
			//Update
			// Hapus elemen So_ResultEntrySDSIdentityCreatedUserID
			unset($dataIdentitas['So_ResultEntrySDSIdentityCreatedUserID']);
			$dataIdentitas['So_ResultEntrySDSIdentityUpdatedUserID'] = $userid;
			$dataIdentitas['So_ResultEntrySDSIdentityUpdated'] =  date("Y-m-d h:i:sa");
			$this->db_onedev->where('So_ResultEntrySDSIdentityID', $cekIdentitas[0]['So_ResultEntrySDSIdentityID']);
			$query = $this->db_onedev->update('so_resultentrysdsidentity', $dataIdentitas);
			if (!$query) {
				$this->sys_error_db("error update identitas");
				exit;
			}
		}

		//INSERT QUESTIONS sds30
		for ($i = 0; $i < count($sds30['questions']); $i++) {
			//Cek data sudah ada belum 
			$e = $sds30['questions'][$i];
			$sql = "SELECT So_ResultEntrySDSID
					FROM so_resultentrysds
					WHERE So_ResultEntrySDSSo_ResultEntryID = {$trx['re_id']}
					AND So_ResultEntrySDSSDS_TemplateQuestionID = {$e['id']}
					AND So_ResultEntrySDSIsActive = 'Y'";
			$query = $this->db_onedev->query($sql, []);
			if (!$query) {
				$this->sys_error_db("error cek result sds");
				exit;
			}
			$cekSds = $query->result_array();
			// print_r($cekSds);
			// echo ("\n");

			// "So_ResultEntrySDSID" => "",
			$dataSds = array(
				"So_ResultEntrySDSSo_ResultEntryID" => $trx['re_id'],
				"So_ResultEntrySDSSDS_TemplateQuestionID" => $e['id'],
				"So_ResultEntrySDSType" => "30",
				"So_ResultEntrySDSSDS_TemplateOptionID" => $e['value'],
				"So_ResultEntrySDSCreatedUserID" => $userid,
				"So_ResultEntrySDSCreated" => $userid,
				"So_ResultEntrySDSUpdated" => 0,
			);

			if (count($cekSds) == 0) {
				$dataSds['So_ResultEntrySDSCreated'] =  date("Y-m-d h:i:sa");
				$query = $this->db_onedev->insert('so_resultentrysds', $dataSds);
				if (!$query) {
					$this->sys_error_db("error insert result sds");
					exit;
				}
			} else {
				//Update
				// Hapus elemen So_ResultEntrySDSID
				// $dataSds['So_ResultEntrySDSUpdated'] =  date("Y-m-d h:i:sa");
				// unset($dataSds['So_ResultEntrySDSCreatedUserID']);
				// $dataSds['So_ResultEntrySDSUpdatedUserID'] = $userid;
				// $this->db_onedev->where('So_ResultEntrySDSID', $cekSds[0]['So_ResultEntrySDSID']);
				// $query = $this->db_onedev->update('so_resultentrysds', $dataSds);

				$sql = 'UPDATE so_resultentrysds 
						SET So_ResultEntrySDSSDS_TemplateOptionID = ?,
							So_ResultEntrySDSUpdated = NOW(),
							So_ResultEntrySDSUpdatedUserID = ?
						WHERE So_ResultEntrySDSID = ?
							';
				$query = $this->db_onedev->query($sql, [$e['value'], $userid, $cekSds[0]['So_ResultEntrySDSID']]);
				if (!$query) {
					echo $this->db_onedev->last_query();
					$this->sys_error_db("error update result sds");
					exit;
				}
			}
		}
		//INSERT QUESTIONS srq20
		for ($i = 0; $i < count($srq20['questions']); $i++) {
			//Cek data sudah ada belum 
			$e = $srq20['questions'][$i];
			$sql = "SELECT So_ResultEntrySDSID
					FROM so_resultentrysds
					WHERE So_ResultEntrySDSSo_ResultEntryID = {$trx['re_id']}
					AND So_ResultEntrySDSSDS_TemplateQuestionID = {$e['id']}
					AND So_ResultEntrySDSIsActive = 'Y'";
			$query = $this->db_onedev->query($sql, []);
			if (!$query) {
				$this->sys_error_db("error cek result sds");
				exit;
			}
			$cekSds = $query->result_array();
			// print_r($cekSds);
			// echo ("\n");

			// "So_ResultEntrySDSID" => "",
			$dataSds = array(
				"So_ResultEntrySDSSo_ResultEntryID" => $trx['re_id'],
				"So_ResultEntrySDSSDS_TemplateQuestionID" => $e['id'],
				"So_ResultEntrySDSType" => "20",
				"So_ResultEntrySDSSDS_TemplateOptionID" => $e['value'],
				"So_ResultEntrySDSCreatedUserID" => $userid,
				"So_ResultEntrySDSCreated" => "",
				"So_ResultEntrySDSUpdated" => 0,
			);

			if (count($cekSds) == 0) {
				$dataSds['So_ResultEntrySDSCreated'] =  date("Y-m-d h:i:sa");
				$query = $this->db_onedev->insert('so_resultentrysds', $dataSds);
				if (!$query) {
					$this->sys_error_db("error insert result sds");
					exit;
				}
			} else {
				//Update
				// Hapus elemen So_ResultEntrySDSID
				// $dataSds['So_ResultEntrySDSUpdated'] =  date("Y-m-d h:i:sa");
				// unset($dataSds['So_ResultEntrySDSCreatedUserID']);
				// $dataSds['So_ResultEntrySDSUpdatedUserID'] = $userid;
				// $this->db_onedev->where('So_ResultEntrySDSID', $cekSds[0]['So_ResultEntrySDSID']);
				// $query = $this->db_onedev->update('so_resultentrysds', $dataSds);

				$sql = 'UPDATE so_resultentrysds 
						SET So_ResultEntrySDSSDS_TemplateOptionID = ?,
							So_ResultEntrySDSUpdated = NOW(),
							So_ResultEntrySDSUpdatedUserID = ?
						WHERE So_ResultEntrySDSID = ?
							';
				$query = $this->db_onedev->query($sql, [$e['value'], $userid, $cekSds[0]['So_ResultEntrySDSID']]);

				if (!$query) {

					$this->sys_error_db("error update result sds");
					exit;
				}
			}
		}
		//INSERT interpretation sds30
		for ($i = 0; $i < count($sds30['interpretation']); $i++) {
			//Cek data sudah ada belum 
			$e = $sds30['interpretation'][$i];
			$sql = "SELECT So_ResultEntrySDSInterpretationID
					FROM so_resultentrysdsinterpretation
					WHERE So_ResultEntrySDSInterpretationTypeSo_ResultEntryID = {$trx['re_id']}
					AND So_ResultEntrySDSInterpretationSDSInterpretationID = {$e['id']}
					AND So_ResultEntrySDSInterpretationIsActive = 'Y'";
			$query = $this->db_onedev->query($sql, []);
			if (!$query) {
				$this->sys_error_db("error cek interpretation sds");
				exit;
			}
			$cekSds = $query->result_array();

			// "So_ResultEntrySDSID" => "",
			$dataSds = array(
				"So_ResultEntrySDSInterpretationTypeSo_ResultEntryID" => $trx['re_id'],
				"So_ResultEntrySDSInterpretationSDSInterpretationID" => $e['id'],
				"So_ResultEntrySDSInterpretationType" => "30",
				"So_ResultEntrySDSInterpretationScore" => $e['score'],
				"So_ResultEntrySDSInterpretationDisplay" => $e['levelDisplay'],
				"So_ResultEntrySDSInterpretationCreatedUserID" => $userid,
				"So_ResultEntrySDSInterpretationUpdatedUserID" => 0,

			);

			if (count($cekSds) == 0) {
				$dataSds['So_ResultEntrySDSInterpretationCreated'] =  date("Y-m-d h:i:sa");
				$query = $this->db_onedev->insert('so_resultentrysdsinterpretation', $dataSds);
				if (!$query) {
					$this->sys_error_db("error insert interpretation sds");
					exit;
				}
			} else {
				//Update
				// Hapus elemen So_ResultEntrySDSID
				$dataSds['So_ResultEntrySDSInterpretationUpdated'] =  date("Y-m-d h:i:sa");
				unset($dataSds['So_ResultEntrySDSInterpretationCreatedUserID']);
				$dataSds['So_ResultEntrySDSInterpretationUpdatedUserID'] = $userid;
				$this->db_onedev->where('So_ResultEntrySDSInterpretationID', $cekSds[0]['So_ResultEntrySDSInterpretationID']);
				$query = $this->db_onedev->update('so_resultentrysdsinterpretation', $dataSds);
				if (!$query) {
					$this->sys_error_db("error update interpretation sds");
					exit;
				}
			}
		}
		//INSERT interpretation srq20
		for ($i = 0; $i < count($srq20['interpretation']); $i++) {
			//Cek data sudah ada belum 
			$e = $srq20['interpretation'][$i];
			$sql = "SELECT So_ResultEntrySDSInterpretationID
				FROM so_resultentrysdsinterpretation
				WHERE So_ResultEntrySDSInterpretationTypeSo_ResultEntryID = {$trx['re_id']}
				AND So_ResultEntrySDSInterpretationSDSInterpretationID = {$e['id']}
				AND So_ResultEntrySDSInterpretationIsActive = 'Y'";
			$query = $this->db_onedev->query($sql, []);
			if (!$query) {
				$this->sys_error_db("error cek interpretation sds");
				exit;
			}
			$cekSds = $query->result_array();

			// "So_ResultEntrySDSID" => "",
			$dataSds = array(
				"So_ResultEntrySDSInterpretationTypeSo_ResultEntryID" => $trx['re_id'],
				"So_ResultEntrySDSInterpretationSDSInterpretationID" => $e['id'],
				"So_ResultEntrySDSInterpretationType" => "20",
				"So_ResultEntrySDSInterpretationScore" => $e['score'],
				"So_ResultEntrySDSInterpretationDisplay" => $e['levelDisplay'],
				"So_ResultEntrySDSInterpretationCreatedUserID" => $userid,
				"So_ResultEntrySDSInterpretationUpdatedUserID" => 0,

			);

			if (count($cekSds) == 0) {
				$dataSds['So_ResultEntrySDSInterpretationCreated'] =  date("Y-m-d h:i:sa");
				$query = $this->db_onedev->insert('so_resultentrysdsinterpretation', $dataSds);
				if (!$query) {
					$this->sys_error_db("error insert interpretation sds");
					exit;
				}
			} else {
				//Update
				// Hapus elemen So_ResultEntrySDSID
				$dataSds['So_ResultEntrySDSInterpretationUpdated'] =  date("Y-m-d h:i:sa");
				unset($dataSds['So_ResultEntrySDSInterpretationCreatedUserID']);
				$dataSds['So_ResultEntrySDSInterpretationUpdatedUserID'] = $userid;
				$this->db_onedev->where('So_ResultEntrySDSInterpretationID', $cekSds[0]['So_ResultEntrySDSInterpretationID']);
				$query = $this->db_onedev->update('so_resultentrysdsinterpretation', $dataSds);
				if (!$query) {
					$this->sys_error_db("error update interpretation sds");
					exit;
				}
			}
		}
		if ($prm['action'] === 'val1' && $data_re['So_ResultEntryValidation1'] == 'N' && $data_re['So_ResultEntryValidation2'] == 'N') {
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation1 = 'Y', So_ResultEntryStatus = 'VAL1', So_ResultEntryLastUpdatedUserID = {$userid}, So_ResultEntryLastUpdated = NOW() WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				// $message = $this->db_onedev->error();
				// $message['qry'] = $this->db_onedev->last_query();
				// print_r($this->db_onedev->last_query());
				// $this->sys_error("Error search" . json_encode($message));
				$this->sys_error_db("error validasi");
				exit;
			}
			//echo $this->db_onedev->last_query();
		}

		if ($prm['action'] === 'unval1' && $data_re['So_ResultEntryValidation1'] == 'Y' && $data_re['So_ResultEntryValidation2'] == 'N') {
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation1 = 'N', So_ResultEntryStatus = 'NEW', So_ResultEntryLastUpdatedUserID = {$userid},  So_ResultEntryLastUpdated = NOW() WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$this->sys_error_db("eror unval");
				exit;
			}
		}
		$act = "UPDATE_ACT";
		if ($prm['action'] === 'val1')
			$act = "VALIDATION";
		if ($prm['action'] === 'unval1')
			$act = "UNVALIDATION";

		$this->soresultlog->step_action($act, $trx['re_id'], $userid);

		$sql = "SELECT * FROM so_resultentry
				WHERE So_ResultEntryID = {$trx['re_id']}
				AND So_ResultEntryIsActive = 'Y'";
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("eror unval");
			exit;
		}
		$reData = $query->row_array();
		$sql = "SELECT * FROM so_resultentrysds
				WHERE So_ResultEntrySDSSo_ResultEntryID = {$trx['re_id']}
				AND So_ResultEntrySDSIsActive = 'Y'";
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("eror unval");
			exit;
		}
		$questionsData = $query->result_array();
		$sql = "SELECT * FROM so_resultentrysdsidentity
				WHERE So_ResultEntrySDSIdentitySo_ResultEntryID = {$trx['re_id']}
				AND So_ResultEntrySDSIdentityIsActive= 'Y';";
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("eror unval");
			exit;
		}
		$identityData = $query->result_array();

		$sql = "SELECT * FROM so_resultentrysdsinterpretation
				WHERE So_ResultEntrySDSInterpretationTypeSo_ResultEntryID = {$trx['re_id']}
				AND So_ResultEntrySDSInterpretationIsActive= 'Y'";
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("eror unval");
			exit;
		}
		$interpretationData = $query->result_array();


		$data_log = json_encode(array(
			'header' => $reData,
			'details' => array(
				"identity" => $identityData,
				"question" => $questionsData,
				"interpretation" => $interpretationData,
			)
		));
		$this->soresultlog->log_result($data_log, $trx['re_id'], $userid);

		$result = array(
			"total" => 1,
			"records" => array('status' => 'OK')
		);
		$this->sys_ok($result);
		exit;
	}

	function getSDS()
	{
		$prm = $this->sys_input;
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$type = 'SDS30';
		$sql = "SELECT *
		        FROM so_resultentrysds_type
				WHERE
					So_ResultEntrySDSTypeSo_ResultEntryID = '{$prm['trx_id']}' AND
					So_ResultEntrySDSTypeIsActive = 'Y'
					";
		$query = $this->db_onedev->query($sql);
		if (!$query) {
			$this->sys_error_db("error get sds question");
			exit;
		}


		$rst_type = $query->result_array();
		//print_r($rst_type);
		if (count($rst_type) > 0) {
			if (intval($rst_type[0]['So_ResultEntrySDSTypeValue']) == 20)
				$type = 'SRQ20';
		}


		//get sds30 questions
		$sql = "SELECT 
				IFNULL(So_ResultEntrySDSID, 0) sdsReID,
				IFNULL(So_ResultEntrySDSSo_ResultEntryID, 0) reID,
				SDS_TemplateQuestionID as id,
				SDS_TemplateQuestionOrder AS orderNumber,
				SDS_TemplateQuestionText AS display, 
				IFNULL(So_ResultEntrySDSSDS_TemplateOptionID,`fn_getDefaultsdsOption`('30')) as value
				FROM sds_templatequestion 
				LEFT JOIN so_resultentrysds
				ON SDS_TemplateQuestionID = So_ResultEntrySDSSDS_TemplateQuestionID
				AND So_ResultEntrySDSSo_ResultEntryID = '{$prm['trx_id']}'
				LEFT JOIN sds_templateoption
				ON SDS_TemplateOptionID  = So_ResultEntrySDSSDS_TemplateOptionID
				WHERE SDS_TemplateQuestionType = 30
				AND SDS_TemplateQuestionIsActive = 'Y'
				ORDER BY SDS_TemplateQuestionOrder asc";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get sds question");
			exit;
		}
		$sds30Question = $query->result_array();

		$sql = "SELECT 
				SDS_TemplateOptionID  as id,
				SDS_TemplateOptionOrder as orderNumber,
				SDS_TemplateOptionText as display,
				SDS_TemplateOptionValue as value
				FROM sds_templateoption
				WHERE SDS_TemplateOptionType = 30
				AND SDS_TemplateOptionIsActive = 'Y'
				ORDER BY SDS_TemplateOptionOrder asc";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get sds option");
			exit;
		}
		$sds30Option = $query->result_array();

		$sql = "SELECT 
				IFNULL(So_ResultEntrySDSInterpretationTypeSo_ResultEntryID, 0)  reID,
				IFNULL(So_ResultEntrySDSInterpretationID, 0) sdsReID,
				SDS_InterpretationID as id,
				SDS_InterpretationOrder as orderNumber,
				SDS_InterpretationText as display,
				GROUP_CONCAT(SDS_InterpretationMapSDS_TemplateQuestionID) as questionID,
				IFNULL(So_ResultEntrySDSInterpretationScore, 0) as score,
				0 as level,
				IFNULL(So_ResultEntrySDSInterpretationDisplay, 'Ringan') as levelDisplay 
				FROM sds_interpretation
				JOIN sds_interpretationmap
				ON SDS_InterpretationID = SDS_InterpretationMapSDS_InterpretationID
				AND SDS_InterpretationMapIsActive = 'Y'
				LEFT JOIN so_resultentrysdsinterpretation
				ON SDS_InterpretationID  = So_ResultEntrySDSInterpretationSDSInterpretationID
				AND So_ResultEntrySDSInterpretationTypeSo_ResultEntryID = '{$prm['trx_id']}'
				WHERE SDS_InterpretationIsActive = 'Y'
				AND SDS_InterpretationType = '30'
				GROUP BY SDS_InterpretationID
				ORDER BY SDS_InterpretationOrder asc";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get sds interpretation");
			exit;
		}
		$sds30Interpretation = $query->result_array();

		$sql = "SELECT 
				SDS_InterpretationRuleID as id,
				SDS_InterpretationRuleText as display,
				SDS_InterpretationRuleMin as min,
				SDS_InterpretationRuleMax as max,
				SDS_InterpretationRuleValue as value,
				SDS_InterpretationRuleIsFix as isFix,
				SDS_InterpretationRuleIsRange as isRange,
				SDS_InterpretationRuleFlag as flag
				FROM sds_interpretationrule
				WHERE SDS_InterpretationRuleType = 30
				AND SDS_InterpretationRuleIsActive = 'Y'";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get sds interpretation rule");
			exit;
		}
		$sds30InterpretationRule = $query->result_array();

		//get srq20 questions
		$sql = "SELECT 
				IFNULL(So_ResultEntrySDSID, 0) sdsReID,
				IFNULL(So_ResultEntrySDSSo_ResultEntryID, 0) reID,
				SDS_TemplateQuestionID as id,
				SDS_TemplateQuestionOrder AS orderNumber,
				SDS_TemplateQuestionText AS display, 
				IFNULL(So_ResultEntrySDSSDS_TemplateOptionID, `fn_getDefaultsdsOption`('20')) as value
				FROM sds_templatequestion 
				LEFT JOIN so_resultentrysds
				ON SDS_TemplateQuestionID = So_ResultEntrySDSSDS_TemplateQuestionID
				AND So_ResultEntrySDSSo_ResultEntryID = '{$prm['trx_id']}'
				LEFT JOIN sds_templateoption
				ON SDS_TemplateOptionID  = So_ResultEntrySDSSDS_TemplateOptionID
				WHERE SDS_TemplateQuestionType = 20
				AND SDS_TemplateQuestionIsActive = 'Y'
				ORDER BY SDS_TemplateQuestionOrder asc";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get srq question");
			exit;
		}
		$srq20Question = $query->result_array();

		$sql = "SELECT 
				SDS_TemplateOptionID  as id,
				SDS_TemplateOptionOrder as orderNumber,
				SDS_TemplateOptionText as display,
				SDS_TemplateOptionValue as value
				FROM sds_templateoption
				WHERE SDS_TemplateOptionType = 20
				AND SDS_TemplateOptionIsActive = 'Y'
				ORDER BY SDS_TemplateOptionOrder asc";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get srq option");
			exit;
		}
		$srq20Option = $query->result_array();

		$sql = "SELECT 
				IFNULL(So_ResultEntrySDSInterpretationTypeSo_ResultEntryID, 0)  reID,
				IFNULL(So_ResultEntrySDSInterpretationID, 0) sdsReID,
				SDS_InterpretationID as id,
				SDS_InterpretationOrder as orderNumber,
				SDS_InterpretationText as display,
				GROUP_CONCAT(SDS_InterpretationMapSDS_TemplateQuestionID) as questionID,
				IFNULL(So_ResultEntrySDSInterpretationScore, 0) as score,
				0 as level,
				IFNULL(So_ResultEntrySDSInterpretationDisplay, 'Tidak ada') as levelDisplay 
				FROM sds_interpretation
				JOIN sds_interpretationmap
				ON SDS_InterpretationID = SDS_InterpretationMapSDS_InterpretationID
				AND SDS_InterpretationMapIsActive = 'Y'
				LEFT JOIN so_resultentrysdsinterpretation
				ON SDS_InterpretationID  = So_ResultEntrySDSInterpretationSDSInterpretationID
				AND So_ResultEntrySDSInterpretationTypeSo_ResultEntryID = '{$prm['trx_id']}'
				WHERE SDS_InterpretationIsActive = 'Y'
				AND SDS_InterpretationType = '20'
				GROUP BY SDS_InterpretationID
				ORDER BY SDS_InterpretationOrder asc";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get srq interpretation");
			exit;
		}
		$srq20Interpretation = $query->result_array();
		$sql = "SELECT 
				SDS_InterpretationRuleID as id,
				SDS_InterpretationRuleText as display,
				SDS_InterpretationRuleMin as min,
				SDS_InterpretationRuleMax as max,
				SDS_InterpretationRuleValue as value,
				SDS_InterpretationRuleIsFix as isFix,
				SDS_InterpretationRuleIsRange as isRange,
				SDS_InterpretationRuleFlag as flag
				FROM sds_interpretationrule
				WHERE SDS_InterpretationRuleType = 20
				AND SDS_InterpretationRuleIsActive = 'Y'";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get sds interpretation rule");
			exit;
		}
		$srq20InterpretationRule = $query->result_array();
		$identitas =
			array(
				"nama" => "",
				"usia" => "",
				"masa_kerja" => "",
				"departement" => "",
				"jenis_kelamin" => "",
				"jenis_kelamin_option" => array("Pria", "Wanita"),
				"status_pekerja" => "",
				"status_pekerja_option" => array("Tetap", "Tidak tetap (Kontrak)"),
				"jenis_pekerjaan" => "",
				"jenis_pekerjaan_option" => array("Kantor", "Lapangan"),
				"level_jabatan" => "",
				"level_jabatan_option" => array("Manager", "Supervisor", "Staff"),
				"pendidikan" => "",
				"pendidikan_option" => array("SD", "SMP", "SLTA", "D3", "S1", "S2,S3"),
				"status_perkawinan" => "",
				"status_perkawinan_option" => array("Single", "Menikah", "Duda", "Janda"),
			);
		//Get Identitas
		$sql = "SELECT 
				IFNULL(So_ResultEntrySDSIdentityAge, T_OrderHeaderM_PatientAge) as age,
				IFNULL(So_ResultEntrySDSIdentityName, CONCAT(M_TitleName,' ',M_PatientName)) as name,
				IFNULL ( So_ResultEntrySDSIdentityGender, CASE
				WHEN M_PatientGender = 'male' THEN 'Pria'
				WHEN M_PatientGender  = 'female' THEN 'Wanita'
				END) as gender, 
				IFNULL(So_ResultEntrySDSIdentityDepartement,M_PatientDepartement) as departement, 
				IFNULL(So_ResultEntrySDSIdentityWorkingTime, '') as workingTime,
				IFNULL(So_ResultEntrySDSIdentityJobStatus, '') as jobStatus,
				IFNULL(So_ResultEntrySDSIdentityJobType, '') as jobType,
				IFNULL(So_ResultEntrySDSIdentityPosition, '') as position,
				IFNULL(So_ResultEntrySDSIdentityEducation, '') as education,
				IFNULL(So_ResultEntrySDSIdentityMaritalStatus, '') as maritalStatus
				FROM t_orderheader
				JOIN m_patient 
				ON T_OrderHeaderM_PatientID = M_PatientID	
				AND T_OrderHeaderID = '{$prm['orderid']}'
				LEFT JOIN m_title
				ON M_PatientM_TitleID = M_TitleID
				AND M_TitleIsActive = 'Y'
				LEFT JOIN so_resultentry
				ON T_OrderHeaderID = So_ResultEntryT_OrderHeaderID
				AND So_ResultEntryID = '{$prm['trx_id']}'
				AND So_ResultEntryIsActive = 'Y'
				LEFT JOIN so_resultentrysdsidentity
				ON So_ResultEntryID  = So_ResultEntrySDSIdentitySo_ResultEntryID
				AND So_ResultEntrySDSIdentityIsActive = 'Y'";
		$query = $this->db_onedev->query($sql, []);
		if (!$query) {
			$this->sys_error_db("error get identitas");
			exit;
		}
		$getIdentitas = $query->result_array();
		if (count($getIdentitas) > 0) {
			$identitas['nama'] = $getIdentitas[0]['name'];
			$identitas['usia'] = $getIdentitas[0]['age'];
			$identitas['jenis_kelamin'] = $getIdentitas[0]['gender'];
			$identitas['departement'] = $getIdentitas[0]['departement'];
			$identitas['masa_kerja'] = $getIdentitas[0]['workingTime'];
			$identitas['status_pekerja'] = $getIdentitas[0]['jobStatus'];
			$identitas['jenis_pekerjaan'] = $getIdentitas[0]['jobType'];
			$identitas['level_jabatan'] = $getIdentitas[0]['position'];
			$identitas['pendidikan'] = $getIdentitas[0]['education'];
			$identitas['status_perkawinan'] = $getIdentitas[0]['maritalStatus'];
		}



		$result = array(
			'sds30' => array(
				"questions" => $sds30Question,
				"options" => $sds30Option,
				"interpretation" => $sds30Interpretation,
				"interpretationRule" => $sds30InterpretationRule,
			),
			'srq20' => array(
				"questions" => $srq20Question,
				"options" => $srq20Option,
				"interpretation" => $srq20Interpretation,
				"interpretationRule" => $srq20InterpretationRule,

			),
			'identitas' => $identitas,
			'type' => $type
		);
		$this->sys_ok($result);
	}
	// function getDefaultSdsValue($interpretationID)
	// {
	// 	$sql = "SELECT 
	// 			SDS_InterpretationID,
	// 			SDS_InterpretationText,
	// 			SDS_InterpretationMapSDS_TemplateQuestionID,
	// 			SDS_InterpretationType,
	// 			MIN(SDS_TemplateOptionValue) as value
	// 			FROM sds_interpretation
	// 			JOIN sds_interpretationmap
	// 			ON SDS_InterpretationID = SDS_InterpretationMapSDS_InterpretationID
	// 			AND SDS_InterpretationMapIsActive = 'Y'
	// 			AND SDS_InterpretationID = {$interpretationID}
	// 			JOIN sds_templateoption 
	// 			ON SDS_InterpretationType = SDS_TemplateOptionType
	// 			AND SDS_TemplateOptionIsActive = 'Y'
	// 			GROUP BY SDS_InterpretationMapSDS_TemplateQuestionID";
	// 	$query = $this->db_onedev->query($sql, []);
	// 	if (!$query) {
	// 		$this->sys_error_db("error get default interpretation value");
	// 		exit;
	// 	}
	// 	$data = $query->result_array();
	// 	if (count($data) == 0) {
	// 		$this->sys_error_db("Default interpretation tidak ditemukan");
	// 		exit;
	// 	}
	// 	$type = $data[0]['SDS_InterpretationType'];
	// 	$score = 0;
	// 	$display = '';
	// 	for ($i = 0; $i < count($data); $i++) {
	// 		if ($type == '30') {
	// 			$score = $score + intval($data[$i]['value']);
	// 		}
	// 	}
	// }

	function getumum()
	{
		$prm = $this->sys_input;

		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$userid = $this->sys_user["M_UserID"];
		$rst = array();
		$rst['riwayats'] = array();
		$rst['fisiks'] = array();
		$rst['umum_saran'] = '';
		$rst['k3s'] = array();
		$rst['konsul'] = array();

		/*$sql = "
				SELECT 'KONSUL' as label,'konsul' as type,fn_get_konsul_by_type({$prm['re_id']},{$prm['language_id']},'konsul') as result 
				UNION
				SELECT 'SARAN' as label,'saran_konsul' as type, fn_get_konsul_by_type({$prm['re_id']},{$prm['language_id']},'saran_konsul') as result 
				";
		//echo $sql;
		$rst['konsul'] = $this->db_onedev->query($sql)->result_array();*/


		$rows = array();
		//$prm = $this->sys_input;

		$sql = "SELECT count(*) as xcount
				FROM so_resultentry_fisik_umum
				WHERE
					So_ResultEntryFisikUmumSo_ResultEntryID = {$prm['re_id']} AND 
					So_ResultEntryFisikUmumIsActive = 'Y'
				ORDER BY So_ResultEntryFisikUmumID ASC ";
		$x_exist = $this->db_onedev->query($sql)->row()->xcount;

		if ($x_exist == 0) {
			if ($prm['template_name'] == 'Fisik Umum' || $prm['template_name'] == 'Fisik Umum K3') {
				$sql = "INSERT INTO so_resultentry_fisik_umum (
					So_ResultEntryFisikUmumSo_ResultEntryID,
					So_ResultEntryFisikUmumFisikTemplateID,
					So_ResultEntryFisikUmumDetails,
					So_ResultEntryFisikUmumCreated,
					So_ResultEntryFisikUmumCreatedUserID
				)
				SELECT {$prm['re_id']},
				FisikTemplateID,
				FisikTemplateJSON,
				NOW(),
				{$userid}
				FROM fisik_template 
				JOIN t_orderheader ON T_OrderHeaderID = {$prm['orderid']}
				JOIN mgm_mcutemplate ON Mgm_McuTemplateMgm_McuID = T_OrderHeaderMgm_McuID
				JOIN fisik_template_mapping ON Mgm_McuTemplateFisikTemplateMappingID = FisikTemplateMappingID 
				JOIN fisik_template_mapping_detail ON FisikTemplateMappingDetailFisikTemplateMappingID = FisikTemplateMappingID AND 
					FisikTemplateMappingDetailFisikTemplateID = FisikTemplateID AND 
					FisikTemplateMappingDetailIsActive = 'Y'
				WHERE
				FisikTemplateIsActive = 'Y'
				GROUP BY FisikTemplateID
				ORDER BY FisikTemplateCode ASC";
				$this->db_onedev->query($sql);
			}
		}

		/*$sql = "SELECT t_samplingso_additional_fisik_vbw.* 
				FROM t_samplingso_additional_fisik_vbw
				JOIN t_samplingso ON T_SamplingAdditionalFisikVBWT_SamplingSoID = T_SamplingSoID AND T_SamplingSoIsActive = 'Y' AND
					T_SamplingSoT_OrderHeaderID = {$prm['orderid']}
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID AND T_TestNat_TestID IN ( 6247, 6245  )
				LIMIT 1";
		$row_result_sampling_visus_bw = $this->db_onedev->query($sql)->row_array();*/
		//print_r($row_result_sampling_visus_bw);

		/*$sql = "SELECT t_samplingso_additional_fisik_bbtb.* 
				FROM t_samplingso_additional_fisik_bbtb
				JOIN t_samplingso ON T_SamplingAdditionalFisikBBTBT_SamplingSoID = T_SamplingSoID AND T_SamplingSoIsActive = 'Y' AND
					T_SamplingSoT_OrderHeaderID = {$prm['orderid']}
				JOIN t_test ON T_SamplingSoT_TestID = T_TestID AND T_TestNat_TestID = 10996 
				LIMIT 1";
		$row_result_sampling_bbtb = $this->db_onedev->query($sql)->row_array();*/

		$riwayats = [];
		$fisiks = [];
		$k3s = [];
		$sql = "SELECT *
				FROM so_resultentry_fisik_umum 
				JOIN fisik_template ON So_ResultEntryFisikUmumFisikTemplateID = FisikTemplateID
				WHERE
				So_ResultEntryFisikUmumSo_ResultEntryID = {$prm['re_id']} AND So_ResultEntryFisikUmumIsActive = 'Y' 
				ORDER BY FisikTemplateCode ASC";
		$rows_data = $this->db_onedev->query($sql)->result_array();
		if ($rows_data) {
			foreach ($rows_data as $key => $value) {
				if ($value['FisikTemplateType'] == 'Riwayat')
					$riwayats[] = json_decode($value['So_ResultEntryFisikUmumDetails'], TRUE);

				if ($value['FisikTemplateType'] == 'Fisik')
					$fisiks[] = json_decode($value['So_ResultEntryFisikUmumDetails'], TRUE);

				/*if($value['FisikTemplateType'] == 'Fisik'){
					$fisik = json_decode($value['So_ResultEntryFisikUmumDetails'],TRUE);
					if($value['FisikTemplateTableName'] === 'status_gizi'){
						$standart_bmi = $fisik['standart_bmi'];
						$bb = floatval($row_result_sampling_bbtb['T_SamplingAdditionalFisikBBTBValueBB']);
						$tb = floatval($row_result_sampling_bbtb['T_SamplingAdditionalFisikBBTBValueTB']);
						$bmi = '';
						$classs = '';
						if($bb > 0 && $tb > 0){
							$rst_bmi = $this->hitung_bmi($bb,$tb,$standart_bmi);
							$bmi = $rst_bmi['bmi'];
							$classs = $rst_bmi['class'];
						}
							


						$f_details = $fisik['details'];
						foreach ($f_details as $k_fisik => $v_fisik) {
							if($v_fisik['id_code'] === 'status_gizi_1' && intval($v_fisik['value']) == 0){
								$f_details[$k_fisik]['value'] = $row_result_sampling_bbtb['T_SamplingAdditionalFisikBBTBValueBB'] ;
							}
							if($v_fisik['id_code'] === 'status_gizi_2' && intval($v_fisik['value']) == 0){
								$f_details[$k_fisik]['value'] = $row_result_sampling_bbtb['T_SamplingAdditionalFisikBBTBValueTB'] ;
							}
							if($v_fisik['id_code'] === 'status_gizi_4'){
								$f_details[$k_fisik]['value'] = $bmi ;
							}
							if($v_fisik['id_code'] === 'status_gizi_6'){
								$f_details[$k_fisik]['value'] = $classs ;
							}
						}
						$fisik['details'] = $f_details;
					}

					if($value['FisikTemplateTableName'] === 'visus_jauh'){
						$f_details = $fisik['details'];
						foreach ($f_details as $k_fisik => $v_fisik) {
							$v_details = $v_fisik['details'];
							if($v_fisik['name'] == 'Tanpa kacamata'){
								
								$v_details[0]['value'] = $row_result_sampling_visus_bw['T_SamplingAdditionalFisikVBWTKODV'];
								if($v_details[0]['value'] != '')
									$v_details[0]['chx'] = true;
								else
									$v_details[0]['chx'] = false;

								$v_details[1]['value'] = $row_result_sampling_visus_bw['T_SamplingAdditionalFisikVBWTKOSV'];
								if($v_details[1]['value'] != '')
									$v_details[1]['chx'] = true;
								else
									$v_details[1]['chx'] = false;
							}

							if($v_fisik['name'] == 'Dengan kacamata'){
								$v_details[0]['value'] = $row_result_sampling_visus_bw['T_SamplingAdditionalFisikVBWDKODV'];
								if($v_details[0]['value'] != '')
									$v_details[0]['chx'] = true;
								else
									$v_details[0]['chx'] = false;
									
								$v_details[1]['value'] = $row_result_sampling_visus_bw['T_SamplingAdditionalFisikVBWDKOSV'];
								if($v_details[1]['value'] != '')
									$v_details[1]['chx'] = true;
								else
									$v_details[1]['chx'] = false;
							}

							$f_details[$k_fisik]['details'] = $v_details;
						}
						$fisik['details'] = $f_details;
					}

					if($value['FisikTemplateTableName'] === 'persepsi_warna'){
						
						$f_details = $fisik['details'];
						$value_rst = $row_result_sampling_visus_bw['T_SamplingAdditionalFisikVBWPWValue'];
						if($value_rst == 'N')
						$f_details[0]['chx'] = true;
						else
						$f_details[0]['chx'] = false;

						if($value_rst == 'BP')
						$f_details[1]['chx'] = true;
						else
						$f_details[1]['chx'] = false;

						if($value_rst == 'BT')
						$f_details[2]['chx'] = true;
						else
						$f_details[2]['chx'] = false;

						$fisik['details'] = $f_details;
					}
					$fisiks[] = $fisik;
				}*/


				if ($value['FisikTemplateType'] == 'K3')
					$k3s[] = json_decode($value['So_ResultEntryFisikUmumDetails'], TRUE);
			}
		}

		$rst['riwayats'] = $riwayats;
		$rst['fisiks'] = $fisiks;
		$rst['k3s'] = $k3s;

		$sql = "SELECT * 
				FROM translate_word
				WHERE
					Translate_WordIsActive = 'Y'";
		$translate_word = $this->db_onedev->query($sql)->result_array();

		$status = 1;

		$sql = "SELECT COUNT(*) as xcount, So_ResultEntryFisikUmumAdditionalValue as xvalue
				FROM so_resultentry_fisik_umum_additional
				WHERE
				So_ResultEntryFisikUmumAdditionalSo_ResultEntryID = {$prm['re_id']} AND 
				So_ResultEntryFisikUmumAdditionalType = 'saran' AND
				So_ResultEntryFisikUmumAdditionalIsActive = 'Y'";
		$row_saran = $this->db_onedev->query($sql)->row_array();
		if ($row_saran['xcount'] > 0)
			$rst['umum_saran'] = $row_saran['xvalue'];

		$result = array(
			"total" => $status,
			"records" => $rst,
			"translate" => $translate_word
		);
		$this->sys_ok($result);
		exit;
	}

	function hitung_bmi($bb, $tb, $standart_bmi)
	{
		$tb = $tb / 100;
		$bmi = '';
		$bmi = $bb / ($tb * $tb);
		$bmi_valuex = number_format($bmi, 2);
		$classs = "Undefined";

		if ($standart_bmi === 'asia_pacific') {
			if ($bmi_valuex < 18.5)
				$classs = 'Underweight';

			if ($bmi_valuex >= 18.5 && $bmi_valuex < 23)
				$classs = 'Normal';

			if ($bmi_valuex >= 23 && $bmi_valuex < 25)
				$classs = 'Overweight';

			if ($bmi_valuex >= 25 && $bmi_valuex < 30)
				$classs = 'Obese I';

			if ($bmi_valuex >= 30)
				$classs = 'Obese II';
		}

		if ($standart_bmi === 'who') {
			if ($bmi_valuex < 18.5)
				$classs  = 'Underweight';

			if ($bmi_valuex >= 18.5 && $bmi_valuex < 25) {
				$classs  = 'Normal';
			}

			if ($bmi_valuex >= 25 && $bmi_valuex < 30) {
				$classs  = 'Overweight';
			}

			if ($bmi_valuex >= 30)
				$classs  = 'Obese';
		}

		if ($standart_bmi === 'kemenkes') {
			if ($bmi_valuex < 18.5)
				$classs  = 'Underweight';

			if ($bmi_valuex >= 18.5 && $bmi_valuex < 25.1)
				$classs  = 'Normal';

			if ($bmi_valuex >= 25.1 && $bmi_valuex < 27)
				$classs  = 'Overweight';

			if ($bmi_valuex >= 27)
				$classs  = 'Obese';
		}

		return array(
			'bmi' => $bmi_valuex,
			'class' => $classs
		);
	}

	function getgroups()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query = "	SELECT Nat_GroupID as id, Nat_GroupName as title, CONCAT('GROUP : ',Nat_GroupName) as fulltitle, '' as childrens
					FROM nat_group 
					WHERE	
						Nat_GroupIsActive = 'Y' AND ( Nat_GroupCode = 2 OR Nat_GroupCode = 3 )
				";
		//echo $query;
		$rows['groups'] = $this->db_onedev->query($query)->result_array();
		if ($rows['groups']) {
			foreach ($rows['groups'] as $k => $v) {
				$childrens = array(array('id' => 0, 'title' => 'Semua', 'fulltitle' => 'Subgroub : Semua'));
				$query = "	SELECT Nat_SubGroupID as id, Nat_SubGroupName as title, CONCAT('SUBGROUP : ',Nat_SubGroupName) as fulltitle
					FROM nat_subgroup 
					WHERE	
						Nat_SubGroupNat_GroupID = {$v['id']} AND Nat_SubGroupIsActive = 'Y'
				";
				//echo $query;
				$xrst = $this->db_onedev->query($query)->result_array();
				if ($xrst) {
					foreach ($xrst as $ki => $vi) {
						array_push($childrens, $vi);
					}
				}
				$rows['groups'][$k]['childrens'] = $childrens;
			}
		}
		$query = "	SELECT M_LangID as id, M_LangCode as name
					FROM m_lang 
					WHERE	
						M_LangIsActive = 'Y' 
				";
		//echo $query;
		$rows['langs'] = $this->db_onedev->query($query)->result_array();
		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function getsubgroups()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$rows = array(array('id' => 0, 'title' => 'Semua', 'fulltitle' => 'Subgroub : Semua'));
		$query = "	SELECT Nat_SubGroupID as id, Nat_SubGroupName as title, CONCAT('SUBGROUP : ',Nat_SubGroupName) as fulltitle
					FROM nat_subgroup 
					WHERE	
						Nat_SubGroupNat_GroupID = {$prm['id']} AND Nat_SubGroupIsActive = 'Y'
				";
		//echo $query;
		$rst = $this->db_onedev->query($query)->result_array();
		if ($rst) {
			foreach ($rst as $k => $v) {
				array_push($rows, $v);
			}
		}
		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function getstation()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$rows = [];
		$query = "	SELECT T_SampleStationID as id, T_SampleStationName as name
					FROM t_samplestation 
					WHERE	
						T_SampleStationIsActive = 'Y'
				";
		//echo $query;
		$rows['stations'] = $this->db_onedev->query($query)->result_array();
		//print_r($statuses);
		foreach ($statuses as $k => $v) {
			array_push($rows['statuses'], $v);
		}



		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}

	function getdoctoraddress()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$query = "	SELECT M_DoctorAddressID as id, M_DoctorAddressDescription as name
					FROM m_doctoraddress 
					WHERE	
						M_DoctorAddressM_DoctorID = {$prm['id']} AND M_DoctorAddressIsActive = 'Y'
				";
		//echo $query;
		$rows = $this->db_onedev->query($query)->result_array();

		$result = array(
			"total" => count($rows),
			"records" => $rows,
		);
		$this->sys_ok($result);
		exit;
	}



	function saveresult()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
		$row_resulentry = $this->db_onedev->query($sql)->row_array();
		//if ($prm['trx']['note']) {
		if (intval($prm['trx']['language_id']) == intval($row_resulentry['So_ResultEntryM_LangID'])) {
			$sql = "UPDATE so_resultentry SET 
							So_ResultEntryNote = '{$prm['trx']['note']}', 
							So_ResultEntryUserID = {$userid} 
						WHERE 
							So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
		} /*else {
				$sql = "SELECT * 
						FROM so_resultentry_other 
						WHERE 
							So_ResultEntryOtherSo_ResultEntryID =  {$prm['trx']['trx_id']} AND
							So_ResultEntryOtherM_LangID = {$prm['trx']['language_id']} AND 
							So_ResultEntryOtherIsActive = 'Y' LIMIT 1";
				$row_resulentry_other = $this->db_onedev->query($sql)->row_array();
				if ($row_resulentry_other) {
					$sql = "UPDATE so_resultentry_other 
							SET So_ResultEntryOtherNote = '{$prm['trx']['note']}',
								So_ResultEntryOtherUserID = {$userid}
							WHERE
								So_ResultEntryOtherID = {$row_resulentry_other['So_ResultEntryOtherID']}";
					$this->db_onedev->query($sql);
				} else {
					$sql = "INSERT INTO so_resultentry_other (
								So_ResultEntryOtherSo_ResultEntryID,
								So_ResultEntryOtherM_LangID,
								So_ResultEntryOtherNote,
								So_ResultEntryOtherUserID,
								So_ResultEntryOtherCreated
							)
							VALUES(
								{$prm['trx']['trx_id']} ,
								{$prm['trx']['language_id']},
								'{$prm['trx']['note']}',
								{$userid},
								NOW()
							)";
					$this->db_onedev->query($sql);
				}
			}*/
		//} else {
		/*if (intval($prm['trx']['language_id']) == intval($row_resulentry['So_ResultEntryM_LangID'])) {
				$sql = "UPDATE so_resultentry SET 
							So_ResultEntryNote = '', 
							So_ResultEntryUserID = {$userid} 
						WHERE 
							So_ResultEntryID = {$prm['trx']['trx_id']}";
				$this->db_onedev->query($sql);
			}*/
		//}


		if ($prm['act'] === 'val1') {
			$sql = "UPDATE so_resultentry SET 
						So_ResultEntryValidation1 = 'Y', 
						So_ResultEntryStatus = 'VAL1', 
						So_ResultEntryLastUpdated = NOW(),So_ResultEntryLastUpdatedUserID = {$userid} 
					WHERE 
						So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
			//echo $this->db_onedev->last_query();
			//$alkelainans = $this->kesimpulanfisik->generate_kelainan_fisik($prm['trx']['trx_id']);
			//print_r($alkelainans);
		}
		if ($prm['act'] === 'unval1') {
			$sql = "SELECT IFNULL(Mcu_ResumeValidation, 'N') as status, COUNT(Mcu_ResumeID)
			FROM mcu_resume
			WHERE Mcu_ResumeT_OrderHeaderID = {$prm['trx']['orderid']}
			AND Mcu_ResumeIsActive = 'Y'";
			$qry = $this->db_onedev->query($sql);
			if (!$qry) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$this->sys_error("Error cek resume individu");
				exit;
			}
			$cek = $qry->row_array();
			if ($cek['status'] == 'Y') {
				$this->sys_error("Resume individu sudah di validasi, unvalidasi resume individu terlebih dahulu ....");
				exit;
			}
		}

		if ($prm['act'] === 'unval1') {

			$sql = "UPDATE so_resultentry SET 
						So_ResultEntryValidation1 = 'N', 
						So_ResultEntryStatus = 'NEW', 
						So_ResultEntryLastUpdated = NOW(),So_ResultEntryLastUpdatedUserID = {$userid} 
					WHERE 
						So_ResultEntryID = {$prm['trx']['trx_id']}";
			$this->db_onedev->query($sql);
		}

		if ($prm['act'] === 'val2') {
			$sql = "UPDATE so_resultentry SET 
						So_ResultEntryValidation2 = 'Y', 
						So_ResultEntryStatus = 'VAL2', 
						So_ResultEntryLastUpdated = NOW(),
						So_ResultEntryLastUpdatedUserID  = {$userid} 
					WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
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
			if ($data_sampling) {
				foreach ($data_sampling as $k => $v) {
					$sql = "UPDATE t_samplingso SET T_SamplingSoIsDone = 'Y' WHERE T_SamplingSoID = {$v['T_SamplingSoID']}";
					$this->db_onedev->query($sql);
				}
			}
		}

		if ($prm['act'] === 'unval2') {
			$sql = "UPDATE so_resultentry SET So_ResultEntryValidation2 = 'N', 
						So_ResultEntryStatus = 'VAL1', 
						So_ResultEntryLastUpdated = NOW(), 
						So_ResultEntryLastUpdatedUserID  = {$userid} 
					WHERE So_ResultEntryID = {$prm['trx']['trx_id']}";
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
			if ($data_sampling) {
				foreach ($data_sampling as $k => $v) {
					$sql = "UPDATE t_samplingso SET T_SamplingSoIsDone = 'N' WHERE T_SamplingSoID = {$v['T_SamplingSoID']}";
					$this->db_onedev->query($sql);
				}
			}
		}

		foreach ($prm['trx']['details'] as $k => $v) {
			$sql = "UPDATE so_resultentrydetail SET 
						So_ResultEntryDetailResult = '{$v['result_value']}', 
						So_ResultEntryDetailLastUpdated = NOW(),
						So_ResultEntryDetailLastUpdatedUserID  = {$userid}
					WHERE So_ResultEntryDetailID = {$v['trx_id']}";
			$this->db_onedev->query($sql);
			//echo $this->db_onedev->last_query();
		}

		if ($prm['act'] === 'save' || $prm['act'] === 'val1' || $prm['act'] === 'val2') {
			if ($prm['trx']['status_result'] && count($prm['trx']['status_result']) > 0) {
				$sql = "UPDATE so_resultentry_category_result SET 
						So_ResultEntryCategoryResultIsActive  = 'N',
						So_ResultEntryCategoryResultLastUpdatedUserID = {$userid},
						So_ResultEntryCategoryResultLastUpdated = NOW()
						WHERE
						So_ResultEntryCategoryResultSo_ResultEntryID = {$prm['trx']['trx_id']} AND
						So_ResultEntryCategoryResultIsActive = 'Y'";
				$qry = $this->db_onedev->query($sql);
				if (!$qry) {
					$message = $this->db_onedev->error();
					$message['qry'] = $this->db_onedev->last_query();
					$this->sys_error($message);
					exit;
				}

				foreach ($prm['trx']['status_result'] as $key_data => $value_data) {
					$sql = "INSERT INTO so_resultentry_category_result (
						So_ResultEntryCategoryResultSo_ResultEntryID,
						So_ResultEntryCategoryNonlabConclusionDetailID,
						So_ResultEntryCategoryResultCreated,
						So_ResultEntryCategoryResultCreatedUserID
					)
					VALUES(
						{$prm['trx']['trx_id']},
						'{$value_data['id']}',
						NOW(),
						{$userid} 
					)";
					$qry = $this->db_onedev->query($sql);
					if (!$qry) {
						$message = $this->db_onedev->error();
						$message['qry'] = $this->db_onedev->last_query();
						$this->sys_error($message);
						exit;
					}
				}
			}
		}


		$last_id = $prm['trx']['trx_id'];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID = {$last_id}";
		$data_log_header = $this->db_onedev->query($sql)->row_array();
		$sql = "SELECT * FROM so_resultentrydetail WHERE So_ResultEntryDetailSo_ResultEntryID = {$last_id}";
		$data_log_details = $this->db_onedev->query($sql)->result_array();

		$act = "UPDATE_ACT";
		if ($prm['act'] === 'val1')
			$act = "VALIDATION";
		if ($prm['act'] === 'unval1')
			$act = "UNVALIDATION";

		$this->soresultlog->step_action($act, $last_id, $userid);

		$data_log = json_encode(array('header' => $data_log_header, 'details' => $data_log_details));
		$this->soresultlog->log_result($data_log, $last_id, $userid);

		$result = array(
			"total" => 1,
			"records" => array('status' => 'OK')
		);
		$this->sys_ok($result);
		exit;
	}



	function deletetrx()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];

		$query = "UPDATE so_walklettercourier SET
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

		$data_log = json_encode(array('header' => $data_log_header, 'details' => $data_log_details));
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
			"total" => 1,
			"records" => array('status' => 'OK'),
			"numbering" => $prm['trx_numbering'],
			"id" => $prm['trx_id']
		);
		$this->sys_ok($result);
		exit;
	}

	function savedoctor()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$rst = array();
		$sql = "UPDATE so_resultentry SET So_ResultEntryM_DoctorID = {$prm['selected_doctor']['id']} WHERE So_ResultEntryID = {$prm['selected_detail']['re_id']}";
		$rst = $this->db_onedev->query($sql);

		$act = "CX_DOCTOR";
		$this->soresultlog->step_action($act, $prm['selected_detail']['re_id'], $userid);

		$result = array(
			"total" => 1,
			"records" => $rst
		);
		$this->sys_ok($result);
		exit;
	}

	function savefisik()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$rst = array();
		if ($prm['action'] === 'unval1') {
			$sql = "SELECT IFNULL(Mcu_ResumeValidation, 'N') as status, COUNT(Mcu_ResumeID)
			FROM mcu_resume
			WHERE Mcu_ResumeT_OrderHeaderID = {$prm['trx']['orderid']}
			AND Mcu_ResumeIsActive = 'Y'";
			$qry = $this->db_onedev->query($sql);
			if (!$qry) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				$this->sys_error("Error cek resume individu");
				exit;
			}
			$cek = $qry->row_array();
			if ($cek['status'] == 'Y') {
				$this->sys_error("Resume individu sudah di validasi, unvalidasi resume individu terlebih dahulu ....");
				exit;
			}
		}
		if ($prm['action'] != 'unval1') {
			$data_json = [];
			if ($prm['act'] == 'Fisik Umum' || $prm['act'] == 'Fisik Umum K3' || $prm['act'] == 'Fisik Umum Konsul') {

				if ($prm["riwayats"] && count($prm["riwayats"]) > 0) {
					foreach ($prm["riwayats"] as $v_riwayat) {
						$data_riwayat = json_encode($v_riwayat);
						$sql = "UPDATE so_resultentry_fisik_umum 
								JOIN fisik_template ON FisikTemplateTitle = '{$v_riwayat['title']}' AND FisikTemplateIsActive = 'Y' AND 
								FisikTemplateType = 'Riwayat'
								SET 
								So_ResultEntryFisikUmumDetails = '{$data_riwayat}',
								So_ResultEntryFisikUmumLastUpdated = NOW(),
								So_ResultEntryFisikUmumLastUpdatedUserID = {$userid}
								WHERE
								So_ResultEntryFisikUmumSo_ResultEntryID = {$prm['trx']['re_id']} AND
								So_ResultEntryFisikUmumIsActive = 'Y' AND So_ResultEntryFisikUmumFisikTemplateID = FisikTemplateID 
								";
						$rst = $this->db_onedev->query($sql);
						//if($v_riwayat['title'] == 'KELUHAN')
						//echo $sql;
					}

					$data_json['riwayat'] = $prm["riwayats"];
				}


				if ($prm["fisiks"] && count($prm["fisiks"]) > 0) {
					foreach ($prm["fisiks"] as $v_fisik) {
						$data_fisik = json_encode($v_fisik);
						$sql = "UPDATE so_resultentry_fisik_umum 
								JOIN fisik_template ON FisikTemplateTitle = '{$v_fisik['title']}' AND FisikTemplateIsActive = 'Y' AND 
								FisikTemplateType = 'Fisik'
								SET 
								So_ResultEntryFisikUmumDetails = '{$data_fisik}',
								So_ResultEntryFisikUmumLastUpdated = NOW(),
								So_ResultEntryFisikUmumLastUpdatedUserID = {$userid}
								WHERE
								So_ResultEntryFisikUmumSo_ResultEntryID = {$prm['trx']['re_id']} AND
								So_ResultEntryFisikUmumIsActive = 'Y' AND So_ResultEntryFisikUmumFisikTemplateID = FisikTemplateID 
								";
						$rst = $this->db_onedev->query($sql);
					}

					$data_json['fisik'] = $prm["fisiks"];
				}

				//echo $sql;
				$umum_saran = $prm['umum_saran'];
				if ($umum_saran && $umum_saran != '') {
					$sql = "SELECT COUNT(*) as xcount, So_ResultEntryFisikUmumAdditionalID as id
						FROM so_resultentry_fisik_umum_additional 
						WHERE
						So_ResultEntryFisikUmumAdditionalSo_ResultEntryID = {$prm['trx']['re_id']} AND
						So_ResultEntryFisikUmumAdditionalType = 'saran' AND 
						So_ResultEntryFisikUmumAdditionalIsActive = 'Y'";

					$rst_exist_saran = $this->db_onedev->query($sql)->row_array();

					if ($rst_exist_saran['xcount'] == 0) {
						$sql = "INSERT INTO so_resultentry_fisik_umum_additional (
							So_ResultEntryFisikUmumAdditionalSo_ResultEntryID,
							So_ResultEntryFisikUmumAdditionalType,
							So_ResultEntryFisikUmumAdditionalValue,
							So_ResultEntryFisikUmumAdditionalCreatedUserID,
							So_ResultEntryFisikUmumAdditionalCreated
						)
						VALUES(
							{$prm['trx']['re_id']},
							'saran',
							'{$umum_saran}',
							{		},
							NOW()
						) ";
						//echo $sql;

						$rst = $this->db_onedev->query($sql);
					} else {
						$sql = "UPDATE so_resultentry_fisik_umum_additional SET 
								So_ResultEntryFisikUmumAdditionalValue = '{$umum_saran}',
								So_ResultEntryFisikUmumAdditionalLastUpdated = NOW(),
								So_ResultEntryFisikUmumAdditionalLastUpdatedUserID = {$userid}
								WHERE
								So_ResultEntryFisikUmumAdditionalID = {$rst_exist_saran['id']}";
						$this->db_onedev->query($sql);
					}
				}
			}

			if ($prm['act'] == 'Fisik Umum K3') {
				if ($prm["k3s"] && count($prm["k3s"]) > 0) {
					foreach ($prm["k3s"] as $v_k3) {
						$data_k3s = json_encode($v_k3);
						$sql = "UPDATE so_resultentry_fisik_umum 
								JOIN fisik_template ON FisikTemplateTitle = '{$v_k3['title']}' AND FisikTemplateIsActive = 'Y' AND 
								FisikTemplateType = 'K3'
								SET 
								So_ResultEntryFisikUmumDetails = '{$data_k3s}',
								So_ResultEntryFisikUmumLastUpdated = NOW(),
								So_ResultEntryFisikUmumLastUpdatedUserID = {$userid}
								WHERE
								So_ResultEntryFisikUmumSo_ResultEntryID = {$prm['trx']['re_id']} AND
								So_ResultEntryFisikUmumIsActive = 'Y' AND So_ResultEntryFisikUmumFisikTemplateID = FisikTemplateID 
								";
						$rst = $this->db_onedev->query($sql);
					}

					$data_json['k3s'] = $prm["k3s"];
				}
			}

			//print_r($data_json);
			if ($data_json && count($data_json) > 0) {
				$this->soresultlog->log_result(json_encode($data_json), $prm['trx']['re_id'], $userid);
			}
		}

		if ($prm['action'] === 'val1') {
			$sql = "UPDATE so_resultentry SET 
						So_ResultEntryValidation1 = 'Y', 
						So_ResultEntryStatus = 'VAL1', 
						So_ResultEntryLastUpdated = NOW(), 
						So_ResultEntryLastUpdatedUserID = {$userid} 
					WHERE 
						So_ResultEntryID = {$prm['trx']['re_id']}";
			$this->db_onedev->query($sql);
			//echo $this->db_onedev->last_query();
			//$alkelainans = $this->kesimpulanfisik->generate_kelainan_fisik($prm['trx']['re_id']);
			/*$this->load->library('Etlfisik');
			$orderID = $prm['trx']['orderid'];
        	$etl = $this->etlfisik->generate_all_fisik($orderID,$userid);
			if($etl){
				$this->etlfisik->generate_summaries($orderID,$prm['trx']['re_id'],$userid,0);
			}*/

			//$this->add_action_log("VALIDATION",$prm['trx']['re_id'],$userid);
		}

		if ($prm['action'] === 'unval1') {
			$sql = "UPDATE so_resultentry SET 
						So_ResultEntryValidation1 = 'N', 
						So_ResultEntryStatus = 'NEW', 
						So_ResultEntryLastUpdated = NOW(), 
						So_ResultEntryLastUpdatedUserID = {$userid} 
					WHERE 
						So_ResultEntryID = {$prm['trx']['re_id']}";
			$this->db_onedev->query($sql);

			//$this->add_action_log("UNVALIDATION",$prm['trx']['re_id'],$userid);
		}


		$act = "UPDATE_ACT";
		if ($prm['action'] === 'unval1')
			$act = "UNVALIDATION";
		if ($prm['action'] === 'val1')
			$act = "VALIDATION";

		$this->soresultlog->step_action($act, $prm['trx']['re_id'], $userid);



		$result = array(
			"total" => 1,
			"records" => $rst
		);
		$this->sys_ok($result);
		exit;
	}

	function add_action_log($act, $re_id, $userid)
	{
		$sql = "INSERT INTO so_reactionlog(
					So_REActionLogDate,
					So_REActionLogSo_ResultEntryID,
					So_REActionLogAction,
					So_REActionLogUserID
				)
				VALUES(
					NOW(),
					{$re_id},
					'{$act}',
					{$userid}
				)";
		$save = $this->db_onedev->query($sql);
		if (!$save) {
			$this->sys_error("Error Save Log Action");
			exit;
		}

		return true;
	}


	function gettemplate()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$rst = array();

		$sql = "SELECT So_TemplateLabelID as id, So_TemplateLabelName as label, '' as details
					FROM so_templatelabel
					WHERE
					So_TemplateLabelM_DoctorID = {$prm['doctor_id']} AND
					So_TemplateLabelT_TestID = {$prm['test_id']} AND 
					So_TemplateLabelSo_TemplateID = {$prm['template_id']} AND
					So_TemplateLabelIsActive = 'Y'";
		//echo $sql;
		$rst = $this->db_onedev->query($sql)->result();

		if (!$rst) {
			$sql = "SELECT So_TemplateLabelID as id, So_TemplateLabelName as label, '' as details
					FROM so_templatelabel
					WHERE
					So_TemplateLabelM_DoctorID = 0 AND
					So_TemplateLabelT_TestID = {$prm['test_id']} AND 
					So_TemplateLabelSo_TemplateID = {$prm['template_id']} AND
					So_TemplateLabelIsActive = 'Y'";
			$rst = $this->db_onedev->query($sql)->result();
		}

		if ($rst) {
			foreach ($rst as $k => $v) {
				$sql = "SELECT so_templatevalueid as id, So_TemplateValueText as value, So_TemplateValueSo_TemplateDetailID as template_detail_id
						FROM so_templatevalue
						JOIN so_templatedetail ON So_TemplateValueSo_TemplateDetailID = So_TemplateDetailID AND
						 So_TemplateDetailM_LangID = {$prm['language_id']}
						WHERE
						So_TemplateValueSo_TemplateLabelID = {$v->id} AND So_TemplateValueIsActive = 'Y' ";
				//echo $sql;
				$v->details = $this->db_onedev->query($sql)->result();
			}
		}

		$result = array(
			"total" => 1,
			"records" => $rst
		);
		$this->sys_ok($result);
		exit;
	}

	function printcount()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$trx_id = $prm["trx_id"];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID = '{$trx_id}'";
		$orderdetail_id = $this->db_onedev->query($sql)->row()->So_ResultEntryT_OrderDetailID;

		$sql = "UPDATE t_orderdetail SET 
					T_OrderDetailPrintCount = T_OrderDetailPrintCount + 1,
					T_OrderDetailPrintBy = {$userid},
					T_OrderDetailPrintTime = NOW()
				WHERE 
					T_OrderDetailID = '{$orderdetail_id}'";
		$this->db_onedev->query($sql);
		$result = array(
			"total" => 1,
			"records" => $prm
		);
		$this->sys_ok($result);
		exit;
	}


	function save_flagprint()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}

		$rst_data = array('status' => 'OK');

		$prm = $this->sys_input;
		$row = $prm['row'];
		$selected_trx = $prm['selected_trx'];
		$userid = $this->sys_user["M_UserID"];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID  = '{$selected_trx['re_id']}'";
		//echo $sql;
		$re_langid = $this->db_onedev->query($sql)->row()->So_ResultEntryM_LangID;
		if ($re_langid == $prm['selected_trx']['language_id']) {
			$sql = "UPDATE so_resultentrydetail SET 
						So_ResultEntryDetailFlagPrint = '{$row['flag_print']}', 
						So_ResultEntryDetailUserID = {$userid}
					WHERE So_ResultEntryDetailID = {$row['trx_id']}";
			//echo $sql;
			$this->db_onedev->query($sql);
		} else {
			$sql = "UPDATE so_resultentrydetail_other SET 
						So_ResultEntryDetailOtherFlagPrint = '{$row['flag_print']}', 
						So_ResultEntryDetailOtherUserID = {$userid}
					WHERE So_ResultEntryDetailOtherID = {$row['trx_id']}";
			//echo $sql;
			$this->db_onedev->query($sql);
		}


		$result = array(
			"total" => 1,
			"records" => $rst_data
		);
		$this->sys_ok($result);

		exit;
	}

	function getrstbylang()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$x_langid = $prm['lang']['id'];
		$x_reid = $prm['detail']['trx_id'];
		$sel_trx = $prm['selected_trx'];
		$sql = "SELECT * FROM so_resultentry WHERE So_ResultEntryID  = '{$x_reid}'";
		//echo $sql;
		$re_langid = $this->db_onedev->query($sql)->row()->So_ResultEntryM_LangID;
		if ($sel_trx['group_name'] != 'pemeriksaan fisik') {
			$sql = 	"SELECT 
						So_ResultEntryDetailID as trx_id,
						So_ResultEntryDetailNonlab_TemplateDetailID as template_detail_id,
						So_ResultEntryDetailNonlab_TemplateDetailName as result_label,
						IFNULL(So_ResultEntryDetailResult,'') as result_value,
						'N' as flag_print,
						'' as note
					FROM so_resultentrydetail
					JOIN so_resultentry ON So_ResultEntryDetailSo_ResultEntryID = So_ResultEntryID
					WHERE
					So_ResultEntryDetailSo_ResultEntryID = {$x_reid} AND So_ResultEntryDetailisActive = 'Y'
					";
		}



		//echo $sql;
		$rst = $this->db_onedev->query($sql)->result_array();
		$result = array(
			"total" => 1,
			"records" => $rst
		);
		$this->sys_ok($result);
		exit;
	}

	function save_signature()
	{
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$prm = $this->sys_input;
		$userid = $this->sys_user["M_UserID"];
		$trx = $prm['trx'];

		$home_dir = "/home/one/project/one/";
		$target_dir = $home_dir . "one-media/one-signature/" . date("Y") . "/";


		if (!file_exists($target_dir)) {
			mkdir($target_dir, 0755, true);
		}

		$target_path = $target_dir . $trx['ordernumber'] . ".png";
		if (file_exists($target_path)) {
			$timestampx = date('YmdHis');
			$new_path = $target_dir . $trx['ordernumber'] . "_" . $timestampx . ".png";
			$xsource = fopen($target_path, 'r');
			$xdestination = fopen($new_path, 'w');

			stream_copy_to_stream($xsource, $xdestination);

			fclose($xsource);
			fclose($xdestination);

			$new_path =  "/" . str_replace($home_dir, "", $new_path);
			$sql = "UPDATE so_signature SET So_SignatureIsActive = 'N', So_SignatureUrl = '{$new_path}' ,So_SignatureUserID = {$userid}
			WHERE So_SignatureT_OrderHeaderID = {$trx['trx_id']} AND So_SignatureIsActive = 'Y'";
			$this->db_onedev->query($sql);
		}
		//echo $target_path;
		$file_png = $this->base64_to_jpeg($prm['data'], $target_path);
		$xurl =  "/" . str_replace($home_dir, "", $target_path);
		if ($file_png) {


			$sql = "INSERT INTO so_signature (
				So_SignatureT_OrderHeaderID,
				So_SignatureUrl,
				So_SignatureCreated,
				So_SignatureUserID
			)
			VALUES(
				{$trx['trx_id']},
				'{$xurl}',
				NOW(),
				{$userid}
			)";
			$this->db_onedev->query($sql);
		}
		$xurl = $xurl . "?=" . date('Ymdhhis');
		$result = array(
			"data" => $xurl
		);
		$this->sys_ok($result);
		exit;
	}

	function searchdoctor()
	{
		if (! $this->isLogin) {
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
			$q['search'] = "{$prm['search']}";
		}

		// QUERY TOTAL
		$sql = "SELECT count(*) as total
				FROM m_doctor
				WHERE
				M_DoctorName like ?
				AND M_DoctorIsActive = 'Y'";
		$query = $this->db_onedev->query($sql, $q['search']);
		//echo $query;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
		} else {
			$this->sys_error_db("m_doctor count", $this->db_onedev);
			exit;
		}

		$sql = "
			SELECT M_DoctorID as id, 
			CONCAT(IF(ISNULL(M_DoctorPrefix),'',M_DoctorPrefix),IF(ISNULL(M_DoctorPrefix2),'',M_DoctorPrefix2),' ',M_DoctorName,IF(ISNULL(M_DoctorSuffix),'',M_DoctorSuffix),IF(ISNULL(M_DoctorSuffix2),'',M_DoctorSuffix2)) as name
			FROM m_doctor
			WHERE
			CONCAT(IF(ISNULL(M_DoctorPrefix),'',M_DoctorPrefix),IF(ISNULL(M_DoctorPrefix2),'',M_DoctorPrefix2),' ',M_DoctorName,IF(ISNULL(M_DoctorSuffix),'',M_DoctorSuffix),IF(ISNULL(M_DoctorSuffix2),'',M_DoctorSuffix2)) like CONCAT('%',?,'%')
			AND M_DoctorIsActive = 'Y'
			ORDER BY M_DoctorName ASC
		  ";
		$query = $this->db_onedev->query($sql, array($q['search']));

		if ($query) {
			$rows = $query->result_array();
			//echo $this->db_onedev->last_query();
			$result = array("total" => $tot_count, "records" => $rows, "total_display" => sizeof($rows));
			$this->sys_ok($result);
		} else {
			$this->sys_error_db("m_city rows", $this->db_onedev);
			exit;
		}
	}

	function base64_to_jpeg($base64_string, $output_file)
	{
		// open the output file for writing
		$ifp = fopen($output_file, 'wb');

		// split the string on commas
		// $data[ 0 ] == "data:image/png;base64"
		// $data[ 1 ] == <actual base64 string>
		$data = explode(',', $base64_string);

		// we could add validation here with ensuring count( $data ) > 1
		fwrite($ifp, base64_decode($data[1]));

		// clean up the file resource
		fclose($ifp);

		return $output_file;
	}
	/*
	-- Adminer 4.7.5 MySQL dump

	SET NAMES utf8;
	SET time_zone = '+00:00';
	SET foreign_key_checks = 0;
	SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

	DROP TABLE IF EXISTS `fisik_templateresult`;
	CREATE TABLE `fisik_templateresult` (
	  `Fisik_TemplateResultID` int(11) NOT NULL AUTO_INCREMENT,
	  `Fisik_TemplateResultM_LangID` int(11) NOT NULL,
	  `Fisik_TemplateResultType` varchar(15) NOT NULL,
	  `Fisik_TemplateResultText` text NOT NULL,
	  `Fisik_TemplateResultIsActive` char(1) NOT NULL DEFAULT 'Y',
	  PRIMARY KEY (`Fisik_TemplateResultID`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;

	INSERT INTO `fisik_templateresult` (`Fisik_TemplateResultID`, `Fisik_TemplateResultM_LangID`, `Fisik_TemplateResultType`, `Fisik_TemplateResultText`, `Fisik_TemplateResultIsActive`) VALUES
	(1,	1,	'riwayat',	'[{\"title\":\"KELUHAN SAAT INI\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada keluhan\",\"flag_normal\":\"Y\",\"show_all\":\"N\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_1\",\"id\":\"1\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Demam\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_2\",\"id\":\"2\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Nyeri Kepala\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_3\",\"id\":\"3\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Batuk dan influensa\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_4\",\"id\":\"4\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Batuk lebih dari 1 bulan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_5\",\"id\":\"5\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Pusing atau rasa berputar (vertigo)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_6\",\"id\":\"6\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lemas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_7\",\"id\":\"7\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan mata atau penglihatan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_8\",\"id\":\"8\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Mata berkunang-kunang\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_9\",\"id\":\"9\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan pendengaran\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_10\",\"id\":\"10\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Nyeri dada\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_11\",\"id\":\"11\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Sesak Napas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_12\",\"id\":\"12\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Sakit Jantung\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_13\",\"id\":\"13\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Hipertensi / tekanan darah tinggi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_14\",\"id\":\"14\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Tidak nafsu makan lebih dari 1 bulan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_15\",\"id\":\"15\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gastritis (maag)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_16\",\"id\":\"16\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Nyeri perut atau gangguan pencernaan lainnya\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_17\",\"id\":\"17\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Haemorrhoid (wasir/ambeien)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_18\",\"id\":\"18\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Diare berulang / lama (kronis)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_19\",\"id\":\"19\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Sakit pinggang\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_20\",\"id\":\"20\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan berkemih\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_21\",\"id\":\"21\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan ginjal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_22\",\"id\":\"22\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan pada alat reproduksi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_23\",\"id\":\"23\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Nyeri otot dan sendi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_24\",\"id\":\"24\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kesemutan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_25\",\"id\":\"25\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Bengkak pada kaki atau anggota badan lainnya\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_26\",\"id\":\"26\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gatal atau gangguan kulit lainnya\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_27\",\"id\":\"27\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Benjolan abnormal pada bagian tubuh\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_keluhan\",\"id_code\":\"fisik_keluhan_28\",\"id\":\"28\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Keluhan lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"RIWAYAT POBIA\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada pobia\",\"flag_normal\":\"Y\",\"show_all\":\"N\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_1\",\"id\":\"1\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Rasa takut yang berlebihan (phobia)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_2\",\"id\":\"2\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Takut ketinggian\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_3\",\"id\":\"3\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Takut di ruangan gelap\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_4\",\"id\":\"4\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Takut melihat darah\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_5\",\"id\":\"5\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Takut di ruang sempit\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_6\",\"id\":\"6\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Takut berada di tengah laut\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_7\",\"id\":\"7\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Takut naik pesawat/helikopter\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_pobia\",\"id_code\":\"fisik_pobia_8\",\"id\":\"8\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Mabuk laut atau mabuk perjalanan\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"RIWAYAT PENYAKIT\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada riwayat penyakit\",\"flag_normal\":\"Y\",\"show_all\":\"N\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"System Pencernaan\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_1\",\"id\":\"1\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gastritis (maag)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_2\",\"id\":\"2\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Hepatitis (penyakit hati/kuning)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_3\",\"id\":\"3\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Batu empedu\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_4\",\"id\":\"4\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Demam typoid\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_5\",\"id\":\"5\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Haemorrhoid (wasir/ambeien)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_6\",\"id\":\"6\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Operasi saluran pencernaan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pencernaan\",\"id_code\":\"fisik_penyakit_7\",\"id\":\"7\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Pencernaan )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"System Pernafasan\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pernafasan\",\"id_code\":\"fisik_penyakit_8\",\"id\":\"8\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Asma\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pernafasan\",\"id_code\":\"fisik_penyakit_9\",\"id\":\"9\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Tuberculosa (TBC)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pernafasan\",\"id_code\":\"fisik_penyakit_10\",\"id\":\"10\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Batuk lebih dari 1 bulan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pernafasan\",\"id_code\":\"fisik_penyakit_11\",\"id\":\"11\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Pneumonia\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Pernafasan\",\"id_code\":\"fisik_penyakit_12\",\"id\":\"12\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Pernafasan )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"System cardiovaskuler\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System cardiovaskuler\",\"id_code\":\"fisik_penyakit_13\",\"id\":\"13\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit jantung\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System cardiovaskuler\",\"id_code\":\"fisik_penyakit_14\",\"id\":\"14\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Hipertensi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System cardiovaskuler\",\"id_code\":\"fisik_penyakit_15\",\"id\":\"15\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Stroke\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System cardiovaskuler\",\"id_code\":\"fisik_penyakit_16\",\"id\":\"16\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Pasang pen atau ring\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System cardiovaskuler\",\"id_code\":\"fisik_penyakit_17\",\"id\":\"17\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Anemia\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System cardiovaskuler\",\"id_code\":\"fisik_penyakit_18\",\"id\":\"18\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Cardiovaskuler )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Sistem Saraf\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Saraf\",\"id_code\":\"fisik_penyakit_19\",\"id\":\"19\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Vertigo (pusing memutar)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Saraf\",\"id_code\":\"fisik_penyakit_20\",\"id\":\"20\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Epilepsi (ayan), kejang, pingsan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Saraf\",\"id_code\":\"fisik_penyakit_21\",\"id\":\"21\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Polio\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Saraf\",\"id_code\":\"fisik_penyakit_22\",\"id\":\"22\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan mental / kejiwaan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Saraf\",\"id_code\":\"fisik_penyakit_23\",\"id\":\"23\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Mengalami cidera kepala\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Saraf\",\"id_code\":\"fisik_penyakit_24\",\"id\":\"24\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Syaraf )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Sistem Penglihatan\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Penglihatan\",\"id_code\":\"fisik_penyakit_25\",\"id\":\"25\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kacamata Minus\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Penglihatan\",\"id_code\":\"fisik_penyakit_26\",\"id\":\"26\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kacamata (+)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Penglihatan\",\"id_code\":\"fisik_penyakit_27\",\"id\":\"27\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kacamata Silender\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Penglihatan\",\"id_code\":\"fisik_penyakit_28\",\"id\":\"28\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Trauma\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Penglihatan\",\"id_code\":\"fisik_penyakit_29\",\"id\":\"29\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Fotopobia\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Penglihatan\",\"id_code\":\"fisik_penyakit_30\",\"id\":\"30\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Penglihatan )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Sistem Pendengaran/THT\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_31\",\"id\":\"31\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan Pendengaran\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_32\",\"id\":\"32\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Sinusitis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_33\",\"id\":\"33\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Rhinitis Allergika\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_34\",\"id\":\"34\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Amandel/tonsilitis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_35\",\"id\":\"35\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Otitis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_36\",\"id\":\"36\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Trauma\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Sistem Pendengaran/THT\",\"id_code\":\"fisik_penyakit_37\",\"id\":\"37\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Pendengaran/THT )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Ginjal & Saluran Kemih\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Ginjal & Saluran Kemih\",\"id_code\":\"fisik_penyakit_38\",\"id\":\"38\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Batu ginjal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Ginjal & Saluran Kemih\",\"id_code\":\"fisik_penyakit_39\",\"id\":\"39\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit ginjal (akut/kronis)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Ginjal & Saluran Kemih\",\"id_code\":\"fisik_penyakit_40\",\"id\":\"40\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Infeksi saluran kemih\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Ginjal & Saluran Kemih\",\"id_code\":\"fisik_penyakit_41\",\"id\":\"41\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Operasi saluran kemih\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Ginjal & Saluran Kemih\",\"id_code\":\"fisik_penyakit_42\",\"id\":\"42\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Ginjal & Saluran Kemih )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Tulang, Sendi & Otot\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_43\",\"id\":\"43\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Patah tulang\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_44\",\"id\":\"44\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Radang sendi (arthritis)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_45\",\"id\":\"45\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Rheumatik\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_46\",\"id\":\"46\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kecelakaan / cidera / trauma / luka parah\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_47\",\"id\":\"47\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Nyeri otot lebih dari 1 bulan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_48\",\"id\":\"48\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Nyeri punggung / back pain\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Tulang, Sendi & Otot\",\"id_code\":\"fisik_penyakit_49\",\"id\":\"49\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Tulang, Sendi & Otot )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Kulit & system reproduksi\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_50\",\"id\":\"50\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan alat reproduksi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_51\",\"id\":\"51\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kista / tumor / kanker alat reproduksi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_52\",\"id\":\"52\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit Akibat Hubungan Sex\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_53\",\"id\":\"53\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"HIV\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_54\",\"id\":\"54\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lepra\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_55\",\"id\":\"55\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit kulit yang lama / kronis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Kulit & system reproduksi\",\"id_code\":\"fisik_penyakit_56\",\"id\":\"56\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Kulit & Sistem Reproduksi )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"System Endokrin\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Endokrin\",\"id_code\":\"fisik_penyakit_57\",\"id\":\"57\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Diabetes Militus (Kencing manis)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Endokrin\",\"id_code\":\"fisik_penyakit_58\",\"id\":\"58\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan tiroid (gondok, hipo/hipertiroid)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"System Endokrin\",\"id_code\":\"fisik_penyakit_59\",\"id\":\"59\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Sistem Endokrin )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Allergi\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Allergi\",\"id_code\":\"fisik_penyakit_60\",\"id\":\"60\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Allergi Obat\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Allergi\",\"id_code\":\"fisik_penyakit_61\",\"id\":\"61\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Allergi Makanan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Allergi\",\"id_code\":\"fisik_penyakit_62\",\"id\":\"62\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Allergi Hirupan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Allergi\",\"id_code\":\"fisik_penyakit_63\",\"id\":\"63\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Allergi Kontak\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Allergi\",\"id_code\":\"fisik_penyakit_64\",\"id\":\"64\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Allergi )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Penyakit daerah tropis\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit daerah tropis\",\"id_code\":\"fisik_penyakit_65\",\"id\":\"65\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"DHF / Demam berdarah\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit daerah tropis\",\"id_code\":\"fisik_penyakit_66\",\"id\":\"66\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Malaria\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit daerah tropis\",\"id_code\":\"fisik_penyakit_67\",\"id\":\"67\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Typoid\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit daerah tropis\",\"id_code\":\"fisik_penyakit_68\",\"id\":\"68\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Penyakit daerah tropis )\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Penyakit lainnya\",\"details\":[{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit lainnya\",\"id_code\":\"fisik_penyakit_69\",\"id\":\"69\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Tumor / kanker\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit lainnya\",\"id_code\":\"fisik_penyakit_70\",\"id\":\"70\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Leukimia\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit lainnya\",\"id_code\":\"fisik_penyakit_71\",\"id\":\"71\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Pernah operasi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakit\",\"segment_name\":\"Penyakit lainnya\",\"id_code\":\"fisik_penyakit_72\",\"id\":\"72\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lain-lain ( Penyakit lainnya )\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"RIWAYAT PENYAKIT KELUARGA\",\"subtitle\":\"Apakah orang tua, kakek nenek, saudara kandung atau keluarga dekat menderita penyakit dibawah ini ?\",\"label_flag_normal\":\"Tidak ada riwayat penyakit\",\"flag_normal\":\"Y\",\"show_all\":\"N\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_1\",\"id\":\"1\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Diabetes Millitus\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_2\",\"id\":\"2\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Hypertensi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_3\",\"id\":\"3\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Stroke\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_4\",\"id\":\"4\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit Jantung\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_5\",\"id\":\"5\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit Ginjal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_6\",\"id\":\"6\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"TBC\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_7\",\"id\":\"7\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Lepra\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_8\",\"id\":\"8\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Penyakit hati / hepatitis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_9\",\"id\":\"9\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Epilepsi (ayan)\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_10\",\"id\":\"10\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Gangguan jiwa\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_11\",\"id\":\"11\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kanker / tumor ganas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_12\",\"id\":\"12\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Autoimmum / Rheumatik / Lupus\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_penyakitkeluarga\",\"id_code\":\"fisik_penyakitkeluarga_13\",\"id\":\"13\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Asma\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"RIWAYAT KEBIASAAN HIDUP\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada riwayat kebiasaan hidup\",\"flag_normal\":\"Y\",\"show_all\":\"N\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Minum alkohol\",\"details\":[{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Minum alkohol\",\"id_code\":\"fisik_kebiasaanhidup_1\",\"id\":\"1\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Tidak\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Minum alkohol\",\"id_code\":\"fisik_kebiasaanhidup_2\",\"id\":\"2\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kadang-kadang\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Minum alkohol\",\"id_code\":\"fisik_kebiasaanhidup_3\",\"id\":\"3\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Rutin\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Olahraga\",\"details\":[{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Olahraga\",\"id_code\":\"fisik_kebiasaanhidup_4\",\"id\":\"4\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Tidak\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Olahraga\",\"id_code\":\"fisik_kebiasaanhidup_5\",\"id\":\"5\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kadang-kadang\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Olahraga\",\"id_code\":\"fisik_kebiasaanhidup_6\",\"id\":\"6\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Rutin\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Merokok\",\"details\":[{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Merokok\",\"id_code\":\"fisik_kebiasaanhidup_8\",\"id\":\"8\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Tidak\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Merokok\",\"id_code\":\"fisik_kebiasaanhidup_9\",\"id\":\"9\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Kadang-kadang\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_kebiasaanhidup\",\"segment_name\":\"Merokok\",\"id_code\":\"fisik_kebiasaanhidup_10\",\"id\":\"10\",\"lang_id\":\"1\",\"type\":\"RIWAYAT\",\"category\":\"UMUM\",\"label\":\"Rutin\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"RIWAYAT KONSUMSI OBAT TERATUR\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"N\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"fisik_konsumsiobatteratur\",\"id_code\":\"fisik_konsumsiobatteratur_1\",\"id\":\"1\",\"lang_id\":\"1\",\"type\":\"UMUM\",\"category\":\"RIWAYAT\",\"label\":\"Obat anti Diabetes Millitus\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_konsumsiobatteratur\",\"id_code\":\"fisik_konsumsiobatteratur_2\",\"id\":\"2\",\"lang_id\":\"1\",\"type\":\"UMUM\",\"category\":\"RIWAYAT\",\"label\":\"Obat anti hypertensi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"fisik_konsumsiobatteratur\",\"id_code\":\"fisik_konsumsiobatteratur_3\",\"id\":\"3\",\"lang_id\":\"1\",\"type\":\"UMUM\",\"category\":\"RIWAYAT\",\"label\":\"Obat lainnya\",\"chx\":false,\"value\":\"\"}]}]',	'Y'),
	(2,	1,	'fisik',	'[{\"title\":\"TANDA VITAL\",\"show_all\":\"Y\",\"type_form\":\"VXX\",\"details\":[{\"table_name\":\"tanda_vital\",\"id_code\":\"tanda_vital_1\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Denyut nadi\",\"value\":\"\",\"unit\":\"x/menit\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"tanda_vital\",\"id_code\":\"tanda_vital_2\",\"lang_id\":\"1\",\"type\":\"vxx-xx\",\"label\":\"Ritme denyut nadi\",\"value\":\"\",\"unit\":\"\",\"chx_y\":true,\"label_y\":\"Reguler\",\"chx_x\":false,\"label_x\":\"Ireguler\"},{\"table_name\":\"tanda_vital\",\"id_code\":\"tanda_vital_3\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Laju pernafasan\",\"value\":\"\",\"unit\":\"x/menit\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"tanda_vital\",\"id_code\":\"tanda_vital_4\",\"lang_id\":\"1\",\"type\":\"vxx-xx\",\"label\":\"Pola nafas\",\"value\":\"\",\"unit\":\"\",\"chx_y\":true,\"label_y\":\"Normal\",\"chx_x\":false,\"label_x\":\"Tidak normal\"},{\"table_name\":\"tanda_vital\",\"id_code\":\"tanda_vital_5\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Tekanan Darah\",\"value\":\"\",\"unit\":\"mmHg\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"tanda_vital\",\"id_code\":\"tanda_vital_4\",\"lang_id\":\"1\",\"type\":\"vxx-xx\",\"label\":\"Suhu\",\"value\":\"\",\"unit\":\"\",\"chx_y\":true,\"label_y\":\"Normal\",\"chx_x\":false,\"label_x\":\"Demam\"}]},{\"title\":\"STATUS GIZI\",\"show_all\":\"Y\",\"type_form\":\"VXX\",\"details\":[{\"table_name\":\"status_gizi\",\"id_code\":\"status_gizi_1\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Berat badan\",\"value\":\"\",\"unit\":\"kg\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"status_gizi\",\"id_code\":\"status_gizi_2\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Tinggi badan\",\"value\":\"\",\"unit\":\"cm\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"status_gizi\",\"id_code\":\"status_gizi_3\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Lingkar perut\",\"value\":\"\",\"unit\":\"cm\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"status_gizi\",\"id_code\":\"status_gizi_4\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"BMI\",\"value\":\"\",\"unit\":\"\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"},{\"table_name\":\"status_gizi\",\"id_code\":\"status_gizi_5\",\"lang_id\":\"1\",\"type\":\"vxx-v\",\"label\":\"Lingkar pinggang / Panggul\",\"value\":\"\",\"unit\":\"cm\",\"chx_y\":false,\"label_y\":\"\",\"chx_x\":false,\"label_x\":\"\"}]},{\"title\":\"KEADAAN UMUM\",\"show_all\":\"Y\",\"type_form\":\"XXV\",\"details\":[{\"table_name\":\"keadaan_umum\",\"id_code\":\"keadaan_umum_1\",\"lang_id\":\"1\",\"type\":\"\",\"label\":\"Kesadaran\",\"value\":\"\",\"unit\":\"\",\"chx_y\":true,\"label_y\":\"Normal\",\"chx_x\":false,\"label_x\":\"Tidak Normal\"},{\"table_name\":\"keadaan_umum\",\"id_code\":\"keadaan_umum_2\",\"lang_id\":\"1\",\"type\":\"\",\"label\":\"Sikap & tingkah laku\",\"value\":\"\",\"unit\":\"\",\"chx_y\":true,\"label_y\":\"Normal\",\"chx_x\":false,\"label_x\":\"Tidak Normal\"},{\"table_name\":\"keadaan_umum\",\"id_code\":\"keadaan_umum_3\",\"lang_id\":\"1\",\"type\":\"\",\"label\":\"Kontak psikis / perhatian\",\"value\":\"\",\"unit\":\"\",\"chx_y\":true,\"label_y\":\"Normal\",\"chx_x\":false,\"label_x\":\"Tidak Normal\"}]},{\"title\":\"KEPALA WAJAH\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"kepala_wajah\",\"id_code\":\"kepala_wajah_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"kepala_wajah\",\"id_code\":\"kepala_wajah_2\",\"lang_id\":\"1\",\"label\":\"Deformitas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"kepala_wajah\",\"id_code\":\"kepala_wajah_3\",\"lang_id\":\"1\",\"label\":\"Luka\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"kepala_wajah\",\"id_code\":\"kepala_wajah_4\",\"lang_id\":\"1\",\"label\":\"Tumor\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"kepala_wajah\",\"id_code\":\"kepala_wajah_5\",\"lang_id\":\"1\",\"label\":\"Kepala benjol\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"kepala_wajah\",\"id_code\":\"kepala_wajah_6\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"MATA\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"mata\",\"id_code\":\"mata_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_2\",\"lang_id\":\"1\",\"label\":\"Strabismus\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_3\",\"lang_id\":\"1\",\"label\":\"Hiperemis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_4\",\"lang_id\":\"1\",\"label\":\"Ikterik\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_5\",\"lang_id\":\"1\",\"label\":\"Sekret\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_6\",\"lang_id\":\"1\",\"label\":\"Pterigium\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_7\",\"lang_id\":\"1\",\"label\":\"Lensa keruh\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_8\",\"lang_id\":\"1\",\"label\":\"Anemis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_9\",\"lang_id\":\"1\",\"label\":\"Merah\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mata\",\"id_code\":\"mata_10\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"PERSEPSI WARNA\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"persepsi_warna\",\"id_code\":\"persepsi_warna_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"persepsi_warna\",\"id_code\":\"persepsi_warna_2\",\"lang_id\":\"1\",\"label\":\"Buta warna parsial\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"persepsi_warna\",\"id_code\":\"persepsi_warna_3\",\"lang_id\":\"1\",\"label\":\"Hiperemis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"persepsi_warna\",\"id_code\":\"persepsi_warna_4\",\"lang_id\":\"1\",\"label\":\"Buta warna total\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"VISUS JAUH\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Tanpa kacamata\",\"details\":[{\"table_name\":\"visus_jauh\",\"segment_name\":\"Tanpa kacamata\",\"id_code\":\"visus_jauh_1\",\"lang_id\":\"1\",\"label\":\"-OD\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"visus_jauh\",\"segment_name\":\"Tanpa kacamata\",\"id_code\":\"visus_jauh_2\",\"lang_id\":\"1\",\"label\":\"-OS\",\"chx\":true,\"value\":\"\"}]},{\"name\":\"Dengan kacamata\",\"details\":[{\"table_name\":\"visus_jauh\",\"segment_name\":\"Dengan kacamata\",\"id_code\":\"visus_jauh_3\",\"lang_id\":\"1\",\"label\":\"-OD\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"visus_jauh\",\"segment_name\":\"Dengan kacamata\",\"id_code\":\"visus_jauh_4\",\"lang_id\":\"1\",\"label\":\"-Os\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"TELINGA\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"telinga\",\"id_code\":\"telinga_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"telinga\",\"id_code\":\"telinga_2\",\"lang_id\":\"1\",\"label\":\"Tanda infeksi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"telinga\",\"id_code\":\"telinga_3\",\"lang_id\":\"1\",\"label\":\"Serumen\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"telinga\",\"id_code\":\"telinga_4\",\"lang_id\":\"1\",\"label\":\"Perforasi MT\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"telinga\",\"id_code\":\"telinga_5\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"HIDUNG\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"hidung\",\"id_code\":\"hidung_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"hidung\",\"id_code\":\"hidung_2\",\"lang_id\":\"1\",\"label\":\"Hiperemis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"hidung\",\"id_code\":\"hidung_3\",\"lang_id\":\"1\",\"label\":\"Oedem\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"hidung\",\"id_code\":\"hidung_4\",\"lang_id\":\"1\",\"label\":\"Deviasi Septum\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"MULUT\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Mukosa rongga mulut\",\"details\":[{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Mukosa rongga mulut\",\"id_code\":\"mukosa_rongga_mulut_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Mukosa rongga mulut\",\"id_code\":\"mukosa_rongga_mulut_2\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Tenggorokan\",\"details\":[{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Tenggorokan\",\"id_code\":\"tenggorokan_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Tenggorokan\",\"id_code\":\"tenggorokan_2\",\"lang_id\":\"1\",\"label\":\"Hiperemis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Tenggorokan\",\"id_code\":\"tenggorokan_3\",\"lang_id\":\"1\",\"label\":\"Tonsil hipertrofi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Tenggorokan\",\"id_code\":\"tenggorokan_4\",\"lang_id\":\"1\",\"label\":\"Deviasi uvula\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"mukosa_rongga_mulut\",\"segment_name\":\"Tenggorokan\",\"id_code\":\"tenggorokan_5\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"LEHER\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"leher\",\"id_code\":\"hidung_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"leher\",\"id_code\":\"leher_2\",\"lang_id\":\"1\",\"label\":\"Spasme\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"leher\",\"id_code\":\"leher_3\",\"lang_id\":\"1\",\"label\":\"Pembesaran tiroid\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"leher\",\"id_code\":\"leher_4\",\"lang_id\":\"1\",\"label\":\"Pembesaran kelenjar limfe\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"leher\",\"id_code\":\"leher_5\",\"lang_id\":\"1\",\"label\":\"JVP meningkat\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"leher\",\"id_code\":\"leher_5\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"THORAX / DADA\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"thorax\",\"id_code\":\"thorax_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"thorax\",\"id_code\":\"thorax_2\",\"lang_id\":\"1\",\"label\":\"Deformitas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"thorax\",\"id_code\":\"thorax_3\",\"lang_id\":\"1\",\"label\":\"Tumor mammae\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"thorax\",\"id_code\":\"thorax_4\",\"lang_id\":\"1\",\"label\":\"Gineko mastia\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"thorax\",\"id_code\":\"thorax_5\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"PARU-PARU\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"paru\",\"id_code\":\"paru_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"}]},{\"title\":\"PEMERIKSAAN FISIK\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"pemeriksaan_fisik\",\"id_code\":\"pemeriksaan_fisik_1\",\"lang_id\":\"1\",\"label\":\"Gerak asimetris\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"pemeriksaan_fisik\",\"id_code\":\"pemeriksaan_fisik_2\",\"lang_id\":\"1\",\"label\":\"Perkusi abnormal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"pemeriksaan_fisik\",\"id_code\":\"pemeriksaan_fisik_3\",\"lang_id\":\"1\",\"label\":\"Suara nafas tambahan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"pemeriksaan_fisik\",\"id_code\":\"pemeriksaan_fisik_4\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"JANTUNG\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"JVP\",\"details\":[{\"table_name\":\"jantung\",\"segment_name\":\"JVP\",\"id_code\":\"jantung_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"jantung\",\"segment_name\":\"JVP\",\"id_code\":\"jantung_2\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Apex\",\"details\":[{\"table_name\":\"jantung\",\"segment_name\":\"Apex\",\"id_code\":\"jantung_3\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"jantung\",\"segment_name\":\"Apex\",\"id_code\":\"jantung_4\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Suara jantung\",\"details\":[{\"table_name\":\"jantung\",\"segment_name\":\"Suara jantung\",\"id_code\":\"jantung_5\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"jantung\",\"segment_name\":\"Suara jantung\",\"id_code\":\"jantung_6\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Bising jantung / Murmur\",\"details\":[{\"table_name\":\"jantung\",\"segment_name\":\"Bising jantung / Murmur\",\"id_code\":\"jantung_7\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"jantung\",\"segment_name\":\"Bising jantung / Murmur\",\"id_code\":\"jantung_8\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"PERUT / ABDOMEN\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Abdomen\",\"details\":[{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_2\",\"lang_id\":\"1\",\"label\":\"Nyeri tekan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_3\",\"lang_id\":\"1\",\"label\":\"Nyeri ketok ginjal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_4\",\"lang_id\":\"1\",\"label\":\"Shifting Dulness\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_5\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Bising Usus\",\"details\":[{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_6\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_7\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Hati\",\"details\":[{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_8\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_9\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Limpa\",\"details\":[{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_11\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_12\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Hernia\",\"details\":[{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_13\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_14\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Hemorroid\",\"details\":[{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_15\",\"lang_id\":\"1\",\"label\":\"Tidak diperiksa\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_16\",\"lang_id\":\"1\",\"label\":\"Tidak Ada\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"perut\",\"segment_name\":\"Abdomen\",\"id_code\":\"perut_17\",\"lang_id\":\"1\",\"label\":\"Ada\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"GENITOURINARIA\",\"subtitle\":\"\",\"label_flag_normal\":\"Tidak ada\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XV\",\"details\":[{\"table_name\":\"genitourinaria\",\"id_code\":\"genitourinaria_1\",\"lang_id\":\"1\",\"label\":\"Tidak diperiksa\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"genitourinaria\",\"id_code\":\"genitourinaria_2\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"genitourinaria\",\"id_code\":\"genitourinaria_3\",\"lang_id\":\"1\",\"label\":\"Retensi Urin\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"genitourinaria\",\"id_code\":\"genitourinaria_4\",\"lang_id\":\"1\",\"label\":\"Tanda Infeksi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"genitourinaria\",\"id_code\":\"genitourinaria_5\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"title\":\"ANGGOTA GERAK\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Ekstrimitas atas\",\"details\":[{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_2\",\"lang_id\":\"1\",\"label\":\"Deformitas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_3\",\"lang_id\":\"1\",\"label\":\"Tremor\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_4\",\"lang_id\":\"1\",\"label\":\"Oedem\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_5\",\"lang_id\":\"1\",\"label\":\"Palmer eritem\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_6\",\"lang_id\":\"1\",\"label\":\"Penurunan Kekuatan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas atas\",\"id_code\":\"anggota_gerak_7\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Ekstrimitas bawah\",\"details\":[{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_8\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_9\",\"lang_id\":\"1\",\"label\":\"Deformitas\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_10\",\"lang_id\":\"1\",\"label\":\"Varices\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_11\",\"lang_id\":\"1\",\"label\":\"Oedem\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_12\",\"lang_id\":\"1\",\"label\":\"Vascularisasi abnormal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_13\",\"lang_id\":\"1\",\"label\":\"Penurunan Kekuatan\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Ekstrimitas bawah\",\"id_code\":\"anggota_gerak_15\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Tonus / otot\",\"details\":[{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Tonus / otot\",\"id_code\":\"anggota_gerak_16\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Tonus / otot\",\"id_code\":\"anggota_gerak_17\",\"lang_id\":\"1\",\"label\":\"Paresis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Tonus / otot\",\"id_code\":\"anggota_gerak_18\",\"lang_id\":\"1\",\"label\":\"Paralysis\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Tonus / otot\",\"id_code\":\"anggota_gerak_19\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Bising anggota_gerak / Murmur\",\"details\":[{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Bising anggota_gerak / Murmur\",\"id_code\":\"anggota_gerak_7\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"anggota_gerak\",\"segment_name\":\"Bising anggota_gerak / Murmur\",\"id_code\":\"anggota_gerak_8\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"SISTEM PERSYARAFAN\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Refleks Fisiologis\",\"details\":[{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Fisiologis\",\"id_code\":\"sistem_persyarafan_1\",\"lang_id\":\"1\",\"label\":\"Tonus / otot\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Fisiologis\",\"id_code\":\"sistem_persyarafan_2\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Fisiologis\",\"id_code\":\"sistem_persyarafan_3\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Refleks Pathologis\",\"details\":[{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Pathologis\",\"id_code\":\"sistem_persyarafan_4\",\"lang_id\":\"1\",\"label\":\"Tidak ada\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Pathologis\",\"id_code\":\"sistem_persyarafan_5\",\"lang_id\":\"1\",\"label\":\"Ada\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Keseimbangan & Koordinasi\",\"details\":[{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Keseimbangan & Koordinasi\",\"id_code\":\"sistem_persyarafan_6\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Keseimbangan & Koordinasi\",\"id_code\":\"sistem_persyarafan_7\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Refleks Neurologis Lainnya\",\"details\":[{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Neurologis Lainnya\",\"id_code\":\"sistem_persyarafan_8\",\"lang_id\":\"1\",\"label\":\"Tidak ada\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_persyarafan\",\"segment_name\":\"Refleks Neurologis Lainnya\",\"id_code\":\"sistem_persyarafan_9\",\"lang_id\":\"1\",\"label\":\"Ada\",\"chx\":false,\"value\":\"\"}]}]},{\"title\":\"SISTEM INTEGUMEN\",\"subtitle\":\"\",\"label_flag_normal\":\"\",\"flag_normal\":\"Y\",\"show_all\":\"Y\",\"type_form\":\"XVS\",\"details\":[{\"name\":\"Kulit\",\"details\":[{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kulit\",\"id_code\":\"sistem_integumen_1\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kulit\",\"id_code\":\"sistem_integumen_2\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kulit\",\"id_code\":\"sistem_integumen_3\",\"lang_id\":\"1\",\"label\":\"Bekas Operasi\",\"chx\":false,\"value\":\"\"},{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kulit\",\"id_code\":\"sistem_integumen_4\",\"lang_id\":\"1\",\"label\":\"Lain-lain\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Kuku\",\"details\":[{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kuku\",\"id_code\":\"sistem_integumen_5\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kuku\",\"id_code\":\"sistem_integumen_6\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]},{\"name\":\"Rambut\",\"details\":[{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kuku\",\"id_code\":\"sistem_integumen_7\",\"lang_id\":\"1\",\"label\":\"Normal\",\"chx\":true,\"value\":\"\"},{\"table_name\":\"sistem_integumen\",\"segment_name\":\"Kuku\",\"id_code\":\"sistem_integumen_8\",\"lang_id\":\"1\",\"label\":\"Tidak Normal\",\"chx\":false,\"value\":\"\"}]}]}]',	'Y');

	-- 2019-12-15 14:10:04
	*/
}
