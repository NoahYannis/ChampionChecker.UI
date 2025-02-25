<!-- Das Formular für alle Turnier-Stationen (derzeit nur Tischtennis) -->

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
        input[type="text"], input[type="number"] {
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
    // Receive the participants data via POST
    $participantsData = json_decode(file_get_contents('php://input'), true);
    $numPlayers = count($participantsData);

    // Set players' names based on participants
    $players = [];
    foreach ($participantsData as $participant) {
        $players[] = $participant['firstName'] . ' ' . $participant['lastName'];
    }

    // create an array to later check for name changes
    $originalplayers = $players;

    // Generate a round-robin schedule
    function generateRoundRobinSchedule($numPlayers) {
        $schedule = [];
        $players = range(1, $numPlayers); // Number the players from 1 to N
 
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
                if($player1 !== "BYE" && $player2 !== "BYE") {
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

    <!-- Region Tischtennis weiblich -->
    <table>
        <caption>Tischtennis Turnier (Weiblich) bis 11 P.; 2 P. Abstand; Jeder gegen Jeden</caption>
        
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

    <!-- Region Endergebnis -->
    <table class="table-style">
        <caption>Endergebnis (Sieg 2P. Unentschieden 1P.)</caption>
        <thead>
            <tr>
                <th>NR</th>
                <th colspan="2">Name</th>
                <th>Punkte</th>
                <th>kl. Punkte</th>
                <th>Platz</th>
                <th>Punkte</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i <= $numPlayers; $i++): ?>
                <tr class="result-row" data-player-id="<?= $i ?>" dataplayer="<?= $player[$i] ?>">
                    <td><?= $i ?>.</td>
                    <td colspan="2"><?= isset($players[$i - 1]) ? $players[$i - 1] : "" ?>
                    </td>
                    <td><input type="text" class="total-points" style="width: 70px; text-align: right;" maxlength="3" required /></td>
                    <td><input type="text" class="small-points" style="width: 70px; text-align: right;" maxlength="3" required /></td>
                    <td><input type="text" name="platz_<?= $i ?>" style="width: 70px; text-align: right;" maxlength="2" /></td>
                    <td><input type="text" name="extra_punkte_<?= $i ?>" style="width: 70px; text-align: right;" maxlength="2" /></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <script>
    // Register event listeners
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

    // Object to track player points
    const playerPoints = {};

    // Initialize playerPoints with default values
    document.querySelectorAll('.result-row').forEach(row => {
        const playerId = row.getAttribute('data-player-id');
        playerPoints[playerId] = {
            matchesWon: 0,
            smallPoints: 0
        };
    });

    // Validate input to ensure it is a number
    function validateNumber(input) {
        input.value = input.value.replace(/\D/g, ''); // Remove non-numeric characters

        // Ensure the value does not exceed two digits (max 99)
        if (input.value.length > 2) {
            input.value = input.value.slice(0, 2); // Truncate to 2 digits
        }

        updateScores();
    }

    // Calculate and update scores
    function updateScores() {
        // Reset all points
        for (const playerId in playerPoints) {
            playerPoints[playerId].matchesWon = 0;
            playerPoints[playerId].smallPoints = 0;
        }

        // Calculate scores from matches table
        document.querySelectorAll('.match-row').forEach(row => {
            const player1 = row.getAttribute('data-player1');
            const player2 = row.getAttribute('data-player2');
            const player1Points = parseInt(row.querySelector('.points-player1').value || 0, 10);
            const player2Points = parseInt(row.querySelector('.points-player2').value || 0, 10);

            playerPoints[player1].smallPoints += player1Points;
            playerPoints[player2].smallPoints += player2Points;

            if (player1Points > player2Points) {
                playerPoints[player1].matchesWon += 2; // Winner gets 2 points
            } else if (player1Points < player2Points) {
                playerPoints[player2].matchesWon += 2;
            } else if (player1Points === player2Points) {
                playerPoints[player1].matchesWon += 1; // Draw
                playerPoints[player2].matchesWon += 1;
            }
        });

        // Update results table
        document.querySelectorAll('.result-row').forEach(row => {
            const playerId = row.getAttribute('data-player-id');
            row.querySelector('.total-points').value = playerPoints[playerId].matchesWon;
            row.querySelector('.small-points').value = playerPoints[playerId].smallPoints;
        });

        // After updating total points and small points, calculate ranks and extra points
        calculateRankings();
    }

    function calculateRankings() {
        // Create an array of players with their data
        const players = [];
        document.querySelectorAll('.result-row').forEach(row => {
            const playerId = row.getAttribute('data-player-id');
            const totalPoints = parseInt(row.querySelector('.total-points').value || 0, 10);
            const smallPoints = parseInt(row.querySelector('.small-points').value || 0, 10);

            players.push({
                playerId: playerId,
                totalPoints: totalPoints,
                smallPoints: smallPoints,
                rank: 0, // Placeholder for rank
                extraPoints: 0 // Placeholder for extra points
            });
        });

        // Sort players by total points, then by small points
        players.sort((a, b) => {
            if (b.totalPoints !== a.totalPoints) {
                return b.totalPoints - a.totalPoints; // Higher total points first
            }
            return b.smallPoints - a.smallPoints; // If tied, higher small points first
        });

        // Assign ranks and extra points based on ranking
        let currentRank = 1;
        for (let i = 0; i < players.length; i++) {
            if (i > 0 &&
                players[i].totalPoints === players[i - 1].totalPoints &&
                players[i].smallPoints === players[i - 1].smallPoints) {
                // Tie: Same rank as previous player
                players[i].rank = players[i - 1].rank;
            } else {
                // New rank
                players[i].rank = currentRank;
            }

            // Assign extra points based on rank (for example, 1st place gets 5 points, 2nd place gets 3 points, etc.)
            if (players[i].rank === 1) {
                players[i].extraPoints = 5; // 1st place gets 5 points
            } else if (players[i].rank === 2) {
                players[i].extraPoints = 3; // 2nd place gets 3 points
            } else if (players[i].rank === 3) {
                players[i].extraPoints = 2; // 3rd place gets 2 points
            } else {
                players[i].extraPoints = 1; // Everyone else gets 1 point
            }

            currentRank++;
        }

        // Update the "Platz" and "extra_punkte" columns in the results table
        players.forEach(player => {
            const row = document.querySelector(`.result-row[data-player-id="${player.playerId}"]`);
            row.querySelector('[name^="platz_"]').value = player.rank;
            row.querySelector('[name^="extra_punkte_"]').value = player.extraPoints;
        });
    }
</script>

</body>
</html>