<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Enhanced Input Class
 * 
 * Extends CodeIgniter's native Input class to provide additional XSS and SQL injection protection.
 * 
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Input
 * @author      Your Name
 */
class MY_Input extends CI_Input
{
    /**
     * @var CI_Controller CodeIgniter instance
     */
    protected $CI;

    /**
     * @var array Configuration for input sanitization
     */
    private $config = [
        'encoding' => 'UTF-8',
        'html_entities_flags' => ENT_QUOTES,
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get sanitized GET data
     *
     * @param string|null $index Index for retrieving the GET data
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return mixed
     */
    public function get($index = null, $xss_clean = true)
    {
        return $this->sanitizeInput(
            parent::get($index, $xss_clean)
        );
    }

    /**
     * Get sanitized POST data
     *
     * @param string|null $index Index for retrieving the POST data
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return mixed
     */
    public function post($index = null, $xss_clean = true)
    {
        return $this->sanitizeInput(
            parent::post($index, $xss_clean)
        );
    }

    /**
     * Sanitize input data
     *
     * @param mixed $input Input data to sanitize
     * @return mixed
     */
    protected function sanitizeInput($input)
    {
        if (is_string($input)) {
            return $this->sanitizeString($input);
        }

        if (is_array($input)) {
            return $this->sanitizeArray($input);
        }

        return $input;
    }

    /**
     * Sanitize an array recursively
     *
     * @param array $input Array to sanitize
     * @return array
     */
    protected function sanitizeArray(array $input): array
    {
        return array_map(
            function ($item) {
                return $this->sanitizeInput($item);
            },
            $input
        );
    }

    /**
     * Sanitize a string value
     *
     * @param string $input String to sanitize
     * @return string
     */
    protected function sanitizeString(string $input): string
    {
        $clean = trim($input);

        // Basic sanitization
        $clean = filter_var($clean, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        // Remove null bytes
        $clean = str_replace(chr(0), '', $clean);

        // Convert special characters to HTML entities
        $clean = htmlspecialchars(
            $clean,
            $this->config['html_entities_flags'],
            $this->config['encoding']
        );

        // Database escape if connection exists
        $this->CI = &get_instance();
        if (isset($this->CI->db_onedev) && $this->CI->db_onedev) {
            $clean = $this->CI->db_onedev->escape($clean);
        }

        return $clean;
    }
}
