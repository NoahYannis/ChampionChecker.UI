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
        if (!empty($this->cachedTeachers)) {
            return $this->cachedTeachers;
        }

        $data = $this->getApiData('/api/teacher');
        $teachers = [];

        foreach ($data as $item) {
            $classesDictionary = [];
            if (isset($item['classes']) && is_array($item['classes'])) {
                foreach ($item['classes'] as $id => $name) {
                    $classesDictionary[$id] = $name;
                }
            }

            $teacher = new Teacher(
                id: $item['id'],
                firstName: $item['firstName'],
                lastName: $item['lastName'],
                shortCode: $item['shortCode'],
                isParticipating: $item['isParticipating'],
                classes: $classesDictionary,
                additionalInfo: $item['additionalInfo'] ?? null
            );

            $teachers[] = $teacher;
            $this->cachedTeachers[$item['id']] = $teacher;
        }
        return $teachers;
    }

    /**
     * @param Teacher $model
     * @return array
     */
    public function create(object $model): array
    {
        $classes = $model->getClasses() ?? [];
        $classDictionary = [];
        foreach ($classes as $id => $name) {
            $classDictionary[(int)$id] = (string)$name;
        }

        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'shortCode' => $model->getShortCode(),
            'isParticipating' => $model->getIsParticipating(),
            'additionalInfo' => $model->getAdditionalInfo() ?? "",
            'classes' => empty($classDictionary) ? null : $classDictionary
        ];

        $createResult = $this->sendApiRequest('/api/teacher', 'POST', $data);
        return $createResult;
    }

    /**
     * @param Teacher $model
     * @return array
     */
    public function update(object $model): array
    {
        $classes = $model->getClasses() ?? [];
        $classDictionary = [];
        foreach ($classes as $id => $name) {
            $classDictionary[(int)$id] = (string)$name;
        }

        $data = [
            'id' => $model->getId(),
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'shortCode' => $model->getShortCode(),
            'isParticipating' => $model->getIsParticipating(),
            'additionalInfo' => $model->getAdditionalInfo() ?? "",
            'classes' => empty($classDictionary) ? null : $classDictionary
        ];

        $updateResult = $this->sendApiRequest("/api/teacher", 'PUT', $data);

        if ($updateResult['success'] === true && isset($_SESSION['teachers'])) {
            foreach ($_SESSION['teachers'] as $key => $teacher) {
                if ($teacher->getId() === $model->getId()) {
                    $_SESSION['teachers'][$key] = $model;
                    break;
                }
            }
        }

        return $updateResult;
    }

    /**
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        $deleteResult = $this->sendApiRequest("/api/teacher/$id", 'DELETE');

        if (isset($_SESSION['teachers'])) {
            foreach ($_SESSION['teachers'] as $key => $teacher) {
                if ($teacher->getId() === $id) {
                    unset($_SESSION['teachers'][$key]);
                    break;
                }
            }
        }

        return $deleteResult;
    }

    public function getIdFromShortCode(string $shortCode): int
    {
        if (isset($_SESSION['teachers'])) {
            foreach ($_SESSION['teachers'] as $teacher) {
                if (strtoupper(trim($teacher->getShortCode())) === strtoupper(trim($shortCode))) {
                    return $teacher->getId();
                }
            }
        }

        return -1;
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
            CURLOPT_USERAGENT => 'PHP API Request',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            $error = curl_error($curl);
            throw new RuntimeException('cURL error: ' . $error);
        }

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
     * @return array
     */
    protected function sendApiRequest(string $endpoint, string $method, array $data = []): array
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
            CURLOPT_USERAGENT => 'PHP API Request',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            throw new RuntimeException('cURL error: ' . $error);
        }

        if (curl_errno($curl)) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . curl_error($curl)
            ];
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode >= 400) {
            $responseData = json_decode($response, true);
            $errorMessage = 'Unbekannter Fehler';

            if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                foreach ($responseData['errors'] as $fieldErrors) {
                    if (is_array($fieldErrors) && !empty($fieldErrors)) {
                        $errorMessage = $fieldErrors['description'] ?? $fieldErrors[0];
                        break;
                    }
                }
            }

            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }
}
