<?php
// Inclusion des dépendances
require_once 'functions.php';
require_once 'filters.php';

// Configuration de la session
configureSession();

// Débogage des paramètres reçus
error_log('Export - Paramètres reçus: ' . print_r($_GET, true));
error_log('Export - Session: ' . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    header('Location: ../index.php?erreur=Vous devez être connecté');
    exit();
}

// Récupération des paramètres
$type = $_GET['type'] ?? '';
$source = $_GET['source'] ?? '';
$all = isset($_GET['all']) ? (bool)$_GET['all'] : false;

// Vérification des paramètres
if (empty($type) || empty($source)) {
    header('Location: ../index.php?erreur=Paramètres d\'exportation manquants');
    exit();
}

// Connexion à la base de données
try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupération des données selon la source
    $data = [];
    $filename = '';
    $headers = [];
    
    switch ($source) {
        case 'appareils':
            // Construction de la requête
            $query = "SELECT a.type, a.modele, a.numero_serie, m.nom_marque, s.nom_salle, a.etat 
                      FROM appareils a
                      LEFT JOIN marque m ON a.id_marque = m.id_marque
                      LEFT JOIN salles s ON a.id_salle = s.id_salle
                      WHERE 1=1";
            
            // Récupération des filtres si on n'exporte pas tout
            if (!$all && isset($_SESSION['filters']['appareil'])) {
                $filters = $_SESSION['filters']['appareil'];
                if (function_exists('buildFilterQuery')) {
                    $filterData = buildFilterQuery('appareil', $filters);
                    if (isset($filterData['clause'])) {
                        $query .= ($filterData['clause'] ? ' AND ' . substr($filterData['clause'], 6) : '');
                        $params = $filterData['params'] ?? [];
                    } else {
                        $params = [];
                        error_log('La fonction buildFilterQuery ne retourne pas de clause');
                    }
                } else {
                    // Fallback si la fonction n'existe pas
                    $params = [];
                    error_log('La fonction buildFilterQuery n\'existe pas');
                }
            } else {
                $params = [];
            }
            
            // Exécution de la requête
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Remplacer les salles vides par 'Non assigné'
            foreach ($data as &$row) {
                if (empty($row['nom_salle'])) {
                    $row['nom_salle'] = 'Non assigné';
                }
            }
            unset($row);
            
            // Configuration de l'export
            $filename = 'export_appareils_' . date('Y-m-d');
            $headers = ['Type', 'Modèle', 'Numéro de série', 'Marque', 'Salle', 'État'];
            break;
            
        case 'unite':
            // Construction de la requête
            $query = "SELECT u.numero_inventaire, u.ram, u.processeur, u.disque_dur_1_type, 
                      u.disque_dur_1_capacite, u.disque_dur_2_type, u.disque_dur_2_capacite, 
                      s.nom_salle, u.etat, p.nom_poste
                      FROM unite u
                      LEFT JOIN poste_unite pu ON u.id_unite = pu.id_unite
                      LEFT JOIN poste p ON pu.id_poste = p.id_poste
                      LEFT JOIN salles s ON p.id_salle = s.id_salle
                      WHERE u.supprime_le IS NULL";
            
            // Récupération des filtres si on n'exporte pas tout
            if (!$all && isset($_SESSION['filters']['unite'])) {
                $filters = $_SESSION['filters']['unite'];
                // Débogage des filtres
                error_log('Filtres unite: ' . print_r($filters, true));
                if (function_exists('buildFilterQuery')) {
                    $filterData = buildFilterQuery('unite', $filters);
                    error_log('FilterData: ' . print_r($filterData, true));
                    if (isset($filterData['clause'])) {
                        $query .= ($filterData['clause'] ? ' AND ' . substr($filterData['clause'], 6) : '');
                        $params = $filterData['params'] ?? [];
                    } else {
                        $params = [];
                        error_log('La fonction buildFilterQuery ne retourne pas de clause');
                    }
                } else {
                    // Fallback si la fonction n'existe pas
                    $params = [];
                    error_log('La fonction buildFilterQuery n\'existe pas');
                }
            } else {
                $params = [];
                error_log('Aucun filtre trouvé pour unite');
            }
            
            // Exécution de la requête
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Configuration de l'export
            $filename = 'export_unites_' . date('Y-m-d');
            $headers = ['N° Inventaire', 'RAM', 'Processeur', 'Type Disque 1', 'Capacité Disque 1', 
                       'Type Disque 2', 'Capacité Disque 2', 'Salle', 'État', 'Poste'];
            break;
            
        case 'peripherique':
            // Construction de la requête
            $query = "SELECT p.numero_inventaire, p.type, m.nom_marque, s.nom_salle, p.etat, po.nom_poste
                      FROM peripherique p
                      LEFT JOIN marque m ON p.id_marque = m.id_marque
                      LEFT JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique
                      LEFT JOIN poste po ON pp.id_poste = po.id_poste
                      LEFT JOIN salles s ON po.id_salle = s.id_salle
                      WHERE p.supprime_le IS NULL";
            
            // Récupération des filtres si on n'exporte pas tout
            if (!$all && isset($_SESSION['filters']['peripherique'])) {
                $filters = $_SESSION['filters']['peripherique'];
                if (function_exists('buildFilterQuery')) {
                    $filterData = buildFilterQuery('peripherique', $filters);
                    if (isset($filterData['clause'])) {
                        $query .= ($filterData['clause'] ? ' AND ' . substr($filterData['clause'], 6) : '');
                        $params = $filterData['params'] ?? [];
                    } else {
                        $params = [];
                        error_log('La fonction buildFilterQuery ne retourne pas de clause');
                    }
                } else {
                    // Fallback si la fonction n'existe pas
                    $params = [];
                    error_log('La fonction buildFilterQuery n\'existe pas');
                }
            } else {
                $params = [];
            }
            
            // Exécution de la requête
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Configuration de l'export
            $filename = 'export_peripheriques_' . date('Y-m-d');
            $headers = ['N° Inventaire', 'Type', 'Marque', 'Salle', 'État', 'Poste'];
            break;
            
        case 'poste':
            // Vérification de la structure de la table poste
            $checkColumnStmt = $pdo->query("SHOW COLUMNS FROM poste LIKE 'supprime_le'");
            $hasSupprimeLe = $checkColumnStmt->rowCount() > 0;
            error_log('La colonne supprime_le existe dans la table poste: ' . ($hasSupprimeLe ? 'Oui' : 'Non'));
            
            // Vérification de la colonne id_unite
            $checkIdUniteStmt = $pdo->query("SHOW COLUMNS FROM poste LIKE 'id_unite'");
            $hasIdUnite = $checkIdUniteStmt->rowCount() > 0;
            error_log('La colonne id_unite existe dans la table poste: ' . ($hasIdUnite ? 'Oui' : 'Non'));
            
            // Vérification de la table poste_unite
            $checkPosteUniteStmt = $pdo->query("SHOW TABLES LIKE 'poste_unite'");
            $hasPosteUnite = $checkPosteUniteStmt->rowCount() > 0;
            error_log('La table poste_unite existe: ' . ($hasPosteUnite ? 'Oui' : 'Non'));
            
            // Vérification de la colonne date_ajout
            $checkDateColumnStmt = $pdo->query("SHOW COLUMNS FROM poste LIKE 'date_ajout'");
            $hasDateAjout = $checkDateColumnStmt->rowCount() > 0;
            
            if (!$hasDateAjout) {
                $checkDateCreationStmt = $pdo->query("SHOW COLUMNS FROM poste LIKE 'date_creation'");
                $hasDateCreation = $checkDateCreationStmt->rowCount() > 0;
                error_log('La colonne date_creation existe dans la table poste: ' . ($hasDateCreation ? 'Oui' : 'Non'));
            } else {
                error_log('La colonne date_ajout existe dans la table poste: Oui');
            }
            
            // Vérification de la disponibilité de GROUP_CONCAT
            try {
                $testStmt = $pdo->query("SELECT VERSION() as version");
                $mysqlVersion = $testStmt->fetch(PDO::FETCH_ASSOC)['version'];
                error_log('Version MySQL: ' . $mysqlVersion);
                
                // Construction de la requête
                $dateColumn = $hasDateAjout ? 'p.date_ajout' : ($hasDateCreation ? 'p.date_creation' : 'NOW() as date_ajout');
                
                $query = "SELECT p.nom_poste, s.nom_salle, p.etat, 
                          GROUP_CONCAT(DISTINCT periph.type SEPARATOR ', ') AS peripheriques,";
                
                // Jointure avec unite selon la structure de la base
                if ($hasIdUnite) {
                    $query .= " u.numero_inventaire AS unite,";
                } else if ($hasPosteUnite) {
                    $query .= " u.numero_inventaire AS unite,";
                } else {
                    $query .= " '' AS unite,";
                }
                
                $query .= " $dateColumn as date_ajout
                          FROM poste p
                          LEFT JOIN salles s ON p.id_salle = s.id_salle
                          LEFT JOIN poste_peripherique pp ON p.id_poste = pp.id_poste
                          LEFT JOIN peripherique periph ON pp.id_peripherique = periph.id_peripherique";
                
                // Jointure avec unite selon la structure de la base
                if ($hasIdUnite) {
                    $query .= " LEFT JOIN unite u ON p.id_unite = u.id_unite";
                } else if ($hasPosteUnite) {
                    $query .= " LEFT JOIN poste_unite pu ON p.id_poste = pu.id_poste
                                LEFT JOIN unite u ON pu.id_unite = u.id_unite";
                }
                
                // Ajout de la condition WHERE manquante
                if ($hasSupprimeLe) {
                    $query .= " WHERE p.supprime_le IS NULL";
                    $query .= " AND (periph.id_peripherique IS NULL OR periph.supprime_le IS NULL)";
                    if ($hasIdUnite || $hasPosteUnite) {
                        $query .= " AND (u.id_unite IS NULL OR u.supprime_le IS NULL)";
                    }
                } else {
                    $query .= " WHERE 1=1";
                }
                
                $query .= " GROUP BY p.id_poste";
            } catch (Exception $e) {
                // Fallback si GROUP_CONCAT n'est pas disponible
                error_log('Erreur lors de la vérification de GROUP_CONCAT: ' . $e->getMessage());
                
                $dateColumn = $hasDateAjout ? 'p.date_ajout' : ($hasDateCreation ? 'p.date_creation' : 'NOW() as date_ajout');
                
                $query = "SELECT p.nom_poste, s.nom_salle, p.etat, 
                          '' AS peripheriques,";
                
                // Jointure avec unite selon la structure de la base
                if ($hasIdUnite) {
                    $query .= " u.numero_inventaire AS unite,";
                } else if ($hasPosteUnite) {
                    $query .= " u.numero_inventaire AS unite,";
                } else {
                    $query .= " '' AS unite,";
                }
                
                $query .= " $dateColumn as date_ajout
                          FROM poste p
                          LEFT JOIN salles s ON p.id_salle = s.id_salle";
                
                // Jointure avec unite selon la structure de la base
                if ($hasIdUnite) {
                    $query .= " LEFT JOIN unite u ON p.id_unite = u.id_unite";
                } else if ($hasPosteUnite) {
                    $query .= " LEFT JOIN poste_unite pu ON p.id_poste = pu.id_poste
                                LEFT JOIN unite u ON pu.id_unite = u.id_unite";
                }
            }
            
            // Récupération des filtres si on n'exporte pas tout
            if (!$all && isset($_SESSION['filters']['poste'])) {
                $filters = $_SESSION['filters']['poste'];
                // Débogage des filtres
                error_log('Filtres poste: ' . print_r($filters, true));
                if (function_exists('buildFilterQuery')) {
                    $filterData = buildFilterQuery('poste', $filters);
                    error_log('FilterData poste: ' . print_r($filterData, true));
                    if (isset($filterData['clause'])) {
                        $query .= ($filterData['clause'] ? ' AND ' . substr($filterData['clause'], 6) : '');
                        $params = $filterData['params'] ?? [];
                    } else {
                        $params = [];
                        error_log('La fonction buildFilterQuery ne retourne pas de clause');
                    }
                } else {
                    // Fallback si la fonction n'existe pas
                    $params = [];
                    error_log('La fonction buildFilterQuery n\'existe pas');
                }
            } else {
                $params = [];
                error_log('Aucun filtre trouvé pour poste');
            }
            
            // Exécution de la requête
            $stmt = $pdo->prepare($query);
            error_log('Requête SQL pour postes: ' . $query);
            error_log('Paramètres: ' . print_r($params, true));
            
            try {
                $stmt->execute($params);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Données récupérées pour postes: ' . print_r($data, true));
                
                // Vérifier si des données ont été trouvées
                if (empty($data)) {
                    throw new Exception("Aucune donnée à exporter. Veuillez modifier vos filtres et réessayer.");
                }
            } catch (PDOException $e) {
                error_log('Erreur lors de l\'exécution de la requête pour postes: ' . $e->getMessage());
                throw new Exception("Erreur lors de la récupération des données: " . $e->getMessage());
            }
            
            // Configuration de l'export
            $filename = 'export_postes_' . date('Y-m-d');
            $headers = ['Nom', 'Salle', 'État', 'Périphériques', 'Unité centrale', 'Date création'];
            break;
            
        case 'tickets':
            // Construction de la requête
            $query = "SELECT t.id_ticket, t.categorie, t.priorite, t.etat, t.date_creation, 
                      CONCAT(u.prenom, ' ', u.nom) as demandeur, s.nom_salle, t.description,
                      (SELECT COUNT(*) FROM interventions i WHERE i.id_ticket = t.id_ticket) as nb_interventions
                      FROM tickets t
                      LEFT JOIN users u ON t.id_user = u.id_user
                      LEFT JOIN salles s ON t.id_salle = s.id_salle
                      WHERE 1=1";
            
            // Récupération des filtres si on n'exporte pas tout
            if (!$all && isset($_GET['etat'])) {
                $query .= " AND t.etat = ?";
                $params = [$_GET['etat']];
                
                if (isset($_GET['priorite']) && $_GET['priorite'] != '') {
                    $query .= " AND t.priorite = ?";
                    $params[] = $_GET['priorite'];
                }
                
                if (isset($_GET['categorie']) && $_GET['categorie'] != '') {
                    $query .= " AND t.categorie = ?";
                    $params[] = $_GET['categorie'];
                }
                
                if (isset($_GET['salle']) && $_GET['salle'] != '') {
                    $query .= " AND t.id_salle = ?";
                    $params[] = $_GET['salle'];
                }
            } else {
                $params = [];
            }
            
            // Exécution de la requête
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Configuration de l'export
            $filename = 'export_tickets_' . date('Y-m-d');
            $headers = ['ID', 'Catégorie', 'Priorité', 'État', 'Date de création', 'Demandeur', 'Salle', 'Description', 'Interventions'];
            break;
            
        default:
            header('Location: ../index.php?erreur=Source d\'exportation non valide');
            exit();
    }
    
    // Traitement selon le type d'exportation
    switch ($type) {
        case 'pdf_paysage':
        case 'pdf_portrait':
            $orientation = ($type === 'pdf_paysage') ? 'landscape' : 'portrait';
            exportHTML($data, $headers, $filename, $orientation, $source);
            break;
        case 'csv':
            exportCSV($data, $headers, $filename);
            break;
        case 'xlsx':
            require_once 'xlsxwriter.class.php';
            exportXLSX($data, $headers, $filename);
            break;
        default:
            header('Location: ../index.php?erreur=Type d\'exportation non valide');
            exit();
    }
    
} catch (Exception $e) {
    // Enregistrement de l'erreur dans un fichier de log
    error_log('Erreur d\'exportation: ' . $e->getMessage() . ' dans ' . $e->getFile() . ' à la ligne ' . $e->getLine());
    
    // Redirection avec message d'erreur
    header('Location: ../index.php?erreur=' . urlencode('Erreur lors de l\'exportation : ' . $e->getMessage()));
    exit();
}

/**
 * Exporte les données au format HTML pour impression PDF
 * 
 * @param array $data Les données à exporter
 * @param array $headers Les en-têtes des colonnes
 * @param string $filename Le nom du fichier
 * @param string $orientation L'orientation du document (landscape ou portrait)
 * @param string $source La source des données
 */
function exportHTML($data, $headers, $filename, $orientation = 'portrait', $source = '') {
    // Titre de la page selon la source
    $title = '';
    switch ($source) {
        case 'appareils':
            $title = 'Liste des appareils';
            break;
        case 'unite':
            $title = 'Liste des unités centrales';
            break;
        case 'peripherique':
            $title = 'Liste des périphériques';
            break;
        case 'poste':
            $title = 'Liste des postes';
            break;
        case 'tickets':
            $title = 'Liste des tickets';
            break;
        default:
            $title = 'Exportation de données';
    }
    
    // Début du document HTML
    $html = '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($filename) . '</title>
        <style>
            @page {
                size: ' . $orientation . ';
                margin: 1cm;
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
            }
            h1 {
                text-align: center;
                color: #333;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th {
                background-color: #343a40;
                color: white;
                font-weight: bold;
                text-align: left;
                padding: 8px;
                border: 1px solid #ddd;
            }
            td {
                padding: 8px;
                border: 1px solid #ddd;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .badge-success {
                background-color: #28a745;
                color: white;
                padding: 3px 6px;
                border-radius: 4px;
                font-size: 12px;
            }
            .badge-danger {
                background-color: #dc3545;
                color: white;
                padding: 3px 6px;
                border-radius: 4px;
                font-size: 12px;
            }
            .badge-warning {
                background-color: #ffc107;
                color: #212529;
                padding: 3px 6px;
                border-radius: 4px;
                font-size: 12px;
            }
            .badge-info {
                background-color: #17a2b8;
                color: white;
                padding: 3px 6px;
                border-radius: 4px;
                font-size: 12px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
            .alert-info {
                color: #0c5460;
                background-color: #d1ecf1;
                border-color: #bee5eb;
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
        <script>
            window.onload = function() {
                // Déclencher l\'impression automatiquement
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
            <button onclick="window.close()">Fermer</button>
        </div>
        
        <h1>' . htmlspecialchars($title) . '</h1>';
    
    // Vérifier si des données sont disponibles
    if (empty($data)) {
        $html .= '<div class="alert-info">Aucune donnée disponible pour cette exportation.</div>';
    } else {
        $html .= '<table>
            <thead>
                <tr>';
        
        // Ajout des en-têtes
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        $html .= '</tr>
            </thead>
            <tbody>';
        
        // Ajout des données
        foreach ($data as $row) {
            $html .= '<tr>';
            $rowData = array_values($row);
            
            foreach ($rowData as $index => $cell) {
                // Traitement spécial selon la source et la colonne
                if ($source === 'appareils' && $index === 5) { // Colonne État pour appareils
                    if ($cell === 'Operationnel') {
                        $html .= '<td><span class="badge-success">Opérationnel</span></td>';
                    } else {
                        $html .= '<td><span class="badge-danger">En panne</span></td>';
                    }
                } else if ($source === 'unite' && $index === 8) { // Colonne État pour unités
                    if ($cell === 'Operationnel') {
                        $html .= '<td><span class="badge-success">Opérationnel</span></td>';
                    } else {
                        $html .= '<td><span class="badge-danger">En panne</span></td>';
                    }
                } else if ($source === 'peripherique' && $index === 4) { // Colonne État pour périphériques
                    if ($cell === 'Operationnel') {
                        $html .= '<td><span class="badge-success">Opérationnel</span></td>';
                    } else {
                        $html .= '<td><span class="badge-danger">En panne</span></td>';
                    }
                } else if ($source === 'poste' && $index === 2) { // Colonne État pour postes
                    if ($cell === 'opérationnel') {
                        $html .= '<td><span class="badge-success">Opérationnel</span></td>';
                    } else {
                        $html .= '<td><span class="badge-danger">En panne</span></td>';
                    }
                } else if ($source === 'poste' && $index === 3) { // Colonne Périphériques pour postes
                    if (empty($cell)) {
                        $html .= '<td>Aucun</td>';
                    } else {
                        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                } else if ($source === 'poste' && $index === 4) { // Colonne Unité centrale pour postes
                    if (empty($cell)) {
                        $html .= '<td>Non affectée</td>';
                    } else {
                        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                } else if ($source === 'poste' && $index === 5) { // Colonne Date d'ajout pour postes
                    if (empty($cell)) {
                        $html .= '<td>Non disponible</td>';
                    } else {
                        $html .= '<td>' . date('d/m/Y', strtotime($cell)) . '</td>';
                    }
                } else if ($source === 'tickets' && $index === 3) { // Colonne État pour tickets
                    switch($cell) {
                        case 'Ouvert':
                            $html .= '<td><span class="badge-danger">Ouvert</span></td>';
                            break;
                        case 'En cours':
                            $html .= '<td><span class="badge-warning">En cours</span></td>';
                            break;
                        case 'Probleme Résoulu(fermé)':
                            $html .= '<td><span class="badge-success">Résolu</span></td>';
                            break;
                        case 'probleme non resoulu(fermé)':
                            $html .= '<td><span class="badge-info">Non résolu</span></td>';
                            break;
                        default:
                            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                } else if ($source === 'tickets' && $index === 2) { // Colonne Priorité pour tickets
                    switch($cell) {
                        case 'Haute':
                            $html .= '<td><span class="badge-danger">Haute</span></td>';
                            break;
                        case 'Moyenne':
                            $html .= '<td><span class="badge-warning">Moyenne</span></td>';
                            break;
                        case 'Faible':
                            $html .= '<td><span class="badge-success">Faible</span></td>';
                            break;
                        default:
                            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                } else {
                    $cellText = empty($cell) ? 'Non assigné' : $cell;
                    $html .= '<td>' . htmlspecialchars($cellText) . '</td>';
                }
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
        </table>';
    }
    
    $html .= '<div class="footer">
            Document généré le ' . date('d/m/Y à H:i:s') . ' - GPII
        </div>
    </body>
    </html>';
    
    // Affichage du HTML
    echo $html;
    exit();
}

/**
 * Exporte les données au format CSV
 * 
 * @param array $data Les données à exporter
 * @param array $headers Les en-têtes des colonnes
 * @param string $filename Le nom du fichier
 */
function exportCSV($data, $headers, $filename) {
    // Vérifier si des données sont disponibles
    if (empty($data)) {
        // Redirection avec message d'erreur
        header('Location: ../index.php?erreur=' . urlencode('Aucune donnée disponible pour cette exportation'));
        exit();
    }
    
    // Configuration des en-têtes HTTP
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Création du flux de sortie
    $output = fopen('php://output', 'w');
    
    // Ajout de la marque BOM pour UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Écriture des en-têtes
    fputcsv($output, $headers, ';');
    
    // Écriture des données
    foreach ($data as $row) {
        $rowData = array_values($row);
        
        // Remplacement des valeurs vides
        foreach ($rowData as &$cell) {
            if (empty($cell)) {
                $cell = 'Non assigné';
            }
            
            // Normalisation des états
            if ($cell === 'Operationnel' || $cell === 'opérationnel') {
                $cell = 'Opérationnel';
            } else if ($cell === 'Probleme Résoulu(fermé)') {
                $cell = 'Résolu';
            } else if ($cell === 'probleme non resoulu(fermé)') {
                $cell = 'Non résolu';
            }
        }
        
        fputcsv($output, $rowData, ';');
    }
    
    fclose($output);
}

function exportXLSX($data, $headers, $filename) {
    $writer = new XLSXWriter();
    $writer->writeSheetHeader('Sheet1', array_fill_keys($headers, 'string'));

    foreach ($data as $row) {
        $writer->writeSheetRow('Sheet1', array_values($row));
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer->writeToStdOut();
    exit();
}