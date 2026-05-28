<?php

namespace App\Services\Cargo\Yurtici;

use App\Models\CargoCredential;
use SoapClient;
use SoapFault;

/**
 * Yurtici Kargo SOAP istemcisi.
 *
 * Test: testwebservices.yurticikargo.com:9090
 * Prod: webservices.yurticikargo.com:8080
 */
class Client
{
    private SoapClient $shipmentClient;

    private SoapClient $trackingClient;

    public function __construct(
        private readonly CargoCredential $credential,
    ) {
        $useTest = config('cargo.providers.yurtici.use_test', true);
        $wsdlKey = $useTest ? 'test' : 'production';

        $shipmentWsdl = config("cargo.providers.yurtici.wsdl.{$wsdlKey}");
        $trackingWsdl = config("cargo.providers.yurtici.tracking_wsdl.{$wsdlKey}");

        $this->shipmentClient = $this->createSoapClient($shipmentWsdl);
        $this->trackingClient = $this->createSoapClient($trackingWsdl);
    }

    /**
     * Gönderi oluşturma servis çağrısı.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws SoapFault
     */
    public function callShipment(string $method, array $params): array
    {
        return $this->wrapCall($this->shipmentClient, $method, $params);
    }

    /**
     * Takip servis çağrısı.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws SoapFault
     */
    public function callTracking(string $method, array $params): array
    {
        return $this->wrapCall($this->trackingClient, $method, $params);
    }

    /**
     * Auth bilgilerini SOAP header oluşturur.
     *
     * @return array<string, string>
     */
    public function authParams(): array
    {
        return [
            'userName' => $this->credential->username,
            'password' => $this->credential->password,
        ];
    }

    public function customerCode(): ?string
    {
        return $this->credential->customer_code;
    }

    private function createSoapClient(string $wsdl): SoapClient
    {
        return new SoapClient($wsdl, [
            'trace' => config('app.debug', false),
            'exceptions' => true,
            'connection_timeout' => 30,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function wrapCall(SoapClient $client, string $method, array $params): array
    {
        $response = $client->__soapCall($method, [$params]);

        return json_decode(json_encode($response), true) ?: [];
    }
}
