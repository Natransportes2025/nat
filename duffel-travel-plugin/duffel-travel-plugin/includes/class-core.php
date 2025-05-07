<?php
/**
 * O arquivo do núcleo do plugin.
 *
 * Isto é usado para definir internacionalização, hooks específicos do admin,
 * e hooks do lado público do site.
 *
 * Também responsável por iniciar todas as funcionalidades do plugin.
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           Duffel_Travel
 */

namespace DuffelTravel; // Mantenha isto se você estiver a usar namespaces de forma consistente.
                        // Se não, remova esta linha e ajuste as instanciações abaixo.

/**
 * A classe principal do núcleo do plugin.
 *
 * @since      1.0.0
 * @package    Duffel_Travel
 * @author     Seu Nome <email@example.com>
 */
// Se não usar namespace:
// class Duffel_Travel_Core {
class Core {

    /**
     * O identificador único deste plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    O nome ou identificador do plugin.
     */
    protected $plugin_name;

    /**
     * A versão atual do plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    A versão atual do plugin.
     */
    protected $version;

    /**
     * Define a funcionalidade principal do plugin.
     *
     * Define o nome e a versão do plugin que podem ser usados em todo o plugin.
     * Carrega as dependências, define a localidade e define os hooks para
     * a área de administração e o lado público do site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'DUFFEL_TRAVEL_VERSION' ) ) {
            $this->version = DUFFEL_TRAVEL_VERSION;
        } else {
            $this->version = '1.0.0'; // Fallback
        }
        $this->plugin_name = 'duffel-travel'; // Use o text-domain/slug do seu plugin

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carrega as dependências necessárias para o plugin.
     *
     * Inclui os seguintes arquivos que compõem o plugin:
     *
     * - Duffel_Travel_Loader. Orchestrates the hooks of the plugin. (Se você usar um loader)
     * - Duffel_Travel_i18n. Define a funcionalidade de internacionalização.
     * - Duffel_Travel_Admin. Define todos os hooks para a área de administração.
     * - Duffel_Travel_Public. Define todos os hooks para o lado público.
     *
     * Cria uma instância de cada um e os passa para o Loader. (Se usar um loader)
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * A classe responsável por definir todas as ações que ocorrem
         * na área de administração.
         */
        require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'admin/class-admin-settings.php';

        /**
         * A classe responsável pela lógica da API Duffel.
         */
        require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/api/class-duffel-api.php';

        /**
         * A classe responsável por definir todas as ações que ocorrem
         * no lado público do site.
         */
        require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'public/class-public.php';

        // Se você tivesse uma classe Loader, seria algo como:
        // require_once DUFFEL_TRAVEL_PLUGIN_DIR . 'includes/class-loader.php';
        // $this->loader = new Loader();

    }

    /**
     * Define a localidade para este plugin para tradução.
     *
     * Usa a classe Duffel_Travel_i18n para definir o domain e carregar
     * o arquivo de tradução textdomain.
     * (Simplificado aqui, pois não criamos uma classe i18n separada ainda)
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
    }

    /**
     * Carrega o text domain do plugin para tradução.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->plugin_name, // ou 'duffel-travel' diretamente
            false,
            dirname( plugin_basename( DUFFEL_TRAVEL_PLUGIN_FILE ) ) . '/languages/'
        );
    }

    /**
     * Regista todos os hooks relacionados com a funcionalidade da área administrativa
     * do plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Se estiver a usar namespaces:
        if ( class_exists( '\DuffelTravel\Admin\AdminSettings' ) ) {
            $plugin_admin = new \DuffelTravel\Admin\AdminSettings( $this->get_plugin_name(), $this->get_version() );
            // Se usasse um loader:
            // $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
            // $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        }
        // Se NÃO estiver a usar namespaces:
        // (assumindo que o nome da classe AdminSettings seria Duffel_Travel_Admin_Settings)
        // else if ( class_exists( 'Duffel_Travel_Admin_Settings' ) ) {
        //     $plugin_admin = new \Duffel_Travel_Admin_Settings( $this->get_plugin_name(), $this->get_version() );
        // }
    }

    /**
     * Regista todos os hooks relacionados com a funcionalidade do lado público
     * do plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // Se estiver a usar namespaces:
        if ( class_exists( '\DuffelTravel\PublicFacing' ) ) {
            $plugin_public = new \DuffelTravel\PublicFacing( $this->get_plugin_name(), $this->get_version() );
            // Se usasse um loader:
            // $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
            // $this->loader->add_shortcode( 'duffel_flight_search_form', $plugin_public, 'render_flight_search_form' );
            // $this->loader->add_action( 'init', $plugin_public, 'handle_flight_search_submission' );
        }
        // Se NÃO estiver a usar namespaces:
        // (assumindo que o nome da classe PublicFacing seria Duffel_Travel_Public)
        // else if ( class_exists( 'Duffel_Travel_Public' ) ) {
        //    $plugin_public = new \Duffel_Travel_Public( $this->get_plugin_name(), $this->get_version() );
        // }
    }

    /**
     * Executa o plugin.
     *
     * Executa o carregador para executar todos os hooks e filtros registados. (Se usar um loader)
     * Ou, neste caso, como as classes AdminSettings e PublicFacing já estão a adicionar os seus
     * próprios hooks nos seus construtores, esta função pode não precisar fazer muito mais.
     *
     * @since    1.0.0
     */
    public function run() {
        // Se usasse um loader:
        // $this->loader->run();
    }

    /**
     * O nome do plugin usado para identificar unicamente ele dentro do WordPress.
     *
     * @since     1.0.0
     * @return    string    O nome do plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retorna o número da versão do plugin.
     *
     * @since     1.0.0
     * @return    string    O número da versão do plugin.
     */
    public function get_version() {
        return $this->version;
    }

} // Certifique-se que esta é a ÚLTIMA chave de fechamento do ficheiro, para a classe Core.