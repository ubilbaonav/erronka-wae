<?php
declare(strict_types=1);

// Configuramos la cookie de sesión antes de iniciar la sesión.
session_set_cookie_params([
    'lifetime' => 0,        // La cookie dura hasta que se cierra el navegador.
    'path' => '/',          // La sesión está disponible en todas las rutas de la web.
    'httponly' => true,     // JavaScript no puede leer la cookie directamente.
    'samesite' => 'Lax'     // Limita el envío de la cookie desde otros sitios.
]);

// Iniciamos la sesión y PHP envía al navegador la cookie PHPSESSID.
session_start();

$dbAccount = 'login';
require __DIR__ . '/../config/conexioa.php';

// Este archivo solo acepta los datos enviados por el formulario mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html#login');
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

// Limpiamos el email y validamos que tenga formato correcto y que haya contraseña.
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: ../index.html?error=1#login');
    exit;
}

// Buscamos el usuario por email mediante una consulta preparada.
// El marcador ? evita insertar directamente datos del formulario en el SQL.
$statement = $conn->prepare(
    'SELECT u.erabiltzaile_id, u.izena, u.abizenak, u.posta_elektronikoa, u.pasahitza,
            u.aktibo_dago, r.rol_izena
     FROM erabiltzaileak u
     INNER JOIN rolak r ON r.rol_id = u.rol_id
     WHERE u.posta_elektronikoa = ?
     LIMIT 1'
);

if (!$statement) {
    // Si la consulta no se puede preparar, no continuamos con un login incompleto.
    http_response_code(500);
    exit('Errorea saioa hastean.');
}

$statement->bind_param('s', $email);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();
$statement->close();

// Comprobamos dos condiciones: la contraseña coincide y la cuenta está activa.
$passwordMatches = $user && hash_equals((string) $user['pasahitza'], $password);
$isActive = $user && (int) $user['aktibo_dago'] === 1;

if (!$passwordMatches || !$isActive) {
    // Para el usuario mostramos un error genérico y no revelamos qué dato falló.
    $conn->close();
    header('Location: ../index.html?error=1#login');
    exit;
}

// Convertimos los nombres de rol de la base de datos a los valores usados por la aplicación.
$roleMap = [
    'Bisitaria' => 'visita',
    'Ikaslea' => 'estudiante',
    'Irakaslea' => 'profesor',
    'Admin' => 'administrador',
];

// Renovamos el ID de sesión después de autenticar para evitar session fixation.
session_regenerate_id(true);

// Guardamos en el servidor solo los datos necesarios para identificar al usuario
// y aplicar sus permisos en las páginas protegidas.
$_SESSION['user'] = [
    'id' => (int) $user['erabiltzaile_id'],                       // ID interno del usuario.
    'email' => $user['posta_elektronikoa'],                       // Email de acceso.
    'name' => trim($user['izena'] . ' ' . $user['abizenak']),     // Nombre completo.
    'role' => $roleMap[$user['rol_izena']] ?? 'visita',           // Rol interno de la aplicación.
];

// Cerramos la conexión y llevamos al usuario autenticado al dashboard.
$conn->close();
header('Location: ../paginas/dashboard.php');
exit;