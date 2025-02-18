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
    private static ?CompetitionResultController $instance = null;
    private string $apiUrl;

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    public static function getInstance(): CompetitionResultController
    {
        if (self::$instance === null) {
            self::$instance = new CompetitionResultController();
        }
        return self::$instance;
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
     * @return array
     */
    public function create(object $model): array
    {
        $data = [
            'pointsAchieved' => $model->getPointsAchieved(),
            'competitionId' => $model->getCompetitionId(),
            'classId' => $model->getClassId(),
            'studentId' => $model->getStudentId()
        ];

        $createResult = $this->sendApiRequest('/api/competitionresult', 'POST', $data);

        if ($createResult['success'] === true) {
            unset($_SESSION['competitionResultsTimestamp']);
            unset($_SESSION['results_competitionResultsTimestamp']);
        }

        return $createResult;
    }

    /**
     * @param CompetitionResult $model
     * @return array
     */
    public function update(object $model): array
    {
        $data = [
            'pointsAchieved' => $model->getPointsAchieved(),
            'competitionId' => $model->getCompetitionId(),
            'classId' => $model->getClassId(),
            'studentId' => $model->getStudentId()
        ];

        $updateResult = $this->sendApiRequest("/api/competitionresult/{$model->getId()}", 'PUT', $data);
        return $updateResult;
    }

    /**
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        $deleteResult = $this->sendApiRequest("/api/competitionresult/$id", 'DELETE');

        if (!$deleteResult['success'] || !isset($_SESSION['results_competitionResults'])) {
            return $deleteResult;
        }

        unset($_SESSION['competitionResultsTimestamp']);
        return $deleteResult;
    }

    public function patch(int $id, array $data, string $operation): array
    {
        // https://jsonpatch.com/
        foreach ($data as $key => $value) {
            $patchDocument[] = [
                "op" => $operation,
                "path" => "/$key",
                "value" => $value
            ];
        }

        $patchResult = $this->sendApiRequest(
            "/api/competitionresult/$id",
            'PATCH',
            $patchDocument,
            "application/json-patch+json",
        );

        if (!$patchResult['success'] || !isset($_SESSION['results_competitionResults'])) {
            return $patchResult;
        }

        // Cache-Eintrag aktualisieren
        foreach ($_SESSION['results_competitionResults'] as &$competitionResult) {
            if ($competitionResult->getId() !== $id) {
                continue;
            }

            foreach ($data as $key => $value) {
                $setterMethod = 'set' . ucfirst($key);
                if (method_exists($competitionResult, $setterMethod)) {
                    $competitionResult->$setterMethod($value);
                }
            }
            break;
        }

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
    private function sendApiRequest(string $endpoint, string $method, array $data = [], string $contentType = 'application/json'): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode(value: $data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: $contentType"
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
            return [
                'success' => false,
                'error' => "API request failed with status code $statusCode. Response: $response"
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'response' => $response
        ];
    }
}
