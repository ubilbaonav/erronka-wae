<?php

declare(strict_types=1);

require_once __DIR__ . '/constantes.php';

function conectarBaseDatos(string $account): mysqli
{
    $accounts = [
        'login' => ['web_login', 'loginweb123'],
        'administrador' => ['web_admin', 'adminweb123'],
        'profesor' => ['web_irakaslea', 'irakasles123'],
        'estudiante' => ['web_ikaslea', 'ikasle123'],
        'public' => ['web_ikaslea', 'ikasle123'],
    ];
    [$username, $password] = $accounts[$account] ?? $accounts['public'];

    $conn = new mysqli(DB_HOST, $username, $password, DB_NAME);
    if ($conn->connect_error) {
        throw new RuntimeException('No se pudo conectar con la base de datos.');
    }

    return $conn;
}

$account = $dbAccount ?? ($_SESSION['user']['role'] ?? 'public');
$conn = conectarBaseDatos($account);