<?php
class Test extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->db_onelite = $this->load->database('onelite', true);
	}

	function getdataflutter()
	{
		$arr = array(
			"branchID" => 1,
			"branchName" => "Cabang Marthadinata",
			"address" => "Jl Marthadinata No. 46 Bandung",
			"test" => array('SGOT', 'SGPT', 'Hematologi Lengkap', 'Urine Rutin')
		);
		echo json_encode(array('status' => 'OK', 'rows' => $arr));
	}

	function getListingTest()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}
		$prm = $this->sys_input;
		$branchID = 1000;
		if ($prm['date'] == '') $prm['date'] = date('Y-m-d');
		//print_r($prm['selected_category']);
		$categories = isset($prm['selected_category']) ? $prm['selected_category'] : [0];

		$param = array(
			"branchID" => $prm['id'],
			"name" => $prm['search'],
			"categoryID" =>  $categories,
			"page" => $prm['current_page'],
			"maxResult" => 25,
			"date" => $prm['date'],
			"is_packet" => 'N',
			"test_id" => 0,
			"packet_id" => 0
		);
		//print_r($param);
		$this->load->library("Quota_v2");
		$data_test = $this->quota_v2->search($param);
		if ($data_test) {
			//print_r($data_test['rows']);
			$new_array = array();
			foreach ($data_test['rows'] as $k => $v) {
				$schedule = array();
				$is_covid = 'N';
				$x_requirement = $v['requirement'];
				$requirement = $v['requirement'];
				$value_requirement = 'X';

				if (count($requirement) > 0) {
					$value_requirement = 'N';
				}


				if (count($v['schedule']) > 0) {
					//$is_covid = 'Y';

					$schedules = array();
					foreach ($v['schedule'] as $k_sec => $v_sec) {
						$schedule = array('nat_testid' =>  $v_child['Nat_TestID'], 'testname' => $v_child['T_TestName'], 'date' => '', 'time' => '', 'datetime' => '');
						array_push($schedules, $schedule);
					}

					$v['schedule'] = $schedules;
				}

				$variants = array();
				if (intval($v['T_PriceAmountCito']) > 0) {
					$variants = array(
						array('name' => 'Reguler', 'flag_cito' => 'N', 'bruto' =>  $v['T_PriceAmount'], 'price' => $v['T_PriceTotal'], 'selected' => true),
						array('name' => 'Cito', 'flag_cito' => 'Y', 'bruto' =>  $v['T_PriceAmountCito'], 'price' => $v['T_PriceTotalCito'], 'selected' => false)
					);
				}

				$new_data = array(
					'moud_id' => $v['Ss_PriceMouM_MouID'],
					'bruto' => $v['T_PriceAmount'],
					'price' => $v['T_PriceTotal'],
					'name' => $v['T_TestName'],
					'description' => $v['description'] == '' ? '-' : $v['description'],
					'type' => $v['is_packet'] == 'Y' ? 'PANEL' : 'TEST',
					'nat_test' => json_decode($v['nat_test']),
					'packet_id' => $v['packet_id'],
					'requirement' => $requirement,
					'value_requirement' => $value_requirement,
					'schedule' => $schedule,
					'is_covid' => $is_covid,
					'variants' => $variants
				);
				array_push($new_array, $new_data);
			}
			//print_r($new_array);

			echo json_encode(array('status' => 'OK', 'rows' => $new_array));
			//echo json_encode( $data_test);

		} else {
			echo json_encode($data_test);
		}
		//echo json_encode( $data_test);

	}

	function getListingAllPacket()
	{

		$prm = $this->sys_input;
		$xuser = $this->sys_user;
		$user = $this->isLogin ? $xuser['M_UserID'] : 0;
		$branch_id = $prm['id'];
		$search = $prm['search'];

		if ($prm['date'] == '') $prm['date'] = date('Y-m-d');
		//print_r($prm['selected_category']);
		$category_id = $prm['selected_category'];
		$number_limit = 25;
		$number_offset = (intval($prm['current_page']) - 1) * $number_limit;

		$sql = "SELECT Ss_PriceMouID as xid,
				T_PriceAmount as bruto,
				T_PacketOnlineDesc as description,
				'N' as is_covid,
				Ss_PriceMouM_MouID as mou_id,
				ss_price_mou_v2.T_TestID as test_id,
				ss_price_mou_v2.T_TestName as name,
				nat_test,
				packet_id,
				GROUP_CONCAT(DISTINCT CONCAT(T_PriceIsCito,'^',T_PriceTotal) SEPARATOR '+') as prices,
				'' as price,
				'' as requirement,
				'' as schedule,
				px_type as type,
				'X' as value_requirement,
				child_test as childs,
				is_packet,
				'N' as is_cito,
				'N' as is_favorite,
				'' as chx
				FROM ss_price_mou_v2 
				JOIN t_packet ON T_PacketM_BranchID = {$branch_id} AND T_PacketID = ss_price_mou_v2.packet_id
				WHERE 
				Ss_PriceMouM_BranchID = {$branch_id} AND is_packet = 'Y' AND
				ss_price_mou_v2.T_TestName LIKE CONCAT('%','{$search}','%') 
				GROUP BY ss_price_mou_v2.T_TestID 
				ORDER BY T_PriceTotal ASC
				limit $number_limit offset $number_offset ";
		//echo $sql;
		$data_test = $this->db_onelite->query($sql)->result_array();
		if ($data_test) {
			foreach ($data_test as $k => $v) {
				$data_test[$k]['chx'] = false;
				$prices = $v['prices'];
				$x_prices = explode('+', $prices);
				//print_r($x_prices);
				$data_prices = array();
				$arr_min = array();
				if ($x_prices) {
					foreach ($x_prices as $k_prices => $v_prices) {
						$x_v_prices = explode('^', $v_prices);
						$x_min = intval($x_v_prices[1]);
						array_push($arr_min, $x_min);
						array_push($data_prices, array('cito' => $x_v_prices[0], 'amount' => intval($x_v_prices[1])));
					}
				}
				if ($v['type'] == 'PX')
					$data_test[$k]['childs'] = [];
				else
					$data_test[$k]['childs'] = json_decode($v['childs']);

				$data_test[$k]['bruto'] = intval($v['bruto']);
				$data_test[$k]['price'] = min($arr_min);
				$data_test[$k]['prices'] = $data_prices;
				$data_test[$k]['nat_test'] = json_decode($v['nat_test']);
				$data_test[$k]['schedule'] = [];
				$data_test[$k]['requirement'] = [];
				$requirements = [];
				if ($v['is_packet'] == 'Y') {
					foreach ($data_test[$k]['childs'] as $key => $value) {
						//echo "SELECT fn_fo_requirement_get('{$value->T_TestID}') x";
						$x = $this->db_onelite->query("SELECT fn_fo_requirement_get('{$value->T_TestID}') x")
							->row();
						if ($x->x != null) {
							$rqmnt = json_decode($x->x);
							foreach ($rqmnt as $k_rqmnt => $val_rqmnt) {
								if ($this->check_requirements_exist($requirements, $val_rqmnt) == -1)
									array_push($requirements, $val_rqmnt);
							}
						}
					}
					//print_r($requirements);
					$data_test[$k]['requirement'] = $requirements;
					if (count($requirements) > 0) {
						$data_test[$k]['value_requirement'] = 'N';
					}
				}

				$schedules = [];
				if ($data_test[$k]['childs'] && count($data_test[$k]['childs']) > 0) {
					foreach ($data_test[$k]['childs'] as $k_child => $v_child) {
						$sql = "SELECT *
								FROM t_subcategory_test
								JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
								WHERE
								T_SubcategoryTestT_TestID = {$v_child->T_TestID} AND 
								T_SubCategoryIsSchedule = 'Y' AND
								T_SubcategoryTestIsActive = 'Y'
								LIMIT 1";
						//if(intval($v['test_id']) == 3606)
						//echo $sql;
						$rst_child = $this->db_onelite->query($sql)->row_array();
						if ($rst_child) {
							$schedule = array('is_cito' => $v_child->T_PriceIsCito, 'test_id' => $v_child->T_TestID, 'nat_testid' =>  $v_child->Nat_TestID, 'testname' => $v_child->T_TestName, 'date' => '', 'time' => '', 'datetime' => '');
							//if(intval($v['test_id']) == 3606)
							//print_r($schedule);
							array_push($schedules, $schedule);
						}
					}
				}
				$data_test[$k]['schedule'] = $schedules;
			}

			echo json_encode(array('status' => 'OK', 'rows' => $data_test));
		} else {
			echo json_encode(array('status' => 'OK', 'rows' => []));
		}
	}


	function check_requirements_exist($requirements, $requirement)
	{
		$rtn = -1;
		foreach ($requirements as $key => $value) {
			if (intval($value->req_id) == intval($requirement->req_id))
				$rtn = $key;
		}
		return $rtn;
	}

	function getListingTestByCategory()
	{

		$prm = $this->sys_input;
		$xuser = $this->sys_user;
		$user = $this->isLogin ? $xuser['M_UserID'] : 0;
		$branch_id = $prm['id'];
		// $branch_id = intval($prm['id']);

		if ($prm['date'] == '') $prm['date'] = date('Y-m-d');
		//print_r($prm['selected_category']);
		$category_id = $prm['selected_category'];
		$number_limit = 25;
		$number_offset = (intval($prm['current_page']) - 1) * $number_limit;

		// echo ("BranchID: " . $branch_id);
		// echo ("Categ: " . $category_id);
		// echo ("\n");

		$sql = "SELECT Ss_PriceMouID as xid,
				T_PriceAmount as bruto,
				IFNULL(NatOlDescriptionNote,'-') as description,
				IF(isnull(M_SpecialFlagID),'N',IF(M_SpecialFlagisCovid = 'Y','Y','N')) as is_covid,
				Ss_PriceMouM_MouID as mou_id,
				ss_price_mou_v2.T_TestID as test_id,
				t_test.T_TestNat_TestID as nat_testid,
				IFNULL(Nat_TestShortNameBarcode,ss_price_mou_v2.T_TestName) as name,
				nat_test,
				packet_id,
				GROUP_CONCAT(DISTINCT CONCAT(T_PriceIsCito,'^',T_PriceTotal) SEPARATOR '+') as prices,
				'' as price,
				'' as requirement,
				'' as schedule,
				px_type as type,
				'X' as value_requirement,
				child_test as childs,
				is_packet,
				if(px_type = 'PX',T_PriceIsCito,'N') as is_cito,
				IF(ISNULL(M_TestFavoritID),'N','Y') as is_favorite,
				'' as chx
				FROM ss_price_mou_v2 
				JOIN t_test ON ss_price_mou_v2.T_TestID = t_test.T_TestID
				JOIN nat_test ON t_test.T_TestNat_TestID = nat_test.Nat_TestID
				LEFT JOIN nat_ol_description ON NatOlDescriptionNat_TestID = nat_test.Nat_TestID AND NatOlDescriptionIsActive = 'Y'
				JOIN nat_ol_category_test ON Nat_OLCategoryTestNat_TestID = t_test.T_TestNat_TestID AND
				Nat_OLCategoryTestNat_OLCategoryID = {$category_id} AND Nat_OLCategoryTestIsActive = 'Y'
				LEFT JOIN m_specialflag ON M_SpecialFlagNat_TestID = t_test.T_TestNat_TestID AND M_SpecialFlagIsActive = 'Y'
				LEFT JOIN m_testfavorit ON M_TestFavoritT_TestID = ss_price_mou_v2.T_TestID AND 
				M_TestFavoritM_UserID = $user AND M_TestFavoritIsActive = 'Y'
				WHERE 
				Ss_PriceMouM_BranchID = {$branch_id} AND is_packet = 'N'
				GROUP BY ss_price_mou_v2.T_TestID 
				limit $number_limit offset $number_offset ";

		echo ($sql);

		$data_test = $this->db_onelite->query($sql)->result_array();
		if ($data_test) {
			foreach ($data_test as $k => $v) {
				$data_test[$k]['chx'] = false;
				$prices = $v['prices'];
				$x_prices = explode('+', $prices);
				//print_r($x_prices);
				$data_prices = array();
				$arr_min = array();
				if ($x_prices) {
					foreach ($x_prices as $k_prices => $v_prices) {
						$x_v_prices = explode('^', $v_prices);
						$x_min = intval($x_v_prices[1]);
						array_push($arr_min, $x_min);
						array_push($data_prices, array('cito' => $x_v_prices[0], 'amount' => intval($x_v_prices[1])));
					}
				}
				if ($v['type'] == 'PX')
					$data_test[$k]['childs'] = [];
				else
					$data_test[$k]['childs'] = json_decode($v['childs']);

				$data_test[$k]['bruto'] = intval($v['bruto']);
				$data_test[$k]['price'] = min($arr_min);
				$data_test[$k]['prices'] = $data_prices;
				$data_test[$k]['nat_test'] = json_decode($v['nat_test']);
				$data_test[$k]['schedule'] = [];
				$data_test[$k]['requirement'] = [];
				if ($v['type'] == "PX" || $v['type'] == "PXR") {
					$x = $this->db_onelite->query("SELECT fn_fo_requirement_get('{$v['test_id']}') x")
						->row();
					if ($x->x != null) {
						$data_test[$k]['requirement'] = json_decode($x->x);
						$data_test[$k]['value_requirement'] = 'N';
					}
				}

				$schedules = [];
				if ($data_test[$k]['childs'] && count($data_test[$k]['childs']) > 0) {
					$new_childs = [];
					foreach ($data_test[$k]['childs'] as $k_child => $v_child) {
						$sql = "SELECT *
								FROM t_subcategory_test
								JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
								WHERE
								T_SubcategoryTestT_TestID = {$v_child->T_TestID} AND 
								T_SubCategoryIsSchedule = 'Y' AND
								T_SubcategoryTestIsActive = 'Y'
								LIMIT 1";
						$rst_child = $this->db_onelite->query($sql)->row_array();
						if ($rst_child) {
							$schedule = array('is_cito' => $v_child->T_PriceIsCito, 'test_id' => $v_child->T_TestID, 'nat_testid' =>  $v_child->Nat_TestID, 'testname' => $v_child->T_TestName, 'date' => '', 'time' => '', 'datetime' => '');
							array_push($schedules, $schedule);
						}

						$sql = "SELECT *
								FROM nat_test
								WHERE
								Nat_TestID = {$v_child->Nat_TestID} AND 
								Nat_TestIsActive = 'Y'
								LIMIT 1";
						$rst_new_child = $this->db_onelite->query($sql)->row_array();
						if ($rst_new_child) {
							if ($rst_new_child['Nat_TestIsPrintNota'] == 'Y')
								array_push($new_childs, $v_child);
						}
					}
					$data_test[$k]['childs'] = $new_childs;
				} else {
					$sql = "SELECT *
							FROM t_subcategory_test
							JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
							WHERE
							T_SubcategoryTestT_TestID = {$v['test_id']} AND 
							T_SubCategoryIsSchedule = 'Y' AND
							T_SubcategoryTestIsActive = 'Y'
							LIMIT 1";
					$rst_child = $this->db_onelite->query($sql)->row_array();
					if ($rst_child) {
						$schedule = array('is_cito' => $v['is_cito'], 'test_id' => $v['test_id'], 'nat_testid' =>  $v['nat_testid'], 'testname' => $v['name'], 'date' => '', 'time' => '', 'datetime' => '');
						array_push($schedules, $schedule);
					}
				}
				$data_test[$k]['schedule'] = $schedules;
			}

			echo json_encode(array('status' => 'OK', 'rows' => $data_test));
		} else {
			echo json_encode(array('status' => 'OK', 'rows' => []));
		}
	}

	function getListingTestAll()
	{

		$prm = $this->sys_input;
		$branch_id = $prm['id'];
		$xuser = $this->sys_user;
		$user = $this->isLogin ? $xuser['M_UserID'] : 0;


		//print_r($prm['selected_category']);
		$search = $prm['search'];
		$number_limit = 25;
		$number_offset = (intval($prm['current_page']) - 1) * $number_limit;

		$sql = "SELECT Ss_PriceMouID as xid,
				T_PriceAmount as bruto,
				IFNULL(NatOlDescriptionNote,'-') as description,
				IF(isnull(M_SpecialFlagID),'N',IF(M_SpecialFlagisCovid = 'Y','Y','N')) as is_covid,
				Ss_PriceMouM_MouID as mou_id,
				ss_price_mou_v2.T_TestID as test_id,
				t_test.T_TestNat_TestID as nat_testid,
				IFNULL(Nat_TestShortNameBarcode,ss_price_mou_v2.T_TestName) as name,
				nat_test,
				packet_id,
				GROUP_CONCAT(DISTINCT CONCAT(T_PriceIsCito,'^',T_PriceTotal) SEPARATOR '+') as prices,
				'' as price,
				'' as requirement,
				'' as schedule,
				px_type as type,
				'X' as value_requirement,
				child_test as childs,
				is_packet,
				if(px_type = 'PX',T_PriceIsCito,'N') as is_cito,
				IF(ISNULL(M_TestFavoritID),'N','Y') as is_favorite
				FROM ss_price_mou_v2 
				JOIN t_test ON ss_price_mou_v2.T_TestID = t_test.T_TestID
				JOIN nat_test ON t_test.T_TestNat_TestID = nat_test.Nat_TestID
				JOIN nat_ol_category_test ON Nat_OLCategoryTestNat_TestID = nat_test.Nat_TestID AND Nat_OLCategoryTestIsActive = 'Y'
				LEFT JOIN nat_ol_description ON NatOlDescriptionNat_TestID = nat_test.Nat_TestID AND NatOlDescriptionIsActive = 'Y'
				LEFT JOIN m_specialflag ON M_SpecialFlagNat_TestID = t_test.T_TestNat_TestID AND M_SpecialFlagIsActive = 'Y'
				LEFT JOIN m_testfavorit ON M_TestFavoritT_TestID = ss_price_mou_v2.T_TestID AND 
				M_TestFavoritM_UserID = $user AND M_TestFavoritIsActive = 'Y'
				WHERE 
				Ss_PriceMouM_BranchID = {$branch_id} AND 
				ss_price_mou_v2.T_TestName LIKE CONCAT('%','{$search}','%') AND
				is_packet = 'N'
				GROUP BY ss_price_mou_v2.T_TestID 
				limit $number_limit offset $number_offset ";
		//	echo $sql;
		$data_test = $this->db_onelite->query($sql)->result_array();
		//echo $this->db_onelite->last_query();
		if ($data_test) {
			foreach ($data_test as $k => $v) {
				$prices = $v['prices'];
				$x_prices = explode('+', $prices);
				//print_r($x_prices);
				$data_prices = array();
				$arr_min = array();
				if ($x_prices) {
					foreach ($x_prices as $k_prices => $v_prices) {
						$x_v_prices = explode('^', $v_prices);
						$x_min = intval($x_v_prices[1]);
						array_push($arr_min, $x_min);
						array_push($data_prices, array('cito' => $x_v_prices[0], 'amount' => intval($x_v_prices[1])));
					}
				}
				if ($v['type'] == 'PX')
					$data_test[$k]['childs'] = [];
				else
					$data_test[$k]['childs'] = json_decode($v['childs']);

				$data_test[$k]['bruto'] = intval($v['bruto']);
				$data_test[$k]['price'] = min($arr_min);
				$data_test[$k]['prices'] = $data_prices;
				$data_test[$k]['nat_test'] = json_decode($v['nat_test']);
				$data_test[$k]['schedule'] = [];
				$data_test[$k]['requirement'] = [];
				if ($v['type'] == "PX" || $v['type'] == "PXR") {
					$x = $this->db_onelite->query("SELECT fn_fo_requirement_get('{$v['test_id']}') x")
						->row();
					if ($x->x != null) {
						$data_test[$k]['requirement'] = json_decode($x->x);
						$data_test[$k]['value_requirement'] = 'N';
					}
				}

				$schedules = [];
				if ($data_test[$k]['childs'] && count($data_test[$k]['childs']) > 0) {
					$new_childs = [];
					foreach ($data_test[$k]['childs'] as $k_child => $v_child) {
						$sql = "SELECT *
								FROM t_subcategory_test
								JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
								WHERE
								T_SubcategoryTestT_TestID = {$v_child->T_TestID} AND 
								T_SubCategoryIsSchedule = 'Y' AND
								T_SubcategoryTestIsActive = 'Y'
								LIMIT 1";
						//	echo $sql;
						$rst_child = $this->db_onelite->query($sql)->row_array();
						if ($rst_child) {
							$schedule = array('is_cito' => $v_child->T_PriceIsCito, 'test_id' => $v_child->T_TestID, 'nat_testid' =>  $v_child->Nat_TestID, 'testname' => $v_child->T_TestName, 'date' => '', 'time' => '', 'datetime' => '');
							array_push($schedules, $schedule);
						}

						$sql = "SELECT *
								FROM nat_test
								WHERE
								Nat_TestID = {$v_child->Nat_TestID} AND 
								Nat_TestIsActive = 'Y'
								LIMIT 1";
						$rst_new_child = $this->db_onelite->query($sql)->row_array();
						if ($rst_new_child) {
							if ($rst_new_child['Nat_TestIsPrintNota'] == 'Y')
								array_push($new_childs, $v_child);
						}
					}
					$data_test[$k]['childs'] = $new_childs;
				} else {
					$sql = "SELECT *
							FROM t_subcategory_test
							JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
							WHERE
							T_SubcategoryTestT_TestID = {$v['test_id']} AND 
							T_SubCategoryIsSchedule = 'Y' AND
							T_SubcategoryTestIsActive = 'Y'
							LIMIT 1";
					//echo $sql;
					$rst_child = $this->db_onelite->query($sql)->row_array();
					if ($rst_child) {
						$schedule = array('is_cito' => $v['is_cito'], 'test_id' => $v['test_id'], 'nat_testid' =>  $v['nat_testid'], 'testname' => $v['name'], 'date' => '', 'time' => '', 'datetime' => '');
						array_push($schedules, $schedule);
					}
				}


				$data_test[$k]['schedule'] = $schedules;
			}

			echo json_encode(array('status' => 'OK', 'rows' => $data_test));
		} else {
			echo json_encode(array('status' => 'OK', 'rows' => []));
		}
	}

	function getListingTestAllHS()
	{

		$prm = $this->sys_input;
		$branch_id = $prm['id'];
		$xuser = $this->sys_user;
		$user = $this->isLogin ? $xuser['M_UserID'] : 0;


		//print_r($prm['selected_category']);
		$search = $prm['search'];
		$number_limit = 25;
		$number_offset = (intval($prm['current_page']) - 1) * $number_limit;

		$sql = "SELECT Ss_PriceMouID as xid,
				T_PriceAmount as bruto,
				IFNULL(NatOlDescriptionNote,'-') as description,
				IF(isnull(M_SpecialFlagID),'N',IF(M_SpecialFlagisCovid = 'Y','Y','N')) as is_covid,
				Ss_PriceMouM_MouID as mou_id,
				ss_price_mou_v2.T_TestID as test_id,
				t_test.T_TestNat_TestID as nat_testid,
				IFNULL(Nat_TestShortNameBarcode,ss_price_mou_v2.T_TestName) as name,
				nat_test,
				packet_id,
				GROUP_CONCAT(DISTINCT CONCAT(T_PriceIsCito,'^',T_PriceTotal) SEPARATOR '+') as prices,
				'' as price,
				'' as requirement,
				'' as schedule,
				px_type as type,
				'X' as value_requirement,
				child_test as childs,
				is_packet,
				if(px_type = 'PX',T_PriceIsCito,'N') as is_cito,
				IF(ISNULL(M_TestFavoritID),'N','Y') as is_favorite
				FROM ss_price_mou_v2 
				JOIN t_test ON ss_price_mou_v2.T_TestID = t_test.T_TestID
				JOIN hs_test ON HS_TestT_TestID = t_test.T_TestID AND HS_TestIsActive = 'Y' AND 
				HS_TestM_BranchID = {$branch_id}
				JOIN nat_test ON t_test.T_TestNat_TestID = nat_test.Nat_TestID
				JOIN nat_ol_category_test ON Nat_OLCategoryTestNat_TestID = nat_test.Nat_TestID AND Nat_OLCategoryTestIsActive = 'Y'
				LEFT JOIN nat_ol_description ON NatOlDescriptionNat_TestID = nat_test.Nat_TestID AND NatOlDescriptionIsActive = 'Y'
				LEFT JOIN m_specialflag ON M_SpecialFlagNat_TestID = t_test.T_TestNat_TestID AND M_SpecialFlagIsActive = 'Y'
				LEFT JOIN m_testfavorit ON M_TestFavoritT_TestID = ss_price_mou_v2.T_TestID AND 
				M_TestFavoritM_UserID = $user AND M_TestFavoritIsActive = 'Y'
				WHERE 
				Ss_PriceMouM_BranchID = {$branch_id} AND 
				ss_price_mou_v2.T_TestName LIKE CONCAT('%','{$search}','%') AND
				is_packet = 'N'
				GROUP BY ss_price_mou_v2.T_TestID 
				limit $number_limit offset $number_offset ";
		//	echo $sql;
		$data_test = $this->db_onelite->query($sql)->result_array();
		//echo $this->db_onelite->last_query();
		if ($data_test) {
			foreach ($data_test as $k => $v) {
				$prices = $v['prices'];
				$x_prices = explode('+', $prices);
				//print_r($x_prices);
				$data_prices = array();
				$arr_min = array();
				if ($x_prices) {
					foreach ($x_prices as $k_prices => $v_prices) {
						$x_v_prices = explode('^', $v_prices);
						$x_min = intval($x_v_prices[1]);
						array_push($arr_min, $x_min);
						array_push($data_prices, array('cito' => $x_v_prices[0], 'amount' => intval($x_v_prices[1])));
					}
				}
				if ($v['type'] == 'PX')
					$data_test[$k]['childs'] = [];
				else
					$data_test[$k]['childs'] = json_decode($v['childs']);

				$data_test[$k]['bruto'] = intval($v['bruto']);
				$data_test[$k]['price'] = min($arr_min);
				$data_test[$k]['prices'] = $data_prices;
				$data_test[$k]['nat_test'] = json_decode($v['nat_test']);
				$data_test[$k]['schedule'] = [];
				$data_test[$k]['requirement'] = [];
				if ($v['type'] == "PX" || $v['type'] == "PXR") {
					$x = $this->db_onelite->query("SELECT fn_fo_requirement_get('{$v['test_id']}') x")
						->row();
					if ($x->x != null) {
						$data_test[$k]['requirement'] = json_decode($x->x);
						$data_test[$k]['value_requirement'] = 'N';
					}
				}

				$schedules = [];
				if ($data_test[$k]['childs'] && count($data_test[$k]['childs']) > 0) {
					$new_childs = [];
					foreach ($data_test[$k]['childs'] as $k_child => $v_child) {
						/*$sql = "SELECT *
								FROM t_subcategory_test
								JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
								WHERE
								T_SubcategoryTestT_TestID = {$v_child->T_TestID} AND 
								T_SubCategoryIsSchedule = 'Y' AND
								T_SubcategoryTestIsActive = 'Y'
								LIMIT 1";
							//	echo $sql;
						$rst_child = $this->db_onelite->query($sql)->row_array();
						if($rst_child){
							$schedule = array('is_cito' => $v_child->T_PriceIsCito,'test_id' => $v_child->T_TestID,'nat_testid' =>  $v_child->Nat_TestID,'testname' => $v_child->T_TestName,'date' => '','time' => '', 'datetime' => '');
							array_push($schedules,$schedule);
						}*/

						$sql = "SELECT *
								FROM nat_test
								WHERE
								Nat_TestID = {$v_child->Nat_TestID} AND 
								Nat_TestIsActive = 'Y'
								LIMIT 1";
						$rst_new_child = $this->db_onelite->query($sql)->row_array();
						if ($rst_new_child) {
							if ($rst_new_child['Nat_TestIsPrintNota'] == 'Y')
								array_push($new_childs, $v_child);
						}
					}
					$data_test[$k]['childs'] = $new_childs;
				} else {
					/*$sql = "SELECT *
							FROM t_subcategory_test
							JOIN t_subcategory ON T_SubcategoryTestT_SubcategoryID = T_SubcategoryID AND T_SubCategoryIsActive = 'Y'
							WHERE
							T_SubcategoryTestT_TestID = {$v['test_id']} AND 
							T_SubCategoryIsSchedule = 'Y' AND
							T_SubcategoryTestIsActive = 'Y'
							LIMIT 1";
							//echo $sql;
					$rst_child = $this->db_onelite->query($sql)->row_array();
					if($rst_child){
						$schedule = array('is_cito' => $v['is_cito'],'test_id' => $v['test_id'],'nat_testid' =>  $v['nat_testid'],'testname' => $v['name'],'date' => '','time' => '', 'datetime' => '');
						array_push($schedules,$schedule);
					}*/
				}


				$data_test[$k]['schedule'] = $schedules;
			}

			echo json_encode(array('status' => 'OK', 'rows' => $data_test));
		} else {
			echo json_encode(array('status' => 'OK', 'rows' => []));
		}
	}


	function addFavorite()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}
		$prm = $this->sys_input;
		$user = $this->sys_user;
		$sql = "INSERT INTO m_testfavorit(
					M_TestFavoritT_TestID,
					M_TestFavoritM_UserID,
					M_TestFavoritUserID,
					M_TestFavoritCreated
				) VALUES ( ?, ?, ?,NOW())";
		$query = $this->db_onelite->query($sql, array($prm['test_id'], $user['M_UserID'], $user['M_UserID']));
		if ($query)
			echo json_encode(array('status' => 'OK', 'message' => 'Berhasil ditambahkan sebagai favorit'));
		else
			echo json_encode(array('status' => 'ERROR', 'message' => 'Maaf gagal menambahkan favorite'));
	}

	function removeFavorite()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}
		$prm = $this->sys_input;
		$user = $this->sys_user;
		$sql = "UPDATE m_testfavorit SET
					M_TestFavoritIsActive = 'N',
					M_TestFavoritUserID = ?
					WHERE
					M_TestFavoritT_TestID = ? AND M_TestFavoritM_UserID = ?
				";
		$query = $this->db_onelite->query($sql, array($user['M_UserID'], $prm['test_id'], $user['M_UserID']));
		if ($query)
			echo json_encode(array('status' => 'OK', 'message' => 'Telah dihapus dari favorit'));
		else
			echo json_encode(array('status' => 'ERROR', 'message' => 'Maaf gagal menghapus favorite'));
	}

	function getPatients()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}
		$prm = $this->sys_input;
		$user = $this->sys_user;
		$sql = "SELECT 	m_patient.*, 
						m_sex.*, 
						m_idtype.*, 
						m_patientaddress.*, 
						M_SexCode as sex,
						'' as idtype,
						M_PatientNoreg as patient_id,
						M_PatientIDNumber as ktp,
						M_PatientName as name,
						DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
						M_PatientJob as job,
						M_PatientNoreg as pramitaid,
						M_PatientAddressDescription as address,
						'' as province,
						''  as city,
						'' as district,
						'' as kelurahan
				FROM m_patient
				JOIN m_sex ON M_SexID = M_PatientM_SexID
				JOIN m_idtype ON M_PatientM_IdTypeID = M_IdTypeID
				JOIN m_patientaddress ON M_PatientAddressM_PatientID = M_PatientID AND M_PatientAddressIsActive = 'Y'
				JOIN m_kelurahan ON M_PatientAddressM_KelurahanID = M_KelurahanID
				JOIN m_district ON M_KelurahanM_DistrictID = M_DistrictID
				JOIN m_city ON M_CityID = M_DistrictM_CityID
				JOIN m_province ON M_ProvinceID = M_CityM_ProvinceID
				WHERE
					M_PatientUserID = ?";
		//echo $user['M_UserID'];
		$query = $this->db_onelite->query($sql, array($user['M_UserID']));
		//echo $this->db_onelite->last_query();
		$rows = $query->result_array();
		if ($rows) {
			foreach ($rows as $k => $v) {
				$rows[$k]['name'] = stripslashes($rows[$k]['name']);
				//echo $rows[$k]['name'];
			}
		}
		$sql = "SELECT M_ProvinceID as id, M_ProvinceName as name
				FROM m_province 
				WHERE
					M_ProvinceIsActive = 'Y'";
		$rows_province = $this->db_onelite->query($sql)->result_array();

		$sql = "SELECT M_IdTypeID as id, M_IdTypeName as name
				FROM m_idtype 
				WHERE
					M_IdTypeIsActive = 'Y'";
		$rows_ids = $this->db_onelite->query($sql)->result_array();

		$sql = "SELECT M_TitleID as id, M_TitleName as name, M_SexCode as sex
				FROM m_title 
				JOIN m_sex ON M_SexID = M_TitleM_SexID
				WHERE
					M_TitleIsActive = 'Y'";

		$qry = $this->db_onelite->query($sql);

		if (! $qry) {
			echo json_encode(array(
				"status" => "ERR",
				"message" => print_r($this->db_onelite->error(), true)
			));
			return;
		}
		$rows_titles = $qry->result_array();

		echo json_encode(array('status' => 'OK', 'rows' => $rows, 'provinces' => $rows_province, 'ids' => $rows_ids, 'titles' => $rows_titles));
	}

	function getInitForm()
	{
		$prm = $this->sys_input;
		$sql = "SELECT M_ProvinceID as id, M_ProvinceName as name
				FROM m_province 
				WHERE
					M_ProvinceIsActive = 'Y'";
		$rows_province = $this->db_onelite->query($sql)->result_array();

		$sql = "SELECT M_IdTypeID as id, M_IdTypeName as name
				FROM m_idtype 
				WHERE
					M_IdTypeIsActive = 'Y'";
		$rows_ids = $this->db_onelite->query($sql)->result_array();

		$sql = "SELECT M_TitleID as id, M_TitleName as name, M_SexCode as sex
				FROM m_title 
				JOIN m_sex ON M_SexID = M_TitleM_SexID
				WHERE
					M_TitleIsActive = 'Y'";

		$qry = $this->db_onelite->query($sql);

		$rows_titles = $qry->result_array();

		echo json_encode(array('status' => 'OK', 'provinces' => $rows_province, 'ids' => $rows_ids, 'titles' => $rows_titles));
	}

	function getDeliveries()
	{
		$prm = $this->sys_input;
		$sql = "SELECT m_delivery.*, 'N' as selected, '' as xvalue FROM `m_delivery` WHERE `M_DeliveryIsActive` = 'Y'";
		//echo $sql;
		$rows = $this->db_onelite->query($sql)->result_array();

		echo json_encode(array('status' => 'OK', 'data' => $rows));
	}




	function getTimesKunjungan()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}

		$prm = $this->sys_input;
		$branchID = $prm['id'];
		$kunjungan_date = $prm['selected_date'];
		$this->load->library("Quota_v2");
		$rst = $this->quota_v2->get_kunjungan($branchID, $kunjungan_date);
		$result = array();
		if ($rst) {
			foreach ($rst['data'] as $k => $v) {
				$arr = array('name' => $k, 'kuota' => $v);
				array_push($result, $arr);
			}
		}

		echo json_encode(array(
			"status" => "OK",
			"data" => $result
		));
	}

	function getTimesKunjunganHS()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}

		$prm = $this->sys_input;
		$branchID = $prm['id'];
		$kunjungan_date = $prm['selected_date'];
		$this->load->library("Quota_hs");
		$rst = $this->quota_hs->get_kunjungan($branchID, $kunjungan_date);
		$result = array();
		if ($rst) {
			foreach ($rst['data'] as $k => $v) {
				$arr = array('name' => $k, 'kuota' => $v);
				array_push($result, $arr);
			}
		}

		echo json_encode(array(
			"status" => "OK",
			"data" => $result
		));
	}

	function getTimesByDate()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}

		$prm = $this->sys_input;
		$branchID = $prm['id'];
		$date = $prm['new_date'];
		$date_visit = explode('-', $prm['date_visit']);
		$date_visit_x =  $date_visit[2] . '-' . $date_visit[1] . '-' . $date_visit[0];
		$time_visit = $prm['time_visit']['name'];
		$visit_date_time = date('Y-m-d H:i', strtotime($date_visit_x . ' ' . $time_visit));
		//echo $visit_date_time;
		$test_id = $prm['test_id'];
		$is_cito = $prm['is_cito'];
		$this->load->library("Quota_v2");
		$rst = $this->quota_v2->get_quota($branchID, $test_id, $date, $is_cito);
		//echo "$branchID , $test_id, $date, $is_cito";
		//print_r($rst);
		$result = array();
		if ($rst) {
			unset($rst['00-debug']);
			foreach ($rst as $k => $v) {
				$new_date = explode('-', $prm['new_date']);
				$new_date_x = $new_date[0] . '-' . $new_date[1] . '-' . $new_date[2];
				//echo $new_date_x.' '.$v['name'];
				$new_date_time = date('Y-m-d H:i', strtotime($new_date_x . ' ' . $k));
				//echo $visit_date_time;
				//echo $new_date_time;
				if ($new_date_time < $visit_date_time) {
					$v = '0';
				}
				$arr = array('name' => $k, 'kuota' => $v);
				array_push($result, $arr);
			}
		}

		echo json_encode(array(
			"status" => "OK",
			"data" => $result
		));
	}



	function checkout()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}
		$this->load->library("Quota_v2");
		$prm = $this->sys_input;
		$user = $this->sys_user;
		$trx_date = date('Y-m-d', strtotime($prm['date']));
		$not_avalilable_schedule = array();


		$cart = $prm['cart'];
		$mouid = 0;
		foreach ($cart as $kcart => $vcart) {
			foreach ($vcart['order'] as $korder => $vorder) {
				$mouid = $vorder['mou_id'];
				$schedules = $vorder['schedule'];
				if (count($schedules) > 0) {
					foreach ($schedules as $k_schedule => $v_schedule) {
						$xdateTime = $v_schedule['new_date'] . ' ' . $v_schedule['time']['name'];

						$rst = $this->quota_v2->get_available_test_v2($prm['branch']['id'], $v_schedule['test_id'], $xdateTime, $v_schedule['is_cito']);

						if ($rst) {
							if (intval($rst['available']) == 0) {
								array_push($not_avalilable_schedule, $v_schedule);
							}
						}
					}
				}
			}
		}

		//print_r($not_avalilable_schedule);

		if (count($not_avalilable_schedule) == 0) {
			$sql = "SELECT fn_numbering('TRO') as numbering";
			$isHS = 'N';
			if ($prm['order_hs']) {
				$isHS = 'Y';
			}
			$row_t_numbering = $this->db_onelite->query($sql)->row_array();
			$sql = "INSERT INTO t_transaction(
						T_TransactionAppVersion,
						T_TransactionNumbering,
						T_TransactionM_BranchID,
						T_TransactionM_MouID,
						T_TransactionDate,
						T_TransactionDistance,
						T_TransactionUserID,
						T_TransactionLastUpdated,
						T_TransactionIsHS
					)
					VALUES('3.0',?,?,?,NOW(),?,?,NOW(),?)";

			$trx = $this->db_onelite->query(
				$sql,
				array(
					$row_t_numbering['numbering'],
					$prm['branch']['id'],
					$mouid,
					$prm['branch']['distance'],
					$user['M_UserID'],
					$isHS
				)
			);
			//echo $this->db_onelite->last_query();
			$trx_id = $this->db_onelite->insert_id();
			if ($prm['order_hs']) {
				if ($prm['selected_hs_address']['id']) {
					$sql = "INSERT INTO t_transactionHS(
						t_transactionHST_TransactionID,
						t_transactionHSCost,
						t_transactionHSHS_AddressID,
						t_transactionHSUserID,
						t_transactionHSOrderName,
						t_transactionHSOrderNoHP,
						t_transactionHSCreated
					)
					VALUES(?,?,?,?,get_username_trx('{$row_t_numbering['numbering']}'),?,NOW())";
					$trx = $this->db_onelite->query(
						$sql,
						array(
							$trx_id,
							$prm['branch']['hs_cost'],
							$prm['selected_hs_address']['id'],
							$user['M_UserID'],
							$user['M_UserUsername']
						)
					);
					//echo $this->db_onelite->last_query();
				} else {
					$sql = "UPDATE t_transaction SET T_TransactionIsActive = 'N', T_TransactionLastUpdated = NOW() 
							WHERE T_TransactionID = ?";
					$trx = $this->db_onelite->query(
						$sql,
						array(
							$trx_id
						)
					);
					echo json_encode(array(
						"status" => "ERR",
						"message" => 'Penyimpanan gagal, silahkan transaksi ulang'
					));
				}
			}

			foreach ($cart as $kxcart => $vxcart) {
				$is_klinisi = $vxcart['is_klinisi'] ? 'Y' : 'N';
				$datevisit = date('Y-m-d', strtotime($prm['datevisit']));
				$timevisit = $prm['timevisit']['name'];
				$sql = "SELECT * FROM qrcode WHERE qrCodeIsUsed = 'N' LIMIT 1";
				$row_qrCode = $this->db_onelite->query($sql)->row_array();
				$sql = "SELECT fn_numbering('RO') as numbering";
				$row_numbering = $this->db_onelite->query($sql)->row_array();
				$ehac_fee = 0;
				$purpose_id = 0;
				if ($vxcart['is_covid'] == 'Y' && intval($vxcart['covid_purpose']['id']) == 5) {
					$sql = "SELECT * FROM config WHERE configIsActive = 'Y' LIMIT 1";
					$row_config = $this->db_onelite->query($sql)->row_array();
					$ehac_fee = intval($row_config['configEhac']);
					$purpose_id = $vxcart['covid_purpose']['id'];
				}

				$sql = "INSERT INTO t_order(
								T_OrderT_TransactionID,
								T_OrderNumber,
								T_OrderQrCode,
								T_OrderM_BranchID,
								T_OrderM_PatientID,
								T_OrderTest_PurposeID,
								T_OrderEHAC,
								T_OrderIsKlinisi,
								T_OrderDate,
								T_OrderTime,
								T_OrderUserID,
								T_OrderLastUpdated
							)
							VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())";
				$order = $this->db_onelite->query(
					$sql,
					array(
						$trx_id,
						$row_numbering['numbering'],
						$row_qrCode['qrCodeCode'],
						$prm['branch']['id'],
						$vxcart['patient']['M_PatientID'],
						$purpose_id,
						$ehac_fee,
						$is_klinisi,
						$datevisit,
						$timevisit,
						$user['M_UserID']
					)
				);
				//echo $this->db_onelite->last_query();
				//print_r($vxcart['deliveries']);

				if ($order) {
					$order_id = $this->db_onelite->insert_id();
					$sql = "UPDATE qrcode SET qrCodeIsUsed = 'Y' WHERE qrCodeID = ?";
					$this->db_onelite->query($sql, array($row_qrCode['qrCodeID']));

					foreach ($vxcart['order'] as $kxorder => $vxorder) {
						$result_option_id = 0;
						$diskon = 0;
						$diskon_rp = 0;
						$packet_id = NULL;
						$packet_name = NULL;
						$test_id = NULL;
						$test_name = NULL;
						if ($vxorder['is_packet'] == 'Y') {
							$packet_id = $vxorder['test_id'];
							$packet_name = $vxorder['name'];
						} else {
							$test_id = $vxorder['test_id'];
							$test_name = $vxorder['name'];
						}
						$sql = "INSERT INTO t_orderdetail(
										T_OrderDetailT_OrderID,
										T_OrderDetailT_PacketName,
										T_OrderDetailT_PacketID,
										T_OrderDetailT_TestID,
										T_OrderDetailT_TestName,
										T_OrderDetailIsCito,
										T_OrderDetailT_ResultOptionID,
										T_OrderDetailAmount,
										T_OrderDetailDiscount,
										T_OrderDetailDiscountRp,
										T_OrderDetailSubtotal,
										T_OrderDetailTotal,
										T_OrderDetailUserID,
										T_OrderDetailLastUpdated
									)
									VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
						$orderdetail = $this->db_onelite->query(
							$sql,
							array(
								$order_id,
								$packet_name,
								$packet_id,
								$test_id,
								$test_name,
								$vxorder['is_cito'],
								$result_option_id,
								$vxorder['price'],
								$diskon,
								$diskon_rp,
								$vxorder['price'],
								$vxorder['price'],
								$user['M_UserID']
							)
						);

						if (count($vxorder['schedule']) > 0) {
							foreach ($vxorder['schedule'] as $kxschedule => $vxschedule) {
								$sql = "INSERT INTO t_orderschedule(
												T_OrderScheduleT_OrderID,
												T_OrderScheduleT_TestID,
												T_OrderScheduleNat_TestID,
												T_OrderScheduleT_TestName,
												T_OrderScheduleDate,
												T_OrderScheduleTime,
												T_OrderScheduleUserID,
												T_OrderScheduleLastUpdated
											)
											VALUES(?,?,?,?,?,?,?,NOW())";
								$schedule = $this->db_onelite->query(
									$sql,
									array(
										$order_id,
										$vxschedule['test_id'],
										$vxschedule['nat_testid'],
										$vxschedule['testname'],
										date('Y-m-d', strtotime($vxschedule['date'])),
										$vxschedule['time']['name'],
										$user['M_UserID']
									)
								);
								//echo $this->db_onelite->last_query();
							}
						}

						if (count($vxorder['requirement']) > 0) {
							foreach ($vxorder['requirement'] as $kxrequirement => $vxrequirement) {
								$sql = "INSERT INTO order_requirement(
												OrderRequirementT_OrderID,
												OrderRequirementRequirement,
												OrderRequirementCreated,
												OrderRequirementUserID
											)
											VALUES(?,?,NOW(),?)";
								$schedule = $this->db_onelite->query(
									$sql,
									array(
										$order_id,
										$vxrequirement['req_name'],
										$user['M_UserID']
									)
								);
								//echo $this->db_onelite->last_query();
							}
						}
					}

					if (count($vxcart['deliveries']) > 0) {
						foreach ($vxcart['deliveries'] as $kdelivery => $vdelivery) {
							if ($vdelivery['selected']) {
								$sql = "INSERT INTO t_orderdeliveries(
												T_OrderDeliveriesT_OrderID,
												T_OrderDeliveriesM_DeliveryID,
												T_OrderDeliveriesM_DeliveryTypeID,
												T_OrderDeliveriesDestination,
												T_OrderDeliveriesUserID,
												T_OrderDeliveriesLastUpdated
											)
											VALUES(?,?,?,?,?,NOW())";
								$schedule = $this->db_onelite->query(
									$sql,
									array(
										$order_id,
										$vdelivery['M_DeliveryID'],
										$vdelivery['M_DeliveryM_DeliveryTypeID'],
										$vdelivery['xvalue'],
										$user['M_UserID']
									)
								);
								//echo $this->db_onelite->last_query();
							}
						}
					}
				}
			}

			echo json_encode(array(
				"status" => "OK",
				"data" => $trx_id
			));
		} else {
			$msg_error = '';
			foreach ($not_avalilable_schedule as $key_scx => $value_scx) {
				$msg_error = $msg_error . " " . "Pemeriksaan " . $value_scx['testname'] . " jadwalnya sudah penuh, silahkan pilih jadwal yang lainnya";
			}
			echo json_encode(array(
				"status" => "ERR",
				"message" => $msg_error
			));
		}
	}

	function getdataorder()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}

		$prm = $this->sys_input;
		$user = $this->sys_user;

		$sql = "SELECT T_TransactionID as id,
				T_TransactionNumbering as order_number,
				M_BranchID as branch_id,
				M_BranchName as branch_name,
				DATE_FORMAT(T_TransactionDate,'%d-%m-%Y %H:%i') as order_date,
				T_TransactionTotal as number_total,
				T_TransactionTotal + configFee as number_total_fee,
				configEhac as number_ehac_fee,
				CONCAT('Rp ',FORMAT(T_TransactionTotal+configFee,0,'de_DE')) as total,
				CONCAT('Rp ',FORMAT(configFee,0,'de_DE')) as fee, 
				CONCAT('Rp ',FORMAT(configEhac,0,'de_DE')) as ehac_fee,
				configFee as number_fee,
				'' as test,
				'' as patients,
				0 as ehac_patient
				FROM t_transaction
				JOIN m_branch ON T_TransactionM_BranchID = M_BranchID
				JOIN config ON configIsActive = 'Y'
				WHERE
					T_TransactionID = ? AND T_TransactionUserID = ?";
		$row = $this->db_onelite->query($sql, array($prm['id'], $user['M_UserID']))->row_array();

		$sql = "SELECT T_OrderID as id,
					T_OrderNumber as trx_number,
					T_OrderQrCode as qrcode,
					M_BranchName as branch_name,
					M_BranchAddress as branch_address,
					M_PatientName as patient_name,
					IFNULL(M_PatientIDNumber,'') as id_number,
					IFNULL(M_IdTypeName,'') as id_name,
					Test_PurposeName as purpose_name,
					T_OrderIsKlinisi as is_klinisi,
					T_OrderEHAC as fee_ehac,
					DATE_FORMAT(T_OrderDate,'%d-%m-%Y') as order_date,
					DATE_FORMAT(T_OrderTime,'%H:%i') as order_time,
					DATE_FORMAT(T_OrderCreated,'%d-%m-%Y %H:%i') as order_created,
					CONCAT('Rp ',FORMAT(T_TransactionTotal,0,'de_DE')) as total,
					IF(T_OrderTest_PurposeID = 5,'Y','N') as is_ehac,
					'N' as xshow,
					'' as details
				FROM t_order 
				JOIN t_transaction ON T_OrderT_TransactionID = T_TransactionID
				JOIN m_branch ON T_OrderM_BranchID = M_BranchID
				JOIN m_patient ON T_OrderM_PatientID = M_PatientID
				LEFT JOIN m_idtype ON M_PatientM_IdTypeID = M_IdTypeID
				LEFT JOIN test_purpose ON T_OrderTest_PurposeID = Test_PurposeID
				WHERE
					T_OrderT_TransactionID = ? AND T_TransactionUserID = ?";
		$rows = $this->db_onelite->query($sql, array($prm['id'], $user['M_UserID']))->result_array();
		//echo $this->db_onelite->last_query();
		if ($rows) {
			foreach ($rows as $k => $v) {
				//	echo date("l") . "<br>";

				// Prints the day, date, month, year, time, AM or PM
				//echo date("l jS \of F Y h:i:s A");

				$mydate = new DateTime($v['order_date']);
				$hari = $mydate->format('l');
				//echo $hari;
				if ($hari == 'Sunday') {
					$hari = 'Minggu';
				}
				if ($hari == 'Monday') {
					$hari = 'Senin';
				}
				if ($hari == 'Tuesday') {
					//echo $hari;
					$hari = 'Selasa';
				}
				if ($hari == 'Wednesday') {
					$hari = 'Rabu';
				}
				if ($hari == 'Thursday') {
					$hari = 'Kamis';
				}
				if ($hari == 'Friday') {
					$hari = 'Jumat';
				}
				if ($hari == 'Saturday') {
					$hari = 'Sabtu';
				}
				$rows[$k]['order_date'] = $hari . ", " . $v['order_date'];
				$sql = "SELECT T_OrderDetailT_PacketName as test_name,
							IFNULL(T_ResultOptionName,'') as result_option_name,
							CONCAT('Rp ',FORMAT(T_OrderDetailAmount,0,'de_DE')) as amount,
							T_OrderDetailDiscount as discount_persen,
							T_OrderDetailDiscountRp as discount_rp,
							CONCAT('Rp ',FORMAT(T_OrderDetailSubtotal,0,'de_DE')) as subtotal,
							CONCAT('Rp ',FORMAT(T_OrderDetailTotal,0,'de_DE')) as total
						FROM t_orderdetail
						LEFT JOIN t_resultoption ON T_OrderDetailT_ResultOptionID = T_ResultOptionID
						WHERE
							T_OrderDetailT_OrderID = ? AND T_OrderDetailIsActive = 'Y'";
				$details = $this->db_onelite->query($sql, array($v['id']))->result_array();
				//echo $this->db_onelite->last_query();
				$rows[$k]['details'] = $details;
				$sql = "SELECT M_DeliveryName as delivery_name,
							M_DeliveryTypeName as delivery_type_name,
							GROUP_CONCAT(T_OrderDeliveriesDestination separator ',') as destination
						FROM t_orderdeliveries
						JOIN m_delivery ON T_OrderDeliveriesM_DeliveryID = M_DeliveryID
						JOIN m_deliverytype ON T_OrderDeliveriesM_DeliveryTypeID = M_DeliveryTypeID
						WHERE
							T_OrderDeliveriesT_OrderID = ?
						GROUP BY T_OrderDeliveriesM_DeliveryTypeID";
				$deliveries = $this->db_onelite->query($sql, array($v['id']))->result_array();
				$rows[$k]['deliveries'] = $deliveries;
			}

			$ehac = 0;
			$ehac_fee = 0;
			$patient_ehac = 0;
			foreach ($rows as $kp => $vp) {
				//print_r($vp);
				if ($vp['is_ehac'] == 'Y') {
					$ehac_fee = intval($vp['fee_ehac']);
					$ehac = $ehac + intval($vp['fee_ehac']);
					$patient_ehac = $patient_ehac + 1;
				}
			}
			if (intval($patient_ehac) > 0) {

				//echo $ehac;
				$row['ehac_patient'] = $patient_ehac;
				$xsum_ehac = $ehac;
				//echo intval($xsum_ehac);
				$row['number_fee'] = $row['number_fee'] + intval($xsum_ehac);
				$row['number_ehac_fee'] = $ehac_fee;
				$row['ehac_fee'] = "Rp " . number_format($ehac_fee, 2, ',', '.');
				$row['number_total_fee'] = intval($row['number_total_fee']) + intval($xsum_ehac);
				$row['total'] = "Rp " . number_format($row['number_total_fee'], 2, ',', '.');
			}
		}
		echo json_encode(array(
			"status" => "OK",
			"data" => $rows,
			"row" => $row
		));
	}

	function getTestPurpose()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}

		$prm = $this->sys_input;

		$sql = "SELECT * FROM m_branch WHERE M_BranchID = ? LIMIT 1";
		$rows_branch = $this->db_onelite->query($sql, array($prm['branch']['id']))->row_array();
		//echo $rows_branch['M_BranchIsEhac'];
		if ($rows_branch['M_BranchIsEhac'] == 'N') {
			$sql = "SELECT Test_PurposeID as id, Test_PurposeName as name
					FROM test_purpose 
					WHERE
						Test_PurposeIsActive = 'Y' AND Test_PurposeID <> 5";
			$rows_purpose = $this->db_onelite->query($sql)->result_array();
		} else {
			$sql = "SELECT Test_PurposeID as id, Test_PurposeName as name
					FROM test_purpose 
					WHERE
						Test_PurposeIsActive = 'Y' ";
			$rows_purpose = $this->db_onelite->query($sql)->result_array();
		}

		echo json_encode(array(
			"status" => "OK",
			"data" => $rows_purpose
		));
	}

	function getOrderTotal()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID, SILAHKAN LOGIN KEMBALI"
			));
			exit;
		}

		$prm = $this->sys_input;
		$user = $this->sys_user;

		$sql = "SELECT T_TransactionID as id,
				T_TransactionNumbering as order_number,
				M_BranchID as branch_id,
				M_BranchCode as branch_code,
				M_BranchName as branch_name,
				DATE_FORMAT(T_TransactionDate,'%d-%m-%Y %H:%i') as order_date,
				T_TransactionTotal as number_total,
				T_TransactionTotal + configFee as number_total_fee,
				configEhac as number_ehac_fee,
				T_TransactionTotal+configFee as total,
				CONCAT(FORMAT(configFee,0,'de_DE')) as fee, 
				CONCAT(FORMAT(configEhac,0,'de_DE')) as ehac_fee,
				configEhac as ehac_fee_sum,
				configFee as number_fee,
				configFee as number_fee_original,
				'' as patients,
				0 as ehac_patient,
				0 as discount_total,
				'' as discount_details,
				IF(ISNULL(t_transactionHSID),'N','Y') as order_hs,
				IFNULL(t_transactionHSCost,0) as hs_cost,
				0 as hs_costminorder,
				HS_ConfigMinOrder as hs_minorder,
				0 as number_costother,
				0 as costother,
				0 as points,
				'0' as text_ponits,
				T_TransactionDistance as distance,
				HS_ConfigAPDFee as apd_fee,
				HS_ConfigSecondVisitPercent as fee_second_visit_percent
				FROM t_transaction
				JOIN m_branch ON T_TransactionM_BranchID = M_BranchID
				LEFT JOIN hs_config ON HS_ConfigM_BranchCode = M_BranchCode AND HS_ConfigIsActive = 'Y'
				JOIN config ON configIsActive = 'Y'
				LEFT JOIN t_transactionHS ON t_transactionHST_TransactionID = T_TransactionID AND t_transactionHSIsActive = 'Y'
				WHERE
					T_TransactionID = ? AND T_TransactionUserID = ?";
		$row = $this->db_onelite->query($sql, array($prm['id'], $user['M_UserID']))->row_array();
		//echo $this->db_onelite->last_query();
		//print_r($row);
		if ($row) {
			$row['number_fee_original'] = intval($row['number_fee_original']);
			if ($row['order_hs'] == 'Y') {
				$row['hs_cost'] = intval($row['hs_cost']);
				$second_visit_fee = 0;

				$apd_fee = 0;
				$sql = "Select T_OrderID
						FROM t_transaction
						JOIN t_order ON T_OrderT_TransactionID = T_TransactionID AND T_OrderIsActive = 'Y'
						JOIN t_orderdetail ON T_OrderDetailT_OrderID = T_OrderID AND T_OrderDetailIsActive = 'Y'
						WHERE
						T_TransactionID = ? 
						GROUP BY T_OrderID";
				$rst_data_order = $this->db_onelite->query($sql, array($prm['id']))->result_array();
				foreach ($rst_data_order as $key_order => $value_order) {
					$sql = "Select COUNT(*) as xc
							FROM t_order
							JOIN t_orderdetail ON T_OrderDetailT_OrderID = T_OrderID AND T_OrderDetailIsActive = 'Y'
							LEFT JOIN m_test_second_visit ON M_TestSecondVisitT_TestID = T_OrderDetailT_TestID AND M_TestSecondVisitIsActive = 'Y'
							WHERE
							T_OrderID = ? AND ISNULL(M_TestSecondVisitID)
							GROUP BY T_OrderDetailT_TestID";
					$rst_ex_data_second_visit = $this->db_onelite->query($sql, array($value_order['T_OrderID']))->row_array();
					$count_ex_second_visit = $rst_ex_data_second_visit['xc'];
					//echo $this->db_onelite->last_query(); 
					$sql = "Select COUNT(*) as xc
							FROM t_order
							JOIN t_orderdetail ON T_OrderDetailT_OrderID = T_OrderID AND T_OrderDetailIsActive = 'Y'
							JOIN m_test_second_visit ON M_TestSecondVisitT_TestID = T_OrderDetailT_TestID AND M_TestSecondVisitIsActive = 'Y'
							WHERE
							T_OrderID = ?
							";
					$rst_data_second_visit = $this->db_onelite->query($sql, array($value_order['T_OrderID']))->row_array();
					$count_second_visit = $rst_data_second_visit['xc'];
					//echo $this->db_onelite->last_query();
					if ($count_second_visit > 0 && $count_ex_second_visit > 0) {
						$second_visit_fee = ($row['fee_second_visit_percent'] / 100) * $row['hs_cost'];
						//echo $second_visit_fee;
					}
				}

				$sql = "Select COUNT(*) as xc
						FROM t_transaction
						JOIN t_order ON T_OrderT_TransactionID = T_TransactionID AND T_OrderIsActive = 'Y'
						JOIN t_orderdetail ON T_OrderDetailT_OrderID = T_OrderID AND T_OrderDetailIsActive = 'Y'
						JOIN t_subcategory_test ON T_SubcategoryTestT_TestID = T_OrderDetailT_TestID AND 
						( T_SubcategoryTestT_SubcategoryID < 5 OR T_SubcategoryTestT_SubcategoryID = 6) AND
						T_SubcategoryTestIsActive = 'Y'
						WHERE
						T_TransactionID = ? 
						";
				$rst_data_apd = $this->db_onelite->query($sql, array($prm['id']))->row_array();
				$count_apd = $rst_data_apd['xc'];
				if ($count_apd > 0) {
					$apd_fee = intval($row['apd_fee']);
				}

				//echo $apd_fee;
				//echo $second_visit_fee;
				$number_costother = max($apd_fee, $second_visit_fee);
				$row['number_costother'] = $number_costother;
				$row['costother'] =  number_format($number_costother, 0, ',', '.');

				if ($row['number_total'] < $row['hs_minorder']) {
					$selisih = $row['hs_minorder'] - $row['number_total'];
					$row['number_total'] = $row['number_total'] + $selisih;
					$row['hs_costminorder'] = $selisih;
				}
				$row['number_total'] = intval($row['number_total']) + intval($row['hs_cost']) + $row['number_costother'];
				$row['number_total_fee'] = intval($row['number_total']) + intval($row['number_fee']);
				$row['total'] = number_format($row['number_total_fee'], 0, ',', '.');
			}
			//print_r($row);
			$sql = "SELECT T_OrderID as id,
					M_PatientName as patient_name,
					IFNULL(M_PatientIDNumber,'') as id_number,
					IFNULL(M_IdTypeName,'') as id_name,
					Test_PurposeName as purpose_name,
					IF(T_OrderTest_PurposeID = 5,'Y','N') as is_ehac,
					T_OrderIsKlinisi as is_klinisi,
					T_OrderEHAC as fee_ehac,
					CONCAT(FORMAT(SUM(T_OrderDetailTotal),0,'de_DE')) as total
				FROM t_order 
				JOIN t_transaction ON T_OrderT_TransactionID = T_TransactionID
				JOIN m_patient ON T_OrderM_PatientID = M_PatientID
				JOIN t_orderdetail ON T_OrderDetailT_OrderID = T_OrderID
				LEFT JOIN m_idtype ON M_PatientM_IdTypeID = M_IdTypeID
				LEFT JOIN test_purpose ON T_OrderTest_PurposeID = Test_PurposeID
				WHERE
					T_OrderT_TransactionID = ? 
				GROUP BY T_OrderID";
			$row['patients'] = $this->db_onelite->query($sql, array($prm['id']))->result_array();
			//echo $this->db_onelite->last_query();
			//print_r($row);
			$ehac = 0;
			$ehac_fee = 0;
			$patient_ehac = 0;
			foreach ($row['patients'] as $kp => $vp) {
				//print_r($vp);
				if ($vp['is_ehac'] == 'Y') {
					$ehac_fee = intval($vp['fee_ehac']);
					$ehac = $ehac + intval($vp['fee_ehac']);
					$patient_ehac = $patient_ehac + 1;
				}
			}
			if (intval($patient_ehac) > 0) {

				//echo $ehac;
				$row['ehac_patient'] = $patient_ehac;
				$xsum_ehac = $ehac;
				//echo intval($xsum_ehac);
				$row['number_fee'] = $row['number_fee'] + intval($xsum_ehac);
				$row['number_ehac_fee'] = $ehac_fee;
				$row['number_ehac_fee_sum'] = $xsum_ehac;
				$row['ehac_fee'] =  number_format($ehac_fee, 0, ',', '.');
				$row['number_total_fee'] = intval($row['number_total_fee']) + intval($xsum_ehac);
				$row['total'] =  number_format($row['number_total_fee'], 0, ',', '.');
			} else {
				$row['number_ehac_fee'] = 0;
				$row['number_ehac_fee_sum'] = 0;
			}
		}

		$filter_onsite = "";
		$sql = "SELECT * FROM t_order WHERE T_OrderT_TransactionID = ? ";
		$rst_order = $this->db_onelite->query($sql, array($prm['id']))->result_array();
		$check_date = true;
		if ($rst_order) {
			foreach ($rst_order as $kro => $vro) {
				$xday = date("Y-m-d");
				$dayorder = date("Y-m-d", strtotime($vro['T_OrderDate']));
				if ($xday != $dayorder) {
					$check_date = false;
				}
			}
		}

		//if(intval($row['distance']) > 1 || !$check_date){
		$filter_hs = "AND pgPaymentMethodeCode <> 'PT'";
		if (!$check_date && $row['order_hs'] != 'Y') {
			$filter_onsite = "AND pgPaymentTypeID <> 5";
		}
		if ($row['order_hs'] == 'Y') {
			$filter_hs = " AND pgPaymentMethodeCode <> 'PO'";
		}

		$sql = "SELECT 0 as id, pgPaymentTypeName as name, 'Y' as is_parent, 'N' as status, pgPaymentTypeID as parent_id
				FROM pg_payment_methode 
				JOIN pg_payment_type ON pgPaymentMethodePgPaymentTypeID = PgPaymentTypeID
				WHERE
					pgPaymentMethodeIsActive = 'Y' $filter_onsite 
				GROUP BY  pgPaymentMethodePgPaymentTypeID";
		$dt_payments = $this->db_onelite->query($sql)->result_array();
		//echo $sql;
		//print_r($dt_payments);
		$payments = array();
		if ($dt_payments) {
			foreach ($dt_payments as $k => $v) {
				array_push($payments, $v);
				$sql = "SELECT 	pgPaymentMethodeID as id, 
								pgPaymentMethodePgID as pg_id,
								pgPaymentMethodePgPaymentTypeID as type_id,
								pgPaymentMethodeName as name, 
								pdPaymentMethodeIcon as icon,
								pgPaymentMethodeCode as code,
								'N' as is_parent, 
								'N' as status, 
								pgPaymentMethodePgPaymentTypeID as parent_id
						FROM pg_payment_methode 
						JOIN pg_payment_type ON pgPaymentMethodePgPaymentTypeID = PgPaymentTypeID
						WHERE
							pgPaymentMethodeIsActive = 'Y' $filter_onsite $filter_hs AND pgPaymentMethodePgPaymentTypeID = ?";
				$dt_payments_childs = $this->db_onelite->query($sql, $v['parent_id'])->result_array();
				//echo $this->db_onelite->last_query();
				foreach ($dt_payments_childs as $kk => $vv) {
					array_push($payments, $vv);
				}
			}
		}

		$row['payments'] = $payments;
		$row['discount_total'] = 0;
		$row['points'] = $this->getPoints($user['M_UserID']);
		$row['text_points'] =  number_format($row['points'], 0, ',', '.');
		/*$get_discount = $this->get_discount($row,$user);
		$row['discount_details'] = $get_discount;
		if(count($get_discount) > 0){
			foreach($get_discount as $dk => $dv){
				$row['discount_total'] = $row['discount_total'] + intval($dv['amount']);
			}

			$row['number_total_fee'] = intval($row['number_total_fee']) - $row['discount_total'];
			$row['total'] =  number_format($row['number_total_fee'],0,',','.');
		}*/
		//sipe
		//$this->hardcode_payment($row);
		echo json_encode(array(
			"status" => "OK",
			"data" => $row
		));
	}

	function pay()
	{

		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID, SILAHKAN LOGIN KEMBALI"
			));
			exit;
		}

		$prm = $this->sys_input;
		$user = $this->sys_user;
		//print_r($prm['order']);
		if ($prm['order']['hs_costminorder'] > 0 || $prm['order']['number_costother'] > 0) {
			$sql = "UPDATE t_transactionHS SET t_transactionHSCostMinOrder = ?, t_transactionHSCostOther = ?, t_transactionHSLastUpdated = NOW()
						WHERE
						t_transactionHST_TransactionID = ?";
			$this->db_onelite->query($sql, array($prm['order']['hs_costminorder'], $prm['order']['number_costother'], $prm['order']['id']));
		}

		if ($prm['payment']['code'] != 'PO' && $prm['payment']['code'] != 'PT') {

			$sql = "SELECT * 
					FROM m_branch_pg
					WHERE
						M_BranchPgM_BranchID = ? AND M_BranchPgIsActive = 'Y'";
			$row_pg = $this->db_onelite->query($sql, array($prm['order']['branch_id']))->row_array();
			//echo $this->db_onelite->last_query();
			if ($row_pg) {
				$merchantCode = $row_pg['M_BranchPgMerchantCode'];
				$apiKey = $row_pg['M_BranchPgApiKey'];

				$sql = "SELECT * FROM config WHERE configIsActive = 'Y' LIMIT 1";
				$expiry = $this->db_onelite->query($sql)->row()->configExpiredTime;

				$sql = "SELECT	T_TransactionID as trxid, T_TransactionDate as created_order, MIN(CONCAT(T_OrderDate,' ',T_OrderTime)) as min_datetime
						FROM t_order
						JOIN t_transaction ON T_OrderT_TransactionID = T_TransactionID AND 
								T_TransactionNumbering = ?
						WHERE
							T_OrderIsActive = 'Y'";
				$row_mindatetime = $this->db_onelite->query($sql, array($prm['order']['order_number']))->row();
				$datetime1 = strtotime($row_mindatetime->created_order);
				$datetime2 = strtotime($row_mindatetime->min_datetime);
				$interval  = abs($datetime2 - $datetime1);
				$minutes   = round($interval / 60);
				$expiry = intval($minutes)  < intval($expiry) ? intval($minutes) : intval($expiry);
				//$expiry = 30;
				$sql = "UPDATE t_transaction SET T_TransactionFee = ?
						WHERE
							T_TransactionNumbering = ?";
				$this->db_onelite->query($sql, array($prm['order']['number_fee'], $prm['order']['order_number']));

				//$qty = count($prm['order']['patients']);
				//$price = ( $qty * $prm['order']['test']['number_total'] );
				$points = $this->getPoints($user['M_UserID']);
				if ($prm['paywith_point'] && intval($points > 0)) {
					$rest_points = intval($points) - intval($prm['order']['number_total_fee']);
					$pay_points = 0;
					if ($rest_points < 0) {
						$rest_points = 0;
						$pay_points = intval($points);

						$prm['order']['number_total_fee'] = intval($prm['order']['number_total_fee']) - intval($points);
						$prm['order']['number_total'] = intval($prm['order']['number_total']) - intval($points);
					} else {
						$pay_points = intval($points) - $rest_points;
					}

					$sql = "INSERT INTO t_pointpayment (
									T_PoinPaymentT_TransactionID,
									T_PointPaymentAmount,
									T_PointPaymentUserID,
									T_PointPaymentLastUpdated
								) 
							VALUES(
								?,?,?,NOW()
							)";
					$this->db_onelite->query($sql, array($row_mindatetime->trxid, $pay_points, $user['M_UserID']));

					$sql = "UPDATE m_userpoint SET M_UserPointAmount = ?, M_UserPointUserID = ?, M_UserPointLastUpdated = NOW() WHERE M_UserPointM_UserID = ?";
					$this->db_onelite->query($sql, array($rest_points, $user['M_UserID'], $user['M_UserID']));

					$sql = "INSERT INTO log_pointuser (
											Log_PointUserType,
											Log_PointUserBeforeAmount,
											Log_PointUserAmount,
											Log_PointUserM_UserID,
											Log_PointUserReffTrxID
							)
							VALUES('USE',?,?,?,?)";
					$this->db_onelite->query($sql, array($points, $pay_points, $user['M_UserID'], $row_mindatetime->trxid));
				}


				if (!$prm['paywith_point'] || ($prm['paywith_point'] && intval($points) < intval($prm['order']['number_total_fee']))) {
					$item_details = array(
						array("name" => 'Total Pemeriksaan', "quantity" => 1, "price" => $prm['order']['number_total']),
						array("name" => 'Biaya Layanan', "quantity" => 1, "price" => $prm['order']['number_fee'])
					);

					$customerDetail = array(
						"firstname" => $user['M_UserUsername'],
						"email" => '',
						"phoneNumber" => $user['M_UserUsername']
					);

					$inp = array(
						"paymentAmount" => $prm['order']['number_total_fee'],
						"paymentMethod" => $prm['payment']['code'],
						"orderID" => $prm['order']['order_number'],
						"orderNote" => "Pembayaran Pemeriksaan Laboratorium",
						"customerName" => $user['M_UserUsername'],
						"email" => '',
						"phoneNumber" => $user['M_UserUsername'],
						"itemDetails" => $item_details,
						"customerDetail" => $customerDetail,
						"expiry" => $expiry
					);

					$this->load->library("Duitku");
					$response = $this->duitku->tx_request($merchantCode, $apiKey, $inp);
					//echo $merchantCode;
					//echo $apiKey;
					//print_r($inp);
					//print_r($response);
					if ($response['statusMessage'] == 'SUCCESS') {
						echo json_encode(array(
							"status" => "OK",
							"data" => $response
						));
					} else {
						echo json_encode(array(
							"status" => "Err",
							"message" => "Payment Gateway Offline",
							"inp" => $inp,
							"data" => $response
						));
					}
				} else {
					$sql = "UPDATE t_transaction SET T_TransactionIsLunas = 'Y', T_TransactionLastUpdated = NOW()
								WHERE
									T_TransactionNumbering = ?";
					$this->db_onelite->query($sql, array($prm['order']['order_number']));
					//echo $this->db_onelite->last_query();
					echo json_encode(array(
						"status" => "LUNAS"
					));
				}
			} else {
				echo json_encode(array(
					"status" => "Err",
					"message" => "Setting Error",
					"inp" => $inp
				));
			}
		} else {
			//print_r($prm);

			$sql = "UPDATE t_transaction SET T_TransactionFee = ?, T_TransactionDiscount = ?
					WHERE
					T_TransactionNumbering = ?";
			$this->db_onelite->query($sql, array($prm['order']['number_fee'], $prm['order']['discount_total'], $prm['order']['order_number']));
			//echo $this->db_onelite->last_query();


			$sql = "SELECT * FROM t_transaction WHERE T_TransactionNumbering = ?";
			$row_transaction = $this->db_onelite->query($sql, array($prm['order']['order_number']))->row();
			$sql = "INSERT INTO pramita_pg (
						PramitaPgT_TransactionID,
						PramitaPgCreated,
						PramitaPgUserID
					)
					VALUES(?,NOW(),?)";
			$this->db_onelite->query($sql, array($row_transaction->T_TransactionID, $user['M_UserID']));
			echo json_encode(array(
				"status" => "OK",
				"data" => ''
			));
		}
	}

	function getOrders()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID, SILAHKAN LOGIN KEMBALI"
			));
			exit;
		}

		//# ambil parameter input

		$prm = $this->sys_input;
		$tot_count = 0;
		$number_limit = 10;
		$number_offset = (!isset($prm['current_page']) ? 1 : intval($prm['current_page']) - 1) * $number_limit;


		$start_date = date("Y-m-d", strtotime($prm['start_date'])) . ' 00:00:00';
		$end_date = date("Y-m-d", strtotime($prm['end_date'])) . ' 23:59:59';
		$user = $this->sys_user;

		$sql = "SELECT T_TransactionAppVersion as app_version,
					T_TransactionID as id,
					configExpiredTime as config_exptime,
					T_TransactionDate as created_order, 
					MIN(CONCAT(T_OrderDate,' ',T_OrderTime)) as min_datetime,
					'' as expired_time_text,
					T_TransactionNumbering as trx_number,
					T_TransactionTotal as total_number,
					T_TransactionFee as fee_number,
					IFNULL(t_transactionHSCost,0) as hs_cost,
					IFNULL(t_transactionHSCostMinOrder,0) as hs_min_order,
					M_BranchName as branch_name,
					M_BranchAddress as branch_address,
					IFNULL(duitkuCbReference,'') as invoice_number,
					DATE_FORMAT(T_TransactionDate,'%d-%m-%Y %H:%i') as order_created,
					CONCAT('Rp ',FORMAT(T_TransactionTotal,0,'de_DE')) as total,
					CONCAT('Rp ',FORMAT(T_TransactionFee,0,'de_DE')) as fee,
					'' as patients,
					fn_get_status(T_TransactionID) as status,
					IF(ISNULL(t_transactionHSID),'N','Y')  as is_hs,
					IF(ISNULL(PramitaPgID),'N','Y') as pay_at_pramita,
					0 paid_from_duitku,
					0 paid_from_gawai
				FROM t_transaction 
				LEFT JOIN t_transactionHS ON t_transactionHST_TransactionID = T_TransactionID AND t_transactionHSIsActive = 'Y'
				JOIN t_order ON T_OrderT_TransactionID = T_TransactionID
				JOIN m_branch ON T_TransactionM_BranchID = M_BranchID
				LEFT JOIN pramita_pg ON PramitaPgT_TransactionID = T_TransactionID
				LEFT JOIN duitku_cb ON duitkuCbMerchantOrderId = T_TransactionNumbering AND duitkuCbPaymentResultCode = '00'
				JOIN config ON configIsActive = 'Y'
				WHERE
					T_TransactionUserID = ? AND
					( T_TransactionDate BETWEEN ? AND ?)
				GROUP BY T_TransactionID
				ORDER BY T_TransactionNumbering DESC
				limit ? offset ?";
		//echo $sql;
		$rows = $this->db_onelite->query($sql, array($user['M_UserID'], $start_date, $end_date, $number_limit, $number_offset))->result_array();
		//echo $this->db_onelite->last_query();
		if (!$rows) {
			//echo $this->db_onelite->last_query();
			//$this->sys_error_db("f_paymentdetail ttt");
			$rows = [];
			//exit;
		} else {
			foreach ($rows as $k => $v) {
				$sql = "SELECT T_OrderID as id,
								T_OrderNumber as trx_number,
								T_OrderQrCode as qrcode,
								M_BranchName as branch_name,
								M_BranchAddress as branch_address,
								M_PatientName as patient_name,
								IFNULL(M_PatientIDNumber,'') as id_number,
								IFNULL(M_IdTypeName,'') as id_name,
								Test_PurposeName as purpose_name,
								T_OrderIsKlinisi as is_klinisi,
								DATE_FORMAT(T_OrderDate,'%d-%m-%Y') as order_date,
								DATE_FORMAT(T_OrderTime,'%H:%i') as order_time,
								DATE_FORMAT(T_OrderCreated,'%d-%m-%Y %H:%i') as order_created,
								'N' as xshow,
								'' as details,
								'' as deliveries,
								'' as schedules
							FROM t_order 
							JOIN m_branch ON T_OrderM_BranchID = M_BranchID
							JOIN m_patient ON T_OrderM_PatientID = M_PatientID
							LEFT JOIN m_idtype ON M_PatientM_IdTypeID = M_IdTypeID
							LEFT JOIN test_purpose ON T_OrderTest_PurposeID = Test_PurposeID
							WHERE
								T_OrderT_TransactionID = ?
							GROUP BY T_OrderID";
				$patients = $this->db_onelite->query($sql, array($v['id']))->result_array();
				//echo $this->db_onelite->last_query();
				$rows[$k]['patients'] = $patients;
				if ($v['status'] == 'pending') {
					$expiry = $v['config_exptime'];
					$datetime1 = strtotime($v['created_order']);
					$datetime2 = strtotime($v['min_datetime']);
					$interval  = abs($datetime2 - $datetime1);
					$minutes   = round($interval / 60);
					$expiry = intval($minutes)  < intval($expiry) ? intval($minutes) : intval($expiry);

					$expired_time = date("Y-m-d H:i", strtotime("+{$expiry} minutes", strtotime($v['created_order'])));
					$rows[$k]['expired_time_text'] = date("d-m-Y H:i", strtotime($expired_time));
					$mydate = new DateTime($expired_time);
					$hari = $mydate->format('l');
					if ($hari == 'Sunday') {
						$hari = 'Minggu';
					}
					if ($hari == 'Monday') {
						$hari = 'Senin';
					}
					if ($hari == 'Tuesday') {
						//echo $hari;
						$hari = 'Selasa';
					}
					if ($hari == 'Wednesday') {
						$hari = 'Rabu';
					}
					if ($hari == 'Thursday') {
						$hari = 'Kamis';
					}
					if ($hari == 'Friday') {
						$hari = 'Jumat';
					}
					if ($hari == 'Saturday') {
						$hari = 'Sabtu';
					}

					$rows[$k]['expired_time_text']  = $hari . ", " . $rows[$k]['expired_time_text'];
				}

				if ($v['is_hs'] === 'Y') {
					if ($v['status'] == 'done')
						$rows[$k]['invoice_number'] = $v['trx_number'] . 'HS';
					/*$paid_from_duitku = 0;
					$paid_from_gawai = 0;
					$sql = "SELECT duitkuCbAmount as amount 
							FROM duitku_cb 
							WHERE 
							duitkuCbMerchantOrderId = ?  AND duitkuCbReply = 'SUCCESS'";
					$query = $this->db_onelite->query($sql, array($v['trx_number']))->row();
					if($query){
						$paid_from_duitku = $query->amount;
					}
					
					$total = intval($v['total_number']);
					$fee = intval($v['fee_number']);

					$sql = "SELECT SUM(F_PaymentAmount) as amount 
							FROM hs_f_payment 
							WHERE 
							F_PaymentT_TransactionID = ?
							GROUP BY F_PaymentT_TransactionID";
					$query = $this->db_onelite->query($sql, array($v['id']))->row();
					if($query){
						$paid_from_gawai = $query->amount;
					}
					$rows[$k]['paid_from_duitku'] = $paid_from_duitku;
					$rows[$k]['paid_from_gawai'] = $paid_from_gawai;
					*/
					//if(($paid_from_duitku - $fee) +$paid_from_gawai == $total+intval($v['hs_cost'])+intval($v['hs_min_order']))
					//$rows[$k]['status'] = 'done' ;

				}
			}
		}


		echo json_encode(array(
			"status" => "OK",
			"data" => $rows
		));
	}

	function getdataorder_list()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}

		$prm = $this->sys_input;
		$user = $this->sys_user;


		$sql = "SELECT T_TransactionAppVersion as app_version,
				T_TransactionID as id,
				T_TransactionNumbering as order_number,
				M_BranchName as branch_name,
				DATE_FORMAT(T_TransactionDate,'%d-%m-%Y %H:%i') as order_date,
				T_TransactionTotal+IFNULL(t_transactionHSCost,0)+IFNULL(t_transactionHSCostMinOrder,0) number_total,
				T_TransactionTotal+IFNULL(t_transactionHSCost,0)+IFNULL(t_transactionHSCostMinOrder,0)+configFee number_total_fee,
				CONCAT('Rp',FORMAT(T_TransactionTotal+IFNULL(t_transactionHSCost,0)+IFNULL(t_transactionHSCostMinOrder,0)+configFee,0,'de_DE')) as total,
				CONCAT('Rp',FORMAT(configFee,0,'de_DE')) as fee, 
				IFNULL(PramitaPgID,0) as local_pay,
				IFNULL(t_transactionHSCost,0) as hs_cost,
				IFNULL(t_transactionHSCostMinOrder,0) as hs_costminorder,
				configFee config_fee,
				configFee number_fee,
				configEhac as number_ehac_fee,
				CONCAT('Rp',FORMAT(configEhac,0,'de_DE')) as ehac_fee,
				0 ehac_patient,
				IF(T_TransactionIsActive = 'E','expired',IF(T_TransactionIsLunas = 'N','pending','done')) as status,
				'' as test,
				'' as patients,
				'N' as payment_status,
				0 as discount_total,
				'' as discount_details
				FROM t_transaction
				LEFT JOIN pramita_pg ON PramitaPgT_TransactionID = T_TransactionID
				JOIN m_branch ON T_TransactionM_BranchID = M_BranchID
				LEFT JOIN t_transactionHS ON t_transactionHST_TransactionID = T_TransactionID AND t_transactionHSIsActive = 'Y'
				JOIN config ON configIsActive = 'Y'
				WHERE
					T_TransactionID = ? AND T_TransactionUserID = ?";
		$header = $this->db_onelite->query($sql, array($prm['id'], $user['M_UserID']))->row_array();

		$payment  = array();
		$sql = "SELECT duitkuReqCreated as created_time,
				'' as order_id,
				pgPaymentMethodeCode as payment_code,
				pgPaymentMethodePgPaymentTypeID as payment_type,
				pgPaymentMethodePgID as pg_id,
				ADDTIME(duitkuReqCreated, configExpiredTime * 100) as expired_time,
				DATE_FORMAT(ADDTIME(duitkuReqCreated, configExpiredTime * 100),'%d-%m-%Y %H:%i') as expired_time_text,
				IFNULL(DATE_FORMAT(duikuCbLastUpdated,'%d-%m-%Y %H:%i'),'') as lunas_datetime,
				IF(duitkuCbPaymentResultCode = '00','Y','N') as is_lunas,
				duitkuReqVaNumber as va_number,
				duitkuReqPaymentUrl as url_pay,
				CONCAT('Rp ',FORMAT(TRIM(TRAILING '.00' FROM CAST(duitkuReqAmount AS CHAR)),0,'de_DE')) as total, 
				pdPaymentMethodeIcon as icon,
				pgPaymentMethodeName as payment_name
				FROM duitku_req
				JOIN pg_payment_methode ON pgPaymentMethodeCode = duitkuReqPaymentMethode AND pgPaymentMethodeIsActive = 'Y'
				JOIN config ON configIsActive = 'Y'
				LEFT JOIN duitku_cb ON duitkuCbMerchantOrderId = duitkuReqMerchantOrderID AND duitkuCbPaymentResultCode = '00'
				WHERE
					duitkuReqMerchantOrderID = '{$header['order_number']}' AND duitkuReqStatusMessage = 'SUCCESS' LIMIT 1";
		//echo $sql;
		$payment = $this->db_onelite->query($sql)->row_array();
		if ($payment) {
			if ($payment['is_lunas'] == 'N' && $payment['payment_code'] != 'VC')
				$header['payment_status'] = 'X';
			if ($payment['is_lunas'] == 'Y')
				$header['payment_status'] = 'Y';
		}

		$sql = "SELECT T_OrderID as id,
					T_OrderNumber as trx_number,
					T_OrderQrCode as qrcode,
					M_BranchName as branch_name,
					M_BranchAddress as branch_address,
					M_PatientName as patient_name,
					IFNULL(M_PatientIDNumber,'') as id_number,
					IFNULL(M_IdTypeName,'') as id_name,
					Test_PurposeName as purpose_name,
					T_OrderIsKlinisi as is_klinisi,
					DATE_FORMAT(T_OrderDate,'%d-%m-%Y') as order_date,
					DATE_FORMAT(T_OrderTime,'%H:%i') as order_time,
					DATE_FORMAT(T_OrderCreated,'%d-%m-%Y %H:%i') as order_created,
					CONCAT('Rp ',FORMAT(T_TransactionTotal,0,'de_DE')) as total,
					T_OrderEHAC as fee_ehac,
					IF(T_OrderTest_PurposeID = 5,'Y','N') as is_ehac,
					'N' as xshow,
					'' as details,
					'' as deliveries,
					'' as schedules,
					'' as requirements
				FROM t_order 
				JOIN t_transaction ON T_OrderT_TransactionID = T_TransactionID
				JOIN m_branch ON T_OrderM_BranchID = M_BranchID
				JOIN m_patient ON T_OrderM_PatientID = M_PatientID
				LEFT JOIN m_idtype ON M_PatientM_IdTypeID = M_IdTypeID
				LEFT JOIN test_purpose ON T_OrderTest_PurposeID = Test_PurposeID
				WHERE
					T_OrderT_TransactionID = ? AND T_TransactionUserID = ?";
		$rows = $this->db_onelite->query($sql, array($prm['id'], $user['M_UserID']))->result_array();
		//echo $this->db_onelite->last_query();
		if ($rows) {
			foreach ($rows as $k => $v) {
				//	echo date("l") . "<br>";

				// Prints the day, date, month, year, time, AM or PM
				//echo date("l jS \of F Y h:i:s A");

				$mydate = new DateTime($v['order_date']);
				$hari = $mydate->format('l');
				//echo $hari;
				if ($hari == 'Sunday') {
					$hari = 'Minggu';
				}
				if ($hari == 'Monday') {
					$hari = 'Senin';
				}
				if ($hari == 'Tuesday') {
					//echo $hari;
					$hari = 'Selasa';
				}
				if ($hari == 'Wednesday') {
					$hari = 'Rabu';
				}
				if ($hari == 'Thursday') {
					$hari = 'Kamis';
				}
				if ($hari == 'Friday') {
					$hari = 'Jumat';
				}
				if ($hari == 'Saturday') {
					$hari = 'Sabtu';
				}
				$rows[$k]['order_date'] = $hari . ", " . $v['order_date'];
				$sql = "SELECT IF(ISNULL(T_OrderDetailT_PacketName),T_OrderDetailT_TestName,T_OrderDetailT_PacketName) as test_name,
							IFNULL(T_ResultOptionName,'') as result_option_name,
							CONCAT('Rp',FORMAT(T_OrderDetailAmount,0,'de_DE')) as amount,
							T_OrderDetailDiscount as discount_persen,
							T_OrderDetailDiscountRp as discount_rp,
							CONCAT('Rp',FORMAT(T_OrderDetailSubtotal,0,'de_DE')) as subtotal,
							CONCAT('Rp',FORMAT(T_OrderDetailTotal,0,'de_DE')) as total,
							T_OrderDetailTotal as total_number
						FROM t_orderdetail
						LEFT JOIN t_resultoption ON T_OrderDetailT_ResultOptionID = T_ResultOptionID
						WHERE
							T_OrderDetailT_OrderID = ?";
				$details = $this->db_onelite->query($sql, array($v['id']))->result_array();
				$total_order_x = 0;
				foreach ($details as $kdetail => $vdetail) {
					$total_order_x =  $total_order_x + intval($vdetail['total_number']);
				}
				$rows[$k]['total'] = $total_order_x + intval($v['fee_ehac']);
				//echo $this->db_onelite->last_query();
				$rows[$k]['details'] = $details;
				$sql = "SELECT M_DeliveryName as delivery_name,
							M_DeliveryTypeName as delivery_type_name,
							GROUP_CONCAT(T_OrderDeliveriesDestination separator ',') as destination
						FROM t_orderdeliveries
						JOIN m_delivery ON T_OrderDeliveriesM_DeliveryID = M_DeliveryID
						JOIN m_deliverytype ON T_OrderDeliveriesM_DeliveryTypeID = M_DeliveryTypeID
						WHERE
							T_OrderDeliveriesT_OrderID = ?
						GROUP BY T_OrderDeliveriesM_DeliveryTypeID";
				$deliveries = $this->db_onelite->query($sql, array($v['id']))->result_array();
				$rows[$k]['deliveries'] = $deliveries;

				$sql = "SELECT  T_TestName as test_name,
								DATE_FORMAT(T_OrderScheduleDate,'%d-%m-%Y') as schedule_date,
								DATE_FORMAT(T_OrderScheduleTime,'%H:%i') as schedule_time
						FROM t_orderschedule
						JOIN t_test ON T_OrderScheduleT_TestID = T_TestID
						WHERE
							T_OrderScheduleT_OrderID = ?";
				$schedules = $this->db_onelite->query($sql, array($v['id']))->result_array();
				if ($schedules) {
					foreach ($schedules as $ksx => $schedule) {
						$sxdate = new DateTime($schedule['schedule_date']);
						$hari = $mydate->format('l');
						//echo $hari;
						if ($hari == 'Sunday') {
							$hari = 'Minggu';
						}
						if ($hari == 'Monday') {
							$hari = 'Senin';
						}
						if ($hari == 'Tuesday') {
							//echo $hari;
							$hari = 'Selasa';
						}
						if ($hari == 'Wednesday') {
							$hari = 'Rabu';
						}
						if ($hari == 'Thursday') {
							$hari = 'Kamis';
						}
						if ($hari == 'Friday') {
							$hari = 'Jumat';
						}
						if ($hari == 'Saturday') {
							$hari = 'Sabtu';
						}
						$schedules[$ksx]['schedule_date'] = $hari . ", " . $schedule['schedule_date'];
					}
					$rows[$k]['schedules'] = $schedules;
				}

				$requirements = array();
				$sql = "SELECT OrderRequirementRequirement as requirement
						FROM order_requirement
						WHERE
						OrderRequirementT_OrderID = ?";
				$get_requirements = $this->db_onelite->query($sql, array($v['id']))->result_array();
				if ($get_requirements) {
					foreach ($get_requirements as $key_requirement => $value_requirement) {
						array_push($requirements, $value_requirement['requirement']);
					}
				}
				$rows[$k]['requirements'] = $requirements;
			}

			$ehac = 0;
			$ehac_fee = 0;
			$patient_ehac = 0;
			if (intval($header['local_pay']) > 0) {
				$header['number_fee'] = 0;
				$header['fee'] = "Rp" . number_format($ehac_fee, 0, ',', '.');

				$header['number_total_fee'] = intval($header['number_total_fee']) - intval($header['config_fee']);
				$header['total'] = "Rp" . number_format($header['number_total_fee'], 0, ',', '.');
			}
			foreach ($rows as $kp => $vp) {
				//print_r($vp);
				if ($vp['is_ehac'] == 'Y') {
					$ehac_fee = intval($vp['fee_ehac']);
					$ehac = $ehac + intval($vp['fee_ehac']);
					$patient_ehac = $patient_ehac + 1;
				}
			}

			$header['ehac_patient'] = $patient_ehac;
			$xsum_ehac = $ehac;
			//echo intval($xsum_ehac);
			$header['number_fee'] = $header['number_fee'] + intval($xsum_ehac);
			$header['number_ehac_fee'] = $ehac_fee;
			$header['ehac_fee'] = "Rp" . number_format($ehac_fee, 0, ',', '.');
			//echo $header['number_total_fee'];
			$header['number_total_fee'] = intval($header['number_total_fee']) + intval($xsum_ehac);
			$header['total'] = "Rp" . number_format($header['number_total_fee'], 0, ',', '.');
		}

		$header['discount_total'] = 0;
		//$get_discount = $this->get_discount($header,$user);
		//print_r($get_discount);
		//$header['discount_details'] = $get_discount;
		/*if(count($get_discount) > 0){
			foreach($get_discount as $dk => $dv){
				$header['discount_total'] = $header['discount_total'] + intval($dv['amount']);
			}

			$header['number_total_fee'] = intval($header['number_total_fee']) - $header['discount_total'];
			$header['total'] =  number_format($header['number_total_fee'],0,',','.');
		}*/

		echo json_encode(array(
			"status" => "OK",
			"data" => array('header' => $header, 'details' => $rows, 'payment' => $payment)
		));
	}


	function getPopuler()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}


		$prm = $this->sys_input;
		$user = $this->sys_user;

		$rows = array(
			array(
				'bruto' => 1100000,
				'description' => '-',
				'is_covid' => 'N',
				'moud_id' => 4874,
				'name' => 'Hematologi Lengkap',
				'nat_test' => array(9030),
				'packet_id' => 4093,
				'price' => 935000,
				'requirement' => array(),
				'schedule' => array(),
				'type' => 'test',
				'value_requirement' => 'N'
			),
			array(
				'bruto' => 120000,
				'description' => '-',
				'is_covid' => 'N',
				'moud_id' => 4874,
				'name' => 'Urine Rutin',
				'nat_test' => array(9029),
				'packet_id' => 4091,
				'price' => 120000,
				'requirement' => array(),
				'schedule' => array(),
				'type' => 'test',
				'value_requirement' => 'N'
			),
			array(
				'bruto' => 56000,
				'description' => '-',
				'is_covid' => 'N',
				'moud_id' => 4874,
				'name' => 'SGOT',
				'nat_test' => array(9829),
				'packet_id' => 1493,
				'price' => 56000,
				'requirement' => array(),
				'schedule' => array(),
				'type' => 'test',
				'value_requirement' => 'N'
			),
			array(
				'bruto' => 56000,
				'description' => '-',
				'is_covid' => 'N',
				'moud_id' => 4874,
				'name' => 'SGPT',
				'nat_test' => array(2829),
				'packet_id' => 2493,
				'price' => 56000,
				'requirement' => array(),
				'schedule' => array(),
				'type' => 'test',
				'value_requirement' => 'N'
			)
		);

		echo json_encode(array(
			"status" => "OK",
			"data" => $rows
		));
	}

	function getPointsUser()
	{
		if (! $this->isLogin) {
			echo json_encode(array(
				"status" => "INVALID_TOKEN",
				"message" => "TOKEN SUDAH TIDAK VALID"
			));
			exit;
		}


		$prm = $this->sys_input;
		$user = $this->sys_user;

		$points = $this->getPoints($user['M_UserID']);
		$points = number_format($points, 0, ',', '.');


		echo json_encode(array(
			"status" => "OK",
			"data" => $points
		));
	}


	function getPoints($userid)
	{
		$points = 0;
		$sql = "SELECT *
				FROM m_userpoint
				WHERE M_UserPointM_UserID = ?";
		$get_points = $this->db_onelite->query($sql, array($userid));
		if ($get_points) {
			$points = $get_points->row()->M_UserPointAmount;
		}

		return $points;
	}
}
