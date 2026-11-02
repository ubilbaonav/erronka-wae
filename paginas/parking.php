<?php
declare(strict_types=1);

require __DIR__ . '/../config/conexioa.php';
require __DIR__ . '/../config/parking.php';

$zones = getParkingZones($conn);
$summary = parkingSummary($zones['public']);
$spots = '';
foreach ($zones['public'] as $spot) {
    $state = $spot['occupied'] ? 'occupied' : 'free';
    $label = $spot['occupied'] ? 'okupatuta' : 'libre';
    $number = htmlspecialchars($spot['number'], ENT_QUOTES, 'UTF-8');
    $spots .= '<span class="parking-spot ' . $state . '" title="Plaza ' . $number . ' ' . $label . '">' . $number . '</span>';
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="eu">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parking publikoa · Txurdinaga</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
  <header class="cabecera"><a class="identitatea" href="../index.html"><span class="identitatea-mark">T</span><span><strong>TXURDINAGA</strong><small>IKASTETXEAREN KUDEAKETA</small></span></a></header>
  <main class="contenedor parking-public-main">
    <section class="parking-view parking-public-view" aria-labelledby="parking-title">
      <div class="dashboard-heading"><div><span class="eyebrow">PARKING PUBLIKOA</span><h1 id="parking-title">Aparkalekuaren egoera</h1><p>Kontsultatu plaza publikoen egoera.</p></div><span class="date-chip">Eguneratuta orain</span></div>
      <div class="parking-layout">
        <article class="content-card parking-map-card"><div class="section-title"><div><span class="eyebrow">MAPA</span><h2>Txurdinaga · Parking publikoa</h2></div></div><div class="parking-map" aria-label="Mapa del parking: <?php echo $summary['free']; ?> plazas libres y <?php echo $summary['occupied']; ?> ocupadas"><div class="parking-lane">Sarrera <span>→</span></div><div class="parking-spots"><?php echo $spots; ?></div><div class="parking-lane parking-exit">Zirkulazioa <span>←</span></div></div></article>
        <aside class="parking-summary"><article class="stat-card parking-total"><div class="stat-head"><span>AFOROA</span></div><p class="cifra"><?php echo $summary['free']; ?> <span>/ <?php echo $summary['total']; ?> plaza libre</span></p><div class="progress green-progress"><span style="width:<?php echo $summary['percentage']; ?>%"></span></div><p class="nota">%<?php echo $summary['percentage']; ?>eko erabilgarritasuna · <?php echo $summary['occupied']; ?> okupatuta</p></article></aside>
      </div>
    </section>
  </main>
  
</body>
</html>
