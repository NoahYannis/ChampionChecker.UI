<?php

namespace MVC\Controller;

use MVC\Model\Teacher;
use RuntimeException;

/**
 * @implements IController<Teacher>
 */
class TeacherController implements IController
{
    private static ?TeacherController $instance = null;
    private string $apiUrl;
    private array $cachedTeachers = [];

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param int $id
     * @return Teacher|null
     */
    public function getById(int $id): ?Teacher
    {
        if (isset($this->cachedTeachers[$id])) {
            return $this->cachedTeachers[$id];
        }

        $data = $this->getApiData("/api/teacher/$id");

        if (isset($data['id'])) {
            $teacher = new Teacher(
                id: $data['id'],
                firstName: $data['firstName'],
                lastName: $data['lastName'],
                shortCode: $data['shortCode'],
                additionalInfo: $data['additionalInfo'] ?? null
            );

            // Lehrer im Cache speichern
            $this->cachedTeachers[$id] = $teacher;
            return $teacher;
        }

        return null;
    }

    /**
     * @return Teacher[]
     */
    public function getAll(): array
    {
        // Überprüfen, ob die Lehrer bereits im Cache sind
        if (!empty($this->cachedTeachers)) {
            return $this->cachedTeachers;
        }

        $data = $this->getApiData('/api/teacher');
        $teachers = [];

        foreach ($data as $item) {
            $teacher = new Teacher(
                id: $item['id'],
                firstName: $item['firstName'],
                lastName: $item['lastName'],
                shortCode: $item['shortCode'],
                additionalInfo: $item['additionalInfo'] ?? null
            );
            $teachers[] = $teacher;

            // Lehrer im Cache speichern
            $this->cachedTeachers[$item['id']] = $teacher;
        }
        return $teachers;
    }

    /**
     * @param Teacher $model
     * @return void
     */
    public function create(object $model): void
    {
        if (!$model instanceof Teacher) {
            throw new \InvalidArgumentException('Model must be an instance of Teacher.');
        }

        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'shortCode' => $model->getShortCode(),
            'additionalInfo' => $model->getAdditionalInfo()
        ];

        $this->sendApiRequest('/api/teacher', 'POST', $data);

        if (isset($_SESSION['teachers'])) {
            $_SESSION['teachers'][] = $model;
        }
    }

    /**
     * @param Teacher $model
     * @return void
     */
    public function update(object $model): void
    {
        if (!$model instanceof Teacher) {
            throw new \InvalidArgumentException('Model must be an instance of Teacher.');
        }

        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'shortCode' => $model->getShortCode(),
            'additionalInfo' => $model->getAdditionalInfo() ?? ""
        ];

        $this->sendApiRequest("/api/teacher/{$model->getId()}", 'PUT', $data);

        if (isset($_SESSION['teachers'])) {
            foreach ($_SESSION['teachers'] as $key => $teacher) {
                if ($teacher->getId() === $model->getId()) {
                    $_SESSION['teachers'][$key] = $model;
                    break;
                }
            }
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->sendApiRequest("/api/teacher/$id", 'DELETE');

        if (isset($_SESSION['teachers'])) {
            foreach ($_SESSION['teachers'] as $key => $teacher) {
                if ($teacher->getId() === $id) {
                    unset($_SESSION['teachers'][$key]);
                    break;
                }
            }
        }
    }

    /**
     * @param string $endpoint
     * @return mixed
     */
    public function getApiData(string $endpoint)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_USERAGENT => 'PHP API Request'
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON response from API');
        }

        return $data;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return void
     */
    protected function sendApiRequest(string $endpoint, string $method, array $data = []): void
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
    }
}