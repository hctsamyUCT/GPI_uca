<?php
session_start();
require_once '../../includes/functions.php';

// Vérification des droits d'accès (admin uniquement)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

$pdo = connectDB();
$message = '';
$messageType = '';

// Traitement du formulaire d'ajout de salle
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
                    <h5 class="mb-0">Ajouter une salle</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="nom_salle">Nom de la salle:</label>
                            <input type="text" class="form-control" id="nom_salle" name="nom_salle" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="etage">Étage:</label>
                            <select class="form-control" id="etage" name="etage" required>
                                <option value="">Sélectionnez un étage</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="groupe">Groupe:</label>
                            <select class="form-control" id="groupe" name="groupe" required>
                                <option value="">Sélectionnez un groupe</option>
                                <option value="Administratif">Administratif</option>
                                <option value="Etude">Etude</option>
                                <option value="Cisco">Cisco</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="ajouter_salle" class="btn btn-primary">Ajouter la salle</button>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salles as $salle): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($salle['id_salle']) ?></td>
                                        <td><?= htmlspecialchars($salle['nom_salle']) ?></td>
                                        <td><?= htmlspecialchars($salle['etage']) ?></td>
                                        <td><?= htmlspecialchars($salle['groupe']) ?></td>
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
                    <h5 class="mb-0">Ajouter une marque</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="nom_marque">Nom de la marque:</label>
                            <input type="text" class="form-control" id="nom_marque" name="nom_marque" required>
                        </div>
                        
                        <button type="submit" name="ajouter_marque" class="btn btn-primary">Ajouter la marque</button>
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