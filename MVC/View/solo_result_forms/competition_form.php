<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition Form</title>
    <link rel="stylesheet" type="text/css" href="/ChampionChecker.UI/styles/base.css" />
</head>

<body>
    <h2>Competition Form</h2>
    <form id="competition-form">
        <div class="form-group">
            <label for="competition-name">Wettbewerbsname:</label>
            <input type="text" id="competition-name" name="competition-name" required>
        </div>
        <div class="form-group">
            <label for="participant-name">Teilnehmername:</label>
            <input type="text" id="participant-name" name="participant-name" required>
        </div>
        <div class="form-group">
            <label for="score">Punkte:</label>
            <input type="number" id="score" name="score" required>
        </div>
        <button type="submit">Ergebnisse einreichen</button>
    </form>
</body>

</html>