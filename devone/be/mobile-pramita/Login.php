<?php
class Login extends MY_Controller
{
   public function __construct()
   {
      parent::__construct();
      $this->db_onelite= $this->load->database('onelite',true);
   }
   
   function strip_unicode($inp) {
	//echo $inp;
	$result = mb_convert_encoding($inp, 'US-ASCII', 'UTF-8');
	//echo $result;
	$result = str_replace("?"," ",$result);
	//echo $result;
	return $result;
}
 
function generate_string($input, $strength = 4) {
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
 
    return $random_string;
}

function rateLimitOtp($hp,$level,$act="") {

	if ($hp == "") {
          echo json_encode([
              "status" => "ERR",
              "message" => "Request too fast | blank hp ",
          ]);
          exit;
	}
      $RATE_LIMIT = 10;
      //delete rate limit >  5 menit 
      $sql = "delete from rate_limit_otp where rateLimitOtpCreated + interval 5 minute < now()";
      $this->db->query($sql);

      //$level 1 = check tujuan HP
      if ($level > 0)  {
        $sql = "select 
          if(max(rateLimitOtpCreated) + interval ? second > now() , 'Y', 'N') IsTooFast
          from rate_limit_otp
          where rateLimitOtpHp = ?";
        $qry = $this->db->query($sql,[$RATE_LIMIT,$hp]); 
        if (!$qry) {
          echo json_encode([

              "status" => "ERR",

              "message" => "Request too fast | ERR " ,

          ]);

          exit;
        }
        $rows = $qry->result_array();
        if (count($rows) > 0 and $rows[0]["IsTooFast"] == "Y") {
          echo json_encode([
              "status" => "ERR",
              "message" => "Request too fast | $hp " . date("Y-m-d H:i:s"),
          ]);

          exit;
        }
      }

      //$level 2 = check Asal IP
      $originIp = $_SERVER["REMOTE_ADDR"];

      if($level > 1)  {
        $sql = "select 
          if(max(rateLimitOtpCreated) + interval ? second > now() , 'Y', 'N') IsTooFast
          from rate_limit_otp
          where rateLimitOtpIp = ?";
        $qry = $this->db->query($sql,[$RATE_LIMIT,$originIp]); 
        if (!$qry) {
          echo json_encode([

              "status" => "ERR",

              "message" => "Request too fast | ERR ",

          ]);

          exit;
        }
        $rows = $qry->result_array();
        if (count($rows) > 0 and $rows[0]["IsTooFast"] == "Y") {
          echo json_encode([

              "status" => "ERR",

              "message" => "Request too fast | $originIp " . date("Y-m-d H:i:s"),

          ]);

          exit;
        }
      }

      //insert into rate_limit_otp 
      $sql = "insert into rate_limit_otp (rateLimitOtpCreated,rateLimitOtpHp,rateLimitOtpIp,rateLimitOtpAction) values(now(),?,?,?) ";
      $qry = $this->db->query($sql,[$hp,$originIp,$act]);

        if (!$qry) {
          echo json_encode([

              "status" => "ERR",

              "message" => "Request too fast | ERR " . $this->db->error()["message"],

          ]);

	  exit;
	}

    }

	function getOTP() {
		$hp = $this->sys_input["hp"];
		$act = $this->sys_input["act"];
		$this->rateLimitOtp($hp,2,$act);
		// ganti now + 30
		$time = date("Y-m-d H:i:s", strtotime("now + 120 second"));
		$permitted_chars = '0123456789';
		$code = $this->generate_string($permitted_chars, 4);
		if(intval($hp) == intval('081574044524')){
			$code = 1122;
		}
		
		if($act == 'wa'){
			//$this->load->library("Zensms");
			$msg = "PRAMITA LAB : KODE OTP ANDA ".$code." JANGAN BERITAHUKAN KEPADA ORANG LAIN";
			//echo $msg;
			//$wa = $this->zensms->whatsapp($hp,$msg);
			//nusa integrasi
			//$this->load->library("Wa_pramita");
			//$xhp = str_replace('0', '62', substr($hp, 0, 1)).substr($hp, 1);
			//$wa = $this->wa_pramita->otp($xhp,$code);
			//kirim pesan
			$this->load->library("Wa_krmv3");
                        $xhp = str_replace('0', '62', substr($hp, 0, 1)) . substr($hp, 1);
			$wa = $this->wa_krmv3->send_otp($xhp, $code);

			//print_r($wa);
			$status = $wa["status"];
			$message= $wa["message"];
		}
		else{
			$this->load->library("Gratikasms");
			$xhp = str_replace('0', '62', substr($hp, 0, 1)).substr($hp, 1);
			$msg = "PRAMITA LAB : KODE OTP ANDA ".$code." JANGAN BERITAHUKAN KEPADA ORANG LAIN. @regonline.pramita.co.id #".$code."";
			//echo $msg;
			$sms = $this->gratikasms->send($xhp,$msg);
			$status = $sms["status"];
			$message= $sms["message"];
		}

		$sql = "INSERT INTO x_otp (X_OTPHp,X_OTPExpired,X_OTPCode)
				VALUES(?,now() + interval 60 second,?)";
		$qry = $this->db_onelite->query($sql, array($hp,$code));
		//$qry = $this->db_onelite->query($sql, array($hp,$time,$code));
		
		echo json_encode( array(
			"status" => 'OK',
			"data" => array('hp'=>$hp,'time'=>$time),
			"message" => $message
		));
	}
	
	function getOTPForget() {
		$hp = $this->sys_input["hp"];
		$act = $this->sys_input["act"];
		$this->rateLimitOtp($hp,2,$act);
		// ganti now + 30
		$time = date("Y-m-d H:i:s", strtotime("now + 120 second"));
		$permitted_chars = '0123456789';
		$code = $this->generate_string($permitted_chars, 4);
		if(intval($hp) == intval('081574044524')){
			$code = 1122;
		}
		
		if($act == 'wa'){
			//$this->load->library("Zensms");
			$msg = "PRAMITA LAB : KODE VERIFIKASI GANTI PASSWORD ANDA : ".$code." JANGAN BERITAHUKAN KEPADA ORANG LAIN";
			//echo $msg;
			//$wa = $this->zensms->whatsapp($hp,$msg);
			//nusa integrasi
			//$this->load->library("Wa_pramita");
			//$xhp = str_replace('0', '62', substr($hp, 0, 1)).substr($hp, 1);
			//$wa = $this->wa_pramita->otp($xhp,$code);
			//kirim pesan
			$this->load->library("Wa_krmv3");
                        $xhp = str_replace('0', '62', substr($hp, 0, 1)) . substr($hp, 1);
			$wa = $this->wa_krmv3->send_otp($xhp, $code);

			//print_r($wa);
			$status = $wa["status"];
			$message= $wa["message"];
		}

		$sql = "INSERT INTO x_otp_forget (X_OTPForgetHp,X_OTPForgetCode)
				VALUES(?,?)";
		$qry = $this->db_onelite->query($sql, array($hp,$code));
		//$qry = $this->db_onelite->query($sql, array($hp,$time,$code));
		
		echo json_encode( array(
			"status" => 'OK',
			"data" => array('hp'=>$hp),
			"message" => $message
		));
	}
   
	function getOTP_coba() {
		$hp = $this->sys_input["hp"];
		$act = $this->sys_input["act"];
		$time = date("Y-m-d H:i:s", time() + 30);
		$permitted_chars = '0123456789';
		$code = $this->generate_string($permitted_chars, 4);
		$code = 1234;
		
		$sql = "INSERT INTO x_otp (X_OTPHp,X_OTPExpired,X_OTPCode)
				VALUES(?,?,?)";
		$qry = $this->db_onelite->query($sql, array($hp,$time,$code));
		
		echo json_encode( array(
			"status" => 'OK',
			"data" => array('hp'=>$hp,'time'=>$time),
			"message" => $message
		));
	}
	
	function checkOTP() {
		$hp = $this->sys_input["hp"];
		$otp = $this->sys_input["otp"];
		$rst = array();
		$sql = "SELECT *
				FROM x_otp
				WHERE
					X_OTPHp = ? AND
					X_OTPCode = ? AND
					NOW() < X_OTPExpired ORDER BY X_OTPExpired DESC LIMIT 1";
		$qry = $this->db_onelite->query($sql, array($hp,$otp));
		//echo $this->db_onelite->last_query();
		$rows = $qry->result_array();
		if($rows){
			$rst = $rows;
			echo json_encode( array(
				"status" => "OK",
				"data" => $rst
			));
		}
		else{
			echo json_encode( array(
				"status" => "ERR",
				"message" => 'KODE OTP yang anda masukkan salah'
			));
		}
		
	}
	
	function newUserX(){
		$prm = $this->sys_input;
			//print_r($prm);
            $username = $prm['hp'];
            $password = $prm['password'];
            $md5_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
			$sql = "SELECT * FROM m_usergroup WHERE  M_UserGroupDefault = 'Y' AND M_UserGroupIsActive = 'Y' LIMIT 1";
			$row_usergroup = $this->db_onelite->query($sql)->row_array();
			//echo $this->db_onelite->last_query();
            $sql = "insert into m_user(
                     M_UserM_UserGroupID,
                     M_UserUsername,
                     M_UserPassword,
                     M_UserCreated,
                     M_UserLastUpdated
                  )
                  values(
					?,
					?,
					?,
					now(),
					now()
				  )";
			$qry = $this->db_onelite->query($sql, array($row_usergroup['M_UserGroupID'],$username,$md5_password));
			//echo $this->db_onelite->last_query();
		
			if (!$qry ) {
				//echo $this->db_onelite->last_query();
				echo json_encode( array(
				   "status" => "ERR",
				   "message" => 'SYNTACT ERROR'
				));
				return;
			}
			
			$query = $this->db_onelite->query("select 
						M_UserID,
						M_UserUsername
						from m_user 
						join m_usergroup ON M_UserM_UserGroupID = M_UserGroupID
						where M_UserUsername = ? and M_UserPassword = ?
						and M_UserIsActive = 'Y'
					",array($username, $md5_password));
			
			
			if (!$query) {
				echo $this->db_onelite->last_query();
				$message = $this->db_onelite->error();
				$this->sys_error($message);
				exit;
			}
			
			
			
			$user = $query->row_array();
			$user['ip'] = $_SERVER['REMOTE_ADDR'];
			$user['agent'] = $_SERVER['HTTP_USER_AGENT'];
			$user['date'] = date('Y-m-d H:i:s');
			$token  = JWT::encode($user,$this->SECRET_KEY);
			$data = array(
			  "user" => $user,
			  "token" => $token
			);

			$query = $this->db_onelite->query("update m_user SET M_UserIsLoggedIn = 'Y', M_UserLastAccess = now(), M_UserActiveToken = ? WHERE M_UserID = ?
			",array($token,$user['M_UserID']));
			if (!$query) {
	
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			$sql = "SELECT * FROM m_sex WHERE M_SexCode = ? AND M_SexIsActive = 'Y'";
			$rows_sex = $this->db_onelite->query($sql, array($prm['sex']))->row_array();
			$dob = date("Y-m-d", strtotime($prm['dob']));
			$prm['name'] = str_replace("'", "\\'", $prm['name']);
			$prm['name'] = $this->strip_unicode($prm['name']);
			
			if(intval($prm['patient_id']) == 0){
				$sql = "INSERT INTO m_patient (
									M_PatientNoreg,
									M_PatientM_TitleID,
									M_PatientName,
									M_PatientM_IdTypeID,
									M_PatientIDNumber,
									M_PatientM_SexID,
									M_PatientDOB,
									M_PatientHp,
									M_PatientJob,
									M_PatientEmail,
									M_PatientUserID,
									M_PatientLastUpdated
								)
								VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())";
				$query = $this->db_onelite->query($sql, array(
							$prm['pramitaid'],
							$prm['title']['id'],
							$prm['name'],
							$prm['idtype']['id'],
							$prm['ktp'],
							$rows_sex['M_SexID'],
							$dob,
							$prm['hp'],
							$prm['job'],
							$prm['email'],
							$user['M_UserID']
						));
						//echo $this->db_onelite->last_query();
				$patient_id = $this->db_onelite->insert_id();
						
				$prm['address'] = str_replace("'", "\\'", $prm['address']);
				$prm['address'] = $this->strip_unicode($prm['address']);
				
				$sql = "INSERT m_patientaddress (
							M_PatientAddressM_PatientID,
							M_PatientAddressNote,
							M_PatientAddressDescription,
							M_PatientAddressM_KelurahanID,
							M_PatientAddressUserID,
							M_PatientAddressLastUpdated
						)
						VALUES(
							?,?,?,?,?,NOW()
						)";
				
				$query = $this->db_onelite->query($sql, array(
							$patient_id,
							'Utama',
							$prm['address'],
							$prm['kelurahan']['id'],
							$user['M_UserID']
						));
						
				
				$sql = "INSERT m_userpatient (
							M_UserPatientM_UserID,
							M_UserPatientM_PatientID,
							M_UserPatientLastUpdated,
							M_UserPatientUserID
						)
						VALUES(
							?,?,NOW(),?
						)";
				
				$query = $this->db_onelite->query($sql, array(
							$user['M_UserID'],
							$patient_id,
							$user['M_UserID']
						));
			
			}
			
			$query = $this->db_onelite->query("INSERT INTO s_loginlog(S_LoginLogUsername,S_LoginLogLat,S_LoginLogLng,S_LoginLogIP,S_LoginLogAgent,S_LoginLogStatus,S_LoginLogType) 
					VALUES (?,?,?,?,?,?,?)",array($user['M_UserUsername'],$prm['lat'],$prm['lng'],$user['ip'],$user['agent'],'SUCCESS','LOGIN'));
			
			if (!$query) {
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			$data_json = json_encode(array(
						'USERNAME' 	=> $user['M_UserUsername'],
						'LAT' 		=> $prm['lat'],
						'LNG' 		=> $prm['lng'],
						'IP' 		=> $user['ip'],
						'AGENT' 	=> $user['agent'],
						'TOKEN' 	=> $token
					));
			$query = $this->db_onelite->query("INSERT INTO log_activity(Log_ActivityUsername,Log_ActivityName,Log_ActivityJSON) 
					VALUES (?,?,?)",array($user['M_UserUsername'],'NEW_USER',$data_json));
			if (!$query) {
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			echo json_encode( array(
				"status" => "OK",
				"data" => array('username'=>$username,'token'=>$token)
			));
	}
	
	function checkHP() {
		$hp = $this->sys_input["hp"];
		$act = $this->sys_input["act"];
		$rst = array();
		$sql = "SELECT *
				FROM m_user
				WHERE
					M_UserUsername = ? AND M_UserIsActive = 'Y' LIMIT 1";
		$qry = $this->db_onelite->query($sql, array($hp));
		$exist_row = $qry->row_array();
		if($exist_row){
			if($act == 'forgetpass'){
				$rst = array('hp'=>$hp,'status'=>'error','msg'=>'Maaf fitur ini sedang dinonaktifkan');
			}
			else{
				$rst = array('hp'=>$hp,'status'=>'error','msg'=>'Nomor HP sudah terdaftar');
			}
		}
		else{
			if($act == 'forgetpass'){
				$rst = array('hp'=>$hp,'status'=>'error','msg'=>'Nomor HP belum terdaftar');
			}
			else{
				$rst = array('hp'=>$hp,'status'=>'success','msg'=>'Nomor HP belum terdaftar');
			}
		}

		echo json_encode( array(
			"status" => "OK",
			"data" => $rst
		));
	}

	
	
	function newUser()
    {
            
			echo 'dasdasd';
			//# ambil parameter input
            //$prm = $this->sys_input;
			//print_r($prm);
            /*$username = $prm['hp'];
            $password = $prm['password'];
            $md5_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
			$sql = "SELECT * FROM m_usergroup WHERE  M_UserGroupDefault = 'Y' AND M_UserGroupIsActive = 'Y' LIMIT 1";
			$row_usergroup = $this->db_onelite->query($sql)->row_array();
			echo $this->db_onelite->last_query();
            $sql = "insert into m_user(
                     M_UserM_UserGroupID,
                     M_UserUsername,
                     M_UserPassword,
                     M_UserCreated,
                     M_UserLastUpdated
                  )
                  values(
					?,
					?,
					?,
					now(),
					now()
				  )";
			$qry = $this->db_onelite->query($sql, array($row_usergroup['M_UserGroupID'],$username,$md5_password));
			//echo $this->db_onelite->last_query();
		
			if (!$qry ) {
				//echo $this->db_onelite->last_query();
				echo json_encode( array(
				   "status" => "ERR",
				   "message" => 'SYNTACT ERROR'
				));
				return;
			}
			
			$query = $this->db_onelite->query("select 
						M_UserID,
						M_UserUsername
						from m_user 
						join m_usergroup ON M_UserM_UserGroupID = M_UserGroupID
						where M_UserUsername = ? and M_UserPassword = ?
						and M_UserIsActive = 'Y'
					",array($username, $md5_password));
			
			
			if (!$query) {
				$message = $this->db_onelite->error();
				$this->sys_error($message);
				exit;
			}
			
			$user = $query->row_array();
			$user['ip'] = $_SERVER['REMOTE_ADDR'];
			$user['agent'] = $_SERVER['HTTP_USER_AGENT'];
			$user['date'] = date('Y-m-d H:i:s');
			$token  = JWT::encode($user,$this->SECRET_KEY);
			$data = array(
			  "user" => $user,
			  "token" => $token
			);

			$query = $this->db_onelite->query("update m_user SET M_UserIsLoggedIn = 'Y', M_UserLastAccess = now(), M_UserActiveToken = ? WHERE M_UserID = ?
			",array($token,$user['M_UserID']));
			if (!$query) {
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			$query = $this->db_onelite->query("INSERT INTO s_loginlog(S_LoginLogUsername,S_LoginLogLat,S_LoginLogLng,S_LoginLogIP,S_LoginLogAgent,S_LoginLogStatus,S_LoginLogType) 
					VALUES (?,?,?,?,?,?,?)",array($user['M_UserUsername'],$prm['lat'],$prm['lng'],$user['ip'],$user['agent'],'SUCCESS','LOGIN'));
			
			if (!$query) {
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			$data_json = json_encode(array(
						'USERNAME' 	=> $user['M_UserUsername'],
						'LAT' 		=> $prm['lat'],
						'LNG' 		=> $prm['lng'],
						'IP' 		=> $user['ip'],
						'AGENT' 	=> $user['agent'],
						'TOKEN' 	=> $token
					));
			$query = $this->db_onelite->query("INSERT INTO log_activity(Log_ActivityUsername,Log_ActivityName,Log_ActivityJSON) 
					VALUES (?,?,?)",array($user['M_UserUsername'],'NEW_USER',$data_json));
			if (!$query) {
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			echo json_encode( array(
				"status" => "OK",
				"data" => array('username'=>$username,'token'=>$token)
			));*/
        
	}
	
	function forgetPass()
    {
            //# ambil parameter input
            $prm = $this->sys_input;
            $username = trim(filter_var($prm['hp'], FILTER_SANITIZE_STRING));
            $password = trim(filter_var($prm['password'], FILTER_SANITIZE_STRING));
            $otp = trim(filter_var($prm['otp'], FILTER_SANITIZE_STRING));

            // Validate required fields
            if (empty($username) || empty($password) || empty($otp)) {
                echo json_encode([
                    "status" => "ERR",
                    "message" => "All fields are required"
                ]);
                return;
            }

            // Validate phone number format (adjust regex pattern as needed)
            if (!preg_match("/^[0-9]{10,15}$/", $username)) {
                echo json_encode([
                    "status" => "ERR", 
                    "message" => "Invalid phone number format"
                ]);
                return;
            }

            // Validate OTP format (assuming 4-6 digit numeric code)
            if (!preg_match("/^[0-9]{4,6}$/", $otp)) {
                echo json_encode([
                    "status" => "ERR",
                    "message" => "Invalid OTP format"
                ]);
                return;
            }

            $sql = "SELECT * FROM x_otp_forget 
                    WHERE X_OTPForgetHp = ? 
                    AND X_OTPForgetCode = ? 
                    AND X_OTPForgetIsUsed = 'N'";
            $qry = $this->db_onelite->query($sql, array($username, $otp));
            if (!$qry ) {
                //echo $this->db_onelite->last_query();
                $this->sys_error("Gagal verifikasi, silahkan ulangi lagi");
                return;
            }

            $data_otp_forget = $qry->result_array();

            if(count($data_otp_forget) > 0 ){
                $md5_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
                $sql = "SELECT * FROM m_user WHERE  M_UserUsername = ? AND M_UserIsActive = 'Y' LIMIT 1";
                $qry = $this->db_onelite->query($sql, array($username));
                
               
                if (!$qry ) {
                    //echo $this->db_onelite->last_query();
                    $this->sys_error("Nomor anda belum terdaftar");
                    return;
                }
                $old_data = $qry->row_array();
                $query = $this->db_onelite->query("UPDATE m_user SET M_UserUsername = ?, M_UserPassword = ? WHERE M_UserUsername = ?
                        ",array($username, $md5_password, $username));
                
                if (!$query) {
                    $message = $this->db_onelite->error();
                    $this->sys_error($message);
                    exit;
                }
                
                $sql = "SELECT * FROM m_user WHERE  M_UserUsername = ? AND M_UserIsActive = 'Y' LIMIT 1";
                $query = $this->db_onelite->query($sql, array($username));
                
                if (!$query) {
                    $message = $this->db_onelite->error();
                    $this->sys_error($message);
                    exit;
                }
                
                $new_data = $query->row_array();
                
                $reqestby = array(
                            'USERNAME' 	=> $$username,
                            'LAT' 		=> $prm['lat'],
                            'LNG' 		=> $prm['lng'],
                            'IP' 		=> $user['ip'],
                            'AGENT' 	=> $user['agent']
                        );
                $data_json = json_encode(array(
                                    'old' => $old_data,
                                    'new' => $new_data,
                                    'requestby' => $reqestby
                                ));
                $query = $this->db_onelite->query("INSERT INTO log_activity(Log_ActivityUsername,Log_ActivityName,Log_ActivityJSON) 
                        VALUES (?,?,?)",array($user['M_UserUsername'],'CHANGE_PASSWORD',$data_json));
                if (!$query) {
                    echo $this->db_onelite->last_query();
                    $message = $this->db_onelite->error();
                    $this->sys_error($message);
                    exit;
                }

                $sql = "UPDATE x_otp_forget SET X_OTPForgetIsUsed = 'Y'
                        WHERE X_OTPForgetHp = ? AND X_OTPForgetCode = ?";
                $query = $this->db_onelite->query($sql, array($username,$otp));

                
                echo json_encode( array(
                    "status" => "OK",
                    "message" => 'Password berhasil diganti',
                    "type" => 'success'
                ));
            }else{
                //echo $this->db_onelite->last_query();
                $this->sys_error("Kode verifikasi tidak ditemukan/salah");
                return;
            }

            
        
	}
	
	function login()
    {
            //# ambil parameter input
            $prm = $this->sys_input;
            $username = $prm['hp'];
            $password = $prm['password'];
            $md5_password = md5($this->one_salt . $prm["password"] . $this->one_salt);
			
			$query = $this->db_onelite->query("select 
						M_UserID,
						M_UserUsername
						from m_user 
						join m_usergroup ON M_UserM_UserGroupID = M_UserGroupID
						where M_UserUsername = ? and M_UserPassword = ?
						and M_UserIsActive = 'Y'
					",array($username, $md5_password));
			
			
			if (!$query) {
				$message = $this->db_onelite->error();
				$this->sys_error($message);
				exit;
			}
			
			$user = $query->row_array();
			if($user){
				$user['ip'] = $_SERVER['REMOTE_ADDR'];
				$user['agent'] = $_SERVER['HTTP_USER_AGENT'];
				$token  = JWT::encode($user,$this->SECRET_KEY);
				$data = array(
				  "user" => $user,
				  "token" => $token
				);

				$query = $this->db_onelite->query("update m_user SET M_UserIsLoggedIn = 'Y', M_UserLastAccess = now(), M_UserActiveToken = '{$token}' WHERE M_UserID = ?
				",array($user['M_UserID']));
				if (!$query) {
				  $message = $this->db_onelite->error();
				  $this->sys_error($message);
				  exit;
				}
				
				$query = $this->db_onelite->query("INSERT INTO s_loginlog(S_LoginLogUsername,S_LoginLogLat,S_LoginLogLng,S_LoginLogIP,S_LoginLogAgent,S_LoginLogStatus,S_LoginLogType) 
						VALUES (?,?,?,?,?,?,?)",array($user['M_UserUsername'],$prm['lat'],$prm['lng'],$user['ip'],$user['agent'],'SUCCESS','LOGIN'));
				if (!$query) {
				  $message = $this->db_onelite->error();
				  $this->sys_error($message);
				  exit;
				}
				
				$data_json = json_encode($user);
				$query = $this->db_onelite->query("INSERT INTO log_activity(Log_ActivityUsername,Log_ActivityName,Log_ActivityJSON) 
					VALUES (?,?,?)",array($username,'LOGIN_SUCCESS',$data_json));
				
				$xrst = array(
					"status" => "OK",
					"data" => array('username'=>$username,'token'=>$token)
				);
			}
			else{
				$query = $this->db_onelite->query("INSERT INTO s_loginlog(S_LoginLogUsername,S_LoginLogLat,S_LoginLogLng,S_LoginLogIP,S_LoginLogAgent,S_LoginLogStatus,S_LoginLogType) 
						VALUES (?,?,?,?,?,?,?)",array($username,$prm['lat'],$prm['lng'], $_SERVER['REMOTE_ADDR'],$_SERVER['HTTP_USER_AGENT'],'FAILED','LOGIN'));
				
				if (!$query) {
				  $message = $this->db_onelite->error();
				  $this->sys_error($message);
				  exit;
				}
				
				$data_json = json_encode(array('username'=>$username,'password'=>$md5_password));
				$query = $this->db_onelite->query("INSERT INTO log_activity(Log_ActivityUsername,Log_ActivityName,Log_ActivityJSON) 
					VALUES (?,?,?)",array($username,'LOGIN_FAILED',$data_json));
				
				if (!$query) {
				  $message = $this->db_onelite->error();
				  $this->sys_error($message);
				  exit;
				}
				
				$xrst = array(
					"status" => "INVALID_USER",
					"data" => array('username'=>$username)
				);
			}
			
			echo json_encode( $xrst);
        
	}
	
	function xlogout()
    {
            //# ambil parameter input
            $prm = $this->sys_input;
            $username = $prm['username'];
			$user = $this->sys_user;
			//print_r($user);
			
			$query = $this->db_onelite->query("update m_user SET M_UserIsLoggedIn = 'N', M_UserLastAccess = now(), M_UserActiveToken = '' WHERE M_UserID = ?
			",array($user['M_UserID']));
			
			//echo $this->db_onelite->last_query();
			if (!$query) {
				echo $this->db_onelite->last_query();
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			$query = $this->db_onelite->query("INSERT INTO s_loginlog(S_LoginLogUsername,S_LoginLogLat,S_LoginLogLng,S_LoginLogIP,S_LoginLogAgent,S_LoginLogStatus,S_LoginLogType) 
					VALUES (?,?,?,?,?,?,?)",array($username,$prm['lat'],$prm['lng'],$_SERVER['REMOTE_ADDR'],$_SERVER['HTTP_USER_AGENT'],'SUCCESS','LOGOUT'));
			if (!$query) {
			  $message = $this->db_onelite->error();
			  $this->sys_error($message);
			  exit;
			}
			
			$data_json = json_encode($prm);
			$query = $this->db_onelite->query("INSERT INTO log_activity(Log_ActivityUsername,Log_ActivityName,Log_ActivityJSON) 
				VALUES (?,?,?)",array($username,'LOGOUT_SUCCESS',$data_json));
			
			$xrst = array(
				"status" => "OK",
				"data" => $prm
			);
			
			
			echo json_encode( $xrst);
        
	}
		
	

}
?>
