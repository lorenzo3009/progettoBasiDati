<?php
// =====================================================================
// index.php :: homepage pubblica con le 4 statistiche
// =====================================================================
require_once 'php/db.php';
require_once 'php/includes/auth.php';

// Eseguo le 4 viste e raccolgo i risultati.
// Le viste sono gia' "query salvate" nel DB: dal PHP basta SELECT * FROM.
$num_aziende       = $pdo->query("SELECT num_aziende FROM v_num_aziende")->fetchColumn();
$num_revisori      = $pdo->query("SELECT num_revisori FROM v_num_revisori")->fetchColumn();
$azienda_top       = $pdo->query("SELECT * FROM v_azienda_piu_affidabile")->fetchAll();
$classifica_bilanci= $pdo->query("SELECT * FROM v_classifica_bilanci_esg LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>ESG-BALANCE</title>
    <!-- Bootstrap via CDN (bonus lode dal regolamento) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">🌱 ESG-BALANCE</a>
    <div>
      <?php if (isLogged()): ?>
        <span class="text-white me-3">Ciao, <?= htmlspecialchars($_SESSION['username']) ?></span>

        <?php
          // Calcolo l'URL della dashboard in base al ruolo dell'utente.
          // Centralizzo qui la logica cosi non la duplico ogni volta.
          $dash_url = match ($_SESSION['tipo']) {
              'amministratore' => 'php/admin/dashboard.php',
              'revisore'       => 'php/revisore/dashboard.php',
              'responsabile'   => 'php/responsabile/dashboard.php',
              default          => 'index.php',
          };
        ?>
        <a href="<?= $dash_url ?>" class="btn btn-light btn-sm me-2">La mia dashboard</a>
        <a href="php/auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>

      <?php else: ?>
        <a href="php/auth/login.php" class="btn btn-light btn-sm">Login</a>
        <a href="php/auth/register.php" class="btn btn-outline-light btn-sm ms-2">Registrati</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="mb-4">Statistiche piattaforma</h1>

  <!-- V1 e V2: numeri secchi -->
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Aziende registrate</h5>
          <p class="display-4 text-success"><?= $num_aziende ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Revisori ESG</h5>
          <p class="display-4 text-primary"><?= $num_revisori ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- V3: azienda piu affidabile -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title">🏆 Azienda più affidabile</h5>
      <?php if (count($azienda_top) > 0): ?>
        <?php foreach ($azienda_top as $a): ?>
          <p class="mb-1">
            <strong><?= htmlspecialchars($a['nome']) ?></strong>
            (<?= htmlspecialchars($a['ragione_sociale']) ?>)
            — affidabilità <span class="badge bg-success"><?= $a['percentuale_affidabilita'] ?>%</span>
          </p>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-muted">Nessun dato disponibile.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- V4: classifica bilanci -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="card-title">📊 Top 5 bilanci per indicatori ESG</h5>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Bilancio</th>
            <th>Azienda</th>
            <th>Stato</th>
            <th>Indicatori ESG</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($classifica_bilanci as $b): ?>
            <tr>
              <td>#<?= $b['id_bilancio'] ?></td>
              <td><?= htmlspecialchars($b['nome_azienda']) ?></td>
              <td><span class="badge bg-secondary"><?= $b['stato'] ?></span></td>
              <td><strong><?= $b['num_indicatori'] ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>