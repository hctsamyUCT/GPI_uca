<?php
require_once '../../includes/functions.php';

// Configuration de la session
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    header('Location: ../login.php?error=Vous devez être connecté');
    exit();
}

// Utiliser la nouvelle fonction pour vérifier les rôles
verifierRole([1, 2], '../../index.php', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page');

// Récupérer l'ID du ticket depuis l'URL
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Vérifier si l'ID du ticket est valide
if ($ticket_id <= 0) {
    header('Location: manage_tickets.php?error=Ticket invalide');
    exit();
}

// Initialiser la connexion à la base de données
$pdo = connectDB();

// Récupérer les détails du ticket
$query = "
    SELECT t.*, u.nom, u.prenom, u.email,
           s.nom_salle, p.nom_poste, a.modele AS appareil_modele, a.type AS appareil_type,
           pe.type AS peripherique_type,
           uc.numero_inventaire
    FROM tickets t
    LEFT JOIN users u ON t.id_user = u.id_user
    LEFT JOIN salles s ON t.id_salle = s.id_salle
    LEFT JOIN poste p ON t.id_poste = p.id_poste
    LEFT JOIN appareils a ON t.id_appareil = a.id_appareil
    LEFT JOIN peripherique pe ON t.id_peripherique = pe.id_peripherique
    LEFT JOIN unite uc ON t.id_unite = uc.id_unite
    WHERE t.id_ticket = ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

// Vérifier si le ticket existe
if (!$ticket) {
    header('Location: manage_tickets.php?error=Ticket introuvable');
    exit();
}

// NOUVELLE SECTION: Traitement des actions sur les équipements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actions pour les tickets (code existant pour add_intervention)
    if (isset($_POST['add_intervention'])) {
        $description = $_POST['description'] ?? '';
        $action = $_POST['action_effectuee'] ?? '';
        $nouvel_etat = $_POST['nouvel_etat'] ?? $ticket['etat'];
        
        // Validation des données
        if (empty($description) || empty($action)) {
            $error = "Tous les champs sont obligatoires";
        } else {
            try {
                // Début de la transaction
                $pdo->beginTransaction();
                
                // Insérer l'intervention
                $query_insert = "
                    INSERT INTO interventions (id_ticket, id_user, description, action_effectuee)
                    VALUES (?, ?, ?, ?)
                ";
                $stmt_insert = $pdo->prepare($query_insert);
                $stmt_insert->execute([$ticket_id, $_SESSION['user_id'], $description, $action]);
                
                // Mettre à jour l'état du ticket si nécessaire
                if ($nouvel_etat !== $ticket['etat']) {
                    $query_update = "
                        UPDATE tickets
                        SET etat = ?
                        WHERE id_ticket = ?
                    ";
                    $stmt_update = $pdo->prepare($query_update);
                    $stmt_update->execute([$nouvel_etat, $ticket_id]);
                }
                
                // Valider la transaction
                $pdo->commit();
                
                // Rediriger pour éviter la soumission multiple du formulaire
                header("Location: ticket_details.php?id=$ticket_id&success=Intervention ajoutée");
                exit();
            } catch (PDOException $e) {
                // Annuler la transaction en cas d'erreur
                $pdo->rollBack();
                $error = "Erreur lors de l'ajout de l'intervention : " . $e->getMessage();
            }
        }
    }
    
    // Actions pour les équipements
    // Poste - Mise à jour d'état
    if (isset($_POST['update_poste'])) {
        $poste_id = intval($_POST['poste_id']);
        $nouvel_etat = $_POST['nouvel_etat_poste'];
        
        try {
            $stmt = $pdo->prepare("UPDATE poste SET etat = ? WHERE id_poste = ?");
            $stmt->execute([$nouvel_etat, $poste_id]);
            header("Location: ticket_details.php?id=$ticket_id&success=État du poste mis à jour");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
    
    // Appareil - Mise à jour d'état
    if (isset($_POST['update_appareil'])) {
        $appareil_id = intval($_POST['appareil_id']);
        $nouvel_etat = $_POST['nouvel_etat_appareil'];
        
        try {
            $stmt = $pdo->prepare("UPDATE appareils SET etat = ? WHERE id_appareil = ?");
            $stmt->execute([$nouvel_etat, $appareil_id]);
            header("Location: ticket_details.php?id=$ticket_id&success=État de l'appareil mis à jour");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
    
    // Appareil - Suppression
    if (isset($_POST['delete_appareil'])) {
        $appareil_id = intval($_POST['appareil_id']);
        
        try {
            $pdo->beginTransaction();
            
            // Récupérer les informations de l'appareil avant suppression
            $stmt = $pdo->prepare("SELECT a.type, a.modele, a.numero_serie, a.etat, m.nom_marque 
                                  FROM appareils a 
                                  JOIN marque m ON a.id_marque = m.id_marque 
                                  WHERE a.id_appareil = ?");
            $stmt->execute([$appareil_id]);
            $appareil_info = $stmt->fetch();
            
            // Créer une intervention pour documenter la suppression
            $description = "Suppression de l'appareil concerné par ce ticket";
            $action = "Appareil supprimé: " . $appareil_info['type'] . " - " . $appareil_info['modele'] . 
                      "\nMarque: " . $appareil_info['nom_marque'] . 
                      "\nNuméro de série: " . $appareil_info['numero_serie'] . 
                      "\nÉtat: " . $appareil_info['etat'];
            
            $stmt = $pdo->prepare("INSERT INTO interventions (id_ticket, id_user, description, action_effectuee) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $description, $action]);
            
            // 1. Mettre à NULL les références dans les tickets
            $stmt = $pdo->prepare("UPDATE tickets SET id_appareil = NULL WHERE id_appareil = ?");
            $stmt->execute([$appareil_id]);
            
            // 2. Supprimer l'appareil (le trigger archivera automatiquement)
            $stmt = $pdo->prepare("DELETE FROM appareils WHERE id_appareil = ?");
            $stmt->execute([$appareil_id]);
            
            $pdo->commit();
            header("Location: ticket_details.php?id=$ticket_id&success=Appareil supprimé et archivé. Une intervention a été créée pour documenter cette action.");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
    
    // Périphérique - Mise à jour d'état
    if (isset($_POST['update_peripherique'])) {
        $peripherique_id = intval($_POST['peripherique_id']);
        $nouvel_etat = $_POST['nouvel_etat_peripherique'];
        
        try {
            $stmt = $pdo->prepare("UPDATE peripherique SET etat = ? WHERE id_peripherique = ?");
            $stmt->execute([$nouvel_etat, $peripherique_id]);
            header("Location: ticket_details.php?id=$ticket_id&success=État du périphérique mis à jour");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
    
    // Périphérique - Suppression
    if (isset($_POST['delete_peripherique'])) {
        $peripherique_id = intval($_POST['peripherique_id']);
        
        try {
            $pdo->beginTransaction();
            
            // Récupérer les informations du périphérique avant suppression
            $stmt = $pdo->prepare("SELECT p.type, p.numero_inventaire, p.etat, m.nom_marque 
                                  FROM peripherique p 
                                  JOIN marque m ON p.id_marque = m.id_marque 
                                  WHERE p.id_peripherique = ?");
            $stmt->execute([$peripherique_id]);
            $periph_info = $stmt->fetch();
            
            // Créer une intervention pour documenter la suppression
            $description = "Suppression du périphérique concerné par ce ticket";
            $action = "Périphérique supprimé: " . $periph_info['type'] . 
                      "\nMarque: " . $periph_info['nom_marque'] . 
                      "\nNuméro d'inventaire: " . $periph_info['numero_inventaire'] . 
                      "\nÉtat: " . $periph_info['etat'];
            
            $stmt = $pdo->prepare("INSERT INTO interventions (id_ticket, id_user, description, action_effectuee) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $description, $action]);
            
            // 1. Détacher le périphérique des postes
            $stmt = $pdo->prepare("DELETE FROM poste_peripherique WHERE id_peripherique = ?");
            $stmt->execute([$peripherique_id]);
            
            // 2. Mettre à NULL les références dans les tickets
            $stmt = $pdo->prepare("UPDATE tickets SET id_peripherique = NULL WHERE id_peripherique = ?");
            $stmt->execute([$peripherique_id]);
            
            // 3. Supprimer le périphérique (le trigger archivera automatiquement)
            $stmt = $pdo->prepare("DELETE FROM peripherique WHERE id_peripherique = ?");
            $stmt->execute([$peripherique_id]);
            
            $pdo->commit();
            header("Location: ticket_details.php?id=$ticket_id&success=Périphérique supprimé et archivé. Une intervention a été créée pour documenter cette action.");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
    
    // Unité - Mise à jour d'état
    if (isset($_POST['update_unite'])) {
        $unite_id = intval($_POST['unite_id']);
        $nouvel_etat = $_POST['nouvel_etat_unite'];
        
        try {
            $stmt = $pdo->prepare("UPDATE unite SET etat = ? WHERE id_unite = ?");
            $stmt->execute([$nouvel_etat, $unite_id]);
            header("Location: ticket_details.php?id=$ticket_id&success=État de l'unité mis à jour");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
    
    // Unité - Suppression
    if (isset($_POST['delete_unite'])) {
        $unite_id = intval($_POST['unite_id']);
        
        try {
            // On commence une transaction pour s'assurer de l'intégrité des données
            $pdo->beginTransaction();
            
            // Récupérer les informations de l'unité avant suppression pour les conserver
            $stmt = $pdo->prepare("SELECT numero_inventaire, ram, processeur, etat FROM unite WHERE id_unite = ?");
            $stmt->execute([$unite_id]);
            $unite_info = $stmt->fetch();
            
            // Créer une intervention pour documenter la suppression
            $description = "Suppression de l'unité centrale concernée par ce ticket";
            $action = "Unité centrale supprimée: " . $unite_info['numero_inventaire'] . 
                      "\nRAM: " . $unite_info['ram'] . 
                      "\nProcesseur: " . $unite_info['processeur'] . 
                      "\nÉtat: " . $unite_info['etat'];
            
            $stmt = $pdo->prepare("INSERT INTO interventions (id_ticket, id_user, description, action_effectuee) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $description, $action]);
            
            // 1. D'abord, détacher l'unité des postes (si elle est affectée)
            $stmt = $pdo->prepare("DELETE FROM poste_unite WHERE id_unite = ?");
            $stmt->execute([$unite_id]);
            
            // 2. Mettre à NULL les références dans les tickets
            $stmt = $pdo->prepare("UPDATE tickets SET id_unite = NULL WHERE id_unite = ?");
            $stmt->execute([$unite_id]);
            
            // 3. Supprimer l'unité (le trigger archivera automatiquement)
            $stmt = $pdo->prepare("DELETE FROM unite WHERE id_unite = ?");
            $stmt->execute([$unite_id]);
            
            // Valider les modifications
            $pdo->commit();
            
            header("Location: ticket_details.php?id=$ticket_id&success=Unité supprimée et archivée. Une intervention a été créée pour documenter cette action.");
            exit();
        } catch (PDOException $e) {
            // En cas d'erreur, annuler toutes les modifications
            $pdo->rollBack();
            $error = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
}

// Récupérer les détails des équipements liés au ticket
$materiel_details = [];

// Détails du poste si applicable
if ($ticket['id_poste']) {
    $query = "SELECT * FROM poste WHERE id_poste = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket['id_poste']]);
    $materiel_details['poste'] = $stmt->fetch();
}

// Détails de l'appareil si applicable
if ($ticket['id_appareil']) {
    $query = "SELECT * FROM appareils WHERE id_appareil = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket['id_appareil']]);
    $materiel_details['appareil'] = $stmt->fetch();
}

// Détails du périphérique si applicable
if ($ticket['id_peripherique']) {
    $query = "SELECT * FROM peripherique WHERE id_peripherique = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket['id_peripherique']]);
    $materiel_details['peripherique'] = $stmt->fetch();
}

// Détails de l'unité si applicable
if ($ticket['id_unite']) {
    $query = "SELECT * FROM unite WHERE id_unite = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket['id_unite']]);
    $materiel_details['unite'] = $stmt->fetch();
}

// Récupérer les interventions liées au ticket
$query_interventions = "
    SELECT i.*, u.nom, u.prenom
    FROM interventions i
    JOIN users u ON i.id_user = u.id_user
    WHERE i.id_ticket = ?
    ORDER BY i.date_intervention DESC
";

$stmt_interventions = $pdo->prepare($query_interventions);
$stmt_interventions->execute([$ticket_id]);
$interventions = $stmt_interventions->fetchAll();

// Affichage de la page
afficherHeader('Détails du ticket', 2);

// Déterminer les classes CSS en fonction de l'état et de la priorité
$ticketStateClass = '';
$badgeStateClass = '';
switch($ticket['etat']) {
    case 'Ouvert': 
        $ticketStateClass = 'ticket-open'; 
        $badgeStateClass = 'badge-danger';
        break;
    case 'En cours': 
        $ticketStateClass = 'ticket-in-progress'; 
        $badgeStateClass = 'badge-warning';
        break;
    case 'Probleme Résoulu(fermé)': 
    case 'Résolu': 
        $ticketStateClass = 'ticket-resolved'; 
        $badgeStateClass = 'badge-success';
        break;
    case 'probleme non resoulu(fermé)': 
        $ticketStateClass = 'ticket-not-resolved'; 
        $badgeStateClass = 'badge-secondary';
        break;
    default: 
        $ticketStateClass = ''; 
        $badgeStateClass = 'badge-secondary';
}

$priorityClass = '';
$badgePriorityClass = '';
switch($ticket['priorite']) {
    case 'Haute': 
        $priorityClass = 'priority-high'; 
        $badgePriorityClass = 'badge-outline-danger';
        break;
    case 'Moyenne': 
        $priorityClass = 'priority-medium'; 
        $badgePriorityClass = 'badge-outline-warning';
        break;
    case 'Faible': 
        $priorityClass = 'priority-low'; 
        $badgePriorityClass = 'badge-outline-success';
        break;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
            <span class="text-primary">#<?= htmlspecialchars($ticket['id_ticket']) ?></span> - 
            <?= htmlspecialchars($ticket['categorie']) ?>
        </h1>
        <div>
            <a href="manage_tickets.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Informations du ticket -->
            <div class="card mb-4 <?= $ticketStateClass ?> border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Informations du ticket</h5>
                    <div>
                        <span class="badge <?= $badgeStateClass ?> mr-2"><?= htmlspecialchars($ticket['etat']) ?></span>
                        <span class="badge <?= $badgePriorityClass ?>"><?= htmlspecialchars($ticket['priorite']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Description</h6>
                        <p class="p-3 bg-light rounded"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Détails du ticket</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th class="border-0">Catégorie</th>
                                    <td class="border-0"><?= htmlspecialchars($ticket['categorie']) ?></td>
                                </tr>
                                <tr>
                                    <th>Date de création</th>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['date_creation']))) ?></td>
                                </tr>
                                <tr>
                                    <th>État</th>
                                    <td><span class="badge <?= $badgeStateClass ?>"><?= htmlspecialchars($ticket['etat']) ?></span></td>
                                </tr>
                                <tr>
                                    <th>Priorité</th>
                                    <td><span class="badge <?= $badgePriorityClass ?>"><?= htmlspecialchars($ticket['priorite']) ?></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Matériel concerné</h6>
                            <table class="table table-sm">
                                <?php if ($ticket['id_salle']): ?>
                                <tr>
                                    <th class="border-0">Salle</th>
                                    <td class="border-0"><?= htmlspecialchars($ticket['nom_salle']) ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($ticket['id_poste']): ?>
                                <tr>
                                    <th>Poste</th>
                                    <td><?= htmlspecialchars($ticket['nom_poste']) ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($ticket['id_appareil']): ?>
                                <tr>
                                    <th>Appareil</th>
                                    <td><?= htmlspecialchars($ticket['appareil_type'] . ' - ' . $ticket['appareil_modele']) ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($ticket['id_peripherique']): ?>
                                <tr>
                                    <th>Périphérique</th>
                                    <td><?= htmlspecialchars($ticket['peripherique_type']) ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($ticket['id_unite']): ?>
                                <tr>
                                    <th>Unité centrale</th>
                                    <td><?= htmlspecialchars($ticket['numero_inventaire']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <span class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 18px;">
                                <?= strtoupper(substr($ticket['prenom'], 0, 1) . substr($ticket['nom'], 0, 1)) ?>
                            </span>
                        </div>
                        <div>
                            <p class="mb-0 font-weight-bold"><?= htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']) ?></p>
                            <p class="mb-0 small text-muted"><?= htmlspecialchars($ticket['email']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- NOUVELLE SECTION: Gestion des équipements liés -->
            <?php if ($ticket['id_poste'] || $ticket['id_appareil'] || $ticket['id_peripherique'] || $ticket['id_unite']): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Gestion des équipements liés</h5>
                </div>
                <div class="card-body">
                    <?php if ($ticket['id_poste'] && isset($materiel_details['poste'])): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-3">Poste: <?= htmlspecialchars($materiel_details['poste']['nom_poste']) ?></h6>
                        <div class="mb-2">
                            <span class="badge <?= $materiel_details['poste']['etat'] == 'opérationnel' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $materiel_details['poste']['etat'] == 'opérationnel' ? 'Opérationnel' : 'En panne' ?>
                            </span>
                        </div>
                        <form method="POST" action="" class="d-flex">
                            <input type="hidden" name="poste_id" value="<?= $materiel_details['poste']['id_poste'] ?>">
                            <select name="nouvel_etat_poste" class="form-control form-control-sm mr-2">
                                <option value="opérationnel" <?= $materiel_details['poste']['etat'] == 'opérationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                <option value="en_panne" <?= $materiel_details['poste']['etat'] == 'en_panne' ? 'selected' : '' ?>>En panne</option>
                            </select>
                            <button type="submit" name="update_poste" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync-alt mr-1"></i> Mettre à jour l'état
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket['id_appareil'] && isset($materiel_details['appareil'])): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-3">Appareil: <?= htmlspecialchars($materiel_details['appareil']['type'] . ' - ' . $materiel_details['appareil']['modele']) ?></h6>
                        <div class="mb-2">
                            <span class="badge <?= $materiel_details['appareil']['etat'] == 'Operationnel' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $materiel_details['appareil']['etat'] ?>
                            </span>
                        </div>
                        <div class="d-flex">
                            <form method="POST" action="" class="d-flex mr-2">
                                <input type="hidden" name="appareil_id" value="<?= $materiel_details['appareil']['id_appareil'] ?>">
                                <select name="nouvel_etat_appareil" class="form-control form-control-sm mr-2">
                                    <option value="Operationnel" <?= $materiel_details['appareil']['etat'] == 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                    <option value="En panne" <?= $materiel_details['appareil']['etat'] == 'En panne' ? 'selected' : '' ?>>En panne</option>
                                </select>
                                <button type="submit" name="update_appareil" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sync-alt mr-1"></i> Mettre à jour
                                </button>
                            </form>
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet appareil ?');">
                                <input type="hidden" name="appareil_id" value="<?= $materiel_details['appareil']['id_appareil'] ?>">
                                <button type="submit" name="delete_appareil" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket['id_peripherique'] && isset($materiel_details['peripherique'])): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-3">Périphérique: <?= htmlspecialchars($materiel_details['peripherique']['type']) ?></h6>
                        <div class="mb-2">
                            <span class="badge <?= $materiel_details['peripherique']['etat'] == 'Operationnel' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $materiel_details['peripherique']['etat'] ?>
                            </span>
                        </div>
                        <div class="d-flex">
                            <form method="POST" action="" class="d-flex mr-2">
                                <input type="hidden" name="peripherique_id" value="<?= $materiel_details['peripherique']['id_peripherique'] ?>">
                                <select name="nouvel_etat_peripherique" class="form-control form-control-sm mr-2">
                                    <option value="Operationnel" <?= $materiel_details['peripherique']['etat'] == 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                    <option value="En panne" <?= $materiel_details['peripherique']['etat'] == 'En panne' ? 'selected' : '' ?>>En panne</option>
                                </select>
                                <button type="submit" name="update_peripherique" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sync-alt mr-1"></i> Mettre à jour
                                </button>
                            </form>
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce périphérique ?');">
                                <input type="hidden" name="peripherique_id" value="<?= $materiel_details['peripherique']['id_peripherique'] ?>">
                                <button type="submit" name="delete_peripherique" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket['id_unite'] && isset($materiel_details['unite'])): ?>
                    <div>
                        <h6 class="mb-3">Unité centrale: <?= htmlspecialchars($materiel_details['unite']['numero_inventaire']) ?></h6>
                        <div class="mb-2">
                            <span class="badge <?= $materiel_details['unite']['etat'] == 'Operationnel' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $materiel_details['unite']['etat'] ?>
                            </span>
                        </div>
                        <div class="d-flex">
                            <form method="POST" action="" class="d-flex mr-2">
                                <input type="hidden" name="unite_id" value="<?= $materiel_details['unite']['id_unite'] ?>">
                                <select name="nouvel_etat_unite" class="form-control form-control-sm mr-2">
                                    <option value="Operationnel" <?= $materiel_details['unite']['etat'] == 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                    <option value="En panne" <?= $materiel_details['unite']['etat'] == 'En panne' ? 'selected' : '' ?>>En panne</option>
                                </select>
                                <button type="submit" name="update_unite" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sync-alt mr-1"></i> Mettre à jour
                                </button>
                            </form>
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette unité centrale ?');">
                                <input type="hidden" name="unite_id" value="<?= $materiel_details['unite']['id_unite'] ?>">
                                <button type="submit" name="delete_unite" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Liste des interventions -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historique des interventions</h5>
                    <span class="badge badge-pill badge-primary"><?= count($interventions) ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($interventions)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-comments fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted">Aucune intervention n'a encore été effectuée sur ce ticket.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($interventions as $intervention): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar bg-info text-white rounded-circle d-flex align-items-center justify-content-center mr-2" style="width: 32px; height: 32px; font-size: 14px;">
                                                <?= strtoupper(substr($intervention['prenom'], 0, 1) . substr($intervention['nom'], 0, 1)) ?>
                                            </span>
                                            <h6 class="mb-0"><?= htmlspecialchars($intervention['prenom'] . ' ' . $intervention['nom']) ?></h6>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($intervention['date_intervention']))) ?></small>
                                    </div>
                                    <div class="ml-4">
                                        <div class="mb-3">
                                            <h6 class="text-muted small">Description</h6>
                                            <p class="p-2 bg-light rounded"><?= nl2br(htmlspecialchars($intervention['description'])) ?></p>
                                        </div>
                                        <div>
                                            <h6 class="text-muted small">Action effectuée</h6>
                                            <p class="p-2 bg-light rounded"><?= nl2br(htmlspecialchars($intervention['action_effectuee'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Formulaire d'ajout d'intervention -->
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ajouter une intervention</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="description">Description du problème</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="action_effectuee">Action effectuée</label>
                            <textarea class="form-control" id="action_effectuee" name="action_effectuee" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="nouvel_etat">Mettre à jour l'état du ticket</label>
                            <select class="form-control" id="nouvel_etat" name="nouvel_etat">
                                <option value="Ouvert" <?= $ticket['etat'] === 'Ouvert' ? 'selected' : '' ?>>Ouvert</option>
                                <option value="En cours" <?= $ticket['etat'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="Probleme Résoulu(fermé)" <?= $ticket['etat'] === 'Probleme Résoulu(fermé)' ? 'selected' : '' ?>>Résolu</option>
                                <option value="probleme non resoulu(fermé)" <?= $ticket['etat'] === 'probleme non resoulu(fermé)' ? 'selected' : '' ?>>Non résolu</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_intervention" class="btn btn-primary btn-block">
                            <i class="fas fa-plus mr-2"></i>Ajouter l'intervention
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
afficherFooter();
?>
