<?php

namespace MVC\Controller;

use MVC\Model\Competition;
use RuntimeException;
use DateTime;
use MVC\Model\CompetitionStatus;

/**
 * @implements IController<Competition>
 */
class CompetitionController implements IController
{

    private static ?CompetitionController $instance = null;
    private array $cachedCompetitions = [];
    private string $apiUrl;

    public function __construct()
    {
        $config = require $_SERVER['DOCUMENT_ROOT'] . '/ChampionChecker.UI/config.php';
        $this->apiUrl = $config['api_url'];
    }


    public static function getInstance(): CompetitionController
    {
        if (self::$instance === null) {
            self::$instance = new CompetitionController();
        }
        return self::$instance;
    }

    /**
     * @param int $id
     * @return Competition|null
     */
    public function getById(int $id): ?Competition
    {

        if (isset($this->cachedCompetitions[$id])) {
            return $this->cachedCompetitions[$id];
        }

        $data = $this->getApiData("/api/competition/$id");

        if (isset($data['id'])) {
            $competition = new Competition(
                id: $data['id'],
                name: $data['name'],
                classParticipants: $data['classParticipants'] ?? [],
                studentParticipants: $data['studentParticipants'] ?? [],
                date: $data['date'],
                refereeId: $data['refereeId'],
                referee: $data['referee'],
                status: CompetitionStatus::from($data['status']),
                additionalInfo: $data['additionalInfo']
            );

            $this->cachedCompetitions[$id] = $competition;

            return $competition;
        }

        return null;
    }

    /**
     * @return Competition[]
     */
    public function getAll(): array
    {

        if (!empty($this->cachedCompetitions)) {
            return $this->cachedCompetitions;
        }

        $data = $this->getApiData('/api/competition');
        $competitions = [];

        foreach ($data as $item) {
            $competition = new Competition(
                id: $item['id'],
                name: $item['name'],
                classParticipants: $item['classParticipants'] ?? [],
                studentParticipants: $item['studentParticipants'] ?? [],
                isTeam: $item['isTeam'],
                isMale: $item['isMale'],
                date: new DateTime($item['date']),
                refereeId: $item['refereeId'],
                referee: $item['referee'],
                status: CompetitionStatus::from($item['status']),
                additionalInfo: $item['additionalInfo']
            );

            $this->cachedCompetitions[$item['id']] = $competition;
            $competitions[] = $competition;
        }

        // Wettbewerbe Sortieren
        return $competitions;
    }

    /**
     * @param Competition $model
     * @return array
     */
    public function create(object $model): array
    {
        if (!$model instanceof Competition) {
            throw new \InvalidArgumentException('Model must be an instance of Competition.');
        }

        $data = [
            'name' => $model->getName(),
            'classParticipants' => $model->getClassParticipants(),
            'studentParticipants' => $model->getStudentParticipants(),
            'date' => $model->getDate(),
            'refereeId' => $model->getRefereeId(),
            'referee' => $model->getReferee(),
            'status' => $model->getStatus(),
            'additionalInfo' => $model->getAdditionalInfo()
        ];

        $createResult = $this->sendApiRequest('/api/competition', 'POST', $data);
        return $createResult;
    }

    /**
     * @param Competition $model
     * @return array
     */
    public function update(object $model): array
    {
        if (!$model instanceof Competition) {
            throw new \InvalidArgumentException('Model must be an instance of Competition.');
        }

        $data = [
            'id' => $model->getId(),
            'name' => $model->getName(),
            'classParticipants' => $model->getClassParticipants(),
            'studentParticipants' => $model->getStudentParticipants(),
            'isTeam' => $model->getIsTeam(),
            'isMale' => $model->getIsMale(),
            'date' => $model->getDate()->format(DateTime::ATOM),
            'refereeId' => $model->getRefereeId(),
            'referee' => $model->getReferee(),
            'status' => $model->getStatus(),
            'additionalInfo' => $model->getAdditionalInfo()
        ];

        $updateResult = $this->sendApiRequest("/api/competition", 'PUT', $data);

        if ($updateResult['success'] === true && isset($_SESSION['overview_competitions'])) {
            foreach ($_SESSION['overview_competitions'] as $key => $comp) {
                if ($comp->getId() === $model->getId()) {
                    $_SESSION['overview_competitions'][$key] = $model;
                    $_SESSION['overview_competitions_timestamp'] = time();

                    if ($comp->getIsTeam()) {
                        unset($_SESSION['classresult_competitions']);
                    } else {
                        unset($_SESSION['soloresult_competitions']);
                    }
                    
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
        $deleteResult = $this->sendApiRequest("/api/competition/$id", 'DELETE');
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
    private function sendApiRequest(string $endpoint, string $method, array $data = []): array
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
