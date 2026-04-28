<?php
require_once '../includes/auth.php';

// Se sono gia' loggato, non ha senso vedere il form: vado alla mia dashboard.
if (isLogged()) {
    header('Location: ../../index.php');
    exit;
}

// Recupero un eventuale messaggio di errore lasciato da login_process
// (sistema "flash message": lo leggo e lo cancello subito).
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Login — ESG-BALANCE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container" style="max-width: 420px; margin-top: 80px;">
  <div class="card shadow-sm">
    <div class="card-body">
      <h3 class="card-title mb-4 text-center">🌱 Accedi</h3>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- action: dove va la POST. method: POST per inviare credenziali. -->
      <form action="login_process.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Accedi</button>
      </form>

      <p class="text-center mt-3 mb-0">
        Non hai un account? <a href="register.php">Registrati</a>
      </p>
      <p class="text-center mt-2">
        <a href="../../index.php" class="text-muted small">← Torna alla home</a>
      </p>
    </div>
  </div>
</div>

</body>
</html>