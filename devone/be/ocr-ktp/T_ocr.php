<?php

class T_ocr extends MY_Controller
{
  var $base_img = "/home/one/project/one/one-media/scan-ktp/";
  public function __construct()
  {
    parent::__construct();
  }
  public function proses_scan()
  {
    print_r($this->sys_input);
  }
  public function index()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_llama');
    $result = $this->ocr_llama->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  // * === DEEPINFRA === *

  public function llama_11b($temperature = 0.1)
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_deepinfra');
    $this->ocr_deepinfra->temperature = $temperature;
    $result = $this->ocr_deepinfra->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function llama_90b()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_deepinfra');
    $this->ocr_deepinfra->modelName = "meta-llama/Llama-3.2-90B-Vision-Instruct";
    $result = $this->ocr_deepinfra->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  // * === OPEN ROUTER === *

  public function qwen_vl_72b()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_oroute');
    $this->ocr_oroute->modelName = "qwen/qwen2.5-vl-72b-instruct:free";
    $result = $this->ocr_oroute->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function qwen_vl_plus()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_oroute');
    $this->ocr_oroute->modelName = "qwen/qwen-vl-plus:free";
    $result = $this->ocr_oroute->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function gemini_flash2()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_oroute');
    $this->ocr_oroute->modelName = "google/gemini-2.0-flash-001";
    $result = $this->ocr_oroute->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function qwen2_vl_72b()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_oroute');
    $this->ocr_oroute->modelName = "qwen/qwen-2-vl-72b-instruct";
    $result = $this->ocr_oroute->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  // * === ALIBABA === *

  public function qwen_vl_plus_2()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_alibaba');
    $result = $this->ocr_alibaba->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function qwen_vl_max()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_alibaba');
    $this->ocr_alibaba->modelName = "qwen-vl-max";
    $result = $this->ocr_alibaba->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function qwen25_vl_3b()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_alibaba');
    $this->ocr_alibaba->modelName = "qwen2.5-vl-3b-instruct";
    $result = $this->ocr_alibaba->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }

  public function qwen25_vl_7b()
  {
    $image_path = $this->base_img . "ktp7.jpg";
    $this->load->library('Ocr_alibaba');
    $this->ocr_alibaba->modelName = "qwen2.5-vl-7b-instruct";
    $result = $this->ocr_alibaba->extract_ocr($image_path);
    echo json_encode($result, JSON_PRETTY_PRINT);
  }
}
