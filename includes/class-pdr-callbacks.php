<?php
defined('ABSPATH') || exit;

class PDR_Callbacks {
    public function __construct() {
        add_action('init', [$this, 'register_endpoints']);
        add_action('template_redirect', [$this, 'handle_callback']);
    }
    // - register_endpoints()
    // - handle_callback()
    // - process_successful_payment()
    public function register_endpoints() {
        add_rewrite_rule('^payeer-callback/?$', 'index.php?payeer_callback=1', 'top');
        add_rewrite_tag('%payeer_callback%', '([^&]+)');
    }
    public function handle_callback() {
        if (!get_query_var('payeer_callback')) {
            return;
        }
        $allowed_ips = array('185.71.65.92', '185.71.65.189', '149.202.17.210');
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!in_array($remote_ip, $allowed_ips)) {
            status_header(403);
            exit('Access denied');
        }
        $payeer_nonce = isset($_POST['payeer_nonce']) ? sanitize_text_field(wp_unslash($_POST['payeer_nonce'])) : '';

        if (!wp_verify_nonce($payeer_nonce, 'payeer_donations_nonce')) {
            status_header(403);
            exit('Invalid nonce');
        }
        $callback_secret = get_option('payeer_donations_callback_secret', '');
        $provided_secret = isset($_GET['secret']) ? sanitize_text_field(wp_unslash($_GET['secret'])) : '';
        if (!empty($callback_secret) && $provided_secret !== $callback_secret) {
            status_header(403);
            exit('Invalid secret');
        }
        $required_params = array(
            'm_operation_id', 'm_sign', 'm_operation_ps', 'm_operation_date',
            'm_operation_pay_date', 'm_shop', 'm_orderid', 'm_amount',
            'm_curr', 'm_desc', 'm_status'
        );
        $data = array();
        foreach ($required_params as $param) {
            if (!isset($_POST[$param])) {
                status_header(400);
                exit('Invalid request - missing ' . esc_html($param));
            }
            $data[$param] = sanitize_text_field(wp_unslash($_POST[$param]));
        }
        if (isset($_POST['m_params'])) {
            $data['m_params'] = sanitize_text_field(wp_unslash($_POST['m_params']));
        }
        $secret_key = get_option('payeer_donations_secret_key', '');
        if (empty($secret_key)) {
            status_header(500);
            exit('Plugin not configured');
        }
        $arHash = array(
            $data['m_operation_id'],
            $data['m_operation_ps'],
            $data['m_operation_date'],
            $data['m_operation_pay_date'],
            $data['m_shop'],
            $data['m_orderid'],
            $data['m_amount'],
            $data['m_curr'],
            $data['m_desc'],
            $data['m_status']
        );
        if (isset($data['m_params'])) {
            $arHash[] = $data['m_params'];
        }
        $arHash[] = $secret_key;
        $sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
        if ($data['m_sign'] !== $sign_hash) {
            status_header(403);
            exit('Invalid signature');
        }
        if ($data['m_status'] === 'success') {
            $this->process_successful_payment($data);
            ob_end_clean();
            exit(esc_html($data['m_orderid']) . '|success');
        }
        ob_end_clean();
        exit(esc_html($data['m_orderid']) . '|error');
    }
    private function process_successful_payment($data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_entry = sprintf(
                "[%s] Successful payment: Order ID %s, Amount %s %s",
                current_time('mysql'),
                $data['m_orderid'],
                $data['m_amount'],
                $data['m_curr']
            );
        }
        do_action('payeer_donation_processed', $data);
    }
}
