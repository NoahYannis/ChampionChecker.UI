<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../../styles/solo_results.css" />
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

        caption {
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <?php
    $numPlayers = 5;

    // Matches data
    $matches = [
        ['player1' => 2, 'player2' => 1],
        ['player1' => 3, 'player2' => 4],
        ['player1' => 5, 'player2' => 1],
        ['player1' => 2, 'player2' => 3],
        ['player1' => 4, 'player2' => 5],
        ['player1' => 1, 'player2' => 3],
        ['player1' => 2, 'player2' => 4],
        ['player1' => 3, 'player2' => 5],
        ['player1' => 1, 'player2' => 4],
        ['player1' => 5, 'player2' => 2]
    ];
    ?>

    <!-- Region Tischtennis weiblich -->
    <table>
        <caption>Tischtennis Turnier (Weiblich) bis 11 P.; 2 P. Abstand; Jeder gegen Jeden</caption>
        <?php foreach ($matches as $index => $match): ?>
            <tr class="match-row" data-player1="<?= $match['player1'] ?>" data-player2="<?= $match['player2'] ?>">
                <td style="text-align: right;"><?= $match['player1'] ?></td>
                <td><input type="text" style=" text-align: left;" name="player_<?= $match['player1'] ?>" required oninput="updateText(this, <?= $match['player1'] ?>)" /></td>
                <td style="text-align: right;"><?= $match['player2'] ?></td>
                <td><input type="text" style=" text-align: left;" name="player_<?= $match['player2'] ?>" required oninput="updateText(this, <?= $match['player2'] ?>)" /></td>
                <td>
                    <div style="display: flex; gap: 10px; align-items: center; justify-content: center;">
                        <input type="number" class="points-player1" style="width: 50px; text-align: right;" maxlength="2" oninput="validateNumber(this)" />
                        :
                        <input type="number" class="points-player2" style="width: 50px; text-align: right;" maxlength="2" oninput="validateNumber(this)" />
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </tbody>
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
                <tr class="result-row" data-player-id="<?= $i ?>">
                    <td><?= $i ?>.</td>
                    <td colspan="2"><input type="text" name="player_<?= $i ?>" required style="text-align: left;" /></td>
                    <td><input type="text" class="total-points" readonly /></td>
                    <td><input type="text" class="small-points" readonly /></td>
                    <td><input type="text" name="platz_<?= $i ?>" /></td>
                    <td><input type="text" name="extra_punkte_<?= $i ?>" /></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>


    <script>
        // Update all text inputs for the same player
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

            // After updating total points and small points, calculate ranks
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
                    rank: 0 // Placeholder for rank
                });
            });

            // Sort players by total points, then by small points
            players.sort((a, b) => {
                if (b.totalPoints !== a.totalPoints) {
                    return b.totalPoints - a.totalPoints; // Higher total points first
                }
                return b.smallPoints - a.smallPoints; // If tied, higher small points first
            });

            // Assign ranks
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
                currentRank++;
            }

            // Update the "Platz" column in the results table
            players.forEach(player => {
                const row = document.querySelector(`.result-row[data-player-id="${player.playerId}"]`);
                row.querySelector('[name^="platz_"]').value = player.rank;
            });
        }
    </script>

</body>

</html>