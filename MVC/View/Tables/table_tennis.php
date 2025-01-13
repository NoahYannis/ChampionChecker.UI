<?php
// table_tischtennis.php

// Number of rows for the table
$numRows = 5;

// Start of the table for Tischtennis
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width initial-scale=1.0">
,
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

// Generate dynamic matchups where each player plays against every other player exactly once
for ($i = 1; $i <= $numRows; $i++) {
    for ($j = $i + 1; $j <= $numRows; $j++) {
        echo '
            <tr>
                <td>' . $i . '. vs ' . $j . '.</td>
                <td>:</td>
            </tr>';
    }
}

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
for ($i = 1; $i <= $numRows; $i++) {
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
