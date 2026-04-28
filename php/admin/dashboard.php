<?php
require_once '../includes/auth.php';
require_once '../db.php';

// Solo amministratori. requireRole include gia' requireLogin.
requireRole('amministratore');

// Statistiche utili all'admin: lo stato del template e dei bilanci.
$num_voci         = $pdo->query("SELECT COUNT(*) FROM voce_contabile")->fetchColumn();
$num_indicatori   = $pdo->query("SELECT COUNT(*) FROM indicatore_esg")->fetchColumn();
$num_bozze        = $pdo->query("SELECT COUNT(*) FROM bilancio WHERE stato = 'bozza'")->fetchColumn();
$num_in_revisione = $pdo->query("SELECT COUNT(*) FROM bilancio WHERE stato = 'in_revisione'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin — ESG-BALANCE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">⚙️ Pannello Amministratore</a>
    <div>
      <span class="text-light me-3"><?= htmlspecialchars($_SESSION['username']) ?></span>
      <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="mb-4">Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?></h1>

  <!-- Riga di statistiche rapide -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h6 class="text-muted">Voci contabili</h6>
          <p class="display-6 mb-0"><?= $num_voci ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h6 class="text-muted">Indicatori ESG</h6>
          <p class="display-6 mb-0"><?= $num_indicatori ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h6 class="text-muted">Bilanci in bozza</h6>
          <p class="display-6 mb-0 text-warning"><?= $num_bozze ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h6 class="text-muted">In revisione</h6>
          <p class="display-6 mb-0 text-info"><?= $num_in_revisione ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Azioni disponibili -->
  <h3 class="mb-3">Azioni</h3>
  <div class="row g-3">
    <div class="col-md-4">
      <a href="inserisci_voce.php" class="text-decoration-none">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5>📋 Aggiungi voce contabile</h5>
            <p class="text-muted small mb-0">Estendi il template globale.</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="inserisci_indicatore.php" class="text-decoration-none">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5>🌱 Aggiungi indicatore ESG</h5>
            <p class="text-muted small mb-0">Ambientale, sociale o generico.</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="assegna_revisore.php" class="text-decoration-none">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5>👤 Assegna revisore</h5>
            <p class="text-muted small mb-0">Assegna un revisore ESG a un bilancio.</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</div>

</body>
</html>