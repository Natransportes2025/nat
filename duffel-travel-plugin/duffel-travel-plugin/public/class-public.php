<?php

namespace DuffelTravel;

use DuffelTravel\Api\DuffelApi; // Para usar a classe da API
use WP_Error;

// Se não usar namespace:
// class Duffel_Travel_Public {

class PublicFacing {

    private $plugin_name;
    private $version;
    private $search_results = null; // Para armazenar os resultados da busca
    private $search_error = null;   // Para armazenar erros da busca

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action( 'init', array( $this, 'handle_flight_search_submission' ) );
        add_shortcode( 'duffel_flight_search_form', array( $this, 'render_flight_search_form' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); // Para JS, se necessário depois
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/duffel-travel-public.css', // Caminho para o seu CSS público
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Lida com a submissão do formulário de busca de voos.
     */
    public function handle_flight_search_submission() {
        if ( ! isset( $_POST['duffel_flight_search_submit'] ) || ! isset( $_POST['duffel_search_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['duffel_search_nonce'], 'duffel_flight_search_action' ) ) {
            $this->search_error = __( 'Nonce verification failed. Please try again.', 'duffel-travel' );
            return;
        }

        // Sanitizar e validar inputs
        $origin = isset( $_POST['origin'] ) ? sanitize_text_field( strtoupper( $_POST['origin'] ) ) : '';
        $destination = isset( $_POST['destination'] ) ? sanitize_text_field( strtoupper( $_POST['destination'] ) ) : '';
        $departure_date = isset( $_POST['departure_date'] ) ? sanitize_text_field( $_POST['departure_date'] ) : '';
        // Adicionar mais campos conforme necessário (passageiros, classe, etc.)
        $passengers_adults = isset( $_POST['passengers_adults'] ) ? intval( $_POST['passengers_adults'] ) : 1;
        $cabin_class = isset( $_POST['cabin_class'] ) ? sanitize_text_field( $_POST['cabin_class'] ) : 'economy';


        // Validação básica (adicione mais conforme necessário)
        if ( empty( $origin ) || empty( $destination ) || empty( $departure_date ) ) {
            $this->search_error = __( 'Please fill in all required fields (Origin, Destination, Departure Date).', 'duffel-travel' );
            return;
        }
        if ( ! preg_match( '/^[A-Z]{3}$/', $origin ) ) {
             $this->search_error = __( 'Origin must be a 3-letter IATA code.', 'duffel-travel' );
            return;
        }
        if ( ! preg_match( '/^[A-Z]{3}$/', $destination ) ) {
             $this->search_error = __( 'Destination must be a 3-letter IATA code.', 'duffel-travel' );
            return;
        }
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $departure_date ) ) {
             $this->search_error = __( 'Departure date must be in YYYY-MM-DD format.', 'duffel-travel' );
            return;
        }
        if ( $passengers_adults < 1 ) {
            $passengers_adults = 1;
        }


        // Construir parâmetros para a API Duffel
        $api_params = [
            'slices' => [
                [
                    'origin' => $origin,
                    'destination' => $destination,
                    'departure_date' => $departure_date,
                ],
                // Adicionar mais slices para ida e volta aqui, se tiver esses campos no formulário
            ],
            'passengers' => [],
            'cabin_class' => $cabin_class,
            'return_offers' => true, // O SDK v2+ do PHP já faz isso por padrão
        ];

        for ($i = 0; $i < $passengers_adults; $i++) {
            $api_params['passengers'][] = ['type' => 'adult'];
        }
        // Adicionar lógica para outros tipos de passageiros (crianças, bebés) se o formulário os incluir

        // Chamar a API
        $duffel_api = new DuffelApi(); // Instancia a classe da API que configuramos antes
        
        if ( ! $duffel_api->is_ready() ) {
            $api_key_status = Admin\AdminSettings::get_api_key() ? __( 'API client could not be initialized.', 'duffel-travel' ) : __( 'API Key is not configured.', 'duffel-travel' );
            $this->search_error = __( 'Duffel API Error:', 'duffel-travel' ) . ' ' . $api_key_status;
            error_log('Duffel Search Error: API Client not ready. Key set: ' . (Admin\AdminSettings::get_api_key() ? 'Yes' : 'No'));
            return;
        }

        $offer_request_result = $duffel_api->search_flights( $api_params );

        if ( is_wp_error( $offer_request_result ) ) {
            $this->search_error = __( 'API Search Error:', 'duffel-travel' ) . ' ' . $offer_request_result->get_error_message();
            // Para mais detalhes do erro da API, se disponíveis:
            // $error_data = $offer_request_result->get_error_data();
            // if (is_array($error_data) && !empty($error_data)) {
            //     $this->search_error .= ' Details: ' . wp_json_encode($error_data);
            // }
            error_log('Duffel API WP_Error: ' . $offer_request_result->get_error_message());
        } elseif ( $offer_request_result && method_exists($offer_request_result, 'getOffers') ) {
            // O SDK retorna um objeto OfferRequest. As ofertas estão dentro dele.
            $this->search_results = $offer_request_result->getOffers(); // Isto retorna uma coleção de objetos Offer
            if (empty($this->search_results)) {
                $this->search_error = __('No flights found for your criteria.', 'duffel-travel');
            }
            // Para depuração:
            // error_log('Duffel Search Success: ' . count($this->search_results) . ' offers found.');
            // error_log(print_r($this->search_results, true));
        } else {
            $this->search_error = __( 'An unexpected error occurred while searching for flights.', 'duffel-travel' );
            error_log('Duffel Search Error: Unexpected result from API.');
            // error_log(print_r($offer_request_result, true));
        }
    }

    /**
     * Renderiza o formulário de busca de voos e os resultados.
     */
    public function render_flight_search_form() {
        ob_start();
        ?>
        <div class="duffel-flight-search-wrapper">
            <h2><?php _e( 'Search Flights', 'duffel-travel' ); ?></h2>

            <form method="POST" action="<?php echo esc_url( remove_query_arg( 'duffel_message' ) ); ?>">
                <?php wp_nonce_field( 'duffel_flight_search_action', 'duffel_search_nonce' ); ?>

                <div class="form-row">
                    <label for="origin"><?php _e( 'Origin (IATA)', 'duffel-travel' ); ?>:</label>
                    <input type="text" id="origin" name="origin" value="<?php echo isset($_POST['origin']) ? esc_attr($_POST['origin']) : ''; ?>" required pattern="[A-Za-z]{3}" title="3-letter IATA code">
                </div>

                <div class="form-row">
                    <label for="destination"><?php _e( 'Destination (IATA)', 'duffel-travel' ); ?>:</label>
                    <input type="text" id="destination" name="destination" value="<?php echo isset($_POST['destination']) ? esc_attr($_POST['destination']) : ''; ?>" required pattern="[A-Za-z]{3}" title="3-letter IATA code">
                </div>

                <div class="form-row">
                    <label for="departure_date"><?php _e( 'Departure Date', 'duffel-travel' ); ?>:</label>
                    <input type="date" id="departure_date" name="departure_date" value="<?php echo isset($_POST['departure_date']) ? esc_attr($_POST['departure_date']) : ''; ?>" required>
                </div>
                
                <div class="form-row">
                    <label for="passengers_adults"><?php _e( 'Adults (18+)', 'duffel-travel' ); ?>:</label>
                    <input type="number" id="passengers_adults" name="passengers_adults" value="<?php echo isset($_POST['passengers_adults']) ? esc_attr($_POST['passengers_adults']) : '1'; ?>" min="1" max="9">
                </div>

                <div class="form-row">
                    <label for="cabin_class"><?php _e( 'Cabin Class', 'duffel-travel' ); ?>:</label>
                    <select id="cabin_class" name="cabin_class">
                        <?php 
                        $selected_cabin_class = isset($_POST['cabin_class']) ? $_POST['cabin_class'] : 'economy';
                        $cabin_classes = ['economy' => __('Economy', 'duffel-travel'), 'premium_economy' => __('Premium Economy', 'duffel-travel'), 'business' => __('Business', 'duffel-travel'), 'first' => __('First', 'duffel-travel')];
                        foreach ($cabin_classes as $value => $label) {
                            echo '<option value="' . esc_attr($value) . '"' . selected($selected_cabin_class, $value, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <input type="submit" name="duffel_flight_search_submit" value="<?php _e( 'Search Flights', 'duffel-travel' ); ?>">
                </div>
            </form>

            <div id="duffel-search-results" class="duffel-search-results">
                <?php if ( $this->search_error ) : ?>
                    <div class="duffel-error"><p><?php echo esc_html( $this->search_error ); ?></p></div>
                <?php endif; ?>

                <?php if ( ! empty( $this->search_results ) && is_array( $this->search_results ) ) : ?>
                    <h3><?php _e( 'Search Results', 'duffel-travel' ); ?></h3>
                    <ul class="flight-offers-list">
                        <?php foreach ( $this->search_results as $offer_obj ) : // $offer_obj é um \Duffel\Resources\Offer ?>
                            <li class="flight-offer-item">
                                <div class="offer-airline">
                                    <?php
                                    // Uma oferta pode ter múltiplos segmentos e cada segmento uma companhia diferente.
                                    // Para simplificar, vamos pegar a primeira companhia do primeiro segmento da primeira slice.
                                    // No layout real, você precisará iterar sobre slices e segmentos.
                                    $first_slice = $offer_obj->getSlices()[0] ?? null;
                                    $first_segment = $first_slice ? ($first_slice->getSegments()[0] ?? null) : null;
                                    $airline = $first_segment ? $first_segment->getOperatingCarrier() : null; // ou getMarketingCarrier()
                                    ?>
                                    <strong><?php _e( 'Airline:', 'duffel-travel' ); ?></strong> 
                                    <?php if ($airline) : ?>
                                        <img src="<?php echo esc_url($airline->getLogoLockupUrl()); ?>" alt="<?php echo esc_attr($airline->getName()); ?>" style="height: 20px; vertical-align: middle; margin-right: 5px;">
                                        <?php echo esc_html( $airline->getName() ); ?> (<?php echo esc_html( $airline->getIataCode() ); ?>)
                                    <?php else: ?>
                                        <?php _e( 'N/A', 'duffel-travel' ); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="offer-itinerary">
                                    <?php foreach ($offer_obj->getSlices() as $index => $slice_obj): // $slice_obj é \Duffel\Resources\Slice ?>
                                        <div class="slice-details">
                                            <h4><?php echo $index === 0 ? __('Outbound', 'duffel-travel') : __('Return', 'duffel-travel'); // Simplificação ?>: 
                                                <?php echo esc_html($slice_obj->getOrigin()->getCityName() ?: $slice_obj->getOrigin()->getIataCode()); ?> (<?php echo esc_html($slice_obj->getOrigin()->getIataCode()); ?>)
                                                to 
                                                <?php echo esc_html($slice_obj->getDestination()->getCityName() ?: $slice_obj->getDestination()->getIataCode()); ?> (<?php echo esc_html($slice_obj->getDestination()->getIataCode()); ?>)
                                            </h4>
                                            <p>
                                                <?php _e('Departure:', 'duffel-travel'); ?> <?php echo esc_html( (new \DateTime($slice_obj->getSegments()[0]->getDepartingAt()))->format('Y-m-d H:i') ); ?> <br>
                                                <?php _e('Arrival:', 'duffel-travel'); ?> <?php echo esc_html( (new \DateTime(end($slice_obj->getSegments())->getArrivingAt()))->format('Y-m-d H:i') ); ?> <br>
                                                <?php _e('Duration:', 'duffel-travel'); ?> <?php echo esc_html( $this->format_duration($slice_obj->getDuration()) ); ?> <br>
                                                <?php _e('Stops:', 'duffel-travel'); ?> <?php echo count($slice_obj->getSegments()) - 1; ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="offer-price">
                                    <strong><?php _e( 'Total Price:', 'duffel-travel' ); ?></strong>
                                    <?php echo esc_html( $offer_obj->getTotalAmount() . ' ' . $offer_obj->getTotalCurrency() ); ?>
                                    <?php // Você pode usar $offer_obj->getTaxAmount() e $offer_obj->getBaseAmount() se precisar separar ?>
                                <h3>Data do voo : <?php echo $departure_date = isset( $_POST['departure_date'] ) ? sanitize_text_field( $_POST['departure_date'] ) : ''; ?></h3>
                                </div>
                                <div class="offer-actions">
                                    <?php // Para selecionar uma oferta, você precisaria do $offer_obj->getId() ?>
                                    <a href="#" class="button select-offer-button" data-offer-id="<?php echo esc_attr( $offer_obj->getId() ); ?>">
                                        <?php _e( 'Select Flight', 'duffel-travel' ); ?>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif ( isset($_POST['duffel_flight_search_submit']) && !$this->search_error ) : // Se foi submetido mas não há resultados e nem erro explícito de "no flights" ?>
                    <p><?php _e( 'No flights found for your criteria, or an error occurred.', 'duffel-travel' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Formata uma duração ISO 8601 (ex: PT2H30M) para um formato legível.
     * @param string $iso_duration
     * @return string
     */
    private function format_duration($iso_duration) {
        if (empty($iso_duration)) return 'N/A';
        try {
            $interval = new \DateInterval($iso_duration);
            return $interval->format('%h hours %i minutes'); // Formato simples
        } catch (\Exception $e) {
            return $iso_duration; // Retorna original se não conseguir parsear
        }
    }
}