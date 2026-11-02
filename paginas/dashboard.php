<?php

declare(strict_types=1);

session_start();
if (!isset($_SESSION['user'])) {
  header('Location: ../index.html#login');
  exit;
}
$user = $_SESSION['user'];

require __DIR__ . '/../config/conexioa.php';
require __DIR__ . '/../config/parking.php';
require __DIR__ . '/../config/erabiltzaileak.php';
require __DIR__ . '/../config/ibilgailuak.php';

$userMessage = '';
$vehicleMessage = '';
$vehicleEdit = null;
$editUser = null;
$roleOptions = [];
$roleResult = $conn->query('SELECT rol_id, rol_izena FROM rolak ORDER BY rol_id');
while ($roleRow = $roleResult->fetch_assoc()) {
  $roleOptions[(int) $roleRow['rol_id']] = $roleRow['rol_izena'];
}

$role = $user['role'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_vehicle') {
  $registration = strtoupper(trim((string) ($_POST['matrikula'] ?? '')));
  $brand = trim((string) ($_POST['marka'] ?? ''));
  $model = trim((string) ($_POST['modeloa'] ?? ''));

  if ($registration === '' || strlen($registration) > 15 || $brand === '' || strlen($brand) > 50 || $model === '' || strlen($model) > 50) {
    $vehicleMessage = 'Bete matrikula, marka eta modeloa balio zuzenekin.';
  } elseif (!addVehicle($registration, $brand, $model, (int) $user['id'], $conn)) {
    $vehicleMessage = 'Ezin izan da ibilgailua gehitu. Egiaztatu matrikula ez dagoela erabilita.';
  } else {
    header('Location: dashboard.php#nire-ibilgailua');
    exit;
  }
}

if ($role === 'administrador' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'vehicle_admin') {
  $vehicleAction = $_POST['vehicle_action'] ?? '';
  $registration = strtoupper(trim((string) ($_POST['matrikula'] ?? '')));
  $originalRegistration = strtoupper(trim((string) ($_POST['original_matrikula'] ?? '')));
  $brand = trim((string) ($_POST['marka'] ?? ''));
  $model = trim((string) ($_POST['modeloa'] ?? ''));
  $vehicleUserId = (int) ($_POST['erabiltzaile_id'] ?? 0);

  if ($vehicleAction === 'delete') {
    if ($registration === '' || !deleteVehicle($registration, $conn)) {
      $vehicleMessage = 'Ezin izan da ibilgailua ezabatu.';
    } else {
      header('Location: dashboard.php#ibilgailuen-kudeaketa');
      exit;
    }
  } elseif (
    !in_array($vehicleAction, ['create', 'update'], true)
    || $registration === ''
    || strlen($registration) > 15
    || $brand === ''
    || strlen($brand) > 50
    || $model === ''
    || strlen($model) > 50
    || $vehicleUserId <= 0
  ) {
    $vehicleMessage = 'Bete ibilgailuaren eremu guztiak balio zuzenekin.';
  } elseif ($vehicleAction === 'create' && !addVehicle($registration, $brand, $model, $vehicleUserId, $conn)) {
    $vehicleMessage = 'Ezin izan da ibilgailua sortu. Egiaztatu matrikula ez dagoela erabilita.';
  } elseif ($vehicleAction === 'update' && ($originalRegistration === '' || !updateVehicle($originalRegistration, $registration, $brand, $model, $vehicleUserId, $conn))) {
    $vehicleMessage = 'Ezin izan da ibilgailua eguneratu. Egiaztatu matrikula ez dagoela erabilita.';
  } else {
    header('Location: dashboard.php#ibilgailuen-kudeaketa');
    exit;
  }
}

if (
  $role === 'administrador'
  && $_SERVER['REQUEST_METHOD'] === 'POST'
  && !in_array($_POST['form_action'] ?? '', ['add_vehicle', 'vehicle_admin'], true)
) {
  $action = $_POST['user_action'] ?? '';
  $userId = (int) ($_POST['user_id'] ?? 0);
  $nan = trim((string) ($_POST['nan'] ?? ''));
  $name = trim((string) ($_POST['izena'] ?? ''));
  $surname = trim((string) ($_POST['abizenak'] ?? ''));
  $email = strtolower(trim((string) ($_POST['email'] ?? '')));
  $resetPassword = isset($_POST['reset_password']);
  $roleId = (int) ($_POST['rol_id'] ?? 0);

  if ($action === 'delete') {
    if ($userId === (int) $user['id']) {
      $userMessage = 'Ezin duzu zure kontua ezabatu.';
    } else {
      if (!deleteUser($userId, $conn)) {
        $userMessage = 'Ezin izan da erabiltzailea ezabatu.';
      } else {
        header('Location: dashboard.php?users=1');
        exit;
      }
    }
  } elseif (!in_array($action, ['create', 'update'], true) || $nan === '' || $name === '' || $surname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !isset($roleOptions[$roleId])) {
    $userMessage = 'Bete eremu guztiak balio zuzenekin.';
  } elseif ($action === 'create') {
    if (!addUser($nan, $name, $surname, $email, $roleId, $conn)) {
      $userMessage = 'Ezin izan da erabiltzailea sortu.';
    }
    if ($userMessage === '') {
      header('Location: dashboard.php?users=1');
      exit;
    }
  } else {
    if (!updateUser($nan, $name, $surname, $email, $roleId, $userId, $resetPassword, $conn)) {
      $userMessage = 'Ezin izan da erabiltzailea eguneratu.';
    }
    if ($userMessage === '') {
      header('Location: dashboard.php?users=1');
      exit;
    }
  }
}

if ($role === 'administrador' && isset($_GET['edit_user'])) {
  $editId = (int) $_GET['edit_user'];
  $editUser = getUserById($editId, $conn);
}
if ($role === 'administrador' && isset($_GET['edit_vehicle'])) {
  $vehicleRegistration = strtoupper(trim((string) $_GET['edit_vehicle']));
  $vehicleResult = $conn->prepare(
    'SELECT matrikula, erabiltzaile_id, marka, modeloa
     FROM ibilgailuak
     WHERE matrikula = ?'
  );
  if (!$vehicleResult) {
    throw new RuntimeException('Ezin izan da ibilgailua irakurri: ' . $conn->error);
  }
  $vehicleResult->bind_param('s', $vehicleRegistration);
  $vehicleResult->execute();
  $vehicleEdit = $vehicleResult->get_result()->fetch_assoc() ?: null;
  $vehicleResult->close();
}

$zones = getParkingZones($conn);
$requestedZone = $_GET['parking'] ?? null;
$allowedZones = [
  'visita' => ['public'],
  'estudiante' => ['public', 'student'],
  'profesor' => ['public', 'teacher'],
  'administrador' => ['public', 'teacher'],
];
$viewParking = $requestedZone !== null && isset($allowedZones[$role]) && in_array($requestedZone, $allowedZones[$role], true);
$selectedZone = $viewParking ? $requestedZone : 'public';
$zoneLabels = [
  'public' => ['PARKING PUBLIKOA', 'Txurdinaga · Parking publikoa', 'Parking publikoa'],
  'student' => ['PARKING IKASLEAK', 'Txurdinaga · Parking ikasleak', 'Parking ikasleak'],
  'teacher' => ['PARKING IRAKASLEAK', 'Txurdinaga · Parking irakasleak', 'Parking irakasleak'],
];
if (!isset($zoneLabels[$selectedZone])) {
  $selectedZone = 'public';
}
$parking = $zones[$selectedZone];
$parkingSummary = parkingSummary($parking);
$parkingSpots = '';
foreach ($parking as $spot) {
  $state = $spot['occupied'] ? 'occupied' : 'free';
  $label = $spot['occupied'] ? 'okupatuta' : 'libre';
  $number = htmlspecialchars($spot['number'], ENT_QUOTES, 'UTF-8');
  $parkingSpots .= '<span class="parking-spot ' . $state . '" title="Plaza ' . $number . ' ' . $label . '">' . $number . '</span>';
}
$sessionName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
$sessionInitial = htmlspecialchars(mb_substr($user['name'], 0, 1), ENT_QUOTES, 'UTF-8');
$roleLabels = [
  'estudiante' => ['IKASLEA', 'Egun on, ' . $sessionName, 'Hona hemen zure ikastetxeko jardueraren laburpena.'],
  'profesor' => ['IRAKASLEA', 'Egun on, ' . $sessionName, 'Kudeatu zure eguneroko jarduera eta ikasleen informazioa.'],
  'administrador' => ['ADMINISTRATZAILEA', 'Egun on, ' . $sessionName, 'Ikastetxearen jardueraren kontrol-panel orokorra.'],
];
$roleData = $roleLabels[$role] ?? $roleLabels['estudiante'];
$users = [];
$students = [];
$vehicles = getVehiclesByUserId($conn, (int) $user['id']);
$allVehicles = [];
if ($role === 'administrador') {
  $users = getUsers($conn);
  $allVehicles = getAllVehicles($conn);
}
if ($role === 'profesor') {
  $students = getStudents($conn);
}


$conn->close();

?>
<!DOCTYPE html>
<html lang="eu">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard · Txurdinaga</title>
  <link rel="stylesheet" href="../assets/styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css">
  <link rel="icon" type="image/png" href="../assets/IMG/icon.png">
</head>

<body>
  <header class="cabecera">
    <a class="identitatea" href="dashboard.php"><span
        class="identitatea-mark">T</span><span><strong>TXURDINAGA</strong><small>IKASTETXEAREN
          KUDEAKETA</small></span></a>
    <div class="sesion"><span class="user-avatar" id="user-initial"><?php echo $sessionInitial; ?></span><span id="user-name"
        class="user-name"><?php echo $sessionName; ?></span><button class="btn btn-quiet btn-mini" id="logout" type="button">Amaitu saioa</button>
    </div>
  </header>
  <div class="app-shell">
    <aside class="sidebar"><span class="sidebar-label">NABIGAZIOA</span><a class="<?php echo !$viewParking ? 'active' : ''; ?>" href="dashboard.php">▦
        <span>Laburpena</span></a><a href="#nire-ibilgailua">🚗 <span>Nire cochea</span></a><?php if ($role === 'profesor'): ?><a href="#alumnos">♙ <span>Ikasleak</span></a><?php endif; ?><?php if (in_array('public', $allowedZones[$role] ?? [], true)): ?><a class="<?php echo $viewParking && $selectedZone === 'public' ? 'active' : ''; ?>" href="dashboard.php?parking=public">⌖ <span>Parking publikoa</span></a><?php endif; ?><?php if (in_array('student', $allowedZones[$role] ?? [], true)): ?><a class="<?php echo $viewParking && $selectedZone === 'student' ? 'active' : ''; ?>" href="dashboard.php?parking=student">▣ <span>Parking ikasleak</span></a><?php endif; ?><?php if (in_array('teacher', $allowedZones[$role] ?? [], true)): ?><a class="<?php echo $viewParking && $selectedZone === 'teacher' ? 'active' : ''; ?>" href="dashboard.php?parking=teacher">▣ <span>Parking irakasleak</span></a><?php endif; ?><a href="#profila">◉ <span>Nire
          profila</span></a><a href="#laguntza">? <span>Laguntza</span></a></aside>
    <main class="dashboard-main vista-dashboard">
      <section class="dashboard-heading" id="summary-heading" <?php echo $viewParking ? 'hidden' : ''; ?>>
        <div><span class="eyebrow" id="role-label"><?php echo $roleData[0]; ?></span>
          <h1 id="dashboard-title"><?php echo $roleData[1]; ?></h1>
          <p id="dashboard-description"><?php echo $roleData[2]; ?></p>
        </div><span class="date-chip">2026ko irailak 28 · Astelehena</span>
      </section>
      <div class="aforo">
        <article class="stat-card">
          <div class="stat-head"><span>Aforoaren egoera</span><span class="stat-icon blue">◉</span></div>
          <p class="cifra">412 <span>/ 600</span></p>
          <div class="progress"><span style="width:69%"></span></div>
          <p class="nota">Aforoaren %69 · Egoera normala</p>
        </article>
        <article class="stat-card">
          <div class="stat-head"><span>Aparkalekua</span><span class="stat-icon green">⌖</span></div>
          <p class="cifra"><?php echo $parkingSummary['free']; ?> <span>plaza libre</span></p>
          <div class="progress green-progress"><span style="width:<?php echo $parkingSummary['percentage']; ?>%"></span></div>
          <p class="nota"><?php echo $parkingSummary['total']; ?> plazatik <?php echo $parkingSummary['free']; ?> erabilgarri</p>
        </article>
        <article class="stat-card stat-highlight">
          <div class="stat-head"><span>Gaurko jarduera</span><span class="stat-icon orange">↗</span></div>
          <p class="cifra">24 <span>hitzordu</span></p>
          <p class="nota">+12% aurreko astearekin alderatuta</p>
        </article>
      </div>
      <div id="role-content" class="dashboard-content">
        <section class="content-card tarjeta">
          <div class="section-title">
            <div><span class="eyebrow">DATUAK</span>
              <h2>Informazio eguneratua</h2>
            </div>
          </div>
          <p>Informazioa kargatu da Txurdinagako datu-basetik.</p>
        </section>
        <section id="nire-ibilgailua" class="content-card tarjeta vehicle-section">
          <div class="section-title">
            <div><span class="eyebrow">IBILGAILUAK</span>
              <h2>Nire cochearen informazioa</h2>
            </div>
            <span class="status success"><?php echo count($vehicles); ?> coche</span>
          </div>
          <?php if ($vehicleMessage !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($vehicleMessage, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
          <form class="user-form vehicle-form" method="post">
            <input type="hidden" name="form_action" value="add_vehicle">
            <label>Matrikula<input name="matrikula" type="text" maxlength="15" required placeholder="0000-ABC"></label>
            <label>Marka<input name="marka" type="text" maxlength="50" required placeholder="Toyota"></label>
            <label>Modeloa<input name="modeloa" type="text" maxlength="50" required placeholder="Corolla"></label>
            <div class="user-form-actions"><button class="btn btn-mini" type="submit">Gorde cochea</button></div>
          </form>
          <?php if ($vehicles !== []): ?>
            <div class="users-table-wrap">
              <table class="user-table">
                <thead>
                  <tr>
                    <th>Matrikula</th>
                    <th>Marka</th>
                    <th>Modeloa</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) $vehicle['matrikula'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $vehicle['marka'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $vehicle['modeloa'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
        <?php if ($role === 'administrador'): ?>
          <section id="ibilgailuen-kudeaketa" class="content-card tarjeta admin-vehicles">
            <div class="section-title">
              <div><span class="eyebrow">IBILGAILUAK</span>
                <h2>Parkeko coche guztiak</h2>
              </div>
              <a class="btn btn-mini" href="dashboard.php?vehicle_create=1#ibilgailuen-kudeaketa">+ Gehitu cochea</a>
            </div>
            <?php if ($vehicleMessage !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($vehicleMessage, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            <?php if ($vehicleEdit !== null || isset($_GET['vehicle_create']) || $vehicleMessage !== ''): ?>
              <form class="user-form vehicle-form" method="post">
                <input type="hidden" name="form_action" value="vehicle_admin">
                <input type="hidden" name="vehicle_action" value="<?php echo $vehicleEdit !== null ? 'update' : 'create'; ?>">
                <?php if ($vehicleEdit !== null): ?><input type="hidden" name="original_matrikula" value="<?php echo htmlspecialchars((string) $vehicleEdit['matrikula'], ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
                <label>Matrikula<input name="matrikula" type="text" maxlength="15" required value="<?php echo htmlspecialchars((string) ($vehicleEdit['matrikula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>Marka<input name="marka" type="text" maxlength="50" required value="<?php echo htmlspecialchars((string) ($vehicleEdit['marka'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>Modeloa<input name="modeloa" type="text" maxlength="50" required value="<?php echo htmlspecialchars((string) ($vehicleEdit['modeloa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>Jabea<select name="erabiltzaile_id" required>
                    <option value="">Aukeratu erabiltzailea</option>
                    <?php foreach ($users as $vehicleUser): ?>
                      <option value="<?php echo (int) $vehicleUser['erabiltzaile_id']; ?>" <?php echo (int) ($vehicleEdit['erabiltzaile_id'] ?? 0) === (int) $vehicleUser['erabiltzaile_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($vehicleUser['izena'] . ' ' . $vehicleUser['abizenak'] . ' · ' . $vehicleUser['posta_elektronikoa'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select></label>
                <div class="user-form-actions"><button class="btn btn-mini" type="submit"><?php echo $vehicleEdit !== null ? 'Gorde aldaketak' : 'Gehitu'; ?></button><a class="btn btn-quiet btn-mini" href="dashboard.php#ibilgailuen-kudeaketa">Utzi</a></div>
              </form>
            <?php endif; ?>
            <div class="users-table-wrap">
              <table class="user-table">
                <thead>
                  <tr>
                    <th>Matrikula</th>
                    <th>Jabea</th>
                    <th>Marka</th>
                    <th>Modeloa</th>
                    <th>Ekintzak</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($allVehicles as $vehicle): ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) $vehicle['matrikula'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(trim((string) ($vehicle['izena'] ?? '') . ' ' . (string) ($vehicle['abizenak'] ?? '')) ?: 'Erabiltzaile ezezaguna', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $vehicle['marka'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $vehicle['modeloa'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="user-actions">
                        <a class="btn btn-quiet btn-mini" href="dashboard.php?edit_vehicle=<?php echo urlencode((string) $vehicle['matrikula']); ?>#ibilgailuen-kudeaketa">Editatu</a>
                        <form method="post" onsubmit="return confirm('Coche hau ezabatu nahi duzu?');">
                          <input type="hidden" name="form_action" value="vehicle_admin">
                          <input type="hidden" name="vehicle_action" value="delete">
                          <input type="hidden" name="matrikula" value="<?php echo htmlspecialchars((string) $vehicle['matrikula'], ENT_QUOTES, 'UTF-8'); ?>">
                          <button class="btn btn-quiet btn-mini" type="submit">Ezabatu</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if ($allVehicles === []): ?><tr>
                      <td colspan="5">Ez dago coche erregistraturik.</td>
                    </tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>
        <?php if ($role === 'profesor'): ?>
          <section id="alumnos" class="content-card tarjeta student-list">
            <div class="section-title">
              <div><span class="eyebrow">IKASLEAK</span>
                <h2>Ikasleen zerrenda</h2>
              </div>
              <span class="status success"><?php echo count($students); ?> ikasle</span>
            </div>
            <div class="users-table-wrap">
              <table class="user-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>NAN</th>
                    <th>Izena</th>
                    <th>Emaila</th>
                    <th>Egoera</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($students as $student): ?>
                    <tr>
                      <td><?php echo (int) $student['erabiltzaile_id']; ?></td>
                      <td><?php echo htmlspecialchars((string) $student['nan'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($student['izena'] . ' ' . $student['abizenak'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student['posta_elektronikoa'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><span class="status <?php echo (int) $student['aktibo_dago'] === 1 ? 'success' : 'warning'; ?>"><?php echo (int) $student['aktibo_dago'] === 1 ? 'Aktiboa' : 'Inaktiboa'; ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if ($students === []): ?>
                    <tr>
                      <td colspan="5">Ez dago ikaslerik erregistratuta.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>
        <?php if ($role === 'administrador'): ?>
          <section class="content-card tarjeta admin-users">
            <div class="section-title">
              <div><span class="eyebrow">ERABILTZAILEAK</span>
                <h2>Erabiltzaileen zerrenda</h2>
              </div>
              <a class="btn btn-mini" href="dashboard.php?users=1#user-modal">+ Gehitu erabiltzailea</a>
            </div>
            <?php if ($userMessage !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            <?php if ($editUser !== null || isset($_GET['users']) || $userMessage !== ''): ?>
              <div id="user-modal" class="modal" style="display:flex">
                <div class="modal-caja tarjeta">
                  <a class="modal-cerrar" href="dashboard.php" aria-label="Itxi">&times;</a>
                  <span class="eyebrow"><?php echo $editUser !== null ? 'ERABILTZAILEA EDITATU' : 'ERABILTZAILE BERRIA'; ?></span>
                  <h2><?php echo $editUser !== null ? 'Erabiltzailea editatu' : 'Erabiltzailea gehitu'; ?></h2>
                  <form id="user-form" class="user-form" method="post">
                    <input type="hidden" name="user_action" value="<?php echo $editUser !== null ? 'update' : 'create'; ?>">
                    <?php if ($editUser !== null): ?><input type="hidden" name="user_id" value="<?php echo (int) $editUser['erabiltzaile_id']; ?>"><?php endif; ?>
                    <label>NAN<input name="nan" type="text" maxlength="15" required value="<?php echo htmlspecialchars((string) ($editUser['nan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Izena<input name="izena" type="text" maxlength="50" required value="<?php echo htmlspecialchars((string) ($editUser['izena'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Abizenak<input name="abizenak" type="text" maxlength="100" required value="<?php echo htmlspecialchars((string) ($editUser['abizenak'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <label>Emaila<input name="email" type="email" maxlength="100" required value="<?php echo htmlspecialchars((string) ($editUser['posta_elektronikoa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                    <?php if ($editUser !== null): ?><label><input name="reset_password" type="checkbox" value="1"> Berrezarri pasahitza: <strong><?php echo htmlspecialchars(DEFAULT_USER_PASSWORD, ENT_QUOTES, 'UTF-8'); ?></strong></label><?php else: ?><p class="nota">Pasahitza lehenetsia: <strong><?php echo htmlspecialchars(DEFAULT_USER_PASSWORD, ENT_QUOTES, 'UTF-8'); ?></strong></p><?php endif; ?>
                    <label>Rola<select name="rol_id" required><?php foreach ($roleOptions as $id => $label): ?><option value="<?php echo $id; ?>" <?php echo (int) ($editUser['rol_id'] ?? 0) === $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
                    <div class="user-form-actions"><button class="btn btn-mini" type="submit"><?php echo $editUser !== null ? 'Gorde aldaketak' : 'Gehitu'; ?></button><a class="btn btn-quiet btn-mini" href="dashboard.php">Utzi</a></div>
                  </form>
                </div>
              </div>
            <?php endif; ?>
            <div class="users-table-wrap">
              <table class="user-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>NAN</th>
                    <th>Izena</th>
                    <th>Emaila</th>
                    <th>Rola</th>
                    <th>Ekintzak</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $user): ?>
                    <tr>
                      <td><?php echo (int) $user['erabiltzaile_id']; ?></td>
                      <td><?php echo htmlspecialchars((string) $user['nan']); ?></td>
                      <td><?php echo htmlspecialchars($user['izena'] . ' ' . $user['abizenak']); ?></td>
                      <td><?php echo htmlspecialchars((string) $user['posta_elektronikoa']); ?></td>
                      <td><?php echo htmlspecialchars((string) $user['rol_izena']); ?></td>
                      <td class="user-actions"><a class="btn btn-quiet btn-mini" href="dashboard.php?users=1&edit_user=<?php echo (int) $user['erabiltzaile_id']; ?>#user-modal">Editatu</a>
                        <form method="post" onsubmit="return confirm('Erabiltzaile hau ezabatu nahi duzu?');"><input type="hidden" name="user_action" value="delete"><input type="hidden" name="user_id" value="<?php echo (int) $user['erabiltzaile_id']; ?>"><button class="btn btn-quiet btn-mini" type="submit">Ezabatu</button></form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>
      </div>
      <section class="parking-view" id="parking-view" <?php echo !$viewParking ? 'hidden' : ''; ?> aria-labelledby="parking-title">
        <div class="dashboard-heading">
          <div><span class="eyebrow" id="parking-eyebrow"><?php echo $zoneLabels[$selectedZone][0]; ?></span>
            <h1 id="parking-title">Aparkalekuaren egoera</h1>
            <p id="parking-description">Uneko plaza erabilgarrien mapa.</p>
          </div><span class="date-chip"><span class="status-dot status-dot-dark"></span> Eguneratuta orain</span>
        </div>
        <div class="parking-layout">
          <article class="content-card parking-map-card">
            <div class="section-title">
              <div><span class="eyebrow">MAPA</span>
                <h2 id="parking-map-title"><?php echo $zoneLabels[$selectedZone][1]; ?></h2>
              </div>
              <div class="map-legend"><span><i class="spot free"></i> Libre</span><span><i class="spot occupied"></i>
                  Okupatuta</span></div>
            </div>
            <div class="parking-map" aria-label="Mapa del parking: <?php echo $parkingSummary['free']; ?> plazas libres y <?php echo $parkingSummary['occupied']; ?> ocupadas">
              <div class="parking-lane">Sarrera <span>→</span></div>
              <div class="parking-spots"><?php echo $parkingSpots; ?></div>
              <div class="parking-lane parking-exit">Zirkulazioa <span>←</span></div>
            </div>
          </article>
          <aside class="parking-summary">
            <article class="stat-card parking-total">
              <div class="stat-head"><span>AFOROA</span><span class="stat-icon green">⌖</span></div>
              <p class="cifra"><?php echo $parkingSummary['free']; ?> <span>/ <?php echo $parkingSummary['total']; ?> plaza libre</span></p>
              <div class="progress green-progress"><span style="width:<?php echo $parkingSummary['percentage']; ?>%"></span></div>
              <p class="nota">%<?php echo $parkingSummary['percentage']; ?>eko erabilgarritasuna · <?php echo $parkingSummary['occupied']; ?> okupatuta</p>
            </article>
            <article class="content-card parking-note"><span class="eyebrow">ORDUTEGIA</span>
              <h2 id="parking-hours-title"><?php echo $zoneLabels[$selectedZone][2]; ?></h2>
              <p>Astegunetan, 07:00etatik 22:00etara irekita.</p><span class="status success">Egoera normala</span>
            </article>
          </aside>
        </div>
      </section>
    </main>
  </div>
  <script type="module">
    import {
      createChat
    } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

    createChat({
      webhookUrl: 'http://localhost:5678/webhook/9c28c255-e4be-4682-b887-083d766bcaf2/chat'
    });
  </script>
  <script src="../assets/app.js?v=3"></script>
  <!-- Cookie txartel flotatzailearen edukiontzi nagusia ('id'-ari esker JavaScript bidez ezkutatuko dugu) -->
  <div id="cookie-banner" class="cookie-banner">

    <!-- Ikonoa eta testuak biltzen dituen barneko edukia (CSS Flexbox bidez lerrokatzeko) -->
    <div class="cookie-content">

      <!-- Galletaren ikono grafikoa, itxura modernoagoa eta bisualki erakargarria emateko -->
      <span class="cookie-icon">🍪</span>

      <!-- Informatzeko testuen atala -->
      <div class="cookie-text">
        <!-- Oharraren izenburu nagusia negritan -->
        <strong>Cookie-en erabilera / Uso de cookies</strong>

        <!-- Erabiltzaileari cookie-en helburua azaltzen dion legezko mezua -->
        <p>Webgune honek cookie-ak erabiltzen ditu saioa kudeatzeko eta zure nabigazio esperientzia hobetzeko. / Utilizamos cookies para gestionar la sesión y mejorar tu experiencia.</p>
      </div>

    </div>

    <!-- Botoia kokatzeko eta antolatzeko atala -->
    <div class="cookie-actions">
      <form method="post" action="../api/cookie-consent.php">
        <input type="hidden" name="onartu" value="bai">
        <input type="hidden" name="return_to" value="../paginas/dashboard.php">
        <button class="btn btn-mini" type="submit">Aceptatu guztia / Aceptar todo</button>
      </form>
    </div>

  </div>
</body>

</html>