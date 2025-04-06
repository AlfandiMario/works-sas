<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* 
    FUNGSI INI OVERRIDING FUNGSI INPUT BAWAAN CI 
    UNTUK MEMBERIKAN SANITASI TAMBAHAN TERHADAP XSS DAN SQLi
*/

class MY_Input extends CI_Input
{
    protected $CI;
    public function __construct()
    {
        parent::__construct();
    }

    public function get($index = NULL, $xss_clean = TRUE)
    {
        $value = parent::get($index, $xss_clean);
        // echo 'Nilai get(xss true): ' . $value . "\n";

        $this->CI = &get_instance(); // Get the CodeIgniter super object

        if (is_string($value)) {
            // echo "Nilai asli: $value \n";
            $value = trim($value); // Remove extra spaces
            $value = filter_var($value, FILTER_SANITIZE_STRING); // Remove HTML tags
            $value = $this->clean_input($value); // Custom sanitization
        } elseif (is_array($value)) {
            array_walk_recursive($value, function (&$item) {
                if (is_string($item)) {
                    // echo "Nilai asli (dalam ARRAY): $value \n";
                    $item = trim($item);
                    $item = filter_var($item, FILTER_SANITIZE_STRING);
                    $item = $this->clean_input($item);
                }
            });
        }
        // echo "Nilai yang sudah diolah: $value \n";

        return $value;
    }

    public function post($index = NULL, $xss_clean = TRUE)
    {
        $value = parent::post($index, $xss_clean);

        $this->CI = &get_instance(); // Get the CodeIgniter super object

        // echo "Ambil dari MY Input Nih \n";
        if (is_string($value)) {
            // echo "Nilai asli: $value \n";
            $value = trim($value); // Remove extra spaces
            $value = filter_var($value, FILTER_SANITIZE_STRING); // Remove HTML tags
            $value = $this->clean_input($value); // Custom sanitization
        } elseif (is_array($value)) {
            array_walk_recursive($value, function (&$item) {
                if (is_string($item)) {
                    // echo "Nilai asli (dalam ARRAY): $value \n";
                    $item = trim($item);
                    $item = filter_var($item, FILTER_SANITIZE_STRING);
                    $item = $this->clean_input($item);
                }
            });
        }

        // echo "Nilai yang sudah diolah: $value \n";
        return $value;
    }

    private function clean_input($data)
    {
        $data = trim($data); // Remove whitespace      
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
        // $data = strip_tags($data); // Remove any HTML tags

        $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $data = str_replace(chr(0), '', $data);         // Remove null bytes

        // Escape special characters for SQL if database connection exists
        if ($this->CI->db_onedev) {
            $data = $this->CI->db_onedev->escape($data);
        }

        return $data;
    }
}
