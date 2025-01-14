<?php
// table_tischtennis.php


// Number of rows for the table
$numPlayers = 5;

// Start of the table for Tischtennis
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width initial-scale=1.0">

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 18px;
            text-align: left;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
        }
        input[type="text"] {
            width: 100%;
            box-sizing: border-box;
        }
        input[type="submit"] {
            margin-top: 10px;
            padding: 10px 15px;
            font-size: 16px;
            background-color: #f8f9fa;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #e2e6ea;
        }
        caption {
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<!-- Region Tischtennis weiblich -->
    <table class="turnier-table">
        <caption>Tischtennis Turnier (Weiblich) bis 11 P.; 2 P. Abstand; Jeder gegen Jeden</caption>
        <tbody>
        ';

$matches = [];
$players = range(1, $numPlayers); // Generate a list of players
$playedPairs = [];
$playersInLastRound = [];
// this generates the matchups
while (count($matches) < ($numPlayers * ($numPlayers - 1)) / 2) { // Total possible unique matches

    for ($round = 0; $round < $numPlayers - 1; $round++) {
        for ($i = 0; $i < $numPlayers / 2; $i++) {
            $player1 = $players[$i];
            $player2 = $players[$numPlayers - 1 - $i];

            // Check if this pair has already played
            if (!in_array([$player1, $player2], $playedPairs) && !in_array([$player2, $player1], $playedPairs)) {
                $matches[] = [
                    'player1' => $player1 ,
                    'player2' => $player2 ,
                ];

                // Add this pair to the played pairs list
                $playedPairs[] = [$player1, $player2];

                // Break to ensure only one match is scheduled at a time
                break 2;
            }
        }
    }

    // Rotate players to ensure fairness
    $firstPlayer = array_shift($players);
    $players[] = $firstPlayer;
}


// Render the matches table
echo '<table>';
$i = 0;
foreach ($matches as $match) {
    echo '
        <tr>
            <td>' . $match['player1'] . '</td>
            <td><input type="text" name="player_' .  $match['player1'] .  '" /></td>
            <td>' . $match['player2'] . '</td>
            <td><input type="text" name="player_' .  $match['player2'] .  '" /></td>
            <td>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="pointsplayer_<?php echo '. $match['player1'] . '; ?>_round<?php echo $i; ?>" />
                    :
                    <input type="text" name="pointsplayer_<?php echo ' . $match['player2'] . '; ?>_round<?php echo $i; ?>" />
                </div>
            </td>
       </tr>';
        $i++;
}
echo '</table>';

echo '
        </tbody>
    </table>
    
<!-- Region Endergebnis -->
    <table>
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
        <tbody>';

// Dynamically generate table rows
for ($i = 1; $i <= $numPlayers; $i++) {
    echo '
            <tr>
                <td>' . $i . '.</td>
                <td colspan="2"><input type="text" name="name_' . $i . '" /></td>
                <td><input type="text" name="punkte_' . $i . '" /></td>
                <td><input type="text" name="kl_punkte_' . $i . '" /></td>
                <td><input type="text" name="platz_' . $i . '" /></td>
                <td><input type="text" name="extra_punkte_' . $i . '" /></td>
            </tr>';
}

echo '
        </tbody>
    </table>
    <form method="post" action="">
		<button onclick="submitForm()" type="submit" name="submit" value="Abschicken">Abschicken</button>
    </form>
</body>
</html>';
?>
