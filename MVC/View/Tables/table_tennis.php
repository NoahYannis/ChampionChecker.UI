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

// Match order as per the desired sequence
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

// Render the matches table
echo '<table>';
$i = 0;
foreach ($matches as $match) {
    // Safely encode player names
    $player1 = htmlspecialchars($match['player1']);
    $player2 = htmlspecialchars($match['player2']);
    
    // Construct the HTML for each match
    echo '
        <tr>
            <td style="text-align: right;">' . $player1 . '</td>
            <td><input type="text" name="player_' . $player1 . '" required style="text-align: left; width: 100%;" oninput="updateText(this, ' . $player1 . ')" /></td>
            <td style="text-align: right;">' . $player2 . '</td>
            <td><input type="text" name="player_' . $player2 . '" required style="text-align: left; width: 100%;" oninput="updateText(this, ' . $player2 . ')" /></td>
            <td style="width: 120px;">
                <div style="display: flex; gap: 10px; align-items: center; justify-content: center;">
                    <input type="number" name="pointsplayer_' . $player1 . '_round' . ($i + 1) . '" style="width: 50px; text-align: right;" maxlength="2" oninput="validateNumber(this)" />
                    :
                    <input type="number" name="pointsplayer_' . $player2 . '_round' . ($i + 1) . '" style="width: 50px; text-align: right;" maxlength="2" oninput="validateNumber(this)" />
                </div>
            </td>
       </tr>';
    $i++;
}
echo '</table>
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
                <td colspan="2"><input type="text" name="player_' . $i . '" required style="text-align: left;" oninput="updateText(this, ' . $i . ')" /></td>
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

<script>
// JavaScript function to update all textboxes with the same name
function updateText(input, player) {
    // Get the value from the changed input box
    var value = input.value;
    
    // Find all inputs with the same player identifier
    var allInputs = document.querySelectorAll('[name="player_' + player + '"]');
    
    // Update all the inputs with the same name
    allInputs.forEach(function(inputElement) {
        inputElement.value = value;
    });
}

// JavaScript function to restrict input to numbers only
function validateNumber(input) {
    // Remove any non-numeric characters from the input value
    input.value = input.value.replace(/[^0-9]/g, '');

    // Ensure the value does not exceed two digits (max 99)
    if (input.value.length > 2) {
        input.value = input.value.slice(0, 2); // Truncate to 2 digits
    }
}
</script>
