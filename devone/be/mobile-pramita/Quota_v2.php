<?php
defined("BASEPATH") or exit("No direct script access allowed");
class Quota_v2
{
    public function __construct()
    {
        $CI = &get_instance();
        $this->db = $CI->load->database("onelite", true);
        $this->debug = false;
    }

    //sudah tidak dipakai langsung query
    function search_param($param, $key, $default)
    {
        if (!isset($param[$key])) {
            return $default;
        }
        return $param[$key];
    }

    public function get_branch_order(
        $pBranchID,
        $pSubCategoryID,
        $pCitoInDay,
        $pDateTime
    ) {
        if ($pCitoInDay == 0) {
            $pDate = date("Y-m-d", strtotime($pDateTime));
            $sql = "select count(*)  sumBranchOrder
         from branch_order 
         join t_testattribute 
            on branch_OrderM_BranchID = ?
            and branch_OrderIsActive = 'Y'
            and branch_OrderCitoInDay = ?
            and date(branch_OrderT_OrderHeaderDate) = ?
            and branch_OrderT_TestID = T_TestAttributeT_TestID 
            and T_TestAttributeT_SubCategoryID = ?";
            $qry = $this->db->query($sql, [
                $pBranchID,
                $pCitoInDay,
                $pDate,
                $pSubCategoryID,
            ]);
            if (!$qry) {
                return [
                    "result" => 0,
                    "status" => "ERR",
                    "message" => $this->db->error()["message"],
                ];
            }
            $rows = $qry->result_array();
            if (count($rows) == 0) {
                return [
                    "result" => 0,
                    "status" => "OK",
                    "message" => "No Branch Order",
                ];
            }
            return [
                "result" => $rows[0]["sumBranchOrder"],
                "status" => "OK",
                "message" => "",
            ];
        }
        // Cito  2 hari
        if ($pCitoInDay == 1) {
            $pDate = date("Y-m-d", strtotime($pDateTime));
            $result = $this->get_regional_id($pBranchID);
            $pRegionalID = $result["result"];
            $msg = "";
            if ($result["status"] != "OK") {
                $msg = $result["message"];
            }

            $sql = "select count(*)  sumBranchOrder
         from branch_order 
         join m_branch on branch_OrderM_BranchID = M_BranchID 
            and M_BranchS_RegionalID = ?
            and M_BranchIsActive = 'Y'
            and branch_OrderIsActive = 'Y'
            and branch_OrderValidation = 'N'
            and branch_OrderCitoInDay = 1
            and date(branch_OrderT_OrderHeaderDate) = ?
         join t_testattribute 
            on branch_OrderT_TestID = T_TestAttributeT_TestID 
            and T_TestAttributeT_SubCategoryID = ?";
            $qry = $this->db->query($sql, [
                $pRegionalID,
                $pDate,
                $pSubCategoryID,
            ]);
            if (!$qry) {
                return [
                    "result" => 0,
                    "status" => "ERR",
                    "message" => $msg . " | " . $this->db->error()["message"],
                ];
            }
            $rows = $qry->result_array();
            $totalBranchOrderNextDay = $rows[0]["sumBranchOrder"];
            return [
                "result" => $totalBranchOrderNextDay,
                "status" => "OK",
                "message" => "",
            ];
        }
        // Reguler
        if ($pCitoInDay == 2) {
            $pDate = date("Y-m-d", strtotime($pDateTime));

            $sql = "select count(*)  sumBranchOrder
         from branch_order 
         join m_branch on branch_OrderM_BranchID = M_BranchID 
            and M_BranchIsActive = 'Y'
            and branch_OrderIsActive = 'Y'
            and branch_OrderValidation = 'N'
            and branch_OrderCitoInDay = 2
            and date(branch_OrderT_OrderHeaderDate) = ?
         join t_testattribute 
            on branch_OrderT_TestID = T_TestAttributeT_TestID 
            and T_TestAttributeT_SubCategoryID = ?";
            $qry = $this->db->query($sql, [$pDate, $pSubCategoryID]);
            if (!$qry) {
                return [
                    "result" => 0,
                    "status" => "ERR",
                    "message" => $this->db->error()["message"],
                ];
            }
            $rows = $qry->result_array();
            $totalOrder = $rows[0]["sumBranchOrder"];
            return ["result" => $totalOrder, "status" => "OK", "message" => ""];
        }
    }

    function get_subcategory($testID)
    {
        $sql = "select T_SubCategoryTestT_SubCategoryID
        from t_subcategory_test
        where T_SubCategoryTestIsActive ='Y'
        and T_SubCategoryTestT_TestID = ?";
        $qry = $this->db->query($sql, [$testID]);
        if (!$qry) {
            echo json_encode([
                "status" => "ERR",
                "message" =>
                    "Error get T_SubCategory | " .
                    $this->db->last_query() .
                    " => " .
                    $this->db->error()["message"],
                "rows" => [],
            ]);
            exit();
        }
        $rows = $qry->result_array();
        if (count($rows) == 0) {
            return 0;
        }
        return $rows[0]["T_SubCategoryTestT_SubCategoryID"];
    }
    public function get_available_test_v2(
        $branchID,
        $testID,
        $dateTime,
        $isCito = "N",
        $isDebug = 0
    ) {
        //check S_ConfigIsNoQuota
        $sql = "select S_ConfigIsNoQuota from s_config limit 0,1";
        $qry = $this->db->query($sql);
        if ($qry) {
            $rows = $qry->result_array();
            if (count($rows) > 0) {
                if ($rows[0]["S_ConfigIsNoQuota"] == "Y") {
                    return [
                        "available" => 0,
                        "message" => "S_ConfigIsNoQuota = Y",
                    ];
                }
            }
        }
        $now = date("Y-m-d H:i:s");
        $date = date("Y-m-d", strtotime($dateTime));
        $res_quota = $this->get_quota($branchID, $testID, $date, $isCito);
        if ($isDebug == 1) {
            print_r($res_quota);
        }
        $hour = date("H:i", strtotime($dateTime));
        if (!isset($res_quota[$hour])) {
            return ["available" => 0, "message" => "No Quota at $hour"];
        } else {
            return [
                "available" => $res_quota[$hour],
                "message" => "Quota available at $hour : " . $res_quota[$hour],
            ];
        }
    }

    function get_quota(
        $branchID,
        $testID,
        $date = "",
        $isCito = "N",
        $debug = false
    ) {
        if ($date == "") {
            $date = date("Y-m-d");
        }
        //1 = Monday | Senin

        $dow = date("N", strtotime($date));
        $resp = $this->get_kunjungan($branchID, $date, $debug);
        if ($resp["status"] == "OK") {
            $max_quota_per_hour = $resp["data"];
        }
        if ($debug) {
            $disp = [];
            $disp[] = ["note" => "MaxQuota Per Hour"];
            $disp[] = ["note" => print_r($max_quota_per_hour, true)];
            $this->print_table($disp, array_keys($disp[0]));
        }
        $subCategoryID = $this->get_subcategory($testID);

        $sql = "select count(*)  as total,
          group_concat(M_RegDayName) as schedDay
          from m_reg_schedule 
          join m_reg_day on M_RegScheduleM_RegDayID = M_RegDayID
          where  
            M_RegScheduleT_SubCategoryID = ? 
            and M_RegScheduleM_BranchID = ?
            and M_RegScheduleIsActive = 'Y'";
        $qry = $this->db->query($sql, [$subCategoryID, $branchID]);
        if (!$qry) {
            echo json_encode([
                "status" => "ERR",
                "message" =>
                    "Error check have schedule | " .
                    $this->db->last_query() .
                    " => " .
                    $this->db->error()["message"],
                "rows" => [],
            ]);
            exit();
        }
        $rows = $qry->result_array();
        $flag_have_schedule = false;
        $schedule_days = "";
        if (count($rows) > 0 && intval($rows[0]["total"]) > 0) {
            $flag_have_schedule = true;
            $schedule_days = $rows[0]["schedDay"];
        }
        if ($debug) {
            echo "<h5>Schedule Days | have Schedule : " .
                ($flag_have_schedule ? "Y" : "N") .
                "</h5>";
            $this->print_table_style();
            $this->print_table($rows, array_keys($rows[0]));
        }
        //get schedule
        $max_quota_per_hour_step1 = $max_quota_per_hour;
        $dbg_schedule_time = [];
        if ($flag_have_schedule) {
            //default schedule limit
            $sql = "select distinct
            M_RegTimeName, M_RegScheduleLimit
            from m_reg_schedule 
            join m_reg_scheduledetail 
              on M_RegScheduleIsActive = 'Y'
               and M_RegScheduleT_SubCategoryID = ?
               and M_RegScheduleDetailIsActive = 'Y'
               and M_RegScheduleM_RegDayID = ?
               and M_RegScheduleID = M_RegScheduleDetailM_RegScheduleID
               and M_RegScheduleM_BranchID = ?
               and M_RegScheduleDetailM_BranchID = ?
            left join m_reg_time 
              on M_RegScheduleDetailM_RegTimeID = M_RegTimeID
              and M_RegTimeIsActive = 'Y'
            ";
            $qry = $this->db->query($sql, [
                $subCategoryID,
                $dow,
                $branchID,
                $branchID,
            ]);
            if (!$qry) {
                echo json_encode([
                    "status" => "ERR",
                    "message" =>
                        "Error get schedule | " .
                        $this->db->last_query() .
                        " => " .
                        $this->db->error()["message"],
                    "rows" => [],
                ]);
                exit();
            }
            $rows = $qry->result_array();
            if ($debug) {
                $disp = [];
                $disp[] = ["query" => "Schedule Time"];
                $disp[] = ["query" => $this->db->last_query()];
                $this->print_table($disp, array_keys($disp[0]));
                $this->print_table($rows, array_keys($rows[0]));
            }
            $schedule_time = [];
            foreach ($rows as $r) {
                $time = $r["M_RegTimeName"];
                $schedule_time[] = $time;
                $quota = $r["M_RegScheduleLimit"];
                $dbg_schedule_time[$time] = $quota;
                if ($quota == "") {
                    $quota = 0;
                }
                if (isset($max_quota_per_hour[$time])) {
                    if ($max_quota_per_hour[$time] > $quota) {
                        $max_quota_per_hour[$time] = $quota;
                    }
                } else {
                    $max_quota_per_hour[$time] = 0;
                }
                if ($debug) {
                    echo "$time => Max Quota Per Hour : " .
                        $max_quota_per_hour["$time"] .
                        "</br>";
                }
            }
            if (count($schedule_time) > 0) {
                foreach ($max_quota_per_hour as $k => $v) {
                    if (!in_array($k, $schedule_time)) {
                        $max_quota_per_hour[$k] = 0;
                        if ($debug) {
                            echo "Zero Schedule : $k <br/>";
                        }
                    }
                }
            }
            if ($debug) {
                $disp = [];
                $disp[] = ["Debug" => "Max Quota per Hour"];
                $disp[] = ["Debug" => print_r($max_quota_per_hour, true)];
                $this->print_table($disp, array_keys($disp[0]));
            }
        }
        // get default quota
        $sql = "select M_RegKuotaCito , M_RegKuotaReguler
          from m_reg_kuota
          where M_RegKuotaT_SubCategoryID = ?
          and M_RegKuotaIsActive = 'Y'
          and M_RegKuotaM_BranchID = ?";
        $qry = $this->db->query($sql, [$subCategoryID, $branchID]);
        if (!$qry) {
            echo json_encode([
                "status" => "ERR",
                "message" =>
                    "Error get reg quota | " .
                    $this->db->last_query() .
                    " => " .
                    $this->db->error()["message"],
                "rows" => [],
            ]);
            exit();
        }
        $rows = $qry->result_array();
        $flag_have_quota = false;
        $quota_cito = 0;
        $quota_reguler = 0;
        if (count($rows) > 0) {
            $flag_have_quota = true;
            $quota_cito = $rows[0]["M_RegKuotaCito"];
            $quota_reguler = $rows[0]["M_RegKuotaReguler"];
        }
        if ($debug) {
            $disp = [];
            $disp[] = ["Debug" => "Default Quota"];
            $this->print_table($disp, array_keys($disp[0]));
            $this->print_table($rows, array_keys($rows[0]));
        }
        if ($flag_have_quota) {
            $sql = "select 
            M_RegKuotaLogCito, M_RegKuotaLogReguler
            from m_reg_kuota_log
            where M_RegKuotaLogT_SubCategoryID = ?
            and M_RegKuotaLogIsActive = 'Y'
            and M_RegKuotaLogDate = ?
            and M_RegKuotaLogM_BranchID = ?";
            $qry = $this->db->query($sql, [$subCategoryID, $date, $branchID]);
            if (!$qry) {
                echo json_encode([
                    "status" => "ERR",
                    "message" =>
                        "Error get reg quota log | " .
                        $this->db->last_query() .
                        " => " .
                        $this->db->error()["message"],
                    "rows" => [],
                ]);
                exit();
            }
            $rows = $qry->result_array();
            if (count($rows) > 0) {
                $quota_cito = $rows[0]["M_RegKuotaLogCito"];
                $quota_reguler = $rows[0]["M_RegKuotaLogReguler"];
            }
            if ($debug) {
                $disp = [];
                $disp[] = ["Debug" => "Quota on Date $date"];
                $this->print_table($disp, array_keys($disp[0]));
                $this->print_table($rows, array_keys($rows[0]));
            }
        }
        $result = [];
        if ($flag_have_quota) {
            foreach ($max_quota_per_hour as $k => $v) {
                if ($quota > $quota_reguler) {
                    $quota = $quota_reguler;
                }
                if ($isCito == "Y") {
                    if ($quota > $quota_cito) {
                        $quota = $quota_cito;
                    }
                }
                if ($v > 0) {
                    $result[$k] = $quota;
                }
            }
        } else {
            foreach ($max_quota_per_hour as $k => $v) {
                $result[$k] = $v;
            }
        }
        if ($debug) {
            $disp = [];
            $disp[] = ["Debug" => "Result irisan Quota "];
            $disp[] = ["Debug" => print_r($result, true)];
            $this->print_table($disp, array_keys($disp[0]));
        }
        //Get Online Order group by time
        if (count(array_keys($result)) > 0) {
            $resp = $this->get_online_order(
                $subCategoryID,
                $date,
                $isCito = "N"
            );
            if ($debug) {
            }
            unset($result["00:00"]);
            if ($resp["status"] == "OK") {
                foreach ($resp["order"] as $o_k => $o_v) {
                    if (isset($result[$o_k])) {
                        $result[$o_k] = $result[$o_k] - $o_v;
                    }
                }
            }
        }

        $result["00-debug"] = [
            "have_schedulee" => $flag_have_schedule ? "Y" : "N",
            "subCategoryID" => $subCategoryID,
            "have_quota" => $flag_have_quota ? "Y" : "N",
            "schedule" => $schedule_days,
            "schedule_time" => json_encode($dbg_schedule_time),
        ];
        return $result;
    }
    function get_online_order($subCategoryID, $date, $isCito = "N")
    {
        $sql = "select 
        left(T_OrderTime,5) OrderTime, count(distinct T_OrderID) total_order  
        from t_order
        join t_orderdetail on T_OrderID = T_OrderDetailT_OrderID
        and T_OrderIsActive = 'Y' and T_OrderDetailIsActive = 'Y'
        and T_OrderDate = ?
        join t_subcategory_test on T_OrderDetailT_TestID = T_SubCategoryTestT_TestID 
          and T_SubCategoryTestT_SubCategoryID = ?
          and T_SubCategoryTestIsActive = 'Y'";
        $qry = $this->db->query($sql, [$date, $subCategoryID]);
        if (!$qry) {
            return [
                "status" => "ERR",
                "message" =>
                    $this->db->error()["message"] .
                    "|" .
                    $this->db->last_query(),
            ];
        }
        $rows = $qry->result_array();
        $result = [];
        foreach ($rows as $r) {
            $o_time = $r["OrderTime"];
            $o_total = $r["total_order"];
            $result[$o_time] = $o_total;
        }
        // anakan nya
        $sql = "select 
        left(T_OrderTime,5) OrderTime, count(distinct T_OrderID) total_order  
        from t_order
        join t_orderdetail on T_OrderID = T_OrderDetailT_OrderID
        and T_OrderIsActive = 'Y' and T_OrderDetailIsActive = 'Y'
        and T_OrderDate = ?
        join t_test on T_OrderDetailT_TestID = T_TestParentT_TestID
            and T_TestIsActive = 'Y' and T_TestIsResult = 'Y'
        join t_subcategory_test on T_TestID = T_SubCategoryTestT_TestID 
          and T_SubCategoryTestT_SubCategoryID = ?
          and T_SubCategoryTestIsActive = 'Y'";
        $qry = $this->db->query($sql, [$date, $subCategoryID]);
        if (!$qry) {
            return [
                "status" => "ERR",
                "message" =>
                    $this->db->error()["message"] .
                    "|" .
                    $this->db->last_query(),
            ];
        }
        $rows = $qry->result_array();
        $result = [];
        foreach ($rows as $r) {
            $o_time = $r["OrderTime"];
            $o_total = $r["total_order"];
            $result[$o_time] = $o_total;
        }
        return [
            "status" => "OK",
            "order" => $result,
            "sql" => $this->db->last_query(),
        ];
    }

    function is_public_holiday($branchID, $date, $debug = false)
    {
        // from branch public holiday 1st
        $flag_have_branch_holiday = false;
        $sql = "select count(*) as total 
          from branch_public_holiday
          where branchPublicHolidayM_BranchID = ?
          and branchPublicHolidayIsActive = 'Y'";
        $qry = $this->db->query($sql, [$branchID]);
        if ($qry) {
            $rows = $qry->result_array();
            if (count($rows) > 0) {
                if ($rows[0]["total"] > 0) {
                    $flag_have_branch_holiday = true;
                }
            }
        }
        if ($flag_have_branch_holiday) {
            $sql = "select count(*) tot
             from branch_public_holiday 
             where 
             branchPublicHolidayIsActive = 'Y' 
             and branchPublicHolidayM_BranchID = ?
             and branchPublicHolidayDate = ?";
            $qry = $this->db->query($sql, [$branchID, $date]);
            if ($qry) {
                $rows = $qry->result_array();
                if (count($rows) > 0) {
                    if ($rows[0]["tot"] > 0) {
                        return true;
                    }
                }
            }
        } else {
            $sql = "select count(*) tot
            from public_holiday 
            where 
            publicHolidayIsActive = 'Y' 
            and publicHolidayDate = ?";
            $qry = $this->db->query($sql, [$date]);
            if ($qry) {
                $rows = $qry->result_array();
                if (count($rows) > 0) {
                    if ($rows[0]["tot"] > 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    function get_service_hour($branchID, $date, $debug = 0)
    {
        $dow = date("N", strtotime($date));
        $sql = "select * from service_hour_day 
            where serviceHourDayM_BranchID=? and serviceHourDayWeekDay = ?";
        $qry = $this->db->query($sql, [$branchID, $dow]);
        if (!$qry) {
            if ($debug != 0) {
                echo "Err ServiceHour Day: " .
                    $this->db->error()["message"] .
                    "\n";
            }
            return [7, 20];
        }
        $rows = $qry->result_array();
        if (count($rows) > 0) {
            return [
                $rows[0]["serviceHourDayOpen"],
                $rows[0]["serviceHourDayClose"],
            ];
        }
        $sql = "select * from service_hour
            where serviceHourM_BranchID=? ";
        $qry = $this->db->query($sql, [$branchID]);
        if (!$qry) {
            if ($debug != 0) {
                echo "Err Service Hour : " .
                    $this->db->error()["message"] .
                    "\n";
            }
            return [7, 20];
        }
        $rows = $qry->result_array();
        if (count($rows) > 0) {
            return [
                $rows[0]["serviceHourOpen"],
                $rows[0]["serviceHourClose"],
            ];
        }
    }

    function get_kunjungan($branchID, $date, $debug = false)
    {
        //Aditya | Cikditiro
        list($jam_buka,$jam_tutup) = $this->get_service_hour($branchID,$date);
        
        if ($jam_buka == "" ) $jam_buka = 7; 
        if ($jam_tutup == "" ) $jam_tutup = 20; 

        $cabang_buka_minggu = [
            14 => 11,
            10 => 11,
        ];

        $max_kunjungan_per_hour = 100;
        $dow = date("N", strtotime($date));
        // 1 = monday
        $sql = "
         select 
         hour(T_OrderTime) xhour, count(*) as tot
         from t_order
         where T_OrderM_BranchID = ?
         and date(T_OrderDate) = ? 
         group by xhour 
         ";
        $qry = $this->db->query($sql, [$branchID, $date]);
        if (!$qry) {
            return [
                "status" => "ERR",
                "message" =>
                    $this->db->error()["message"] .
                    "|" .
                    $this->db->last_query(),
            ];
        }
        $result = [];
        $max_hour = $jam_tutup;
        if ($dow == 6) {
            if ($jam_tutup > 19) {
                $max_hour = 19;
            }
        }
        if ($dow == 7) {
            $max_hour = 0;
            if (isset($cabang_buka_minggu[$branchID])) {
                $max_hour = $cabang_buka_minggu[$branchID];
            }
        }
        if ($this->is_public_holiday($branchID, $date) === true) {
            $max_hour = 0;
            if ($debug) {
                echo "Have Public Holiday on $date \n";
            }
        }
        for ($i = $jam_buka; $i <= $max_hour; $i++) {
            $idx = strval(sprintf("%02d:00", $i));
            $result["$idx"] = $max_kunjungan_per_hour;
        }
        $rows = $qry->result_array();
        if ($this->is_public_holiday($branchID, $date) === true) {
            $rows = [];
        }
        foreach ($rows as $r) {
            $idx = strval(sprintf("%02d:00", $r["xhour"]));
            $result["$idx"] = $max_kunjungan_per_hour - $r["tot"];
        }
        if ($date == date("Y-m-d")) {
            $cur_hour = date("H:i");
            foreach ($result as $idx => $value) {
                $idx = strval($idx);
                if ($idx <= $cur_hour) {
                    $result["$idx"] = 0;
                }
            }
        }

        if ($date < date("Y-m-d")) {
            foreach ($result as $idx => $value) {
                $result["$idx"] = 0;
            }
        }
        if ($debug) {
            $disp = [];
            $disp[] = [
                "query" => $this->db->last_query(),
                "note" => "Get Kunjungan",
            ];
            $this->print_table($disp, array_keys($disp[0]));
            $disp = [];
            $disp[] = [
                "result" => json_encode($result, JSON_PRETTY_PRINT),
                "note" => "Result",
            ];
            $this->print_table($disp, array_keys($disp[0]));
        }
        return ["status" => "OK", "data" => $result];
    }

    function get_schedule($is_packet, $branchID, $testID, $date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d");
        }
        $x_ids = "-1";
        if ($is_packet == "N") {
            $sql = "select group_concat(ct.T_TestNat_TestID) xNat_Test
         from t_test t
            join t_test ct on ct.T_TestSasCode like concat(t.T_TestSasCode,'%')
            and ct.T_TestIsActive = 'Y' and ct.T_TestIsResult = 'Y'
            and t.T_TestID = ?
         ";
            $qry = $this->db->query($sql, [$testID]);
            if (!$qry) {
                echo [
                    "status" => "ERR",
                    "message" =>
                        "Error get Nat_Test | " . $this->db->last_query(),
                    "rows" => [],
                ];
                exit();
            }
            $rows = $qry->result_array();
            if (count($rows) > 0) {
                if ($rows[0]["xNat_Test"] != "") {
                    $x_ids = $rows[0]["xNat_Test"];
                }
            }
        } else {
            $sql = "select group_concat(ct.T_TestNat_TestID) xNat_Test
         from t_packetdetail
            join t_test t on T_PacketDetailT_TestID = t.T_TestID 
               and T_PacketDetailT_PacketID = ?
               and T_PacketDetailIsActive = 'Y'
            join t_test ct on ct.T_TestSasCode like concat(t.T_TestSasCode,'%')
            and ct.T_TestIsActive = 'Y' and ct.T_TestIsResult = 'Y'
         ";
            $qry = $this->db->query($sql, [$testID]);
            if (!$qry) {
                echo [
                    "status" => "ERR",
                    "message" =>
                        "Error get Nat_Test | " . $this->db->last_query(),
                    "rows" => [],
                ];
                exit();
            }
            $rows = $qry->result_array();
            if (count($rows) > 0) {
                if ($rows[0]["xNat_Test"] != "") {
                    $x_ids = $rows[0]["xNat_Test"];
                }
            }
        }
        $day_of_week = date("N", strtotime($date));
        // get from default
        $sql = "select 
      distinct
      Nat_TestID,
      Nat_TestName,
      Ol_ScheduleSaleM_DayOfWeekID,
      Ol_ScheduleSaleStartHour,
      Ol_ScheduleSaleEndHour,
      Ol_SchdeuleSaleQuotaPerHour
      from ol_schedule_sale
      join nat_test 
      on Nat_TestID = Ol_ScheduleSaleNat_TestID
         and Nat_TestID in ( $x_ids )
         and Ol_ScheduleSaleM_DayOfWeekID = ?
      where 
      Ol_ScheduleSaleIsActive = 'Y'
      and Ol_ScheduleSaleM_BranchID = ?
      order by Ol_ScheduleSaleStartHour
      ";
        $qry = $this->db->query($sql, [$day_of_week, $branchID]);
        /* echo $this->db->last_query() . ";";
         echo "\n\n"; */
        if (!$qry) {
            return [
                "status" => "ERR",
                "message" =>
                    "Error get schedule | " .
                    $this->db->error()["message"] .
                    "|" .
                    $this->db->last_query(),
                "rows" => [],
            ];
        }
        $rows = $qry->result_array();

        $dayID = "-1";
        //1 = Senin

        $result = [];
        $prevNatTestID = 0;
        foreach ($rows as $r) {
            $natTestID = $r["Nat_TestID"];
            $resultIdx = -1;
            $quotaPerHour = $r["Ol_SchdeuleSaleQuotaPerHour"];
            foreach ($result as $idx => $rr) {
                if ($rr["Nat_TestID"] == $r["Nat_TestID"]) {
                    $resultIdx = $idx;
                    break;
                }
            }
            if ($resultIdx == -1) {
                $result[] = [
                    "Nat_TestID" => $r["Nat_TestID"],
                    "Nat_TestName" => $r["Nat_TestName"],
                ];
                $resultIdx = count($result) - 1;
            }
            $result[$resultIdx]["date"] = $date;
            $result[$resultIdx]["time"] = $this->populate_hour(
                $r["Nat_TestID"],
                $date,
                $r["Ol_ScheduleSaleStartHour"],
                $r["Ol_ScheduleSaleEndHour"],
                $quotaPerHour,
                $branchID
            );
        }
        return $result;
    }
    function populate_hour($nat_testId, $date, $start, $end, $quota, $branchID)
    {
        $result = [];
        for ($i = $start; $i < $end - 1; $i++) {
            $real_quota = $this->get_realquota(
                $quota,
                $branchID,
                $nat_testId,
                $date,
                $i,
                $i + 1
            );

            if ($quota < $real_quota) {
                $real_quota = 0;
            }
            $cur_hour = (int) date("G");
            $cur_date = date("Y-m-d");
            //echo "$cur_date : $cur_hour  => $date  : $i\n";
            if ($date == $cur_date && (int) $i < $cur_hour) {
                $real_quota = 0;
            }
            /* 
         $result[] = array(
            "Start" => (int) $i,
            "End"   => (int) $i+1,
            "Quota" => (int) $quota,
            "Used" => (int) $quota - $real_quota,
            "Available" => (int) $real_quota
         ); 
         */
            $hour = sprintf("%02d:00:00", (int) $i);
            $result[] = [
                $hour => [
                    "Quota" => (int) $quota,
                    "Used" => (int) $quota - $real_quota,
                    "Available" => (int) $real_quota,
                ],
            ];
        }
        return $result;
    }

    function get_realquota($quota, $branchID, $nat_testId, $date, $start, $end)
    {
        $start = sprintf("%02d:00:00", $start);
        $end = sprintf("%02d:00:00", $end);
        $sql = "select count(*) total
      from 
      t_orderschedule
      join t_order on T_OrderID = T_OrderScheduleT_OrderID 
      and T_OrderM_BranchID = ?
      where T_OrderScheduleNat_TestID = ? 
      and T_OrderScheduleDate = ?
      and T_OrderScheduleTime >= ?
      and T_OrderScheduleTime <= ? ";
        $qry = $this->db->query($sql, [
            $branchID,
            $nat_testId,
            $date,
            $start,
            $end,
        ]);
        if (!$qry) {
            echo "ERR | " .
                $this->db->error()["message"] .
                " | " .
                $this->db->last_query() .
                "\n";
            return $quota;
        }
        $rows = $qry->result_array();
        return $quota - $rows[0]["total"];
    }

    function check_categoryID($categoryID, $arr_nat_test)
    {
        $s_nat_test = implode(",", $arr_nat_test);
        $sql = "select 
      Nat_OLCategoryTestNat_OLCategoryID
      from 
      nat_ol_category_test
      where 
      Nat_OLCategoryTestNat_TestID in ($s_nat_test)
      and Nat_OLCategoryTestIsActive = 'Y'";
        $qry = $this->db->query($sql);
        if ($qry) {
            $rows = $qry->result_array();
            foreach ($rows as $r) {
                //if ($r["Nat_OLCategoryTestNat_OLCategoryID"] == $categoryID) {
                $r_categoryID = $r["Nat_OLCategoryTestNat_OLCategoryID"];
                echo "checked $r_categoryID => \n";
                if (in_array($r_categoryID, $categoryID)) {
                    return true;
                }
            }
        } else {
            echo "ERR : " .
                $this->db->error()["message"] .
                "|" .
                $this->db->last_query() .
                "\n";
        }
        return false;
    }

    //Tidak dipakai ambil langsung ke table nat_requirement dkk
    //dengan fungsi di samakan bizone
    function get_requirement_testID($testID)
    {
        $sql = "select distinct T_TestNat_TestID Nat_TestID, T_TestCode
      from
      t_test
      where T_TestID = ?";
        $qry = $this->db->query($sql, [$testID]);
        $arr_query = [];
        if (!$qry) {
            return [
                "status" => "ERR",
                "message" =>
                    "Error get requirement | " . $this->db->last_query(),
                "rows" => [],
            ];
        }
        $arr_query[] = $this->db->last_query();
        $rows = $qry->result_array();
        $a_ids = [];
        $sql_where = "";
        //get child nat_test
        foreach ($rows as $r) {
            $a_ids[] = $r["Nat_TestID"];
            if ($sql_where != "") {
                $sql_where .= " OR ";
            }
            $sql_where .=
                "( T_TestCode like '" .
                $r["T_TestCode"] .
                "%' and 
            T_TestCode <> '" .
                $r["T_TestCode"] .
                "' and T_TestIsActive = 'Y' ) ";
        }
        if ($sql_where != "") {
            $sql = "select distinct T_TestNat_TestID
         from t_test
         where $sql_where ";
            $qry = $this->db->query($sql);
            if (!$qry) {
                return [
                    "status" => "ERR",
                    "message" =>
                        "Error get requirement | " . $this->db->last_query(),
                    "rows" => [],
                ];
            }
            $arr_query[] = $this->db->last_query();

            $rows = $qry->result_array();
            foreach ($rows as $r) {
                $natTestID = $r["T_TestNat_TestID"];
                if (!in_array($natTestID, $a_ids)) {
                    $a_ids[] = $natTestID;
                }
            }
        }
        $result = [];
        if (count($a_ids) > 0) {
            $s_ids = implode(",", $a_ids);
            $sql = "
            select 
            distinct Nat_RequirementName, Nat_RequirementIsAllTest
            from 
            nat_requirementposition
            join nat_requirement
               on Nat_RequirementPositionNat_PositionID = 1 
               and Nat_RequirementPositionIsActive = 'Y'
               and Nat_RequirementIsActive = 'Y'
               and Nat_RequirementPositionNat_RequirementID = Nat_RequirementID
            join 
            nat_testrequirement
            on 
            Nat_TestRequirementIsActive = 'Y'
            and Nat_TestRequirementNat_RequirementID = Nat_RequirementID
            and 
            (
               Nat_TestRequirementNat_TestID in ( $s_ids ) 
               or
               Nat_RequirementIsAllTest = 'Y'
            ) 
         ";
            $qry = $this->db->query($sql);
            if (!$qry) {
                return [
                    "status" => "ERR",
                    "message" =>
                        "Error get child requirement | " .
                        $this->db->last_query(),
                    "rows" => [],
                ];
            }
            $arr_query[] = $this->db->last_query();
            $rows = $qry->result_array();
            foreach ($rows as $r) {
                $result[] = $r["Nat_RequirementName"];
            }
        }
        return [
            "status" => "OK",
            "rows" => $result,
            "message" => "",
            "query" => $arr_query,
        ];
    }

    //Tidak dipakai ambil langsung ke table nat_requirement dkk
    //dengan fungsi di samakan bizone
    function get_requirement_packetID($branchID, $packetID)
    {
        $sql = "select distinct Nat_TestID, T_TestCode
      from
      t_test
      join t_packetdetail 
      on T_PacketDetailM_BranchID=?
      and T_PacketDetailT_PacketID = ?
      and T_PacketDetailIsActive = 'Y'
      and T_PacketDetailT_TestID = T_TestID
      join nat_test on T_TestNat_TestID = Nat_TestID";
        $qry = $this->db->query($sql, [$branchID, $packetID]);
        $arr_query = [];
        if (!$qry) {
            return [
                "status" => "ERR",
                "message" =>
                    "Error get requirement | " . $this->db->last_query(),
                "rows" => [],
            ];
        }
        $arr_query[] = $this->db->last_query();
        $rows = $qry->result_array();
        $a_ids = [];
        $sql_where = "";
        //get child nat_test
        foreach ($rows as $r) {
            $a_ids[] = $r["Nat_TestID"];
            if ($sql_where != "") {
                $sql_where .= " OR ";
            }
            $sql_where .=
                "( T_TestCode like '" .
                $r["T_TestCode"] .
                "%' and 
            T_TestCode <> '" .
                $r["T_TestCode"] .
                "' and T_TestIsActive = 'Y' ) ";
        }
        if ($sql_where != "") {
            $sql = "select distinct T_TestNat_TestID
         from t_test
         where $sql_where ";
            $qry = $this->db->query($sql, [$branchID, $packetID]);
            if (!$qry) {
                return [
                    "status" => "ERR",
                    "message" =>
                        "Error get requirement | " . $this->db->last_query(),
                    "rows" => [],
                ];
            }
            $arr_query[] = $this->db->last_query();

            $rows = $qry->result_array();
            foreach ($rows as $r) {
                $natTestID = $r["T_TestNat_TestID"];
                if (!in_array($natTestID, $a_ids)) {
                    $a_ids[] = $natTestID;
                }
            }
        }
        $result = [];
        if (count($a_ids) > 0) {
            $s_ids = implode(",", $a_ids);
            $sql = "
            select 
            distinct Nat_RequirementName, Nat_RequirementIsAllTest
            from 
            nat_requirementposition
            join nat_requirement
               on Nat_RequirementPositionNat_PositionID = 1 
               and Nat_RequirementPositionIsActive = 'Y'
               and Nat_RequirementIsActive = 'Y'
               and Nat_RequirementPositionNat_RequirementID = Nat_RequirementID
            join 
            nat_testrequirement
            on (
               Nat_TestRequirementNat_TestID in ( $s_ids ) 
               and Nat_TestRequirementIsActive
               and Nat_TestRequirementNat_RequirementID = Nat_RequirementID
            ) or Nat_RequirementIsAllTest = 'Y'
         ";
            $qry = $this->db->query($sql);
            if (!$qry) {
                return [
                    "status" => "ERR",
                    "message" =>
                        "Error get child requirement | " .
                        $this->db->last_query(),
                    "rows" => [],
                ];
            }
            $arr_query[] = $this->db->last_query();
            $rows = $qry->result_array();
            foreach ($rows as $r) {
                $result[] = $r["Nat_RequirementName"];
            }
        }
        return [
            "status" => "OK",
            "rows" => $result,
            "message" => "",
            "query" => $arr_query,
        ];
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
    public function print_table($rows, $keys)
    {
        $this->print_table_style();
        echo "<table>";
        echo "<tr>";
        foreach ($keys as $k) {
            echo "<td>$k</td>";
        }
        echo "</tr>\n";
        foreach ($rows as $r) {
            echo "<tr>";
            foreach ($keys as $k) {
                echo "<td>" . $r[$k] . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}
