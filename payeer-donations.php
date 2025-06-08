<?php
/**
 * Plugin Name: Payeer Donations
 * Plugin URI: https://wordpress.org/plugins/payeer-donations/
 * Description: Accept donations via Payeer payment system.
 * Version: 1.0.11
 * Author: sergeyvladimirovich
 * Author URI: https://anna-ivanovna.ru/
 * License: GPLv2 or later
 * Text Domain: payeer-donations
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/includes/class-pdr-admin.php';
require_once __DIR__ . '/includes/class-pdr-frontend.php';
require_once __DIR__ . '/includes/class-pdr-callbacks.php';

final class PDR_Payeer_Donations {
    private static $instance;
    public $admin;
    public $frontend;
    public $callbacks;

    const VERSION = '1.0.11';



    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init() {
        // Initialize components
        $this->admin = new PDR_Admin();
        $this->frontend = new PDR_Frontend();
        $this->callbacks = new PDR_Callbacks();

        // Activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        // Create donate page on activation
        if (!get_page_by_path('donate')) {
            $page_id = wp_insert_post([
                'post_title' => __('Donate', 'payeer-donations'),
                'post_name' => 'donate',
                'post_content' => '[payeer_donate]',
                'post_status' => 'publish',
                'post_type' => 'page'
            ]);
            update_option('payeer_donations_page_id', $page_id);
        }
        flush_rewrite_rules();
    }
}

PDR_Payeer_Donations::instance();
