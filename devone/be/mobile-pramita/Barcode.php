<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Barcode extends CI_Controller
{

   function formulir()
   {
      $orderId = $this->input->get("orderId", true);
      $this->db_onedev = $this->load->database("onedev", true);
      if ($orderId == "") $orderId = "'0'";

      $sql = "select
        DATE_FORMAT(T_OrderHeaderDate, '%d-%m-%Y ')as datereg,
           T_OrderHeaderLabNumber as nolab,
           M_PatientNoReg as noreg,
           CONCAT(M_TitleName,'. ',M_PatientName) AS M_PatientName,
           T_OrderHeaderM_PatientAge as age,
           DATE_FORMAT(M_PatientDOB, '%d-%m-%Y') AS M_PatientDOB,
           (SELECT M_PatientAddressDescription from m_patientaddress  AS p
           WHERE  M_PatientAddressM_PatientID  = M_PatientID
           ORDER BY  M_PatientAddressM_PatientID
           LIMIT 1) AS alamat,
           M_CityName as city,
           CONCAT(M_DoctorPrefix,' ',M_DoctorName,' ',M_DoctorSufix) AS M_DoctorName,
           group_concat(distinct T_TestName) as test ,
           M_SexCode as sexcode, M_CompanyName as patienttype
           from t_orderheader
           join m_patient on T_OrderHeaderM_PatientID  = M_PatientID
           join t_orderdetail on T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
           join t_test on T_OrderDetailT_TestID = T_TestID
           join m_title on M_PatientM_TitleID  = M_TitleID
           left join m_sex ON M_PatientM_SexID = M_SexID  AND M_SexIsActive = 'Y'
           left join m_company ON T_OrderHeaderM_CompanyID = M_CompanyID    AND M_CompanyIsActive = 'Y'
           left join m_mou ON T_OrderHeaderM_MouID = M_MouID   AND M_MouIsActive = 'Y'
           left join m_doctor  ON T_OrderHeaderSenderM_DoctorID =  M_DoctorID  AND  M_DoctorIsActive = 'Y'
           left join  m_patientaddress  on M_PatientAddressM_PatientID = M_PatientID
           left join m_kelurahan on M_PatientAddressM_KelurahanID  = M_KelurahanID
           left join m_district on M_KelurahanM_DistrictID  = M_DistrictID
           left join m_city on M_DistrictM_CityID = M_CityID
           where T_OrderHeaderID   = $orderId
           group by T_OrderHeaderID ";
      $query = $this->db_onedev->query($sql);
      $rows = $query->result_array();
      $data = array();
      if (count($rows) > 0) $data = $rows[0];

      echo json_encode(array(
         "status" => "OK",
         "message" => "",
         "rows" => $data
      ));
   }


   function so_group()
   {
      $orderId = $this->input->get("orderId", true);
      $this->db_onedev = $this->load->database("onedev", true);
      if ($orderId == "") $orderId = "'0'";

      $sql = "select
         DATE_FORMAT(T_OrderHeaderDate, '%d-%m-%Y ')as datereg,
            T_OrderHeaderLabNumber as nolab,
            M_PatientNoReg as noreg,
            CONCAT(M_TitleName,'. ',M_PatientName) AS M_PatientName,
            T_OrderHeaderM_PatientAge as age,
            DATE_FORMAT(M_PatientDOB, '%d-%m-%Y') AS M_PatientDOB,
            (SELECT M_PatientAddressDescription from m_patientaddress  AS p
            WHERE  M_PatientAddressM_PatientID  = M_PatientID
            ORDER BY  M_PatientAddressM_PatientID
            LIMIT 1) AS alamat,
            M_CityName as city,
            CONCAT(M_DoctorPrefix,' ',M_DoctorName,' ',M_DoctorSufix) AS M_DoctorName,
            group_concat(distinct T_TestName separator ', ') as test ,
            M_SexCode as sexcode, M_CompanyName as patienttype
            from t_orderheader
            join m_patient on T_OrderHeaderM_PatientID  = M_PatientID
            join t_orderdetail on T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
            join t_test on T_OrderDetailT_TestID = T_TestID
            join so_resultentry on T_OrderDetailID = So_ResultEntryT_OrderDetailID
            join m_title on M_PatientM_TitleID  = M_TitleID
            left join m_sex ON M_PatientM_SexID = M_SexID  AND M_SexIsActive = 'Y'
            left join m_company ON T_OrderHeaderM_CompanyID = M_CompanyID    AND M_CompanyIsActive = 'Y'
            left join m_mou ON T_OrderHeaderM_MouID = M_MouID   AND M_MouIsActive = 'Y'
            left join m_doctor  ON T_OrderHeaderSenderM_DoctorID =  M_DoctorID  AND  M_DoctorIsActive = 'Y'
            left join  m_patientaddress  on M_PatientAddressM_PatientID = M_PatientID
            left join m_kelurahan on M_PatientAddressM_KelurahanID  = M_KelurahanID
            left join m_district on M_KelurahanM_DistrictID  = M_DistrictID
            left join m_city on M_DistrictM_CityID = M_CityID
            where T_OrderHeaderID   = $orderId and T_TestIsResult ='y' and T_TestIsNonLab <> ''
            group by T_OrderHeaderID ";
      $query = $this->db_onedev->query($sql);
      $rows = $query->result_array();
      $data = array();
      if (count($rows) > 0) $data = $rows[0];

      echo json_encode(array(
         "status" => "OK",
         "message" => "",
         "rows" => $data
      ));
   }
   /*
   //+  "^FT5,200^A0N,23,24^FH\^FD"+ data.city+"^FS"+ "\n"
   //+  "^FT5,175^A0N,23,24^FH\^FD" + data.address + "^FS"+ "\n"
   //+  "^FT3,150^A0N,28,28^FH\^FD"+ data.title+" "+ data.patientName +"^FS"+ "\n"
   //+  "^BY3,3,45^FT22,70^BCN,,Y,N"+ "\n"
   //+  "^FD>;" + xbarcodeId + "^FS"+ "\n"
   //+  "^FT5,225^A0N,23,24^FH\^FD"+ data.patienttype +"^FS"+ "\n"
   //+  "^PQ1,0,1,Y^XZ"+ "\n"
    */
   function so()
   {
      $orderId = $this->input->get("orderId", true);
      $this->db_onedev = $this->load->database("onedev", true);
      if ($orderId == "") $orderId = "'0'";
      // orderId = order detail id
      $sql = "select
      DATE_FORMAT(T_OrderHeaderDate, '%d-%m-%Y ')as datereg,
         T_OrderHeaderLabNumber as nolab,
         M_PatientNoReg as noreg,
         CONCAT(M_TitleName,'. ',M_PatientName) AS M_PatientName,
         T_OrderHeaderM_PatientAge as age,
         DATE_FORMAT(M_PatientDOB, '%d-%m-%Y') AS M_PatientDOB,
         (SELECT M_PatientAddressDescription from m_patientaddress  AS p
         WHERE  M_PatientAddressM_PatientID  = M_PatientID
         ORDER BY  M_PatientAddressM_PatientID
         LIMIT 1) AS alamat,
         M_CityName as city,
         CONCAT(M_DoctorPrefix,' ',M_DoctorName,' ',M_DoctorSufix) AS M_DoctorName,
         T_TestName as test ,
         M_SexCode as sexcode, M_CompanyName as patienttype
         from t_orderheader
         join m_patient on T_OrderHeaderM_PatientID  = M_PatientID
         join t_orderdetail on T_OrderHeaderID = T_OrderDetailT_OrderHeaderID and T_OrderDetailID = $orderId
         join t_test on T_OrderDetailT_TestID = T_TestID
         join so_resultentry on T_OrderDetailID = So_ResultEntryT_OrderDetailID
         join m_title on M_PatientM_TitleID  = M_TitleID
         left join m_sex ON M_PatientM_SexID = M_SexID  AND M_SexIsActive = 'Y'
         left join m_company ON T_OrderHeaderM_CompanyID = M_CompanyID    AND M_CompanyIsActive = 'Y'
         left join m_mou ON T_OrderHeaderM_MouID = M_MouID   AND M_MouIsActive = 'Y'
         left join m_doctor  ON T_OrderHeaderSenderM_DoctorID =  M_DoctorID  AND  M_DoctorIsActive = 'Y'
         left join  m_patientaddress  on M_PatientAddressM_PatientID = M_PatientID
         left join m_kelurahan on M_PatientAddressM_KelurahanID  = M_KelurahanID
         left join m_district on M_KelurahanM_DistrictID  = M_DistrictID
         left join m_city on M_DistrictM_CityID = M_CityID";
      $query = $this->db_onedev->query($sql);
      $rows = $query->result_array();
      echo json_encode(array(
         "status" => "OK",
         "message" => "",
         "rows" => $rows
      ));
   }

   function pk()
   {
      $barcodeId = $this->input->get("barcodeId", true);
      $this->db_onedev = $this->load->database("onedev", true);
      if ($barcodeId == "") $barcodeId = "'0'";
      $a_bcodes = explode(",", $barcodeId);
      $s_bcode = "";
      foreach ($a_bcodes as $b) {
         if ($s_bcode != "") $s_bcode .= ",";
         $s_bcode .= "'$b'";
      }

      $sql = "select
   RIGHT(T_OrderHeaderLabNumber,7 ) T_OrderHeaderLabNumber ,
   T_OrderHeaderLabNumber Full_T_OrderHeaderLabNumber,
   T_SampleTypeName,
   T_BarcodeLabBarcode ,
   concat(M_TitleName, ' ', M_PatientName) M_PatientName,
   M_PatientNoReg, 
   T_OrderHeaderM_PatientAge,
   DATE_FORMAT(T_OrderHeaderDate,'%d/%m/%Y %T') T_OrderHeaderDate

   from t_barcodelab
   join t_sampletype on T_BarcodeLabT_SampleTypeID = T_SampleTypeID
   join t_orderheader on T_BarcodeLabT_OrderHeaderID = T_OrderHeaderID
   join m_patient on T_OrderHeaderM_PatientID = M_PatientID
   join m_title on M_PatientM_TitleID = M_TitleID
   where T_BarcodeLabBarcode in ( $s_bcode ) ";
      $query = $this->db_onedev->query($sql);
      $rows = $query->result_array();
      echo json_encode(array(
         "status" => "OK",
         "message" => "",
         "rows" => $rows
      ));
   }
}
