<?php
defined('ABSPATH') || exit;

class PDR_Frontend {
    public function __construct() {
        add_shortcode('payeer_donate', [$this, 'donation_form_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_payeer_process_donation', [$this, 'process_donation']);
        add_action('wp_ajax_nopriv_payeer_process_donation', [$this, 'process_donation']);
    }
    // - register_assets()
    // - donation_form_shortcode()
    // - process_donation()
    public function register_assets() {
        wp_register_style(
            'payeer-donations-frontend',
            plugins_url('assets/css/payeer-donations.css', dirname(__FILE__)),
            array(),
            PDR_Payeer_Donations::VERSION
        );

        wp_register_script(
            'payeer-donations-frontend',
            plugins_url('assets/js/payeer-donations.js', dirname(__FILE__)),
            array('jquery'),
            PDR_Payeer_Donations::VERSION,
            true
        );

        // Локализация с правильными ключами
        wp_localize_script('payeer-donations-frontend', 'payeerDonations', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('payeer-donations-nonce'),
            'processingText' => __('Processing...', 'payeer-donations'),
            'minAmountError' => __('Minimum donation amount is 0.01. Please increase your donation.', 'payeer-donations'),
            'genericError' => __('Payment processing error. Please try again later.', 'payeer-donations'),
            'timeoutError' => __('Connection timeout. Please check your internet connection.', 'payeer-donations'),
            'securityError' => __('Security error. Please refresh the page.', 'payeer-donations'),
            'serverError' => __('Server error. Please try again later.', 'payeer-donations'),
            'connectionError' => __('Connection error. Please try again.', 'payeer-donations')
        ]);

        // Не забудьте зарегистрировать скрипт!
        wp_enqueue_script('payeer-donations-frontend');
    }
    public function donation_form_shortcode($atts = array()) {
        wp_enqueue_style('payeer-donations-frontend');
        wp_enqueue_script('payeer-donations-frontend');
        $defaults = array(
            'amount' => get_option('payeer_donations_default_amount', '10.00'),
            'currency' => get_option('payeer_donations_currency', 'USD'),
            'title' => __('Support Our Project', 'payeer-donations')
        );
        $args = shortcode_atts($defaults, $atts);
        ob_start();
        ?>
        <div class="payeer-donation-form">
            <h3><?php echo esc_html($args['title']); ?></h3>
            <form id="payeerDonationForm">
                <div class="form-group">
                    <label for="donationAmount"><?php esc_html_e('Amount:', 'payeer-donations'); ?></label>
                    <input type="number" id="donationAmount" name="amount" min="0.01" step="0.01" value="<?php echo esc_attr($args['amount']); ?>" required>
                    <select id="donationCurrency" name="currency">
                        <option value="USD" <?php selected($args['currency'], 'USD'); ?>>USD</option>
                        <option value="RUB" <?php selected($args['currency'], 'RUB'); ?>>RUB</option>
                        <option value="EUR" <?php selected($args['currency'], 'EUR'); ?>>EUR</option>
                    </select>
                </div>
                <div class="form-group">
                  <!--  <label for="donationMessage"><?php esc_html_e('Message (optional):', 'payeer-donations'); ?></label>
                    <textarea id="donationMessage" name="message" rows="3"></textarea>
                </div>-->
        <!--checkbox for adminpanel -->
        <?php if (get_option('payeer_donations_enable_messages', true)) : ?>
        <div class="form-group">
            <label for="donationMessage"><?php esc_html_e('Message (optional):', 'payeer-donations'); ?></label>
            <textarea id="donationMessage" name="message" rows="3"></textarea>
        </div>
        <?php endif; ?>

                <button type="submit" class="donate-button"><?php esc_html_e('Donate', 'payeer-donations'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    public function process_donation() {
        check_ajax_referer('payeer-donations-nonce', '_ajax_nonce');
        $merchant_id = get_option('payeer_donations_merchant_id', '');
        $secret_key = get_option('payeer_donations_secret_key', '');
        if (empty($merchant_id)) {
            wp_send_json_error(array(
                'message' => __('Merchant ID is not configured. Please set it in plugin settings.', 'payeer-donations')
            ));
            return;
        }
        if (empty($secret_key)) {
            wp_send_json_error(array(
                'message' => __('Secret Key is not configured. Please set it in plugin settings.', 'payeer-donations')
            ));
            return;
        }
        if (!isset($_POST['amount'])) {
            wp_send_json_error(array(
                'message' => __('Amount field is required. Please enter donation amount.', 'payeer-donations')
            ));
            return;
        }
        $amount = floatval($_POST['amount']);
        if ($amount <= 0) {
            wp_send_json_error(array(
                'message' => __('Amount must be greater than zero. Please enter valid amount.', 'payeer-donations')
            ));
            return;
        }
        if ($amount < 0.01) {
            wp_send_json_error(array(
                'message' => __('Minimum donation amount is 0.01. Please increase your donation.', 'payeer-donations')
            ));
            return;
        }
        if (!isset($_POST['currency'])) {
            wp_send_json_error(array(
                'message' => __('Currency is not selected. Please choose payment currency.', 'payeer-donations')
            ));
            return;
        }
        $currency = sanitize_text_field(wp_unslash($_POST['currency']));
        $allowed_currencies = array('USD', 'EUR', 'RUB');
        if (!in_array($currency, $allowed_currencies, true)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: %s is the selected currency */
                    __('Invalid currency selected (%s). Allowed currencies: USD, EUR, RUB.', 'payeer-donations'),
                    esc_html($currency)
                )
            ));
            return;
        }
        $message = '';
        if (get_option('payeer_donations_enable_messages', false) && isset($_POST['message'])) {
            $message = sanitize_textarea_field(wp_unslash($_POST['message']));
        }
        $order_id = uniqid();
        $description = $message ?: __('Donation', 'payeer-donations');
        $signature = strtoupper(hash('sha256', implode(':', array(
            $merchant_id,
            $order_id,
            number_format($amount, 2, '.', ''),
            $currency,
            base64_encode($description),
            $secret_key
        ))));
        wp_send_json_success(array(
            'form_data' => array(
                'm_shop' => $merchant_id,
                'm_orderid' => $order_id,
                'm_amount' => number_format($amount, 2, '.', ''),
                'm_curr' => $currency,
                'm_desc' => base64_encode($description),
                'm_sign' => $signature
            ),
            'message' => __('Redirecting to Payeer...', 'payeer-donations')
        ));
    }
}
