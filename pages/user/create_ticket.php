<?php
require_once '../../includes/functions.php';

// Configurer et démarrer la session avant toute sortie
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    // Redirection explicite avec un message d'erreur
    header('Location: ../../index.php?erreur=Vous devez être connecté');
    exit();
}

// Vérifier si l'utilisateur a le rôle approprié (tous les rôles peuvent accéder à cette page)
// Mais on peut ajouter une note spécifique pour les utilisateurs standard
$estUtilisateurStandard = ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 2);

$pdo = connectDB();

// Gestion de la soumission pour les filtres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtre'])) {
    $selectedSalle = $_POST['id_salle'] ?? null;
    $selectedPoste = $_POST['id_poste'] ?? null;
} else {
    $selectedSalle = null;
    $selectedPoste = null;
}

// Récupérer les sélections existantes
$salles = $pdo->query("SELECT * FROM salles")->fetchAll();

// Récupérer les postes en fonction de la salle sélectionnée
if ($selectedSalle) {
    $stmt = $pdo->prepare("SELECT * FROM poste WHERE id_salle = ?");
    $stmt->execute([$selectedSalle]);
    $postes = $stmt->fetchAll();
} else {
    $postes = [];
}

// Récupérer les périphériques en fonction du poste sélectionné
if ($selectedPoste) {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM peripherique p 
        JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique 
        WHERE pp.id_poste = ?
    ");
    $stmt->execute([$selectedPoste]);
    $peripheriques = $stmt->fetchAll();
} else {
    $peripheriques = [];
}

// Récupérer les unités en fonction du poste sélectionné
if ($selectedPoste) {
    $stmt = $pdo->prepare("
        SELECT u.* 
        FROM unite u
        JOIN poste_unite pu ON u.id_unite = pu.id_unite
        WHERE pu.id_poste = ?
    ");
    $stmt->execute([$selectedPoste]);
    $unites = $stmt->fetchAll();
} else {
    $unites = [];
}

// Récupérer les appareils en fonction de la salle sélectionnée
if ($selectedSalle) {
    $stmt = $pdo->prepare("SELECT * FROM appareils WHERE id_salle = ?");
    $stmt->execute([$selectedSalle]);
    $appareils = $stmt->fetchAll();
} else {
    $appareils = [];
}

// Utiliser la fonction afficherHeader avec le bon niveau de profondeur
afficherHeader('Créer un ticket', 2);
?>
<form method="POST" action="../../includes/ticket_insertt.php" class="needs-validation" novalidate></form>
<div class="container">
    <h1 class="mb-4">Créer un nouveau ticket</h1>
    
    <?php afficherMessage(); ?>
    
    <!-- Formulaire de filtrage -->
    <form method="POST" id="filtreForm">
        <input type="hidden" name="filtre" value="1">
        
        <div class="form-group">
            <label for="id_salle">Salle :</label>
            <select name="id_salle" id="id_salle" class="form-control" onchange="this.form.submit()">
                <option value="">Sélectionner une salle</option>
                <?php foreach($salles as $salle): ?>
                    <option value="<?= $salle['id_salle'] ?>" <?= $selectedSalle == $salle['id_salle'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($salle['nom_salle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_poste">Poste :</label>
            <select name="id_poste" id="id_poste" class="form-control" <?= !$selectedSalle ? 'disabled' : '' ?> onchange="this.form.submit()">
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
    <form method="POST" action="../../includes/ticket_insertt.php" class="needs-validation" novalidate>
        <!-- Ajouter les champs cachés pour les sélections existantes -->
        <input type="hidden" name="id_salle" value="<?= $selectedSalle ?>">
        <input type="hidden" name="id_poste" value="<?= $selectedPoste ?>">
        
        <div class="form-group">
            <label for="id_appareil">Appareil :</label>
            <select name="id_appareil" id="id_appareil" class="form-control" <?= (!$selectedSalle || $selectedPoste) ? 'disabled' : '' ?>>
                <option value="">Sélectionner un appareil</option>
                <?php if (!empty($appareils) && !$selectedPoste): ?>
                    <?php foreach($appareils as $appareil): ?>
                        <option value="<?= $appareil['id_appareil'] ?>" <?= ($_POST['id_appareil'] ?? '') == $appareil['id_appareil'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($appareil['type'] . ' - ' . $appareil['modele']) ?>
                            (<?= htmlspecialchars($appareil['numero_serie']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_peripherique">Périphérique :</label>
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
        </div>

        <div class="form-group">
            <label for="id_unite">Unité :</label>
            <select name="id_unite" id="id_unite" class="form-control" <?= !$selectedPoste ? 'disabled' : '' ?>>
                <option value="">Sélectionner une unité</option>
                <?php if ($selectedPoste): ?>
                    <?php foreach($unites as $unite): ?>
                        <option value="<?= $unite['id_unite'] ?>">
                            <?= htmlspecialchars($unite['numero_inventaire']) ?>
                            (<?= htmlspecialchars($unite['processeur']) ?> - <?= htmlspecialchars($unite['ram']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Description du problème :</label>
            <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
            <div class="invalid-feedback">Veuillez décrire le problème</div>
        </div>

        <div class="form-group">
            <label for="categorie">Catégorie :</label>
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
            <a href="../common/views_all.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php
afficherFooter();
?>
