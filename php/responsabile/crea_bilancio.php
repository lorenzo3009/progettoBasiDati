<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('responsabile');

$me            = $_SESSION['username'];
$rag_soc_pre   = $_GET['azienda'] ?? '';

// Carico le aziende del responsabile per il dropdown
$stmt = $pdo->prepare("SELECT ragione_sociale, nome FROM azienda WHERE username_responsabile = ? ORDER BY nome");
$stmt->execute([$me]);
$mie_aziende = $stmt->fetchAll();

$error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Crea bilancio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🏢 Pannello Responsabile</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container" style="max-width: 600px;">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2 mb-4">Crea un nuovo bilancio</h1>

  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (empty($mie_aziende)): ?>
        <p class="text-muted">Non hai ancora aziende registrate.
          <a href="registra_azienda.php">Registra prima un'azienda</a>.
        </p>
      <?php else: ?>
        <form action="crea_bilancio_process.php" method="POST">
          <div class="mb-3">
            <label class="form-label">Per quale azienda?</label>
            <select name="ragione_sociale" class="form-select" required>
              <option value="">-- Seleziona --</option>
              <?php foreach ($mie_aziende as $a): ?>
                <option value="<?= htmlspecialchars($a['ragione_sociale']) ?>"
                  <?= ($rag_soc_pre === $a['ragione_sociale']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['nome']) ?> (<?= htmlspecialchars($a['ragione_sociale']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <p class="text-muted small">
            Il bilancio sara' creato in stato <code>bozza</code> con la data di oggi.
            Potrai aggiungere voci e indicatori dopo.
          </p>
          <button type="submit" class="btn btn-success">Crea bilancio</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>