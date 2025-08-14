<?php
require_once '../../includes/functions.php';
require_once '../../includes/qrlib.php';
configureSession();

$pdo = connectDB();

// Récupérer toutes les salles
$salles = $pdo->query("SELECT * FROM salles")->fetchAll();

// Génération du QR code pour cette page
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
// On reconstruit l'URL propre (sans paramètres POST éventuels)
$url = $protocol . '://' . $host . $uri; // Lien vers la page de sélection

// Chemin temporaire pour le QR code
$qrTempDir = sys_get_temp_dir();
$qrFile = $qrTempDir . '/qrcode_salle_selection.png';

// Générer le QR code (il sera écrasé à chaque affichage)
QRcode::png($url, $qrFile, 'L', 5, 2);

afficherHeader('Sélectionner une salle', 2);
?>
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="container">
    <h1 class="mb-4">Sélectionner une salle</h1>
    <form id="salleForm" method="get" action="create_tik_salle.php" class="mb-4">
        <div class="input-group">
            <select name="id_salle" id="id_salle" class="form-select" required style="width: 100%">
                <option value="">-- Choisir une salle --</option>
                <?php foreach ($salles as $salle): ?>
                    <option value="<?= htmlspecialchars($salle['id_salle']) ?>">
                        <?= htmlspecialchars($salle['nom_salle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Créer un ticket</button>
        </div>
    </form>
</div>
<!-- jQuery (nécessaire pour Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#id_salle').select2({
        placeholder: '-- Choisir une salle --',
        allowClear: true,
        width: 'resolve'
    });
});
</script>
<?php
afficherFooter();
?>
