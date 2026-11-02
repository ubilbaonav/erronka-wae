<?php

// Datu motak zorrotz egiaztatu (akatsak saihesteko)
declare(strict_types=1);

// Webgunearen konfigurazioa eta datu nagusiak kargatu
require __DIR__ . '/../config/constantes.php';

// Formularioa POST bidez bidali dela eta 'bai' sakatu dela egiaztatu
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['onartu'] ?? '') !== 'bai') {
    // Baldintzak betetzen ez badira, errorea erakutsi eta kodea gelditu
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid request');
}

// Erabiltzaileak cookie-ak onartu dituela gogoratzeko cookie-a sortu
setcookie(COOKIE_CONSENT_NAME, COOKIE_CONSENT_VALUE, [
    'expires' => time() + (COOKIE_CONSENT_DAYS * 24 * 60 * 60), // Zenbat denborarako gordetzen den
    'path' => '/', // Webgune osoan erabilgarri egotea
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // Konexio seguruan bakarrik (HTTPS)
    'httponly' => false, // JavaScript bidez irakurri ahal izatea
    'samesite' => 'Lax', // Segurtasun babes orokorra
]);

// Erabiltzailea nora itzuli behar den ikusi (bestela index.html-era)
$returnTo = $_POST['return_to'] ?? '../index.html';

// Segurtasuna: baimenduta dauden orrien zerrenda
$allowedPages = ['../index.html', '../paginas/dashboard.php'];

// Aukeratutako orria baimenduta ez badago, errorea eman
if (!in_array($returnTo, $allowedPages, true)) {
    http_response_code(400);
    exit('Invalid redirect');
}

// Erabiltzailea bere orrira bidali eta amaitu
header('Location: ' . $returnTo);
exit;