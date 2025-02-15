// Skript, dass bei Auswahl von "CSV-Import" in add_teachers_manual.php eingebunden wird.

(function () {
  const fileInput = document.getElementById("fileToUpload");
  const submitButton = document.getElementById("submitButton");
  const fileName = document.getElementById("fileName");
  const spinner = document.getElementById("spinner");
  const resultMessage = document.getElementById("resultMessage");

  let teachers = [];

  fileInput.addEventListener("change", function () {
    // Importieren-Button aktivieren, wenn Datei ausgewählt.
    submitButton.disabled = fileInput.files.length == 0 || teachers.length == 0;
    fileName.textContent = fileInput.files[0].name; // Dateiname anzeigen
  });

  function previewTeachers() {
    resultMessage.innerHTML = "";
    teachers = []; // Leeren, falls noch alte Daten vorhanden sind.
    const file = fileInput.files[0];

    if (!file) {
      return;
    }

    if (!file.name.endsWith(".csv")) {
      alert("Bitte wählen Sie eine CSV-Datei aus.");
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      const fileContent = e.target.result; // e.target = FileReader, der das onload-Event ausgelöst hat.
      teachers = parseCSV(fileContent);
      displayPreview(teachers);
    };

    reader.readAsText(file); // onload-Event wird ausgelöst, wenn das Lesen abgeschlossen ist.
  }

  function parseCSV(data) {
    const lines = data.split("\n").slice(1); // Kopfzeile entfernen.

    for (const line of lines) {
      const [lastName, firstName, shortCode] = line
        .split(";")
        .map((item) => item.trim());
      if (lastName && firstName && shortCode) {
        teachers.push({
          lastName,
          firstName,
          shortCode,
        });
      }
    }

    submitButton.disabled = teachers.length == 0;
    return teachers;
  }

  function displayPreview(teachers) {
    const previewDiv = document.getElementById("teacherPreview");
    previewDiv.innerHTML = "";

    if (teachers.length === 0) {
      previewDiv.innerHTML = `
              <p style="text-align: center; margin: 0;">
                  Keine Lehrer gefunden.<br>
                  <strong>Format der CSV-Datei:</strong><br>
                  1. Erste Zeile: Kopfzeile mit den Spaltennamen.<br>
                  2. Reihenfolge der Spalten: <em>Nachname;Vorname;Kürzel</em>.<br>
                  <strong>Beispiel:</strong> Mustermann;Max;MM<br>
                  3. Trennzeichen: Semikolon ( ; )
              </p>
          `;
      return;
    }

    // Tabelle erstellen und Lehrer-Daten anzeigen.
    const table = document.createElement("table");
    const header = document.createElement("tr");
    header.innerHTML = "<th>Vorname</th><th>Nachname</th><th>Kürzel</th>";
    table.appendChild(header);

    // Alle Zeilen bis auf Kopfzeile durchgehen.
    teachers.forEach((teacher) => {
      const row = document.createElement("tr");
      row.innerHTML = `<td>${teacher.firstName}</td><td>${teacher.lastName}</td><td>${teacher.shortCode}</td>`;
      table.appendChild(row);
    });

    previewDiv.appendChild(table);
  }

  // Postet die Lehrer-Daten, sodass PHP sie an die API weiterleiten kann.
  function uploadTeachers() {
    const teachersJSON = JSON.stringify(teachers);

    resultMessage.innerHTML = "";
    spinner.style.display = "inline-block";

    fetch("import_teachers_csv.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: teachersJSON,
    })
      .then((response) => response.text())
      .then((data) => {
        spinner.style.display = "none";
        const isSuccess = data.includes("erfolgreich");
        resultMessage.innerHTML = `<p class="resultMessage ${
          isSuccess ? "success" : "error"
        }">${data}</p>`;
      })
      .catch((error) => {
        spinner.style.display = "none";
        console.error("Fehler:", error);
        resultMessage.innerHTML = `<p class="resultMessage error">Fehler beim Importieren der Lehrer.</p>`;
      });
  }

  // Funktionen dem globalen Scope zuweisen
  window.previewTeachers = previewTeachers;
  window.uploadTeachers = uploadTeachers;
  window.parseCSV = parseCSV;
  window.displayPreview = displayPreview;
})();
