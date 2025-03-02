<?php // Directive d'ouverture du traitement PHP

/*------------------------------
/* INCLUSION DES DÉPENDANCES
/*------------------------------
*/
require_once '../../includes/functions.php';// Charge le fichier functions.php une seule fois :
// - Contient les fonctions connectDB(), afficherHeader(), etc.
// - Chemin relatif depuis le dossier courant

// Configuration de la session avant d'afficher l'en-tête
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    // Redirection explicite avec un message d'erreur
    header('Location: ../../index.php?erreur=Vous devez être connecté');
    exit();
}

// Vérifier si l'utilisateur a le rôle approprié (admin ou technicien)
if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 2) {
    // Redirection vers la page de création de ticket pour les utilisateurs standard
    header('Location: ../user/create_ticket.php?erreur=Accès non autorisé');
    exit();
}

/*------------------------------
/* EN-TÊTE HTML
/*------------------------------
*/
afficherHeader('Archives des appareils', 2);
// Appel de la fonction qui génère le début du HTML :
// - Paramètre 'Archives des appareils' devient le <title>
// - Génère la structure HTML jusqu'au <div class="container">

/*------------------------------
/* CONNEXION BASE DE DONNÉES
/*------------------------------
*/
try { // Début du bloc de gestion d'erreurs
    $pdo = connectDB();
    // Appel de la fonction de connexion :
    // - Retourne un objet PDO
    // - Utilise les paramètres de connexion de functions.php

    // Traitement de la confirmation de restauration
    if (isset($_POST['restaurer']) && isset($_POST['id_archive'])) {
        header('Location: ../../includes/gestion.php?action=restaurer_appareil&id=' . $_POST['id_archive']);
        exit();
    }

    $sql = "SELECT aa.*, 
                   m.nom_marque, 
                   s.nom_salle 
            FROM archives_appareils aa
            LEFT JOIN marque m ON aa.id_marque = m.id_marque
            LEFT JOIN salles s ON aa.id_salle = s.id_salle";

    $stmt = $pdo->query($sql);
    /* Exécution directe d'une requête SQL :
       - "SELECT *" : Sélectionne toutes les colonnes
       - FROM archives_appareils : Table des archives
       - ORDER BY date_archive DESC : Tri décroissant par date
       Sécurité :
       - Aucune donnée externe => pas de risque d'injection SQL
       Performance :
       - Pour les grosses tables : limiter avec LIMIT
    */

    $archives = $stmt->fetchAll();
    // Récupération de tous les résultats :
    // - fetchAll() retourne un tableau associatif
    // - Alternative : fetch() pour traitement ligne par ligne

/*------------------------------
/* GESTION DES RÉSULTATS
/*------------------------------
*/
    echo '<div class="d-flex justify-content-between align-items-center mb-4">';
    echo '<h1>Archives des appareils supprimés</h1>';
    echo '</div>';

    if (empty($archives)) { // Vérifie si le tableau est vide
        echo '<div class="alert alert-info">Aucun appareil archivé.</div>';
        // Message de contenu vide :
        // - Balise p (paragraphe)
        // - Texte statique
    } else { // Si des résultats existent
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped">';
        echo '<thead class="thead-dark">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Type</th>';
        echo '<th>Modèle</th>';
        echo '<th>N° Série</th>';
        echo '<th>État</th>';
        echo '<th>Date d\'archivage</th>';
        echo '<th>Salle</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($archives as $archive) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($archive['id_appareil']) . '</td>';
            // Colonne ID :
            // - htmlspecialchars() échappe HTML
            // - Conversion automatique en string

            echo '<td>' . htmlspecialchars($archive['type']) . '</td>';
            // Colonne Type :
            // - Valeur parmi 'Imprimante'/'Projecteur'

            echo '<td>' . htmlspecialchars($archive['modele']) . '</td>';
            // Colonne Modèle :
            // - Chaîne libre (ex: 'MX405')

            echo '<td>' . htmlspecialchars($archive['numero_serie']) . '</td>';
            // Colonne Numéro de série :
            // - Identifiant unique de l'appareil

            echo '<td>';
            if ($archive['etat'] === 'Operationnel') {
                echo '<span class="badge badge-success">Opérationnel</span>';
            } else {
                echo '<span class="badge badge-danger">En panne</span>';
            }
            echo '</td>';
            // Colonne État :
            // - Valeur parmi 'Operationnel'/'En panne'

            echo '<td>' . date('d/m/Y H:i', strtotime($archive['date_archive'])) . '</td>';
            // Colonne Date d'archivage :
            // - Format datetime de MySQL (ex: '2025-02-10 08:00:00')
            // - Pourrait être formaté avec date

            echo '<td>' . htmlspecialchars($archive['nom_salle'] ?? 'Non assigné') . '</td>';
            // Colonne Salle :
            // - Nom de la salle associée

            echo '<td>';
            echo '<form method="post" class="d-inline">';
            echo '<input type="hidden" name="id_archive" value="' . $archive['id_archive'] . '">';
            echo '<button type="submit" name="restaurer" class="btn btn-success btn-sm restore-btn">';
            echo '<i class="fas fa-undo"></i> Restaurer</button>';
            echo '</form>';
            echo '</td>';

            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Une erreur est survenue : ' . htmlspecialchars($e->getMessage()) . '</div>';
    // Affichage d'erreur :
    // - Classe CSS 'error' pour le style
    // - $e->getMessage() contient les détails techniques
    // Sécurité : En production, remplacer par un message générique
}

/*------------------------------
/* PIED DE PAGE
/*------------------------------
*/
afficherFooter();
// Appel de la fonction qui ferme les balises HTML :
// - Ferme le div.container
// - Ferme body et html
// - Aucun paramètre nécessaire
?> <!-- Fin du script PHP -->
