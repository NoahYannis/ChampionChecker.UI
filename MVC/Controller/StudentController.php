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
        if (isset($this->cachedStudents[$id])) {
            return $this->cachedStudents[$id];
        }

        $data = $this->getApiData("/api/student/$id");

        if (isset($data['id'])) {
            $studentModel = new Student(
                id: $data['id'],
                firstName: $data['firstName'],
                lastName: $data['lastName'],
                isMale: $data['isMale'],
                classId: $data['classId'],
                competitions: $data['competitions'] ?? [],
                competitionResults: $data['competitionResults'] ?? []
            );

            // Student im Cache speichern
            $this->cachedStudents[$id] = $studentModel;
            return $studentModel;
        }
        return null;
    }

    public function getByName(string $name): ?Student
    {
        foreach ($this->cachedStudents as $studentModel) {
            if ($studentModel->getFirstName() === $name || $studentModel->getLastName() === $name) {
                return $studentModel; // Gecachten Studenten zurückgeben
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
                classId: $item['classId'],
                competitions: $item['competitions'] ?? [],
                competitionResults: $item['competitionResults'] ?? []
            );
            $students[] = $studentModel;

            // Student im Cache speichern
            $this->cachedStudents[$item['id']] = $studentModel;
        }
        return $students;
    }

    /**
     * @param Student $model
     * @return void
     */
    public function create(object $model): void
    {
        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'isMale' => $model->getIsMale(),
            'classId' => $model->getClassId(),
            'competitions' => $model->getCompetitions(),
            'competitionResults' => $model->getCompetitionResults()
        ];

        $this->sendApiRequest('/api/student', 'POST', $data);
    }

    /**
     * @param Student $model
     * @return void
     */
    public function update(object $model): void
    {
        $data = [
            'firstName' => $model->getFirstName(),
            'lastName' => $model->getLastName(),
            'isMale' => $model->getIsMale(),
            'classId' => $model->getClassId(),
            'competitions' => $model->getCompetitions(),
            'competitionResults' => $model->getCompetitionResults()
        ];

        $this->sendApiRequest("/api/student/{$model->getId()}", 'PUT', $data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->sendApiRequest("/api/student/$id", 'DELETE');
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
    private function sendApiRequest(string $endpoint, string $method, array $data = []): void
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
