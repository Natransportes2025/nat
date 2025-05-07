<?php

namespace DuffelTravel\Admin;

// Se não usar namespace:
// class Duffel_Travel_Admin_Settings {

class AdminSettings {

    private $plugin_name;
    private $version;
    private $option_group = 'duffel_travel_options_group'; // Grupo de opções para register_setting
    private $options_name = 'duffel_travel_settings'; // Nome da opção no banco de dados

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Adiciona a página de menu do plugin no admin.
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __( 'Duffel Travel Settings', 'duffel-travel' ), // Título da Página
            __( 'Duffel Travel', 'duffel-travel' ),        // Título do Menu
            'manage_options',                              // Capacidade
            $this->plugin_name . '-settings',              // Slug do Menu
            array( $this, 'display_plugin_setup_page' ),   // Função de callback para renderizar a página
            'dashicons-airplane',                          // Ícone (escolha um de https://developer.wordpress.org/resource/dashicons/)
            75                                             // Posição
        );
    }

    /**
     * Regista as configurações do plugin.
     */
    public function register_settings() {
        register_setting(
            $this->option_group,                          // Grupo de opções (deve corresponder ao usado em settings_fields())
            $this->options_name,                          // Nome da opção
            array( $this, 'sanitize_settings' )           // Callback de sanitização (opcional, mas recomendado)
        );

        // Secção de Configurações da API
        add_settings_section(
            'duffel_travel_api_section',                  // ID da secção
            __( 'API Settings', 'duffel-travel' ),        // Título da secção
            array( $this, 'api_section_callback' ),       // Callback para renderizar a descrição da secção
            $this->plugin_name . '-settings'              // Página onde a secção será exibida
        );

        // Campo API Key
        add_settings_field(
            'duffel_api_key',                             // ID do campo
            __( 'Duffel API Key', 'duffel-travel' ),      // Título do campo
            array( $this, 'api_key_field_render' ),       // Callback para renderizar o campo
            $this->plugin_name . '-settings',              // Página
            'duffel_travel_api_section'                   // Secção
        );
        
        // Campo Ambiente (Test/Live)
        add_settings_field(
            'duffel_api_environment',                     // ID do campo
            __( 'API Environment', 'duffel-travel' ),     // Título do campo
            array( $this, 'api_environment_field_render' ), // Callback para renderizar o campo
            $this->plugin_name . '-settings',              // Página
            'duffel_travel_api_section'                   // Secção
        );
    }

    /**
     * Callback para a descrição da secção de API.
     */
    public function api_section_callback() {
        echo '<p>' . __( 'Enter your Duffel API credentials below.', 'duffel-travel' ) . '</p>';
        echo '<p>' . sprintf(
            __( 'You can find your API key in your %sDuffel dashboard%s.', 'duffel-travel' ),
            '<a href="https://app.duffel.com/tokens" target="_blank">',
            '</a>'
        ) . '</p>';
    }

    /**
     * Renderiza o campo para a API Key.
     */
    public function api_key_field_render() {
        $options = get_option( $this->options_name );
        $api_key = isset( $options['duffel_api_key'] ) ? esc_attr( $options['duffel_api_key'] ) : '';
        ?>
        <input type='text' name='<?php echo $this->options_name; ?>[duffel_api_key]' value='<?php echo $api_key; ?>' class='regular-text'>
        <?php
    }
    
    /**
     * Renderiza o campo para o Ambiente da API.
     */
    public function api_environment_field_render() {
        $options = get_option( $this->options_name );
        $environment = isset( $options['duffel_api_environment'] ) ? $options['duffel_api_environment'] : 'test'; // Padrão para 'test'
        ?>
        <select name='<?php echo $this->options_name; ?>[duffel_api_environment]'>
            <option value='test' <?php selected( $environment, 'test' ); ?>><?php _e( 'Test', 'duffel-travel' ); ?></option>
            <option value='live' <?php selected( $environment, 'live' ); ?>><?php _e( 'Live', 'duffel-travel' ); ?></option>
        </select>
        <p class="description"><?php _e('Select if you are using Test or Live API credentials.', 'duffel-travel'); ?></p>
        <?php
    }

    /**
     * Sanitiza os dados das configurações antes de salvar.
     * @param array $input Os dados brutos do formulário.
     * @return array Os dados sanitizados.
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = array();
        if ( isset( $input['duffel_api_key'] ) ) {
            $sanitized_input['duffel_api_key'] = sanitize_text_field( $input['duffel_api_key'] );
        }
        if ( isset( $input['duffel_api_environment'] ) ) {
            $env = sanitize_text_field( $input['duffel_api_environment'] );
            $sanitized_input['duffel_api_environment'] = ( $env === 'live' ) ? 'live' : 'test';
        }
        return $sanitized_input;
    }

    /**
     * Renderiza a página de configurações do plugin.
     */
    public function display_plugin_setup_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_group ); // Imprime nonces, action e option_page
                do_settings_sections( $this->plugin_name . '-settings' ); // Imprime as secções e campos
                submit_button( __( 'Save Settings', 'duffel-travel' ) );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Método estático para obter as opções do plugin.
     * Útil para acesso fácil em outras partes do plugin.
     */
    public static function get_settings() {
        return get_option( 'duffel_travel_settings', array() ); // Retorna array vazio se não existir
    }

    public static function get_api_key() {
        $settings = self::get_settings();
        return isset( $settings['duffel_api_key'] ) ? $settings['duffel_api_key'] : null;
    }

    public static function get_api_environment() {
        $settings = self::get_settings();
        return isset( $settings['duffel_api_environment'] ) ? $settings['duffel_api_environment'] : 'test'; // Padrão para 'test'
    }
}