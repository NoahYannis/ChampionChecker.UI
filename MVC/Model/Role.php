<?php

namespace MVC\Model;

enum Role: int
{
    case SPECTATOR = 0; // Besitzt nur einfache Leserechte.
    case STUDENT = 1; // Besitzt Leserechte und kann zusätzlich seine eigenen und die Ergebnisse seiner Klasse ansehen.
    case SUPERVISOR = 2; // Lehrer & Wettkampfrichter. Verfügen zusätzlich über Schreibrechte (Schüler, Wettkampfergebnisse hinzufügen).
    case ADMIN = 3; // Wettkampfleiter - Verfügt über vollständige Lese- und Schreibrechte und kann die Berechtigungen aller Accounts verwalten.
}
