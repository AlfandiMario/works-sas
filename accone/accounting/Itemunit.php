<?php
class Itemunit extends MY_Controller
{
    var $db;
    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        echo "Api: Training Playground";
        echo "<br>";
    }

    function search()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            $prm = $this->sys_input;
            $sql_data = "";
            $sql_filter = "";
            $search = "";
            if (isset($prm['search'])) {
                $search = trim($prm["search"]);
                if ($search != "") {
                    $search = '%' . $prm['search'] . '%';
                } else {
                    $search = '%%';
                }
            }

            $all = $prm['all'];
            $limit = '';
            if ($all == 'N') {
                $limit = ' LIMIT 10';
            }

            // sort
            $sortBy = $prm['sortBy'];
            $sortStatus = $prm['sortStatus'];
            if ($sortBy) {
                $q_sort = "ORDER BY " . $sortBy . " " . $sortStatus;
            }

            $number_offset = 0;
            $number_limit = 10;
            // $number_limit = 1;
            if ($prm['current_page'] > 0) {
                $number_offset = ($prm['current_page'] - 1) * $number_limit;
            }

            // $number_offset = ($prm['current_page'] - 1) * $number_limit;

            // $sql_filter .= "select count(distinct ItemUnitID, ItemUnitCode,
            // ItemUnitName) 
            // as total
            // from itemunit
            // where ItemUnitIsActive = 'Y'
            // AND (
            //     ItemUnitCode like ?
            //     OR ItemUnitName like ?
            // )";


            $sql_filter .= "
            select count(*) as total from (
            select distinct ItemUnitID, ItemUnitCode,
            ItemUnitName
            from itemunit
            where ItemUnitIsActive = 'Y'
            AND (
                ItemUnitCode like ?
                OR ItemUnitName like ?)
            ) x";

            $qry_filter = $this->db->query($sql_filter, [$search, $search]);
            // echo $this->db->last_query();

            $tot_count = 0;
            $tot_page = 0;
            if ($qry_filter) {
                // $tot_count = $qry_filter->result_array()[0]["total"];
                $tot_count = $qry_filter->row()->total;
                $tot_page = ceil($tot_count / $number_limit);
            } else {
                $this->sys_error_db("itemunit count", $this->db);
                exit;
            }

            $sql_data .= "select 
            distinct ItemUnitID, 
            ItemUnitCode,
            ItemUnitName,
            ItemUnitCode as code, 
            ItemUnitName as name, 
            ItemUnitID as id
            from itemunit
            where ItemUnitIsActive = 'Y'
            AND (
                ItemUnitCode like ?
                OR ItemUnitName like ?
            )
            $q_sort
            limit ? offset ?";

            $qry_data = $this->db->query($sql_data, [
                $search,
                $search,
                $number_limit,
                $number_offset
            ]);

            // var_dump($this->db->last_query());

            if ($qry_data) {
                $rows = $qry_data->result_array();
            } else {
                $this->sys_error_db("itemunit select");
                exit;
            }

            $result = array(
                "total" => $tot_page,
                "total_filter" => count($rows),
                "records" => $rows,
                "qry" => $sql_data
            );

            $this->sys_ok($result);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function add()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            //begin transaction 
            $this->db->trans_begin();

            //# ambil parameter input
            $prm = $this->sys_input;

            $userid = $this->sys_user['M_UserID'];
            // $userid = 3;

            $unit_name_search = "";
            $unit_name = "";
            if (isset($prm['unit_name'])) {
                $unit_name_search = trim($prm["unit_name"]);
                $unit_name = trim($prm['unit_name']);
                if ($unit_name_search != "") {
                    $unit_name_search = $prm['unit_name'];
                }
            }

            $sql_count = "SELECT COUNT(*) as exist 
            FROM itemunit 
            WHERE ItemUnitIsActive = 'Y' 
            AND ItemUnitName = ?";
            $query_count =  $this->db->query($sql_count, [
                $unit_name_search
            ]);

            $last_query_count = $this->db->last_query();

            if (!$query_count) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunit search & count by name");
                exit;
            } else {
                // substring date
                // $date = date('Y');
                // $substring_date = substr($date,-2);

                // var_dump($substring_date);

                $unit_code_generate = "UI";

                $get_count = $query_count->row_array();
                if ($get_count['exist'] == 0) {
                    // call fungsi untuk generate code
                    $sql_generate_code = "select fn_numbering(?) as code";
                    $query_generate_code = $this->db->query(
                        $sql_generate_code,
                        [
                            $unit_code_generate
                        ]
                    );

                    if (!$query_generate_code) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("itemunit call sp");
                        exit;
                    }

                    $get_unit_code = $query_generate_code->row_array();
                    $unit_code = $get_unit_code['code'];

                    // query insert
                    $sql_insert = "INSERT INTO itemunit
                    (
                        ItemUnitCode,
                        ItemUnitName,
                        ItemUnitCreated,
                        ItemUnitLastUpdated,
                        ItemUnitUserID
                    )
                    VALUES (?, ?, now(), now(), ?)";

                    $query_insert = $this->db->query($sql_insert, [
                        $unit_code,
                        $unit_name,
                        $userid
                    ]);

                    if (!$query_insert) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("itemunit insert");
                        exit;
                    }

                    // var_dump($this->db->affected_rows());
                    $insert_id = $this->db->insert_id();
                    // print_r($insert_id);

                    $sql_json_before = "SELECT * 
                    FROM itemunit
                    WHERE ItemUnitIsActive = 'Y'
                    AND ItemUnitID = ?";

                    $qry_json_before = $this->db->query(
                        $sql_json_before,
                        [
                            $insert_id
                        ]
                    );

                    if (!$qry_json_before) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("itemunit select json");
                        exit;
                    }

                    $data_by_id = $qry_json_before->row();

                    $json_after_log = json_encode($data_by_id);

                    // print_r($json_after_log);

                    $sql_insert_log = "INSERT INTO acc_one_log.itemunit_log(
                        ItemUnitLogItemUnitID,
                        ItemUnitLogStatus,
                        ItemUnitLogJSONBefore,
                        ItemUnitLogJSONAfter,
                        ItemUnitLogUserID,
                        ItemUnitLogCreated
                    ) VALUES (
                        ?,
                        'ADD',
                        null,
                        ?,
                        ?,
                        now()
                    )";

                    $qry_insert_log = $this->db->query(
                        $sql_insert_log,
                        [
                            $insert_id,
                            $json_after_log,
                            $userid
                        ]
                    );

                    if (!$qry_insert_log) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("itemunit insert log");
                        exit;
                    }

                    // sukses
                    $this->db->trans_commit();
                    $result = array(
                        "total" => 1,
                        "records" => array("xid" => 0)
                    );
                    $this->sys_ok($result);
                } else {
                    $errors = array();
                    if ($get_count['exist'] != 0) {
                        array_push($errors, array(
                            'field' => 'name',
                            'msg' => 'Nama sudah ada'
                        ));
                    }

                    $insert_id = $this->db->insert_id();
                    // print_r($insert_id);

                    $sql_json_before = "SELECT * 
                    FROM itemunit
                    WHERE ItemUnitIsActive = 'Y'
                    AND ItemUnitID = ?";

                    $qry_json_before = $this->db->query(
                        $sql_json_before,
                        [
                            $insert_id
                        ]
                    );

                    if (!$qry_json_before) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("itemunit select json");
                        exit;
                    }

                    $data_by_id = $qry_json_before->row();

                    $json_after_log = json_encode($data_by_id);

                    // print_r($json_after_log);

                    $sql_insert_log = "INSERT INTO acc_one_log.itemunit_log(
                        ItemUnitLogItemUnitID,
                        ItemUnitLogStatus,
                        ItemUnitLogJSONBefore,
                        ItemUnitLogJSONAfter,
                        ItemUnitLogUserID,
                        ItemUnitLogCreated
                    ) VALUES (
                        ?,
                        'DELETE',
                        null,
                        ?,
                        ?,
                        now()
                    )";

                    $qry_insert_log = $this->db->query(
                        $sql_insert_log,
                        [
                            $insert_id,
                            $json_after_log,
                            $userid
                        ]
                    );

                    if (!$qry_insert_log) {
                        $this->db->trans_rollback();
                        $this->sys_error_db("itemunit insert log");
                        exit;
                    }


                    // sukses
                    $this->db->trans_commit();
                    $result = array(
                        "total" => 1,
                        "records" => array("xid" => 0)
                    );
                    $this->sys_ok($result);
                }
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function edit()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            //begin transaction 
            $this->db->trans_begin();

            //# ambil parameter input
            $prm = $this->sys_input;

            $userid = $this->sys_user['M_UserID'];
            // $userid = 1;
            $id = $prm['id'];

            $unit_name_search = "";
            $unit_name = "";
            if (isset($prm['unit_name'])) {
                $unit_name_search = trim($prm["unit_name"]);
                $unit_name = trim($prm['unit_name']);
                if ($unit_name_search != "") {
                    $unit_name_search = $prm['unit_name'];
                }
            }

            $sql_count = "SELECT COUNT(*) as exist 
            FROM itemunit 
            WHERE ItemUnitIsActive = 'Y' 
            AND ItemUnitName = ?";
            $query_count =  $this->db->query($sql_count, [
                $unit_name_search
            ]);

            $last_query_count = $this->db->last_query();

            if (!$query_count) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunit search & count by name");
                exit;
            } else {
                // substring date
                // $date = date('Y');
                // $substring_date = substr($date,-2);

                // var_dump($substring_date);

                // $unit_code_generate = "UI";

                $get_count = $query_count->row_array();
                // if($get_count['exist'] == 0)
                // {
                // call fungsi untuk generate code
                // $sql_generate_code = "select fn_numbering(?) as code";
                // $query_generate_code = $this->db->query($sql_generate_code,
                // [
                //     $unit_code_generate
                // ]);

                // if(!$query_generate_code){
                //     $this->db->trans_rollback();
                //     $this->sys_error_db("itemunit call sp");
                //     exit;
                // }

                // $get_unit_code = $query_generate_code->row_array();
                // $unit_code = $get_unit_code['code'];

                // json before
                $sql_json_before = "SELECT * 
                    FROM itemunit
                    WHERE ItemUnitIsActive = 'Y'
                    AND ItemUnitID = ?";

                $qry_json_before = $this->db->query(
                    $sql_json_before,
                    [
                        $id
                    ]
                );

                if (!$qry_json_before) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("itemunit select json before");
                    exit;
                }

                $data_before_by_id = $qry_json_before->row();

                $json_before_log = json_encode($data_before_by_id);

                // print_r($json_before_log);

                // query update
                $sql_update = "UPDATE itemunit
                    set
                        ItemUnitName = ?,
                        ItemUnitLastUpdated = now(),
                        ItemUnitUserID = ?
                    WHERE ItemUnitID = ?";

                $query_update = $this->db->query($sql_update, [
                    $unit_name,
                    $userid,
                    $id
                ]);

                if (!$query_update) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("itemunit insert");
                    exit;
                }

                //  print_r($json_before_log);

                // print_r($query_data);

                // json after
                $sql_json_after = "SELECT * 
                    FROM itemunit
                    WHERE ItemUnitIsActive = 'Y'
                    AND ItemUnitID = ?";

                $qry_json_after = $this->db->query(
                    $sql_json_after,
                    [
                        $id
                    ]
                );

                if (!$qry_json_after) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("itemunit select json after");
                    exit;
                }

                $data_after_by_id = $qry_json_after->row();

                $json_after_log = json_encode($data_after_by_id);

                $sql_insert_log = "INSERT INTO acc_one_log.itemunit_log(
                        ItemUnitLogItemUnitID,
                        ItemUnitLogStatus,
                        ItemUnitLogJSONBefore,
                        ItemUnitLogJSONAfter,
                        ItemUnitLogUserID,
                        ItemUnitLogCreated
                    ) VALUES (
                        ?,
                        'EDIT',
                        ?,
                        ?,
                        ?,
                        now()
                    )";

                $qry_insert_log = $this->db->query(
                    $sql_insert_log,
                    [
                        $id,
                        $json_before_log,
                        $json_after_log,
                        $userid
                    ]
                );

                if (!$qry_insert_log) {
                    $this->db->trans_rollback();
                    $this->sys_error_db("itemunit edit log");
                    exit;
                }

                // sukses
                $this->db->trans_commit();
                $result = array(
                    "total" => 1,
                    "records" => array("xid" => 0)
                );
                $this->sys_ok($result);
                // }
                // else
                // {
                //     $errors = array(); 
                //     if($get_count['exist'] != 0){
                //         array_push($errors,array(
                //             'field'=>'name',
                //             'msg'=>'Nama sudah ada'
                //         ));
                //     }

                // $result = array (
                //     "total" => -1,
                //     "errors" => $errors, 
                //     "records" => 0);
                // $this->sys_ok($result);
                // }
            }
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }

    function delete()
    {
        try {
            //# cek token valid
            if (! $this->isLogin) {
                $this->sys_error("Invalid Token");
                exit;
            }

            //begin transaction 
            $this->db->trans_begin();

            //# ambil parameter input
            $prm = $this->sys_input;
            $id = $prm['id'];
            $userid = $this->sys_user['M_UserID'];
            // $userid = 1;

            $sql_delete = "UPDATE itemunit 
            SET ItemUnitIsActive = 'N',
            ItemUnitLastUpdated = now(),
            ItemUnitUserID = ?
            WHERE ItemUnitID = ?";

            $query_delete = $this->db->query($sql_delete, [
                $userid,
                $id
            ]);

            if (!$query_delete) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunit delete");
                exit;
            }

            // var_dump($this->db->affected_rows());
            // print_r($insert_id);

            $sql_json_before = "SELECT * 
            FROM itemunit
            WHERE ItemUnitIsActive = 'N'
            AND ItemUnitID = ?";

            $qry_json_before = $this->db->query(
                $sql_json_before,
                [
                    $id
                ]
            );

            if (!$qry_json_before) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunit select json");
                exit;
            }

            $data_by_id = $qry_json_before->row();

            $json_after_log = json_encode($data_by_id);

            // print_r($json_after_log);

            $sql_insert_log = "INSERT INTO acc_one_log.itemunit_log(
                ItemUnitLogItemUnitID,
                ItemUnitLogStatus,
                ItemUnitLogJSONBefore,
                ItemUnitLogJSONAfter,
                ItemUnitLogUserID,
                ItemUnitLogCreated
            ) VALUES (
                ?,
                'DELETE',
                null,
                ?,
                ?,
                now()
            )";

            $qry_insert_log = $this->db->query(
                $sql_insert_log,
                [
                    $id,
                    $json_after_log,
                    $userid
                ]
            );

            if (!$qry_insert_log) {
                $this->db->trans_rollback();
                $this->sys_error_db("itemunit delete log");
                exit;
            }

            // sukses
            $this->db->trans_commit();
            $result = array(
                "total" => 1,
                "records" => array("xid" => 0)
            );
            $this->sys_ok($result);

            // sukses
            // $this->db->trans_commit();
            // $result = array ("total" => 1, "records" => array("xid" => 0));
            // $this->sys_ok($result);

        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->sys_error($message);
        }
    }
}
