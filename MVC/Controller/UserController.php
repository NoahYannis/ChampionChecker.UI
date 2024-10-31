<?php

namespace MVC\Controller;

use MVC\Model\User;
use RuntimeException;
use Exception;

class UserController
{
    private static ?UserController $instance = null;
    private string $apiUrl;

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    public static function getInstance(): UserController
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function login(string $email, string $password): bool
    {
        $data = [
            'email' => $email,
            'password' => $password
        ];

        try {
            $apiResult = $this->sendApiRequest('/api/auth/login', 'POST', $data);
            $statusCode = $apiResult['statusCode'];
            return $statusCode < 400;
        } catch (Exception $e) {
            echo "<script>alert('Fehler beim Login: {$e->getMessage()}');</script>";
            return false;
        }
    }

    public function register(User $user): bool
    {
        $data = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
        ];

        try {
            $apiResult = $this->sendApiRequest('/api/auth/register', 'POST', $data);
            $statusCode = $apiResult['statusCode'];
            return $statusCode < 400;
        } catch (Exception $e) {
            echo "<script>alert('Fehler bei der Registrierung: {$e->getMessage()}');</script>";
            return false;
        }
    }



    private function sendApiRequest(string $endpoint, string $method, array $data = []): array
    {
        $curl = curl_init($this->apiUrl . $endpoint);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true, // Header anfordern, um Cookie daraus auszulesen
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_USERAGENT => 'PHP API Request',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // Server SSL-Zertifikat nicht prüfen (nur für Entwicklung)
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new RuntimeException('cURL error: ' . curl_error($curl));
        }

        curl_close($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($statusCode >= 400) {
            throw new RuntimeException("API request failed with status code $statusCode.");
        }
        
        // Cookies aus den Headern extrahieren und setzen
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        foreach ($matches[1] as $cookieStr) {
            [$cookieName, $cookieValue] = explode('=', trim($cookieStr), 2);
            setcookie($cookieName, $cookieValue, [
                'expires' => time() + 1209600, // Zwei Wochen
                'path' => '/', // Cookie auf allen Pfaden im Frontend verfügbar
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => true, // Cookie wird nur über HTTPS übertragen
                'httponly' => true, // Cookie kann nicht über JavaScript ausgelesen werden
                'samesite' => 'None', // Cookie wird bei Cross-Site-Requests mitschickt
            ]);
        }

        return [
            'statusCode' => $statusCode,
            'response' => $response,
        ];
    }
}
