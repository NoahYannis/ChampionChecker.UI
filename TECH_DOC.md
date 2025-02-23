# ChampionChecker Frontend - Technische Dokumentation

## Verwendete Technologien
- **HTML & CSS**: Grundgerüst und Design der Seite
- **JavaScript**: Dynamische Interaktivität
- **PHP**: Datenvalidierung und Kommunikation mit der .NET-API

## Architektur
Das Projekt folgt dem **MVC-Architekturprinzip**, wobei die Kommunikation mit der API über die jeweiligen Controller stattfindet.
- Die **Entitäts-Controller** implementieren das generische `IController`-Interface, welches grundlegende Methoden wie `getAll()` oder `create()` bereitstellt.
- Der **API-Endpunkt** ist in `config.php` konfiguriert. Dort sind die Links zur **in Azure gehosteten WebAPI** sowie der **lokale API-Port** für die Entwicklungsumgebung hinterlegt.

## CSS-Struktur
- Grundlegende, wiederverwendbare Klassen, einschließlich **Light- und Darkmode**, sind in `base.css` definiert.
- Views können diese Klassen bei Bedarf **erweitern oder überschreiben**.

## Autorisierung & Authentifizierung
- Das `UserModel` enthält alle Eigenschaften des Nutzers.
- Beim **Registrieren** wird der `UserController` aufgerufen.
- Nach einem erfolgreichen Login übermittelt die API ein **Cookie**, das:
  - Durch den `UserController` gespeichert wird
  - Bei jedem Seitenaufruf validiert wird
  - Verschlüsselte Informationen über den Nutzer und seine **Rolle** enthält
- Die Nutzerrolle kann über den UserController::getRole() abgefragt werden.

## Caching & Performance
- Von der API abgefragte Daten werden im **Session-Cache** gespeichert, um die **Ladezeiten zu optimieren**.
- Jeder Cache-Eintrag wird mit einem **Zeitstempel** versehen.
- Bei Ablauf des Zeitstempels oder bei **Hinzufügen oder Löschen von Daten** wird der Cache aktualisiert.

## Helferklassen
- Der `Helpers`-Ordner enthält **Hilfsskripte**, die **kleine, spezifische Funktionen** erfüllen.
- Sie sind **wiederverwendbar** und helfen dabei, die aufrufende View **kompakter** zu gestalten.

## Autoloading
- Das Projekt verwendet **Autoloader**, um genutzte Klassen automatisch einzubinden.

## Docker-Unterstützung
- Das Projekt enthält ein **Dockerfile** und eine **Docker-Compose-Konfiguration**.
- Aktuelle Docker-Images sind im **Packages-Tab** hinterlegt.

## Light-/Darkmode
- Beim ersten Laden wird in `nav.php` per **JavaScript** geprüft, ob der Nutzer in den **Browser-Einstellungen** den Light- oder Darkmode bevorzugt.
- Abhängig davon wird der **entsprechende Modus gesetzt und im LocalStorage gespeichert**.
- Änderungen werden sofort übernommen, indem die **Darkmode-Klasse** am `body`-Element gesetzt und im `LocalStorage` gespeichert wird.
