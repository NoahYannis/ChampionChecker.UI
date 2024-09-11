<?php

namespace MVVM\Controller;

use MVVM\Model\ClassModel;
use RuntimeException;

/**
 * @implements IController<ClassModel>
 */
class ClassController implements IController {
    private string $apiUrl;

    public function __construct() {
        $config = require  $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    /**
     * @param int $id
     * @return ClassModel|null
     */
    public function getById(int $id): ?ClassModel {
        $data = $this->getApiData("api/class/$id");
        if (isset($data['id'])) {
            return new ClassModel(
                $data['id'],
                $data['name'],
                $data['students'] ?? [],
                $data['pointsAchieved'],
                $data['classTeacherId'] ?? null
            );
        }
        return null;
    }

    /**
     * @return ClassModel[]
     */
    public function getAll(): array {
        $data = $this->getApiData('api/class');
        foreach ($data as $item) {
            $classes[] = new ClassModel(
                $item['id'],
                $item['name'],
                $item['students'] ?? [],
                $item['pointsAchieved'],
                $item['classTeacherId'] ?? null
            );
        }
        return $classes ?? [];
    }

    /**
     * @param ClassModel $model
     * @return void
     */
    public function create(object $model): void {
        if (!$model instanceof ClassModel) {
            throw new \InvalidArgumentException('Model must be an instance of ClassModel.');
        }

        $data = [
            'name' => $model->getName(),
            'students' => $model->getStudents(),
            'pointsAchieved' => $model->getPointsAchieved(),
            'classTeacherId' => $model->getClassTeacherId()
        ];

        $this->sendApiRequest('api/class', 'POST', $data);
    }

    /**
     * @param ClassModel $model
     * @return void
     */
    public function update(object $model): void {
        if (!$model instanceof ClassModel) {
            throw new \InvalidArgumentException('Model must be an instance of ClassModel.');
        }

        $data = [
            'name' => $model->getName(),
            'students' => $model->getStudents(),
            'pointsAchieved' => $model->getPointsAchieved(),
            'classTeacherId' => $model->getClassTeacherId()
        ];

        $this->sendApiRequest("api/class/{$model->getId()}", 'PUT', $data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void {
        $this->sendApiRequest("api/class/$id", 'DELETE');
    }


    /**
     * @param string $endpoint
     * @return mixed
     */
    public function getApiData(string $endpoint) {
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
    private function sendApiRequest(string $endpoint, string $method, array $data = []): void {
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