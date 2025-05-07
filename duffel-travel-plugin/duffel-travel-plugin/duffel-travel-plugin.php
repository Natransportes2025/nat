<?php
/**
 * Plugin Name:       Duffel Travel
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Integração com a API Duffel para venda de bilhetes de passagens, pacotes de hotéis e rent a car.
 * Version:           1.0.0
 * Author:            Seu Nome/Empresa
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       duffel-travel
 * Domain Path:       /languages
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Definições de constantes do plugin.
 */
define( 'DUFFEL_TRAVEL_VERSION', '1.0.0' );
// Garanta que DUFFEL_TRAVEL_PLUGIN_DIR está definido corretamente
if ( ! defined( 'DUFFEL_TRAVEL_PLUGIN_DIR' ) ) {
    define( 'DUFFEL_TRAVEL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
define( 'DUFFEL_TRAVEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DUFFEL_TRAVEL_PLUGIN_FILE', __FILE__ );

// Caminho para o autoloader do SDK da Duffel
// *** CONFIRME ESTE CAMINHO ***
// Deve ser algo como: DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/duffel-api-php-2.0.2/vendor/autoload.php'
// Onde 'duffel-api-php-2.0.2' é a pasta que você copiou para 'includes/'
define( 'DUFFEL_SDK_AUTOLOAD_PATH', DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/duffel-api-php-2.0.2/vendor/autoload.php' );

// Incluir o autoloader do SDK da Duffel
if ( file_exists( DUFFEL_SDK_AUTOLOAD_PATH ) ) {
    require_once DUFFEL_SDK_AUTOLOAD_PATH;
} else {
    // Adicionar um aviso no admin se o SDK não for encontrado
    if ( is_admin() ) {
        add_action( 'admin_notices', function() {
            $sdk_path_message = sprintf(
                // translators: %s is the path to the SDK autoloader.
                esc_html__( 'Duffel SDK not found. Please ensure it is placed correctly. Expected path: %s', 'duffel-travel' ),
                '<code>' . esc_html( DUFFEL_SDK_AUTOLOAD_PATH ) . '</code>'
            );
            echo '<div class="notice notice-error"><p>' . $sdk_path_message . '</p></div>';
        });
    }
    // Opcional: impedir o carregamento do resto do plugin se o SDK for crítico
    // return;
}

/**
 * O arquivo principal da funcionalidade do plugin.
 */
require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/class-core.php';

/**
 * Inicia a execução do plugin.
 *
 * Como o plugin depende da classe Duffel_Travel_Core,
 * ele só é inicializado após todos os plugins terem sido carregados.
 */
function run_duffel_travel_plugin() {
    $plugin = new DuffelTravel\Core(); // Assumindo namespace se usar PSR-4
    // Se não usar namespace: $plugin = new Duffel_Travel_Core();
    $plugin->run();
}
add_action( 'plugins_loaded', 'run_duffel_travel_plugin' );

/**
 * Código a ser executado na ativação do plugin.
 */
function activate_duffel_travel_plugin() {
    // Ex: Criar tabelas customizadas, definir opções padrão, etc.
    // Por agora, vamos deixar em branco, mas é bom ter.
    require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/class-plugin-activator.php';
    // DuffelTravel\Activator::activate(); // Com namespace
    // Duffel_Travel_Plugin_Activator::activate(); // Sem namespace
}
register_activation_hook( __FILE__, 'activate_duffel_travel_plugin' );

/**
 * Código a ser executado na desativação do plugin.
 */
function deactivate_duffel_travel_plugin() {
    // Ex: Limpar opções, remover cron jobs, etc.
    require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/class-plugin-deactivator.php';
    // DuffelTravel\Deactivator::deactivate(); // Com namespace
    // Duffel_Travel_Plugin_Deactivator::deactivate(); // Sem namespace
}
register_deactivation_hook( __FILE__, 'deactivate_duffel_travel_plugin' );

?>