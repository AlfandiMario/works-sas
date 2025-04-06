<?php
$sql = "SELECT 
            t_orderheader.*, 
            DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
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
                                JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
                                JOIN corporate ON T_OrderHeaderCorporateID = CorporateID
                                JOIN t_ordersample ON T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND 
                                    T_OrderSampleT_SampleStationID = 1 AND 
                                    T_OrderSampleIsActive = 'Y'
                                JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderSampleT_OrderHeaderID AND 
                                T_OrderLocationT_SampleStationID = T_OrderSampleT_SampleStationID AND
                                T_OrderLocationIsActive = 'Y'
                                JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
                                JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
                                LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND T_SamplingSoT_TestID = T_TestID AND T_SamplingSoIsActive = 'Y'	
            WHERE T_OrderHeaderIsActive = 'Y' 
            AND DATE(T_OrderHeaderDate) = '2025-01-28'
        GROUP BY T_OrderHeaderID";
