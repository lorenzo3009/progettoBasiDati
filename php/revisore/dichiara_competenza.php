<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('revisore');

$me = $_SESSION['username'];
$error   = $_SESSION['error']   ?? null;  unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;  unset($_SESSION['success']);

// Carico le competenze gia' presenti nel sistema per fare un autocomplete.
$comp_esistenti = $pdo->query("SELECT nome FROM competenza ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dichiara competenza</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🔍 Pannello Revisore</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container" style="max-width: 600px;">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2 mb-4">Dichiara una competenza</h1>

  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="dichiara_competenza_process.php" method="POST">

        <div class="mb-3">
          <label class="form-label">Nome competenza</label>
          <!-- list="..." collega l'input al <datalist> sotto: l'utente
               vede un'autocomplete con le competenze esistenti,
               ma puo' anche scriverne una nuova. -->
          <input type="text" name="nome_competenza" maxlength="100"
                 list="comp-list" class="form-control" required>
          <datalist id="comp-list">
            <?php foreach ($comp_esistenti as $c): ?>
              <option value="<?= htmlspecialchars($c['nome']) ?>">
            <?php endforeach; ?>
          </datalist>
          <small class="text-muted">Scegli da quelle esistenti o inseriscine una nuova.</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Livello (0-5)</label>
          <input type="number" name="livello" min="0" max="5" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Dichiara competenza</button>
        <a href="le_mie_competenze.php" class="btn btn-link">Le mie competenze →</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>