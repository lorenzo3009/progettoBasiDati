<?php
require_once '../includes/auth.php';
require_once '../db.php';

requireRole('responsabile');

$me = $_SESSION['username'];

// Le aziende di cui sono responsabile.
$stmt = $pdo->prepare("
    SELECT ragione_sociale, nome, settore, num_dipendenti, nr_bilanci
    FROM azienda
    WHERE username_responsabile = ?
    ORDER BY nome
");
$stmt->execute([$me]);
$le_mie_aziende = $stmt->fetchAll();

// I miei bilanci (di tutte le mie aziende).
$stmt = $pdo->prepare("
    SELECT b.id_bilancio, b.data_creazione, b.stato, a.nome AS nome_azienda
    FROM bilancio b
    JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
    WHERE a.username_responsabile = ?
    ORDER BY b.data_creazione DESC
");
$stmt->execute([$me]);
$miei_bilanci = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Responsabile — ESG-BALANCE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🏢 Pannello Responsabile</a>
    <div>
      <span class="text-light me-3"><?= htmlspecialchars($me) ?></span>
      <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="mb-4">Benvenuto, <?= htmlspecialchars($me) ?></h1>

  <!-- Le mie aziende -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Le mie aziende</h3>
    <a href="registra_azienda.php" class="btn btn-success btn-sm">+ Nuova azienda</a>
  </div>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <?php if (count($le_mie_aziende) > 0): ?>
        <table class="table">
          <thead>
            <tr><th>Nome</th><th>Settore</th><th>Dipendenti</th><th>Bilanci</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($le_mie_aziende as $a): ?>
              <tr>
                <td><strong><?= htmlspecialchars($a['nome']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($a['ragione_sociale']) ?></small></td>
                <td><?= htmlspecialchars($a['settore']) ?></td>
                <td><?= $a['num_dipendenti'] ?></td>
                <td><span class="badge bg-info"><?= $a['nr_bilanci'] ?></span></td>
                <td>
                  <a href="crea_bilancio.php?azienda=<?= urlencode($a['ragione_sociale']) ?>"
                     class="btn btn-sm btn-outline-success">+ Bilancio</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted mb-0">Non hai ancora registrato aziende. <a href="registra_azienda.php">Inizia ora</a>.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- I miei bilanci -->
  <h3 class="mb-3">I miei bilanci</h3>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <?php if (count($miei_bilanci) > 0): ?>
        <table class="table">
          <thead>
            <tr><th>#</th><th>Azienda</th><th>Data</th><th>Stato</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($miei_bilanci as $b): ?>
              <tr>
                <td>#<?= $b['id_bilancio'] ?></td>
                <td><?= htmlspecialchars($b['nome_azienda']) ?></td>
                <td><?= $b['data_creazione'] ?></td>
                <td><span class="badge bg-secondary"><?= $b['stato'] ?></span></td>
                <td><a href="bilancio.php?id=<?= $b['id_bilancio'] ?>" class="btn btn-sm btn-outline-primary">Apri</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted mb-0">Nessun bilancio creato.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>