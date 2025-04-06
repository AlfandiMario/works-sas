<?php
class Photo extends CI_Controller
{
    var $base_dir = "/home/one/project/one/one-media/one-photo/patient/";
    function test()
    {
        echo "Test";
    }
    function small()
    {
        $y = $this->input->get("y", true);
        $pid = $this->input->get("pid", true);
        $foto_file = $this->base_dir . "$y" . "/" . $pid . "_thumb.jpg";
        header('Content-type: image/jpg');

        echo file_get_contents($foto_file);
    }
}
