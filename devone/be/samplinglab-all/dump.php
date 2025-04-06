<?php

$sql = " SELECT t_orderheader.*, 
                DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
                IFNULL(M_PatientPhotoThumb,'') as M_PatientPhotoThumb,
                M_SexName as M_SexName, 
                M_TitleName as M_TitleName, 
                CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
                M_PatientName as M_PatientName,
                M_CompanyName,
                fn_sampling_queue_status_name(T_OrderHeaderID,T_SampleStationID) as status,
                DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
                fn_sampling_queue_status_id(T_OrderHeaderID,T_SampleStationID)  as statusid, T_SampleStationID, T_SampleTypeID,
                -- T_SampleStationID as stationid,
                -- Tambahan
                T_TestID,
                2 as stationid,
                T_OrderPromiseDateTime,

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
                -- Tambahan
                CONCAT(M_DoctorPrefix,M_DoctorPrefix2,' ',M_DoctorName,' ',M_DoctorSufix,M_DoctorSufix2,M_DoctorSufix3) as doctor_sender,
                fn_sampling_queue_status_confirm(T_OrderHeaderID,T_SampleStationID) as status_confirm,
                
                T_OrderLocationID as order_location_id,
                IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as antri_time,
                IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as skip_time

                FROM t_orderheader	
                JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND T_OrderLocationT_SampleStationID = 2  AND T_OrderLocationIsActive = 'Y'
                JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID
                JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID
                JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
                JOIN m_title ON M_PatientM_TitleID = M_TitleID
                JOIN m_sex ON M_PatientM_SexID = M_SexID

                JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
                JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
                JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'

                LEFT JOIN antrian_samplestation ON AntrianSampleStationT_OrderLocationID =T_OrderLocationID AND AntrianSampleStationIsActive = 'Y'
                JOIN m_location ON T_OrderLocationM_LocationID = M_LocationID AND M_LocationID = 6
                LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
                -- JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID AND ( Last_StatusM_StatusID > 3 OR Last_StatusM_StatusID NOT IN (4,6) )
                JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
                JOIN t_ordersample ON  
                        T_OrderSampleT_OrderHeaderID = T_OrderHeaderID AND
                        T_OrderSampleIsActive = 'Y'

                JOIN t_sampletype ON T_SampleTypeID = T_OrderSampleT_SampleTypeID
                JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
                JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = 2

                WHERE T_OrderHeaderIsActive = 'Y' AND ( DATE(T_OrderHeaderAddonIsComingDate) = '2025-01-28' OR DATE(T_OrderHeaderDate) = '2025-01-28' ) AND T_OrderSampleReceive = 'N'
				GROUP BY T_OrderHeaderID
                    ";


$sql = "SELECT * FROM (
SELECT t_orderheader.*,
				DATE_FORMAT(T_OrderHeaderDate,'%d-%m-%Y %H:%i') as order_date,
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as dob,
				m_patient.*, 
				M_SexName, 
				M_TitleName, 
				CONCAT(M_TitleName,' ',M_PatientName) as patient_fullname, 
				M_CompanyName,
				fn_sampling_queue_status_name(T_OrderHeaderID,T_SampleStationID) as status, 
				DATE_FORMAT(M_PatientDOB,'%d-%m-%Y') as patient_dob,
				fn_sampling_queue_status_id(T_OrderHeaderID,T_SampleStationID)  as statusid,
				T_SampleStationID, 
				T_TestID,
				2 as stationid,
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
				T_OrderHeaderAddonIsComing as status_coming,
				T_OrderLocationID as order_location_id,
				IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as antri_time,
				IF(ISNULL(AntrianSampleStationTime),IFNULL(T_OrderHeaderAddonIsComingDate,T_OrderHeaderDate),AntrianSampleStationTime) as skip_time
				
                FROM t_orderheader	
				JOIN t_orderheaderaddon ON T_OrderHeaderAddOnT_OrderHeaderID = T_OrderHeaderID
				JOIN m_company ON T_OrderHeaderM_CompanyID = M_CompanyID 
				JOIN m_patient ON T_OrderHeaderM_PatientID = M_PatientID
				JOIN m_doctor ON T_OrderHeaderSenderM_DoctorID = M_DoctorID
				JOIN m_title ON M_PatientM_TitleID = M_TitleID
				JOIN m_sex ON M_PatientM_SexID = M_SexID
				JOIN t_order_location ON T_OrderLocationT_OrderHeaderID = T_OrderHeaderID AND T_OrderLocationT_SampleStationID = 2  AND T_OrderLocationIsActive = 'Y'
				LEFT JOIN antrian_samplestation ON AntrianSampleStationT_OrderLocationID =T_OrderLocationID AND AntrianSampleStationIsActive = 'Y'
				LEFT JOIN t_orderpromise ON T_OrderPromiseT_OrderHeaderID = T_OrderHeaderID AND T_OrderPromiseIsActive = 'Y'
				JOIN t_orderdetail ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID AND T_OrderDetailIsActive = 'Y'
				JOIN t_test ON T_OrderDetailT_TestID = T_TestID AND T_TestIsResult = 'Y'
				JOIN t_sampletype ON T_TestT_SampleTypeID = T_SampleTypeID
				JOIN t_bahan ON T_SampleTypeT_BahanID = T_BahanID
				JOIN t_samplestation ON T_BahanT_SampleStationID = T_SampleStationID AND T_SampleStationID = 2
				JOIN last_status ON Last_StatusT_OrderHeaderID = T_OrderHeaderID
				LEFT JOIN t_samplingso ON T_SamplingSoT_OrderHeaderID = T_OrderHeaderID AND 
				T_SamplingSoT_TestID = T_TestID AND 
				T_SamplingSoT_SampleStationID = T_SampleStationID AND
				T_SamplingSoIsActive = 'Y'
				
                WHERE T_OrderHeaderIsActive = 'Y' AND ( DATE(T_OrderHeaderAddonIsComingDate) = '2025-01-28' OR DATE(T_OrderHeaderDate) = '2025-01-28' ) 

				GROUP BY T_OrderHeaderID

				HAVING last_status_fo IN (3,5)
                ) x
				ORDER BY T_OrderHeaderIsCito DESC, antri_time ASC
				
";
