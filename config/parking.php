<?php
declare(strict_types=1);

function getParkingZones(mysqli $conn): array
{
    // Barneko izenak erabiltzen dira interfazeak eremu bakoitza rolarekin lotu dezan.
    $zones = [
        'student' => [],
        'teacher' => [],
        'public' => [],
    ];
    $zoneNames = [
        'Ikasleak' => 'student',
        'Irakasleak' => 'teacher',
        'Publikoa' => 'public',
    ];

    $result = $conn->query(
        'SELECT g.gune_izena, p.plaza_zenbakia, p.okupatuta_dago
         FROM parking_plazak p
         INNER JOIN parking_guneak g ON g.gune_id = p.gune_id
         ORDER BY p.gune_id, p.plaza_id'
    );
    if (!$result) {
        throw new RuntimeException('Ezin izan dira aparkalekuko datuak irakurri.');
    }

    while ($row = $result->fetch_assoc()) {
        // Datu-baseko eremu-izenak frontend-ak erabiltzen duen formatura bihurtzen dira.
        $zone = $zoneNames[$row['gune_izena']] ?? null;
        if ($zone !== null) {
            $zones[$zone][] = [
                'number' => $row['plaza_zenbakia'],
                'occupied' => (bool) $row['okupatuta_dago'],
            ];
        }
    }

    return $zones;
}

function parkingSummary(array $spots): array
{
    // Eremu baten okupazio-egoeraren laburpena kalkulatzen du.
    $total = count($spots);
    $occupied = count(array_filter($spots, static fn (array $spot): bool => $spot['occupied']));
    return [
        'total' => $total,
        'occupied' => $occupied,
        'free' => $total - $occupied,
        'percentage' => $total > 0 ? (int) round((($total - $occupied) / $total) * 100) : 0,
    ];
}
