<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('amministratore');

$error   = $_SESSION['error']   ?? null;  unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;  unset($_SESSION['success']);

// Pre-seleziono ?bilancio=N nell'URL se proviene da una pagina dettaglio
$bilancio_preselect = $_GET['bilancio'] ?? '';

// Carico i bilanci assegnabili: solo bozza o in_revisione
// (non ha senso assegnare revisori a bilanci gia' approvati/respinti).
$bilanci = $pdo->query("
    SELECT b.id_bilancio, b.data_creazione, b.stato, a.nome AS nome_azienda
    FROM bilancio b
    JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
    WHERE b.stato IN ('bozza', 'in_revisione')
    ORDER BY b.data_creazione DESC
")->fetchAll();

// Carico i revisori (con num_revisioni per aiutare l'admin a bilanciare il carico).
$revisori = $pdo->query("
    SELECT username, num_revisioni, indice_affidabilita
    FROM revisore_esg
    ORDER BY num_revisioni ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Assegna revisore</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">⚙️ Pannello Amministratore</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container" style="max-width: 700px;">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2 mb-4">Assegna revisore a bilancio</h1>

  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (empty($bilanci) || empty($revisori)): ?>
        <p class="text-muted">Nessun bilancio assegnabile o nessun revisore registrato.</p>
      <?php else: ?>
        <form action="assegna_revisore_process.php" method="POST">
          <div class="mb-3">
            <label class="form-label">Bilancio</label>
            <select name="id_bilancio" class="form-select" required>
              <option value="">-- Seleziona --</option>
              <?php foreach ($bilanci as $b): ?>
                <option value="<?= $b['id_bilancio'] ?>" <?= ($bilancio_preselect == $b['id_bilancio']) ? 'selected' : '' ?>>
                  #<?= $b['id_bilancio'] ?> — <?= htmlspecialchars($b['nome_azienda']) ?>
                  (<?= $b['data_creazione'] ?>, <?= $b['stato'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Revisore</label>
            <select name="username_revisore" class="form-select" required>
              <option value="">-- Seleziona --</option>
              <?php foreach ($revisori as $r): ?>
                <option value="<?= htmlspecialchars($r['username']) ?>">
                  <?= htmlspecialchars($r['username']) ?>
                  (revisioni: <?= $r['num_revisioni'] ?>,
                   affidabilità: <?= number_format($r['indice_affidabilita']*100, 0) ?>%)
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">I revisori sono ordinati per carico crescente.</small>
          </div>

          <button type="submit" class="btn btn-success">Assegna</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>