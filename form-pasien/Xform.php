<?php
/* 
	API untuk menyediakan label untuk form Layanan Klinik::Dokumentasi Hasil
*/
class Xform extends MY_Controller
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

	public function getformtemplate()
	{
		try {
			// Retrieve Template Form Body
			$prm = $this->sys_input;
			$formCode = $prm['formCode'];

			$sql = "SELECT * FROM x_form_template_fisik_umum
					WHERE X_Form_Fisik_FormCode = '{$formCode}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}

			$result = $query->result_array();
			$formBody = json_decode($result[0]['X_Form_Fisik_Body']);
			$resp = [
				"status" => "OK",
				"data" => $formBody
			];

			header('Content-Type: application/json');
			echo json_encode($resp, JSON_PRETTY_PRINT);
		} catch (\Throwable $th) {
			$errorDetails = json_decode($th->getMessage(), true);
			if (!$errorDetails) {
				// If decoding fails, fallback to generic error
				$errorDetails = ['message' => $th->getMessage()];
			}
			$this->sys_error($errorDetails);
		}
	}

	public function getpatientinfo()
	{
		try {
			// Get Patient Info by NoReg. Hit by form
			$prm = $this->sys_input;
			$no_reg = $prm['noreg'];
			$mcu_num = $prm['mcu_num'];

			$sql = "SELECT M_PatientID as PatientID, M_PatientName as PatientName, M_PatientDOB as PatientDoB, M_PatientHp as PatientHp, M_PatientNoReg as PatientNoReg
					FROM m_patient
					WHERE M_PatientNoReg = '{$no_reg}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}
			$pat = $query->row_array();

			// Get MCU Label by MCUNum
			$sql = "SELECT Mgm_McuLabel as McuLabel, Mgm_McuID as McuID, Mgm_McuNumber as McuNum
					FROM mgm_mcu
					WHERE Mgm_McuNumber = '{$mcu_num}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}
			$mcu = $query->row_array();

			$pat = $pat ?? [];
			$mcu = $mcu ?? [
				"McuLabel" => ""
			];

			// ! Beware : Kalau salah satu array NULL, array_merge akan menghasilkan NULL
			$result = array_merge($pat, $mcu);

			$this->sys_ok($result);
		} catch (\Throwable $th) {
			$errorDetails = json_decode($th->getMessage(), true);
			if (!$errorDetails) {
				$errorDetails = ['message' => $th->getMessage()];
			}
			$this->sys_error($errorDetails);
		}
	}

	public function hasfilledform()
	{
		try {
			$no_reg = $this->sys_input['noreg'];
			$mcu_num = $this->sys_input['mcu_num'];

			$sql = "SELECT count(*) as xcount
					FROM x_form_resultentry_fisik_umum
					WHERE
						X_FormRE_Fisik_MPatientNoReg = '{$no_reg}' OR
						X_FormRE_Fisik_MgmMcuNumber = '{$mcu_num}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}
			$xcount = $query->row()->xcount;

			if ($xcount > 0) {
				$this->sys_ok(True);
			} else {
				$this->sys_ok(False);
			}
		} catch (\Throwable $th) {
			$this->sys_error($th->getMessage());
		}
	}

	public function saveform()
	{
		try {
			$pload = $this->sys_input;

			$patientID = $pload['form_meta']['PatientID'];
			$patientName = $pload['form_meta']['PatientName'];
			$patientNoReg = $pload['form_meta']['PatientNoReg'];
			$mcuID = $pload['form_meta']['McuID'];
			$mcuNumber = $pload['form_meta']['McuNum'];
			$formData = $pload['form_data'];

			// Validation
			if (empty($patientID) || empty($patientName) || empty($patientNoReg) || empty($mcuID) || empty($mcuNumber)) {
				$message = "Patient ID, Name, NoReg, MCU ID, and MCU Number cannot be empty";
				$this->sys_error($message);
				exit;
			}

			// Cek sudah atau belum. Kalau sudah tolak
			$sql = "SELECT count(*) as xcount
					FROM x_form_resultentry_fisik_umum
					WHERE
						X_FormRE_Fisik_MPatientNoReg = '{$patientNoReg}' AND
						X_FormRE_Fisik_MgmMcuNumber = '{$mcuNumber}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}
			$xcount = $query->row()->xcount;
			if ($xcount > 0) {
				$this->sys_error("Data sudah ada");
				exit;
			}

			// Encode the $formData array into a JSON string
			$formDataJson = json_encode($formData);

			// Escape the JSON string for safe insertion into the SQL query
			$escapedFormDataJson = $this->db_onedev->escape($formDataJson);

			$sql = "INSERT INTO x_form_resultentry_fisik_umum 
                (
                    X_FormRE_Fisik_MPatientID,
                    X_FormRE_Fisik_MPatientName,
                    X_FormRE_Fisik_MPatientNoReg,
                    X_FormRE_Fisik_MgmMcuID,
                    X_FormRE_Fisik_MgmMcuNumber,
                    X_FormRE_Fisik_Data,
                    X_FormRE_Fisik_CreatedAt
                )
                VALUES
                (
                    '{$patientID}',
                    '{$patientName}',
                    '{$patientNoReg}',
                    '{$mcuID}',
                    '{$mcuNumber}',
                    {$escapedFormDataJson},
                    NOW()
                )";

			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}

			$resp = [
				"msg" => "Data saved successfully",
				"patientID" => $patientID,
				"patientName" => $patientName,
				"patientNoReg" => $patientNoReg,
				"mcuID" => $mcuID
			];

			$this->sys_ok($resp);
		} catch (\Throwable $th) {
			$this->sys_error($th->getMessage());
			exit;
		}
	}

	public function getmcubynoreg($no_reg)
	{
		try {
			// Get PatientID by noreg
			$sql = "SELECT M_PatientID as pid
					FROM m_patient
					WHERE M_PatientNoReg = '{$no_reg}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception("Error fetching patient ID: " . $message);
			}
			$pid = $query->row()->pid;

			// Get MCU_ID byt patientID in t_orderheader
			$sql = "SELECT T_OrderHeaderMgm_McuID as mcu_id
					FROM t_orderheader
					WHERE T_OrderHeaderM_PatientID = '{$pid}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception("Error fetching mcu ID: " . $message);
			}
			$mcu_id = $query->row()->mcu_id;

			// Get MCU Number by MCU_ID in mgm_mcu
			$sql = "SELECT Mgm_McuNumber as mcu_number
					FROM mgm_mcu
					WHERE Mgm_McuID = '{$mcu_id}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception("Error fetching mcu number: " . $message);
			}
			$mcu_number = $query->row()->mcu_number;


			$result = [
				"M_PatientNoReg" => $no_reg,
				"M_PatientID" => $pid,
				"Mgm_McuID" => $mcu_id,
				"Mgm_McuNumber" => $mcu_number
			];

			$resp = [
				"status" => "OK",
				"data" => $result
			];

			// Define the return content type to be application/json
			header('Content-Type: application/json');
			echo json_encode($resp, JSON_PRETTY_PRINT);
		} catch (\Throwable $th) {
			$this->sys_error($th->getMessage());
			exit;
		}
	}

	public function getunlistedidcode()
	{
		try {
			$formCode = $this->sys_input['formCode'];

			$sql = "SELECT X_Form_Fisik_Unlisted FROM x_form_template_fisik_umum
                WHERE X_Form_Fisik_FormCode = '{$formCode}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}

			// Fetch the data from the database
			$unlistedString = $query->row()->X_Form_Fisik_Unlisted;

			// Decode the JSON-like string into a PHP array
			$unlistedArray = json_decode($unlistedString, true);

			// Check if json_decode was successful
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Failed to decode JSON: ' . json_last_error_msg());
			}

			// Return the array
			$this->sys_ok($unlistedArray);
		} catch (\Throwable $th) {
			$errorDetails = json_decode($th->getMessage(), true);
			if (!$errorDetails) {
				$errorDetails = ['message' => $th->getMessage()];
			}
			$this->sys_error($errorDetails);
		}
	}

	public function getfilledbydoctor()
	{
		try {
			// Retrieve Template Form Body
			$prm = $this->sys_input;
			$formCode = $prm['formCode'];

			$sql = "SELECT * FROM x_form_template_fisik_umum
					WHERE X_Form_Fisik_FormCode = '{$formCode}'";
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$message = $this->db_onedev->error();
				$message['qry'] = $this->db_onedev->last_query();
				throw new Exception(json_encode($message));
			}

			$unlisted = [];

			$result = $query->result_array();
			$formBody = json_decode($result[0]['X_Form_Fisik_Body'], true);

			foreach ($formBody as $key => $value) {
				foreach ($value as $item) {
					if ($item['type_form'] == 'XVS') {
						foreach ($item['details'] as $detail) {
							foreach ($detail['details'] as $subdetail) {
								if (isset($subdetail['color']) && $subdetail['color'] == 'red') {
									$unlisted[] = $subdetail['id_code'];
								}
							}
						}
					} else if ($item['type_form'] == 'XV') {
						foreach ($item['details'] as $detail) {
							if (isset($detail['color']) && $detail['color'] == 'red') {
								$unlisted[] = $detail['id_code'];
							}
						}
					} else {
						continue;
					}
				}
			}

			$resp = [
				"status" => "OK",
				"data" => $unlisted
			];

			header('Content-Type: application/json');
			echo json_encode($resp, JSON_PRETTY_PRINT);
		} catch (\Throwable $th) {
			$errorDetails = json_decode($th->getMessage(), true);
			if (!$errorDetails) {
				// If decoding fails, fallback to generic error
				$errorDetails = ['message' => $th->getMessage()];
			}
			$this->sys_error($errorDetails);
		}
	}

	public function getformtemplate_old()
	{
		try {
			// Getdetails dengan param re_id dan T_SamplingSoID 
			// untuk dapat template_name, orderid
			// $re_id = 867;
			// $T_SamplingSOID = 1947;
			$prm = $this->sys_input;
			$re_id = $prm['re_id'];
			$T_SamplingSOID = $prm['T_SamplingSOID'];

			$prm_dtl = [
				're_id' => $re_id,
				'T_SamplingSOID' => $T_SamplingSOID
			];

			$getdetail[] = $this->get_details($prm_dtl);

			// Get Umum untuk dapat template form based on pasien
			// Atau tidak usah jika body template dari tabel

			$template_name = $getdetail['template_name'];
			$order_id = $getdetail['orderid'];

			$prm_umum = [
				're_id' => $re_id,
				'template_name' => $template_name,
				'orderid' => $order_id
			];

			$getumum = $this->getumumlocal($prm_umum);

			// echo json_encode($getumum);
			// exit;

			$this->sys_ok($getumum);

			// Dari template_name query ke tabel template_form
			// Dapat bentuk form


		} catch (\Throwable $th) {
			// throw $th;
			$this->sys_error($th->getMessage());
		}
	}

	function getumumlocal($prm)
	{
		$rst = array();
		$rst['riwayats'] = array();
		$rst['fisiks'] = array();
		$rst['umum_saran'] = '';
		$rst['k3s'] = array();
		$rst['konsul'] = array();

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

		return $rst;
	}

	function getumum()
	{
		$prm = $this->sys_input;

		$userid = $this->sys_user["M_UserID"];
		$rst = array();
		$rst['riwayats'] = array();
		$rst['fisiks'] = array();
		$rst['umum_saran'] = '';
		$rst['k3s'] = array();
		$rst['konsul'] = array();


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

	function savefisik()
	{
		$prm = $this->sys_input;

		// $this->sys_ok($prm);
		// exit;

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

	function get_details($prm)
	{
		try {
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
				$rst_details[$ki]['details'] = $this->db_onedev->query($sql)->result_array();
				$rst_details[$ki]['photos'] = $this->getphotos($vi['orderid'], $vi['sampletypeid']);
			}

			return $rst_details;
			// $result = array("records" => $rst_details);
			// $this->sys_ok($result);
			// return $result;
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	function get_details_api()
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
}
