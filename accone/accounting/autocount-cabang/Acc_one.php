<?php
class Acc_one extends MY_Controller
{
	var $db;

	function get_branch()
	{
		$sql =
			"select M_BranchCode from m_branch  where M_BranchIsActive ='Y' and M_BranchIsDefault = 'Y'";
		$resp = $this->get_row($sql);
		if ($resp["status"] != 1) {
			echo "No Default Branch";
			exit();
		}
		return $resp["data"]["M_BranchCode"];
	}
	function test_db()
	{
		$dbx = $this->load_db();
		$qry = $dbx->query("select * from test");
		if (!$qry) {
			echo $dbx->error()["message"];
			exit();
		}
		$rows = $qry->result_array();
		$this->print_table_style();
		$this->print_table($rows, array_keys($rows[0]));
	}
	function ar_db($result)
	{
		if (count($result) == 0) {
			echo "No Records";
			return;
		}
		$date = $result[0]["Date"];
		$dbx = $this->load_db();
		$sql = "select count(*) total from ar where arDate = ?";
		$qry = $dbx->query($sql, [$date]);
		if (!$qry) {
			echo "Error : " . $dbx->error()["message"];
			return;
		}
		$rows = $qry->result_array();
		if (count($rows) > 0 && $rows[0]["total"] > 0) {
			echo "Error : AR $date sudah di Posting ";
			return;
		}
		$dbx->trans_begin();
		$count = 0;
		foreach ($result as $r) {
			$data = [];
			foreach ($r as $k => $v) {
				$data["ar" . $k] = $v;
			}
			$qry = $dbx->insert("ar", $data);
			$count++;
			if (!$qry) {
				echo "Error : " . $dbx->error()["message"];
				$dbx->trans_rollback();
				return;
			}
		}
		echo "ar data , inserted $count rows";
		$dbx->trans_commit();
	}
	function jurnal_db($result)
	{
		if (count($result) == 0) {
			echo "No Records";
			return;
		}
		$date = $result[0]["Date"];
		$dbx = $this->load_db();
		$sql = "select count(*) total from jurnal where jurnalDate = ?";
		$qry = $dbx->query($sql, [$date]);
		if (!$qry) {
			echo "Error : " . $dbx->error()["message"];
			return;
		}
		$rows = $qry->result_array();
		if (count($rows) > 0 && $rows[0]["total"] > 0) {
			echo "Error : Jurnal $date sudah di Posting ";
			return;
		}
		$dbx->trans_begin();
		$count = 0;
		foreach ($result as $r) {
			$data = [];
			foreach ($r as $k => $v) {
				$data["jurnal" . $k] = $v;
			}
			$qry = $dbx->insert("jurnal", $data);
			$count++;
			if (!$qry) {
				echo "Error : " . $dbx->error()["message"];
				$dbx->trans_rollback();
				return;
			}
		}
		echo "Jurnal data , inserted $count rows";
		$dbx->trans_commit();
	}
	function xformat($inp)
	{
		return number_format($inp, 0, "", "");
	}

	function receive_payment_db($result)
	{
		if (count($result) == 0) {
			echo "No Records";
			return;
		}
		$date = $result[0]["Date"];
		$dbx = $this->load_db();
		$sql = "select count(*) total from receive_payment where rcvPaymentDate = ?";
		$qry = $dbx->query($sql, [$date]);
		if (!$qry) {
			echo "Error : " . $dbx->error()["message"];
			return;
		}
		$rows = $qry->result_array();
		if (count($rows) > 0 && $rows[0]["total"] > 0) {
			echo "Error : Receive Payment $date sudah di Posting ";
			return;
		}
		$dbx->trans_begin();
		$count = 0;
		foreach ($result as $r) {
			$data = [];
			foreach ($r as $k => $v) {
				if ($k == "AR Date") {
					$data["rcvPaymentArDate"] = $v;
				} else if ($k == "Payment Methode") {
					$data["rcvPaymentPaymentMethode"] = $v;
				} else if ($k == "Tipe Bayar") {
					$data["rcvPaymentTipeBayar"] = $v;
				} else if ($k == "Kode Pelanggan") {
					$data["rcvPaymentKdPelanggan"] = $v;
				} else {
					$data["rcvPayment" . $k] = $v;
				}
			}
			$qry = $dbx->insert("receive_payment", $data);
			$count++;
			if (!$qry) {
				echo "Error : " . $dbx->error()["message"];
				$dbx->trans_rollback();
				return;
			}
		}
		echo "receive payment data , inserted $count rows";
		$dbx->trans_commit();
	}


	function jurnal($date = "", $format = "html", $debug = "")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}
		$branchCode = $this->get_branch();
		$r92x = $this->r_092x($date);
		$r92xa1 = $this->r_092x_a1($date);
		$r92xa2 = $this->r_092x_a2($date);
		$r92xa3 = $this->r_092x_a3($date);
		$r92xa4 = $this->r_092x_a4($date);
		$r92xa5 = $this->r_092x_a5($date);
		$r92xa6 = $this->r_092x_a6($date);

		$tot_db = 0;
		$tot_cr = 0;
		if ($debug != "") {
			echo "r92x\n";
			print_r($r92x);
			echo "r92xa1\n";
			print_r($r92xa1);
			echo "r92xa2\n";
			print_r($r92xa2);
		}
		$result_pendapatan = [];
		foreach ($r92x as $r) {
			$result_pendapatan[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => $r["M_PaymentTypeName"],
				"Note" => $r["M_PaymentTypeName"],
				"Debit" => $r["total|R"],
				"Kredit" => "",
			];
			$tot_db += $r["total|R"];
		}

		$result_sales = [];
		foreach ($r92xa1 as $r) {
			$ref_no =
				"Sales|" .
				$r["M_OmzetTypeName"] .
				"|" .
				sprintf("%02d", $r["Nat_GroupID"]) .
				"|" .
				sprintf("%02d", $r["groupTestId"]);
			$result_sales[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => $ref_no,
				"Note" =>
				$r["M_OmzetTypeName"] .
					"|" .
					$r["id"] .
					"|" .
					$r["grouptest"],
				"Debit" => "",
				"Kredit" => $r["bruto|R"],
			];
			$tot_cr += $r["bruto|R"];
		}
		$result_diskon = [];
		// edit tanggal 25/3/2024 Adhi
		foreach ($r92xa5 as $r) {
			//  $ref_no = "";
			//  $result_sales[] = [
			//    "Date" => $date,
			//    "BranchCode" => $branchCode,
			//    "Ref" => "Diskon",
			//    "Note" => "Diskon",
			//    "Debit" => $r["diskon|R"],
			//    "Kredit" => "",
			//  ];
			$tot_db += $r["diskon|R"];
		}

		$result_discount = [];
		foreach ($r92xa6 as $r) {
			$ref_no = "";
			$result_sales[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" =>  $r["Groupname"],
				"Note" => $r["Groupname"],
				"Debit" => $r["discount"],
				"Kredit" => "",
			];
			$tot_db += $r["diskon|R"];
		}

		$result_round = [];
		foreach ($r92xa2 as $r) {
			$ref_no = "";
			$result_sales[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => "Round",
				"Note" => "Round",
				"Debit" => $r["round|R"],
				"Kredit" => "",
			];
			$tot_db += $r["round|R"];
		}
		$result = array_merge(
			$result_pendapatan,
			$result_sales,
			$result_diskon,
			$result_round
		);

		if ($format == "html") {
			$this->print_table_style();
			$this->print_table($result, array_keys($result[0]));
			exit();
		}
		if ($format == "csv") {
			$this->print_csv($result, array_keys($result[0]), "jurnal.csv");
			exit();
		}
		if ($format == "json") {
			echo json_encode(["status" => "OK", "data" => $result]);
			exit();
		}
		if ($format == "db") {
			$this->jurnal_db($result);
		}
	}

	// Old name function: jurnal
	function sales($date = "", $format = "html", $debug = "")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}
		$branchCode = $this->get_branch();
		$r92x = $this->r_092x($date);
		$r92xa1 = $this->r_092x_a1($date);
		$r92xa2 = $this->r_092x_a2($date);
		$r92xa3 = $this->r_092x_a3($date);
		$r92xa4 = $this->r_092x_a4($date);
		$r92xa5 = $this->r_092x_a5($date);
		$r92xa6 = $this->r_092x_a6($date);

		$tot_db = 0;
		$tot_cr = 0;
		if ($debug != "") {
			echo "r92x\n";
			print_r($r92x);
			echo "r92xa1\n";
			print_r($r92xa1);
			echo "r92xa2\n";
			print_r($r92xa2);
			echo "r92xa6\n";
			print_r($r92xa6);
		}
		$result_pendapatan = [];
		foreach ($r92x as $r) {
			$norek = $r["BankAccountNo"];
			if ($r["BankAccountNo"] != "EDC") {
				$norek = preg_replace("/[^0-9]/", "", $r["BankAccountNo"]);
			}

			$result_pendapatan[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => $r["M_PaymentTypeName"],
				"Note" => $r["M_PaymentTypeName"],
				"Debit" => round($r["total|R"], 2),
				"Kredit" => "",
				"Nat_BankIsEDC" => $r["Nat_BankIsEDC"],
				"Nat_BankID" => $r["Nat_BankID"],
				"M_BankAccountNo" => $norek,
				"M_PaymentTypeCode" => $r["M_PaymentTypeCode"],
			];
			$tot_db += $r["total|R"];
		}

		$result_sales = [];
		foreach ($r92xa1 as $r) {
			$ref_no =
				"Sales|" .
				$r["M_OmzetTypeName"] .
				"|" .
				sprintf("%02d", $r["Nat_GroupID"]) .
				"|" .
				sprintf("%02d", $r["groupTestId"]);
			$result_sales[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => $ref_no,
				"Note" =>
				$r["M_OmzetTypeName"] .
					"|" .
					$r["id"] .
					"|" .
					$r["grouptest"],
				"Debit" => "",
				"Kredit" => round($r["bruto|R"], 2),
				"NatGroupID" => $r["Nat_GroupID"],
				"NatSubGroupID" => $r["groupTestId"],
				"M_OmzetTypeID" => $r["M_OmzetTypeID"],
				"M_OmzetTypeName" => $r["M_OmzetTypeName"]
			];
			$tot_cr += $r["bruto|R"];
		}

		// Diskon dan retur
		foreach ($r92xa6 as $r) {
			$ref_no = "";
			$result_sales[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" =>  $r["Groupname"],
				"Note" => $r["Groupname"],
				"Debit" => $r["discount"],
				"Kredit" => "",
				"NatGroupID" => $r["Nat_GroupID"],
			];
			$tot_db += $r["diskon|R"];
		}

		foreach ($r92xa2 as $r) {
			$ref_no = "";
			$result_sales[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => "Round",
				"Note" => "Round",
				"Debit" => $r["round|R"],
				"Kredit" => "",
			];
			$tot_db += $r["round|R"];
		}
		$result = array_merge(
			$result_pendapatan,
			$result_sales
		);

		if ($format == "html") {
			$this->print_table_style();
			$this->print_table($result, array_keys($result[0]));
			exit();
		}
		if ($format == "csv") {
			$this->print_csv($result, array_keys($result[0]), "jurnal.csv");
			exit();
		}
		if ($format == "json") {
			echo json_encode(["status" => "OK", "data" => $result]);
			exit();
		}
		if ($format == "db") {
			$this->jurnal_db($result);
		}
	}

	function ar($date = "", $format = "html", $debug = "")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}
		$branchCode = $this->get_branch();
		$r_092b = $this->r_092_b($date);
		$r_092b1 = $this->r_092_b1($date);
		$r_092b2 = $this->r_092_b2($date);
		$r_092b3 = $this->r_092_b3($date);
		$r_092b4 = $this->r_092_b4($date);

		if ($debug != "") {
			echo "r92b\n";
			print_r($r_092b);
		}

		$result = [];
		$tot_db = 0;
		$tot_cr = 0;
		foreach ($r_092b as $r) {
			$k = "AR|" . $r["TxNo"];
			$result[] = [
				"Date" => $date,
				"BranchCode" => $branchCode,
				"Ref" => $k,
				"Deskripsi" =>
				$r["M_CompanyName"],
				"Debit" => $r["total|R"],
				"ProductDesc" => "",
				"Product" => "",
				"Kredit" => "",
				"is_parent" => true,
				"M_CompanyID" => $r["M_CompanyID"],
				"M_CompanyName" => $r["M_CompanyName"],
				"M_CompanyNumber" => $r["M_CompanyNumber"],
				"M_OmzetTypeName" => $r["M_OmzetTypeName"],
				"M_OmzetTypeID" => $r["M_OmzetTypeID"]
			];
			$code_omz = $r["M_OmzetTypeName"];
			switch (strtolower($code_omz)) {
				case "perusahaan":
					$code_omz = "RKN";
					break;
				case "aps mandiri":
					$code_omz = "APS";
					break;
				case "rujukan":
					$code_omz = "RJK";
					break;
				case "dokter":
					$code_omz = "KLN";
					break;
				case "penelitian":
					$code_omz = "PNT";
					break;
				case "anak perusahaan":
					$code_omz = "ANP";
					break;
			}
			$tot_db += $r["total|R"];
			$last_idx = count($result) - 1;
			$is_first = true;
			foreach ($r_092b1 as $r1) {
				if ($r["TxNo"] != $r1["TxNo"]) {
					continue;
				}
				if ($is_first) {
					$result[$last_idx]["Product"] =
						$code_omz . "|" .
						sprintf("%02d", $r1["Nat_GroupID"]) .
						"|" .
						sprintf("%02d", $r1["groupTestId"]);
					$result[$last_idx]["Kredit"] = $r1["bruto|R"];
					$result[$last_idx]["ProductDesc"] = $r1["id"] . "|" . $r1["grouptest"];
					$result[$last_idx]["NatGroupID"] = $r1["Nat_GroupID"];
					$result[$last_idx]["NatSubGroupID"] = $r1["groupTestId"];
				} else {
					$result[] = [
						"Date" => $date,
						"BranchCode" => $branchCode,
						"Ref" => $k,
						"Deskripsi" => "",
						"Debit" => "",
						"ProductDesc" => $r1["id"] . "|" . $r1["grouptest"],
						"Product" =>
						$code_omz . "|" . sprintf("%02d", $r1["Nat_GroupID"]) .
							"|" .
							sprintf("%02d", $r1["groupTestId"]),
						"Kredit" => $r1["bruto|R"],
						"NatGroupID" => $r1["Nat_GroupID"],
						"NatSubGroupID" => $r1["groupTestId"],
						"M_CompanyName" => $r1["M_CompanyName"],
						"M_CompanyID" => $r1["M_CompanyID"],
						"M_CompanyNumber" => $r1["M_CompanyNumber"],
						"M_OmzetTypeName" => $r1["M_OmzetTypeName"],
						"M_OmzetTypeID" => $r1["M_OmzetTypeID"]
					];
				}
				$is_first = false;
				$tot_cr += $r1["bruto|R"];
			}

			foreach ($r_092b4 as $r1) {
				if ($r["TxNo"] != $r1["TxNo"]) {
					continue;
				}
				if ($r1["diskon"] == 0) {
					continue;
				}
				//$ref_no = "Diskon|" . $r1["TxNo"];
				$ref_no = "AR|" . $r1["TxNo"];
				$xkre = -1 * $r1["diskon"];
				$result[] = [
					"Date" => $date,
					"BranchCode" => $branchCode,
					"Ref" => $ref_no,
					"Deskripsi" =>   $r1["groupname"],
					"Debit" => "",
					"Product" =>  $r1["groupnameshort"],
					"ProductDesc" => $r1["groupnameshort"],
					"Kredit" => $xkre,
					"NatGroupID" => $r1["Nat_GroupID"],
					"M_CompanyName" => $r1["M_CompanyName"],
					"M_CompanyID" => $r1["M_CompanyID"],
					"M_CompanyNumber" => $r1["M_CompanyNumber"],
					"M_OmzetTypeName" => $r1["M_OmzetTypeName"],
					"M_OmzetTypeID" => $r1["M_OmzetTypeID"],
					"is_diskon" => true
				];
				$tot_db += $r1["diskon"];
			}
		}

		// Baris terakhir
		$result[] = [
			"Date" => $date,
			"BranchCode" => $branchCode,
			"Ref" => "",
			"Deskripsi" => "Total",
			"Debit" => $tot_db,
			"Product" => "",
			"Kredit" => $tot_cr,
		];


		if ($format == "html") {
			$this->print_table_style();
			$this->print_table($result, array_keys($result[0]));
			exit();
		}
		if ($format == "json") {
			echo json_encode(["status" => "OK", "data" => $result]);
			exit();
		}
		if ($format == "db") {
			$this->ar_db($result);
			exit();
		}
		$this->print_csv($result, array_keys($result[0]), "ar.csv");
	}

	// Old name function: receive_payment
	function arPayment($date = "", $format = "html")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}

		$branchCode = $this->get_branch();
		$r92c = $this->r_092_c($date);
		$result = [];
		$result_json = [];
		$tot_db = 0;
		$tot_cr = 0;
		foreach ($r92c as $r) {
			//$k = $r["M_BankAccountNo"];
			$k = "AR|" . $r["TxNo"];
			$datex = date("dmy", strtotime($date));
			$companyno = substr($r["M_CompanyNumber"], -6);
			$p = "PY" . $datex . $companyno;

			$result[] = [
				"Date" => $date,
				"AR Date" => $r["ArDate"],
				"BranchCode" => $branchCode,
				"Ref" => $k,
				"Pay" => $p,
				"Kode Pelanggan" => $r["M_CompanyNumber"],
				"Deskripsi" => $r["M_OmzetTypeName"] . "\t| " . $r["M_PaymentTypeName"],
				"Tipe Bayar" => $r["TipeBayar"],
				"Account" => $r["Account"],
				"Payment Methode" => $r["PaymentMethode"],
				"Debit" => $r["total|R"],
				// "Kredit" => "",
			];
			$isBank = false;
			$norek = "";
			if ($r["M_BankAccountNo"] != "") {
				$isBank = true;
				$norek = $r["M_BankAccountNo"];
			}

			$result_json[] = [
				"Date" => $date,
				"AR_Date" => date("Y-m-d", strtotime($r["ArDate"])),
				"BranchCode" => $branchCode,
				"Ref" => $k,
				"Pay" => $p,
				"M_CompanyNumber" => $r["M_CompanyNumber"],
				"M_CompanyName" => $r["M_CompanyName"],
				"M_CompanyID" => $r["M_CompanyID"],
				"M_OmzetTypeName" => $r["M_OmzetTypeName"],
				"M_OmzetTypeID" => $r["M_OmzetTypeID"],
				"Deskripsi" => $r["M_OmzetTypeName"] . "\t| " . $r["M_PaymentTypeName"],
				"Tipe_Bayar" => $r["TipeBayar"],
				"Account" => $r["Account"],
				"Payment_Methode" => $r["PaymentMethode"],
				"Debit" => $r["total|R"],
				"Kredit" => "",
				"M_BankAccountNo" => $norek,
				"IsBank" => $isBank,
				"Nat_BankID" => $r["Nat_BankID"],
				"Nat_BankCode" => $r["Nat_BankCode"],
				"Nat_BankIsEDC" => $r["Nat_BankIsEDC"],
			];
			$tot_db += $r["total|R"];
		}
		if ($format == "html") {
			$this->print_table_style();
			$this->print_table($result, array_keys($result[0]));
			exit();
		}
		if ($format == "json") {
			echo json_encode(["status" => "OK", "data" => $result_json]);
			exit();
		}
		if ($format == "db") {
			$this->receive_payment_db($result);
			exit;
		}
		$this->print_csv(
			$result,
			array_keys($result[0]),
			"receive_payment.csv"
		);
	}

	// Kalau nitip tagihan ke cabang lain
	// Old name function: receive_payment_rk
	function arPaymentRkTagihan($date = "", $format = "html")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}

		$branchCode = $this->get_branch();
		$r92c = $this->r_092_c_rk($date);
		$result = [];
		$result_json = [];
		$tot_db = 0;
		$tot_cr = 0;
		foreach ($r92c as $r) {
			$k = "AR|" . $r["TxNo"];
			$datex = date("dmy", strtotime($date));
			$companyno = substr($r["M_CompanyNumber"], -6);
			$p = "PY" . $datex . $companyno;

			// ? Nominal ambil dari Payment_RkAmount atau SsPiutangPayment

			//* Jika Total_RkAmount < 0, maka debit (nitip tagih cabang lain)
			if ($r["Total_RkAmount"] < 0) {
				$r["Debit"] = -1 * $r["Total_RkAmount"];
				$r["Kredit"] = 0;
			}
			//* Jika Total_RkAmount > 0 Dititipin tagihan cabang lain
			else {
				$r["Debit"] = 0;
				$r["Kredit"] = -1 * $r["Total_RkAmount"];
			}

			$result[] = [
				"Date" => $date,
				"AR Date" => $r["ArDate"],
				"BranchCode" => $branchCode,
				"Ref" => $k,
				"Pay" => $p,
				"Kode Pelanggan" => $r["M_CompanyNumber"],
				"Deskripsi" => $r["M_OmzetTypeName"] . "\t| " . $r["M_PaymentTypeName"],
				"Tipe Bayar" => $r["TipeBayar"],
				"Account" => $r["Account"],
				"Payment Methode" => $r["PaymentMethode"],
				"RK_BranchCode" => $r["Payment_RkM_BranchCode"],
				"Debit" => $r["Debit"],
				"Kredit" => $r["Kredit"],
				"Total_RkAmount" => $r["Total_RkAmount"],
			];

			$result_json[] = [
				"Date" => $date,
				"AR_Date" => date("Y-m-d", strtotime($r["ArDate"])),
				"BranchCode" => $branchCode,
				"Ref" => $k,
				"Pay" => $p,
				"M_CompanyNumber" => $r["M_CompanyNumber"],
				"M_CompanyName" => $r["M_CompanyName"],
				"M_CompanyID" => $r["M_CompanyID"],
				"M_OmzetTypeName" => $r["M_OmzetTypeName"],
				"M_OmzetTypeID" => $r["M_OmzetTypeID"],
				"Deskripsi" => $r["M_OmzetTypeName"] . "\t| " . $r["M_PaymentTypeName"],
				"Tipe_Bayar" => $r["TipeBayar"],
				"Account" => $r["Account"],
				"Payment_Methode" => $r["PaymentMethode"],
				"RK_BranchCode" => $r["Payment_RkM_BranchCode"],
				"Debit" => $r["Debit"],
				"Kredit" => $r["Kredit"],
				"Total_RkAmount" => $r["Total_RkAmount"],
			];
			$tot_db += $r["total|R"];
		}
		if ($format == "html") {
			$this->print_table_style();
			$this->print_table($result, array_keys($result[0]));
			exit();
		}
		if ($format == "json") {
			echo json_encode(["status" => "OK", "data" => $result_json]);
			exit();
		}
		if ($format == "db") {
			$this->receive_payment_db($result);
			exit;
		}
		$this->print_csv(
			$result,
			array_keys($result[0]),
			"receive_payment.csv"
		);
	}

	// Kalau dititipin buat cabang lain
	// Old name function: r_092_c_rk_pelunasan
	function arPaymentRkPelunasan($date)
	{
		$result = [];

		$sql = "SELECT * FROM f_bill_payment_pusat 
		WHERE F_BillPaymentPusatDate = ? AND F_BillPaymentPusatIsActive = 'Y' ";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		// Untuk setiap payment pusat, loop untuk setiap detailnya
		foreach ($resp["data"] as $item) {
			$billIssuePusatID = $item["F_BillPaymentPusatF_BillIssuePusatID"];
			$paymentTypeID = $item["F_BillPaymentPusatM_PaymentTypeID"];
			$mBankAccountID = $item["F_BillPaymentPusatM_BankAccountID"];
			$amountFromClient = $item["F_BillPaymentPusatAmount"]; // untuk debit dari client

			// Data Pelengkap untuk mapping coa 
			$sql = "SELECT * FROM m_paymenttype WHERE M_PaymentTypeID = ?
				AND M_PaymentTypeIsActive = 'Y'";
			$qry =  $this->db->query($sql, [$paymentTypeID]);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$paymentType = $qry->row();

			$sql = "SELECT * FROM m_bank_account 
				LEFT JOIN nat_bank ON Nat_BankID = M_BankAccountNat_BankID
				WHERE M_BankAccountID = ? 
				AND M_BankAccountIsActive = 'Y'";
			$qry =  $this->db->query($sql, [$mBankAccountID]);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$bankAccount = $qry->row();

			$sql = "SELECT M_CompanyName, M_CompanyNumber, M_CompanyID
				FROM f_bill_issue_pusat
				JOIN m_company ON F_BillIssuePusatM_CompanyID = M_CompanyID
				WHERE F_BillIssuePusatID = ? AND F_BillIssuePusatIsActive = 'Y'";
			$qry =  $this->db->query($sql, [$billIssuePusatID]);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$comp = $qry->row();

			$sql = "SELECT Nat_BankID, Nat_BankCode, Nat_BankIsEDC
				FROM nat_bank
				LEFT JOIN m_bank_account ON Nat_BankID = M_BankAccountNat_BankID
				WHERE 
				WHERE M_BankAccountID = ? 
				AND M_BankAccountIsActive = 'Y'";

			/* 
			* DEBIT
			* -------------------------
			* Dari client mapping pakai coa Bank Account
			*/
			$debit = [
				"F_BillPaymentPusatDate" => $date,
				"F_BillPaymentPusatAmount" => $amountFromClient,
				"M_PaymentTypeID" => $paymentTypeID,
				"M_PaymentTypeName" => $paymentType->M_PaymentTypeName,
				"M_BankAccountNo" => $bankAccount->M_BankAccountNo,
				"M_CompanyID" => $comp->M_CompanyID,
				"M_CompanyName" => $comp->M_CompanyName,
				"M_CompanyNumber" => $comp->M_CompanyNumber,
				"Desc" => "{$date} | F_BillIssuePusatID {$billIssuePusatID} | From {$comp->M_CompanyName} | Amount {$amountFromClient} | Via {$bankAccount->M_BankAccountNo} | TipeBayar {$paymentType->M_PaymentTypeName}"
			];

			/* 
			* KREDIT
			* -------------------------
			* Kalau dititipin buat cabang lain mapping coa R/K cabang lain
			*/
			$sql = "SELECT
						F_BillIssuePusatDetailF_BillID as F_BillID,
						F_BillIssuePusatDetailID,
						F_BillIssuePusatDetailTotal,
						F_BillIssuePusatDetailUnpaid,
						M_BranchCode as RK_BranchCode
					FROM f_bill_issue_pusat_detail
						JOIN m_branch ON F_BillIssuePusatDetailM_BranchID = M_BranchID
					WHERE F_BillIssuePusatDetailF_BillIssuePusatID = ?
					AND M_BranchIsDefault = 'N' AND M_BranchIsActive = 'Y'
					AND F_BillIssuePusatDetailIsActive = 'Y' ";
			$qry =  $this->db->query($sql, [$billIssuePusatID]);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$rk_othercab = $qry->result_array();
			foreach ($rk_othercab as &$q) {
				$q['M_PaymentTypeID'] = $paymentTypeID;
				$q['M_PaymentTypeName'] = $paymentType->M_PaymentTypeName;
				$q['M_CompanyID'] = $comp->M_CompanyID;
				$q['M_CompanyName'] = $comp->M_CompanyName;
				$q['M_CompanyNumber'] = $comp->M_CompanyNumber;
				$q['Desc'] = "F_BillIssuePusatDetailID {$q['F_BillIssuePusatDetailID']} | To {$q['RK_BranchCode']} | Amount {$q['F_BillIssuePusatDetailTotal']} | Company {$comp->M_CompanyName} | TipeBayar {$paymentType->M_PaymentTypeName}";
			}
			unset($q);

			/* 
			* KREDIT
			* -------------------------
			* Kalau untuk AR diri sendiri perlu join T_OrderHeader agar tahu AR Date dan Companynya
			*/
			$thisBranch = "SELECT * FROM m_branch WHERE M_BranchIsDefault = 'Y' AND M_BranchIsActive = 'Y'";
			$qry =  $this->db->query($thisBranch);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$rst = $qry->row();
			$branchID = $rst->M_BranchID;
			$branchCode = $rst->M_BranchCode;

			$sql = "SELECT
				F_BillID,
				F_BillIssuePusatDetailID,
				ArDate,
				SUM(F_BillDetailTotal) AS SumBillDetailTotal,
				F_BillIssuePusatDetailUnpaid,
				M_CompanyName, M_CompanyNumber,M_CompanyID,
				M_OmzetTypeName, M_OmzetTypeID
			FROM (
				SELECT 
					F_BillIssuePusatDetailF_BillID AS F_BillID,
					F_BillIssuePusatDetailID,
					F_BillIssuePusatDetailUnpaid,
					F_BillDetailTotal,
					DATE_FORMAT(T_OrderHeaderDate, '%Y-%m-%d') AS ArDate,  -- Fixed: Added missing comma
					M_CompanyName, M_CompanyNumber,M_CompanyID, M_OmzetTypeName, M_OmzetTypeID
				FROM f_bill_issue_pusat_detail
					JOIN f_bill_detail ON F_BillIssuePusatDetailF_BillID = F_BillDetailF_BillID	
					JOIN t_orderheader ON F_BillDetailT_OrderHeaderID = T_OrderHeaderID
					JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
					JOIN m_mou ON T_OrderHeaderM_MouID = M_MouID
					JOIN m_omzettype ON M_MouM_OmzetTypeID = M_OmzetTypeID 
				WHERE F_BillIssuePusatDetailF_BillIssuePusatID = ?
				AND F_BillIssuePusatDetailM_BranchID = ?
				AND M_OmzetTypeID <> 7
				AND T_OrderHeaderIsActive = 'Y'
				AND F_BillIssuePusatDetailIsActive = 'Y' 
			) AS x
			GROUP BY ArDate";
			$qry =  $this->db->query($sql, [$billIssuePusatID, $branchID]);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}

			$total = 0;
			$rk_self_cab = $qry->result_array();
			foreach ($rk_self_cab as &$r) {
				$r['BranchCode'] = $branchCode;
				$r['M_PaymentTypeID'] = $paymentTypeID;
				$r['M_PaymentTypeName'] = $paymentType->M_PaymentTypeName;
				$r['M_BankAccountNo'] = $bankAccount->M_BankAccountNo;
				$r['Desc'] = "{$r['ArDate']} | F_BillIssuePusatDetailID {$r['F_BillIssuePusatDetailID']} | Amount {$r['SumBillDetailTotal']} | Company {$r['M_CompanyName']} | TipeBayar {$paymentType->M_PaymentTypeName}";
				$total += $r["TotalBill"];
			}
			unset($r); // Unset the reference to avoid potential side effects

			$result[] = [
				"Debit" => $debit,
				"AR_RK_Cabang_Lain" => $rk_othercab,
				"AR_Cabang_Ini" => $rk_self_cab
			];
		}
		$resp = [
			"status" => "OK",
			"data" => $result
		];
		echo (json_encode($resp, JSON_PRETTY_PRINT));
		exit;
	}

	// CreatedAt: 2025-02-03. Untuk insert map_bank_coa di accone
	function getBanks()
	{
		try {
			$sql = "SELECT M_BankAccountNo, 
				Nat_BankID,
				Nat_BankCode,
				Nat_BankName,
				Nat_BankIsCard,
				Nat_BankIsEDC
			FROM m_bank_account
			JOIN nat_bank ON M_BankAccountNat_BankID = Nat_BankID
			WHERE M_BankAccountIsActive = 'Y'
			AND Nat_BankIsActive = 'Y'
			ORDER BY Nat_BankID";
			$qry =  $this->db->query($sql);
			if (!$qry) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$banks = $qry->result_array();

			// get this cabang
			$sql2 = "SELECT M_BranchCode
				FROM m_branch
			WHERE M_BranchIsDefault = 'Y' AND M_BranchIsActive = 'Y'";
			$qry2 =  $this->db->query($sql2);
			if (!$qry2) {
				$this->sys_error_db("Error: " . $this->db);
				exit();
			}
			$branch = $qry2->row()->M_BranchCode;

			// append branchCode to each row in banks
			foreach ($banks as &$b) {
				$b["M_BranchCode"] = $branch;
				// Clean M_BankAccountNo from - . and space and all letters. Except EDC
				if ($b["M_BankAccountNo"] != "EDC") {
					$b["M_BankAccountNo"] = preg_replace("/[^0-9]/", "", $b["M_BankAccountNo"]);
				}
			}

			$this->sys_ok($banks);
		} catch (Exception $exc) {
			$msg = $exc->getMessage();
			$this->sys_error($msg);
		}
	}

	function print_csv($rows, $header, $file_name = "download.csv")
	{
		//header("Content-type: text/csv");
		//header('Content-Disposition: inline; filename="' . $file_name . '"');
		echo "<pre>";
		$line = "";
		foreach ($header as $h) {
			if ($line != "") {
				$line .= ",";
			}
			if (strpos(",", $h) === false) {
				$line .= $h;
			} else {
				$line .= "\"$h\"";
			}
		}
		echo $line . "\n";
		foreach ($rows as $r) {
			$line = "";
			foreach ($header as $h) {
				if ($line != "") {
					$line .= ",";
				}
				if (strpos(",", $r[$h]) === false) {
					if (in_array($h, ["Debit", "Kredit"])) {
						$line .= $this->xformat($r[$h]);
					} else {
						$line .= $r[$h];
					}
				} else {
					$line .= "\"{$r[$h]}\"";
				}
			}
			echo $line . "\n";
		}
	}

	function check_receive_payment($date = "")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}
		$this->print_table_style();
		$rows = $this->r_092_c($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092_c");
		echo "<br/>";
		$rows = $this->r_092_c1($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092_c1");
		echo "<br/>";
		$rows = $this->r_092_c2($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092_c2");
		echo "<br/>";
	}
	function r_092_c2($date)
	{
		$sql = "select 
          sum(SsPiutangPaymentAmount) as `total|R` , date(T_OrderHeaderDate) as ArDate,
        concat(date(T_OrderHeaderDate),'|',M_CompanyNumber) as TxNo,
        M_PaymentTypeName,
        M_OmzetTypeName
  --      M_OmzetTypeID

        from ss_piutang
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
        left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
        left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
        where SsPiutangDate =  ?
        and SsPiutangType =  'B2'  and T_OrderHeaderAddOnIsKaPus = 'N'
        and M_OmzetTypeID <> 7
        group by M_OmzetTypeID , TxNo
        order by M_OmzetTypeID , TxNo";
		$sql = " select
    sum(total)as`total|R` ,  ArDate,
    TxNo,
    M_PaymentTypeName,
    M_OmzetTypeName,TipeBayar,
    M_OmzetTypeID 
    from (

    select 
           SsPiutangPayment    as  total,date(T_OrderHeaderDate) as ArDate,
            concat(date(T_OrderHeaderDate),'|',M_CompanyNumber) as TxNo,M_PaymentTypeName as TipeBayar,
            M_PaymentTypeName,
            M_OmzetTypeName,
        M_OmzetTypeID

            from ss_piutang
            join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
            left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
            left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
            left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
            left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
            left join m_mou on T_OrderHeaderM_MouID  = M_MouID
             left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
            left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
            where SsPiutangDate =  '2022-12-22'
    and SsPiutangType IN ('B2','A3')    and T_OrderHeaderAddOnIsKaPus = 'N' and SsPiutangIsActive = 'Y'       
            and M_OmzetTypeID <> 7
    group by SsPiutangID,T_OrderHeaderID  ) as x
    group by M_OmzetTypeID , TxNo
    order by M_OmzetTypeID , TxNo
    ";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["total|R"];
			$r["total|R"] = $r["total|R"];
			$result[] = $r;
		}
		$result[] = [
			"total|R" => $total,
			"M_PaymentTypeName" => "",
			"M_OmzetTypeName" => "",
			"TxNo" => "SUM",
		];

		return $result;
	}
	function r_092_c1($date)
	{
		$sql = "select
      -- SsPiutangT_OrderHeaderID as id, 	date(T_OrderHeaderDate) as ArDate,
      concat(date(T_OrderHeaderDate),'-',M_CompanyName) as TxNo,
      sum( MOD(SsPiutangPaymentAmount, 500)) as `round|R` ,
      M_PaymentTypeName,
      M_OmzetTypeName,
      M_OmzetTypeID,
	  M_CompanyNumber, M_CompanyName, M_CompanyID
      from ss_piutang
      join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
      left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
      left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
      left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      where SsPiutangDate =  ?
      and SsPiutangType = 'B2'
      and M_PaymentTypeID = '1' and T_OrderHeaderAddOnIsKaPus = 'N'
        and M_OmzetTypeID <> 7
      group by M_OmzetTypeID,TxNo
      order by M_OmzetTypeID,TxNo
      ";
		$sql = "
    select
      -- SsPiutangT_OrderHeaderID as id, 	
      concat(date(T_OrderHeaderDate),'-',M_CompanyName) as TxNo,date(T_OrderHeaderDate) as ArDate,M_PaymentTypeName as TipeBayar,
      sum( MOD(SsPiutangPayment  , 500)) as `round|R` ,
      M_PaymentTypeName,
      M_OmzetTypeName
      -- M_OmzetTypeID
      from ss_piutang
      join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
      left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
      left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
      left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      where SsPiutangDate =  ?
      and SsPiutangType IN ('B2','A3')    and T_OrderHeaderAddOnIsKaPus = 'N' and SsPiutangIsActive = 'Y'
      and M_PaymentTypeID = '1'  
        and M_OmzetTypeID <> 7
      group by M_OmzetTypeID,TxNo
      order by M_OmzetTypeID,TxNo 
    ";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["round|R"];
			$r["round|R"] = $r["round|R"];
			$result[] = $r;
		}

		return $result;
	}
	function r_092_c($date)
	{
		$sql = "SELECT
				sum(total)as`total|R` ,  
				TxNo,ArDate,
				M_PaymentTypeName,  TipeBayar,
				M_OmzetTypeName, if(Account= '' , TipeBayar, Account) as Account ,
				M_OmzetTypeID, M_CompanyID, M_CompanyName , M_CompanyNumber,
				M_BankAccountNo, 
				IFNULL(Nat_BankID, '') as Nat_BankID,
				IFNULL(Nat_BankCode, '') as Nat_BankCode,
				IFNULL(Nat_BankIsEDC, '') as Nat_BankIsEDC,
			case
				when M_PaymentTypeID IN ('2','3') then  concat('EDC',' ' , Nat_BankCode , ' ' , M_OmzetTypeName) 
				when M_PaymentTypeID = '4' then Account
			else  TipeBayar end as PaymentMethode
			from (
			select 
			SsPiutangPayment    as  total,
				concat(DATE_FORMAT(T_OrderHeaderDate,'%d%m%Y'),'|',M_CompanyNumber) as TxNo,DATE_FORMAT(T_OrderHeaderDate,'%Y/%m/%d') as ArDate,M_PaymentTypeName as TipeBayar,
				concat(M_PaymentTypeName,'|' , M_CompanyName , '|', ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,'')) as M_PaymentTypeName,
				concat( ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,''))as Account,
				ifnull(M_BankAccountNo,'') as M_BankAccountNo, 
				M_PaymentTypeID, M_OmzetTypeName, M_CompanyName,M_CompanyNumber, M_CompanyID,
				M_OmzetTypeID, Nat_BankID, Nat_BankCode, Nat_BankIsEDC
			from ss_piutang
				join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
				left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
				left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
				left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
				left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
				left join m_mou on T_OrderHeaderM_MouID  = M_MouID
				left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
				left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID 
				left join m_bank_account on SsPiutangPaymentM_BankAccountID = M_BankAccountID
				left join  nat_bank on Nat_BankID = M_BankAccountNat_BankID
				where SsPiutangDate =  ?    
				and SsPiutangType IN ('B2','A3')    and T_OrderHeaderAddOnIsKaPus = 'N' and SsPiutangIsActive = 'Y'       
				and M_OmzetTypeID <> 7
				group by SsPiutangID,T_OrderHeaderID  ) as x
				group by M_OmzetTypeID , TxNo
				order by M_OmzetTypeID , TxNo ";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["total|R"];
			$r["total|R"] = $r["total|R"];
			$result[] = $r;
		}

		return $result;
	}

	function r_092_c_rk($date)
	{
		$sql_nitip = "select
			sum(total) as`total|R` ,
			sum(Payment_RkAmount) as `Total_RkAmount`,  
			TxNo, ArDate,
			M_PaymentTypeName,  TipeBayar,
			M_OmzetTypeName, if(Account= '' , TipeBayar, Account) as Account ,
			M_OmzetTypeID, M_CompanyID, M_CompanyName , M_CompanyNumber,
			M_BankAccountNo, 
			Payment_RkF_PaymentNumber, Payment_RkM_BranchCode, Payment_RkAmount,
		case
			when M_PaymentTypeID IN ('2','3') then  concat('EDC',' ' , Nat_BankCode , ' ' , M_OmzetTypeName) 
			when M_PaymentTypeID = '4' then Account
		else  TipeBayar end as PaymentMethode
		from (
			select 
				SsPiutangPayment as total,
				concat(DATE_FORMAT(T_OrderHeaderDate,'%d%m%Y'),'|',M_CompanyNumber) as TxNo, 
				DATE_FORMAT(T_OrderHeaderDate,'%Y/%m/%d') as ArDate, 
				M_PaymentTypeName as TipeBayar,
				concat(M_PaymentTypeName,'|' , M_CompanyName , '|', ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,'')) as M_PaymentTypeName,
				concat( ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,''))as Account, 
				ifnull(M_BankAccountNo,'') as M_BankAccountNo, 
				M_PaymentTypeID, Nat_BankCode,
				M_OmzetTypeName, M_OmzetTypeID, M_CompanyName,M_CompanyNumber, M_CompanyID,
				Payment_RkF_PaymentNumber, Payment_RkM_BranchCode, Payment_RkAmount
			from ss_piutang
				join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
				left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
				
				join f_payment on SsPiutangT_OrderHeaderID = F_PaymentT_OrderHeaderID
				left join payment_rk on F_PaymentID = Payment_RkF_PaymentID

				left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
				left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
				left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
				left join m_mou on T_OrderHeaderM_MouID  = M_MouID
				left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
				left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID 
				left join m_bank_account on SsPiutangPaymentM_BankAccountID = M_BankAccountID
				left join  nat_bank on Nat_BankID = M_BankAccountNat_BankID
				
				where SsPiutangDate =  ?    
				AND M_PaymentTypeID = 20
				and SsPiutangType IN ('B2','A3')    and T_OrderHeaderAddOnIsKaPus = 'N' and SsPiutangIsActive = 'Y'       
				and M_OmzetTypeID <> 7
				group by SsPiutangID,T_OrderHeaderID  
		) as x
			group by M_OmzetTypeID , TxNo
			order by M_OmzetTypeID , TxNo ";
		$resp = $this->get_rows($sql_nitip, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["total|R"];
			$r["total|R"] = $r["total|R"];
			$result[] = $r;
		}

		return $result;
	}

	function check_ar($date = "")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}
		$this->print_table_style();
		$rows = $this->r_092_b($date);
		$this->print_table(
			$rows,
			array_keys($rows[0]),
			"r_092_b | total piutang per omzet"
		);
		echo "<br/>";
		$rows = $this->r_092_b1($date);
		$this->print_table(
			$rows,
			array_keys($rows[0]),
			"r_092_b1 | 28 test group per omzet"
		);
		echo "<br/>";
		$rows = $this->r_092_b2($date);
		$this->print_table(
			$rows,
			array_keys($rows[0]),
			"r_092_b2 | total  28 test group per omzet"
		);
		echo "<br/>";

		$rows = $this->r_092_b3($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092_b3 | diskon");
		echo "<br/>";
	}

	function r_092_b4($date)
	{
		$sql = "select
		concat(date(T_OrderHeaderDate),'|',M_CompanyNumber) as TxNo,
		M_OmzetTypeName, M_OmzetTypeID,
		M_CompanyName, M_CompanyNumber, M_CompanyID,
		sum(ifnull(SsPiutangTestDiscTotal,0)) as `diskon`,
 		Nat_GroupID,
		concat('Diskon',' ',Nat_GroupName) as groupname,
		(case 
			when Nat_GroupID= '1' then 'Diskon|Lab'
			when Nat_GroupID= '2' then 'Diskon|Elt'
			when Nat_GroupID= '3' then 'Diskon|Rad'
			when Nat_GroupID= '4' then 'Diskon|LKL'
		else '' end) as groupnameshort
     from ss_piutang
 join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
 left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
left join m_mou on T_OrderHeaderM_MouID  = M_MouID
 left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
left join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
  and T_OnlineOrderIsActive = 'Y'
 join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
 join t_test on SsPiutangTestT_TestID = T_TestID
 join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID and Nat_SubGroupIsActive = 'Y'
 join nat_group on T_TestNat_GroupID = Nat_GroupID


where SsPiutangDate =  ?
and SsPiutangType IN ('A1','A3')     and T_OrderHeaderAddOnIsKaPus = 'N'
and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'
and  T_OnlineOrderID is null 
group by M_CompanyID ,Nat_GroupID
 
order by M_CompanyID ,Nat_GroupID";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["diskon"];
			$r["diskon"] = $r["diskon"];
			$result[] = $r;
		}

		return $result;
	}




	function r_092_b3($date)
	{
		$sql = "select
      -- SsPiutangT_OrderHeaderID as id, 	
      -- M_OmzetTypeID ,
      concat(date(T_OrderHeaderDate),'|',M_CompanyNumber) as TxNo,
      M_OmzetTypeName, M_CompanyName,
      sum(ifnull(SsPiutangDiscount,0)) as `diskon|R`
      from ss_piutang
       join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
       left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      left join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
        and T_OnlineOrderIsActive = 'Y'
      -- left join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
      -- left  join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID  

  where SsPiutangDate = ?
      and SsPiutangType IN ('A1','A3')     and T_OrderHeaderAddOnIsKaPus = 'N'
      -- and (F_PaymentDetailM_PaymentTypeID <> 6 or T_OnlineOrderID is null)  
      and ( T_OnlineOrderID is null)  
      and M_OmzetTypeID <> 7
      group by TxNo, M_OmzetTypeID 
      order by TxNo, M_OmzetTypeID";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["diskon|R"];
			$r["diskon|R"] = $r["diskon|R"];
			$result[] = $r;
		}

		return $result;
	}
	function r_092_b2($date)
	{
		$sql = "select
      -- SsPiutangT_OrderHeaderID as id, 	
      sum(SsPiutangTestPrice) `price|R` ,
      M_OmzetTypeName
      -- M_OmzetTypeID 
      from ss_piutang
      join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
      join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
      left join t_test on SsPiutangTestT_TestID = T_TestID
      left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID
      left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      left join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
        and T_OnlineOrderIsActive = 'Y'
      -- left join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
      -- left  join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID  

  where SsPiutangDate = ?
      and SsPiutangType IN ('A1','A3')  and T_OrderHeaderAddOnIsKaPus = 'N'
      -- and (F_PaymentDetailM_PaymentTypeID <> 6 or T_OnlineOrderID is null)  
      and (T_OnlineOrderID is null)  
      and M_OmzetTypeID <> 7
      group by M_OmzetTypeID 
      order by M_OmzetTypeID,Nat_SubGroupID";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["price|R"];
			$r["price|R"] = $r["price|R"];
			$result[] = $r;
		}
		return $result;
	}
	function r_092_b1($date)
	{
		$sql = "select
      Nat_GroupName  as id, 	
      concat(date(T_OrderHeaderDate),'|',M_CompanyNumber) as TxNo,
      Nat_GroupID,
      sum(SsPiutangTestPrice) as `bruto|R`,
      M_OmzetTypeName,
      M_OmzetTypeID,
	  M_CompanyID, M_CompanyName, M_CompanyNumber,
      Nat_SubGroupName as grouptest,
      Nat_SubGroupID as groupTestId
      from ss_piutang
      join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
      join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
      left join t_test on SsPiutangTestT_TestID = T_TestID
      left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID
      left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      left join nat_group on T_TestNat_GroupID = Nat_GroupID
      left join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
        and T_OnlineOrderIsActive = 'Y'
      -- left join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
      -- left  join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID  

  where SsPiutangDate = ?
      and SsPiutangType IN ('A1','A3')  and T_OrderHeaderAddOnIsKaPus = 'N'
      -- and (F_PaymentDetailM_PaymentTypeID <> 6 or T_OnlineOrderID is null)  
      and ( T_OnlineOrderID is null)  
      and M_OmzetTypeID <> 7
      group by TxNo,Nat_GroupID,Nat_SubGroupID,M_OmzetTypeName 
      order by TxNo,M_OmzetTypeName,	Nat_GroupID,Nat_SubGroupID";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["bruto|R"];
			$r["bruto|R"] = $r["bruto|R"];
			$result[] = $r;
		}
		return $result;
	}
	function r_092_b($date)
	{
		$sql = "select
      M_OmzetTypeName,
      M_OmzetTypeID,
      concat(date(T_OrderHeaderDate),'|',M_CompanyNumber) as TxNo,
      -- M_OmzetTypeID,
      -- SsPiutangT_OrderHeaderID as id, 	
      sum(SsPiutangTotal)  as `total|R` , 
      sum(SsPiutangTotal) +  sum(SsPiutangDiscount) as `grandTotal|R`,
      M_CompanyName,
	  M_CompanyNumber,
	  M_CompanyID
      from ss_piutang
      join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
       left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      left join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
        and T_OnlineOrderIsActive = 'Y'
      -- left join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
      -- left  join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID  

      where SsPiutangDate = ? 
      and SsPiutangType IN ('A1','A3')  and T_OrderHeaderAddOnIsKaPus = 'N'
      -- and (F_PaymentDetailM_PaymentTypeID <> 6 or T_OnlineOrderID is null)   
      and (T_OnlineOrderID is null)   
      and M_OmzetTypeID <> 7
      group by M_OmzetTypeID,TxNo 
      order by M_OmzetTypeID,TxNo	";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		$grandTotal = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["total|R"];
			$grandTotal += $r["grandTotal|R"];
			$r["total|R"] = $r["total|R"];
			$r["grandTotal|R"] = $r["grandTotal|R"];
			$result[] = $r;
		}
		return $result;
	}
	function check_jurnal_entry($date = "")
	{
		if ($date == "") {
			$date = date("Y-m-d");
		}
		$this->print_table_style();
		$rows = $this->r_092x($date);
		$this->print_table(
			$rows,
			array_keys($rows[0]),
			"r_092x | Tipe Pembayaran"
		);
		echo "<br/>";
		$rows = $this->r_092x_a1($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092x_a1 | 28 test");
		echo "<br/>";
		$rows = $this->r_092x_a2($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092x_a2 | Rounding");
		echo "<br/>";
		$rows = $this->r_092x_a3($date);
		$this->print_table(
			$rows,
			array_keys($rows[0]),
			"r_092x_a3 | total pembayaran - round - diskon"
		);
		echo "<br/>";
		$rows = $this->r_092x_a4($date);
		$this->print_table(
			$rows,
			array_keys($rows[0]),
			"r_092x_a4 | total 28 test"
		);
		echo "<br/>";
		$rows = $this->r_092x_a5($date);
		$this->print_table($rows, array_keys($rows[0]), "r_092x_a5 | diskon");
	}

	function r_092x_a6($date = "")
	{
		$sql = "
        select
        sum(diskon) as `discount`,concat('diskon',' ',Nat_GroupName) as Groupname,
		Nat_GroupID

		from (
				
		select
		SsPiutangT_OrderHeaderID as id, 	
		M_OmzetTypeID ,M_OmzetTypeName,
		sum(SsPiutangTestDiscTotal) as diskon,
		Nat_GroupID,
		Nat_GroupName


		from ss_piutang
		join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
		left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
		left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
		left join m_mou on T_OrderHeaderM_MouID  = M_MouID
		left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
		left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
		join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
		join t_test on SsPiutangTestT_TestID = T_TestID
		join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID and Nat_SubGroupIsActive = 'Y'
		join nat_group on T_TestNat_GroupID = Nat_GroupID

		where SsPiutangDate = ?
		and SsPiutangType = 'A2'    and T_OrderHeaderAddOnIsKaPus = 'N'
		and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'
		group by Nat_GroupID

				union all
		select
		SsPiutangT_OrderHeaderID as id, 	
		M_OmzetTypeID ,M_OmzetTypeName,
		sum(SsPiutangTestDiscTotal) as diskon,
		Nat_GroupID,
		Nat_GroupName


		from ss_piutang
		join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
		join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
		and T_OnlineOrderIsActive = 'Y'
		join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
		join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID and F_PaymentDetailM_PaymentTypeID = 6
		left join m_paymenttype on F_PaymentDetailM_PaymentTypeID = M_PaymentTypeID
		left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
		left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
		left join m_mou on T_OrderHeaderM_MouID  = M_MouID
		left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID 
		join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
		join t_test on SsPiutangTestT_TestID = T_TestID
		join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID and Nat_SubGroupIsActive = 'Y'
		join nat_group on T_TestNat_GroupID = Nat_GroupID

		where SsPiutangDate = ?
		and SsPiutangType = 'A1'    
		and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y' 
		group by Nat_GroupID 
				) as x	 group by Nat_GroupID
				";

		$xsql = "select
      sum(SsPiutangTestDiscTotal) as `discount`
      from ss_piutang
 join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
 left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
left join m_mou on T_OrderHeaderM_MouID  = M_MouID
 left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
 join t_test on SsPiutangTestT_TestID = T_TestID
 join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID and Nat_SubGroupIsActive = 'Y'
 join nat_group on T_TestNat_GroupID = Nat_GroupID

      where SsPiutangDate = ?
      and SsPiutangType = 'A2' 
      and M_OmzetTypeID <> 7
      and T_OrderHeaderAddOnIsKaPus = 'N'	";
		$resp = $this->get_rows($sql, [$date, $date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		foreach ($resp["data"] as $idx => $r) {
			$r["discount"] = $r["discount"];
			$result[] = $r;
		}
		return $result;
	}


	function r_092x_a5($date = "")
	{
		$sql = "
        select
        sum(diskon) as `diskon|R`

        from (
        select
        SsPiutangT_OrderHeaderID as id, 	
        M_OmzetTypeID ,M_OmzetTypeName,
        sum(SsPiutangDiscount) as diskon

        from ss_piutang
         join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
         left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
        left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  

        where SsPiutangDate = ?
        and SsPiutangType = 'A2'    and T_OrderHeaderAddOnIsKaPus = 'N'
        and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'

        union all

        select
        SsPiutangT_OrderHeaderID as id, 	
        M_OmzetTypeID ,M_OmzetTypeName,
        sum(SsPiutangDiscount) as diskon

        from ss_piutang
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
          and T_OnlineOrderIsActive = 'Y'
        join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
        join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID and F_PaymentDetailM_PaymentTypeID = 6
        left join m_paymenttype on F_PaymentDetailM_PaymentTypeID = M_PaymentTypeID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID 

        where SsPiutangDate =?
        and SsPiutangType = 'A1'    
        and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y' 
        ) as x	
        ";
		$xsql = "select
      sum(SsPiutangDiscount) as `diskon|R`
      from ss_piutang
       join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
       left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      where SsPiutangDate = ?
      and SsPiutangType = 'A2' 
      and M_OmzetTypeID <> 7
      and T_OrderHeaderAddOnIsKaPus = 'N'	";
		$resp = $this->get_rows($sql, [$date, $date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		foreach ($resp["data"] as $idx => $r) {
			$r["diskon|R"] = $r["diskon|R"];
			$result[] = $r;
		}
		return $result;
	}
	function r_092x_a4($date = "")
	{
		$sql = "
        select
        sum(a) `total|R` 
        from
        (
        select
        SsPiutangT_OrderHeaderID as id, 	
        sum(SsPiutangTestPrice) as a ,
        M_OmzetTypeName,
        M_OmzetTypeID 
        from ss_piutang
        join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        left join t_test on SsPiutangTestT_TestID = T_TestID
        left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
        left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  

        where SsPiutangDate = ?
        and SsPiutangType = 'A2'     and T_OrderHeaderAddOnIsKaPus = 'N'
        and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'
         
         union all

        select
        SsPiutangT_OrderHeaderID as id, 	
        sum(SsPiutangTestPrice)  as a,
        M_OmzetTypeName,
        M_OmzetTypeID 
        from ss_piutang
        join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'

        join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID  and T_OnlineOrderIsActive = 'Y'
        join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
        join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID and F_PaymentDetailM_PaymentTypeID = 6

        left join t_test on SsPiutangTestT_TestID = T_TestID
        left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
         
        where SsPiutangDate =?
        and SsPiutangType = 'A1'      
        and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'

        ) as x
        ";
		$xsql = "select
      sum(SsPiutangTestPrice) `total|R` 
      from ss_piutang
      join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
      join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
      left join t_test on SsPiutangTestT_TestID = T_TestID
      left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID
      left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
      left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
      left join m_mou on T_OrderHeaderM_MouID  = M_MouID
       left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
      left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
      where SsPiutangDate = ?
      and SsPiutangType = 'A2'     and T_OrderHeaderAddOnIsKaPus = 'N'
      and M_OmzetTypeID <> 7
      order by Nat_SubGroupID	";
		$resp = $this->get_rows($sql, [$date, $date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		foreach ($resp["data"] as $idx => $r) {
			$r["total|R"] = $r["total|R"];
			$result[] = $r;
		}
		return $result;
	}
	function r_092x_a3($date = "")
	{
		$sql = "select
       sum(bayar) + sum(SsPiutangDiscount)  as `total|R`  
        from 
        (
        select 
         sum(SsPiutangPaymentAmount) as bayar,
        SsPiutangDiscount,
         M_PaymentTypeName,
        M_OmzetTypeName,
        M_OmzetTypeID
        from ss_piutang
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
        left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
        left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
        where SsPiutangDate = ?
        and SsPiutangType = 'A2'  and T_OrderHeaderAddOnIsKaPus = 'N'
        and M_OmzetTypeID <> 7
        group by T_OrderHeaderID 

        union

        select
         sum(F_PaymentDetailAmount) as bayar,
        SsPiutangDiscount,
         M_PaymentTypeName,
        M_OmzetTypeName,
        M_OmzetTypeID
        from ss_piutang
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
          and T_OnlineOrderIsActive = 'Y'
        join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
        join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID and F_PaymentDetailM_PaymentTypeID = 6
        left join m_paymenttype on F_PaymentDetailM_PaymentTypeID = M_PaymentTypeID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID 
        where SsPiutangDate = ?
        and SsPiutangType = 'A1'  
        and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'
        group by T_OrderHeaderID 
      ) as x";
		$resp = $this->get_rows($sql, [$date, $date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		foreach ($resp["data"] as $idx => $r) {
			$r["total|R"] = $r["total|R"];
			$result[] = $r;
		}
		return $result;
	}
	function r_092x_a2($date = "")
	{
		$sql = "select
        SsPiutangT_OrderHeaderID as id, 	
        sum( MOD(SsPiutangPaymentAmount, 500)) as 'round|R' ,
        M_PaymentTypeName,
        M_OmzetTypeName,
        M_OmzetTypeID

        from ss_piutang
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
        left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
        left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
        where SsPiutangDate = ?
        and SsPiutangType = 'A2'  
        and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'
        and M_PaymentTypeID = '1' and T_OrderHeaderAddOnIsKaPus = 'N'	";

		$xsql = "select
        -- SsPiutangT_OrderHeaderID as id, 	
        sum( MOD(SsPiutangPaymentAmount, 500)) as `round|R` 
        -- M_PaymentTypeName,
        -- M_OmzetTypeName,
        -- M_OmzetTypeID
        from ss_piutang
        join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
        left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
        left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
        left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
        left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
        left join m_mou on T_OrderHeaderM_MouID  = M_MouID
         left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
        left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
        where SsPiutangDate = ?
        and SsPiutangType = 'A2'  
        and M_PaymentTypeID = '1' and T_OrderHeaderAddOnIsKaPus = 'N'	
        and M_OmzetTypeID <> 7
        ";
		$resp = $this->get_rows($sql, [$date]);
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$prevCompanyName = "";
		$total = 0;
		$prevPaymentType = "";
		$sub_pay_total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$r["round|R"] = $r["round|R"];
			$result[] = $r;
		}
		return $result;
	}
	function r_092x_a1($date = "")
	{
		$sql = "SELECT
			Nat_GroupID,
			Nat_GroupName as id, 	
			sum(SsPiutangTestPrice) as `bruto|R`  , 
			M_OmzetTypeName,
			M_OmzetTypeID,
			Nat_SubGroupName as grouptest,
			Nat_SubGroupID as groupTestId
        from ss_piutang
			join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
			join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
			left join t_test on SsPiutangTestT_TestID = T_TestID
			left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID and Nat_SubGroupIsActive = 'Y'
			left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
			left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
			left join m_mou on T_OrderHeaderM_MouID  = M_MouID
			left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
			left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
			left join nat_group on T_TestNat_GroupID = Nat_GroupID
			where SsPiutangDate = ?
			and SsPiutangType = 'A2'    and T_OrderHeaderAddOnIsKaPus = 'N'
			and M_OmzetTypeID <> 7
        group by  Nat_GroupID,Nat_SubGroupID,M_OmzetTypeName
        union 
        select
			Nat_GroupID,
			Nat_GroupName as id, 	
			sum(SsPiutangTestPrice) as bruto  , 
			M_OmzetTypeName,
			M_OmzetTypeID,
			Nat_SubGroupName as grouptest,
			Nat_SubGroupID as groupTestId
        from ss_piutang
			join ss_piutang_test on SsPiutangTestSsPiutangID = SsPiutangID
			join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
			join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID  and T_OnlineOrderIsActive = 'Y'
			join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
			join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID and F_PaymentDetailM_PaymentTypeID = 6
			left join t_test on SsPiutangTestT_TestID = T_TestID
			left join nat_subgroup on T_TestNat_SubgroupID = Nat_SubGroupID and Nat_SubGroupIsActive = 'Y'
			left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
			left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
			left join m_mou on T_OrderHeaderM_MouID  = M_MouID
			left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
			left join nat_group on T_TestNat_GroupID = Nat_GroupID
			where SsPiutangDate = ?
			and SsPiutangType = 'A1'    
			and M_OmzetTypeID <> '7' and M_OmzetTypeIsActive = 'Y'
        group by  Nat_GroupID,Nat_SubGroupID,M_OmzetTypeName
        order by M_OmzetTypeName, Nat_GroupID ";
		$resp = $this->get_rows($sql, [$date, $date]);

		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["bruto|R"];
			$r["bruto|R"] = $r["bruto|R"];
			$result[] = $r;
		}
		/*
        $result[] = [
            "Nat_GroupID" => "",
            "id" => "Total",
            "bruto|R" => number_format($total, 0, "", " "),
            "M_OmzetTypeName" => "",
            "M_OmzetTypeID" => "",
            "grouptest" => "",
        ];
        */
		return $result;
	}

	function r_092x($date = "")
	{
		if (true) {
			$sql = "SELECT Nat_BankIsEDC,
			Nat_BankID,
			M_PaymentTypeCode,
			-- SsPiutangT_OrderHeaderID as id, 
			ifnull(M_BankAccountNo,'') BankAccountNo,
			case when M_PaymentTypeName='RK' then concat(M_PaymentTypeName,'|', fn_get_asal_rk(SsPiutangT_OrderHeaderID) )  
			else 
			concat(M_PaymentTypeName,'|' , ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,'')) 
			end as M_PaymentTypeName,
			sum(SsPiutangPaymentAmount  -  if(M_PaymentTypeID =  '1' , MOD(SsPiutangPaymentAmount, 500) ,'')) as total 
			from ss_piutang
			join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
			join m_company on T_OrderHeaderM_CompanyID = M_CompanyID 
			left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID
			left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
			left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
			left join m_mou on T_OrderHeaderM_MouID  = M_MouID
			left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
			left join t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID  
			left join m_bank_account on SsPiutangPaymentM_BankAccountID = M_BankAccountID
			left join  nat_bank on Nat_BankID = M_BankAccountNat_BankID
			where SsPiutangDate = ?
			and SsPiutangType = 'A2' and T_OrderHeaderAddOnIsKaPus = 'N'
			and M_OmzetTypeID <> 7
			group by M_PaymentTypeName, Nat_BankID ,M_BankAccountID
			union 
			SELECT  
			-- SsPiutangT_OrderHeaderID as id, 
			Nat_BankIsEDC,
			Nat_BankID,	
			M_PaymentTypeCode,
			ifnull(M_BankAccountNo,'') BankAccountNo,
			concat('DP ', M_PaymentTypeName,'|' , ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,'')) as M_PaymentTypeName,
			sum(F_PaymentDetailAmount  ) as total 
			from ss_piutang
			join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
			join t_onlineorder on T_OrderHeaderID = T_OnlineOrderT_OrderHeaderID 
				and T_OnlineOrderIsActive = 'Y'
			join f_payment on F_PaymentT_OrderHeaderID = T_OrderHeaderID and F_PaymentIsActive = 'Y'
			join f_paymentdetail on F_PaymentID = F_PaymentDetailF_PaymentID and F_PaymentDetailM_PaymentTypeID = 6
			left join m_paymenttype on F_PaymentDetailM_PaymentTypeID = M_PaymentTypeID
			left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
			left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
			left join m_mou on T_OrderHeaderM_MouID  = M_MouID
			left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
			left join m_bank_account on F_PaymentDetailM_BankAccountID = M_BankAccountID
			left join  nat_bank on Nat_BankID = M_BankAccountNat_BankID
			where SsPiutangDate = ?
			and SsPiutangType = 'A1'
			group by M_PaymentTypeName, Nat_BankID ,M_BankAccountID
			order by M_PaymentTypeName";

			$resp = $this->get_rows($sql, [$date, $date]);
		}
		if ($resp["status"] == -1) {
			echo "Error: " . $resp["message"];
			exit();
		}
		if (count($resp["data"]) == 0) {
			return [];
		}
		$result = [];
		$prevCompanyName = "";
		$total = 0;
		$prevPaymentType = "";
		$sub_pay_total = 0;
		foreach ($resp["data"] as $idx => $r) {
			$total += $r["total"];
			$r["total|R"] = $r["total"];
			unset($r["total"]);

			$prevCompanyName = $r["M_CompanyName"];
			$prevPaymentType = $r["M_PaymentTypeName"];
			$result[] = $r;
		}
		return $result;
	}

	function get_rows($sql, $param = false)
	{
		if ($param) {
			$qry = $this->db->query($sql, $param);
		} else {
			$qry = $this->db->query($sql);
		}
		if (!$qry) {
			return [
				"status" => -1,
				"message" =>
				$this->db->last_query() .
					"|" .
					$this->db->error()["message"],
			];
		}
		return ["status" => 0, "data" => $qry->result_array()];
	}
	function get_row($sql, $param = false)
	{
		$resp = $this->get_rows($sql, $param);
		if ($resp["status"] == -1) {
			return $resp;
		}
		if (count($resp["data"]) == 0) {
			return ["status" => 0, "message" => "Not found."];
		}
		return ["status" => 1, "data" => $resp["data"][0]];
	}
	public function print_table_style()
	{
		echo "
        <style>
        th, td {
            padding: 15px;
            text-align: left;
          }
          tr:nth-child(even) {background-color: #f2f2f2;}
          table {
            border: solid 1px ;
            min-width:600px;
          }
        </style>
        ";
	}
	public function print_table($rows, $keys, $title = false)
	{
		echo "<table>";
		if ($title) {
			$col_span = count($keys);
			echo "<tr>";
			echo "<th colspan=$col_span>$title</th>";
			echo "</tr>";
		}
		echo "<tr>";
		foreach ($keys as $k) {
			$k = str_replace("|R", "", $k);
			echo "<td>$k</td>";
		}
		echo "</tr>\n";
		foreach ($rows as $r) {
			echo "<tr>";
			foreach ($keys as $k) {
				if (in_array($k, ["Debit", "Debit", "Kredit"])) {
					echo "<td style='text-align:right' >" .
						$this->xformat($r[$k]) .
						"</td>";
				} else {
					echo "<td>" . $r[$k] . "</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
	}
	function load_db()
	{
		$config = [
			"dsn" => "",
			"hostname" => "192.168.55.227",
			"username" => "xone",
			"password" => "xone!xx123",
			"database" => "autocon_v2",
			"dbdriver" => "mysqli",
			"dbprefix" => "",
			"pconnect" => false,
			"db_debug" => false,
			"cache_on" => false,
			"cachedir" => "",
			"char_set" => "utf8",
			"dbcollat" => "utf8_general_ci",
			"swap_pre" => "",
			"encrypt" => false,
			"compress" => false,
			"stricton" => false,
			"failover" => [],
			"save_queries" => true,
		];
		return $this->load->database($config, true);
	}
}
/*
create table jurnal (
  jurnalID int not null auto_increment primary key,
  jurnalDate date,
  jurnalBranchCode varchar(3),
  jurnalRef varchar(100),
  jurnalNote varchar(100),
  jurnalDebit decimal(15),
  jurnalKredit decimal(15),
  jurnalCreated datetime default current_timestamp(),
  key(jurnalDate),
  key(jurnalBranchCode),
  key(jurnalRef)
);

            "Date" => $date,
            "BranchCode" => $branchCode ,
            "Ref" => $k,
            "Deskripsi" => $r["M_OmzetTypeName"] . "/" . $r["M_CompanyName"] ,
            "Debit" => $r["total|R"],
            "ProductDesc" => "",
            "Product" => "",
            "Kredit" => ""
create table ar(
  arID int not null auto_increment primary key,
  arDate date,
  arBranchCode varchar(3),
  arRef varchar(100),
  arDeskripsi varchar(100),
  arDebit decimal(10),
  arProductDesc varchar(100),
  arProduct varchar(20),
  arKredit decimal(10),
  arCreatedDate datetime default current_timestamp(),
  key(arDate),
  key(arBranchCode)
);

create table receive_payment(
  rcvPaymentID int not null auto_increment primary key,
  rcvPaymentDate date,
  rcvPaymentArDate date,
  rcvPaymentBranchCode varchar(3),
  rcvPaymentRef varchar(100),
  rcvPaymentPay varchar(100),
  rcvPaymentKdPelanggan varchar(100),
  rcvPaymentDeskripsi varchar(100),
  rcvPaymentTipeBayar varchar(100),
  rcvPaymentAccount varchar(100),
  rcvPaymentPaymentMethode varchar(100),
  rcvPaymentDebit decimal(10),
  rcvPaymentCreatedDate datetime default current_timestamp(),
  key(rcvPaymentDate),
  key(rcvPaymentBranchCode)
);
)
*/
