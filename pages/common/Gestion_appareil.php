<?php // Balise d'ouverture PHP - Démarre l'interprétation du code par le serveur

/*------------------------------
/* INCLUSION DES DÉPENDANCES
/*------------------------------
*/
require_once '../../includes/functions.php';
require_once '../../includes/filters.php';  // Ajout de cette ligne

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

<div class="mb-4">
    <h1 class="mb-3 text-gradient">Liste des appareils</h1>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-3 justify-content-start">
                <a href="../../includes/gestion.php?action=ajouter" class="btn btn-gradient-primary px-3 py-2">
                    <i class="fas fa-plus me-2"></i>Ajouter un appareil
                </a>
                
                <a href="../archives/archives_appareil.php" class="btn btn-gradient-secondary px-3 py-2">
                    <i class="fas fa-archive me-2"></i>Archives
                </a>
                
                <a href="../../includes/export.php?type=pdf_paysage&source=appareils" class="btn btn-gradient-info px-3 py-2">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </a>
                
                <a href="../../includes/export.php?type=csv&source=appareils" class="btn btn-gradient-info px-3 py-2">
                    <i class="fas fa-file-csv me-2"></i>CSV
                </a>
                
                <button id="copyNames" class="btn btn-gradient-info px-3 py-2">
                    <i class="fas fa-copy me-2"></i>Copier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ajout des styles pour les dégradés de boutons -->
<style>
    .btn-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-gradient-primary:hover {
        background: linear-gradient(135deg, #3a5fc8 0%, #1a3a9c 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-gradient-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-gradient-secondary:hover {
        background: linear-gradient(135deg, #5a6268 0%, #3d4347 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-gradient-info {
        background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-gradient-info:hover {
        background: linear-gradient(135deg, #2c9faf 0%, #1e6f7a 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-gradient-edit {
        background: linear-gradient(135deg, #17a2b8 0%, #0f6674 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-gradient-edit:hover {
        background: linear-gradient(135deg, #138496 0%, #0c525d 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-gradient-danger {
        background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-gradient-danger:hover {
        background: linear-gradient(135deg, #d52a1a 0%, #9e1f13 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .gap-3 {
        gap: 1rem !important;
    }
    
    .gap-2 {
        gap: 0.5rem !important;
    }
    
    /* Amélioration des badges d'état */
    .badge-success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        color: white;
        padding: 0.4rem 0.6rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }
    
    .badge-danger {
        background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
        color: white;
        padding: 0.4rem 0.6rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }
    
    /* Nouveaux styles */
    .text-gradient {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
        display: inline-block;
    }
    
    .shadow-sm {
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
    }
    
    .table {
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .thead-dark {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }
    
    .thead-dark th {
        color: white !important;
        border: none !important;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }
    
    /* Animation pour les boutons */
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .btn:after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
    }
    
    .btn:hover:after {
        animation: ripple 1s ease-out;
    }
    
    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }
        100% {
            transform: scale(20, 20);
            opacity: 0;
        }
    }
</style>

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
    
    // Enregistrement des filtres dans la session pour l'exportation
    $_SESSION['filters']['appareil'] = $filters;

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
    
    // Affichage du message d'information pour l'exportation
    if (isset($_SESSION['info'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info']) . '</div>';
        unset($_SESSION['info']); // On supprime le message après l'avoir affiché
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
                                    <span class="badge-success">Opérationnel</span>
                                <?php else : ?>
                                    <span class="badge-danger">En panne</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="../../includes/gestion.php?action=modifier&id=<?= $appareil['id_appareil'] ?>" 
                                       class="btn btn-gradient-edit btn-sm" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../includes/gestion.php?action=supprimer&id=<?= $appareil['id_appareil'] ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet appareil ?')" 
                                       class="btn btn-gradient-danger btn-sm" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Script pour la fonctionnalité de copie des noms -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('copyNames').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Récupérer tous les modèles d'appareils
                const rows = document.querySelectorAll('table tbody tr');
                let names = [];
                
                rows.forEach(row => {
                    const type = row.cells[0].textContent.trim();
                    const model = row.cells[1].textContent.trim();
                    names.push(type + ' - ' + model);
                });
                
                // Copier dans le presse-papiers
                const textToCopy = names.join('\n');
                navigator.clipboard.writeText(textToCopy)
                    .then(() => {
                        alert('Les noms des appareils ont été copiés dans le presse-papiers');
                    })
                    .catch(err => {
                        console.error('Erreur lors de la copie :', err);
                        alert('Erreur lors de la copie dans le presse-papiers');
                    });
            });
        });
        </script>
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
