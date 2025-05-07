<?php
// Em includes/api/endpoints/class-flight-offer-request-endpoint.php
namespace DuffelTravel\Api\Endpoints;

use DuffelTravel\Api\DuffelApi;

class FlightOfferRequestEndpoint {
    private $duffel_api;

    public function __construct(DuffelApi $duffel_api) {
        $this->duffel_api = $duffel_api;
    }

    public function search(array $params) {
        return $this->duffel_api->search_flights($params);
    }
}