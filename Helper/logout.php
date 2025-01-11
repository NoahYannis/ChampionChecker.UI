<?php
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

if (isset($_COOKIE['ChampionCheckerCookie'])) {
    setcookie('ChampionCheckerCookie', '', time() - 3600, '/');
}
