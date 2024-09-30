<?php

namespace MVC\Controller;

use MVC\Model\ClassModel;
use RuntimeException;

/**
 * @implements IController<ClassModel>
 */
class ClassController implements IController
{
    private static ?ClassController $instance = null;
    private string $apiUrl;
    private array $cachedClasses = [];

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    public static function getInstance(): ClassController
    {
        if (self::$instance === null) {
            self::$instance = new ClassController();
        }
        return self::$instance;
    }

    /**
     * @param int $id
     * @return ClassModel|null
     */
    public function getById(int $id): ?ClassModel
    {
        if (isset($this->cachedClasses[$id])) {
            return $this->cachedClasses[$id];
        }

        $data = $this->getApiData("/api/class/$id");

        if (isset($data['id'])) {
            $classModel = new ClassModel(
                id: $data['id'],
                name: $data['name'],
                students: $data['students'] ?? [],
                pointsAchieved: $data['pointsAchieved'],
                classTeacherId: $data['classTeacherId'] ?? null
            );

            // Klasse im Cache speichern
            $this->cachedClasses[$id] = $classModel;
            return $classModel;
        }
        return null;
    }

    public function getClassName($classId): string
    {
        if (isset($_SESSION['classes']) && isset($_SESSION['classes'][$classId])) {
            return $_SESSION['classes'][$classId];
        }

        $class = $this->getById($classId);
        $className = $class->getName();
        $_SESSION['classes'][$classId] = $className;
        return $className;
    }


    public function getByName(string $name): ?ClassModel
    {
        foreach ($this->cachedClasses as $classModel) {
            if ($classModel->getName() === $name) {
                return $classModel; // Gecachte Klasse zurückgeben
            }
        }

        $allClasses = $this->getApiData("/api/class");

        if (empty($allClasses)) {
            return null;
        }

        foreach ($allClasses as $classData) {
            if (isset($classData['name']) && $classData['name'] === $name) {
                return new ClassModel(
                    id: $classData['id'],
                    name: $classData['name'],
                    students: $classData['students'] ?? [],
                    pointsAchieved: $classData['pointsAchieved'] ?? 0,
                    classTeacherId: $classData['classTeacherId'] ?? null
                );
            }
        }
    }


    /**
     * @return ClassModel[]
     */
    public function getAll(): array
    {
        // Überprüfen, ob die Klassen bereits im Cache sind
        if (!empty($this->cachedClasses)) {
            return $this->cachedClasses;
        }

        $data = $this->getApiData('/api/class');
        $classes = [];

        foreach ($data as $item) {
            $classModel = new ClassModel(
                id: $item['id'],
                name: $item['name'],
                students: $item['students'] ?? [],
                pointsAchieved: $item['pointsAchieved'],
                classTeacherId: $item['classTeacherId'] ?? null
            );
            $classes[] = $classModel;

            // Klasse im Cache speichern
            $this->cachedClasses[$item['id']] = $classModel;
        }
        return $classes;
    }

    /**
     * @param ClassModel $model
     * @return void
     */
    public function create(object $model): void
    {
        if (!$model instanceof ClassModel) {
            throw new \InvalidArgumentException('Model must be an instance of ClassModel.');
        }

        $data = [
            'name' => $model->getName(),
            'students' => $model->getStudents(),
            'pointsAchieved' => $model->getPointsAchieved(),
            'classTeacherId' => $model->getClassTeacherId()
        ];

        $this->sendApiRequest('/api/class', 'POST', $data);
    }

    /**
     * @param ClassModel $model
     * @return void
     */
    public function update(object $model): void
    {
        if (!$model instanceof ClassModel) {
            throw new \InvalidArgumentException('Model must be an instance of ClassModel.');
        }

        $data = [
            'name' => $model->getName(),
            'students' => $model->getStudents(),
            'pointsAchieved' => $model->getPointsAchieved(),
            'classTeacherId' => $model->getClassTeacherId()
        ];

        $this->sendApiRequest("/api/class/{$model->getId()}", 'PUT', $data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->sendApiRequest("/api/class/$id", 'DELETE');
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
