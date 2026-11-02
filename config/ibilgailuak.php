<?php

declare(strict_types=1);

// Erabiltzaile batek bere saioarekin lotutako ibilgailuak bakarrik kontsultatzen ditu.
function getVehiclesByUserId(mysqli $conn, int $userId): array
{
    $vehicles = [];
    $statement = $conn->prepare(
        'SELECT matrikula, marka, modeloa
         FROM ibilgailuak
         WHERE erabiltzaile_id = ?
         ORDER BY matrikula'
    );
    if (!$statement) {
        throw new RuntimeException('Ezin izan dira zure ibilgailuak irakurri: ' . $conn->error);
    }
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    $statement->close();

    return $vehicles;
}

function addVehicle(
    string $registration,
    string $brand,
    string $model,
    int $userId,
    mysqli $conn
): bool {
    // Matrícula gako nagusia denez, eragiketa honek matrikula bikoiztuak baztertzen ditu.
    $statement = $conn->prepare(
        'INSERT INTO ibilgailuak (matrikula, erabiltzaile_id, marka, modeloa)
         VALUES (?, ?, ?, ?)'
    );
    if (!$statement) {
        throw new RuntimeException('Ezin izan da ibilgailua gehitu: ' . $conn->error);
    }
    $statement->bind_param('siss', $registration, $userId, $brand, $model);
    $success = $statement->execute();
    $statement->close();

    return $success;
}

function getAllVehicles(mysqli $conn): array
{
    // Administratzaileak ibilgailu guztiak ikusten ditu, jabearen izenarekin batera.
    $vehicles = [];
    $result = $conn->query(
        'SELECT v.matrikula, v.erabiltzaile_id, v.marka, v.modeloa,
                e.izena, e.abizenak
         FROM ibilgailuak v
         LEFT JOIN erabiltzaileak e ON e.erabiltzaile_id = v.erabiltzaile_id
         ORDER BY v.matrikula'
    );
    if (!$result) {
        throw new RuntimeException('Ezin izan dira ibilgailuak irakurri: ' . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }

    return $vehicles;
}

function updateVehicle(
    string $originalRegistration,
    string $registration,
    string $brand,
    string $model,
    int $userId,
    mysqli $conn
): bool {
    // Jatorrizko matrikula erabiltzen da erregistroa aurkitzeko nahiz eta matrikula aldatu.
    $statement = $conn->prepare(
        'UPDATE ibilgailuak
         SET matrikula = ?, erabiltzaile_id = ?, marka = ?, modeloa = ?
         WHERE matrikula = ?'
    );
    if (!$statement) {
        throw new RuntimeException('Ezin izan da ibilgailua eguneratu: ' . $conn->error);
    }
    $statement->bind_param('sisss', $registration, $userId, $brand, $model, $originalRegistration);
    $success = $statement->execute();
    $statement->close();

    return $success;
}

function deleteVehicle(string $registration, mysqli $conn): bool
{
    // Ezabatzea matrikularekin lotutako ibilgailura mugatzen da.
    $statement = $conn->prepare('DELETE FROM ibilgailuak WHERE matrikula = ?');
    if (!$statement) {
        throw new RuntimeException('Ezin izan da ibilgailua ezabatu: ' . $conn->error);
    }
    $statement->bind_param('s', $registration);
    $success = $statement->execute();
    $statement->close();

    return $success;
}
