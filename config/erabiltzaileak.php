<?php

require_once __DIR__ . '/constantes.php';


function addUser(
    string $nan,
    string $name,
    string $surname,
    string $email,
    int $roleId,
    mysqli $conn
): bool
{
    $password = DEFAULT_USER_PASSWORD;
    $stmt = $conn->prepare(
        'INSERT INTO erabiltzaileak
         (nan, izena, abizenak, posta_elektronikoa, pasahitza, rol_id, aktibo_dago)
         VALUES (?, ?, ?, ?, ?, ?, 1)'
    );
    if (!$stmt) {
        throw new RuntimeException('Ezin izan da erabiltzailea gehitu: ' . $conn->error);
    }
    $stmt->bind_param('sssssi', $nan, $name, $surname, $email, $password, $roleId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}


function getUsers(mysqli $conn): array
{
    $users = [];
    $result = $conn->query(
        'SELECT e.erabiltzaile_id, e.nan, e.izena, e.abizenak, e.posta_elektronikoa,
                e.rol_id, r.rol_izena
         FROM erabiltzaileak e
         LEFT JOIN rolak r ON r.rol_id = e.rol_id
         ORDER BY e.erabiltzaile_id'
    );
    if (!$result) {
        throw new RuntimeException('Ezin izan dira erabiltzaileak irakurri: ' . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

function getStudents(mysqli $conn): array
{
    $students = [];
    $result = $conn->query(
        'SELECT e.erabiltzaile_id, e.nan, e.izena, e.abizenak, e.posta_elektronikoa,
                e.aktibo_dago
         FROM erabiltzaileak e
         INNER JOIN rolak r ON r.rol_id = e.rol_id
         WHERE r.rol_izena = "Ikaslea"
         ORDER BY e.abizenak, e.izena, e.erabiltzaile_id'
    );
    if (!$result) {
        throw new RuntimeException('Ezin izan dira ikasleak irakurri: ' . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    return $students;
}

function deleteUser(int $userId, mysqli $conn): bool
{
    $stmt = $conn->prepare('DELETE FROM erabiltzaileak WHERE erabiltzaile_id = ?');
    if (!$stmt) {
        throw new RuntimeException('Ezin izan da erabiltzailea ezabatu: ' . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function getUserById(int $userId, mysqli $conn): ?array
{
    $stmt = $conn->prepare(
        'SELECT erabiltzaile_id, nan, izena, abizenak, posta_elektronikoa, rol_id
         FROM erabiltzaileak
         WHERE erabiltzaile_id = ?'
    );
    if (!$stmt) {
        throw new RuntimeException('Ezin izan da erabiltzailea irakurri: ' . $conn->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $user;
}

function updateUser(
    string $nan,
    string $name,
    string $surname,
    string $email,
    int $roleId,
    int $userId,
    bool $resetPassword,
    mysqli $conn
): bool
{
    if ($resetPassword) {
        $defaultPassword = DEFAULT_USER_PASSWORD;
        $stmt = $conn->prepare(
            'UPDATE erabiltzaileak
             SET nan = ?, izena = ?, abizenak = ?, posta_elektronikoa = ?, pasahitza = ?, rol_id = ?
             WHERE erabiltzaile_id = ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Ezin izan da erabiltzailea eguneratu: ' . $conn->error);
        }
        $stmt->bind_param('sssssii', $nan, $name, $surname, $email, $defaultPassword, $roleId, $userId);
    } else {
        $stmt = $conn->prepare(
            'UPDATE erabiltzaileak
             SET nan = ?, izena = ?, abizenak = ?, posta_elektronikoa = ?, rol_id = ?
             WHERE erabiltzaile_id = ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Ezin izan da erabiltzailea eguneratu: ' . $conn->error);
        }
        $stmt->bind_param('ssssii', $nan, $name, $surname, $email, $roleId, $userId);
    }
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

?>