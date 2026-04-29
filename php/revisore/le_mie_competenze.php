<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('revisore');

$me = $_SESSION['username'];

$stmt = $pdo->prepare("
    SELECT nome_competenza, livello
    FROM possesso_competenza
    WHERE username = ?
    ORDER BY livello DESC, nome_competenza
");
$stmt->execute([$me]);
$competenze = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Le mie competenze</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🔍 Pannello Revisore</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container" style="max-width: 700px;">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2 mb-4">Le mie competenze</h1>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (count($competenze) > 0): ?>
        <table class="table">
          <thead><tr><th>Competenza</th><th>Livello</th></tr></thead>
          <tbody>
            <?php foreach ($competenze as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['nome_competenza']) ?></td>
                <td>
                  <!-- Stelline visive in base al livello (0-5) -->
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?= ($i <= $c['livello']) ? '★' : '☆' ?>
                  <?php endfor; ?>
                  <span class="text-muted ms-2">(<?= $c['livello'] ?>/5)</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted mb-0">Non hai ancora dichiarato competenze.</p>
      <?php endif; ?>
      <a href="dichiara_competenza.php" class="btn btn-primary">+ Dichiara nuova</a>
    </div>
  </div>
</div>
</body>
</html>