<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 18px;
            text-align: left;
        }

        th,
        td {
            padding: 8px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            box-sizing: border-box;
        }

        caption {
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>

</head>

<body>

    <?php
    $participantsData = file_get_contents('php://input');
    $participants = json_decode($participantsData, true);
    $numPlayers = count($participants);
    $playerID = [];

    $players = [];
    foreach ($participants as $id => $participant) {
        $players[] = $participant['firstName'] . ' ' . $participant['lastName'];
        $playerID[] =  $id;
    }

    // Stationsspielplan erstellen
    function generateRoundRobinSchedule($numPlayers)
    {
        $schedule = [];
        $players = range(1, $numPlayers);

        if ($numPlayers % 2 == 1) {
            $players[] = "BYE"; // Add a bye if odd number of players
            $numPlayers++;
        }

        $numRounds = $numPlayers - 1;
        for ($round = 0; $round < $numRounds; $round++) {
            $roundMatches = []; // Reset the roundMatches array for each round
            for ($i = 0; $i < $numPlayers / 2; $i++) {
                $player1 = $players[$i];
                $player2 = $players[$numPlayers - 1 - $i];

                // Exclude matches involving "bye"
                if ($player1 !== "BYE" && $player2 !== "BYE") {
                    $roundMatches[] = ['player1' => $player1, 'player2' => $player2];
                }
            }

            // Shuffle matches to prevent back-to-back same players if possible
            shuffle($roundMatches);
            $schedule = array_merge($schedule, $roundMatches);
            // Rotate players (except first player)
            array_splice($players, 1, 0, array_pop($players));
        }

        return $schedule;
    }

    $matches = generateRoundRobinSchedule($numPlayers);
    ?>

    <table>
        <caption>Tischtennis Turnier bis 11 P.; 2 P. Abstand; Jeder gegen Jeden</caption>

        <?php foreach ($matches as $match): ?>
            <tr class="match-row" data-player1="<?= $match['player1'] ?>" data-player2="<?= $match['player2'] ?>">
                <td style="text-align: right;"><?= $match['player1'] ?></td>
                <td><?= isset($players[$match['player1'] - 1]) ? $players[$match['player1'] - 1] : "?" ?></td>
                <td style="text-align: right;"><?= $match['player2'] ?></td>
                <td><?= isset($players[$match['player2'] - 1]) ? $players[$match['player2'] - 1] : "?" ?></td>
                <td>
                    <div style="display: flex; gap: 10px; align-items: left; justify-content: left;">
                        <input type="number" class="points-player1" style="width: 70px; text-align: right;" maxlength="2" />
                        :
                        <input type="number" class="points-player2" style="width: 70px; text-align: right;" maxlength="2" />
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Auswertungstabelle -->
    <table id="evaluation-table" class="table-style">
        <caption>Endergebnis (Sieg 2P. Unentschieden 1P.)</caption>
        <thead>
            <tr>
                <th>Nr.</th>
                <th colspan="2">Name</th>
                <th>Stationspunkte</th>
                <th>kl. Punkte</th>
                <th>Turnierpunkte</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i <= $numPlayers; $i++): ?>
                <tr class="result-row" data-player-id="<?= $i ?>" dataplayer="<?= $players[$i - 1] ?>">
                    <td data-id="<?= $playerID[$i - 1] ?>"><?= $i ?>.</td>
                    <td colspan="2"><?= isset($players[$i - 1]) ? $players[$i - 1] : "" ?></td>
                    <td><span class="total-points" style="width: 70px; text-align: right;"></span></td>
                    <td><span class="small-points" style="width: 70px; text-align: right;"></span></td>
                    <td data-column="pointsAchieved">
                        <span name="extra_punkte_<?= $i ?>" style="width: 70px; text-align: right;"></span>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <script>
        document.querySelectorAll('.match-row input[type="text"]').forEach(input => {
            const player = input.name.split('_')[1];
            input.addEventListener('input', () => updateText(input, player));
        });

        document.querySelectorAll('.match-row input[type="number"]').forEach(input => {
            input.addEventListener('input', () => validateNumber(input));
        });

        function updateText(input, player) {
            const value = input.value;
            const allInputs = document.querySelectorAll(`[name="player_${player}"]`);
            allInputs.forEach(inputElement => inputElement.value = value);
        }

        const playerPoints = {};

        document.querySelectorAll('.result-row').forEach(row => {
            const playerId = row.getAttribute('data-player-id');
            playerPoints[playerId] = {
                matchesWon: 0,
                smallPoints: 0
            };
        });

        // Gültigkeit eingegebener Zahlen prüfen
        function validateNumber(input) {
            input.value = input.value.replace(/\D/g, ''); // Remove non-numeric characters

            // Bei über zwei Stellen diese kürzen
            if (input.value.length > 2) {
                input.value = input.value.slice(0, 2);
            }

            updateScores();
        }

        function updateScores() {
            for (const playerId in playerPoints) {
                playerPoints[playerId].matchesWon = 0;
                playerPoints[playerId].smallPoints = 0;
            }

            document.querySelectorAll('.match-row').forEach(row => {
                const player1 = row.getAttribute('data-player1');
                const player2 = row.getAttribute('data-player2');
                const player1Points = parseInt(row.querySelector('.points-player1').value || 0, 10);
                const player2Points = parseInt(row.querySelector('.points-player2').value || 0, 10);

                playerPoints[player1].smallPoints += player1Points;
                playerPoints[player2].smallPoints += player2Points;

                if (player1Points > player2Points) {
                    playerPoints[player1].matchesWon += 2; // 2 Punkte für Gewinner
                } else if (player1Points < player2Points) {
                    playerPoints[player2].matchesWon += 2;
                } else if (player1Points === player2Points) {
                    playerPoints[player1].matchesWon += 1; // Unentschieden
                    playerPoints[player2].matchesWon += 1;
                }
            });

            // Auswertungstabelle aktualisieren
            document.querySelectorAll('.result-row').forEach(row => {
                const playerId = row.getAttribute('data-player-id');
                row.querySelector('.total-points').textContent = playerPoints[playerId].matchesWon;
                row.querySelector('.small-points').textContent = playerPoints[playerId].smallPoints;
            });

            const players = [];
            document.querySelectorAll('.result-row').forEach(row => {
                const playerId = row.getAttribute('data-player-id');
                const totalPoints = parseInt(row.querySelector('.total-points').textContent || 0, 10);
                const smallPoints = parseInt(row.querySelector('.small-points').textContent || 0, 10);

                players.push({
                    playerId: playerId,
                    totalPoints: totalPoints,
                    smallPoints: smallPoints,
                    rank: 0,
                    extraPoints: 0
                });
            });

            // Spieler nach erreichter Punktzahl sortieren
            players.sort((a, b) => {
                if (b.totalPoints !== a.totalPoints) {
                    return b.totalPoints - a.totalPoints;
                }
                return b.smallPoints - a.smallPoints; // Bei Gleichstand werden die kleinen Punkte geprüft
            });

            let currentRank = 1;
            for (let i = 0; i < players.length; i++) {
                if (i > 0 &&
                    players[i].totalPoints === players[i - 1].totalPoints &&
                    players[i].smallPoints === players[i - 1].smallPoints) {
                    players[i].rank = players[i - 1].rank;
                } else {
                    players[i].rank = currentRank;
                }

                if (players[i].rank === 1) {
                    players[i].extraPoints = 5;
                } else if (players[i].rank === 2) {
                    players[i].extraPoints = 3;
                } else if (players[i].rank === 3) {
                    players[i].extraPoints = 2;
                } else {
                    players[i].extraPoints = 1;
                }

                currentRank++;
            }

            document.querySelectorAll('.result-row').forEach(row => {
                const playerId = row.getAttribute('data-player-id');
                const player = players.find(p => p.playerId == playerId);
                row.querySelector('span[name="extra_punkte_' + playerId + '"]').textContent = player.extraPoints;
            });

        }
    </script>
</body>

</html>