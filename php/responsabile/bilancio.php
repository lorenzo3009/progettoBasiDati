<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('responsabile');

$me          = $_SESSION['username'];
$id_bilancio = (int)($_GET['id'] ?? 0);

if ($id_bilancio <= 0) {
    die('ID bilancio non valido.');
}

// =====================================================================
// AUTORIZZAZIONE: il responsabile vede SOLO i bilanci delle sue aziende
// =====================================================================
$stmt = $pdo->prepare("
    SELECT b.*, a.nome AS nome_azienda
    FROM bilancio b
    JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
    WHERE b.id_bilancio = ?
      AND a.username_responsabile = ?
");
$stmt->execute([$id_bilancio, $me]);
$bilancio = $stmt->fetch();

if (!$bilancio) {
    die('Bilancio non trovato o non sei il responsabile.');
}

// Stato bozza? Controlla per nascondere i form di modifica
$is_bozza = ($bilancio['stato'] === 'bozza');

// Voci contabili del template (per il dropdown del form valore)
$voci_template = $pdo->query("SELECT nome, descrizione FROM voce_contabile ORDER BY nome")->fetchAll();

// Valori già inseriti per questo bilancio
$stmt = $pdo->prepare("
    SELECT vb.nome_voce, vb.valore, vc.descrizione
    FROM valore_bilancio vb
    JOIN voce_contabile vc ON vc.nome = vb.nome_voce
    WHERE vb.id_bilancio = ?
    ORDER BY vb.nome_voce
");
$stmt->execute([$id_bilancio]);
$valori = $stmt->fetchAll();

// Indicatori già collegati
$stmt = $pdo->prepare("
    SELECT vi.* FROM voce_indicatore vi
    WHERE vi.id_bilancio = ?
    ORDER BY vi.nome_voce, vi.nome_indicatore
");
$stmt->execute([$id_bilancio]);
$indicatori = $stmt->fetchAll();

// Indicatori ESG del template (per il dropdown del form indicatore)
$indicatori_template = $pdo->query("SELECT nome, rilevanza FROM indicatore_esg ORDER BY nome")->fetchAll();

// Voci già valorizzate (per il dropdown del form indicatore: solo voci con valore)
$voci_valorizzate = array_column($valori, 'nome_voce');

$error   = $_SESSION['error']   ?? null;  unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;  unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Bilancio #<?= $id_bilancio ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🏢 Pannello Responsabile</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>
  <h1 class="mt-2">Bilancio #<?= $bilancio['id_bilancio'] ?></h1>
  <p class="text-muted">
    <strong><?= htmlspecialchars($bilancio['nome_azienda']) ?></strong>
    | Data: <?= $bilancio['data_creazione'] ?>
    | Stato:
    <span class="badge <?= $is_bozza ? 'bg-warning text-dark' : 'bg-secondary' ?>">
      <?= $bilancio['stato'] ?>
    </span>
  </p>

  <?php if (!$is_bozza): ?>
    <div class="alert alert-info">
      ℹ️ Questo bilancio non è più in bozza. Non puoi più aggiungere o modificare valori e indicatori.
    </div>
  <?php endif; ?>

  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <!-- VALORI CONTABILI -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h4>Valori contabili</h4>
      <?php if (count($valori) > 0): ?>
        <table class="table">
          <thead><tr><th>Voce</th><th>Descrizione</th><th class="text-end">Valore (€)</th></tr></thead>
          <tbody>
            <?php foreach ($valori as $v): ?>
              <tr>
                <td><strong><?= htmlspecialchars($v['nome_voce']) ?></strong></td>
                <td class="text-muted small"><?= htmlspecialchars($v['descrizione']) ?></td>
                <td class="text-end"><?= number_format($v['valore'], 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted">Nessun valore inserito.</p>
      <?php endif; ?>

      <?php if ($is_bozza): ?>
        <hr>
        <h6>Aggiungi/modifica un valore</h6>
        <form action="inserisci_valore_voce_process.php" method="POST">
          <input type="hidden" name="id_bilancio" value="<?= $id_bilancio ?>">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Voce contabile</label>
              <select name="nome_voce" class="form-select" required>
                <option value="">-- Seleziona --</option>
                <?php foreach ($voci_template as $vt): ?>
                  <option value="<?= htmlspecialchars($vt['nome']) ?>"><?= htmlspecialchars($vt['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Valore (€)</label>
              <input type="number" name="valore" step="0.01" class="form-control" required>
            </div>
          </div>
          <button type="submit" class="btn btn-success">Salva valore</button>
          <small class="text-muted ms-2">Se la voce esiste già, il valore verrà aggiornato.</small>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- INDICATORI ESG -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h4>Indicatori ESG collegati</h4>
      <?php if (count($indicatori) > 0): ?>
        <table class="table">
          <thead><tr><th>Voce</th><th>Indicatore</th><th>Valore</th><th>Fonte</th><th>Data</th></tr></thead>
          <tbody>
            <?php foreach ($indicatori as $i): ?>
              <tr>
                <td><?= htmlspecialchars($i['nome_voce']) ?></td>
                <td><?= htmlspecialchars($i['nome_indicatore']) ?></td>
                <td><?= htmlspecialchars($i['valore']) ?></td>
                <td><?= htmlspecialchars($i['fonte']) ?></td>
                <td><?= $i['data_rilevazione'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted">Nessun indicatore collegato.</p>
      <?php endif; ?>

      <?php if ($is_bozza): ?>
        <hr>
        <h6>Collega un indicatore ESG a una voce</h6>
        <?php if (empty($voci_valorizzate)): ?>
          <p class="text-muted small">Aggiungi prima almeno un valore contabile.</p>
        <?php else: ?>
          <form action="inserisci_voce_indicatore_process.php" method="POST">
            <input type="hidden" name="id_bilancio" value="<?= $id_bilancio ?>">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Voce contabile</label>
                <select name="nome_voce" class="form-select" required>
                  <option value="">-- Seleziona --</option>
                  <?php foreach ($voci_valorizzate as $v): ?>
                    <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Indicatore ESG</label>
                <select name="nome_indicatore" class="form-select" required>
                  <option value="">-- Seleziona --</option>
                  <?php foreach ($indicatori_template as $it): ?>
                    <option value="<?= htmlspecialchars($it['nome']) ?>">
                      <?= htmlspecialchars($it['nome']) ?> (rilevanza <?= $it['rilevanza'] ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Valore misurato</label>
                <input type="number" name="valore" step="0.01" class="form-control" required>
              </div>
              <div class="col-md-5 mb-3">
                <label class="form-label">Fonte</label>
                <input type="text" name="fonte" maxlength="255" class="form-control" required>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Data rilevazione</label>
                <input type="date" name="data_rilevazione" class="form-control" required>
              </div>
            </div>
            <button type="submit" class="btn btn-success">Collega indicatore</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>