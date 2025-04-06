<?php
class Patient extends MY_Controller
{
   var $db_onedev;
   public function index()
   {
      echo "Patient API";
   }
   public function __construct()
   {
      parent::__construct();
      $this->db_onedev = $this->load->database("onedev", true);
   }

   public function add_notes($orderid){
	   $sql = "	SELECT F_PaymentT_OrderHeaderID as note_order_id,
				F_PaymentID as note_id,
				F_PaymentDate as note_date,
				F_PaymentNumber as note_number,
				GROUP_CONCAT(M_PaymentTypeName separator ' , ') as paymenttypes_name,
				SUM(F_PaymentDetailAmount) as note_amount,
				M_UserUsername as note_user,
				F_PaymentDetailIsActive as note_active
				FROM f_payment
				JOIN f_paymentdetail ON F_PaymentDetailF_PaymentID = F_PaymentID
				JOIN m_paymenttype ON F_PaymentDetailM_PaymentTypeID = M_PaymentTypeID
				LEFT JOIN m_user ON F_PaymentDetailUserID = M_UserID
				WHERE
				F_PaymentT_OrderHeaderID = {$orderid}
				GROUP BY F_PaymentID";
		$query = $this->db_onedev->query($sql);
		if ($query) {
			$rows = $query->result_array();
			return $rows;

		} else {
			$this->sys_error_db("get notes", $this->db_onedev);
			exit;
		}
   }

   public function search_v2()
   {
	    //# cek token valid
        if (! $this->isLogin) {
        $this->sys_error("Invalid Token");
        exit;
        }
		$prm = $this->sys_input;
		$filter = $prm['filter'];
		$search = $prm["search"];
		$status = $prm["status"];
		$number_limit = 20;
		$number_offset = ($prm['current_page'] - 1) * $number_limit ;
		$where = "";
		if($search != '')
			$where .= "( M_PatientName LIKE '%{$search}%' OR T_OrderHeaderLabNumber LIKE '%{$search}%' )  AND ";

		if($filter == 'day')
			$where .= " DATE(T_OrderHeaderDate) = CURDATE() AND ";
		if($filter == 'notsampled')
			$where .= " Last_StatusM_StatusID < 7 AND ";

      $sql = "	SELECT count(*) as total
				FROM(
					SELECT T_OrderHeaderID
					FROM t_orderheader
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
					JOIN m_mou ON T_OrderHeaderM_MouID = M_MouID
					JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
					LEFT JOIN last_statuspayment ON Last_StatusPaymentT_OrderHeaderID = T_OrderHeaderID AND Last_StatusPaymentIsActive = 'Y'
					JOIN t_orderdetail on T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' aND T_OrderDetailT_TestIsPrice    = 'Y'
					join t_orderpromise on T_OrderPromiseT_OrderHeaderID  = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
					join t_orderdelivery on T_OrderDeliveryT_OrderHeaderID  = T_OrderHeaderID AND T_OrderDeliveryIsActive = 'Y'
					join m_delivery on T_OrderDeliveryM_DeliveryID  = M_DeliveryID AND M_DeliveryIsActive = 'Y'


					WHERE
					$where
                                        (
                                          ('{$status}' = 'N' AND (Last_StatusPaymentIsLunas = 'N' OR Last_StatusPaymentID IS NULL))
                                          OR ('{$status}' = 'Y' AND Last_StatusPaymentIsLunas = 'Y')
                                          OR '{$status}' = 'A'
                                        )
					group by T_OrderHeaderID
				)x";

		$query = $this->db_onedev->query($sql, $sql_param);


		$tot_count = 0;
		$tot_page = 0;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
			$tot_page = ceil($tot_count/$number_limit);
		} else {
			$this->sys_error_db("t_samplestorage count", $this->db_onedev);
			exit;
		}
    $janji = T_OrderPromiseDateTime;
    $janji_hasil = Date_format($janji, "d-m-Y H:i:s");

    $sql ="
               set @counter = 0;
               SELECT @counter := @counter +1 no_urut, 
                        t_orderheader.*,T_OrderHeaderIsCito as cito,
				M_PatientNoReg,
				concat(M_TitleName,'. ',M_PatientName) as M_PatientName,
				M_CompanyName,
				M_MouName,
				DATE(T_OrderHeaderDate) as order_date,
				T_OrderHeaderTotal as totalbill,
				IFNULL(Last_StatusPaymentPaid,0) as paid,
				IFNULL(Last_StatusPaymentUnpaid,T_OrderHeaderTotal)as unpaid,
				Last_StatusPaymentIsLunas as flaglunas,
				Last_StatusM_StatusID as last_status,
				'' as notes,
				M_MouMinDP as mindp_percent,
				GROUP_CONCAT(distinct concat(T_OrderDetailT_TestName,'^',T_OrderDetailIsCito) SEPARATOR ',') as test ,
				fn_report_promise_list(T_OrderHeaderID) as janji,
				(M_MouMinDP/100) * T_OrderHeaderTotal as mindp_amount,
				case
					when Last_StatusPaymentPaid = '0' then 'BELUM BAYAR'
					when Last_StatusPaymentIsLunas = 'Y' then 'LUNAS'
					when Last_StatusPaymentIsLunas = 'N' then 'BELUM LUNAS' ELSE ''
				END as status,
				GROUP_CONCAT(distinct M_DeliveryName SEPARATOR ' , ') as delivery
				FROM t_orderheader
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_mou ON T_OrderHeaderM_MouID = M_MouID
				JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
				LEFT JOIN last_statuspayment ON Last_StatusPaymentT_OrderHeaderID = T_OrderHeaderID AND Last_StatusPaymentIsActive = 'Y'
				JOIN t_orderdetail on T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' aND T_OrderDetailT_TestIsPrice    = 'Y'
				join t_orderpromise on T_OrderPromiseT_OrderHeaderID  = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				join t_orderdelivery on T_OrderDeliveryT_OrderHeaderID  = T_OrderHeaderID AND T_OrderDeliveryIsActive = 'Y'
				join m_delivery on T_OrderDeliveryM_DeliveryID  = M_DeliveryID AND M_DeliveryIsActive = 'Y'


				WHERE
				$where
                                ( ('{$status}' = 'N' AND (Last_StatusPaymentIsLunas = 'N' OR Last_StatusPaymentID IS NULL)) OR ('{$status}' = 'Y' AND Last_StatusPaymentIsLunas = 'Y')
                                          OR '{$status}' = 'A'
)
				group by T_OrderHeaderID
				ORDER BY `fn_get_cito`(T_OrderHeaderID),T_OrderPromiseDateTime asc
				limit $number_limit offset $number_offset";
		//echo $sql;
		$query = $this->db_onedev->query($sql, $sql_param);
		$rows = $query->result_array();
		/*if($rows){
			foreach($rows as $k => $v){
				$sql = "SELECT * FROM t_orderpromise WHERE T_OrderPromiseT_OrderHeaderID";
				$rows[$k]['result_promise'] = $this->add_notes($v['T_OrderHeaderID']);
			}
		}*/


		$result = array("total" => $tot_page, "total_filter"=>count($rows),"records" => $rows, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }

   public function search()
   {
	    //# cek token valid
        if (! $this->isLogin) {
        $this->sys_error("Invalid Token");
        exit;
        }
		$prm = $this->sys_input;
		$filter = $prm['filter'];
		$search = $prm["search"];
		$status = $prm["status"];
		$number_limit = 20;
		$number_offset = ($prm['current_page'] - 1) * $number_limit ;
		$where = "";
		if($search != '')
			$where .= "( M_PatientName LIKE '%{$search}%' OR T_OrderHeaderLabNumber LIKE '%{$search}%' )  AND ";

		if($filter == 'day')
			$where .= " DATE(T_OrderHeaderDate) = CURDATE() AND ";
		if($filter == 'notsampled')
			$where .= " Last_StatusM_StatusID < 7 AND ";

      $sql = "	SELECT count(*) as total
				FROM(
					SELECT T_OrderHeaderID
					FROM t_orderheader
					JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
					JOIN m_title ON M_PatientM_TitleID = M_TitleID
					JOIN m_sex ON M_PatientM_SexID = M_SexID
					JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
					JOIN m_mou ON T_OrderHeaderM_MouID = M_MouID
					JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
					LEFT JOIN last_statuspayment ON Last_StatusPaymentT_OrderHeaderID = T_OrderHeaderID AND Last_StatusPaymentIsActive = 'Y'
					JOIN t_orderdetail on T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' aND T_OrderDetailT_TestIsPrice    = 'Y'
					join t_orderpromise on T_OrderPromiseT_OrderHeaderID  = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
					join t_orderdelivery on T_OrderDeliveryT_OrderHeaderID  = T_OrderHeaderID AND T_OrderDeliveryIsActive = 'Y'
					join m_delivery on T_OrderDeliveryM_DeliveryID  = M_DeliveryID AND M_DeliveryIsActive = 'Y'


					WHERE
					$where
                                        (
                                          ('{$status}' = 'N' AND (Last_StatusPaymentIsLunas = 'N' OR Last_StatusPaymentID IS NULL))
                                          OR ('{$status}' = 'Y' AND Last_StatusPaymentIsLunas = 'Y')
                                          OR '{$status}' = 'A'
                                        )
					group by T_OrderHeaderID
				)x";

		$query = $this->db_onedev->query($sql, $sql_param);


		$tot_count = 0;
		$tot_page = 0;
		if ($query) {
			$tot_count = $query->result_array()[0]["total"];
			$tot_page = ceil($tot_count/$number_limit);
		} else {
			$this->sys_error_db("t_samplestorage count", $this->db_onedev);
			exit;
		}
    $janji = T_OrderPromiseDateTime;
    $janji_hasil = Date_format($janji, "d-m-Y H:i:s");

		$sql = 	"SELECT '' as rownumber,t_orderheader.*,T_OrderHeaderIsCito as cito,
				M_PatientNoReg,
				concat(M_TitleName,'. ',M_PatientName) as M_PatientName,
				M_CompanyName,
				M_MouName,
				DATE(T_OrderHeaderDate) as order_date,
				T_OrderHeaderTotal as totalbill,
				IFNULL(Last_StatusPaymentPaid,0) as paid,
				IFNULL(Last_StatusPaymentUnpaid,T_OrderHeaderTotal)as unpaid,
				Last_StatusPaymentIsLunas as flaglunas,
				Last_StatusM_StatusID as last_status,
				'' as notes,
				M_MouMinDP as mindp_percent,
				GROUP_CONCAT(distinct concat(T_OrderDetailT_TestName,'^',T_OrderDetailIsCito) SEPARATOR ',') as test ,
				fn_report_promise_list(T_OrderHeaderID) as janji,
				(M_MouMinDP/100) * T_OrderHeaderTotal as mindp_amount,
				case
					when Last_StatusPaymentPaid = '0' then 'BELUM BAYAR'
					when Last_StatusPaymentIsLunas = 'Y' then 'LUNAS'
					when Last_StatusPaymentIsLunas = 'N' then 'BELUM LUNAS' ELSE ''
				END as status,
				GROUP_CONCAT(distinct M_DeliveryName SEPARATOR ' , ') as delivery,
				fn_lookup_external(T_OrderHeaderLabNumber,'L') as external_numbering
				FROM t_orderheader
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
				JOIN m_mou ON T_OrderHeaderM_MouID = M_MouID
				JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
				LEFT JOIN last_statuspayment ON Last_StatusPaymentT_OrderHeaderID = T_OrderHeaderID AND Last_StatusPaymentIsActive = 'Y'
				JOIN t_orderdetail on T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y' aND T_OrderDetailT_TestIsPrice    = 'Y'
				join t_orderpromise on T_OrderPromiseT_OrderHeaderID  = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				join t_orderdelivery on T_OrderDeliveryT_OrderHeaderID  = T_OrderHeaderID AND T_OrderDeliveryIsActive = 'Y'
				join m_delivery on T_OrderDeliveryM_DeliveryID  = M_DeliveryID AND M_DeliveryIsActive = 'Y'


				WHERE
				$where
                                ( ('{$status}' = 'N' AND (Last_StatusPaymentIsLunas = 'N' OR Last_StatusPaymentID IS NULL)) OR ('{$status}' = 'Y' AND Last_StatusPaymentIsLunas = 'Y')
                                          OR '{$status}' = 'A'
)
				group by T_OrderHeaderID
				ORDER BY `fn_get_cito`(T_OrderHeaderID),T_OrderPromiseDateTime asc
				limit $number_limit offset $number_offset";
		//echo $sql;
		$query = $this->db_onedev->query($sql, $sql_param);
		$rows = $query->result_array();
		if($rows){
			foreach($rows as $k => $v){
				$xno = ($k + 1) + $number_offset;
				$rows[$k]['rownumber'] = $xno;
			}
		}


		$result = array("total" => $tot_page,"total_all"=>$tot_count, "total_filter"=>count($rows),"records" => $rows, "sql"=> $this->db_onedev->last_query());
		$this->sys_ok($result);
		exit;
   }

   function lookup_promises(){
	    if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
        }

        //# ambil parameter input
		$xuserid = $this->sys_user['M_UserID'];
		$prm = $this->sys_input;

		$sql = "SELECT T_OrderPromiseID as id, DATE_FORMAT(T_OrderPromiseDateTime,'%d-%m-%Y') as xdate, TIME_FORMAT(T_OrderPromiseDateTime,'%H:%i') as xtime
				FROM t_orderpromise
				WHERE T_OrderPromiseT_OrderHeaderID = {$prm['T_OrderHeaderID']} AND T_OrderPromiseIsActive = 'Y'";
		//echo $sql;
		$query = $this->db_onedev->query($sql)->result_array();
		if (!$query) {
			$this->sys_error_db("f_paymentdetail delete");
			exit;
		}

		$result = array(
			"total" => 1 ,
			"records" => $query
		);
		$this->sys_ok($result);
		exit;
   }
   
   function lookup_barcodes()
   {
    try {
        $prm = $this->sys_input;
        //# cek token valid
        if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
        }
		$sql = "SELECT T_BarcodeLabID as id, 
						'barcode' as type,
						T_SampleTypeID,
						T_BarcodeLabID,
						T_BarcodeLabBarcode, 
						T_BarcodeLabT_OrderHeaderID as orderid, 
						T_BarcodeLabCounter, 
						T_SampleTypeName, 
						'N' as chex
				FROM t_barcodelab 
				JOIN t_sampletype ON T_BarcodeLabT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationIsNonLab = ''
				WHERE 
				T_BarcodeLabT_OrderHeaderID = {$prm['T_OrderHeaderID']} AND T_BarcodeLabIsActive = 'Y'
				UNION
				SELECT T_OrderHeaderID as id, 'formulir' as type, 0 as T_SampleTypeID,0 as T_BarcodeLabID,T_OrderHeaderLabNumber as T_BarcodeLabBarcode,{$prm['T_OrderHeaderID']}, 1, 'Formulir' as T_SampleTypeName, 'N' as chex
				FROM t_orderheader
				WHERE
				T_OrderHeaderID = {$prm['T_OrderHeaderID']}
				UNION
				SELECT T_TestID as id, 
					'nonlab' as type, 
					T_OrderDetailID as detail_id,
					'' as xxx,
					'-' as T_BarcodeLabBarcode,
					T_OrderHeaderID as order_id, 
					DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y') as order_date, 
					T_TestName as T_SampleTypeName,
					'N' as chex
				FROM t_orderheader
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN documentation_group_detail ON DocumentationGroupDetailNat_SubGroupID = T_TestNat_SubGroupID
				JOIN documentation_group ON DocumentationGroupDetailDocumentationGroupID = DocumentationGroupID AND DocumentationGroupName <> 'lab'
				WHERE
				T_OrderHeaderID = {$prm['T_OrderHeaderID']}
				GROUP BY T_TestID
				UNION 
				SELECT T_OrderDetailID as id, 
					'nonlab_group' as type, 
					T_OrderDetailID as detail_id,
					'' as xxx,
					GROUP_CONCAT(T_TestName separator ' , ') as T_BarcodeLabBarcode,
					T_OrderHeaderID as order_id, 
					DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y') as order_date, 
					'Amplop Besar' as T_SampleTypeName,
					'N' as chex
				FROM t_orderheader
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN documentation_group_detail ON DocumentationGroupDetailNat_SubGroupID = T_TestNat_SubGroupID
				JOIN documentation_group ON DocumentationGroupDetailDocumentationGroupID = DocumentationGroupID AND DocumentationGroupName <> 'lab'
				WHERE
				T_OrderHeaderID = {$prm['T_OrderHeaderID']}
				GROUP BY T_OrderHeaderID
				";
		//echo $sql;	
			
		$rows = $this->db_onedev->query($sql)->result_array();
		if($rows){
			foreach($rows as $k => $v){
				if($v['chex'] == 'N')
					$rows[$k]['chex'] = false;
				else
					$rows[$k]['chex'] = true;
			}
		}
		$result = array ("total" => 0, "records" => $rows);
		$this->sys_ok($result);

    } catch(Exception $exc) {
        $message = $exc->getMessage();
        $this->sys_error($message);
    }
   }

   function save_promises(){
	    if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
        }

        //# ambil parameter input
		$xuserid = $this->sys_user['M_UserID'];
		$prm = $this->sys_input;
		foreach($prm['data'] as $k => $v){
			$xdatetime = date('Y-m-d H:i:s',strtotime($v['xdate'].' '.$v['xtime']));
			$sql = "UPDATE t_orderpromise SET T_OrderPromiseDateTime = '{$xdatetime}' WHERE T_OrderPromiseID = {$v['id']}";
			//echo $sql;
			$query = $this->db_onedev->query($sql);
			if (!$query) {
				$this->sys_error_db("f_paymentdetail delete");
				exit;
			}
		}

		$sql = "UPDATE t_orderheaderaddon SET T_OrderHeaderAddonIsComing = 'Y'WHERE T_OrderHeaderAddOnT_OrderHeaderID = {$prm['orderid']} AND T_OrderHeaderAddOnIsActive = 'Y'";
		$this->db_onedev->query($sql);

		$result = array(
			"total" => 1 ,
			"records" => $prm
		);
		$this->sys_ok($result);
		exit;
   }


}
