<?php

namespace MVC\Controller;

use MVC\Model\CompetitionResult;
use MVC\Controller\IController;
use RuntimeException;

/**
 * @implements IController<CompetitionResult>
 */
class CompetitionResultController implements IController
{
    
    private string $apiUrl;

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }


    /**
     * @param int $id
     * @return CompetitionResult|null
     */
    public function getById(int $id): ?CompetitionResult
    {
        $data = $this->getApiData("/api/competitionresult/$id");
        if (isset($data['id'])) {
            return new CompetitionResult(
                id: $data['id'],
                pointsAchieved: $data['pointsAchieved'],
                competitionId: $data['competitionId'],
                classId: $data['classId'],
                studentId: $data['studentId']
            );
        }
        return null;
    }

    /**
     * @return CompetitionResult[]
     */
    public function getAll(): array
    {
        $data = $this->getApiData('/api/competitionresult');
        foreach ($data as $item) {
            $results[] = new CompetitionResult(
                id: $item['id'],
                pointsAchieved: $item['pointsAchieved'],
                competitionId: $item['competitionId'],
                classId: $item['classId'],
                studentId: $item['studentId']
            );
        }
        return $results ?? [];
    }

    /**
     * @param CompetitionResult $model
     * @return void
     */
    public function create(object $model): void
    {
        if (!$model instanceof CompetitionResult) {
            throw new \InvalidArgumentException('Model must be an instance of CompetitionResult.');
        }

        $data = [
            'pointsAchieved' => $model->getPointsAchieved(),
            'competitionId' => $model->getCompetitionId(),
            'classId' => $model->getClassId(),
            'studentId' => $model->getStudentId()
        ];

        $this->sendApiRequest('/api/competitionresult', 'POST', $data);
    }

    /**
     * @param CompetitionResult $model
     * @return void
     */
    public function update(object $model): void
    {
        if (!$model instanceof CompetitionResult) {
            throw new \InvalidArgumentException('Model must be an instance of CompetitionResult.');
        }

        $data = [
            'pointsAchieved' => $model->getPointsAchieved(),
            'competitionId' => $model->getCompetitionId(),
            'classId' => $model->getClassId(),
            'studentId' => $model->getStudentId()
        ];

        $this->sendApiRequest("/api/competitionresult/{$model->getId()}", 'PUT', $data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->sendApiRequest("/api/competitionresult/$id", 'DELETE');
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
        try {

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
    
            $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
    
            if ($statusCode >= 400) {
                throw new RuntimeException("API request failed with status code $statusCode. Response: $response");
            }
        } catch (RuntimeException $e) {
            echo 'Ein Fehler ist aufgetreten: ' . $e->getMessage();
        }
    }
}
