<?php
require_once 'functions.php';

// Configurer et démarrer la session
configureSession();

// Ajoutez cette fonction de validation (si elle n'est pas déjà dans functions.php)
function validerReference($pdo, $table, $id) {
    if ($id !== null) {
        // Corriger les noms de colonnes spéciaux
        $column = match($table) {
            'salles' => 'id_salle',
            'poste' => 'id_poste',
            'appareils' => 'id_appareil',
            // Ajoutez 'peripherique' et 'unite' si leurs clés primaires ne sont pas id_table
            'peripherique' => 'id_peripherique',
            'unite' => 'id_unite',
            default => "id_$table" // Hypothèse par défaut
        };
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Référence invalide pour $table avec ID $id");
        }
    }
}

// --- Traitement du formulaire ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['filtre'])) {
    $pdo = connectDB();

    // --- CAS 1: Création de ticket via code_utilisateur (formulaire public) ---
    if (isset($_POST['code_utilisateur'])) {
        $code_utilisateur = trim($_POST['code_utilisateur']);
        $id_salle = !empty($_POST['id_salle']) ? $_POST['id_salle'] : null;
        $id_poste = !empty($_POST['id_poste']) ? $_POST['id_poste'] : null;
        $id_appareil = !empty($_POST['id_appareil']) ? $_POST['id_appareil'] : null;
        $id_peripherique = !empty($_POST['id_peripherique']) ? $_POST['id_peripherique'] : null;
        $id_unite = !empty($_POST['id_unite']) ? $_POST['id_unite'] : null;
        $description = trim($_POST['description'] ?? '');
        $categorie = $_POST['categorie'] ?? '';
        $id_user = null;

        try {
            // Validation Code Utilisateur
            if (empty($code_utilisateur)) {
                throw new Exception('Le code utilisateur est obligatoire.');
            }
            if (strlen($code_utilisateur) !== 6) {
                throw new Exception('Le code utilisateur doit contenir exactement 6 caractères.');
            }
            // Correction: Retrait de la condition sur 'supprime_le'
            $stmt_user = $pdo->prepare("SELECT id_user FROM users WHERE code_utilisateur = ?");
            $stmt_user->execute([$code_utilisateur]);
            $user_data = $stmt_user->fetch();
            if (!$user_data) {
                throw new Exception('Code utilisateur invalide ou inconnu.');
            }
            $id_user = $user_data['id_user'];

            // Validation autres champs
            if (empty($description) || empty($categorie)) {
                throw new Exception('La description et la catégorie sont obligatoires.');
            }
            // Validation équipement (au moins un doit être sélectionné, sauf si demande générale)
            if ($categorie !== 'Demande' && $id_salle === null && $id_poste === null && $id_appareil === null && $id_peripherique === null && $id_unite === null) {
                 throw new Exception('Veuillez sélectionner au moins un équipement (salle, poste, appareil, etc.) ou choisir la catégorie "Demande".');
            }

            // Validation des références (salle, poste, appareil, etc.)
            validerReference($pdo, 'salles', $id_salle);
            validerReference($pdo, 'poste', $id_poste);
            validerReference($pdo, 'appareils', $id_appareil);
            validerReference($pdo, 'peripherique', $id_peripherique);
            validerReference($pdo, 'unite', $id_unite);

            // Validation cohérence appareil/salle
            if ($id_appareil && $id_salle) {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM appareils WHERE id_appareil = ? AND id_salle = ?");
                $stmt_check->execute([$id_appareil, $id_salle]);
                if ($stmt_check->fetchColumn() == 0) {
                    throw new Exception('L\'appareil sélectionné n\'appartient pas à la salle indiquée.');
                }
            }
             // Ajoutez des vérifications similaires pour poste/salle, peripherique/poste, unite/poste si nécessaire

            // Préparation des données pour l'insertion
            $data = [
                ':id_user' => $id_user,
                ':id_salle' => $id_salle,
                ':id_poste' => $id_poste,
                ':id_appareil' => $id_appareil,
                ':id_peripherique' => $id_peripherique,
                ':id_unite' => $id_unite,
                ':description' => $description,
                ':priorite' => 'Moyenne', // Priorité par défaut
                ':categorie' => $categorie
            ];

            // Insertion du ticket
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

            // Définir le message de succès et rediriger
            setMessage('Ticket créé avec succès.', 'success');
            header('Location: ../pages/user/create_ticket.php');
            exit();

        } catch (Exception $e) {
            // error_log("Erreur création ticket (code_utilisateur): " . $e->getMessage()); // Pour le débogage
            setMessage('Erreur lors de la création du ticket: ' . $e->getMessage(), 'danger');
            header('Location: ../pages/user/create_ticket.php');
            exit();
        }

    // --- CAS 2: Création de ticket par utilisateur connecté (Admin/Tech) ---
    } elseif (estConnecte()) {
        try {
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

             // Validation des références (salle, poste, appareil, etc.)
            validerReference($pdo, 'salles', $id_salle);
            validerReference($pdo, 'poste', $id_poste);
            validerReference($pdo, 'appareils', $id_appareil);
            validerReference($pdo, 'peripherique', $id_peripherique);
            validerReference($pdo, 'unite', $id_unite);


            // Valider la cohérence appareil/salle
            if ($id_appareil && $id_salle) {
                $stmt = $pdo->prepare("SELECT id_salle FROM appareils WHERE id_appareil = ?");
                $stmt->execute([$id_appareil]);
                $salle_appareil = $stmt->fetchColumn();
                if ($salle_appareil != $id_salle) {
                    throw new Exception("L'appareil ne correspond pas à la salle sélectionnée");
                }
            }
             // Ajoutez des vérifications similaires pour poste/salle, peripherique/poste, unite/poste si nécessaire


            // Préparation des données
            $data = [
                ':id_user' => $id_user,
                ':id_salle' => $id_salle,
                ':id_poste' => $id_poste,
                ':id_appareil' => $id_appareil,
                ':id_peripherique' => $id_peripherique,
                ':id_unite' => $id_unite,
                ':description' => $_POST['description'],
                // La priorité peut être définie différemment ici si nécessaire
                ':priorite' => $_POST['priorite'] ?? 'Moyenne',
                ':categorie' => $_POST['categorie']
            ];

            // Insertion du ticket
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

            // Redirection après succès (pas besoin de setMessage ici, la redirection inclut le message)
            // Admin (1) ou Technicien (2) sont redirigés vers manage_tickets.php
            // Les autres utilisateurs (si ce cas est possible) sont redirigés vers create_ticket.php
            if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2)) {
                header('Location: ../pages/admin/manage_tickets.php?succes=Ticket créé avec succès');
            } else {
                // Ce cas ne devrait pas arriver si seuls admin/tech peuvent créer via cette voie
                // Mais par sécurité, on redirige vers la page de création standard
                 setMessage('Ticket créé avec succès.', 'success'); // Ajout message pour ce cas
                 header('Location: ../pages/user/create_ticket.php');
            }
            exit();

        } catch (Exception $e) {
             // error_log("Erreur création ticket (connecté): " . $e->getMessage()); // Pour le débogage
            // Rediriger vers la page appropriée avec un message d'erreur
             $redirectPage = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2))
                ? '../pages/admin/manage_tickets.php' // Ou une page de création dédiée admin/tech?
                : '../pages/user/create_ticket.php';
            header('Location: ' . $redirectPage . '?erreur=' . urlencode('Erreur: ' . $e->getMessage()));
            exit();
        }
    } else {
         // Cas où la méthode est POST mais ni code_utilisateur n'est fourni, ni l'utilisateur n'est connecté
         // Rediriger vers la page de connexion ou de création publique
         setMessage('Accès non autorisé ou session expirée.', 'warning');
         header('Location: ../../index.php'); // Ou ../pages/user/create_ticket.php ?
         exit();
    }
} else {
     // Si la méthode n'est pas POST ou si c'est un filtre, ne rien faire ici
     // (ou rediriger si l'accès direct au script n'est pas souhaité)
     // header('Location: ../../index.php');
     // exit();
}
?>
