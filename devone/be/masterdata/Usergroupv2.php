<?php
class Usergroupv2 extends MY_Controller
{
   var $db_onedev;
   public function index()
   {
      echo "USER GROUP API";
   }

   public function __construct()
   {
      parent::__construct();
      $this->db_onedev = $this->load->database("onedev", true);
   }

   function lookupuser()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }
         $prm = $this->sys_input;
         $id = $prm['id'];
         $sql = "select M_UserID as id,
            M_UserM_UserGroupID as usergroupid,
            
            M_UserUsername as username,
            M_StaffName As staffname,
            M_UserM_StaffID as xstaff,
            M_UserDefaultT_SampleStationID  as xsamplestation,
            M_UserIsCoordinator as iscoordinator,
            M_UserR_ReportGroupID as xreport,
            'xxx' as action
            from m_user
             join m_staff oN M_UserM_StaffID = M_StaffID
            where
            M_UserM_UserGroupID  = {$id} AND M_UserIsActive = 'Y'";
         //echo $sql;
         $rows = $this->db_onedev->query($sql)->result();

         $result = array("total" => count($rows), "records" => $rows);
         $this->sys_ok($result);
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }

   public function lookup()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }

         $prm = $this->sys_input;
         $search = $prm['search'];
         $all = $prm['all'];
         $limit = '';
         if ($all == 'N') {
            $limit = ' LIMIT 10';
         }
         $sql = "select COUNT(*) as total
            from m_usergroup
            where
            M_UserGroupIsActive = 'Y'";
         $sql_param = array($search);
         $total = $this->db_onedev->query($sql, $sql_param)->row()->total;


         $sql = "SELECT M_UserGroupID as id, M_UserGroupDashboard as dashboard,
            M_UserGroupName as name,  M_UserGroupIsClinic as clinic,   M_UserGroupName as description , 'xxx' as usergrouptype
            from m_usergroup
            where
            M_UserGroupName LIKE CONCAT('%','{$search}','%')  AND
            M_UserGroupIsActive = 'Y' $limit";
         $sql_param = array($search);
         $query = $this->db_onedev->query($sql);
         if ($query) {
            $rows = $query->result_array();
         } else {
            $this->sys_error_db("m_usergroup select", $this->db_onedev);
            exit;
         }

         $result = array("total" => $total, "total_filter" => count($rows), "records" => $rows);
         $this->sys_ok($result);
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }


   public function getdashboards()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }

         $prm = $this->sys_input;
         $group_id = $prm['group_id'];

         $sql = "select menu.S_MenuID as id, CONCAT(menu.S_MenuName,' [ ',menuparent.S_MenuName,' ]') as name, CONCAT('one-ui/',menu.S_MenuUrl) as url, menuparent.S_MenuName as group_name
				FROM s_menu menu
				LEFT JOIN s_menu menuparent ON menu.S_MenuParentS_MenuID = menuparent.S_MenuID
				WHERE
				menu.S_MenuUrl <> '#' AND menu.S_MenuIsActive = 'Y'";
         $sql_param = array($search);
         $query = $this->db_onedev->query($sql);
         //echo $this->db_onedev->last_query();
         if ($query) {
            $rows = $query->result_array();
         } else {
            $this->sys_error_db("m_usergroup select", $this->db_onedev);
            exit;
         }
         $result = array("total" => $total, "total_filter" => count($rows), "records" => $rows);
         $this->sys_ok($result);
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }


   public function addnewusergroup()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }
         $prm = $this->sys_input;

         $name_usergroup = $prm['name'];
         $dashboard_usergroup = $prm['dashboard'];
         $clinic_usergroup = $prm['clinic'];

         $query = "SELECT COUNT(*) as exist FROM m_usergroup WHERE M_UserGroupIsActive = 'Y' AND M_UserGroupName = '{$name_usergroup}'";
         $exist_name =  $this->db_onedev->query($query)->row()->exist;

         if ($exist_name == 0) {
            $sql = "insert into m_usergroup(
               M_UserGroupName,
               M_UserGroupDashboard,
               M_UserGroupIsClinic,
               M_UserGroupCreated,
               M_UserGroupLastUpdated
            )
            values( ?, ?, ?,now(), now())";
            $query = $this->db_onedev->query(
               $sql,
               array(
                  $name_usergroup,
                  $dashboard_usergroup,
                  $clinic_usergroup
               )
            );
            if (!$query) {
               $this->sys_error_db("m_usergroup insert");
               exit;
            }

            $result = array("total" => 1, "records" => array("xid" => 0));
            $this->sys_ok($result);
         } else {
            $errors = array();

            if ($exist_name != 0) {
               array_push($errors, array('field' => 'name', 'msg' => 'Nama sudah ada yang pakai dong'));
            }

            $result = array("total" => -1, "errors" => $errors, "records" => 0);
            $this->sys_ok($result);
         }
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }

   public function editusergroup()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }

         $prm = $this->sys_input;
         $id_usergroup = $prm['id'];
         $name_usergroup = $prm['name'];
         $dashboard_usergroup = $prm['dashboard'];
         $clinic_usergroup = $prm['clinic'];

         $query = "SELECT COUNT(*) as exist FROM m_usergroup WHERE M_UserGroupIsActive = 'Y' AND M_UserGroupName = '{$name_usergroup}'
            AND M_UserGroupID <> {$id_usergroup} ";
         $exist_name =  $this->db_onedev->query($query)->row()->exist;

         if ($exist_name == 0) {

            $sql = "update  m_usergroup SET
                     M_UserGroupName = ?,
                     M_UserGroupDashboard = ?,
                     M_UserGroupIsClinic = ?,
                     M_UserGroupLastUpdated = now()
                     where
                     M_UserGroupID = ?
               ";
            $query = $this->db_onedev->query(
               $sql,
               array(
                  $name_usergroup,
                  $dashboard_usergroup,
                  $clinic_usergroup,
                  $id_usergroup
               )
            );
            if (!$query) {
               $this->sys_error_db("m_usergroup update");
               exit;
            }

            $result = array("total" => 1, "records" => array("xid" => $id_usergroup));
            $this->sys_ok($result);
         } else {
            $errors = array();

            if ($exist_name != 0) {
               array_push($errors, array('field' => 'name', 'msg' => 'Nama sudah ada yang pakai dong'));
            }

            $result = array("total" => -1, "errors" => $errors, "records" => 0);
            $this->sys_ok($result);
         }
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }

   function getreportsample()
   {
      if (! $this->isLogin) {
         $this->sys_error("Invalid Token");
         exit;
      }
      $rows = [];
      $query = "	SELECT *
         FROM r_reportgroup
         WHERE
         R_ReportGroupIsActive = 'Y' ";
      $rows['reports'] = $this->db_onedev->query($query)->result_array();

      $query = "	SELECT * FROM t_samplestation WHERE T_SampleStationIsActive = 'Y' ";
      $rows['samplestations'] = $this->db_onedev->query($query)->result_array();

      $query = "	SELECT *
         FROM m_usergroup
         WHERE
         M_UserGroupIsActive  = 'Y' ";
      $rows['usergroupnames'] = $this->db_onedev->query($query)->result_array();

      $query = "	SELECT *
      FROM m_staff
      WHERE
      M_StaffIsActive  = 'Y' ";
      $rows['staffs'] = $this->db_onedev->query($query)->result_array();

      $result = array(
         "total" => count($rows),
         "records" => $rows,
      );
      $this->sys_ok($result);
      exit;
   }

   public function edituser()
   {
      try {
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }
         //# ambil parameter input
         $prm = $this->sys_input;
         $userid = $prm['xid'];
         $username = $prm['username'];

         $password = $prm['password'];
         $md5_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
         $xstaff = $prm['xstaff'];
         $xsamplestation = $prm['xsamplestation'];
         $xreport = $prm['xreport'];
         $xusergroupname = $prm['xusergroupname'];
         $iscoordinator = $prm['iscoordinator'];

         $query = "SELECT COUNT(*) as exist FROM m_user WHERE M_UserIsActive = 'Y' AND M_UserUsername = '{$username}'
         and M_UserID <> $userid ";
         $exist_username =  $this->db_onedev->query($query)->row()->exist;

         if ($exist_username == 0) {

            $sql = "update  m_user SET
            M_UserUsername = ?,
            M_UserM_StaffID = ?,
            M_UserDefaultT_SampleStationID = ?,
            M_UserR_ReportGroupID = ?,
            M_UserM_UserGroupID = ?,
            M_UserIsCoordinator = ?,
            M_UserLastUpdated = now()
            where M_UserID = ? ";

            $query = $this->db_onedev->query(
               $sql,
               array(
                  $username,
                  $xstaff["M_StaffID"],
                  $xsamplestation["T_SampleStationID"],
                  $xreport["R_ReportGroupID"],
                  $xusergroupname["M_UserGroupID"],
                  $iscoordinator,
                  $userid
               )
            );
            if (!$query) {
               $this->sys_error_db("m_user update", $this->db_onedev);
               exit;
            }
            $result = array("total" => 1, "records" => array("xid" => $userid));
            $this->sys_ok($result);
         } else {
            $errors = array();

            if ($exist_name != 0) {
               array_push($errors, array('field' => 'username', 'msg' => 'Nama sudah ada yang pakai dong'));
            }

            $result = array("total" => -1, "errors" => $errors, "records" => 0);
            $this->sys_ok($result);
         }
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }

   public function addnewuser()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }

         //# ambil parameter input
         $prm = $this->sys_input;
         $usergroupid = $prm['usergroupid'];
         $username = $prm['username'];

         $password = $prm['password'];
         $md5_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
         $xstaff = $prm['xstaff'];
         $xsamplestation = $prm['xsamplestation'];
         $xreport = $prm['xreport'];
         $iscoordinator = $prm['iscoordinator'];

         if ($prm['xid'] == 0) {
            $query = "SELECT COUNT(*) as exist FROM m_user WHERE M_UserIsActive = 'Y' AND M_UserUsername = '{$username}'";
            $exist_username =  $this->db_onedev->query($query)->row()->exist;

            if ($exist_username == 0) {
               $sql = "insert into m_user(
                     M_UserM_UserGroupID,M_UserUsername,
                     M_UserPassword,M_UserM_StaffID,
                     M_UserDefaultT_SampleStationID,
                     M_UserR_ReportGroupID,
                     M_UserIsCoordinator,
                     M_UserCreated,M_UserLastUpdated
                  )
                  values( ?,?,?,?,?,?,?,now(),now())";
               $query = $this->db_onedev->query(
                  $sql,
                  array(
                     $usergroupid,
                     $username,
                     $md5_password,
                     $xstaff["M_StaffID"],
                     $xsamplestation["T_SampleStationID"],
                     $xreport["R_ReportGroupID"],
                     $iscoordinator
                  )
               );
               if (!$query) {
                  $this->sys_error_db("m_user insert", $this->db_onedev);
                  exit;
               }
               $result = array("total" => 1, "records" => array("xid" => 0));
               $this->sys_ok($result);
            } else {
               $errors = array();
               if ($exist_username != 0) {
                  array_push($errors, array('field' => 'username', 'msg' => 'Nama User sudah ada yang pakai dong'));
               }

               $result = array("total" => -1, "errors" => $errors, "records" => 0);
               $this->sys_ok($result);
            }
         } else {
            $query = "SELECT COUNT(*) as exist FROM m_user WHERE M_UserIsActive = 'Y' AND M_UserUsername = '{$username}' AND M_UserID <> {$prm['xid']}";
            $exist_username =  $this->db_onedev->query($query)->row()->exist;

            if ($exist_username == 0) {
               $sql = "UPDATE m_user SET M_UserUsername = '{$username}',  M_UserPassword = '{$password}', M_UserM_StaffID = '{$xstaff}', M_UserR_ReportGroupID = '{$xreport}', M_UserDefaultT_SampleStationID = '{$xsamplestation}', M_UserIsCoordinator = '{$iscoordinator}' WHERE M_UserID = '{$prm['xid']}'";
               $query = $this->db_onedev->query($sql);
               $result = array("total" => 1, "records" => array("xid" => 0));
               $this->sys_ok($result);
            } else {
               $errors = array();
               if ($exist_username != 0) {
                  array_push($errors, array('field' => 'username', 'msg' => 'Username sudah ada yang pakai dong'));
               }
               $result = array("total" => -1, "errors" => $errors, "records" => 0);
               $this->sys_ok($result);
            }
         }
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }



   public function deleteuser()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }

         //# ambil parameter input
         $prm = $this->sys_input;

         $sql = "update m_user SET
               M_UserIsActive = 'N',
               M_UserLastUpdated = now()
               WHERE
               M_UserID = ? ";

         $query = $this->db_onedev->query(
            $sql,
            array(
               $prm['id']
            )
         );
         // echo $query;
         if (!$query) {
            $this->sys_error_db("m_user delete");
            exit;
         }
         $result = array("total" => 1, "records" => array("xid" => 0));
         $this->sys_ok($result);
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }

   public function deleteusergroup()
   {
      try {
         //# cek token valid
         if (! $this->isLogin) {
            $this->sys_error("Invalid Token");
            exit;
         }

         //# ambil parameter input
         $prm = $this->sys_input;

         $sql = "update m_usergroup SET
               M_UserGroupIsActive = 'N',
               M_UserGroupLastUpdated = now()
               WHERE
               M_UserGroupID = ? ";

         $query = $this->db_onedev->query(
            $sql,
            array(
               $prm['id']
            )
         );
         if (!$query) {
            $this->sys_error_db("m_usergroup delete");
            exit;
         }

         $sql = "UPDATE m_user SET
               M_UserIsActive = 'N',
               M_UserLastUpdated = now()
               WHERE M_UserM_UserGroupID = ? ";

         $query = $this->db_onedev->query(
            $sql,
            array(
               $prm['id']
            )
         );
         if (!$query) {
            $this->sys_error_db("m_user delete");
            exit;
         }

         $result = array("total" => 1, "records" => array("xid" => 0));
         $this->sys_ok($result);
      } catch (Exception $exc) {
         $message = $exc->getMessage();
         $this->sys_error($message);
      }
   }
}
