<?php
/*
### Auth API
- Functions
  - login x
  - logout
  template function  {
    $this->sys_debug();
    try {
      if (! $this->isLogin) {
        $this->sys_error("Invalid Token");
        exit;
      }
      $prm = $this->sys_input;

    } catch(Exception $exc) {
      $message = $exc->getMessage();
      $this->sys_error($message);
    }

  }
*/

class Auth extends MY_Controller
{
  var $db_onedev;
  public function index()
  {
    echo "AUTH API";
  }
  public function __construct()
  {
    parent::__construct();
    $this->db_onedev = $this->load->database("onedev", true);
  }
  function isLogin()
  {
    if (! $this->isLogin) {
      $this->sys_error("Invalid Token");
    } else {
      $prm = $this->sys_input;
      $data = array(
        "user" => $this->sys_user
      );
      $this->sys_ok($data);
    }
  }

  function login()
  {
    $prm = $this->sys_input;
    try {
      //existing password enc
      $sm_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
      $query = $this->db_onedev->query("select M_UserID,M_UserUsername, M_UserGroupDashboard, M_UserDefaultT_SampleStationID,
         M_StaffName, IF(ISNULL(M_CourierID), 'N','Y') as is_courier,
         IFNULL(S_SystemsAutoLogoutTime,0) as time_autologout
      from m_user 
      join m_usergroup ON M_UserM_UserGroupID = M_UserGroupID
      left join m_staff on M_UserM_StaffID = M_StaffID
	  left join m_courier ON M_CourierM_StaffID = M_UserM_StaffID AND M_CourierIsActive = 'Y'
    left join conf_systems ON S_SystemsIsActive = 'Y'
      where M_UserUsername=? and M_UserPassword=?
      and M_UserIsActive = 'Y'
      ", array($prm["username"], $sm_password));
      //echo $query;
      if (!$query) {
        $message = $this->db_onedev->error();
        $this->sys_error($message);
        exit;
      }
      // echo $this->db_onedev->last_query();
      $rows = $query->result_array();
      if (count($rows) > 0) {
        $user = $rows[0];
        $user['ip'] = $_SERVER['REMOTE_ADDR'];
        $user['agent'] = $_SERVER['HTTP_USER_AGENT'];
        //v2
        $user['version'] = 'v2';
        $user['last-login'] = date('Y-m-d H:i:s');
        if (isset($prm['M_SatelliteID'])) {
          $user['M_SatelliteID'] = $prm['M_SatelliteID'];
        } else {
          $user['M_SatelliteID'] = 0;
        }
        $token  = JWT::encode($user, $this->SECRET_KEY);
        $data = array(
          "user" => $user,
          "token" => $token
        );

        $query = $this->db_onedev->query("update m_user SET M_UserIsLoggedIn = 'Y', M_UserLastAccess = now(), M_UserActiveToken = '{$token}' WHERE M_UserID = ?
        ", array($user['M_UserID']));
        if (!$query) {
          $message = $this->db_onedev->error();
          $this->sys_error($message);
          exit;
        }

        $query = $this->db_onedev->query("INSERT INTO one_log.log_login(Log_LoginDateTime,Log_LoginIP,Log_LoginType,Log_LoginStatus,Log_LoginLogin) VALUES (?,?,?,?,?)
        ", array(date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'], 'LOGIN', 'SUCCESS', $prm["username"]));
        if (!$query) {
          $message = $this->db_onedev->error();
          $this->sys_error($message);
          exit;
        }

        $this->sys_ok($data);
        exit;
      }
      $query = $this->db_onedev->query("INSERT INTO one_log.log_login(Log_LoginDateTime,Log_LoginIP,Log_LoginType,Log_LoginStatus,Log_LoginLogin) VALUES (?,?,?,?,?)
        ", array(date('Y-m-d H:i:s'), $this->input->ip_address(), 'LOGIN', 'FAILED', $prm["username"]));
      if (!$query) {
        $message = $this->db_onedev->error();
        $this->sys_error($message);
        exit;
      }
      $this->sys_error_db("Invalid UserName / Password");
    } catch (Exception $exc) {
      $message = $exc->getMessage();
      $this->sys_error($message);
    }
  }

  function logout()
  {
    $prm = $this->sys_input;
    try {

      $query = $this->db_onedev->query(
        "
        UPDATE m_user 
        SET M_UserIsLoggedIn = 'N', M_UserActiveToken = null
        WHERE M_UserID = ?",
        array($this->sys_user['M_UserID'])
      );

      if (!$query) {
        $message = $this->db_onedev->error();
        $this->sys_error($message);
        exit;
      }

      $this->db_onedev->query("INSERT INTO one_log.log_login(Log_LoginDateTime,Log_LoginIP,Log_LoginType,Log_LoginStatus,Log_LoginLogin) VALUES (?,?,?,?,?)
        ", array(date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'], 'LOGOUT', 'SUCCESS', $this->sys_user['M_UserUsername']));
      $this->sys_ok("OK");
    } catch (Exception $exc) {
      $message = $exc->getMessage();
      $this->sys_error($message);
    }
  }
}
