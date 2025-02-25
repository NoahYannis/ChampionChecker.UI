<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];

if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', [
        'expires' => 1,  // Zeit in der Vergangenheit
        'path' => '/',
        'domain' => $domain,
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    unset($_COOKIE['PHPSESSID']); 
}

if (isset($_COOKIE['ChampionCheckerCookie'])) {
    setcookie('ChampionCheckerCookie', '', [
        'expires' => 1,  // Zeit in der Vergangenheit
        'path' => '/',
        'domain' => $domain,
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    unset($_COOKIE['ChampionCheckerCookie']); 
}

session_start();
$_SESSION = array(); 
session_destroy();