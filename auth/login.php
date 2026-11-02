<?php
declare(strict_types=1);

// 1. Saio-cookie-aren segurtasun parametroak konfiguratzen ditugu session_start() egin AURRETIK
session_set_cookie_params([
    'lifetime' => 0,        // Cookie-a nabigatzailea ixtean automatikoki ezabatzen da
    'path' => '/',          // Cookie-a webgune osoko karpeta guztietan dago erabilgarri (XAMPP-erako ezinbestekoa)
    'httponly' => true,     // XSS babesa: JavaScript bidez ezin da saio-cookie hau irakurri
    'samesite' => 'Lax'     // CSRF babesa: Kanpoko orrialdeetatik datozen eskaera kaltegarriak ekiditen ditu
]);

// 2. Zerbitzarian saioa abiarazten dugu eta PHPSESSID cookie-a nabigatzailera bidaltzen dugu
session_start();

$dbAccount = 'login';
require __DIR__ . '/../config/conexioa.php';

// Formularioa POST bidez bidali ez bada, login orrira bideratzen dugu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html#login');
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

// Baliozkotu emaila eta pasahitza ez daudela hutsik
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: ../index.html?error=1#login');
    exit;
}

// Erabiltzailea datu-basean bilatu SQL Injection erasoak saihestuz (prepared statement)
$statement = $conn->prepare(
    'SELECT u.erabiltzaile_id, u.izena, u.abizenak, u.posta_elektronikoa, u.pasahitza,
            u.aktibo_dago, r.rol_izena
     FROM erabiltzaileak u
     INNER JOIN rolak r ON r.rol_id = u.rol_id
     WHERE u.posta_elektronikoa = ?
     LIMIT 1'
);

if (!$statement) {
    http_response_code(500);
    exit('Errorea saioa hastean.');
}

$statement->bind_param('s', $email);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();
$statement->close();

// Pasahitza eta erabiltzailea aktibo dagoen egiaztatu
$passwordMatches = $user && hash_equals((string) $user['pasahitza'], $password);
$isActive = $user && (int) $user['aktibo_dago'] === 1;

if (!$passwordMatches || !$isActive) {
    $conn->close();
    header('Location: ../index.html?error=1#login');
    exit;
}

// Rola mapatu gure sistemako testuekin
$roleMap = [
    'Bisitaria' => 'visita',
    'Ikaslea' => 'estudiante',
    'Irakaslea' => 'profesor',
    'Admin' => 'administrador',
];

// 3. Saioaren identifikazio-kodea (ID) berriro sortzen dugu segurtasunagatik (Session Fixation saihesteko)
session_regenerate_id(true);

// 4. Erabiltzailearen identifikazio datuak zerbitzariko saioan ($_SESSION) gordetzen ditugu
$_SESSION['user'] = [
    'id' => (int) $user['erabiltzaile_id'],                       // Erabiltzailearen IDa
    'email' => $user['posta_elektronikoa'],                       // Posta elektronikoa
    'name' => trim($user['izena'] . ' ' . $user['abizenak']),     // Izena eta abizenak garbi
    'role' => $roleMap[$user['rol_izena']] ?? 'visita',           // Rola (ikaslea, irakaslea, admin...)
];

// Konexioa itxi eta erabiltzailea dashboard orrira bideratu
$conn->close();
header('Location: ../paginas/dashboard.php');
exit;