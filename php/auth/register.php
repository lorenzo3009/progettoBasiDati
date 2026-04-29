<?php
require_once '../includes/auth.php';

if (isLogged()) {
    header('Location: ../../index.php');
    exit;
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

// Conservo i valori inseriti per ripopolare il form in caso di errore.
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Registrazione — ESG-BALANCE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container" style="max-width: 600px; margin-top: 50px;">
  <div class="card shadow-sm">
    <div class="card-body">
      <h3 class="card-title mb-4 text-center">Crea un account</h3>

      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form action="register_process.php" method="POST" enctype="multipart/form-data">

        <div class="mb-3">
          <label class="form-label">Tipo di utente</label>
          <select name="tipo" class="form-select" required onchange="toggleCV(this.value)">
            <option value="">-- Seleziona --</option>
            <option value="revisore"      <?= ($old['tipo'] ?? '')==='revisore'?'selected':'' ?>>Revisore ESG</option>
            <option value="responsabile"  <?= ($old['tipo'] ?? '')==='responsabile'?'selected':'' ?>>Responsabile aziendale</option>
          </select>
          <!-- Note: l'amministratore non si registra dal form pubblico,
               viene creato manualmente nel DB dal team della piattaforma. -->
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" maxlength="50" class="form-control"
                   value="<?= htmlspecialchars($old['username'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" minlength="4" class="form-control" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Codice fiscale</label>
          <input type="text" name="codice_fiscale" maxlength="16" minlength="16"
                 pattern="[A-Za-z0-9]{16}" class="form-control"
                 style="text-transform:uppercase;"
                 value="<?= htmlspecialchars($old['codice_fiscale'] ?? '') ?>" required>
          <small class="text-muted">16 caratteri, lettere maiuscole e numeri.</small>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Data di nascita</label>
            <input type="date" name="data_nascita" class="form-control"
                   value="<?= htmlspecialchars($old['data_nascita'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Luogo di nascita</label>
            <input type="text" name="luogo_nascita" maxlength="100" class="form-control"
                   value="<?= htmlspecialchars($old['luogo_nascita'] ?? '') ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" maxlength="150" class="form-control"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>

        <!-- Campo cv_pdf, mostrato solo se ho selezionato "responsabile" -->
        <div class="mb-3" id="cv-field" style="display:none;">
          <label class="form-label">CV (PDF)</label>
          <input type="file" name="cv_pdf" accept="application/pdf" class="form-control">
          <small class="text-muted">Opzionale, max 2MB.</small>
        </div>

        <button type="submit" class="btn btn-success w-100">Registrati</button>
      </form>

      <p class="text-center mt-3 mb-0">
        Hai gia' un account? <a href="login.php">Accedi</a>
      </p>
    </div>
  </div>
</div>

<script>
// Mostra/nasconde il campo CV in base al tipo selezionato.
function toggleCV(value) {
    document.getElementById('cv-field').style.display =
        (value === 'responsabile') ? 'block' : 'none';
}
// Eseguito al caricamento per ripristinare lo stato dopo un errore di validazione.
toggleCV(document.querySelector('select[name="tipo"]').value);
</script>

</body>
</html>