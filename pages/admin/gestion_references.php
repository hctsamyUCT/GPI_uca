<?php
require_once '../../includes/functions.php';

// Configuration de la session avant d'afficher l'en-tête
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    // Redirection explicite avec un message d'erreur
    header('Location: ../../index.php?erreur=Vous devez être connecté');
    exit();
}

// Vérification des droits d'accès (admin uniquement)
if ($_SESSION['user_role'] != 1) {
    header('Location: ../user/create_ticket.php?erreur=Accès non autorisé');
    exit();
}

$pdo = connectDB();
$message = '';
$messageType = '';

// Gestion des actions GET (éditer / supprimer)
$editSalle = null;
if (isset($_GET['action']) && $_GET['action'] === 'delete_salle' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM salles WHERE id_salle = ?");
        $stmt->execute([$id]);
        header('Location: gestion_references.php?succes=1');
        exit();
    } catch (PDOException $e) {
        $message = "Erreur lors de la suppression de la salle: " . $e->getMessage();
        $messageType = "danger";
    }
} elseif (isset($_GET['action']) && $_GET['action'] === 'edit_salle' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM salles WHERE id_salle = ?");
    $stmt->execute([$id]);
    $editSalle = $stmt->fetch();
}

// Traitement du formulaire d'ajout ou modification de salle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_salle'])) {
    $nom_salle = trim($_POST['nom_salle']);
    $etage = $_POST['etage'];
    $groupe = $_POST['groupe'];
    
    // Validation des données
    if (empty($nom_salle) || empty($etage) || empty($groupe)) {
        $message = "Tous les champs sont obligatoires pour ajouter une salle.";
        $messageType = "danger";
    } else {
        try {
            // Vérification si la salle existe déjà
            $stmt = $pdo->prepare("SELECT id_salle FROM salles WHERE nom_salle = ? AND etage = ?");
            $stmt->execute([$nom_salle, $etage]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Cette salle existe déjà à cet étage.";
                $messageType = "warning";
            } else {
                // Insertion de la nouvelle salle
                $stmt = $pdo->prepare("INSERT INTO salles (nom_salle, etage, groupe) VALUES (?, ?, ?)");
                $stmt->execute([$nom_salle, $etage, $groupe]);
                
                $message = "La salle a été ajoutée avec succès.";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout de la salle: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Traitement du formulaire de modification de salle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_salle'])) {
    $id_salle = (int) $_POST['id_salle'];
    $nom_salle = trim($_POST['nom_salle']);
    $etage = $_POST['etage'];
    $groupe = $_POST['groupe'];
    if (empty($nom_salle) || empty($etage) || empty($groupe)) {
        $message = "Tous les champs sont obligatoires pour modifier une salle.";
        $messageType = "danger";
    } else {
        try {
            // Vérification si une autre salle avec le même nom/étage existe
            $stmt = $pdo->prepare("SELECT id_salle FROM salles WHERE nom_salle = ? AND etage = ? AND id_salle != ?");
            $stmt->execute([$nom_salle, $etage, $id_salle]);
            if ($stmt->rowCount() > 0) {
                $message = "Une autre salle existe déjà à cet étage.";
                $messageType = "warning";
            } else {
                $stmt = $pdo->prepare("UPDATE salles SET nom_salle = ?, etage = ?, groupe = ? WHERE id_salle = ?");
                $stmt->execute([$nom_salle, $etage, $groupe, $id_salle]);
                header('Location: gestion_references.php?succes=1');
                exit();
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la modification de la salle: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Traitement du formulaire d'ajout de marque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_marque'])) {
    $nom_marque = trim($_POST['nom_marque']);
    
    // Validation des données
    if (empty($nom_marque)) {
        $message = "Le nom de la marque est obligatoire.";
        $messageType = "danger";
    } else {
        try {
            // Vérification si la marque existe déjà
            $stmt = $pdo->prepare("SELECT id_marque FROM marque WHERE nom_marque = ?");
            $stmt->execute([$nom_marque]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Cette marque existe déjà.";
                $messageType = "warning";
            } else {
                // Insertion de la nouvelle marque
                $stmt = $pdo->prepare("INSERT INTO marque (nom_marque) VALUES (?)");
                $stmt->execute([$nom_marque]);
                
                $message = "La marque a été ajoutée avec succès.";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout de la marque: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Traitement du formulaire de modification de marque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_marque'])) {
    $id_marque = (int) $_POST['id_marque'];
    $nom_marque = trim($_POST['nom_marque']);
    if (empty($nom_marque)) {
        $message = "Le nom de la marque est obligatoire.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_marque FROM marque WHERE nom_marque = ? AND id_marque != ?");
            $stmt->execute([$nom_marque, $id_marque]);
            if ($stmt->rowCount() > 0) {
                $message = "Une autre marque porte déjà ce nom.";
                $messageType = "warning";
            } else {
                $stmt = $pdo->prepare("UPDATE marque SET nom_marque = ? WHERE id_marque = ?");
                $stmt->execute([$nom_marque, $id_marque]);
                header('Location: gestion_references.php?succes=1');
                exit();
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la modification de la marque: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Actions de suppression pour les marques via GET
if (isset($_GET['action']) && $_GET['action'] === 'delete_marque' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM marque WHERE id_marque = ?");
        $stmt->execute([$id]);
        header('Location: gestion_references.php?succes=1');
        exit();
    } catch (PDOException $e) {
        $message = "Erreur lors de la suppression de la marque: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Préparation de l'édition de marque si demandé
$editMarque = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit_marque' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM marque WHERE id_marque = ?");
    $stmt->execute([$id]);
    $editMarque = $stmt->fetch();
}

// Récupération des salles existantes
$salles = $pdo->query("SELECT * FROM salles ORDER BY etage, groupe, nom_salle")->fetchAll();

// Récupération des marques existantes
$marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque")->fetchAll();

// Affichage de la page
afficherHeader('Gestion des Références', 2);
?>

<div class="container mt-4">
    <h1 class="mb-4">Gestion des Références</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Formulaire d'ajout de salle -->
        <div class="col-md-6">
            <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $editSalle ? 'Modifier la salle' : 'Ajouter une salle'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="nom_salle">Nom de la salle:</label>
                                <input type="text" class="form-control" id="nom_salle" name="nom_salle" required value="<?php echo $editSalle ? htmlspecialchars($editSalle['nom_salle']) : ''; ?>">
                            </div>
                        
                            <div class="form-group">
                                <label for="etage">Étage:</label>
                                <select class="form-control" id="etage" name="etage" required>
                                    <option value="">Sélectionnez un étage</option>
                                    <option value="2" <?php echo ($editSalle && $editSalle['etage']=='2') ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo ($editSalle && $editSalle['etage']=='3') ? 'selected' : ''; ?>>3</option>
                                </select>
                            </div>
                        
                            <div class="form-group">
                                <label for="groupe">Groupe:</label>
                                <select class="form-control" id="groupe" name="groupe" required>
                                    <option value="">Sélectionnez un groupe</option>
                                    <option value="Administratif" <?php echo ($editSalle && $editSalle['groupe']=='Administratif') ? 'selected' : ''; ?>>Administratif</option>
                                    <option value="Etude" <?php echo ($editSalle && $editSalle['groupe']=='Etude') ? 'selected' : ''; ?>>Etude</option>
                                    <option value="Cisco" <?php echo ($editSalle && $editSalle['groupe']=='Cisco') ? 'selected' : ''; ?>>Cisco</option>
                                </select>
                            </div>
                            <?php if ($editSalle): ?>
                                <input type="hidden" name="id_salle" value="<?php echo (int)$editSalle['id_salle']; ?>">
                                <button type="submit" name="modifier_salle" class="btn btn-warning">Enregistrer les modifications</button>
                                <a href="gestion_references.php" class="btn btn-secondary ml-2">Annuler</a>
                            <?php else: ?>
                                <button type="submit" name="ajouter_salle" class="btn btn-primary">Ajouter la salle</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            
            <!-- Liste des salles existantes -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Salles existantes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Étage</th>
                                        <th>Groupe</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salles as $salle): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($salle['id_salle']) ?></td>
                                            <td><?= htmlspecialchars($salle['nom_salle']) ?></td>
                                            <td><?= htmlspecialchars($salle['etage']) ?></td>
                                            <td><?= htmlspecialchars($salle['groupe']) ?></td>
                                            <td>
                                                <a href="gestion_references.php?action=edit_salle&id=<?= (int)$salle['id_salle'] ?>" class="btn btn-sm btn-info" title="Modifier"><i class="fas fa-edit"></i></a>
                                                <a href="gestion_references.php?action=delete_salle&id=<?= (int)$salle['id_salle'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cette salle ?')" class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulaire d'ajout de marque -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $editMarque ? 'Modifier la marque' : 'Ajouter une marque'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="nom_marque">Nom de la marque:</label>
                            <input type="text" class="form-control" id="nom_marque" name="nom_marque" required value="<?php echo $editMarque ? htmlspecialchars($editMarque['nom_marque']) : ''; ?>">
                        </div>
                        <?php if ($editMarque): ?>
                            <input type="hidden" name="id_marque" value="<?php echo (int)$editMarque['id_marque']; ?>">
                            <button type="submit" name="modifier_marque" class="btn btn-warning">Enregistrer les modifications</button>
                            <a href="gestion_references.php" class="btn btn-secondary ml-2">Annuler</a>
                        <?php else: ?>
                            <button type="submit" name="ajouter_marque" class="btn btn-primary">Ajouter la marque</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Liste des marques existantes -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Marques existantes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($marques as $marque): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($marque['id_marque']) ?></td>
                                        <td><?= htmlspecialchars($marque['nom_marque']) ?></td>
                                        <td>
                                            <a href="gestion_references.php?action=edit_marque&id=<?= (int)$marque['id_marque'] ?>" class="btn btn-sm btn-info" title="Modifier"><i class="fas fa-edit"></i></a>
                                            <a href="gestion_references.php?action=delete_marque&id=<?= (int)$marque['id_marque'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cette marque ?')" class="btn btn-sm btn-danger" title="Supprimer"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="../common/views_all.php" class="btn btn-secondary">Retour à la vue globale</a>
        <a href="../../index.php" class="btn btn-secondary ml-2">Retour à l'accueil</a>
    </div>
</div>

<?php
afficherFooter();
?>