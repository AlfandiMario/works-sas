<?php
class Code128 extends CI_Controller
{
    function test()
    {
        echo "Test";
    }
    function index()
    {
        $no = $this->input->get("no", true);
        $generator = new Picqer\Barcode\BarcodeGeneratorJPG();
        header('Content-type: image/jpg');
        echo $generator->getBarcode($no, $generator::TYPE_CODE_128);
    }
}
