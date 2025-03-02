<?php // Ouverture du script PHP

// Définition de la fonction de connexion à la base de données
function connectDB() {
    $host = 'localhost';    // Définition de l'hôte de la base de données
    $db   = 'gpii';          // Nom de la base de données
    $user = 'root';         // Nom d'utilisateur pour la connexion MySQL
    $pass = '';             // Mot de passe pour la connexion MySQL
    $charset = 'utf8mb4';   // Encodage des caractères utilisé

    // Construction de la chaîne de connexion PDO
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset"; // Format DSN pour MySQL

    // Configuration des options PDO
    $options = [ // Tableau d'options pour PDO
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Active le mode exception pour les erreurs
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Définit le mode de récupération par défaut
    ];

    // Création et retour de l'objet PDO
    return new PDO($dsn, $user, $pass, $options); // Instancie la connexion à la BD
}

// Fonction pour afficher l'en-tête HTML
function afficherHeader($titre, $niveau = 1) {
    $prefix = str_repeat('../', $niveau);
    
    echo '<!DOCTYPE html>';
    echo '<html lang="fr">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titre) . '</title>';
    
    // Chemins CSS dynamiques selon le niveau
    echo '<link rel="stylesheet" href="' . $prefix . 'assets/css/bootstrap.css">';
    echo '<link rel="stylesheet" href="' . $prefix . 'assets/css/custom.css">';
    
    // Ajout de jQuery et Bootstrap JS pour les fonctionnalités avancées
    echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>';
    
    echo '</head>';
    echo '<body>';
    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">';
    echo '<div class="container">';
    echo '<a class="navbar-brand" href="' . $prefix . 'index.php">GPI</a>';
    
    // Ajout du bouton de déconnexion si l'utilisateur est connecté
    if (isset($_SESSION['user_id'])) {
        echo '<div class="navbar-nav ml-auto">';
        echo '<a href="' . $prefix . 'includes/logout.php" class="btn btn-light">Déconnexion</a>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</nav>';
    echo '<div class="container">';
    
    // Système de messages amélioré
    if(isset($_GET['succes'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo 'Opération réussie !';
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>';
        echo '</div>';
    }
    if(isset($_GET['erreur'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo 'Erreur : ' . htmlspecialchars($_GET['erreur']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>';
        echo '</div>';
    }
}

// Fonction pour afficher le pied de page
function afficherFooter() {
    echo '</div>'; // Fermeture du conteneur principal (div class="container")
    echo '</body>'; // Fermeture du corps du document
    echo '</html>'; // Fermeture de la balise HTML
}

// Fonction pour afficher les messages système
function afficherMessage() {
    if(isset($_GET['succes'])) { // Vérifie la présence d'un message de succès
        echo '<div class="success">Opération réussie !</div>'; // Affiche un message de succès
    }
    if(isset($_GET['erreur'])) { // Vérifie la présence d'un message d'erreur
        echo '<div class="error">Erreur : ' . htmlspecialchars($_GET['erreur']) . '</div>'; // Affiche le message d'erreur
    }
}

// Fonction pour afficher le formulaire d'appareil
function afficherFormulaire($data = null) {
    try {
        $pdo = connectDB();
        
        // Vérification de l'existence des tables nécessaires
        $tables = ['marque', 'salles'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("La table $table n'existe pas");
            }
        }

        // Récupération des marques avec vérification
        $marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque")->fetchAll();
        if (empty($marques)) {
            throw new Exception("Aucune marque n'est disponible");
        }

        // Récupération des salles avec vérification
        $salles = $pdo->query("SELECT * FROM salles ORDER BY nom_salle")->fetchAll();

        $values = $data ?: [
            'type' => 'Imprimante',
            'modele' => '',
            'numero_serie' => '',
            'etat' => 'Operationnel',
            'id_marque' => '',
            'id_salle' => ''
        ];
        ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="../includes/gestion.php<?= $data ? '?action=modifier&id=' . $data['id_appareil'] : '?action=ajouter' ?>" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="type">Type :</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="Imprimante" <?= $values['type'] === 'Imprimante' ? 'selected' : '' ?>>Imprimante</option>
                            <option value="Projecteur" <?= $values['type'] === 'Projecteur' ? 'selected' : '' ?>>Projecteur</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un type</div>
                    </div>

                    <div class="form-group">
                        <label for="modele">Modèle :</label>
                        <input type="text" class="form-control" id="modele" name="modele" value="<?= htmlspecialchars($values['modele']) ?>" required>
                        <div class="invalid-feedback">Veuillez entrer un modèle</div>
                    </div>

                    <div class="form-group">
                        <label for="numero_serie">Numéro de série :</label>
                        <input type="text" class="form-control" id="numero_serie" name="numero_serie" value="<?= htmlspecialchars($values['numero_serie']) ?>" required pattern="[A-Za-z0-9-]+" minlength="3">
                        <div class="invalid-feedback">Le numéro de série doit contenir au moins 3 caractères (lettres, chiffres et tirets uniquement)</div>
                    </div>

                    <div class="form-group">
                        <label for="etat">État :</label>
                        <select name="etat" id="etat" class="form-control" required>
                            <option value="Operationnel" <?= $values['etat'] === 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                            <option value="En panne" <?= $values['etat'] === 'En panne' ? 'selected' : '' ?>>En panne</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un état</div>
                    </div>

                    <div class="form-group">
                        <label for="id_marque">Marque :</label>
                        <select name="id_marque" id="id_marque" class="form-control" required>
                            <option value="">Sélectionnez une marque</option>
                            <?php foreach($marques as $marque): ?>
                            <option value="<?= $marque['id_marque'] ?>" <?= $values['id_marque'] == $marque['id_marque'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($marque['nom_marque']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner une marque</div>
                    </div>

                    <div class="form-group">
                        <label for="id_salle">Salle :</label>
                        <select name="id_salle" id="id_salle" class="form-control">
                            <option value="">Non affecté</option>
                            <?php foreach($salles as $salle): ?>
                            <option value="<?= $salle['id_salle'] ?>" <?= $values['id_salle'] == $salle['id_salle'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($salle['nom_salle']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?= $data ? 'Modifier' : 'Ajouter' ?></button>
                        <a href="../pages/common/Gestion_appareil.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // Validation côté client
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        </script>
        <?php
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Modifiez la fonction configureSession
function configureSession() {
    // Vérifier si la session n'est pas déjà démarrée
    if (session_status() == PHP_SESSION_NONE) {
        // Configuration des paramètres de session avant de la démarrer
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Définir le chemin du cookie de session
        session_set_cookie_params(
            1800,      // 30 minutes
            '/',       // Chemin
            '',        // Domaine
            false,     // Secure (mettez true si vous utilisez HTTPS)
            true       // HTTPOnly
        );
        
        // Démarrer la session
        session_start();
    }
}

// Modifiez la fonction estConnecte
function estConnecte() {
    // Appeler configureSession qui vérifiera si la session est déjà démarrée
    configureSession();
    return isset($_SESSION['user_id']);
}

function restaurerAppareil($id_archive) {
    $pdo = connectDB();
    try {
        $pdo->beginTransaction();

        // 1. Récupérer les données de l'archive
        $stmt = $pdo->prepare("SELECT * FROM archives_appareils WHERE id_archive = ?");
        $stmt->execute([$id_archive]);
        $archive = $stmt->fetch();

        if (!$archive) {
            throw new Exception("Archive non trouvée");
        }

        // 2. Réinsérer dans la table appareils
        $stmt = $pdo->prepare("
            INSERT INTO appareils (
                id_appareil, type, modele, numero_serie, 
                etat, id_marque, id_salle, date_ajout
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $archive['id_appareil'],
            $archive['type'],
            $archive['modele'],
            $archive['numero_serie'],
            $archive['etat'],
            $archive['id_marque'],
            $archive['id_salle'],
            $archive['date_ajout']
        ]);

        // 3. Supprimer l'entrée des archives
        $stmt = $pdo->prepare("DELETE FROM archives_appareils WHERE id_archive = ?");
        $stmt->execute([$id_archive]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function restaurerPeripherique($id_archive) {
    $pdo = connectDB();
    try {
        $pdo->beginTransaction();

        // 1. Récupérer les données de l'archive
        $stmt = $pdo->prepare("SELECT * FROM archives_peripherique WHERE id_archive = ?");
        $stmt->execute([$id_archive]);
        $archive = $stmt->fetch();

        if (!$archive) {
            throw new Exception("Archive non trouvée");
        }

        // 2. Réinsérer dans la table peripherique
        $stmt = $pdo->prepare("
            INSERT INTO peripherique (
                id_peripherique, type, id_marque, numero_inventaire,
                etat, date_ajout, est_affecte
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $archive['id_peripherique'],
            $archive['type'],
            $archive['id_marque'],
            $archive['numero_inventaire'],
            $archive['etat'],
            $archive['date_ajout'],
            $archive['est_affecte']
        ]);

        // 3. Supprimer l'entrée des archives
        $stmt = $pdo->prepare("DELETE FROM archives_peripherique WHERE id_archive = ?");
        $stmt->execute([$id_archive]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function restaurerUnite($id_archive) {
    $pdo = connectDB();
    try {
        $pdo->beginTransaction();

        // 1. Récupérer les données de l'archive
        $stmt = $pdo->prepare("SELECT * FROM archives_unite WHERE id_archive = ?");
        $stmt->execute([$id_archive]);
        $archive = $stmt->fetch();

        if (!$archive) {
            throw new Exception("Archive non trouvée");
        }

        // 2. Réinsérer dans la table unite
        $stmt = $pdo->prepare("
            INSERT INTO unite (
                id_unite, numero_inventaire, ram, processeur,
                disque_dur_1_type, disque_dur_1_capacite,
                disque_dur_2_type, disque_dur_2_capacite,
                etat, date_achat, date_ajout, est_affecte
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $archive['id_unite'],
            $archive['numero_inventaire'],
            $archive['ram'],
            $archive['processeur'],
            $archive['disque_dur_1_type'],
            $archive['disque_dur_1_capacite'],
            $archive['disque_dur_2_type'],
            $archive['disque_dur_2_capacite'],
            $archive['etat'],
            $archive['date_achat'],
            $archive['date_ajout'],
            $archive['est_affecte']
        ]);

        // 3. Supprimer l'entrée des archives
        $stmt = $pdo->prepare("DELETE FROM archives_unite WHERE id_archive = ?");
        $stmt->execute([$id_archive]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?> <!-- Fin du script PHP -->
