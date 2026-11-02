<?php
declare(strict_types=1);

// 1. Uneko saioa abiarazten dugu, zein erabiltzaileren saioa itxi behar den jakiteko
session_start();

// 2. Zerbitzariko memoria garbitzen dugu: saioaren aldagai guztiak ($_SESSION) hustuz
$_SESSION = [];

// 3. Nabigatzaileko saio-cookie-a (PHPSESSID) ezabatzen dugu
if (ini_get('session.use_cookies')) {
    // Cookie-ak dituen jatorrizko parametroak lortzen ditugu
    $params = session_get_cookie_params();
    
    // Cookie-aren iraungitze-data iraganean jartzen dugu (duela 42.000 segundoko data)
    // Horrela, nabigatzaileak automatikoki ezabatzen du cookie-a gailutik
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 4. Zerbitzarian dagoen saioaren fitxategia guztiz ezabatzen dugu
session_destroy();

// 5. Erabiltzailea berriro login orrira bideratzen dugu eta PHPren exekuzioa gelditzen dugu
header('Location: ../index.html#login');
exit;