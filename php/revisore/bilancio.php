<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('revisore');

$me          = $_SESSION['username'];
$id_bilancio = (int)($_GET['id'] ?? 0);

if ($id_bilancio <= 0) {
    die('ID bilancio non valido.');
}

// =====================================================================
// AUTORIZZAZIONE: il revisore puo' aprire SOLO i bilanci a lui assegnati.
// Se non c'e' una riga in 'revisione', accesso negato.
// =====================================================================
$check = $pdo->prepare("SELECT 1 FROM revisione WHERE username_revisore = ? AND id_bilancio = ?");
$check->execute([$me, $id_bilancio]);
if (!$check->fetch()) {
    die('Non sei assegnato a questo bilancio.');
}

// Dettagli bilancio + azienda
$stmt = $pdo->prepare("
    SELECT b.*, a.nome AS nome_azienda, a.ragione_sociale
    FROM bilancio b
    JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
    WHERE b.id_bilancio = ?
");
$stmt->execute([$id_bilancio]);
$bilancio = $stmt->fetch();

// Valori contabili
$stmt = $pdo->prepare("
    SELECT vb.nome_voce, vb.valore, vc.descrizione
    FROM valore_bilancio vb
    JOIN voce_contabile vc ON vc.nome = vb.nome_voce
    WHERE vb.id_bilancio = ?
    ORDER BY vb.nome_voce
");
$stmt->execute([$id_bilancio]);
$valori = $stmt->fetchAll();

// Indicatori ESG
$stmt = $pdo->prepare("
    SELECT vi.nome_voce, vi.nome_indicatore, vi.valore, vi.fonte, vi.data_rilevazione
    FROM voce_indicatore vi
    WHERE vi.id_bilancio = ?
    ORDER BY vi.nome_voce, vi.nome_indicatore
");
$stmt->execute([$id_bilancio]);
$indicatori = $stmt->fetchAll();

// Note di tutti i revisori su questo bilancio
$stmt = $pdo->prepare("
    SELECT username_revisore, nome_voce, data, testo
    FROM nota
    WHERE id_bilancio = ?
    ORDER BY data DESC
");
$stmt->execute([$id_bilancio]);
$note = $stmt->fetchAll();

// Mio giudizio (se gia' emesso)
$stmt = $pdo->prepare("SELECT * FROM giudizio WHERE id_bilancio = ? AND username_revisore = ?");
$stmt->execute([$id_bilancio, $me]);
$mio_giudizio = $stmt->fetch();
// Tutti i giudizi sul bilancio (di tutti i revisori).
$stmt = $pdo->prepare("
    SELECT username_revisore, esito, data_giudizio, rilievi
    FROM giudizio
    WHERE id_bilancio = ?
    ORDER BY data_giudizio
");
$stmt->execute([$id_bilancio]);
$tutti_giudizi = $stmt->fetchAll();

// Voci disponibili per aggiungere una nota (solo quelle con valore_bilancio)
$voci_per_nota = array_column($valori, 'nome_voce');

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
<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">🔍 Pannello Revisore</a>
    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container">
  <a href="dashboard.php" class="text-decoration-none">← Dashboard</a>

  <h1 class="mt-2">Bilancio #<?= $bilancio['id_bilancio'] ?></h1>
  <p class="text-muted">
    <strong><?= htmlspecialchars($bilancio['nome_azienda']) ?></strong>
    — <?= htmlspecialchars($bilancio['ragione_sociale']) ?>
    | Data: <?= $bilancio['data_creazione'] ?>
    | Stato: <span class="badge bg-secondary"><?= $bilancio['stato'] ?></span>
  </p>

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
        <p class="text-muted mb-0">Nessun valore inserito.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- INDICATORI ESG -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h4>Indicatori ESG</h4>
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
        <p class="text-muted mb-0">Nessun indicatore collegato.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- NOTE GIA' INSERITE -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h4>Note dei revisori</h4>
      <?php if (count($note) > 0): ?>
        <ul class="list-unstyled">
          <?php foreach ($note as $n): ?>
            <li class="mb-2 pb-2 border-bottom">
              <small class="text-muted">
                <?= htmlspecialchars($n['username_revisore']) ?> · <?= $n['data'] ?>
                · voce <strong><?= htmlspecialchars($n['nome_voce']) ?></strong>
              </small>
              <p class="mb-0"><?= htmlspecialchars($n['testo']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted">Nessuna nota.</p>
      <?php endif; ?>

      <!-- FORM AGGIUNGI NOTA -->
      <hr>
      <h5>Aggiungi una nota</h5>
      <?php if (count($voci_per_nota) > 0): ?>
        <form action="inserisci_nota_process.php" method="POST">
          <input type="hidden" name="id_bilancio" value="<?= $id_bilancio ?>">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Voce</label>
              <select name="nome_voce" class="form-select" required>
                <option value="">-- Seleziona --</option>
                <?php foreach ($voci_per_nota as $v): ?>
                  <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label">Testo della nota</label>
              <input type="text" name="testo" maxlength="500" class="form-control" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Aggiungi nota</button>
        </form>
      <?php else: ?>
        <p class="text-muted">Nessuna voce disponibile per aggiungere note.</p>
      <?php endif; ?>
    </div>
  </div>

    <!-- GIUDIZIO -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h4>Giudizio</h4>

      <!-- Stato complessivo del bilancio (calcolato dal trigger T2) -->
      <p>
        Stato complessivo del bilancio:
        <span class="badge
          <?= $bilancio['stato']==='approvato' ? 'bg-success' :
              ($bilancio['stato']==='respinto' ? 'bg-danger' : 'bg-secondary') ?>">
          <?= $bilancio['stato'] ?>
        </span>
      </p>

      <!-- Giudizi di TUTTI i revisori -->
      <?php if (count($tutti_giudizi) > 0): ?>
        <h6>Giudizi emessi</h6>
        <ul class="list-unstyled mb-3">
          <?php foreach ($tutti_giudizi as $g): ?>
            <li class="mb-2">
              <strong><?= htmlspecialchars($g['username_revisore']) ?></strong>:
              <span class="badge
                <?= $g['esito']==='approvazione' ? 'bg-success' :
                    ($g['esito']==='respingimento' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                <?= $g['esito'] ?>
              </span>
              <small class="text-muted"><?= $g['data_giudizio'] ?></small>
              <?php if ($g['rilievi']): ?>
                <p class="mb-0 ms-3 small"><em><?= htmlspecialchars($g['rilievi']) ?></em></p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    
      <?php if (empty($valori)): ?>
        <div class="alert alert-warning">
         ⚠️ Questo bilancio non ha ancora dati inseriti dal responsabile.
         Non è possibile emettere un giudizio finché non ci sono valori da revisionare.
        </div>
      <?php endif; ?>

      <?php if (empty($valori)): ?>
        <p class="text-muted">Form non disponibile: il bilancio è ancora vuoto.</p>
        <!-- Form solo se IO non ho ancora giudicato -->
      <?php elseif ($mio_giudizio): ?>
        <div class="alert alert-info mb-0">
          Hai già emesso il tuo giudizio: <strong><?= $mio_giudizio['esito'] ?></strong>.
        </div>
      <?php else: ?>
        <hr>
        <h6>Emetti il tuo giudizio</h6>
        <form action="emetti_giudizio_process.php" method="POST">
          <input type="hidden" name="id_bilancio" value="<?= $id_bilancio ?>">
          <div class="mb-3">
            <label class="form-label">Esito</label>
            <select name="esito" class="form-select" required>
              <option value="">-- Seleziona --</option>
              <option value="approvazione">Approvazione (senza rilievi)</option>
              <option value="approvazione_con_rilievi">Approvazione con rilievi</option>
              <option value="respingimento">Respingimento</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Rilievi (opzionale)</label>
            <textarea name="rilievi" rows="3" class="form-control" maxlength="1000"></textarea>
          </div>
          <button type="submit" class="btn btn-success">Emetti giudizio</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>