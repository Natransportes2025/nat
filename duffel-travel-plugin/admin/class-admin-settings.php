<?php
if (!defined('ABSPATH')) exit;

class Duffel_Travel_Settings {

    private static $instance = null;
    private $settings_page;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_admin_menu() {
        $this->settings_page = add_menu_page(
            'Duffel Travel Settings',
            'Duffel Travel',
            'manage_options',
            'duffel-travel',
            [$this, 'render_settings_page'],
            'dashicons-airplane',
            76
        );
    }

    public function register_settings() {
        register_setting('duffel_travel_settings', 'duffel_travel_options');

        add_settings_section(
            'api_settings',
            'API Configuration',
            null,
            'duffel-travel'
        );

        add_settings_field(
            'api_key',
            'Duffel API Key',
            [$this, 'render_api_key_field'],
            'duffel-travel',
            'api_settings'
        );
    }

    public function render_api_key_field() {
        $options = get_option('duffel_travel_options');
        $api_key = $options['api_key'] ?? '';
        ?>
        <input type="password" 
               id="duffel_api_key" 
               name="duffel_travel_options[api_key]" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text">
        <button type="button" id="toggle_api_key" class="button button-secondary">
            <span class="dashicons dashicons-visibility"></span>
        </button>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Duffel Travel Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('duffel_travel_settings');
                do_settings_sections('duffel-travel');
                submit_button('Save Settings');
                ?>
            </form>
            <button id="test_api_connection" class="button button-primary">
                Test API Connection
            </button>
            <span id="test_api_spinner" class="spinner" style="float: none;"></span>
            <div id="test_api_result"></div>
        </div>
        <?php
    }

    public function enqueue_assets($hook) {
        if ($hook !== $this->settings_page) return;

        wp_enqueue_style(
            'duffel-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css'
        );

        wp_enqueue_script(
            'duffel-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            ['jquery'],
            false,
            true
        );

        wp_localize_script('duffel-admin-js', 'duffel_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('duffel_admin_nonce')
        ]);
    }

    public static function get_api_key() {
        $options = get_option('duffel_travel_options');
        return $options['api_key'] ?? '';
    }
}

// Em qualquer arquivo do seu plugin:
$api_key = Duffel_Travel_Settings::get_api_key();

if (empty($api_key)) {
    // Tratar o caso quando a chave não está configurada
} else {
    // Usar a chave para fazer requisições
}

// Initialize
Duffel_Travel_Settings::get_instance();