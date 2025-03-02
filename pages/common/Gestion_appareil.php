<?php // Balise d'ouverture PHP - Démarre l'interprétation du code par le serveur

/*------------------------------
/* INCLUSION DES DÉPENDANCES
/*------------------------------
*/
require_once '../../includes/functions.php';
require_once '../../includes/filters.php';  // Ajout de cette ligne
// Inclusion du fichier de fonctions :
// - Chemin relatif depuis le dossier courant
// - Charge connectDB(), afficherHeader(), afficherMessage(), etc.
// - Évite les inclusions multiples grâce à require_once

/*------------------------------
/* CONFIGURATION DE LA PAGE
/*------------------------------
*/
afficherHeader('Gestion du parc informatique', 2);
// Appel de la fonction générant l'en-tête HTML :
// - Paramètre : titre de la page entre guillemets simples
// - Génère : doctype, html, head, meta, title, link CSS, body, nav
// - Le titre est échappé via htmlspecialchars() dans la fonction
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Liste des appareils</h1>
    <div>
        <a href="../archives/archives_appareil.php" class="btn btn-secondary me-2">
            <i class="fas fa-archive mr-2"></i>Archives
        </a>
        <a href="../../includes/gestion.php?action=ajouter" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Ajouter un appareil
        </a>
    </div>
</div>

<?php
/*------------------------------
/* CONNEXION ET RÉCUPÉRATION
/*------------------------------
*/
try { // Début du bloc try pour gestion d'erreurs
    $pdo = connectDB();
    // Connexion à MySQL via PDO :
    // - Utilise les paramètres de functions.php
    // - Retourne une instance PDO partagée
    // - Gère les exceptions en cas d'échec

    // Vérification de la connexion à la base de données
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
        
    // Traitement de la restauration
    if (isset($_POST['restaurer']) && isset($_POST['id_archive'])) {
        $id_archive = intval($_POST['id_archive']);
        try {
            // Vérifier si l'appareil existe dans les archives
            $stmt = $pdo->prepare("SELECT * FROM archives_appareils WHERE id_archive = ?");
            $stmt->execute([$id_archive]);
            
            if ($stmt->rowCount() > 0) {
                // Rediriger vers la page de gestion avec les bons paramètres
                header("Location: ../../includes/gestion.php?action=restaurer_appareil&id_archive=" . $id_archive);
                exit();
            } else {
                throw new Exception("Appareil non trouvé dans les archives");
            }
        } catch (Exception $e) {
            $_SESSION['erreur'] = "Erreur lors de la restauration : " . $e->getMessage();
        }
    }

    // Récupération des filtres
    $filters = [
        'type' => $_GET['type'] ?? '',
        'etat' => $_GET['etat'] ?? '',
        'id_salle' => $_GET['id_salle'] ?? '',
        'id_marque' => $_GET['id_marque'] ?? ''
    ];

    $search = $_GET['search'] ?? '';

    // Affichage du formulaire de filtrage
    echo getFilterForm('appareil', $filters);

    $searchColumns = [
        'modele' => 'Modèle',
        'numero_serie' => 'Numéro de série'
    ];

    echo getSearchForm($search, $searchColumns);

    // Construction de la requête avec filtres
    $baseQuery = "SELECT a.*, m.nom_marque, s.nom_salle 
                  FROM appareils a
                  LEFT JOIN marque m ON a.id_marque = m.id_marque
                  LEFT JOIN salles s ON a.id_salle = s.id_salle
                  WHERE 1=1";

    $filterData = buildFilterQuery('appareil', $filters);
    $finalQuery = $baseQuery . ($filterData['clause'] ? ' AND ' . substr($filterData['clause'], 6) : '');
    
    if (!empty($search)) {
        $selectedColumns = $_GET['search_columns'] ?? [];
        $whereClauses = [];
        $searchParams = [];
        
        foreach ($selectedColumns as $column) {
            switch($column) {
                case 'modele':
                    $whereClauses[] = "a.modele LIKE ?";
                    break;
                case 'numero_serie':
                    $whereClauses[] = "a.numero_serie LIKE ?";
                    break;
                case 'nom_marque':
                    $whereClauses[] = "m.nom_marque LIKE ?";
                    break;
            }
            $searchParams[] = "%$search%";
        }
        
        if (!empty($whereClauses)) {
            $finalQuery .= " AND (" . implode(" OR ", $whereClauses) . ")";
            $params = array_merge($filterData['params'], $searchParams);
        }
    } else {
        $params = $filterData['params'];
    }

    $stmt = $pdo->prepare($finalQuery);
    $stmt->execute($params);
    $appareils = $stmt->fetchAll();

/*------------------------------
/* AFFICHAGE DES MESSAGES
/*------------------------------
*/
    if (isset($_GET['erreur'])) {
        echo '<div class="alert alert-danger">';
        // Décode le message et autorise les balises HTML spécifiques
        echo strip_tags(urldecode($_GET['erreur']), '<br><ul><li>');
        echo '</div>';
    }

    if (isset($_GET['succes'])) {
        echo '<div class="alert alert-success">Opération réalisée avec succès</div>';
    }

/*------------------------------
/* GESTION DE L'AFFICHAGE
/*------------------------------
*/
    if (count($appareils) > 0) : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Type</th>
                        <th>Modèle</th>
                        <th>N° Série</th>
                        <th>Marque</th>
                        <th>Salle</th>
                        <th>État</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appareils as $appareil) : ?>
                        <tr>
                            <td><?= htmlspecialchars($appareil['type']) ?></td>
                            <td><?= htmlspecialchars($appareil['modele']) ?></td>
                            <td><?= htmlspecialchars($appareil['numero_serie']) ?></td>
                            <td><?= htmlspecialchars($appareil['nom_marque'] ?? 'Non définie') ?></td>
                            <td><?= htmlspecialchars(empty($appareil['nom_salle']) ? 'Non assigné' : $appareil['nom_salle']) ?></td>
                            <td>
                                <?php if ($appareil['etat'] === 'Operationnel') : ?>
                                    <span class="badge badge-success">Opérationnel</span>
                                <?php else : ?>
                                    <span class="badge badge-danger">En panne</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="../../includes/gestion.php?action=modifier&id=<?= $appareil['id_appareil'] ?>" 
                                       class="btn btn-info btn-sm" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../includes/gestion.php?action=supprimer&id=<?= $appareil['id_appareil'] ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet appareil ?')" 
                                       class="btn btn-danger btn-sm" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="alert alert-info">Aucun appareil trouvé.</div>
    <?php endif;

} catch (Exception $e) {
    ?>
    <div class="alert alert-danger">
        Une erreur est survenue : <?= htmlspecialchars($e->getMessage()) ?>
    </div>
    <?php
}

/*------------------------------
/* PIED DE PAGE
/*------------------------------
*/
afficherFooter();
// Appel de la fonction de fermeture :
// - Ferme le div.container ouvert dans afficherHeader()
// - Ferme les balises body et html
// - Garantit une structure HTML valide
?> <!-- Fin du script PHP -->
