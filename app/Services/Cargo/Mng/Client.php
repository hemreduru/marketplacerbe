<?php

namespace App\Services\Cargo\Mng;

use App\Models\CargoCredential;
use SoapClient;

class Client
{
    private SoapClient $soapClient;

    public function __construct(
        private readonly CargoCredential $credential,
    ) {
        $wsdl = config('cargo.providers.mng.wsdl.production');

        $this->soapClient = new SoapClient($wsdl, [
            'trace' => config('app.debug', false),
            'exceptions' => true,
            'connection_timeout' => 30,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params): array
    {
        $response = $this->soapClient->__soapCall($method, [$params]);

        return json_decode(json_encode($response), true) ?: [];
    }

    /**
     * @return array<string, string>
     */
    public function authParams(): array
    {
        return [
            'username' => $this->credential->username,
            'password' => $this->credential->password,
        ];
    }

    public function customerCode(): ?string
    {
        return $this->credential->customer_code;
    }
}
