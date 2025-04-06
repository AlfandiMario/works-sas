<?php
class Api_itf extends MY_Controller
{
  var $intervalHour;
  var $tmp_body;
  function __construct()
  {
    parent::__construct();
    $this->intervalHour = 12;
  }
  function param()
  {
    $body = file_get_contents("php://input");
    $this->tmp_body = $body;
    return json_decode($body, true);
  }
  function auth_token()
  {
    $headers = getallheaders();
    if (!array_key_exists('Authorization', $headers)) {
      echo json_encode(["status" => "ERR", "message" => "No Authorization Bearer [Auth003]"]);
      exit;
    }
    $token = $headers["Authorization"];
    $token = trim(str_replace("Bearer", "", $token));
    $param = JWT::decode($token, $this->SECRET_KEY, true);
    $param = json_decode(json_encode($param), true);
    $xnow = date("Y-m-d H:i:s");
    if ($param["expired"] < $xnow) {
      echo json_encode(["status" => "ERR", "message" => "Access Token expired [Auth003]"]);
      exit;
    }
    return $param["branchCode"];
  }
  function update_order()
  {
    $branch_code = $this->auth_token();
    $param = $this->param();
    $sql_i = "insert into order_download(order_DownloadT_OrderHeaderID,order_DownloadM_BranchCode) values(?,?)";
    $this->db->trans_begin();
    foreach ($param["ids"] as $id) {
      $qry = $this->db->query($sql_i, [$id, $branch_code]);
      if (!$qry) {
        // echo $this->db->last_query();
        // print_r($this->db->error());
        echo json_encode(["status" => "ERR", "message" => "Update Order error [UpdOrder001]"]);
        $this->db->trans_rollback();
        exit;
      }
    }
    $this->db->trans_commit();
    echo json_encode(["status" => "OK", "message" => "Order Updated"]);
  }
  /*

alter table api_result 
add api_ResultSpecimenCollectDate datetime,
add api_ResultSpecimenReceivedDate datetime,
add api_ResultInputDate datetime,
add api_ResultComment varchar(300),
add api_ResultNote varchar(300),
add api_ResultPathologistNote varchar(300),
add api_ResultNormalNote text,
add api_ResultVerificationUser varchar(300),
add api_ResultVerificationDate datetime,
add api_ResultAuthorizationDate datetime,
add api_ResultAuthorizationUser varchar(300);

alter table api_result 
add api_ResultAgeYear varchar(3),
add api_ResultAgeMonth varchar(3),
add api_ResultAgeDays varchar(3);

{
    "PatientName": "Pasien 5 Karyawan PT PMF",
    "LisRegNo": "W10530500",
    "LisTestID": "00000062",
    "HisRegNo": "04802782DA",
    "SpecimenCollectDate": "2024-02-08 08:12:49",
    "SpecimenReceivedDate": "2024-02-08 08:12:49",
    "TestName": "Asam Urat",
    "TestTypeID": "1",
    "TestHeader": "False",
    "TestGroupName": "Kimia Klinik",
    "TestSubGroupName": "Fungsi Lemak",
    "TestSequence": "L010200000012",
    "Result": "5.3",
    "ResultInputDate": "2024-02-08 08:12:49",
    "ResultComment": "Duplo",
    "ResultNote": "Periksa ulang dalam 3 hari",
    "PathologistNote": "",
    "ReferenceValue": "3,5 - 7,2",
    "ReferenceNote": "",
    "TestMethodName": "URICASE",
    "TestFlagSign": "",
    "TestUnitsName": "mg\/dL",
    "InstrumentName": "Kimia Klinik Analyzer",
    "VerificationDate": "2023/06/13 12:45:39",
    "VerificationUser": "Analis Bagian Kimia",
    "AuthorizationDate": "2023/06/13 13:00:12",
    "AuthorizationUser": "dr. Laboratorium",
    "GreaterthanValue": "3.5",
    "LessthanValue": "7.2",
    "AgeYear": "042",
    "AgeMonth": "00",
    "AgeDays": "14",
    "TestFlagTextNote": "",
    "HisTestIDOrder": "720400001",
    "TransferFlag": "1"
}

  */
  function LabResult()
  {
    $branchCode = "NOAUTH";
    $param = $this->param();
    if (json_last_error() != JSON_ERROR_NONE) {
      $log_ApiID = $this->log($branchCode, "LabResult", $this->tmp_body, "", "Result");
      $sql = "update cpone_log.log_api set log_ApiIsParsed = 'E', log_ApiParam =? where log_ApiID=?";
      $qry = $this->db->query($sql, [$this->tmp_body, $log_ApiID]);
      if (!$qry) {
        echo json_encode([
          "Status" => ["OK" => false, "Code" => 0, "Messages" => "Invalid JSON Param [Result001]"],
          "OrderList" => [],
        ]);
        exit;
      }
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Invalid JSON Param [Result002]"],
        "OrderList" => [],
      ]);
      exit;
    }
    // insert to log_api 
    $log_ApiID = $this->log($branchCode, "LabResult", $this->tmp_body, "", "Result");
    //parsed param 
    // partial commit
    $this->db->trans_begin();

    $sql_i = "insert into api_result(api_ResultDate,api_ResultNolab,
    api_ResultPatient, api_ResultTestCode, api_ResultTestName, api_ResultResult, 
    api_ResultFlag, api_ResultUnit, api_ResultNormalText, api_ResultNormalNote, api_ResultNormalMethode, 
    api_ResultNormalMinValue, api_ResultNormalMinInclusive, api_ResultNormalMaxValue, api_ResultNormalMaxInclusive,
    api_ResultM_BranchCode, api_ResultLog_ApiID,
    api_ResultSpecimenCollectDate, api_ResultSpecimenReceivedDate, api_ResultInputDate, api_ResultComment,
    api_ResultPathologistNote, api_ResultVerificationUser, api_ResultVerificationDate, api_ResultAuthorizationUser, 
    api_ResultAuthorizationDate, api_ResultAgeYear, api_ResultAgeMonth, api_ResultAgeDays, api_ResultNote
    ) 
    values ( 
    now(),?,
    ?,?,?,?, 
    ?,?,?,?,?,
    ?,?,?,?,
    ?,?,
    ?,?,?,?,
    ?,?,?,?,
    ?,?,?,?,?
    )";


    $qry = $this->db->query($sql_i, [
      $param["HisRegNo"],
      $param["PatientName"], $param["LisTestID"], $param["TestName"], $param["Result"],
      $param["TestFlagSign"], $param["TestUnitsName"], $param["ReferenceValue"], $param["ReferenceNote"], $param["TestMethodName"],
      $param["GreaterthanValue"], "N", $param["LessthanValue"], "N",
      $branchCode, $log_ApiID,
      $param["SpecimenCollectDate"], $param["SpecimenReceivedDate"], $param["ResultInputDate"], $param["ResultComment"],
      $param["PathologistNote"], $param["VerificationUser"], $param["VerificationDate"], $param["AuthorizationUser"],
      $param["AuthorizationDate"], $param["AgeYear"], $param["AgeMonth"], $param["AgeDays"], $param["ResultNote"]
    ]);

    if (!$qry) {
      echo json_encode([
        "Status" => [
          "OK" => false, "Code" => 0, "Messages" => "Sys Error [Result003]",
          "err" => $this->db->error()
        ],
        "OrderList" => [],
      ]);
      $this->db->trans_rollback();
      exit;
    }

    $date = $param["date"];
    $noreg = $param["noreg"];
    $nolab = $param["nolab"];
    $patient = $param["patient"];
    foreach ($param["result"] as $r) {
      $abnormal = $r["abnormal"];
      $flag = $r["flag"];
      $is_quantitative = $r["is_quantitative"];
      $result = $r["result"];
      $test_code = $r["test_code"];
      $test_name = $r["test_name"];
      $unit = $r["unit"];
      $description = $r["ref_range"]["description"];
      $methode = $r["ref_range"]["methode"];
      $min_comparator = $r["ref_range"]["min"]["comparator"];

      $min_value = $r["ref_range"]["min"]["value"];
      $max_value = $r["ref_range"]["max"]["value"];
      $max_comparator = $r["ref_range"]["max"]["comparator"];


      $min_value_inclusive = "N";
      if ($min_comparator == ">=") $min_value_inclusive = "Y";
      $max_value_inclusive = "N";
      if ($max_comparator == ">=") $max_value_inclusive = "Y";
      $qry = $this->db->query($sql_i, [
        $date, $nolab,
        $noreg, $patient, $test_code, $test_name,
        $result, $flag, $unit, $is_quantitative,
        $abnormal, $description, $methode,
        $min_value, $min_value_inclusive,
        $max_value, $max_value_inclusive,
        $branchCode, $log_ApiID
      ]);
    }
    $this->db->trans_commit();
    $this->parse($log_ApiID);
    echo json_encode([
      "Status" => ["OK" => true, "Code" => -1, "Messages" => "SUCCESS"],
    ]);
  }

  function SetReceivedOrder()
  {
    $param = $this->param();
    $this->log("NOAUTH", "SetReceivedOrder", $this->tmp_body, "", "SetReceivedOrder");
    $nolab = $param["orderNumber"];
    $labRegNo = $param["LabRegNo"];
    $receivedFlag = $param["ReceivedFlag"];
    $ReceivedDateTime = $param["ReceivedDatetime"];
    $sql = "insert into order_download(order_DownloadT_OrderHeaderID,order_DownloadT_OrderHeaderDate,order_DownloadLabRegNo,
    order_DownloadReceivedFlag,order_DownloadReceivedDateTime)
    select T_OrderHeaderID, T_OrderHeaderDate, ?, ?, ?
    from t_orderheader
    where T_OrderHeaderLabNumber = ?";
    $qry = $this->db->query($sql, [$labRegNo, $receivedFlag, $ReceivedDateTime, $nolab]);
    if (!$qry) {
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Get Order Detail issue [Order005]"],
        "OrderList" => [],
      ]);
      exit;
    }
    echo json_encode([
      "Status" => [
        "OK" => true, "Code" => -1,
        "Messages" => ""
      ],
      "orderNumber" => $nolab,
      "ReceivedFlag" => $receivedFlag,
      "LabRegNo" => $labRegNo,
      "ReceivedDatetime" => $ReceivedDateTime
    ]);
  }
  function GetOrderDetail()
  {
    $param = $this->param();
    $nolab = $param["orderNumber"];
    $sql = "select 
    CorporateID,
    CorporateName,
    M_BranchCodeLab outletId,
    M_BranchCode BranchCode,
    M_BranchName BranchName,
    M_PatientNoreg PatientCode,
    concat(
    ifnull(M_TitleName,''),
    if(M_TitleName is not null, ' ',''),
    if (M_PatientPrefix is not null, M_PatientPrefix, '') ,
    if (M_PatientPrefix is not null, ' ', '') ,
    M_PatientName,
    if (M_PatientSuffix is not null, M_PatientSuffix, '') ,
    if (M_PatientSuffix is not null, ' ', '') 
    ) PatientName,
    if( lower(M_PatientGender) = 'male', 'M','F') PatientSexCode,
    if( lower(M_PatientGender) = 'male', 'Male','Female') PatientSexName,
    replace(M_PatientDOB,'-','/') PatientDOB,
    'CpOne Address' PatientAdress,
    T_OrderHeaderLabNumber OrderNumber,
    replace(T_OrderHeaderDate,'-','/') OrderDateTime,
    'CpOne001' DoctorOrderCode ,
    'Dr CpOne' DoctorOrderName , 
    M_BranchCode GuarantorID,
    M_BranchName GuarantorName,
    'Cp001' AgreementID,
    'CpOne' AgreementName,
    false ReceivedFlag,
    null LabRegNo,
    null ReceivedDateTime,
    ifnull(Nat_TestMapCode,'Un-Map') ItemCode,
    T_TestName ItemName
    from t_orderheader 
    join m_branch 
    on T_OrderHeaderLabNumber = ?
    and T_OrderHeaderIsActive = 'Y'
    and T_OrderHeaderM_BranchID = M_BranchID
    and M_BranchIsActive = 'Y'
    join corporate on T_OrderHeaderCorporateID = CorporateID
    and CorporateIsActive = 'Y'
    join m_patient on T_OrderHeaderM_PatientID = M_PatientID 
    join t_orderdetail on T_OrderHeaderID = T_OrderDetailT_OrderHeaderID
    and T_OrderDetailIsActive = 'Y'
    join t_test on T_OrderDetailT_TestID = T_TestID 
    and (T_TestIsPrice = 'Y' 
    -- or T_TestIsResult = 'Y'
    )
    and T_TestNat_GroupID=1
    left join nat_testmap 
    on T_TestNat_TestID = Nat_TestMapNat_TestID
    and Nat_TestMapIsActive = 'Y'  
    left join m_title on M_PatientM_TitleID = M_TitleID
    order by T_OrderHeaderLabNumber,Nat_TestMapCode";

    $qry = $this->db->query($sql, [$nolab]);
    //echo $this->db->last_query();

    if (!$qry) {
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Get Order Detail issue [Order004]"],
        "OrderList" => [],
      ]);
      exit;
    }
    $rows = $qry->result_array();
    $result = [];
    foreach ($rows as $idx => $r) {
      if ($idx == 0) {
        $result = $r;
        unset($result["ItemCode"]);
        unset($result["ItemName"]);
        $result["OrderedItems"] = [];
        $result["ReceivedFlag"] = (bool) false;
      }
      $result["OrderedItems"][] = [
        "itemCode" => $r["ItemCode"],
        "itemName" => $r["ItemName"]
      ];
    }

    $this->send_order($result);

    echo json_encode([
      "Status" => ["OK" => true, "Code" => -1, "Messages" => "SUCCESS"],
      "OrderList" => $result
    ]);
  }

  function GetOutstandingList()
  {
    $param = $this->param();
    $s_date = $param["orderDateTimeStart"]; // “2019/05/01”,
    $e_date = $param["orderDateTimeEnd"]; // : “2019/06/01”,
    $branchCode = $param["branchCode"]; // : “2019/06/01”,
    $receivedFlag = $param["receivedFlag"]; //: fals
    if (strtotime($s_date)) {
      $s_date = date("Y-m-d", strtotime($s_date));
    }
    if (strtotime($e_date)) {
      $e_date = date("Y-m-d", strtotime($e_date));
    }
    $s_date = $s_date . " 00:00:00";
    $e_date = $e_date . " 23:59:59";

    if (!strtotime($s_date)) {
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "orderDateTimeStart is not valid [Order001]"],
        "OrderList" => []
      ]);
      exit;
    }
    if (!strtotime($e_date)) {
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "orderDateTimeEnd {$param["orderDateTimeEnd"]} $e_date is not valid [Order002]"],
        "OrderList" => []
      ]);
      exit;
    }
    $w_flag = " not in ";
    $s_flag = "false";
    if ($receivedFlag === true) {
      $w_flag = " in ";
      $s_flag = "true";
    }
    $sql = "select 
    CorporateID,
    CorporateName,
    M_BranchCode BranchCode,
    M_BranchName BranchName,
    M_PatientNoreg PatientCode,
    concat(
    ifnull(M_TitleName,''),
    if(M_TitleName is not null, ' ',''),
    if (M_PatientPrefix is not null, M_PatientPrefix, '') ,
    if (M_PatientPrefix is not null, ' ', '') ,
    M_PatientName,
    if (M_PatientSuffix is not null, M_PatientSuffix, '') ,
    if (M_PatientSuffix is not null, ' ', '') 
    ) PatientName,
    if( lower(M_PatientGender) = 'male', 'M','F') PatientSexCode,
    if( lower(M_PatientGender) = 'male', 'Male','Female') PatientSexName,
    replace(M_PatientDOB,'-','/') PatientDOB,
    'CpOne Address' PatientAdress,
    T_OrderHeaderLabNumber OrderNumber,
    replace(T_OrderHeaderDate,'-','/') OrderDateTime,
    'CpOne001' DoctorOrderCode ,
    'Dr CpOne' DoctorOrderName , 
    M_BranchCode GuarantorID,
    M_BranchName GuarantorName,
    'Cp001' AgreementID,
    'CpOne' AgreementName,
    $s_flag ReceivedFlag,
    null LabRegNo,
    null ReceivedDateTime
    from t_orderheader 
    join m_branch 
    on T_OrderHeaderDate >= ?
    and T_OrderHeaderDate <= ?
    and T_OrderHeaderIsActive = 'Y'
    and T_OrderHeaderM_BranchID = M_BranchID
    and M_BranchIsActive = 'Y'
    and T_OrderHeaderID $w_flag (
    select order_DownloadT_OrderHeaderID 
    from 
    order_download 
    where order_DownloadT_OrderHeaderDate >= ? 
    and order_DownloadT_OrderHeaderDate <= ?  
    )
    join mcu_preregister_patients on Mcu_PreregisterPatientsT_OrderHeaderID = T_OrderHeaderID 
    and Mcu_PreregisterPatientsIsActive = 'Y'
    join corporate on T_OrderHeaderCorporateID = CorporateID
    and CorporateIsActive = 'Y'
    join m_patient on T_OrderHeaderM_PatientID = M_PatientID 
    left join m_title on M_PatientM_TitleID = M_TitleID
    order by T_OrderHeaderLabNumber
    ";
    $qry = $this->db->query($sql, [$s_date, $e_date, $s_date, $e_date]);
    if ($branchCode != "") {
      $sql = "select 
    CorporateID,
    CorporateName,
    M_BranchCode BranchCode,
    M_BranchName BranchName,
    M_PatientNoreg PatientCode,
    concat(
    ifnull(M_TitleName,''),
    if(M_TitleName is not null, ' ',''),
    if (M_PatientPrefix is not null, M_PatientPrefix, '') ,
    if (M_PatientPrefix is not null, ' ', '') ,
    M_PatientName,
    if (M_PatientSuffix is not null, M_PatientSuffix, '') ,
    if (M_PatientSuffix is not null, ' ', '') 
    ) PatientName,
    if( lower(M_PatientGender) = 'male', 'M','F') PatientSexCode,
    if( lower(M_PatientGender) = 'male', 'Male','Female') PatientSexName,
    replace(M_PatientDOB,'-','/') PatientDOB,
    'CpOne Address' PatientAdress,
    T_OrderHeaderLabNumber OrderNumber,
    replace(T_OrderHeaderDate,'-','/') OrderDateTime,
    'CpOne001' DoctorOrderCode ,
    'Dr CpOne' DoctorOrderName , 
    M_BranchCode GuarantorID,
    M_BranchName GuarantorName,
    concat('CpOne-',T_OrderHeaderMgm_McuID) AgreementID,
    concat('CpOne-',T_OrderHeaderMgm_McuID) AgreementName,
    $s_flag ReceivedFlag,
    null LabRegNo,
    null ReceivedDateTime
    from t_orderheader 
    join m_branch 
    on T_OrderHeaderDate >= ?
    and T_OrderHeaderDate <= ?
    and T_OrderHeaderIsActive = 'Y'
    and T_OrderHeaderM_BranchID = M_BranchID
    and M_BranchCode = ?
    and M_BranchIsActive = 'Y'
    join mcu_preregister_patients on Mcu_PreregisterPatientsT_OrderHeaderID = T_OrderHeaderID 
    and Mcu_PreregisterPatientsIsActive = 'Y'
    join corporate on T_OrderHeaderCorporateID = CorporateID
    and CorporateIsActive = 'Y'
    and T_OrderHeaderID $w_flag (
    select order_DownloadT_OrderHeaderID 
    from 
    order_download 
    where order_DownloadT_OrderHeaderDate >= ? 
    and order_DownloadT_OrderHeaderDate <= ?  
    )
    join m_patient on T_OrderHeaderM_PatientID = M_PatientID 
    left join m_title on M_PatientM_TitleID = M_TitleID
    order by T_OrderHeaderLabNumber
    ";
      $qry = $this->db->query($sql, [$s_date, $e_date, $branchCode, $s_date, $e_date]);
    }

    if (!$qry) {
      echo json_encode([
        "Status" => [
          "OK" => false, "Code" => 0, "Messages" => "Get Order issue [Order003]",
        ],
        "OrderList" => [],
      ]);
      exit;
    }
    $rows = $qry->result_array();
    foreach ($rows as $idx =>  $r) {
      $rows[$idx]["ReceivedFlag"] = (bool) $receivedFlag;
    }
    echo json_encode([
      "Status" => ["OK" => true, "Code" => -1, "Messages" => "SUCCESS"],
      "OrderList" => $rows
    ]);
  }

  function order()
  {
    $branch_code = $this->auth_token();
    $param = $this->param();
    $order_per_page = 10;
    $sdate = date("Y-m-d 00:00:00");
    $edate = date("Y-m-d 23:59:59");
    if (array_key_exists("date", $param)) {
      $date = $param["date"];
      if (!strtotime($date)) {
        echo json_encode(["status" => "ERR", "message" => "Date param is not valid [Order001]"]);
        exit;
      }
      $sdate = "$date 00:00:00";
      $edate = "$date 23:59:59";
    }
    $page = 1;
    if (array_key_exists("page", $param)) {
      $page = $param["page"];
      if ($page == "") $page = 1;
    }
    $start_offset = ($page - 1) * $order_per_page;
    $sql = "select count(*) as total 
    from t_orderheader 
    where T_OrderHeaderDate >= ? 
    and T_OrderHeaderDate <=? 
    and T_OrderHeaderIsActive = 'Y'
    and T_OrderHeaderID not in (
      select order_DownloadT_OrderHeaderID 
      from order_download where order_DownloadIsActive = 'Y'
    )
    ";
    $qry = $this->db->query($sql, [$sdate, $edate]);

    if (!$qry) {
      echo json_encode(["status" => "ERR", "message" => "Get Order error [Order002]"]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      echo json_encode([
        "status" => "OK", "pages" => 0, "orders" => 0,
        "records" => []
      ]);
      exit;
    }
    $orders = $rows[0]["total"];
    $pages = ceil($orders / $order_per_page);

    $sql = "select T_OrderHeaderID
    from t_orderheader 
    where T_OrderHeaderDate >= ? 
    and T_OrderHeaderDate <= ? 
    and T_OrderHeaderIsActive = 'Y'
    and T_OrderHeaderID not in (
      select order_DownloadT_OrderHeaderID 
      from order_download where order_DownloadIsActive = 'Y'
    )
    limit ?,?";
    $qry = $this->db->query($sql, [
      $sdate, $edate,
      $start_offset, $order_per_page
    ]);
    if (!$qry) {
      echo json_encode(["status" => "ERR", "message" => "Get Order error [Order003]"]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      echo json_encode([
        "status" => "OK", "pages" => 0, "orders" => 0,
        "data" => []
      ]);
      exit;
    }
    $s_ids = "0";
    foreach ($rows as $r) {
      $s_ids .= "," . $r["T_OrderHeaderID"];
    }
    $sql = "select 
    T_OrderHeaderid id,
    T_OrderHeaderDate date,
    T_OrderHeaderLabNumber nolab,
    M_PatientNoreg noreg,
    concat(
    ifnull(M_TitleName,''),
    if(M_TitleName is not null, ' ',''),
    if (M_PatientPrefix is not null, M_PatientPrefix, '') ,
    if (M_PatientPrefix is not null, ' ', '') ,
    M_PatientName,
    if (M_PatientSuffix is not null, M_PatientSuffix, '') ,
    if (M_PatientSuffix is not null, ' ', '') 
    ) patient,
    M_PatientDOB dob,
    M_PatientGender gender,
    M_PatientCitizenship citizenship,
    M_PatientIdentifierCode identifier_code,
    M_PatientIdentifierValue identifier_value,
    Nat_TestMapCode,
    T_TestName
    from t_orderheader 
    join t_orderdetail on 
    T_OrderHeaderID in ( $s_ids )
    and T_OrderHeaderID = T_OrderDetailT_OrderHeaderID
    and T_OrderDetailIsActive = 'Y'
    join m_patient on T_OrderHeaderM_PatientID = M_PatientID 
    join t_test 
    on T_OrderDetailT_TestID = T_TestID 
    and T_TestIsResult = 'Y'
    left join m_title on M_PatientM_TitleID = M_TitleID
    left join nat_testmap 
    on T_TestNat_TestID = Nat_TestMapNat_TestID
    and Nat_TestMapIsActive = 'Y'  
    order by T_OrderHeaderLabNumber, Nat_TestMapCode
    ";
    $qry = $this->db->query($sql);
    if (!$qry) {
      echo json_encode(["status" => "ERR", "message" => "Get Order error [Order004]"]);
      exit;
    }
    $rows = $qry->result_array();
    $response = [
      "status" => "OK",
      "pages" => $pages,
      "orders" => $orders,
      "records" => []
    ];
    $prev_no = "";
    $records_idx = 0;
    foreach ($rows as $r) {
      if ($prev_no != $r["nolab"]) {
        $response["records"][] = [
          "id" => $r["id"],
          "nolab" => $r["nolab"],
          "date" => $r["date"],
          "noreg" => $r["noreg"],
          "patient" => $r["patient"],
          "citizenship" => $r["citizenship"],
          "dob" => $r["dob"],
          "gender" => $r["gender"],
          "identifier_code" => $r["identifier_code"],
          "identifier_value" => $r["identifier_value"],
          "order" => []
        ];
        $records_idx = count($response["records"]) - 1;
      }
      $response["records"][$records_idx]["order"][] = [
        "test_code" => $r["Nat_TestMapCode"],
        "test_name" => $r["T_TestName"]
      ];
      $prev_no = $r["nolab"];
    }
    $this->log($branch_code, "/one-api/order", json_encode($param), json_encode($response));
    echo json_encode($response);
  }

  function log(
    $branch_code,
    $end_point,
    $param,
    $response,
    $type =  "Order",
    $is_rollback =  false
  ) {
    $sql = "insert into cpone_log.log_api(log_ApiM_BranchCode,
    log_ApiEndPoint, log_ApiParam, log_ApiResponse, log_ApiType) values(?,?,?,?,?)";
    $qry = $this->db->query($sql, [$branch_code, $end_point, $param, $response, $type]);
    if (!$qry) {
      // print_r($this->db->error());
      // echo $this->db->last_query();
      echo json_encode(["status" => "ERR", "message" => "System Log error [Log001]"]);
      if ($is_rollback) $this->db->trans_rollback();
      exit;
    }
    return $this->db->insert_id();
  }
  function access_token()
  {
    //auth token 
    //get branchID from token 
    $param = $this->param();
    $client = $param["client"];
    $secret = $param["secret"];
    $sql = "select api_KeyM_BranchCode
    from api_key
    where api_KeyM_BranchCode= ? 
    and api_KeySecretKey = ?
    and api_KeyIsActive='Y'";
    $qry = $this->db->query($sql, [$client, $secret]);
    if (!$qry) {
      echo json_encode(["status" => "ERR", "message" => "Auth Err, no client or secret key [Auth0001]"]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      echo json_encode(["status" => "ERR", "message" => "Auth Err, invalid client or secret key [Auth0002]"]);
      exit;
    }
    $expired = date("Y-m-d H:i:s", strtotime("now + {$this->intervalHour} hour"));
    $auth_param = ["branchCode" => $rows[0]["api_KeyM_BranchCode"], "expired" => $expired];
    $token = JWT::encode($auth_param, $this->SECRET_KEY);
    echo json_encode(["status" => "OK", "token" => $token, "expired" => $expired]);
  }
  function refresh_token()
  {
    $branchCode = $this->auth_token();
    $sql = "select api_KeyM_BranchCode
    from api_key
    where api_KeyM_BranchCode= ? 
    and api_KeyIsActive='Y'";
    $qry = $this->db->query($sql, [$branchCode]);
    if (!$qry) {
      echo $this->db->error()["message"];
      echo json_encode(["status" => "ERR", "message" => "Auth Err, access Token invalid [Auth003]"]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      echo json_encode(["status" => "ERR", "message" => "Auth Err, access Token invalid [Auth0004]"]);
      exit;
    }
    $expired = date("Y-m-d H:i:s", strtotime("now + {$this->intervalHour} hour"));
    $auth_param = ["branchCode" => $rows[0]["api_KeyM_BranchCode"], "expired" => $expired];
    $token = JWT::encode($auth_param, $this->SECRET_KEY);
    echo json_encode(["status" => "OK", "token" => $token, "expired" => $expired]);
  }
  function result()
  {
    $branchCode = $this->auth_token();
    $param = $this->param();
    if (json_last_error() != JSON_ERROR_NONE) {
      $log_ApiID = $this->log($branchCode, "/one-api/result", $this->tmp_body, "", "Result");
      $sql = "update cpone_log set log_ApiIsParsed = 'E', log_ApiParam =? where log_ApiID=?";
      $qry = $this->db->query($sql, [$this->tmp_body, $log_ApiID]);
      if (!$qry) {
        echo $this->db->error()["message"];
        echo json_encode(["status" => "ERR", "message" => "Invalid Json Param [Result001]"]);
        exit;
      }
      echo json_encode(["status" => "ERR", "message" => "Invalid Json Param [Result002]"]);
      exit;
    }
    // insert to log_api 
    $log_ApiID = $this->log($branchCode, "/one-api/result", $this->tmp_body, "", "Result");
    //parsed param 
    // partial commit
    $this->db->trans_begin();

    $sql_i = "insert into api_result(api_ResultDate,api_ResultNolab,
    api_ResultNoreg, api_ResultPatient, api_ResultTestCode, api_ResultTestName,
    api_ResultResult, api_ResultFlag, api_ResultUnit, api_ResultIsQuantitative,
    api_ResultAbnormalText, api_ResultNormalText, api_ResultNormalMethode, 
    api_ResultNormalMinValue, api_ResultNormalMinInclusive,
    api_ResultNormalMaxValue, api_ResultNormalMaxInclusive,
    api_ResultM_BranchCode, api_ResultLog_ApiID ) 
    values ( ?,?,  ?,?,?,?, ?,?,?,?, ?,?,?, ?,?, ?,?, ?,?)";

    $date = $param["date"];
    $noreg = $param["noreg"];
    $nolab = $param["nolab"];
    $patient = $param["patient"];
    foreach ($param["result"] as $r) {
      $abnormal = $r["abnormal"];
      $flag = $r["flag"];
      $is_quantitative = $r["is_quantitative"];
      $result = $r["result"];
      $test_code = $r["test_code"];
      $test_name = $r["test_name"];
      $unit = $r["unit"];
      $description = $r["ref_range"]["description"];
      $methode = $r["ref_range"]["methode"];
      $min_comparator = $r["ref_range"]["min"]["comparator"];

      $min_value = $r["ref_range"]["min"]["value"];
      $max_value = $r["ref_range"]["max"]["value"];
      $max_comparator = $r["ref_range"]["max"]["comparator"];


      $min_value_inclusive = "N";
      if ($min_comparator == ">=") $min_value_inclusive = "Y";
      $max_value_inclusive = "N";
      if ($max_comparator == ">=") $max_value_inclusive = "Y";
      $qry = $this->db->query($sql_i, [
        $date, $nolab,
        $noreg, $patient, $test_code, $test_name,
        $result, $flag, $unit, $is_quantitative,
        $abnormal, $description, $methode,
        $min_value, $min_value_inclusive,
        $max_value, $max_value_inclusive,
        $branchCode, $log_ApiID
      ]);
      if (!$qry) {
        // print_r($this->db->error());
        // echo $this->db->last_query();
        echo json_encode(["status" => "ERR", "message" => "Error Api_Result $test_code [Result003]"]);
        $this->db->trans_rollback();
        exit;
      }
    }
    $this->db->trans_commit();
    $this->parse($log_ApiID);
    echo json_encode(["status" => "OK", "message" => "$nolab received"]);
  }
  function get_header_id($nolab)
  {
    $sql = "select * from t_orderheader 
    where T_OrderHeaderLabNumber = ?
    and T_OrderHeaderIsActive = 'Y'";
    $qry = $this->db->query($sql, [$nolab]);
    if (!$qry) {
      // print_r($this->db->error());
      // echo $this->db->last_query();
      // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Header [Result004]"]);
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Invalid JSON Param [Result001]"],
        "OrderList" => [],
      ]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      return 0;
    }
    return $rows[0]["T_OrderHeaderID"];
  }
  function get_detail_id($header_id, $test_code)
  {
    $sql = "select 
    T_OrderDetailID
    from t_orderdetail
    join t_test on T_OrderDetailT_OrderHeaderID = ?
    and T_OrderDetailT_TestID = T_TestID 
    and T_OrderDetailIsActive = 'Y'
    join nat_testmap on T_TestNat_TestID = Nat_TestMapNat_TestID
    and Nat_TestMapIsActive = 'Y'
    and Nat_TestMapCode = ?";
    $qry = $this->db->query($sql, [$header_id, $test_code]);
    if (!$qry) {
      // echo $this->db->last_query();
      // print_r($this->db->error());
      // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Detail O2 [Result007]"]);
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Invalid JSON Param [Result001]"],
        "OrderList" => [],
      ]);
      exit;
    }
    $rows = $qry->result_array();
    if (count($rows) == 0) {
      return 0;
    }
    return $rows[0]["T_OrderDetailID"];
  }

  function send_order($params)
  {

    

    // URL endpoint yang ingin dikirimkan POST request
    $url = 'http://10.9.10.207:23381/PushOrder';

    // Inisialisasi CURL
    $ch = curl_init($url);

    // Mengatur CURL untuk mengirim POST request dengan JSON
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

    // Eksekusi CURL dan mendapatkan respons dari server
    $response = curl_exec($ch);

    // Cek apakah ada error saat eksekusi CURL
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    // Tutup CURL
    curl_close($ch);

    // Tampilkan respons dari server
    $rst = json_decode($response);
    //print_r($rst);
    //echo $rst->message;
    //exit;
    $rst_save = stripslashes(json_encode($rst));
    $res = json_encode($response);
    $save_param = $params;
    $nolab_lis = $rst->orderNumber?$rst->orderNumber:"Kosong";

    $sql = "INSERT INTO api_push (
      Api_PushT_OrderHeaderLabNumber,
      Api_PushParams,
      Api_PushResponse,
      Api_PushRetry,
      Api_PushLISLabNumber,
      Api_PushStatus,
      Api_PushCreated
  )
  VALUES(?,?,?,?,?,?,NOW())
  ON DUPLICATE KEY
    UPDATE Api_PushParams = ?, Api_PushResponse = ?, Api_PushRetry = Api_PushRetry + 1, Api_PushStatus = ?";
  $qry = $this->db->query($sql, [$params['OrderNumber'], stripslashes(json_encode($save_param)), $rst_save, 1, $nolab_lis, $rst->message,stripslashes(json_encode($save_param)), $rst_save, $rst->message]);
  //echo   $this->db->last_query();
  //exit;
  return true;
  }


  function parse($logApiID)
  {
    $sql = "select *
    from api_result where api_ResultLog_ApiID = ?
    and api_ResultIsParsed = 'N'";
    $qry = $this->db->query($sql, [$logApiID]);
    if (!$qry) {
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse001]"],
        "OrderList" => [],
      ]);
      exit;
    }
    $rows = $qry->result_array();
    $arr_header_id = [];

    $sql_u = "update api_result set api_ResultIsParsed = ?, 
    api_ResultT_OrderDetailID = ? where api_ResultID = ?";
    $sql_r = "update t_orderdetail set 
    T_OrderDetailResult = ?, T_OrderDetailNat_UnitName = ?,
    T_OrderDetailResultFlag = ?, T_OrderDetailNormalValueNote = ?, T_OrderDetailNormalValueDescription= ?, T_OrderdetailNat_MethodeName = ?,
    T_OrderDetailMinValue = ?, T_OrderDetailMinValueInclusive = ?, 
    T_OrderDetailMaxValue = ?, T_OrderDetailMaxValueInclusive = ?, T_OrderDetailNote = ?
    where T_OrderDetailID = ? 
  ";
    foreach ($rows as $r) {
      $nolab = $r["api_ResultNolab"];
      $id = $r["api_ResultID"];
      if (array_key_exists($nolab, $arr_header_id)) {
        $header_id = $arr_header_id[$nolab];
      } else {
        $header_id = $this->get_header_id($nolab);
        if ($header_id != 0) {
          $arr_header_id[$nolab] = $header_id;
        }
      }
      if ($header_id == 0) {
        $qry = $this->db->query($sql_u, ["O1", 0, $id]);
        if (!$qry) {
          echo json_encode([
            "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse002]"],
            "OrderList" => [],
          ]);
          exit;
        }
        continue;
      }
      $detail_id = $this->get_detail_id($header_id, $r["api_ResultTestCode"]);
      if ($detail_id == 0) {
        $qry = $this->db->query($sql_u, ["O2", 0, $id]);
        if (!$qry) {
          // echo $this->db->last_query();
          // print_r($this->db->error());
          // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Parse O2 [Result007]"]);
          echo json_encode([
            "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse003]"],
            "OrderList" => [],
          ]);
          exit;
        }
        continue;
      }
      $qry = $this->db->query($sql_u, ["H", $detail_id, $id]);
      if (!$qry) {
        // echo $this->db->last_query();
        // print_r($this->db->error());
        // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Parse H [Result008]"]);
        echo json_encode([
          "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse004]"],
          "OrderList" => [],
        ]);
        exit;
      }
      //update result, flag, unit, methode , normal_value,
      $qry = $this->db->query($sql_r, [
        $r["api_ResultResult"], $r["api_ResultUnit"],
        $r["api_ResultFlag"], $r["api_ResultNormalText"], $r["api_ResultNormalNote"], $r["api_ResultNormalMethode"],
        $r["api_ResultNormalMinValue"], $r["api_ResultNormalMinInclusive"],
        $r["api_ResultNormalMaxValue"], $r["api_ResultNormalMaxInclusive"], $r["api_ResultNote"],
        $detail_id
      ]);
      if (!$qry) {
        // echo $this->db->last_query();
        // print_r($this->db->error());
        // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Parse H [Result009]"]);
        echo json_encode([
          "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse005]"],
          "OrderList" => [],
        ]);
        exit;
      }
    }
  }
  function fix_api_result($nolab)
  {
    $sql = "select *
    from api_result where api_ResultNolab= ?
    ";
    $qry = $this->db->query($sql, [$nolab]);
    if (!$qry) {
      echo json_encode([
        "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse001]"],
        "OrderList" => [],
      ]);
      exit;
    }
    $rows = $qry->result_array();
    $arr_header_id = [];

    $sql_u = "update api_result set api_ResultIsParsed = ?, 
    api_ResultT_OrderDetailID = ? where api_ResultID = ?";
    $sql_r = "update t_orderdetail set 
    T_OrderDetailResult = ?, T_OrderDetailNat_UnitName = ?,
    T_OrderDetailResultFlag = ?, T_OrderDetailNormalValueNote = ?, T_OrderDetailNormalValueDescription = ?, T_OrderdetailNat_MethodeName = ?,
    T_OrderDetailMinValue = ?, T_OrderDetailMinValueInclusive = ?, 
    T_OrderDetailMaxValue = ?, T_OrderDetailMaxValueInclusive = ?
    where T_OrderDetailID = ? and (
    trim(T_OrderDetailResult) = ''
    or
    T_OrderDetailResult is null
    or 
    true
    )
    ";
    foreach ($rows as $r) {
      $nolab = $r["api_ResultNolab"];
      $id = $r["api_ResultID"];
      if (array_key_exists($nolab, $arr_header_id)) {
        $header_id = $arr_header_id[$nolab];
      } else {
        $header_id = $this->get_header_id($nolab);
        if ($header_id != 0) {
          $arr_header_id[$nolab] = $header_id;
        }
      }
      if ($header_id == 0) {
        $qry = $this->db->query($sql_u, ["O1", 0, $id]);
        if (!$qry) {
          echo json_encode([
            "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse002]"],
            "OrderList" => [],
          ]);
          exit;
        }
        continue;
      }
      $detail_id = $this->get_detail_id($header_id, $r["api_ResultTestCode"]);
      if ($detail_id == 0) {
        $qry = $this->db->query($sql_u, ["O2", 0, $id]);
        if (!$qry) {
          // echo $this->db->last_query();
          // print_r($this->db->error());
          // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Parse O2 [Result007]"]);
          echo json_encode([
            "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse003]"],
            "OrderList" => [],
          ]);
          exit;
        }
        continue;
      }
      $qry = $this->db->query($sql_u, ["H", $detail_id, $id]);
      if (!$qry) {
        // echo $this->db->last_query();
        // print_r($this->db->error());
        // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Parse H [Result008]"]);
        echo json_encode([
          "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse004]"],
          "OrderList" => [],
        ]);
        exit;
      }
      //update result, flag, unit, methode , normal_value,
      $qry = $this->db->query($sql_r, [
        $r["api_ResultResult"], $r["api_ResultUnit"],
        $r["api_ResultFlag"], $r["api_ResultNormalText"], $r["api_ResultNormalNote"], $r["api_ResultNormalMethode"],
        $r["api_ResultNormalMinValue"], $r["api_ResultNormalMinInclusive"],
        $r["api_ResultNormalMaxValue"], $r["api_ResultNormalMaxInclusive"],
        $detail_id
      ]);
      if (!$qry) {
        // echo $this->db->last_query();
        // print_r($this->db->error());
        // echo json_encode(["status" => "ERR", "message" => "Error Api_Result Parse H [Result009]"]);
        echo json_encode([
          "Status" => ["OK" => false, "Code" => 0, "Messages" => "Error LabResult [Parse005]"],
          "OrderList" => [],
        ]);
        exit;
      }
    }
  }
  function sample_abnormal($id)
  {
    $sql = "select
    Mcu_ResumeDetailsT_TestID test_id,
    Mcu_ResumeDetailsResult result
    from mcu_resume
    join mcu_resumedetails on Mcu_ResumeT_OrderHeaderID = ?
    and Mcu_ResumeIsActive = 'Y' 
    and Mcu_ResumeID = Mcu_ResumeDetailsMcu_ResumeID
    and Mcu_ResumeDetailsIsActive = 'Y'
    ";
    $qry = $this->db->query($sql, [$id]);
    if (!$qry) {
      print_r($this->db->error());
      echo "\n " . $this->db->last_query();
      return [];
    }
    $rows = $qry->result_array();
    $result = [];
    foreach ($rows as $r) {
      $id = $r["test_id"];
      $result[$id] = $r["result"];
    }
    return $result;
  }

  function sample_order($id)
  {
    $sql = "select 
    M_PatientNoreg noreg, 
    M_PatientName patient,
    T_OrderHeaderLabNumber nolab,
    T_OrderHeaderDate date,
    T_OrderDetailT_TestID test_id,
    T_OrderDetailT_TestCode test_code,
    T_OrderDetailT_TestName test_name,
    T_OrderDetailResult result,
    T_OrderDetailResultFlag flag,
    ifnull(T_OrderDetailNat_UnitName,'') unit,
    T_OrderDetailVerDate verif_date,
    T_OrderDetailValDate valid_date,
    T_OrderDetailNormalValueNote normal_value,
    T_OrderDetailNormalValueDescription normal_value_desc,
    T_OrderDetailMinValueInclusive min_inclusive,
    T_OrderDetailMinValue min_value,
    T_OrderDetailMaxValueInclusive max_inclusive,
    T_OrderDetailMaxValue max_value,
    T_OrderdetailNat_MethodeName methode_name,
    T_TestIsQuantitative is_quantitative
    from 
    t_orderheader
    join m_patient on T_OrderHeaderM_PatientID = M_PatientID
    and T_OrderHeaderIsActive = 'Y'
    and T_OrderHeaderID = ?
    join t_orderdetail on T_OrderHeaderID = T_OrderDetailT_OrderHeaderID
    and T_OrderDetailT_TestIsResult = 'Y'
    and T_OrderDetailIsActive ='Y'
    join t_test 
    on T_OrderDetailT_TestID = T_TestID";
    $qry = $this->db->query($sql, [$id]);
    if (!$qry) {
      echo $this->db->last_query();
      print_r($this->db->error());
    }
    $rows = $qry->result_array();
    $header = [];
    $details = [];
    $lookup_abnormal = $this->sample_abnormal($id);
    foreach ($rows as $r) {
      if ($header == []) {
        $header = [
          "noreg" => $r["noreg"],
          "patient" => $r["patient"],
          "nolab" => $r["nolab"],
          "date" => $r["date"]
        ];
      }

      $min_comparator = ">";
      if ($r["min_inclusive"] == "Y") {
        $min_comparator = ">=";
      }
      $max_comparator = "<";
      if ($r["max_inclusive"] == "Y") {
        $max_comparator = "<=";
      }
      $ref_range =  [
        "methode" => $r["methode_name"],
        "description" => $r["normal_value"],
        "min" => [
          "comparator" => $min_comparator,
          "value" => $r["min_value"]
        ],
        "max" => [
          "comparator" => $max_comparator,
          "value" => $r["max_value"]
        ]
      ];
      if ($r["is_quantitative"] == "N") {
        $ref_range =  [
          "methode" => $r["methode_name"],
          "description" => $r["normal_value"],
        ];
      }
      $details[] = [
        "test_code" => "W" . $r["test_code"],
        "test_name" => $r["test_name"],
        "result" => $r["result"],
        "flag" => $r["flag"],
        "unit" => $r["unit"],
        "is_quantitative" => $r["is_quantitative"],
        "ref_range" => $ref_range,
        "abnormal" => array_key_exists($r["test_id"], $lookup_abnormal) ? $lookup_abnormal[$r["test_id"]] : ""
      ];
    }
    $result = $header;
    $result["result"] = $details;
    echo json_encode($result);
  }
}
