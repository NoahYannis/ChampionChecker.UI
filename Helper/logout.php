<?php
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

// Entfernt den Cookie, wodurch der Nutzer nicht mehr autorisiert ist. 
if (isset($_COOKIE['ChampionCheckerCookie'])) {
    setcookie('ChampionCheckerCookie', '', time() - 3600, '/');
}
