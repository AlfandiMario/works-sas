<?php
class Download_sync extends MY_Controller
{
    function __contruct()
    {
        parent::__contruct();
    }
    // curl -o data.gz http://bandungraya.aplikasi.web.id/one-api/pramitalabku/sync
    function index() {
        $this->process();
    }
    function process($force = 0)
    {
        //download nasional data
        // php /home/one/project/one/one-api/index.php pramitalabku download_sync
        $HOST_CODE = "PramitaMobile";
        $this->log("Start Download from Nasional");
        $url = "http://bandungraya.aplikasi.web.id/one-api/pramitalabku/sync";
        if ($force == 1) {
            $url = "http://bandungraya.aplikasi.web.id/one-api/pramitalabku/sync/process/1";
        }
        $jdata = $this->post($url, json_encode(["branchCode"=>$HOST_CODE]));
        $resp = json_decode($jdata, true);
        if ($resp["status"] != "OK") {
            $this->log($resp["message"]);
            exit();
        }
        foreach ($resp["data"] as $table => $rows) {
            switch ($table) {
                case "t_test":
                    $keys = ["T_TestID"];
                    break;
                case "nat_test":
                    $keys = ["Nat_TestID"];
                    break;
                case "nat_ol_description":
                    $keys = ["NatOlDescriptionID"];
                    break;
                case "nat_position":
                    $keys = ["Nat_PositionID"];
                    break;
                case "nat_requirement":
                    $keys = ["Nat_RequirementID"];
                    break;
                case "nat_requirementposition":
                    $keys = ["Nat_RequirementPositionID"];
                    break;
                case "nat_ol_category":
                    $keys = ["Nat_OLCategoryID"];
                    break;
                case "nat_ol_category_test":
                    $keys = ["Nat_OLCategoryTestID"];
                    break;
                case "t_subcategory":
                    $keys = ["T_SubCategoryID"];
                    break;
                case "t_subcategory_test":
                    $keys = ["T_SubcategoryTestID"];
                    break;
                case "m_specialflag":
                    $keys = ["M_SpecialFlagID"];
                    break;
                default:
                    $this->log("Table $table not configured yet!");
                    exit();
            }
            $tot_insert = 0;
            $tot_update = 0;
            if($table != "child_t_subcategory_test" ) {
                $s_ids = "0";
                foreach ($rows as $r) {
                    $state = $this->insert_or_update($table, $r, $keys);
                    if ($state == "U") {
                        $tot_update++;
                    } else {
                        $tot_insert++;
                    }
                    $s_ids .= ", " . $r["T_SubcategoryTestID"];
                }
                if ($table == "t_subcategory_test") {
                    $sql = "delete from t_subcategory_test where T_SubcategoryTestID not in($s_ids)";
                    $qry = $this->db->query($sql);
                    if (!$qry) {
                        $this->log("Error : " . $this->db->error()["message"]);
                    }
                }
                $tot_rows = $tot_insert + $tot_update;
                $this->log(
                    "$table , $tot_insert inserted / $tot_update updated total : $tot_rows"
                );
            }
        }
        $this->log("Start Synch Icon folder from Nasional");
        $this->sync_icon();
    }
    function sync_icon()
    {
        $icon_folder =
            "/home/regional/project/regional/one-media/one-regonline/*";
        $target_folder = "/home/one/project/one/one-media/one-regonline";
        $rsync_cmd = "rsync -avzr -e 'ssh -i /home/one/nasdl_rsa_cert' one@bandungraya.aplikasi.web.id:$icon_folder $target_folder";
        $output = [];
        exec($rsync_cmd, $output, $retval);
        $status = "Error";

        if ($retval == 0) {
            $status = "Success";
        }
        $this->log("\tResult Status : $status");
        foreach ($output as $o) {
            if (trim($o) == "") {
                continue;
            }
            $this->log("\t $o");
        }
    }
    function post($url, $data, $timeout = 60, $c_timeout = 5)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $c_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Content-Length: " . strlen($data),
        ]);
        $result = curl_exec($ch);
        $err_msg = curl_error($ch);
        if ($err_msg != "") {
            $this->log("Err Post " . $err_msg);
            exit;
        }
        return gzuncompress($result);
    }

    function insert_or_update($table, $data, $key)
    {
        $s_where = "";
        foreach ($key as $k) {
            if ($s_where != "") {
                $s_where .= " and ";
            }
            $s_where .= " $k = '" . $data[$k] . "' ";
        }
        $this->db->trans_begin();
        $sql = "select " . $key[0] . " from $table " . " where $s_where ";
        $qry = $this->db->query($sql, $key);
        if (!$qry) {
            $this->log(
                "Error Insert/Update $table Check | " .
                    $this->db->error()["message"] .
                    "\n" .
                    $this->db->last_query()
            );
            $this->db->trans_rollback();
            exit();
        }
        $rows = $qry->result_array();
        if (count($rows) > 0) {
            foreach ($key as $k) {
                $this->db->where($k, $data[$k]);
            }
            $qry = $this->db->update($table, $data);
            $state = "U";
        } else {
            $qry = $this->db->insert($table, $data);
            $state = "I";
        }
        if (!$qry) {
            $this->log(
                "Error Insert/Update $table | " .
                    $this->db->error()["message"] .
                    "\n" .
                    $this->db->last_query()
            );
            $this->db->trans_rollback();
            exit();
        }
        $this->db->trans_commit();
        return $state;
    }

    function log($message)
    {
        echo date("Y-m-d H:i:s ") . $message . "\n";
    }
}
?>
