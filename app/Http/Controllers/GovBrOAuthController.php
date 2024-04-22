<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class GovBrOAuthController extends Controller
{
    public function index(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('welcome');
    }

    public function authorizeURL(): string
    {
        $config = $this->getConfiguration();
        if ($this->invalidConfig($config)) {
            abort(500, 'Erro: authorize - Os parâmetros REDIRECT_URI, CLIENT_ID e SECRET são obrigatórios');
        }
        $url = "{$config['URL_PROVIDER']}/authorize?response_type={$config['RESPONSE_TYPE']}&client_id={$config['CLIENT_ID']}&scope={$config['SCOPES']}&redirect_uri={$config['REDIRECT_URI']}";
        return Redirect::to($url);
    }

    public function logoutURL(): string
    {
        $config = $this->getConfiguration();
        if ($this->invalidConfig($config)) {
            abort(500, 'Erro: logout - Os parâmetros REDIRECT_URI, CLIENT_ID e SECRET são obrigatórios');
        }
        return "{$config['URL_PROVIDER']}/logout?post_logout_redirect_uri={$config['REDIRECT_URI']}";
    }

    public function getToken(Request $request): Application|Factory|View|\Illuminate\Foundation\Application
    {
        $code = $request->input('code');
        $config = $this->getConfiguration();
        if ($this->invalidConfig($config)) {
            abort(500, 'Erro: getToken - Os parâmetros REDIRECT_URI, CLIENT_ID e SECRET são obrigatórios');
        }
        if (empty($code)) {
            abort(400, 'Erro: getToken - O parâmetro code é obrigatório');
        }

        $url = "{$config['URL_PROVIDER']}/token?grant_type={$config['GRANT_TYPE']}&code={$code}&redirect_uri=" . ($config['REDIRECT_URI']);
        try {
            $response = Http::withHeader(
                "Authorization",
                "Basic " . base64_encode($config['CLIENT_ID'] . ':' . $config['SECRET']
                )
            )->post($url);
            $res = $response->json();
            $jwtAccessToken = $this->jwtDecode($res['access_token']);
            $jwtIdToken = $this->jwtDecode($res['id_token']);

            $res = array_merge($jwtAccessToken, $res);
            $res['user'] = $jwtIdToken;
            dd($res);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    // Outras funções de serviço...

    private function getConfiguration(): array
    {
        return [
            'URL_PROVIDER' => env('GOVBR_URL_PROVIDER'),
            'URL_SERVICE' => env('GOVBR_URL_SERVICE'),
            'REDIRECT_URI' => env('GOVBR_REDIRECT_URI'),
            'SCOPES' => env('GOVBR_SCOPES'),
            'CLIENT_ID' => env('GOVBR_CLIENT_ID'),
            'SECRET' => env('GOVBR_SECRET'),
            'RESPONSE_TYPE' => 'code',
            'GRANT_TYPE' => 'authorization_code',
        ];
    }

    private function invalidConfig(array $config): bool
    {
        return empty($config['REDIRECT_URI']) || empty($config['CLIENT_ID']) || empty($config['SECRET']) || empty($config['URL_PROVIDER']) || empty($config['URL_SERVICE']) || empty($config['SCOPES']);
    }

    private function jwtDecode(string $jwt): array
    {
        $jwt = explode('.', $jwt);
        return json_decode(base64_decode($jwt[1]), true);
    }
}
