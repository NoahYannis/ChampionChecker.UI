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
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_USERAGENT => 'PHP API Request'
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

        return [
            'statusCode' => $statusCode,
            'response' => $response,
        ];
    }
}
