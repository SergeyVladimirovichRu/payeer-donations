<?php
defined('ABSPATH') || exit;
class PDR_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links']);
    }
    // - add_admin_menu()
    // - register_settings()
    // - settings_page()
    // - Все callback-функции для полей (merchant_id_callback и др.)
    // - add_action_links()
    public function add_admin_menu() {
        add_options_page(
            __('Payeer Donations Settings', 'payeer-donations'),
            __('Payeer Donations', 'payeer-donations'),
            'manage_options',
            'payeer-donations',
            array($this, 'settings_page')
        );
    }
    public function register_settings() {
        if (isset($_POST['_payeer_admin_nonce'])) {
        check_admin_referer('payeer_admin_save', '_payeer_admin_nonce');
       }
       register_setting('payeer_donations_settings', 'payeer_donations_merchant_id', 'sanitize_text_field');
        register_setting('payeer_donations_settings', 'payeer_donations_secret_key', 'sanitize_text_field');
        register_setting('payeer_donations_settings', 'payeer_donations_default_amount', 'floatval');
        register_setting('payeer_donations_settings', 'payeer_donations_currency', 'sanitize_text_field');
        register_setting('payeer_donations_settings', 'payeer_donations_callback_secret', 'sanitize_text_field');
            register_setting(
        'payeer_donations_settings',
        'payeer_donations_enable_messages',
        array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        )
    );

        add_settings_section(
            'payeer_donations_main',
            __('Payeer Settings', 'payeer-donations'),
            array($this, 'settings_section_callback'),
            'payeer-donations'
        );

        add_settings_field(
            'merchant_id',
            __('Merchant ID', 'payeer-donations'),
            array($this, 'merchant_id_callback'),
            'payeer-donations',
            'payeer_donations_main'
        );

        add_settings_field(
            'secret_key',
            __('Secret Key', 'payeer-donations'),
            array($this, 'secret_key_callback'),
            'payeer-donations',
            'payeer_donations_main'
        );

        add_settings_field(
            'default_amount',
            __('Default Amount', 'payeer-donations'),
            array($this, 'default_amount_callback'),
            'payeer-donations',
            'payeer_donations_main'
        );

        add_settings_field(
            'currency',
            __('Default Currency', 'payeer-donations'),
            array($this, 'currency_callback'),
            'payeer-donations',
            'payeer_donations_main'
        );
        add_settings_field(
            'callback_secret',
            __('Callback Secret Key', 'payeer-donations'),
            array($this, 'callback_secret_callback'),
            'payeer-donations',
            'payeer_donations_main'
        );
        add_settings_field(
            'enable_messages',
            __('Enable Donation Messages', 'payeer-donations'),
            array($this, 'enable_messages_callback'),
            'payeer-donations',
            'payeer_donations_main'
        );
    }
 /*   public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="payeer-settings-container">
                <div class="payeer-settings-main">
                    <form action="options.php" method="post">
                        <?php
                        wp_nonce_field('payeer_admin_save', '_payeer_admin_nonce');
                        settings_fields('payeer_donations_settings');
                        do_settings_sections('payeer-donations');
                        submit_button(__('Save Settings', 'payeer-donations'));
                        ?>
                    </form>
                </div>

                <div class="payeer-settings-sidebar">
                    <div class="payeer-info-box">
                        <h3><?php esc_html_e('Plugin Information', 'payeer-donations'); ?></h3>
                        <p><strong><?php esc_html_e('Version:', 'payeer-donations'); ?></strong> <?php echo esc_html(PDR_Payeer_Donations::VERSION); ?></p>
                        <p><strong><?php esc_html_e('Need help?', 'payeer-donations'); ?></strong><br>
                        <a href="https://payeer.com/merchant/en/help" target="_blank"><?php esc_html_e('Payeer Documentation', 'payeer-donations'); ?></a></p>
                    </div>

                    <div class="payeer-info-box">
                        <h3><?php esc_html_e('Shortcode', 'payeer-donations'); ?></h3>
                        <p><?php esc_html_e('Use this shortcode to display the donation form:', 'payeer-donations'); ?></p>
                        <code>[payeer_donate]</code>
                        <code>[payeer_donate amount="50" currency="USD"]</code>
                        <code>[payeer_donate amount="1000" currency="RUB" title="Support the project"]</code>

                        <h4><?php esc_html_e('Options:', 'payeer-donations'); ?></h4>
                        <ul>
                            <li><code>amount="50.00"</code></li>
                            <li><code>currency="RUB"</code></li>
                            <li><code>title="Custom Title"</code></li>
                        </ul>
                    </div>

                                    <!-- Добавляем блок с инструкцией -->
                <div class="payeer-info-box payeer-instructions">
                    <h3><?php esc_html_e('Quick Guide', 'payeer-donations'); ?></h3>
                    <ol>
                        <li><strong><?php esc_html_e('Merchant ID:', 'payeer-donations'); ?></strong>
                            <?php esc_html_e('Get this from your Payeer merchant account.Where to find: In your Payeer account → "Merchant" section → "API settings".Format: 10-digit number (for example: 1234567890).Important: Do not share this identifier with anyone.', 'payeer-donations'); ?></li>
                        <li><strong><?php esc_html_e('Secret Key:', 'payeer-donations'); ?></strong>
                            <?php esc_html_e('Generate in Payeer merchant settings (keep it secure!).Where to find: Same place as Merchant ID, "Generate key" button.Requirements: At least 16 characters, letters + numbers + special characters.Storage: Recommended to change every 3 months.', 'payeer-donations'); ?></li>
                        <li><strong><?php esc_html_e('Callback Secret:', 'payeer-donations'); ?></strong>
                            <?php esc_html_e('Add any password to protect callbacks.Purpose: Password for confirmation of callback requests.Example: MySecurePassword_2024!.Where to specify: In the Payeer merchant settings in the "Secret Key" field.', 'payeer-donations'); ?></li>
                        <li><strong><?php esc_html_e('Default Amount:', 'payeer-donations'); ?></strong>
                            <?php esc_html_e('Suggested donation sum (e.g. 10.00).Minimum value: 0.10 (10 cents/rubles).Tip: Set the average donation amount.', 'payeer-donations'); ?></li>
                        <li><strong><?php esc_html_e('Currency:', 'payeer-donations'); ?></strong>
                            <?php esc_html_e('Base currency for donations.Available options:Fiat: USD, EUR, RUB, UAH, KZT, BYN. Cryptocurrencies: BTC, ETH, USDT.Important: The currency must be active in your Payeer account.', 'payeer-donations'); ?></li>
                    </ol>
                    <p><strong><?php esc_html_e('Shortcode:', 'payeer-donations'); ?></strong>
                        <code>[payeer_donate]</code> <?php esc_html_e('or with parameters:', 'payeer-donations'); ?>
                        <code>[payeer_donate amount="50" currency="EUR"]</code></p>
                </div>

                <div class="payeer-info-box">
                    <h3><?php esc_html_e('Need Help?', 'payeer-donations'); ?></h3>
                    <p><?php esc_html_e('For detailed documentation visit:', 'payeer-donations'); ?>
                    <a href="https://payeer.com/merchant/en/help" target="_blank">Payeer Merchant Docs</a></p>
                </div>

                </div>
            </div>
        </div>


        <?php
    }*/
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="payeer-settings-container" style="display: flex; gap: 20px;">
                <div class="payeer-settings-main" style="flex: 1;">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('payeer_donations_settings');
                        do_settings_sections('payeer-donations');
                        submit_button(__('Save Settings', 'payeer-donations'));
                        ?>
                    </form>
                </div>

                <div class="payeer-settings-sidebar" style="width: 300px;">
                    <div class="payeer-info-box">
                        <h3><?php esc_html_e('Plugin Information', 'payeer-donations'); ?></h3>
                        <p><strong><?php esc_html_e('Version:', 'payeer-donations'); ?></strong> <?php echo esc_html(PDR_Payeer_Donations::VERSION); ?></p>
                        <p><strong><?php esc_html_e('Need help?', 'payeer-donations'); ?></strong><br>
                        <a href="https://payeer.com/merchant/en/help" target="_blank"><?php esc_html_e('Payeer Documentation', 'payeer-donations'); ?></a></p>
                    </div>

                    <div class="payeer-info-box">
                        <h3><?php esc_html_e('Shortcode', 'payeer-donations'); ?></h3>
                        <p><?php esc_html_e('Use this shortcode to display the donation form:', 'payeer-donations'); ?></p>
                        <code>[payeer_donate]</code>
                        <code>[payeer_donate amount="50" currency="USD"]</code>
                        <code>[payeer_donate amount="1000" currency="RUB" title="Support the project"]</code>

                        <h4><?php esc_html_e('Options:', 'payeer-donations'); ?></h4>
                        <ul>
                            <li><code>amount="50.00"</code></li>
                            <li><code>currency="RUB"</code></li>
                            <li><code>title="Custom Title"</code></li>
                        </ul>
                    </div>

                    <div class="payeer-info-box payeer-instructions">
                        <h3><?php esc_html_e('Quick Guide', 'payeer-donations'); ?></h3>
                        <ol>
                            <li><strong><?php esc_html_e('Merchant ID:', 'payeer-donations'); ?></strong>
                                <?php esc_html_e('Get this from your Payeer merchant account.', 'payeer-donations'); ?></li>
                            <li><strong><?php esc_html_e('Secret Key:', 'payeer-donations'); ?></strong>
                                <?php esc_html_e('Generate in Payeer merchant settings (keep it secure!).', 'payeer-donations'); ?></li>
                            <li><strong><?php esc_html_e('Callback Secret:', 'payeer-donations'); ?></strong>
                                <?php esc_html_e('Add any password to protect callbacks.', 'payeer-donations'); ?></li>
                            <li><strong><?php esc_html_e('Default Amount:', 'payeer-donations'); ?></strong>
                                <?php esc_html_e('Suggested donation sum (e.g. 10.00).', 'payeer-donations'); ?></li>
                            <li><strong><?php esc_html_e('Currency:', 'payeer-donations'); ?></strong>
                                <?php esc_html_e('Base currency for donations.', 'payeer-donations'); ?></li>
                        </ol>
                        <p><strong><?php esc_html_e('Shortcode:', 'payeer-donations'); ?></strong>
                            <code>[payeer_donate]</code> <?php esc_html_e('or with parameters:', 'payeer-donations'); ?>
                            <code>[payeer_donate amount="50" currency="EUR"]</code></p>
                    </div>

                    <div class="payeer-info-box">
                        <h3><?php esc_html_e('Need Help?', 'payeer-donations'); ?></h3>
                        <p><?php esc_html_e('For detailed documentation visit:', 'payeer-donations'); ?>
                        <a href="https://payeer.com/merchant/en/help" target="_blank">Payeer Merchant Docs</a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    public function callback_secret_callback() {
    $value = get_option('payeer_donations_callback_secret', '');
    printf(
        '<input type="password" id="callback_secret" name="payeer_donations_callback_secret" value="%s" class="regular-text" />
        <p class="description">%s</p>',
        esc_attr($value),
        esc_html__('Add extra security layer for callbacks', 'payeer-donations')
    );
    }
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=payeer-donations'),
            __('Settings', 'payeer-donations')
        );
        array_unshift($links, $settings_link);
        return $links;
    }
    public function settings_section_callback() {
        echo '<p>' . esc_html__('Configure your Payeer merchant account settings below.', 'payeer-donations') . '</p>';


        $callback_url = home_url('/payeer-callback/');
        echo '<div class="notice notice-info"><p>';
        echo '<strong>' . esc_html__('Callback URL:', 'payeer-donations') . '</strong> ';
        echo '<code>' . esc_url($callback_url) . '</code><br>';

        $secret = get_option('payeer_donations_callback_secret');
        if ($secret) {
            echo '<strong>' . esc_html__('Secure callback URL:', 'payeer-donations') . '</strong> ';
            echo '<code>' . esc_url(add_query_arg('secret', $secret, $callback_url)) . '</code>';
        }
        echo '</p></div>';
    }
    public function merchant_id_callback() {
        $value = get_option('payeer_donations_merchant_id', '');
        printf(
            '<input type="text" id="merchant_id" name="payeer_donations_merchant_id" value="%s" class="regular-text" required />
            <p class="description">%s</p>',
            esc_attr($value),
            esc_html__('Enter your Payeer Merchant ID', 'payeer-donations')
        );
    }
    public function secret_key_callback() {
        $value = get_option('payeer_donations_secret_key', '');
        printf(
            '<input type="password" id="secret_key" name="payeer_donations_secret_key" value="%s" class="regular-text" required />
            <p class="description">%s</p>',
            esc_attr($value),
            esc_html__('Enter your Payeer Secret Key', 'payeer-donations')
        );
    }
    public function default_amount_callback() {
        $value = get_option('payeer_donations_default_amount', '10.00');
        printf(
            '<input type="number" id="default_amount" name="payeer_donations_default_amount" value="%s" min="0.01" step="0.01" />
            <p class="description">%s</p>',
            esc_attr($value),
            esc_html__('Default donation amount', 'payeer-donations')
        );
    }
    public function currency_callback() {
        $value = get_option('payeer_donations_currency', 'USD');
        $currencies = array(
            'USD' => __('US Dollar', 'payeer-donations'),
            'RUB' => __('Russian Ruble', 'payeer-donations'),
            'EUR' => __('Euro', 'payeer-donations'),
            'BTC' => __('Bitcoin', 'payeer-donations'),
            'ETH' => __('Ethereum', 'payeer-donations'),
            'UAH' => __('Ukrainian Hryvnia', 'payeer-donations'),
            'KZT' => __('Kazakhstani Tenge', 'payeer-donations'),
            'BYN' => __('Belarusian Ruble', 'payeer-donations')
        );

        echo '<select id="currency" name="payeer_donations_currency" class="regular-text">';
        foreach ($currencies as $code => $name) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($code),
                selected($value, $code, false),
                esc_html($name)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select default currency for donations', 'payeer-donations') . '</p>';
    }
    public function enable_messages_callback() {
        $enabled = get_option('payeer_donations_enable_messages', true);
        echo '<label><input type="checkbox" name="payeer_donations_enable_messages" value="1" ' . checked(1, $enabled, false) . '> '
            . esc_html('Allow donors to include messages', 'payeer-donations') . '</label>';
    }
}
