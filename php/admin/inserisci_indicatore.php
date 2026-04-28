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
  <title>Aggiungi indicatore ESG</title>
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
  <h1 class="mt-2 mb-4">Aggiungi indicatore ESG</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="inserisci_indicatore_process.php" method="POST">

        <!-- Campi comuni -->
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" maxlength="100" class="form-control"
                   value="<?= htmlspecialchars($old['nome'] ?? '') ?>" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Rilevanza (0-10)</label>
            <input type="number" name="rilevanza" min="0" max="10" class="form-control"
                   value="<?= htmlspecialchars($old['rilevanza'] ?? '') ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Immagine (URL)</label>
          <input type="text" name="immagine" maxlength="255" class="form-control"
                 placeholder="es. co2.png"
                 value="<?= htmlspecialchars($old['immagine'] ?? '') ?>">
          <small class="text-muted">Opzionale.</small>
        </div>

        <!-- Categoria: pilota i campi condizionali sotto -->
        <div class="mb-3">
          <label class="form-label">Categoria</label>
          <select name="categoria" class="form-select" required onchange="aggiornaCampi(this.value)">
            <option value="">-- Seleziona --</option>
            <option value="ambientale" <?= ($old['categoria'] ?? '')==='ambientale'?'selected':'' ?>>Ambientale</option>
            <option value="sociale"    <?= ($old['categoria'] ?? '')==='sociale'   ?'selected':'' ?>>Sociale</option>
            <option value="nessuna"    <?= ($old['categoria'] ?? '')==='nessuna'   ?'selected':'' ?>>Nessuna (generico)</option>
          </select>
        </div>

        <!-- Campi specifici Ambientale -->
        <div id="campi-ambientale" style="display:none;">
          <div class="mb-3">
            <label class="form-label">Codice normativa</label>
            <input type="text" name="codice_normativa" maxlength="50" class="form-control"
                   placeholder="es. GHG-Protocol-Scope1"
                   value="<?= htmlspecialchars($old['codice_normativa'] ?? '') ?>">
          </div>
        </div>

        <!-- Campi specifici Sociale -->
        <div id="campi-sociale" style="display:none;">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Ambito</label>
              <input type="text" name="ambito" maxlength="100" class="form-control"
                     placeholder="es. Risorse Umane"
                     value="<?= htmlspecialchars($old['ambito'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Frequenza</label>
              <input type="text" name="frequenza" maxlength="50" class="form-control"
                     placeholder="es. Annuale"
                     value="<?= htmlspecialchars($old['frequenza'] ?? '') ?>">
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-success">Aggiungi indicatore</button>
      </form>
    </div>
  </div>
</div>

<script>
// Mostra/nasconde i campi ISA in base alla categoria scelta.
function aggiornaCampi(cat) {
    document.getElementById('campi-ambientale').style.display = (cat === 'ambientale') ? 'block' : 'none';
    document.getElementById('campi-sociale').style.display    = (cat === 'sociale')    ? 'block' : 'none';
}
// Ripristina lo stato dopo un eventuale errore di validazione.
aggiornaCampi(document.querySelector('select[name="categoria"]').value);
</script>
</body>
</html>