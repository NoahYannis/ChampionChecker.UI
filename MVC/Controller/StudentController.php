<?php

namespace MVC\Controller;

use MVC\Model\Student;
use RuntimeException;

/**
 * @implements IController<Student>
 */
class StudentController implements IController
{
    private static ?StudentController $instance = null;
    private string $apiUrl;
    private array $cachedStudents = [];

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    public static function getInstance(): StudentController
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getById(int $id): ?Student
    {
        if (isset($_SESSION["students"][$id])) {
            return $_SESSION["students"][$id];
        }

        $data = $this->getApiData("/api/student/$id");

        if (isset($data['id'])) {
            $studentModel = new Student(
                id: $data['id'],
                firstName: $data['firstName'],
                lastName: $data['lastName'],
                isMale: $data['isMale'],
                isRegistrationFinalized: $data['isRegistrationFinalized'],
                classId: key($data['class']),
                competitions: $data['competitions'] ?? [],
                competitionResults: $data['competitionResults'] ?? []
            );

            $_SESSION["students"][$id] = $studentModel;
            return $studentModel;
        }
        return null;
    }

    public function getByName(string $name): ?Student
    {
        foreach ($this->cachedStudents as $studentModel) {
            if ($studentModel->getFirstName() === $name || $studentModel->getLastName() === $name) {
                return $studentModel; // Gecachten Schüler zurückgeben
            }
        }

        $allStudents = $this->getApiData("/api/student");

        if (empty($allStudents)) {
            return null;
        }

        foreach ($allStudents as $studentData) {
            if (isset($studentData['firstName']) && $studentData['firstName'] === $name) {
                return new Student(
                    id: $studentData['id'],
                    firstName: $studentData['firstName'],
                    lastName: $studentData['lastName'],
                    isMale: $studentData['isMale'],
                    isRegistrationFinalized: $studentData['isRegistrationFinalized'],
                    classId: $studentData['classId'],
                    competitions: $studentData['competitions'] ?? [],
                    competitionResults: $studentData['competitionResults'] ?? []
                );
            }
        }
        return null;
    }

    /**
     * @return Student[]
     */
    public function getAll(): array
    {
        if (!empty($this->cachedStudents)) {
            return $this->cachedStudents;
        }

        $data = $this->getApiData('/api/student');
        $students = [];

        foreach ($data as $item) {
            $studentModel = new Student(
                id: $item['id'],
                firstName: $item['firstName'],
                lastName: $item['lastName'],
                isMale: $item['isMale'],
                isRegistrationFinalized: $item['isRegistrationFinalized'],
                classId: key($item['class']),
                competitions: $item['competitions'] ?? [],
                competitionResults: $item['competitionResults'] ?? []
            );
            $students[] = $studentModel;

            $this->cachedStudents[$item['id']] = $studentModel;
        }
        return $students;
    }

    /**
     * @param Student $model
     * @return array
     */
    public function create(object $model): array
    {
        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'isMale' => $model->getIsMale(),
            'isRegistrationFinalized' => $model->getIsRegistrationFinalized(),
            'class' => [
                $model->getClassId() => ClassController::getInstance()->getClassName($model->getClassId())
            ],
            'competitions' => empty($model->getCompetitions) ? null : $model->getCompetitions(),
            'competitionResults' => empty($model->getCompetitionResults()) ? null : $model->getCompetitionResults(),
        ];

        $createResult = $this->sendApiRequest('/api/student', 'POST', $data);
        return $createResult;
    }

    /**
     * @param Student $model
     * @return array
     */
    public function update(object $model): array
    {
        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'isMale' => $model->getIsMale(),
            'isRegistrationFinalized' => $model->getIsRegistrationFinalized(),
            'classId' => $model->getClassId(),
            'competitions' => $model->getCompetitions(),
            'competitionResults' => $model->getCompetitionResults()
        ];

        $updateResult = $this->sendApiRequest("/api/student", 'PUT', $data);
        return $updateResult;
    }

    /**
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        $deleteResult = $this->sendApiRequest("/api/student/$id", 'DELETE');
        return $deleteResult;
    }


    public function patch(int $id, array $data, string $operation): array
    {
        $patchDocument = [];
        foreach ($data as $key => $value) {
            $patchDocument[] = [
                "op" => $operation,
                "path" => "/$key",
                "value" => $value
            ];
        }

        $patchResult = $this->sendApiRequest(
            "/api/student/$id",
            'PATCH',
            $patchDocument,
            "application/json-patch+json"
        );

        if (!$patchResult['success']) {
            return $patchResult;
        }

        unset($_SESSION['overview_competitions_timestamp']);
        unset($_SESSION['overview_students_timestamp']);

        return $patchResult;
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
    protected function sendApiRequest(string $endpoint, string $method, array $data = [], string $contentType = 'application/json'): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $contentType
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
            $errorMessage = $responseData['errors'][0]['description'] ?? 'Unbekannter Fehler';

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
