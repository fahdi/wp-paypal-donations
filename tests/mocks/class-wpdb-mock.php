<?php
/**
 * Minimal wpdb stand-in so tests can create PHPUnit mocks of it without WordPress.
 *
 * @package WP_PayPal_Donations
 */

if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $insert_id = 0;

        public function insert($table, $data, $format = null) { return 1; }
        public function update($table, $data, $where, $format = null, $where_format = null) { return 1; }
        public function get_row($query, $output = OBJECT, $y = 0) { return null; }
        public function get_results($query, $output = OBJECT) { return array(); }
        public function get_var($query, $x = 0, $y = 0) { return null; }
        public function query($query) { return 0; }
        public function prepare($query, ...$args) { return $query; }
        public function esc_like($text) { return $text; }
        public function get_charset_collate() { return ''; }
    }
}
