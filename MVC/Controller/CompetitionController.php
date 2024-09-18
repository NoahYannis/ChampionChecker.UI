<?php

namespace MVC\Controller;

use MVC\Model\Competition;
use RuntimeException;
use DateTime;

/**
 * @implements IController<Competition>
 */
class CompetitionController implements IController {
    private string $apiUrl;

    public function __construct() {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }

    /**
     * @param int $id
     * @return Competition|null
     */
    public function getById(int $id): ?Competition {
        $data = $this->getApiData("/api/competition/$id");
        if (isset($data['id'])) {
            return new Competition(
                id: $data['id'],
                name: $data['name'],
                classParticipants: $data['classParticipants'] ?? [],
                studentParticipants: $data['studentParticipants'] ?? [],
                date: $data['date'],
                refereeId: $data['refereeId'],
                referee: $data['referee'],
            );
        }
        return null;
    }

    /**
     * @return Competition[]
     */
    public function getAll(): array {
        $data = $this->getApiData('/api/competition');
        foreach ($data as $item) {
            $competitions[] = new Competition(
                id: $item['id'],
                name: $item['name'],
                classParticipants: $item['classParticipants'] ?? [],
                studentParticipants: $item['studentParticipants'] ?? [],
                isTeam: $item['isTeam'],
                isMale: $item['isMale'],
                date: new DateTime($item['date']),
                refereeId: $item['refereeId'],
                referee: $item['referee'],
            );
        }
        return $competitions ?? [];
    }

    /**
     * @param Competition $model
     * @return void
     */
    public function create(object $model): void {
        if (!$model instanceof Competition) {
            throw new \InvalidArgumentException('Model must be an instance of Competition.');
        }

        $data = [
            'name' => $model->getName(),
            'classParticipants' => $model->getClassParticipants(),
            'studentParticipants' => $model->getStudentParticipants(),
            'date' => $model->getDate(),
            'refereeId' => $model->getRefereeId(),
            'referee' => $model->getReferee()
        ];

        $this->sendApiRequest('/api/competition', 'POST', $data);
    }

    /**
     * @param Competition $model
     * @return void
     */
    public function update(object $model): void {
        if (!$model instanceof Competition) {
            throw new \InvalidArgumentException('Model must be an instance of Competition.');
        }

        $data = [
            'name' => $model->getName(),
            'classParticipants' => $model->getClassParticipants(),
            'studentParticipants' => $model->getStudentParticipants(),
            'date' => $model->getDate(),
            'refereeId' => $model->getRefereeId(),
            'referee' => $model->getReferee()
        ];

        $this->sendApiRequest("/api/competition/{$model->getId()}", 'PUT', $data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void {
        $this->sendApiRequest("/api/competition/$id", 'DELETE');
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