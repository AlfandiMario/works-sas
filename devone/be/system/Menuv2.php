<?php

class Menu extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->db_smartone = $this->load->database("onedev", true);
    }
    function change_password() {
       $prm = $this->sys_input;
       if ( ! $this->isLogin ) {
         echo json_encode(
            array("status"=>"ERR", "message"=> "Invalid Token")
         );
         exit;
       }
       $sm_password = md5($this->one_salt . $prm["old"] . $this->one_salt);
       $userID = $this->sys_user["M_UserID"];

       $query = $this->db_onedev->query("select * from m_user where M_UserID = ? and M_UserPassword = ?",
         array($userID, $sm_password) );
       if(!$query) {
         echo json_encode(
            array("status"=>"ERR", "message"=> "Invalid Password")
         );
         exit;
       }
       $rows = $query->result_array();
       if(count($rows) == 0 ) {
         echo json_encode(
            array("status"=>"ERR", "message"=> "Invalid Password")
         );
         exit;
       }
       $new_password = md5($this->one_salt . $prm["new"] . $this->one_salt);
       $query = $this->db_onedev->query("update m_user set M_UserPassword=? where M_UserID = ?",
          array($new_password,$userID) );
       if(!$query) {
         echo json_encode(
            array("status"=>"ERR", "message"=> "Invalid Password")
         );
         exit;
      }
       echo json_encode( array("status"=>"OK", "message"=>""));
    }
    function get_bread_crumb_v2() {
       $prm = $this->sys_input;
       /*
       if ( ! $this->is_login ) {
         echo json_encode(
            array("status"=>"ERR", "message"=> "Invalid Token","data"=>$data)
         );
       } 
        */
       $xpath = parse_url($prm["xref"]);
       $path = $xpath["path"];
       if ( substr($path,-1) == "/" ) $path = substr($path,0, strlen($path) - 1);
       $path = str_replace("/one-ui/","",$path);
       $path = str_replace("one-ui/","",$path);
         echo  "path : $path \n";
       $user_id = $this->sys_user['M_UserID'];
       // get bread_crumb
       $sql = "select fn_sys_breadcrumb(?,?) as breadcrumb";
       $qry = $this->db_smartone->query($sql,array($path,$user_id));
       $rows = $qry->result();
       $breadcrumb = "";
       $is_page_allowed = false;
       $dashboard = "one-ui/test/vuex/one-fo-verification";
       if (count($rows) > 0 ) {
          $breadcrumb = $rows[0]->breadcrumb;
          if ($breadcrumb != "" ) $is_page_allowed = true;
       } 
       $data = array(
          "bread_crumb" => $breadcrumb,
          "dashboard" => $dashboard,
          "is_page_allowed" => $is_page_allowed
       );
       echo json_encode(
          array("status"=>"OK", "data"=>$data)
       );

    }
    function get_bread_crumb() {
       $prm = $this->sys_input;
       /*
       if ( ! $this->is_login ) {
         echo json_encode(
            array("status"=>"ERR", "message"=> "Invalid Token","data"=>$data)
         );
       } 
        */
       $xpath = parse_url($prm["xref"]);
       $path = $xpath["path"];
       if ( substr($path,-1) == "/" ) $path = substr($path,0, strlen($path) - 1);
       $path = str_replace("/one-ui/","",$path);
       $path = str_replace("one-ui/","",$path);

       $user_id = $this->sys_user['M_UserID'];
       // get bread_crumb
       $sql = "select fn_sys_breadcrumb(?,?) as breadcrumb";
       $qry = $this->db_smartone->query($sql,array($path,$user_id));
       //file_put_contents("/xtmp/fx-last_query","\n" . $this->db_smartone->last_query() );
       $rows = $qry->result();
       $breadcrumb = "";
       $is_page_allowed = false;
       $dashboard = "one-ui/test/vuex/one-fo-verification";
       if (count($rows) > 0 ) {
          $breadcrumb = $rows[0]->breadcrumb;
          if ($breadcrumb != "" ) $is_page_allowed = true;
       } 
       $data = array(
          "bread_crumb" => $breadcrumb,
          "dashboard" => $dashboard,
          "is_page_allowed" => $is_page_allowed
       );
       echo json_encode(
          array("status"=>"OK", "data"=>$data)
       );

    }
    function get_menu()
    {
        $sql = "CALL sp_sys_menu_user('{$this->sys_user['M_UserID']}')";
        // $query = $this->db_smartone->query($sql);

        $index     = 0;
        $ResultSet = array();

        /* execute multi query */
        if (mysqli_multi_query($this->db_smartone->conn_id, $sql)) {
            do {
                if (false != $result = mysqli_store_result($this->db_smartone->conn_id)) {
                    $rowID = 0;
                    while ($row = $result->fetch_assoc()) {
                        $x = json_decode($row['x']);
                        
                        foreach ($x as $k => $v)
                        {
                            if (!isset($ResultSet[$index]['p_'.$v->parent_id]))
                                $ResultSet[$index]['p_'.$v->parent_id] = [];

                                $ResultSet[$index]['p_'.$v->parent_id][] = $v;
                        }
                        // $ResultSet[$index] = 
                        // $rowID++;
                    }
                }
                $index++;
            } while (mysqli_next_result($this->db_smartone->conn_id));
        }

        echo json_encode(["status"=>"OK", "data"=>$ResultSet]);
    }
}
?>
