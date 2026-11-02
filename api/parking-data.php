<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// API honek parkingaren datuak JSON formatuan ematen ditu.
require __DIR__ . '/../config/conexioa.php';
require __DIR__ . '/../config/parking.php';

$zones = getParkingZones($conn);
$conn->close();

// Unicode karaktereak euskaraz behar bezala mantentzen dira.
echo json_encode($zones, JSON_UNESCAPED_UNICODE);
