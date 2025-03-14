## ChampionChecker - Digitalisierung des Sportfests der BBSOVG Magdeburg

### Überblick

**[ChampionChecker](https://championchecker-bbsovg.azurewebsites.net/ChampionChecker.UI/MVC/View/home.php)** ist eine digitale Lösung zur Organisation und Auswertung des jährlichen Sportfests der BBSOVG Magdeburg. Die Anwendung ersetzt den bisherigen papierbasierten Prozess durch eine moderne Webanwendung, die eine effizientere Organisation, Echtzeit-Auswertung und bessere Zusammenarbeit zwischen Lehrkräften und Wettkampfleitern ermöglicht.

---

### 🎯 Motivation

Bisher erfolgte die gesamte Organisation des Sportfests auf Papier, was zahlreiche Probleme verursachte:

❌ **Langwierige Organisation**
- Verwaltung von Schülern, Lehrern und Stationen auf dutzenden Zetteln.
- Schwieriger Überblick über Anmeldungen und Zeitkonflikte.
- Änderungen mussten umständlich kommuniziert werden und dauerten lange.

❌ **Kein Live-Tracking**
- Ergebnisse waren erst nach Ende des Wettbewerbs verfügbar.
- Während des Sportfests gab es keine Echtzeit-Einsicht in den aktuellen Stand.

❌ **Fehleranfällige Auswertung**
- Punktzahlen wurden manuell berechnet, was fehleranfällig und zeitintensiv war.
- Fehlerhaft eingetragene Ergebnisse waren nur aufwendig änderbar.

---

### 🚀 Vorteile der digitalen Lösung

Mit **ChampionChecker** wird das Sportfest einfacher, schneller und transparenter:

✅ **Live-Tracking & Auswertung**
- Zwischenstände sind in Echtzeit einsehbar ➜ Mehr Spannung & Motivation für alle Teilnehmer.
- Automatische Punkteberechnung basierend auf den eingetragenen Ergebnissen.
- Ergebnisse können mühelos korrigiert werden.

✅ **Effiziente Anmeldung & Verwaltung**
- Schüler- und Lehrerdaten lassen sich per CSV-Import schnell hinzufügen.
- Einfache Übersicht über alle Schüler, Stationen und Lehrer für autorisierte Nutzer
- Einfache Kollaboration zwischen Lehrkräften und Wettkampfleitung.

✅ **Bessere Planung & Steuerung**
- Lehrkräfte, Schüler und Stationen können effizienter koordiniert werden.
- Automatische Erkennung von Zeitkonflikten bei Schülerstationen für eine bessere Planung.
- Flexible Bearbeitung der Stationendaten jederzeit möglich.

---

## 🚀 Schnellstart

### 🔹 Zugriff für Gäste

Ohne Anmeldung können Sie:
- Den aktuellen Stand der Auswertung verfolgen
- Anstehende Stationen einsehen
- Ergebnisse der einzelnen Klassen abrufen

### 🔹 Anmeldung & Benutzerrollen

Um Daten zu ändern oder geschützte Informationen zu sehen, ist eine Anmeldung erforderlich. Es gibt folgende Rollen:
- Gast 🏁 → Kann Klassen-Ergebnisse und Auswertungsstand einsehen.
- Schüler 🎓 → Eingeloggter Nutzer
- Lehrkraft 🏫 → Kann zusätzlich zur allgemeinen Übersicht:
  - Alle Klassen- und Schüler-Ergebnisse einsehen
  - Neue Klassen- und Schüler-Ergebnisse eintragen
  - Bestehende Ergebnisse korrigieren
  - Die Schülerübersicht aufrufen und neue Schüler anlegen
  - Die Stationenübersicht einsehen und dort Teilnehmer setzen
- Admin ⚙️ → Hat vollen Zugriff und kann alle Daten inklusive Lehrkräfte verwalten.

![Auswertung](resources/documentation-images/profile.png)


---

## Anwendungsaufbau

### Auswertung
- Anzeige des Auswertungsfortschritts und der aktuellen Platzierungen 
- Anzeige der anstehenden Wettbewerbe
  
  ![Auswertung](resources/documentation-images/auswertung.png)

---

### Ergebnisse ansehen
- Auf der Ergebnisseite werden alle Klassenergebnisse angezeigt. Lehrkräfte und Admins können zusätzlich Schüler-Ergebnisse einsehen
- Lehrkräfte und Admins können die Klassen- und Schülerergebnisse bearbeiten durch Klick auf den blauen Bearbeitungsbutton.
  
  ![Ergebnisse ansehen](resources/documentation-images/ergebnis-ansicht.png)

---

### Klassenergebnisse hinzufügen
- Nach Auswahl der Station werden alle teilnehmenden Klassen der Station angezeigt. Stationen ohne Klassen sind deaktiviert.
- Um ein Ergebnis hinzuzufügen wählen Sie die jeweilige Klasse aus und geben Sie die erreichte Punktzahl ein.
  
  ![Klassenergebnisse hinzufügen](resources/documentation-images/klassenpunkte-eintragen.png)

---

### Einzelergebnisse hinzufügen
- Bei Auswahl einer Station werden Stations-Infos angezeigt
  
  ![Einzelergebnisse hinzufügen](resources/documentation-images/einzelergebnis-info.png)

- Die Anzahl der Versuche pro Schüler und die Mess-Einheit ist flexibel konfigurierbar (Punkte, Meter, Zeit)
- Bei Eingabe eines Werts wird der beste Versuch jedes Schülers automatisch ermittelt und hervorgehoben. Die Auswertung wird automatisch berechnet und bei Änderungen aktualisiert.
- Bei Klick auf den "Station abschließen"-Button wird ein Hinweis angezeigt, dass die Vollständigkeit der Ergebnisse bestätigt werden soll.
- Erfolgreich ausgewertete Einzelstationen werden automatisch als "Beendet" gekennzeichnet, wodurch der Auswertungsfortschritt direkt aktualisiert wird. Ausgewertete Einzelergebnisse fließen mit in die Auswertung   mit ein
  
  ![Einzelergebnisse Auswertung](resources/documentation-images/einzelergebnis-auswertung.png)

---

### Stationenverwaltung
- Hier werden alle Informationen über die Stationen angezeigt
- Bei Teamstationen werden die Klassennamen angezeigt, bei Einzelstationen die Schülernamen
- Admins haben volle Bearbeitungsrechte, Lehrkräfte können Teilnehmer, Status und sonstige Notizen bearbeiten
- Um eine Station zu bearbeiten klicken Sie auf den blauen Bearbeitungsbutton
  
  ![Stationenverwaltung](resources/documentation-images/stationsverwaltung.png)
  ![Stationenverwaltung Edit](resources/documentation-images/stationsverwaltung-edit.png)

---

### Schülerübersicht
- Auflistung aller Schüler
- Hinweis für Schüler mit zu wenigen Stationen (weißes "!")
- Hinweis auf Zeitkonflikte bei Schüler-Stationen (rotes "!")
- Information über Anmeldungsstand der Schüler (Zwischenstand oder offiziell)
- Neue Schüler können über das "+" hinzugefügt werden (CSV-Import)
  
  ![Schülerübersicht](resources/documentation-images/schülerübersicht.png)

---

### Schüler CSV-Import
- Schüler lassen sich bequem über eine CSV-Datei ins System bringen
- Anforderungen an das CSV-Format werden im Import-Dialog angezeigt
- Vorschau über zu importierende Schüler, Import bei Klick auf den Button

  ![Schüler CSV-Import](resources/documentation-images/csv-schüler.png)

---

### Lehrerverwaltung
- Zugriff nur für Administratoren
- Überblick über alle Lehrkräfte und deren Teilnahme am Sportfest
  
  ![Lehrerverwaltung](resources/documentation-images/lehrerverwaltung.png)

- Neue Lehrer hinzufügen: Auswahl zwischen manueller Erstellung und CSV-Import analog zu Schüler-CSV-Import
- Bei aktiver Teilnahme können der Lehrkraft direkt bis zu zwei Klassen zugewiesen werden
  
  ![Lehrer Hinzufügen](resources/documentation-images/lehrer-hinzufügen.png)

---

### Ansichtsmodus
- Die Anwendung bietet einen Hell- und Dunkelmodus an, der flexibel umgeschaltet werden kann
  
  ![Hellmodus](resources/documentation-images/lightmode.png)
  ![Dunkelmodus](resources/documentation-images/darkmode.png)


---

