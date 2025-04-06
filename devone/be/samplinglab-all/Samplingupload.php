<?php

class SamplingUpload extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->db_onedev = $this->load->database("onedev", true);
		$this->load->helper(array('form', 'url'));
	}
	
	function get_userdatadir()
    {
        $r = $this->db_onedev->query('select S_SystemsUserdataUrl from s_systems where S_SystemsIsActive = "Y"')->row();

        $q = preg_replace('/(smartlab\_)[a-zA-Z0-9]+/', $r->S_SystemsUserdataUrl, getcwd());
        return $q;
    }
	
	function uploadimage(){
		if (! $this->isLogin) {
			$this->sys_error("Invalid Token");
			exit;
		}
		$userid = $this->sys_user["M_UserID"];
		$data = [];
		//print_r($_SERVER);
		$labnumber = $this->input->post('ordernumber');
		$orderid = $this->input->post('orderid');
		$sampletype = $this->input->post('sampletype');
		//$config['upload_path'] = 'assets/'; 
		
		$path = '/home/one/project/one/one-media/one-image-nonlab/';
		//echo $path;
		$config['upload_path'] = $path;
		$config['allowed_types'] = 'jpg|jpeg|png|gif';

		$config['max_size'] = '10000';
		$count = count($_FILES['photos']['name']);
		$this->load->library('upload',$config); 
	
		$error = [];
      for($i=0;$i<$count;$i++){

    

        if(!empty($_FILES['photos']['name'][$i])){

    

          $_FILES['file']['name'] = $_FILES['photos']['name'][$i];

          $_FILES['file']['type'] = $_FILES['photos']['type'][$i];

          $_FILES['file']['tmp_name'] = $_FILES['photos']['tmp_name'][$i];

          $_FILES['file']['error'] = $_FILES['photos']['error'][$i];

          $_FILES['file']['size'] = $_FILES['photos']['size'][$i];

  

          $namex = $labnumber.'-'.$sampletype.'-'.$this->generateRandomString(5);
			//echo $namex;
		
          $config['file_name'] =  $namex;
			$this->upload->initialize($config);
			//echo  $config['file_name'] ;

          

    

          if($this->upload->do_upload('file')){

            $uploadData = $this->upload->data();

            $filename = $uploadData['file_name'];
			//echo $filename;
			
			$sql = "INSERT INTO so_imageupload (
						So_ImageUploadT_SampleTypeID,
						So_ImageUploadT_OrderHeaderID,
						So_ImageUploadT_OrderHeaderLabNumber,
						So_ImageUploadOldName,
						So_ImageUploadNewName,
						So_ImageUploadCreated,
						So_ImageUploadUserID
					)
					VALUES(
						{$sampletype},
						{$orderid},
						'{$labnumber}',
						'{$_FILES['photos']['name'][$i]}',
						'{$filename}',
						NOW(),
						{$userid}
						
					)";
					//echo $sql;
			$this->db_onedev->query($sql);
			$doctorid = $this->input->post('doctorid');
			$doctoraddressid = $this->input->post('doctoraddressid');
			$sql = "UPDATE t_samplingso SET T_SamplingSoM_DoctorID = {$doctorid}, T_SamplingSoM_DoctorAddressID = {$doctoraddressid} 
					WHERE T_SamplingSoT_OrderHeaderID = {$orderid} AND T_SamplingSoT_SampleTypeID = {$sampletype} AND T_SamplingSoIsActive = 'Y'";
			$this->db_onedev->query($sql);
            $data['totalFiles'][] = $filename;

          }
		  else{
			  $error = array('error' => $this->upload->display_errors());
		  }

        }

   

      }
	  
	  $result = array("total" =>count( $data['totalFiles']), "records" =>  $data['totalFiles'],'errors'=>$error);
	  $this->sys_ok($result);
	}

	function xdo_upload()
	{
		$config['upload_path'] = 'assets/';
		$config['allowed_types'] = 'pdf|rar|jpg|png';
		$config['max_size'] = '5000';		
		$config['file_name'] = date('YmdHis');
                
        
		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload('photos'))
		{
			$error = array('error' => $this->upload->display_errors());
			echo json_encode(array('status'=>$error));
		}
		else
		{
			print_r($this->upload->data());
			/*$lastUpdated = date('Y-m-d H:i:s');
			$sql ="UPDATE file_attachment SET File_AattachmentIsActive = 'N' WHERE File_AattachmentReffNumber = '{$this->input->post('trxnumber')}'";
			$this->db->query($sql);
			$sql = "INSERT INTO file_attachment (
						File_AattachmentReffNumber,
						File_AattachmentUrl,
						File_AattachmentIsActive,
						File_AattachmentLastUpdated
					)  
					VALUES ( 
						'{$this->input->post('trxnumber')}',
						'{$this->upload->data('file_name')}',
						'Y',
						'{$lastUpdated}'
						
					)";
					//echo $sql;
			$r = $this->db->query($sql);
			$data = $this->upload->data();
			print_r($this->upload->data();
			echo json_encode('Upload Success');*/
		}
                
                
	}
	
	function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
        
       
}
?>