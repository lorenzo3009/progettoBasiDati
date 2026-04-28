<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('amministratore');

$error   = $_SESSION['error']   ?? null;  unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;  unset($_SESSION['success']);
$old     = $_SESSION['old_input'] ?? [];  unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Aggiungi voce contabile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">⚙️ Pannello Amministratore</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container" style="max-width: 600px;">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2 mb-4">Aggiungi voce contabile</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="inserisci_voce_process.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Nome voce</label>
          <input type="text" name="nome" maxlength="100" class="form-control"
                 value="<?= htmlspecialchars($old['nome'] ?? '') ?>" required>
          <small class="text-muted">Esempi: "Ricavi", "Costo_Personale". Univoco a livello di template.</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Descrizione</label>
          <textarea name="descrizione" rows="3" class="form-control" required><?= htmlspecialchars($old['descrizione'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">Aggiungi voce</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>