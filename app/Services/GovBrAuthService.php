<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GovBrAuthService
{
    public function authorizeURL(): string
    {
        $config = $this->getConfiguration();
        if ($this->invalidConfig($config)) {
            abort(500, 'Erro: authorize - Os parâmetros REDIRECT_URI, CLIENT_ID e SECRET são obrigatórios');
        }
        return "{$config['URL_PROVIDER']}/authorize?response_type={$config['RESPONSE_TYPE']}&client_id={$config['CLIENT_ID']}&scope={$config['SCOPES']}&redirect_uri={$config['REDIRECT_URI']}";
    }

    public function logoutURL(): string
    {
        $config = $this->getConfiguration();
        if ($this->invalidConfig($config)) {
            abort(500, 'Erro: logout - Os parâmetros REDIRECT_URI, CLIENT_ID e SECRET são obrigatórios');
        }
        return "{$config['URL_PROVIDER']}/logout?post_logout_redirect_uri={$config['REDIRECT_URI']}";
    }

    public function getToken(string $code, ?string $redirect_uri = null)
    {
        $config = $this->getConfiguration();
        if ($this->invalidConfig($config)) {
            abort(500, 'Erro: getToken - Os parâmetros REDIRECT_URI, CLIENT_ID e SECRET são obrigatórios');
        }
        if (empty($code)) {
            abort(400, 'Erro: getToken - O parâmetro code é obrigatório');
        }
        $url = "{$config['URL_PROVIDER']}/token?grant_type={$config['GRANT_TYPE']}&code={$code}&redirect_uri=".($redirect_uri ?? $config['REDIRECT_URI']);
        try {
            $response = Http::post($url, [
                'Authorization' => 'Basic '.base64_encode($config['CLIENT_ID'].':'.$config['SECRET']),
            ]);
            return $response->json();
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // Outras funções de serviço...

    private function getConfiguration(): array
    {
        return [
            'URL_PROVIDER' => config('services.govbr.url_provider'),
            'URL_SERVICE' => config('services.govbr.url_service'),
            'REDIRECT_URI' => config('services.govbr.redirect_uri'),
            'SCOPES' => config('services.govbr.scopes'),
            'CLIENT_ID' => config('services.govbr.client_id'),
            'SECRET' => config('services.govbr.secret'),
            'RESPONSE_TYPE' => 'code',
            'GRANT_TYPE' => 'authorization_code',
        ];
    }

    private function invalidConfig(array $config): bool
    {
        return empty($config['REDIRECT_URI']) || empty($config['CLIENT_ID']) || empty($config['SECRET']) || empty($config['URL_PROVIDER']) || empty($config['URL_SERVICE']) || empty($config['SCOPES']);
    }
}
