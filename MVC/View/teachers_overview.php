<?php
require '../../vendor/autoload.php';
session_start();

if (!isset($_COOKIE['ChampionCheckerCookie'])) {
    header("Location: login.php");
    exit();
}


use MVC\Controller\TeacherController;
use MVC\Controller\ClassController;
use MVC\Model\Teacher;
use MVC\Model\ClassModel;

$teacherController = TeacherController::getInstance();
$classController = ClassController::getInstance();

function loadAllTeachers($cacheDuration = 300): array
{
    global $teacherController;

    // Gecachte Daten für die Dauer des Cache zurückgeben.
    if (isset($_SESSION['teachers']) && isset($_SESSION['overview_teachers_timestamp'])) {
        if ((time() - $_SESSION['overview_teachers_timestamp']) < $cacheDuration) {
            return $_SESSION['teachers'];
        }
    }

    // Daten aus der DB laden und im Cache speichern
    $teachers = $teacherController->getAll();

    // Ergebnisse und Zeitstempel in der Session speichern
    $_SESSION['teachers'] = $teachers;
    $_SESSION['overview_teachers_timestamp'] = time();

    return $teachers;
}

function loadAllClassNames($cacheDuration = 300): array
{
    global $classController;
    $classNames = [];

    // Gecachte Daten für die Dauer des Cache zurückgeben. Gleichen Stempel wie bei Lehrern nehmen, damit Daten gemeinsam aktualisiert werden.
    if (isset($_SESSION['classes']) && isset($_SESSION['overview_teachers_timestamp'])) {
        if ((time() - $_SESSION['overview_teachers_timestamp']) < $cacheDuration) {
            foreach ($_SESSION['classes'] as $class) {
                $classNames[] = $class->getName();
            }
            return $classNames;
        }
    }

    $classes = $classController->getAll();
    $_SESSION['classes'] = $classes;

    foreach ($_SESSION['classes'] as $class) {
        $classNames[] = $class->getName();
    }

    $_SESSION['overview_teachers_timestamp'] = time();

    return $classNames;
}


function printTeachers($teachers)
{
    echo "<table id='teacherTable' class='table-style'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th onclick='filterTable(0)'>Nachname</th>";
    echo "<th onclick='filterTable(1)'>Vorname</th>";
    echo "<th onclick='filterTable(2)'>Kürzel</th>";
    echo "<th onclick='filterTable(3)'><abbr title='Ein Lehrer kann maximal zwei Klassen zugeordnet sein, jede Klasse kann maximal zwei Lehrer haben.'>Klassen</abbr></th>";
    echo "<th onclick='filterTable(4)'>Sonstige Informationen</th>";
    echo "<th onclick='filterTable(5)'>Turnier-Teilnahme</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($teachers as $teacher) {
        echo "<tr>";
        echo "<td data-column='Nachname'><div class='td-content'>" . htmlspecialchars($teacher->getLastName()) . "</div></td>";
        echo "<td data-column='Vorname'><div class='td-content'>" . htmlspecialchars($teacher->getFirstName()) . "</div></td>";
        echo "<td data-column='Kürzel'><div class='td-content'>" . htmlspecialchars($teacher->getShortCode()) . "</div></td>";

        $classes = $teacher->getClasses() ?? [];
        $classNames = [];

        if (is_array($classes)) {
            $classes = array_map(function ($class) {
                return $class instanceof ClassModel ? $class : ClassModel::mapToModel($class);
            }, $classes);
        }


        foreach ($classes as $class) {
            $className = $class->getName();
            if ($className) {
                $escapedName = htmlspecialchars($className);
                $classNames[] = "<span class='class'>{$escapedName}</span>";
            }
        }

        echo "<td data-column='Klassen'><div class='td-content'>" .
            (!empty($classNames) ? implode(' ', $classNames) : '-') .
            "</div></td>";
        echo "<td data-column='Sonstiges'><div class='td-content'>" . (empty($teacher->getAdditionalInfo()) ? '-' : htmlspecialchars($teacher->getAdditionalInfo())) . "</div></td>";
        echo "<td data-column='Teilnahme'><div class='td-content'><span class='status-circle " . ($teacher->getIsParticipating() ? "green" : "red") . "'></span></div></td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    if (isset($_SESSION['overview_teachers_timestamp'])) {
        echo "<p style='text-align: center;'>Zuletzt aktualisiert: " . date('d.m.Y H:i:s', $_SESSION['overview_teachers_timestamp']) . "<br></p>";
    }
}

$response = [
    'success' => true,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $putData = file_get_contents('php://input');

    if (empty($putData)) {
        $response['success'] = false;
        $response['message'] = 'Leere Anfrage erhalten.';
        echo json_encode($response);
        exit;
    }

    $teachersData = json_decode($putData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['success'] = false;
        $response['message'] = 'Ungültiges JSON erhalten.';
        echo json_encode($response);
        exit;
    }


    $changedTeachers = [];

    foreach ($teachersData as $data) {
        $classes = $data['classes'];

        $classObjects = [];
        foreach ($classes as $class) {
            $classObjects[] = $classController->getByName($class);
        }


        $additionalInfo = trim($data['additionalInfo']) === '-' ? null : trim($data['additionalInfo']);
        $shortCode = trim($data['shortCode']);

        $teacher = new Teacher(
            id: $teacherController->getIdFromShortCode($shortCode),
            firstName: trim($data['firstName']),
            lastName: trim($data['lastName']),
            shortCode: $shortCode,
            isParticipating: isset($data['isParticipating']) ? (bool)$data['isParticipating'] : false,
            additionalInfo: $additionalInfo,
            classes: $classObjects,
        );
        $changedTeachers[] = $teacher;
    }

    $putSuccess = true;

    foreach ($changedTeachers as $teacher) {
        $updateResult = $teacherController->update($teacher);
        $putSuccess &= $updateResult['success'] === true;
        $updateResults[] = $updateResult;
    }

    // Ergebnis an die Frontend-Seite zurückgeben
    echo json_encode([
        'success' => $putSuccess,
        'results' => $updateResults ?? []
    ]);
    exit;

    // TOOO: Update-Ergebnis visualisieren
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');

    if (!isset($_GET['shortCode'])) {
        echo json_encode(['success' => false, 'message' => 'Das Lehrerkürzel wurde nicht übermittelt.']);
        exit;
    }

    $shortCode = $_GET['shortCode'];
    $teacherId = $teacherController->getIdFromShortCode($shortCode);
    $deleteResult = $teacherController->delete($teacherId);

    if ($deleteResult['success'] === true) {
        echo json_encode(['success' => true, 'message' => 'Der Lehrer ' . addslashes($shortCode) . ' wurde erfolgreich entfernt.']);
    } else {
        $errorMessage = addslashes(htmlspecialchars($deleteResult["error"], ENT_NOQUOTES, 'UTF-8'));
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
    exit;
}

$classes = loadAllClassNames();
$teachers = loadAllTeachers();

// Lehrer nach Nachnamen sortieren
usort($teachers, function ($teacherA, $teacherB) {
    return strcmp($teacherA->getLastName(), $teacherB->getLastName());
});

include 'nav.php';
?>


<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet/less" type="text/css" href="../../styles/styles.less" />
    <link rel="stylesheet" type="text/css" href="../../styles/base.css" />
    <link rel="stylesheet" type="text/css" href="../../styles/teacher_overview.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/less"></script>
    <title>Lehrerverwaltung</title>
</head>

<body>
    <header>
        <h1>Lehrerverwaltung</h1>
    </header>

    <div class="button-container">
        <button class="circle-button add-button" onclick="window.location.href='add_teachers_overview.php?mode=manual'">
            <i class="fas fa-plus"></i>
        </button>
        <button class="circle-button edit-button">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button class="circle-button cancel-button hidden">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <section>
        <?php printTeachers($teachers); ?>
    </section>

    <script>
        let isEditing = false;
        let sortDirections = {};
        let storedValues = [];
        let changedTeachers = [];

        const editButton = document.querySelector('.edit-button i');
        const cancelButton = document.querySelector(".cancel-button");
        const table = document.getElementById("teacherTable");
        const tbody = table.getElementsByTagName("tbody")[0];
        const headerRow = table.getElementsByTagName("tr")[0];
        const rows = Array.from(tbody.getElementsByTagName("tr"));

        document.querySelector('.edit-button').addEventListener('click', function() {
            toggleEditState();
            cancelButton.classList.toggle("hidden");
        });

        document.querySelector('.cancel-button').addEventListener('click', function() {
            const confirmation = confirm('Alle Änderungen gehen verloren. Bearbeitung abbrechen?');
            if (confirmation) {
                toggleEditState(true);
                this.classList.toggle("hidden");
            }
        });


        function toggleEditState(wasCanceled = false) {
            isEditing = !isEditing;
            toggleEditButtonIcon();

            if (isEditing) {
                enterEditState();
            } else {
                exitEditState(wasCanceled);
                if (!wasCanceled) {
                    saveChangedTeachers(changedTeachers);
                    changedTeachers = [];
                }
            }
        }


        function toggleEditButtonIcon() {
            editButton.classList.toggle('fa-pencil-alt');
            editButton.classList.toggle('fa-save');
        }


        // Zeileninhalt innerhalb von Input-Elementen anzeigen.
        function enterEditState() {
            let deleteHeader = document.createElement("th");
            headerRow.appendChild(deleteHeader);

            const classNames = <?php echo json_encode(loadAllClassNames()); ?>;

            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");

                let deleteColumn = document.createElement("td");
                deleteColumn.innerHTML = `
                <button class="circle-button delete-button">
                    <i class="fas fa-trash"></i>
                </button>`;
                row.appendChild(deleteColumn);

                let lastName = cells[0].querySelector('.td-content').innerText;
                let firstName = cells[1].querySelector('.td-content').innerText;
                let shortCode = cells[2].querySelector('.td-content').innerText;
                let classes = cells[3].querySelector('.td-content').innerText.split(" ").map(c => c.trim());
                let additionalInfo = cells[4].querySelector('.td-content').innerText;
                let isParticipating = cells[5].querySelector('.td-content').querySelector('.status-circle').classList.contains('green');

                // Werte zwischenspeichern, falls die Bearbeitung abgebrochen wird.
                storedValues[row.rowIndex] = [lastName, firstName, shortCode, classes, additionalInfo, isParticipating];

                cells[0].innerHTML = `<input type="text" value="${lastName}">`;
                cells[1].innerHTML = `<input type="text" value="${firstName}">`;
                cells[2].innerHTML = `<input type="text" value="${shortCode}" class='readonly-input' readonly>`;

                cells[3].innerHTML = classes.map(className => {
                    return className !== "-" ? `<span class="class" title="Klasse entfernen">
                        ${className} <i onclick="this.parentElement.remove()" class="fas fa-times"></i>
                    </span>` : "-";
                }).join(' ');

                if (isParticipating) {
                    addClassSelect(cells, classNames, classes);
                }

                cells[4].innerHTML = `<input type="text" value="${additionalInfo}">`;
                cells[5].innerHTML = `<input type="checkbox" ${isParticipating ? 'checked' : ''}>`;

                let deleteButton = row.querySelector('.delete-button');
                deleteButton.addEventListener('click', () => {
                    const confirmation = confirm('Sind Sie sicher, dass Sie diesen Lehrer löschen möchten?');
                    if (confirmation) {
                        deleteTeacher(shortCode, row.rowIndex);
                    }
                });
            });
        }

        // Input-Elemente durch Text ersetzen.
        function exitEditState(wasCanceled = false) {
            rows.forEach(row => {
                let cells = row.getElementsByTagName("td");
                let storedRow = storedValues[row.rowIndex];

                let classSelect = cells[3].querySelector('select');
                if (classSelect) {
                    classSelect.remove();
                }

                let lastName = wasCanceled && storedRow ? storedRow[0] : cells[0].querySelector('input').value;
                let firstName = wasCanceled && storedRow ? storedRow[1] : cells[1].querySelector('input').value;
                let shortCode = wasCanceled && storedRow ? storedRow[2] : cells[2].querySelector('input').value;

                let classes = wasCanceled && storedRow ?
                    storedRow[3] :
                    Array.from(cells[3].querySelectorAll('.class')).map(c => c.innerText.trim());

                let additionalInfo = wasCanceled && storedRow ? storedRow[4] : cells[4].querySelector('input').value;
                let isParticipating = wasCanceled && storedRow ? storedRow[5] : cells[5].querySelector('input').checked;

                // Prüfen, ob Zeile geändert wurde
                if (checkIfRowWasModified(row, storedRow)) {
                    let changedTeacher = {
                        lastName: lastName,
                        firstName: firstName,
                        shortCode: shortCode,
                        classes: classes,
                        additionalInfo: additionalInfo,
                        isParticipating: isParticipating
                    };
                    changedTeachers.push(changedTeacher);
                }

                lastName = lastName || "-";
                firstName = firstName || "-";
                shortCode = shortCode || "-";
                classes = classes || "-";
                additionalInfo = additionalInfo || "-";

                let classElements;
                if (classes.length === 1 && classes[0] === "-") {
                    classElements = "-";
                } else {
                    classElements = classes
                        .map(className => `<span class='class'>${className.trim()}</span>`)
                        .join("");
                }

                cells[0].innerHTML = `<div class='td-content'>${lastName}</div>`;
                cells[1].innerHTML = `<div class='td-content'>${firstName}</div>`;
                cells[2].innerHTML = `<div class='td-content'>${shortCode}</div>`;
                cells[3].innerHTML = `<div class='td-content'>${classElements}</div>`;
                cells[4].innerHTML = `<div class='td-content` + (additionalInfo === '-' ? ' empty' : '') + `'>${additionalInfo}</div>`;
                cells[5].innerHTML = `<div class='td-content'><span class='status-circle ${isParticipating ? 'green' : 'red'}'></span></div>`;
            });

            storedValues = [];

            // Löschen-Spalte & -Knöpfe entfernen.
            headerRow.querySelector("th:last-child").remove();
            document.querySelectorAll(".delete-button").forEach(b => b.parentElement.remove());
        }


        function saveChangedTeachers(changedTeachers) {
            if (changedTeachers.length === 0) {
                return;
            }

            const teacherJSON = JSON.stringify(changedTeachers);

            fetch('teachers_overview.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: teacherJSON
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Alle Lehrer wurden erfolgreich aktualisiert.');
                    } else {
                        alert('Einige Lehrer konnten nicht aktualisiert werden.');
                        console.log(data.results);
                    }
                }).catch(error => console.error('Error:', error));
        }


        function deleteTeacher(shortCode, rowIndex) {
            fetch(`teachers_overview.php?shortCode=${shortCode}`, {
                    method: 'DELETE',
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        const row = table.rows[rowIndex];
                        if (row) {
                            row.remove();
                            storedValues.splice(rowIndex, 1);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }


        // Prüft, ob eine Zeile im Edit-Modus bearbeitet wurde
        function checkIfRowWasModified(row, storedRow) {
            if (!storedRow) {
                return false;
            }

            let cells = row.getElementsByTagName("td");
            for (let i = 0; i < cells.length; i++) {
                const inputElement = cells[i].querySelector('input, textarea');
                const storedValue = storedRow[i];
                let inputValue;

                if (inputElement) {
                    inputValue = inputElement.type === 'checkbox' ?
                        inputElement.checked :
                        inputElement.value;
                }

                // Klassen
                if (i === 3) {
                    let classElements = Array.from(cells[3].querySelectorAll('.class')).map(c => c.innerText);
                    inputValue = classElements.length > 0 ? classElements : "-";
                }

                let trimmedInputValue = Array.isArray(inputValue) ?
                    inputValue.map(c => c.trim()).join(',') :
                    inputValue;

                let trimmedStoredValue = Array.isArray(storedValue) ?
                    storedValue.map(c => c.trim()).join(',') :
                    storedValue;

                if (trimmedInputValue !== trimmedStoredValue) {
                    return true;
                }
            }

            return false;
        }


        function filterTable(columnIndex) {
            if (isEditing) {
                return;
            }

            // Richtung togglen
            sortDirections[columnIndex] = sortDirections[columnIndex] === "asc" ? "desc" : "asc";
            let sortOrder = sortDirections[columnIndex];

            rows.sort((rowA, rowB) => {
                let cellA = rowA.getElementsByTagName("td")[columnIndex].innerText.trim();
                let cellB = rowB.getElementsByTagName("td")[columnIndex].innerText.trim();

                // Turnier-Teilnahme
                if (columnIndex === 5) {
                    let circleA = rowA.querySelector('.status-circle');
                    let circleB = rowB.querySelector('.status-circle');
                    let isGreenA = circleA.classList.contains('green') ? 1 : 0;
                    let isGreenB = circleB.classList.contains('green') ? 1 : 0;
                    return sortOrder === "asc" ? isGreenA - isGreenB : isGreenB - isGreenA;
                }

                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
        }


        // Select-Menü für teilnehmende Lehrer
        function addClassSelect(cells, classNames, currentClasses) {
            let select = document.createElement("select");
            let defaultOption = document.createElement("option");
            defaultOption.textContent = "Klassen auswählen:";
            defaultOption.value = "";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            select.appendChild(defaultOption);

            classNames.forEach(className => {
                let option = document.createElement("option");
                option.value = className;
                option.textContent = className;
                select.appendChild(option);
            });

            cells[3].appendChild(select);
        }
    </script>

</body>

</html>