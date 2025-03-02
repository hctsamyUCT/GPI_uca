<?php
require_once 'functions.php';

// Configurer et démarrer la session
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    header('Location: ../pages/login.php?error=Vous devez être connecté');
    exit();
}

// Ajoutez cette fonction de validation
function validerReference($pdo, $table, $id) {
    if ($id !== null) {
        // Corriger les noms de colonnes spéciaux
        $column = match($table) {
            'salles' => 'id_salle',
            'poste' => 'id_poste',
            'appareils' => 'id_appareil',
            default => "id_$table"
        };
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Référence invalide pour $table");
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['filtre'])) {
    try {
        $pdo = connectDB();
        $id_user = $_SESSION['user_id'];

        // Validation des champs requis
        $required = ['description', 'categorie'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ $field est requis");
            }
        }

        // Récupération des données avec gestion des valeurs nulles
        $id_appareil = isset($_POST['id_appareil']) && $_POST['id_appareil'] !== '' ? $_POST['id_appareil'] : null;
        $id_poste = !empty($_POST['id_poste']) ? $_POST['id_poste'] : null;
        $id_salle = !empty($_POST['id_salle']) ? $_POST['id_salle'] : null;
        $id_peripherique = !empty($_POST['id_peripherique']) ? $_POST['id_peripherique'] : null;
        $id_unite = !empty($_POST['id_unite']) ? $_POST['id_unite'] : null;

        // Valider la cohérence appareil/salle
        if ($id_appareil && $id_salle) {
            $stmt = $pdo->prepare("SELECT id_salle FROM appareils WHERE id_appareil = ?");
            $stmt->execute([$id_appareil]);
            $salle_appareil = $stmt->fetchColumn();
            
            if ($salle_appareil != $id_salle) {
                throw new Exception("L'appareil ne correspond pas à la salle sélectionnée");
            }
        }

        // Récupération des données
        $data = [
            ':id_user' => $id_user,
            ':id_salle' => $id_salle,
            ':id_poste' => $id_poste,
            ':id_appareil' => $id_appareil,
            ':id_peripherique' => $id_peripherique,
            ':id_unite' => $id_unite,
            ':description' => $_POST['description'],
            ':priorite' => 'Normal',
            ':categorie' => $_POST['categorie']
        ];

        // Ajoutez ces validations avant l'insertion
        $references = [
            'salles' => $data[':id_salle'],
            'poste' => $data[':id_poste'],
            'appareils' => $data[':id_appareil'],
            'peripherique' => $data[':id_peripherique'],
            'unite' => $data[':id_unite']
        ];

        foreach ($references as $table => $id) {
            if ($id !== null) {
                validerReference($pdo, $table, $id);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO tickets (
                id_user, id_salle, id_poste, id_appareil, 
                id_peripherique, id_unite, description, 
                priorite, categorie
            ) VALUES (
                :id_user, :id_salle, :id_poste, :id_appareil, 
                :id_peripherique, :id_unite, :description, 
                :priorite, :categorie
            )
        ");

        $stmt->execute($data);
        
        // Redirection basée sur le rôle de l'utilisateur
        // Admin (1) ou Technicien (2) sont redirigés vers manage_tickets.php
        // Les autres utilisateurs sont redirigés vers views_all.php
        if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2)) {
            header('Location: ../pages/admin/manage_tickets.php?succes=Ticket créé avec succès');
        } else {
            header('Location: ../pages/common/views_all.php?succes=Ticket créé avec succès');
        }
        exit();

    } catch (Exception $e) {
        header('Location: ../pages/user/create_ticket.php?erreur=' . urlencode($e->getMessage()));
        exit();
    }
}
?>
