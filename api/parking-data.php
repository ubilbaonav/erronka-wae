<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/conexioa.php';
require __DIR__ . '/../config/parking.php';

$zones = getParkingZones($conn);
$conn->close();
echo json_encode($zones, JSON_UNESCAPED_UNICODE);
