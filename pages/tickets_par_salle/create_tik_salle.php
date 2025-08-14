<?php
require_once '../../includes/functions.php';
require_once '../../includes/qrlib.php';
configureSession();

$pdo = connectDB();

$id_salle = $_GET['id_salle'] ?? $_POST['id_salle'] ?? '';

// 1. Récupérer l'id de la salle depuis l'URL
$id_salle = isset($_GET['id_salle']) ? intval($_GET['id_salle']) : null;
if (!$id_salle) {
    die("Salle non spécifiée.");
}

// 2. Charger les infos de la salle
$stmt = $pdo->prepare("SELECT * FROM salles WHERE id_salle = ?");
$stmt->execute([$id_salle]);
$salle = $stmt->fetch();
if (!$salle) {
    die("Salle introuvable.");
}

// Génération du QR code pour cette page

// URL absolue de la page de création de ticket pour cette salle
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
// On reconstruit l'URL propre (sans paramètres POST éventuels)
$url = $protocol . '://' . $host . dirname($uri) . '/create_tik_salle.php?id_salle=' . $id_salle;

// Chemin temporaire pour le QR code
$qrTempDir = sys_get_temp_dir();
$qrFile = $qrTempDir . '/qrcode_salle_' . $id_salle . '.png';

// Générer le QR code (il sera écrasé à chaque affichage)
QRcode::png($url, $qrFile, 'L', 5, 2);

// 3. Charger les postes de la salle
$stmt = $pdo->prepare("SELECT * FROM poste WHERE id_salle = ?");
$stmt->execute([$id_salle]);
$postes = $stmt->fetchAll();

// 4. Charger les appareils de la salle
$stmt = $pdo->prepare("SELECT * FROM appareils WHERE id_salle = ?");
$stmt->execute([$id_salle]);
$appareils = $stmt->fetchAll();

// 5. Si un poste est sélectionné, charger ses périphériques et unités
$selectedPoste = $_POST['id_poste'] ?? null;
$peripheriques = [];
$unites = [];
if ($selectedPoste) {
    $stmt = $pdo->prepare("SELECT p.* FROM peripherique p JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique WHERE pp.id_poste = ?");
    $stmt->execute([$selectedPoste]);
    $peripheriques = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT u.* FROM unite u JOIN poste_unite pu ON u.id_unite = pu.id_unite WHERE pu.id_poste = ?");
    $stmt->execute([$selectedPoste]);
    $unites = $stmt->fetchAll();
}

// Afficher le header
afficherHeader('Créer un ticket pour la salle', 2);
?>
<div class="container">
    <h1 class="mb-4">Créer un nouveau ticket pour la salle : <span class="text-primary"><?= htmlspecialchars($salle['nom_salle']) ?></span></h1>

    <!-- QR code d'accès direct -->
    <div class="mb-4 text-center">
        <h5>QR code d'accès direct à cette page</h5>
        <div style="display: inline-block; padding: 16px; border: 2px solid #007bff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); background: #fff;">
            <img id="qrcode-img" src="data:image/png;base64,<?= base64_encode(file_get_contents($qrFile)) ?>" alt="QR Code Salle" style="max-width:200px; display:block; margin:auto;">
            <div class="mt-2 fw-bold text-primary" style="font-size: 1.1em;">Salle : <?= htmlspecialchars($salle['nom_salle']) ?></div>
            <button class="btn btn-outline-primary btn-sm mt-2" onclick="downloadQR()">Télécharger le QR code</button>
        </div>
        <div class="mt-2"><small>Scannez ce QR code pour accéder directement à la création de ticket pour cette salle.</small></div>
    </div>
    <script>
    function downloadQR() {
        var img = document.getElementById('qrcode-img');
        var url = img.src;
        var a = document.createElement('a');
        a.href = url;
        a.download = 'QR_Salle_<?= htmlspecialchars($salle['nom_salle']) ?>.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    </script>

    <?php afficherMessage(); ?>

    <!-- Formulaire de sélection du poste (pour filtrer les périphériques et unités) -->
    <form method="POST" id="filtrePosteForm">
        <div class="form-group mb-3">
            <label for="id_poste">Poste :</label>
            <select name="id_poste" id="id_poste" class="form-control" onchange="this.form.submit()">
                <option value="">Sélectionner un poste</option>
                <?php foreach($postes as $poste): ?>
                    <option value="<?= $poste['id_poste'] ?>" <?= $selectedPoste == $poste['id_poste'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($poste['nom_poste']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- Formulaire principal de création de ticket -->
    <form method="POST" action="../../includes/ticket_insertt.php" class="needs-validation mt-4" novalidate>
        <!-- Champs cachés pour la salle et le poste -->
        <input type="hidden" name="id_salle" value="<?= htmlspecialchars($id_salle) ?>">
        <input type="hidden" name="id_poste" value="<?= htmlspecialchars($selectedPoste ?? '') ?>">

        <div class="form-group mb-3">
            <label for="code_utilisateur">Votre Code Utilisateur : <span class="text-danger">*</span></label>
            <input type="text" name="code_utilisateur" id="code_utilisateur" class="form-control" required maxlength="6" pattern=".{6,6}" title="Entrez votre code utilisateur à 6 caractères">
            <div class="invalid-feedback">Veuillez entrer votre code utilisateur (6 caractères).</div>
            <small class="form-text text-muted">Ce code vous a été fourni par l'administrateur.</small>
        </div>

        <div class="form-group mb-3">
            <label for="id_appareil">Appareil (si non lié à un poste) :</label>
            <select name="id_appareil" id="id_appareil" class="form-control" <?= ($selectedPoste) ? 'disabled' : '' ?>>
                <option value="">Sélectionner un appareil</option>
                <?php if (!empty($appareils) && !$selectedPoste): ?>
                    <?php foreach($appareils as $appareil): ?>
                        <option value="<?= $appareil['id_appareil'] ?>" <?= ($_POST['id_appareil'] ?? '') == $appareil['id_appareil'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($appareil['type'] . ' - ' . $appareil['modele']) ?> (<?= htmlspecialchars($appareil['numero_inventaire']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="form-text text-muted">Sélectionnez si le problème concerne un appareil non assigné à un poste spécifique dans cette salle.</small>
        </div>

        <div class="form-group mb-3">
            <label for="id_peripherique">Périphérique (si lié à un poste) :</label>
            <select name="id_peripherique" id="id_peripherique" class="form-control" <?= !$selectedPoste ? 'disabled' : '' ?>>
                <option value="">Sélectionner un périphérique</option>
                <?php if ($selectedPoste): ?>
                    <?php foreach($peripheriques as $peripherique): ?>
                        <option value="<?= $peripherique['id_peripherique'] ?>">
                            <?= htmlspecialchars($peripherique['type'] . ' - ' . $peripherique['numero_inventaire']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="form-text text-muted">Sélectionnez si le problème concerne un périphérique spécifique du poste choisi.</small>
        </div>

        <div class="form-group mb-3">
            <label for="id_unite">Unité Centrale (si lié à un poste) :</label>
            <select name="id_unite" id="id_unite" class="form-control" <?= !$selectedPoste ? 'disabled' : '' ?>>
                <option value="">Sélectionner une unité</option>
                <?php if ($selectedPoste): ?>
                    <?php foreach($unites as $unite): ?>
                        <option value="<?= $unite['id_unite'] ?>">
                            <?= htmlspecialchars($unite['numero_inventaire']) ?> (<?= htmlspecialchars($unite['processeur']) ?> - <?= htmlspecialchars($unite['ram']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small class="form-text text-muted">Sélectionnez si le problème concerne l'unité centrale spécifique du poste choisi.</small>
        </div>

        <div class="alert alert-info" role="alert">
            Veuillez sélectionner soit un Appareil (si non lié à un poste), soit un Périphérique ou une Unité Centrale (si lié au poste sélectionné). Un seul choix est nécessaire pour identifier l'équipement concerné.
        </div>

        <div class="form-group mb-3">
            <label for="description">Description du problème : <span class="text-danger">*</span></label>
            <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
            <div class="invalid-feedback">Veuillez décrire le problème</div>
        </div>

        <div class="form-group mb-3">
            <label for="categorie">Catégorie : <span class="text-danger">*</span></label>
            <select name="categorie" id="categorie" class="form-control" required>
                <option value="">Sélectionner une catégorie</option>
                <option value="Matériel">Matériel</option>
                <option value="Logiciel">Logiciel</option>
                <option value="Réseau">Réseau</option>
                <option value="Demande">Demande</option>
            </select>
            <div class="invalid-feedback">Veuillez sélectionner une catégorie</div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Créer le ticket</button>
        </div>
    </form>
</div>

<script>
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>

<?php
afficherFooter();
?>
