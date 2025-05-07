<?php

namespace DuffelTravel\Api;

use Duffel\Client; // Importa a classe Client do SDK da Duffel
// Se não usar o SDK:
// use WP_Error;

// Se não usar namespace:
// class Duffel_Travel_Duffel_Api {

class DuffelApi {

    private $api_key;
    private $client; // Instância do SDK da Duffel
    private $environment; // 'test' ou 'live'

    public function __construct() {
        $this->api_key = \DuffelTravel\Admin\AdminSettings::get_api_key();
        $this->environment = \DuffelTravel\Admin\AdminSettings::get_api_environment();
        
        // Se não usar namespace:
        // $this->api_key = Duffel_Travel_Admin_Settings::get_api_key();
        // $this->environment = Duffel_Travel_Admin_Settings::get_api_environment();

        if ( ! $this->api_key ) {
            // Log de erro ou aviso de que a API key não está configurada
            // error_log('Duffel API Key not configured.');
            return;
        }

        // Inicializar o cliente do SDK da Duffel
        // Documentação do SDK: https://github.com/duffel/duffel-api-php
        try {
            $this->client = new Client([
                'token' => $this->api_key,
                // O SDK usa 'https://api.duffel.com' por padrão. 
                // O ambiente 'test' vs 'live' é geralmente controlado pela própria API Key que você usa.
                // Verifique a documentação da Duffel para confirmar se o endpoint base muda
                // ou se é apenas a chave que diferencia o ambiente.
                // Se o endpoint base mudar, você pode precisar de lógica adicional aqui.
                // Por exemplo: 'base_uri' => ($this->environment === 'live' ? 'https://api.duffel.com' : 'https://api.staging.duffel.com')
                // Mas, tipicamente, a chave de API já direciona para o ambiente correto.
            ]);
        } catch (\Exception $e) {
            error_log('Error initializing Duffel SDK client: ' . $e->getMessage());
            $this->client = null;
        }
    }

    /**
     * Verifica se o cliente da API está pronto.
     * @return bool
     */
    public function is_ready() {
        return !is_null( $this->client );
    }

    /**
     * Exemplo de método para buscar ofertas de voos.
     * Os parâmetros exatos dependerão da estrutura da sua busca.
     *
     * @param array $params Parâmetros da busca (slices, passengers, cabin_class, etc.)
     * @return \Duffel\Resources\OfferRequest|\WP_Error|array Retorna o objeto OfferRequest, WP_Error em caso de falha, ou array se não usar SDK.
     */
    public function search_flights( array $params ) {
        if ( ! $this->is_ready() ) {
            return new \WP_Error( 'api_not_ready', __( 'Duffel API client not initialized. Check API Key.', 'duffel-travel' ) );
        }

        /*
        Estrutura esperada para $params (exemplo):
        $params = [
            'slices' => [
                [
                    'origin' => 'LHR', // IATA code
                    'destination' => 'JFK', // IATA code
                    'departure_date' => '2024-12-25',
                ],
                // Adicionar mais slices para ida e volta ou multi-cidade
            ],
            'passengers' => [
                ['type' => 'adult'], // ou ['age' => 30]
                // ['type' => 'child', 'age' => 5]
            ],
            'cabin_class' => 'economy', // 'economy', 'premium_economy', 'business', 'first'
            // 'max_connections' => 0, (opcional)
        ];
        */

        try {
            // Documentação: https://duffel.com/docs/api/v1/offer-requests/create-offer-request
            $offerRequest = $this->client->offerRequests()->create($params);
            // O resultado do create() é o próprio OfferRequest, que pode conter ofertas ou ser paginado.
            // Você precisará então buscar as ofertas:
            // $offers = $this->client->offers()->all($offerRequest->getId());

            // Para simplificar e seguir o fluxo mais comum, a Duffel agora permite
            // que o 'create' já retorne as ofertas se você não precisar de paginação complexa ou "live updates"
            // Verifique a documentação do SDK e da API para `return_offers` no `create`.
            // Se `return_offers` for `true` (padrão no SDK >= v2), $offerRequest->getOffers() já terá as ofertas.

            // Exemplo de como obter ofertas:
            // $offers = $offerRequest->getOffers(); // Retorna uma coleção de objetos Offer
            
            return $offerRequest; // Ou $offers, dependendo do seu fluxo.

        } catch (\Duffel\Exception\ApiException $e) {
            // Tratar erros específicos da API Duffel
            // $e->getErrors() pode dar mais detalhes
            error_log('Duffel API Error (search_flights): ' . $e->getMessage() . ' Details: ' . json_encode($e->getErrors()));
            return new \WP_Error( 'duffel_api_error', $e->getMessage(), $e->getErrors() );
        } catch (\Exception $e) {
            // Tratar outros erros
            error_log('Generic Error (search_flights): ' . $e->getMessage());
            return new \WP_Error( 'generic_error', $e->getMessage() );
        }
    }

    // --- Outros Métodos da API ---
    // Você precisará adicionar métodos para:

    // public function get_single_offer( $offer_id ) { ... }
    
    // public function create_order( $selected_offer_id, array $passengers, array $payments ) { ... }
    // Documentação: https://duffel.com/docs/api/v1/orders/create-order

    // public function get_order( $order_id ) { ... }

    // public function create_payment_intent( $amount, $currency ) { ... } // Se usar Duffel Payments
    // Documentação: https://duffel.com/docs/api/v1/payments/create-payment-intent

    // public function confirm_payment_intent( $payment_intent_id ) { ... }
    // Documentação: https://duffel.com/docs/api/v1/payments/confirm-payment-intent

    // Para Hotéis (Stays API - se a Duffel já suportar no SDK ou se você fizer chamadas diretas)
    // public function search_hotels( array $params ) { ... }

    // Para Aluguer de Carros (Transport API - similarmente)
    // public function search_car_rentals( array $params ) { ... }

    // --- Endpoints Específicos ---
    // A sua estrutura `includes/api/endpoints/` sugere que você pode querer
    // mover lógicas de endpoints específicos para classes separadas.
    // Por exemplo, poderia ter uma classe `FlightOffersEndpoint` que usa esta `DuffelApi` classe.
    // Esta `DuffelApi` classe seria então mais um wrapper do SDK e gerenciador de autenticação.

}