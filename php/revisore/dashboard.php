<?php
require_once '../includes/auth.php';
require_once '../db.php';

requireRole('revisore');

$me = $_SESSION['username'];

// Stats personali del revisore.
// 1) num_revisioni: dato denormalizzato sulla tabella revisore_esg
$info = $pdo->prepare("SELECT num_revisioni, indice_affidabilita FROM revisore_esg WHERE username = ?");
$info->execute([$me]);
$mio_profilo = $info->fetch();

// 2) bilanci assegnati al revisore con stato e azienda
$stmt = $pdo->prepare("
    SELECT b.id_bilancio, b.data_creazione, b.stato, a.nome AS nome_azienda,
           CASE WHEN g.username_revisore IS NULL THEN 'da_giudicare'
                ELSE 'giudicato' END AS mio_giudizio
    FROM revisione r
    JOIN bilancio b ON b.id_bilancio = r.id_bilancio
    JOIN azienda  a ON a.ragione_sociale = b.ragione_sociale
    LEFT JOIN giudizio g
           ON g.username_revisore = r.username_revisore
          AND g.id_bilancio = r.id_bilancio
    WHERE r.username_revisore = ?
    ORDER BY b.data_creazione DESC
");
$stmt->execute([$me]);
$miei_bilanci = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Revisore — ESG-BALANCE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🔍 Pannello Revisore</a>
    <div>
      <span class="text-light me-3"><?= htmlspecialchars($me) ?></span>
      <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h1 class="mb-4">Benvenuto, <?= htmlspecialchars($me) ?></h1>

  <!-- Profilo revisore -->
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h6 class="text-muted">Revisioni effettuate</h6>
          <p class="display-6 mb-0"><?= $mio_profilo['num_revisioni'] ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h6 class="text-muted">Indice di affidabilità</h6>
          <p class="display-6 mb-0"><?= number_format($mio_profilo['indice_affidabilita'] * 100, 1) ?>%</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabella bilanci assegnati -->
  <h3 class="mb-3">I miei bilanci assegnati</h3>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <?php if (count($miei_bilanci) > 0): ?>
        <table class="table">
          <thead>
            <tr>
              <th>Bilancio</th>
              <th>Azienda</th>
              <th>Stato</th>
              <th>Mio giudizio</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($miei_bilanci as $b): ?>
              <tr>
                <td>#<?= $b['id_bilancio'] ?></td>
                <td><?= htmlspecialchars($b['nome_azienda']) ?></td>
                <td><span class="badge bg-secondary"><?= $b['stato'] ?></span></td>
                <td>
                  <?php if ($b['mio_giudizio'] === 'giudicato'): ?>
                    <span class="badge bg-success">Inviato</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Da inviare</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="bilancio.php?id=<?= $b['id_bilancio'] ?>" class="btn btn-sm btn-outline-primary">Apri</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted mb-0">Non hai ancora bilanci assegnati.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Azioni -->
  <h3 class="mb-3">Azioni</h3>
  <div class="row g-3">
    <div class="col-md-6">
      <a href="dichiara_competenza.php" class="text-decoration-none">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5>🎓 Dichiara competenza</h5>
            <p class="text-muted small mb-0">Aggiungi una competenza con livello (0-5).</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-6">
      <a href="le_mie_competenze.php" class="text-decoration-none">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5>📜 Le mie competenze</h5>
            <p class="text-muted small mb-0">Visualizza le competenze dichiarate.</p>
          </div>
        </div>
      </a>
    </div>
  </div>

  <p class="text-muted small mt-4">
    Per inserire note ed emettere giudizi, apri un singolo bilancio dall'elenco sopra.
  </p>
</div>

</body>
</html>