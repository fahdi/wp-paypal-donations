<?php
/**
 * Minimal WP_Error stand-in for running the suite without the WordPress test library.
 *
 * @package WP_PayPal_Donations
 */

class WP_Error {

    private $errors = array();

    public function __construct($code = '', $message = '', $data = '') {
        if ($code !== '') {
            $this->errors[$code][] = $message;
        }
    }

    public function get_error_code() {
        $codes = array_keys($this->errors);
        return $codes ? $codes[0] : '';
    }

    public function get_error_message($code = '') {
        if ($code === '') {
            $code = $this->get_error_code();
        }
        return isset($this->errors[$code]) ? $this->errors[$code][0] : '';
    }
}
