<?php

namespace MVC\Controller;

use MVC\Model\User;
use MVC\Model\Role;

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

    public function getRole(): Role
    {
        if (!isset($_COOKIE['ChampionCheckerCookie'])) {
            return Role::Gast;
        }

        if (isset($_SESSION['user_role'])) {
            return $_SESSION['user_role'];
        }

        $roleResult = $this->getApiData('/api/auth/role');

        if ($roleResult['success'] === false) {
            error_log("Die Nutzerrolle konnte nicht abgefragt werden: " . json_encode($roleResult));
            return Role::Gast;
        }

        $roleValue = json_decode($roleResult['response'], true);

        try {
            $role = Role::from($roleValue['role']);
            $_SESSION['user_role'] = $role;
            return $role;
        } catch (Exception $e) {
            throw new RuntimeException('Ungültiger Rollenwert: ' . $roleValue['role']);
        }
    }


    public function login(string $email, string $password): array
    {
        $data = [
            'email' => $email,
            'password' => $password
        ];

        try {
            $apiResult = $this->sendApiRequest('/api/auth/login', 'POST', $data);
            $success = $apiResult['statusCode'] < 400;

            if ($success) {
                $userName = explode(',', $apiResult['response'])[0];
                $userRole = explode(',', $apiResult['response'])[1];
                $this->extractUserInitials($userName);


                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                $_SESSION['user_role'] = match ($userRole) {
                    'Spectator' => Role::Gast,
                    'Student' => Role::Schüler,
                    'Supervisor' => Role::Lehrkraft,
                    'Admin' => Role::Admin,
                    default => throw new RuntimeException(message: 'Ungültiger Rollenwert: ' . $userRole),
                };
            }

            $response = $success ? $userName : $apiResult['response'];

            return [
                'success' => $success,
                'response' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler beim Login: ' . $e->getMessage()
            ];
        }
    }

    public function register(User $user): array
    {
        $data = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
        ];

        try {
            $apiResult = $this->sendApiRequest('/api/auth/register', 'POST', $data);
            $registerSuccess = $apiResult['statusCode'] < 400;

            // Bei erfolgreicher Registrierung direkt einloggen
            if ($registerSuccess) {
                $loginResult = $this->login($user->getEmail(),  $user->getPassword());
                $registerSuccess = $registerSuccess && ($loginResult['success'] ?? false);
            }

            return [
                'success' => $registerSuccess,
                'response' => $apiResult['response']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler bei der Registrierung: ' . $e->getMessage()
            ];
        }
    }

    public function forgotPassword(string $email): array
    {
        $data = [
            'email' => $email,
            'clientUri' => 'https://' . $_SERVER['HTTP_HOST'] . '/ChampionChecker.UI/MVC/View/forgot_password.php'
        ];

        try {
            $apiResult = $this->sendApiRequest('/api/auth/forgot-password', 'POST', $data);
            $statusCode = $apiResult['statusCode'];
            return [
                'success' => $statusCode < 400,
                'response' => $apiResult['response']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler beim Zurücksetzen des Passworts: ' . $e->getMessage()
            ];
        }
    }


    // Ermöglicht es dem Nutzer, sein Passwort durch die Eingabe des Reset-Tokens aus der Passwort-vergessen-Email zurückzusetzen
    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        $data = [
            'userMail' => $email,
            'resetToken' => $token,
            'newPassword' => $newPassword
        ];

        try {
            $apiResult = $this->sendApiRequest('/api/auth/reset-password', 'POST', $data);
            $statusCode = $apiResult['statusCode'];
            return [
                'success' => $statusCode < 400,
                'response' => $apiResult['response']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler beim Zurücksetzen des Passworts: ' . $e->getMessage()
            ];
        }
    }


    // Nutzerinitialien im LocalStorage setzen, damit sie für das Profilbild in der Navbar verwendet werden können.
    public function extractUserInitials($apiUsername): void
    {
        $parts = explode('.', $apiUsername);
        if (count($parts) < 2) {
            return;
        }

        $firstName = $parts[0][0];
        $lastName = $parts[1][0];
        $initials = strtoupper($firstName . $lastName);

        echo "<script>
        localStorage.setItem('Initials', '" . addslashes(string: $initials) . "');
      </script>";
    }


    private function getApiData(string $endpoint)
    {
        $curl = curl_init($this->apiUrl . $endpoint);
        $cookieValue = $_COOKIE['ChampionCheckerCookie'] ?? null;


        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_USERAGENT => 'PHP API Request',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIE => $cookieValue !== null ? "ChampionCheckerCookie={$cookieValue}" : null,
        ]);

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $success = $statusCode < 400;

        if ($response === false) {
            $error = curl_error($curl);
            throw new RuntimeException('cURL error: ' . $error);
        }

        return ['response' => $response, 'success' => $success];
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
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new RuntimeException('cURL error: ' . curl_error($curl));
        }

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $responseBody = json_decode($body, true);

        // Cookie aus dem Header extrahieren und speichern
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
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
            'response' => $responseBody,
            // Bei erfolgreicher Anfrage enthält der Body den Nutzernamen.
            // Bei Fehlern die Fehlermeldung, die über response['errors'][0]['description']
            // abgerufen werden kann
        ];
    }
}
