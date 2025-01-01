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

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
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



    public function getIdFromName($className): int
    {
        $className = strtoupper($className);

        if (isset($_SESSION['classes'])) {
            foreach ($_SESSION['classes'] as $class) {
                if (strtoupper(trim($class->getName())) === strtoupper(trim($className))) {
                    return $class->getId();
                }
            }
        }

        $class = $this->getByName($className);

        if ($class === null) {
            return -1;
        }

        $classId = $class->getId();
        return $classId;
    }

    public function getClassName($classId): string
    {
        if (isset($_SESSION['classes'])) {
            foreach ($_SESSION['classes'] as $class) {
                if ($class->getId() === $classId) {
                    return $class->getName();
                }
            }
        }

        $class = $this->getById($classId);

        if ($class === null) {
            return "???";
        }

        $className = $class->getName();
        return $className;
    }

    public function getByName(string $name): ?ClassModel
    {
        $name = strtoupper(trim($name));

        if (isset($_SESSION['classes'])) {
            foreach ($_SESSION['classes'] as $class) {
                if (strtoupper(trim($class->getName())) === $name) {
                    return $class;
                }
            }
        }

        $allClasses = $this->getApiData("/api/class");

        if (empty($allClasses)) {
            return null;
        }

        foreach ($allClasses as $classData) {
            if (isset($classData['name']) && strtoupper(trim($classData['name'])) === $name) {
                return new ClassModel(
                    id: $classData['id'],
                    name: $classData['name'],
                    students: $classData['students'] ?? [],
                    pointsAchieved: $classData['pointsAchieved'] ?? 0,
                    classTeacherId: $classData['classTeacherId'] ?? null
                );
            }
        }

        return null; // Es wurde keine Klasse mit dem Namen gefunden
    }


    /**
     * @return ClassModel[]
     */
    public function getAll(): array
    {
        // Überprüfen, ob die Klassen bereits im Cache sind
        if (!empty($_SESSION['classes'])) {
            return $_SESSION['classes'];
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
     * @return array
     */
    public function create(object $model): array
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

        $createResult = $this->sendApiRequest('/api/class', 'POST', $data);

        if ($createResult['success'] === true && isset($_SESSION['classes'])) {
            $_SESSION['classes'][] = $model;
        }

        return $createResult;
    }

    /**
     * @param ClassModel $model
     * @return array
     */
    public function update(object $model): array
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

        $updateResult = $this->sendApiRequest("/api/class/{$model->getId()}", 'PUT', $data);

        if (isset($_SESSION['classes'])) {
            foreach ($_SESSION['classes'] as $key => $class) {
                if ($class->getId() === $model->getId()) {
                    $_SESSION['classes'][$key] = $model;
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
        $deleteResult = $this->sendApiRequest("/api/class/$id", 'DELETE');

        if (isset($_SESSION['classes'])) {
            foreach ($_SESSION['classes'] as $key => $class) {
                if ($class->getId() === $id) {
                    unset($_SESSION['classes'][$key]);
                    break;
                }
            }
        }

        return $deleteResult;
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
            CURLOPT_USERAGENT => 'PHP API Request'
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . curl_error($curl)
            ];
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode >= 400) {
            return [
                'success' => false,
                'error' => "API request failed with status code $statusCode.: $response"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }
}
