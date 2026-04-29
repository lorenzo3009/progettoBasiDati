<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('responsabile');

$error   = $_SESSION['error']   ?? null;  unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;  unset($_SESSION['success']);
$old     = $_SESSION['old_input'] ?? [];  unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Registra azienda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🏢 Pannello Responsabile</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container" style="max-width: 700px;">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2 mb-4">Registra una nuova azienda</h1>

  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="registra_azienda_process.php" method="POST">

        <div class="mb-3">
          <label class="form-label">Ragione sociale</label>
          <input type="text" name="ragione_sociale" maxlength="100" class="form-control"
                 value="<?= htmlspecialchars($old['ragione_sociale'] ?? '') ?>" required>
          <small class="text-muted">Identificativo unico (es. "EcoTech S.r.l.").</small>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Nome commerciale</label>
            <input type="text" name="nome" maxlength="100" class="form-control"
                   value="<?= htmlspecialchars($old['nome'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Partita IVA (11 cifre)</label>
            <input type="text" name="partita_iva" maxlength="11" minlength="11"
                   pattern="[0-9]{11}" class="form-control"
                   value="<?= htmlspecialchars($old['partita_iva'] ?? '') ?>" required>
          </div>
        </div>

        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Settore</label>
            <input type="text" name="settore" maxlength="80" class="form-control"
                   value="<?= htmlspecialchars($old['settore'] ?? '') ?>" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Numero dipendenti</label>
            <input type="number" name="num_dipendenti" min="0" class="form-control"
                   value="<?= htmlspecialchars($old['num_dipendenti'] ?? '') ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Logo (URL o nome file)</label>
          <input type="text" name="logo" maxlength="255" class="form-control"
                 placeholder="es. ecotech.png"
                 value="<?= htmlspecialchars($old['logo'] ?? '') ?>">
          <small class="text-muted">Opzionale.</small>
        </div>

        <button type="submit" class="btn btn-success">Registra azienda</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>