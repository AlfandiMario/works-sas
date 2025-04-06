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
     * @var array SQL injection patterns to detect
     */
    private $sqlInjectionPatterns = [
        // Previous patterns
        '/\b(union\s+all|union\s+select|insert\s+into|drop\s+table|exec\s+xp|exec\s+sp)\b/i',
        '/\b(or\s+\d+=\d+|or\s+\'.*\'=\'.*\'|or\s+[\'"].*[\'"]=[\'"].*[\'"])\b/i',
        '/(\-\-\s*$|\/\*|\*\/|#\s*$)/',
        '/\b(concat|char|cast|convert|decode|encode|eval|exec)\s*\(/i',
        '/\b(xp_cmdshell|xp_regread|sp_password|sp_executesql)\b/i',

        // Enhanced time-based injection patterns
        '/\b(?:and|or|if)\s*\(?[\s\w]*(?:sleep|delay|benchmark|wait)\s*[\(\s]*\d+[^\w\s]*\)?/i',

        // Enhanced conditional patterns
        '/\b(?:and|or|if)\s*\([^)]*(?:sleep|delay|benchmark|wait)[^)]*\)/i',

        // Case-insensitive function detection (including spaces and parentheses)
        '/\b(?:s[\s]*l[\s]*e[\s]*e[\s]*p|d[\s]*e[\s]*l[\s]*a[\s]*y|b[\s]*e[\s]*n[\s]*c[\s]*h[\s]*m[\s]*a[\s]*r[\s]*k)\s*\(/i',

        // Additional patterns for IF statements
        '/\b(?:if)\s*\(\s*(?:sleep|delay|benchmark|wait)\s*\(\s*\d+\s*\)\s*,\s*\d+\s*,\s*\d+\s*\)/i',

        // Pattern specifically for your case
        '/\band\s+if\s*\(\s*sleep\s*\(\s*\d+\s*\)\s*,\s*\d+\s*,\s*\d+\s*\)/i',

        // Previous patterns continue
        '/\b(0x[0-9a-f]+)\b/i',
        '/(\s+and\s+\d+=\d+|\s+and\s+\'.*\'=\'.*\'|\s+and\s+[\'"].*[\'"]=[\'"].*[\'"])/i'
    ];

    /**
     * @var array Suspicious characters that might indicate SQL injection
     */
    private $suspiciousChars = [
        ';',        // Statement terminator
        '\'',       // Single quote
        '"',        // Double quote
        '`',        // Backtick
        '\\',       // Backslash
        '\x00',     // Null byte
        '\x1a',     // Ctrl+Z
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
     * Get raw input stream
     * 
     * @return string
     */
    // Harusnya override raw_input_stream tapi takut kalau merubah banyak fungsi yang pakai input->raw_input_stream
    // public function getRawInput()
    public function raw_input_stream()
    {
        $input = file_get_contents('php://input');
        return $this->sanitizeInput($input);
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
        // Return empty string for null input
        if ($input === null) {
            return '';
        }

        $clean = trim($input);

        // Check for SQL injection attempts
        if ($this->containsSQLInjection($clean)) {
            // log_message('warning', 'Potential SQL injection detected: ' . $clean);
            return '';
        }

        // Basic sanitization
        $clean = filter_var($clean, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        // Remove null bytes
        $clean = str_replace(chr(0), '', $clean);

        // Remove control characters
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);

        // Convert special characters to HTML entities
        $clean = htmlspecialchars(
            $clean,
            $this->config['html_entities_flags'],
            $this->config['encoding']
        );

        return $clean;
    }

    /**
     * Check if a string contains SQL injection attempts
     *
     * @param string $input String to check
     * @return bool
     */
    protected function containsSQLInjection(string $input): bool
    {
        // Normalize input for better detection
        $normalizedInput = $this->normalizeInput($input);

        // Check original and normalized input against patterns
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input) || preg_match($pattern, $normalizedInput)) {
                return true;
            }
        }

        // Check for suspicious characters
        foreach ($this->suspiciousChars as $char) {
            if (strpos($input, $char) !== false) {
                return true;
            }
        }

        // Additional check specifically for time-based attacks
        if ($this->containsTimeBased($normalizedInput)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize input for consistent checking
     *
     * @param string $input
     * @return string
     */
    private function normalizeInput(string $input): string
    {
        // Convert to lowercase
        $normalized = strtolower($input);

        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Remove url encoding
        $normalized = urldecode($normalized);

        // Replace common obfuscation patterns
        $replacements = [
            '/s[\s]*l[\s]*e[\s]*e[\s]*p/' => 'sleep',
            '/i[\s]*f/' => 'if',
            '/a[\s]*n[\s]*d/' => 'and',
            '/o[\s]*r/' => 'or',
            '/b[\s]*e[\s]*n[\s]*c[\s]*h[\s]*m[\s]*a[\s]*r[\s]*k/' => 'benchmark',
            '/w[\s]*a[\s]*i[\s]*t[\s]*f[\s]*o[\s]*r/' => 'waitfor'
        ];

        foreach ($replacements as $pattern => $replacement) {
            $normalized = preg_replace($pattern, $replacement, $normalized);
        }

        return $normalized;
    }

    /**
     * Specific check for time-based SQL injection attempts
     *
     * @param string $input
     * @return bool
     */
    private function containsTimeBased(string $input): bool
    {
        $timeBased = [
            // Detect IF(SLEEP()) pattern with variations
            '/if\s*\(\s*sleep\s*\(\s*\d+\s*\)\s*,\s*\d+\s*,\s*\d+\s*\)/i',

            // Detect AND IF(SLEEP()) pattern
            '/and\s+if\s*\(\s*sleep\s*\(\s*\d+\s*\)\s*,\s*\d+\s*,\s*\d+\s*\)/i',

            // Additional time-based patterns
            '/benchmark\s*\(\s*\d+\s*,\s*[^)]+\)/i',
            '/waitfor\s+delay\s+\'\d+\:\d+\:\d+\'/i',
            '/pg_sleep\s*\(\s*\d+\s*\)/i'
        ];

        foreach ($timeBased as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}
