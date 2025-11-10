<?php // Balise d'ouverture PHP pour l'exécution côté serveur

/*------------------------------
/* INCLUSION DES FONCTIONS
/*------------------------------
*/
require_once 'functions.php';
// Charge le fichier functions.php une seule fois :
// - Chemin relatif depuis le dossier courant
// - Contient les fonctions connectDB(), afficherHeader(), etc.

/*------------------------------
/* VÉRIFICATION D'AUTHENTIFICATION
/*------------------------------
*/
// Configuration de la session
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    // Redirection explicite avec un message d'erreur
    header('Location: ../index.php?erreur=Vous devez être connecté pour accéder à cette page');
    exit();
}

// Vérifier si l'utilisateur a le rôle approprié (admin ou technicien)
if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 2) {
    // Redirection vers la page de création de ticket pour les utilisateurs standard
    header('Location: ../pages/user/create_ticket.php?erreur=Accès non autorisé');
    exit();
}

/*------------------------------
/* RÉCUPÉRATION DES PARAMÈTRES
/*------------------------------
*/
$action = $_GET['action'] ?? '';
// Opérateur null coalescent PHP 7+ :
// - Récupère 'action' dans $_GET ou chaîne vide si non défini
// - Possibles valeurs : 'ajouter', 'modifier', 'supprimer'

$id = $_GET['id'] ?? null;
// Récupération optionnelle de l'ID :
// - null si non présent (cas de l'ajout)
// - Doit être validé avant usage (type et existence en base)

/*------------------------------
/* CONNEXION BASE DE DONNÉES
/*------------------------------
*/
try { // Début du bloc de gestion d'erreurs
    $pdo = connectDB();
    // Établit la connexion MySQL via PDO :
    // - Utilise les paramètres de functions.php
    // - Retourne un objet PDO partagé

/*------------------------------
/* GESTION DES ACTIONS
/*------------------------------
*/
    switch ($action) { // Structure de contrôle multi-cas

        /* CAS AJOUT D'APPAREILS
        */
        case 'ajouter':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Vérification de la méthode HTTP :
                // - POST uniquement pour l'envoi de formulaire
                // - Protection contre l'accès direct par URL

                /* VALIDATION DES CHAMPS
                */
                $required = ['type', 'modele', 'etat', 'id_marque'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        header('Location: ../pages/common/gestion_appareil.php?erreur=Champs manquants');
                        exit;
                    }
                }

                // Génération automatique du numéro d'inventaire
                $type = $_POST['type'];
                $annee = date('Y');
                $prefix = ($type === 'Imprimante') ? 'IMP' : 'PRJ';
                // Chercher le dernier numéro séquentiel pour ce type et cette année
                $stmt = $pdo->prepare("SELECT numero_inventaire FROM appareils WHERE type = ? AND numero_inventaire LIKE ? ORDER BY id_appareil DESC LIMIT 1");
                $like = $prefix . '/%/' . $annee;
                $stmt->execute([$type, $like]);
                $last = $stmt->fetchColumn();
                if ($last) {
                    // Extraire le NNN
                    $parts = explode('/', $last);
                    $num = isset($parts[1]) ? intval($parts[1]) : 0;
                    $nextNum = str_pad($num + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $nextNum = '001';
                }
                $numero_inventaire = $prefix . '/' . $nextNum . '/' . $annee;

                // Vérification unicité
                $check = $pdo->prepare("SELECT id_appareil FROM appareils WHERE numero_inventaire = ?");
                $check->execute([$numero_inventaire]);
                if ($check->fetch()) {
                    header('Location: ../pages/common/gestion_appareil.php?erreur=Conflit sur le numéro d\'inventaire généré, réessayez.');
                    exit;
                }

                // Vérification marque existante
                $checkMarque = $pdo->prepare("SELECT id_marque FROM marque WHERE id_marque = ?");
                $checkMarque->execute([$_POST['id_marque']]);
                if (!$checkMarque->fetch()) {
                    header('Location: ../pages/common/gestion_appareil.php?erreur=Marque invalide');
                    exit;
                }

                // Vérification salle (si renseignée)
                if (!empty($_POST['id_salle'])) {
                    $checkSalle = $pdo->prepare("SELECT id_salle FROM salles WHERE id_salle = ?");
                    $checkSalle->execute([$_POST['id_salle']]);
                    if (!$checkSalle->fetch()) {
                        header('Location: ../pages/common/gestion_appareil.php?erreur=Salle invalide');
                        exit;
                    }
                }

                // Insertion en base
                $stmt = $pdo->prepare("INSERT INTO appareils
                    (type, modele, numero_inventaire, etat, id_marque, id_salle)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['type'],
                    $_POST['modele'],
                    $numero_inventaire,
                    $_POST['etat'],
                    $_POST['id_marque'],
                    ($_POST['id_salle'] !== '') ? $_POST['id_salle'] : null
                ]);

                // Message de succès avec le numéro généré
                $_SESSION['info'] = "Appareil ajouté avec succès. Numéro d'inventaire généré : <strong>" . htmlspecialchars($numero_inventaire) . "</strong>";
                header('Location: ../pages/common/gestion_appareil.php?succes=1');
                exit;
            }

            /* AFFICHAGE FORMULAIRE VIDE
            */
            afficherHeader('Ajouter un appareil'); // Génère le <head> et début de body
            echo '<h2>Ajouter un nouvel appareil</h2>'; // Titre spécifique
            afficherMessage(); // Affiche erreurs/succès
            afficherFormulaire(); // Formulaire sans données
            afficherFooter(); // Fermeture des balises HTML
            break;

        /* CAS MODIFICATION
        */
        case 'modifier':
            if (!$id) {
                header('Location: ../pages/common/gestion_appareil.php?erreur=ID manquant');
                exit;
            }

            /* RÉCUPÉRATION APPAREIL EXISTANT
            */
            $stmt = $pdo->prepare("SELECT * FROM appareils WHERE id_appareil = ?");
            $stmt->execute([$id]); // ID passé en paramètre
            $appareil = $stmt->fetch(); // Récupère une seule ligne

            if (!$appareil) { // Si aucun résultat
                header('Location: ../pages/common/gestion_appareil.php?erreur=Appareil introuvable');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Mêmes validations que pour l'ajout...

                /* MISE À JOUR EN BASE
                */
                $stmt = $pdo->prepare("UPDATE appareils SET
                    type = ?,
                    modele = ?,
                    numero_inventaire = ?,
                    etat = ?,
                    id_marque = ?,
                    id_salle = ?
                    WHERE id_appareil = ?"); // 7 paramètres
                $stmt->execute([
                    $_POST['type'],
                    $_POST['modele'],
                    $_POST['numero_inventaire'],
                    $_POST['etat'],
                    $_POST['id_marque'],
                    ($_POST['id_salle'] !== '') ? $_POST['id_salle'] : null,
                    $id // ID WHERE clause
                ]);

                // Gestion de l'affectation au poste
                if (!empty($_POST['id_poste'])) {
                    // Vérifier si le poste existe
                    $checkPoste = $pdo->prepare("SELECT id_poste FROM poste WHERE id_poste = ?");
                    $checkPoste->execute([$_POST['id_poste']]);
                    
                    if ($checkPoste->fetch()) {
                        // Mettre à jour ou créer la liaison
                        $stmt = $pdo->prepare("REPLACE INTO poste_unite (id_poste, id_unite) VALUES (?, ?)");
                        $stmt->execute([$_POST['id_poste'], $id]);
                    }
                } else {
                    // Supprimer toute affectation existante
                    $stmt = $pdo->prepare("DELETE FROM poste_unite WHERE id_unite = ?");
                    $stmt->execute([$id]);
                }

                header('Location: ../pages/common/gestion_appareil.php?succes=1');
                exit;
            }

            /* AFFICHAGE FORMULAIRE PRÉREMPLI
            */
            afficherHeader('Modifier un appareil');
            echo '<h2>Modifier l\'appareil</h2>'; // Apostrophe échappée
            afficherMessage();
            afficherFormulaire($appareil); // Passe les données existantes
            afficherFooter();
            break;

        /* CAS SUPPRESSION
        */
        case 'supprimer':
            if (isset($_GET['id'])) {
                try {
                    // Vérifier si l'appareil est associé à des tickets
                    $stmt = $pdo->prepare("
                        SELECT t.id_ticket, t.description, t.date_creation, t.etat, u.prenom, u.nom
                        FROM tickets t
                        LEFT JOIN users u ON t.id_user = u.id_user
                        WHERE t.id_appareil = ?
                    ");
                    $stmt->execute([$_GET['id']]);
                    $tickets = $stmt->fetchAll();

                    if (!empty($tickets)) {
                        // Construire le message d'erreur avec les détails des tickets
                        $message = "Impossible de supprimer l'appareil car il est associé aux tickets suivants :<br><ul>";
                        foreach ($tickets as $ticket) {
                            $message .= "<li>Ticket #" . $ticket['id_ticket'] . 
                                       " (créé le " . date('d/m/Y H:i', strtotime($ticket['date_creation'])) . 
                                       ")<br>" .
                                       "État : " . $ticket['etat'] . "<br>" .
                                       "Créé par : " . htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']) . "<br>" .
                                       "Description : " . htmlspecialchars($ticket['description']) . "</li>";
                        }
                        $message .= "</ul>";
                        
                        header('Location: ../pages/common/Gestion_appareil.php?erreur=' . urlencode($message));
                        exit();
                    }

                    // Si pas de tickets associés, procéder à la suppression
                    $stmt = $pdo->prepare("DELETE FROM appareils WHERE id_appareil = ?");
                    $stmt->execute([$_GET['id']]);

                    header('Location: ../pages/common/Gestion_appareil.php?succes=1');
                    exit();
                } catch (Exception $e) {
                    header('Location: ../pages/common/Gestion_appareil.php?erreur=' . urlencode($e->getMessage()));
                    exit();
                }
            }
            break;

        case 'ajouter_unite':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation des champs requis
                $required = ['ram', 'processeur', 'disque_dur_1_type', 'disque_dur_1_capacite'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        header('Location: ../pages/common/gestion_unit.php?erreur=Tous les champs obligatoires doivent être remplis');
                        exit;
                    }
                }

                // Génération automatique du numéro d'inventaire pour unité centrale
                $annee = !empty($_POST['date_achat']) ? date('Y', strtotime($_POST['date_achat'])) : date('Y');
                $prefix = 'UNT';
                // Chercher le dernier numéro séquentiel pour cette année
                $stmt = $pdo->prepare("SELECT numero_inventaire FROM unite WHERE numero_inventaire LIKE ? ORDER BY id_unite DESC LIMIT 1");
                $like = $prefix . '/%/' . $annee;
                $stmt->execute([$like]);
                $last = $stmt->fetchColumn();
                if ($last) {
                    $parts = explode('/', $last);
                    $num = isset($parts[1]) ? intval($parts[1]) : 0;
                    $nextNum = str_pad($num + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $nextNum = '001';
                }
                $numero_inventaire = $prefix . '/' . $nextNum . '/' . $annee;

                // Vérification du numéro d'inventaire unique
                $check = $pdo->prepare("SELECT id_unite FROM unite WHERE numero_inventaire = ?");
                $check->execute([$numero_inventaire]);
                if ($check->fetch()) {
                    header('Location: ../pages/common/gestion_unit.php?erreur=Conflit sur le numéro d\'inventaire généré, réessayez.');
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO unite (
                    numero_inventaire, ram, processeur, 
                    disque_dur_1_type, disque_dur_1_capacite,
                    disque_dur_2_type, disque_dur_2_capacite,
                    etat, date_achat
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $numero_inventaire,
                    $_POST['ram'],
                    $_POST['processeur'],
                    $_POST['disque_dur_1_type'],
                    $_POST['disque_dur_1_capacite'],
                    !empty($_POST['disque_dur_2_type']) ? $_POST['disque_dur_2_type'] : null,
                    !empty($_POST['disque_dur_2_capacite']) ? $_POST['disque_dur_2_capacite'] : null,
                    $_POST['etat'] ?? 'Operationnel',
                    !empty($_POST['date_achat']) ? $_POST['date_achat'] : null
                ]);

                $id_unite = $pdo->lastInsertId();

                if (!empty($_POST['id_poste'])) {
                    // Vérifier l'existence du poste
                    $checkPoste = $pdo->prepare("SELECT id_poste FROM poste WHERE id_poste = ?");
                    $checkPoste->execute([$_POST['id_poste']]);
                    
                    if ($checkPoste->fetch()) {
                        // Lier l'unité au poste
                        $stmt = $pdo->prepare("INSERT INTO poste_unite (id_poste, id_unite) VALUES (?, ?)");
                        $stmt->execute([$_POST['id_poste'], $id_unite]);
                    }
                }

                $_SESSION['info'] = "Unité ajoutée avec succès. Numéro d'inventaire généré : <strong>" . htmlspecialchars($numero_inventaire) . "</strong>";
                // Stocker le message de succès dans la session pour la modification
                $_SESSION['message'] = [
                    'text' => "L'unité <strong>" . htmlspecialchars($_POST['numero_inventaire']) . "</strong> a été modifiée avec succès.",
                    'type' => 'success'
                ];
                
                header('Location: ../pages/common/gestion_unit.php');
                exit;
            }

            afficherHeader('Ajouter une unité');
            echo '<h2>Ajouter une nouvelle unité</h2>';
            ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="gestion.php?action=ajouter_unite" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label for="ram">RAM :</label>
                            <select class="form-control" id="ram" name="ram" required>
                                <option value="">Sélectionnez la RAM</option>
                                <option value="2 GB">2 GB</option>
                                <option value="4 GB">4 GB</option>
                                <option value="8 GB">8 GB</option>
                                <option value="16 GB">16 GB</option>
                                <option value="32 GB">32 GB</option>
                                <option value="64 GB">64 GB    </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="processeur">Processeur :</label>
                            <select class="form-control" id="processeur" name="processeur" required>
                                <option value="">Sélectionnez le processeur</option>
                                <option value="INTEL_CORE_I3">Intel Core i3</option>
                                <option value="INTEL_CORE_I5">Intel Core i5</option>
                                <option value="INTEL_CORE_I7">Intel Core i7</option>
                                <option value="INTEL_CORE_I9">Intel Core i9</option>
                                <option value="INTEL_XEON">Intel Xeon</option>
                                <option value="INTEL_PENTIUM">Intel Pentium</option>
                                <option value="INTEL_CELERON">Intel Celeron</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_1_type">Type du premier disque :</label>
                            <select class="form-control" id="disque_dur_1_type" name="disque_dur_1_type" required>
                                <option value="">Sélectionnez le type</option>
                                <option value="SSD">SSD</option>
                                <option value="HDD">HDD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_1_capacite">Capacité du premier disque :</label>
                            <select class="form-control" id="disque_dur_1_capacite" name="disque_dur_1_capacite" required>
                                <option value="">Sélectionnez la capacité</option>
                                <option value="70 GB">70 GB</option>
                                <option value="120 GB">120 GB</option>
                                <option value="500 GB">500 GB</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_2_type">Type du second disque (optionnel) :</label>
                            <select class="form-control" id="disque_dur_2_type" name="disque_dur_2_type">
                                <option value="">Aucun</option>
                                <option value="SSD">SSD</option>
                                <option value="HDD">HDD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_2_capacite">Capacité du second disque (optionnel) :</label>
                            <select class="form-control" id="disque_dur_2_capacite" name="disque_dur_2_capacite">
                                <option value="">Aucun</option>
                                <option value="70 GB">70 GB</option>
                                <option value="120 GB">120 GB</option>
                                <option value="500 GB">500 GB</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="etat">État :</label>
                            <select class="form-control" id="etat" name="etat">
                                <option value="Operationnel">Opérationnel</option>
                                <option value="En panne">En panne</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_achat">Date d'achat :</label>
                            <input type="date" class="form-control" id="date_achat" name="date_achat">
                        </div>

                        <div class="form-group">
                            <label for="id_poste">Affecter au poste :</label>
                            <select class="form-control" id="id_poste" name="id_poste">
                                <option value="">Non affecté</option>
                                <?php
                                $postes = $pdo->prepare("
                                    SELECT p.id_poste, p.nom_poste, s.nom_salle
                                    FROM poste p
                                    JOIN salles s ON p.id_salle = s.id_salle
                                    WHERE p.id_poste NOT IN (
                                        SELECT id_poste
                                        FROM poste_unite
                                    )
                                    ORDER BY p.nom_poste
                                ");
                                $postes->execute([]); // Pas de paramètre à passer ici pour l'ajout
                                while ($poste = $postes->fetch()) {
                                ?>
                                <option value="<?= $poste['id_poste'] ?>">
                                    <?= htmlspecialchars($poste['nom_poste'] . ' - ' . $poste['nom_salle']) ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                            <a href="../pages/common/gestion_unit.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php
            afficherFooter();
            break;

        case 'modifier_unite':
            if (!$id) {
                header('Location: ../pages/common/gestion_unit.php?erreur=ID manquant');
                exit;
            }

            $stmt = $pdo->prepare("SELECT u.*, pu.id_poste 
                                  FROM unite u
                                  LEFT JOIN poste_unite pu ON u.id_unite = pu.id_unite 
                                  WHERE u.id_unite = ?");
            $stmt->execute([$id]);
            $unite = $stmt->fetch();

            if (!$unite) {
                header('Location: ../pages/common/gestion_unit.php?erreur=Unité introuvable');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation des champs requis
                $required = ['ram', 'processeur', 'disque_dur_1_type', 'disque_dur_1_capacite'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        header('Location: ../pages/common/gestion_unit.php?erreur=Tous les champs obligatoires doivent être remplis');
                        exit;
                    }
                }

                // Vérification du numéro d'inventaire unique (sauf pour l'unité en cours)
                $check = $pdo->prepare("SELECT id_unite FROM unite WHERE numero_inventaire = ? AND id_unite != ?");
                $check->execute([$_POST['numero_inventaire'], $id]);
                if ($check->fetch()) {
                    header('Location: ../pages/common/gestion_unit.php?erreur=Ce numéro d\'inventaire existe déjà');
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE unite SET 
                    ram = ?,
                    processeur = ?,
                    disque_dur_1_type = ?,
                    disque_dur_1_capacite = ?,
                    disque_dur_2_type = ?,
                    disque_dur_2_capacite = ?,
                    etat = ?,
                    date_achat = ?
                    WHERE id_unite = ?");

                $stmt->execute([
                    $_POST['ram'],
                    $_POST['processeur'],
                    $_POST['disque_dur_1_type'],
                    $_POST['disque_dur_1_capacite'],
                    !empty($_POST['disque_dur_2_type']) ? $_POST['disque_dur_2_type'] : null,
                    !empty($_POST['disque_dur_2_capacite']) ? $_POST['disque_dur_2_capacite'] : null,
                    $_POST['etat'] ?? 'Operationnel',
                    !empty($_POST['date_achat']) ? $_POST['date_achat'] : null,
                    $id
                ]);

                // Gestion de l'affectation au poste
                if (!empty($_POST['id_poste'])) {
                    // Vérifier si le poste existe
                    $checkPoste = $pdo->prepare("SELECT id_poste FROM poste WHERE id_poste = ?");
                    $checkPoste->execute([$_POST['id_poste']]);
                    
                    if ($checkPoste->fetch()) {
                        // Mettre à jour ou créer la liaison
                        $stmt = $pdo->prepare("REPLACE INTO poste_unite (id_poste, id_unite) VALUES (?, ?)");
                        $stmt->execute([$_POST['id_poste'], $id]);
                    }
                } else {
                    // Supprimer toute affectation existante
                    $stmt = $pdo->prepare("DELETE FROM poste_unite WHERE id_unite = ?");
                    $stmt->execute([$id]);
                }

                header('Location: ../pages/common/gestion_unit.php?succes=1');
                exit;
            }

            afficherHeader('Modifier une unité');
            echo '<h2>Modifier l\'unité</h2>';
            ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="gestion.php?action=modifier_unite&id=<?= $unite['id_unite'] ?>" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label for="ram">RAM :</label>
                            <select class="form-control" id="ram" name="ram" required>
                                <option value="">Sélectionnez la RAM</option>
                                <option value="2 GB" <?= $unite['ram'] === '2 GB' ? 'selected' : '' ?>>2 GB</option>
                                <option value="4 GB" <?= $unite['ram'] === '4 GB' ? 'selected' : '' ?>>4 GB</option>
                                <option value="8 GB" <?= $unite['ram'] === '8 GB' ? 'selected' : '' ?>>8 GB</option>
                                <option value="16 GB" <?= $unite['ram'] === '16 GB' ? 'selected' : '' ?>>16 GB</option>
                                <option value="32 GB" <?= $unite['ram'] === '32 GB' ? 'selected' : '' ?>>32 GB</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="processeur">Processeur :</label>
                            <select class="form-control" id="processeur" name="processeur" required>
                                <option value="">Sélectionnez le processeur</option>
                                <option value="INTEL_CORE_I3" <?= $unite['processeur'] === 'INTEL_CORE_I3' ? 'selected' : '' ?>>Intel Core i3</option>
                                <option value="INTEL_CORE_I5" <?= $unite['processeur'] === 'INTEL_CORE_I5' ? 'selected' : '' ?>>Intel Core i5</option>
                                <option value="INTEL_CORE_I7" <?= $unite['processeur'] === 'INTEL_CORE_I7' ? 'selected' : '' ?>>Intel Core i7</option>
                                <option value="INTEL_CORE_I9" <?= $unite['processeur'] === 'INTEL_CORE_I9' ? 'selected' : '' ?>>Intel Core i9</option>
                                <option value="INTEL_XEON" <?= $unite['processeur'] === 'INTEL_XEON' ? 'selected' : '' ?>>Intel Xeon</option>
                                <option value="INTEL_PENTIUM" <?= $unite['processeur'] === 'INTEL_PENTIUM' ? 'selected' : '' ?>>Intel Pentium</option>
                                <option value="INTEL_CELERON" <?= $unite['processeur'] === 'INTEL_CELERON' ? 'selected' : '' ?>>Intel Celeron</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_1_type">Type du premier disque :</label>
                            <select class="form-control" id="disque_dur_1_type" name="disque_dur_1_type" required>
                                <option value="">Sélectionnez le type</option>
                                <option value="SSD" <?= $unite['disque_dur_1_type'] === 'SSD' ? 'selected' : '' ?>>SSD</option>
                                <option value="HDD" <?= $unite['disque_dur_1_type'] === 'HDD' ? 'selected' : '' ?>>HDD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_1_capacite">Capacité du premier disque :</label>
                            <select class="form-control" id="disque_dur_1_capacite" name="disque_dur_1_capacite" required>
                                <option value="">Sélectionnez la capacité</option>
                                <option value="70 GB" <?= $unite['disque_dur_1_capacite'] === '70 GB' ? 'selected' : '' ?>>70 GB</option>
                                <option value="120 GB" <?= $unite['disque_dur_1_capacite'] === '120 GB' ? 'selected' : '' ?>>120 GB</option>
                                <option value="500 GB" <?= $unite['disque_dur_1_capacite'] === '500 GB' ? 'selected' : '' ?>>500 GB</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_2_type">Type du second disque (optionnel) :</label>
                            <select class="form-control" id="disque_dur_2_type" name="disque_dur_2_type">
                                <option value="">Aucun</option>
                                <option value="SSD" <?= $unite['disque_dur_2_type'] === 'SSD' ? 'selected' : '' ?>>SSD</option>
                                <option value="HDD" <?= $unite['disque_dur_2_type'] === 'HDD' ? 'selected' : '' ?>>HDD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="disque_dur_2_capacite">Capacité du second disque (optionnel) :</label>
                            <select class="form-control" id="disque_dur_2_capacite" name="disque_dur_2_capacite">
                                <option value="">Aucun</option>
                                <option value="70 GB" <?= $unite['disque_dur_2_capacite'] === '70 GB' ? 'selected' : '' ?>>70 GB</option>
                                <option value="120 GB" <?= $unite['disque_dur_2_capacite'] === '120 GB' ? 'selected' : '' ?>>120 GB</option>
                                <option value="500 GB" <?= $unite['disque_dur_2_capacite'] === '500 GB' ? 'selected' : '' ?>>500 GB</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="etat">État :</label>
                            <select class="form-control" id="etat" name="etat">
                                <option value="Operationnel" <?= $unite['etat'] === 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                <option value="En panne" <?= $unite['etat'] === 'En panne' ? 'selected' : '' ?>>En panne</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_achat">Date d'achat :</label>
                            <input type="date" class="form-control" id="date_achat" name="date_achat" value="<?= $unite['date_achat'] ? date('Y-m-d', strtotime($unite['date_achat'])) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="id_poste">Affecter au poste :</label>
                            <select class="form-control" id="id_poste" name="id_poste">
                                <option value="">Non affecté</option>
                                <?php
                                $postes = $pdo->prepare("
                                    SELECT p.id_poste, p.nom_poste, s.nom_salle
                                    FROM poste p
                                    JOIN salles s ON p.id_salle = s.id_salle
                                    WHERE p.id_poste NOT IN (
                                        SELECT id_poste
                                        FROM poste_unite
                                    )
                                    ORDER BY p.nom_poste
                                ");
                                $postes->execute([]); // Pas de paramètre à passer ici pour l'ajout
                                while ($poste = $postes->fetch()) {
                                ?>
                                <option value="<?= $poste['id_poste'] ?>" <?= $poste['id_poste'] == $unite['id_poste'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($poste['nom_poste'] . ' - ' . $poste['nom_salle']) ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Modifier</button>
                            <a href="../pages/common/gestion_unit.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php
            afficherFooter();
            break;

        case 'supprimer_unite':
            if (!$id) {
                header('Location: ../pages/common/gestion_unit.php?erreur=ID manquant');
                exit;
            }

            // Récupérer le numéro d'inventaire avant suppression pour le message
            $stmt = $pdo->prepare("SELECT numero_inventaire FROM unite WHERE id_unite = ?");
            $stmt->execute([$id]);
            $unite = $stmt->fetch();
            
            if (!$unite) {
                header('Location: ../pages/common/gestion_unit.php?erreur=Unité introuvable');
                exit;
            }
            
            $numero_inventaire = $unite['numero_inventaire'];

            // Suppression physique pour activer le trigger d'archivage
            $stmt = $pdo->prepare("DELETE FROM unite WHERE id_unite = ?");
            $stmt->execute([$id]);

            // Stocker le message de succès dans la session avec le numéro d'inventaire
            $_SESSION['message'] = [
                'text' => "L'unité <strong>" . htmlspecialchars($numero_inventaire) . "</strong> a été supprimée avec succès.",
                'type' => 'success'
            ];

            header('Location: ../pages/common/gestion_unit.php');
            exit;

        case 'ajouter_peripherique':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation des champs requis
                $required = ['type', 'id_marque'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        header('Location: ../pages/common/Gestion_peripherique.php?erreur=Champs obligatoires manquants');
                        exit;
                    }
                }

                // Détermination du préfixe selon le type
                $type = $_POST['type'];
                $annee = date('Y');
                switch (strtolower($type)) {
                    case 'écran':
                    case 'ecran':
                        $prefix = 'ECR';
                        break;
                    case 'souris':
                        $prefix = 'SRI';
                        break;
                    case 'clavier':
                        $prefix = 'CLA';
                        break;
                    default:
                        header('Location: ../pages/common/Gestion_peripherique.php?erreur=Type de périphérique inconnu');
                        exit;
                }
                // Année d'acquisition (si on veut la prendre depuis une date, il faut ajouter le champ au formulaire)
                // Ici, on prend l'année courante
                // Chercher le dernier numéro séquentiel pour ce type et cette année
                $stmt = $pdo->prepare("SELECT numero_inventaire FROM peripherique WHERE type = ? AND numero_inventaire LIKE ? ORDER BY id_peripherique DESC LIMIT 1");
                $like = $prefix . '/%/' . $annee;
                $stmt->execute([$type, $like]);
                $last = $stmt->fetchColumn();
                if ($last) {
                    $parts = explode('/', $last);
                    $num = isset($parts[1]) ? intval($parts[1]) : 0;
                    $nextNum = str_pad($num + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $nextNum = '001';
                }
                $numero_inventaire = $prefix . '/' . $nextNum . '/' . $annee;

                // Vérification numéro inventaire unique
                $check = $pdo->prepare("SELECT id_peripherique FROM peripherique WHERE numero_inventaire = ?");
                $check->execute([$numero_inventaire]);
                if ($check->fetch()) {
                    header('Location: ../pages/common/Gestion_peripherique.php?erreur=Conflit sur le numéro d\'inventaire généré, réessayez.');
                    exit;
                }

                // Insertion du périphérique
                $stmt = $pdo->prepare("INSERT INTO peripherique (type, id_marque, numero_inventaire, etat) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $type,
                    $_POST['id_marque'],
                    $numero_inventaire,
                    $_POST['etat'] ?? 'Operationnel'
                ]);

                // Gestion de l'affectation au poste si spécifié
                $id_peripherique = $pdo->lastInsertId();
                if (!empty($_POST['id_poste'])) {
                    $stmt = $pdo->prepare("INSERT INTO poste_peripherique (id_poste, id_peripherique) VALUES (?, ?)");
                    $stmt->execute([$_POST['id_poste'], $id_peripherique]);
                }

                $_SESSION['info'] = "Périphérique ajouté avec succès. Numéro d'inventaire généré : <strong>" . htmlspecialchars($numero_inventaire) . "</strong>";
                header('Location: ../pages/common/Gestion_peripherique.php?succes=1');
                exit;
            }

            // Affichage du formulaire d'ajout
            afficherHeader('Ajouter un périphérique');
            ?>
            <div class="container mt-4">
                <h2>Ajouter un nouveau périphérique</h2>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="gestion.php?action=ajouter_peripherique" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="type">Type :</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">Sélectionnez le type</option>
                                    <option value="Clavier">Clavier</option>
                                    <option value="Souris">Souris</option>
                                    <option value="Ecran">Écran</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_marque">Marque :</label>
                                <select class="form-control" id="id_marque" name="id_marque" required>
                                    <option value="">Sélectionnez la marque</option>
                                    <?php 
                                    $marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque");
                                    while ($marque = $marques->fetch()) {
                                        echo '<option value="'.$marque['id_marque'].'">'
                                            .htmlspecialchars($marque['nom_marque']).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="etat">État :</label>
                                <select class="form-control" id="etat" name="etat">
                                    <option value="Operationnel">Opérationnel</option>
                                    <option value="En panne">En panne</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_poste">Affecter au poste (optionnel) :</label>
                                <select class="form-control" id="id_poste" name="id_poste">
                                    <option value="">Non affecté</option>
                                    <?php 
                                    $postes = $pdo->query("
                                        SELECT p.id_poste, p.nom_poste, s.nom_salle 
                                        FROM poste p
                                        JOIN salles s ON p.id_salle = s.id_salle
                                        ORDER BY p.nom_poste
                                    ");
                                    while ($poste = $postes->fetch()) {
                                        echo '<option value="'.$poste['id_poste'].'">'
                                            .htmlspecialchars($poste['nom_poste'].' - '.$poste['nom_salle']).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Ajouter</button>
                                <a href="../pages/common/Gestion_peripherique.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
            afficherFooter();
            break;

        case 'modifier_peripherique':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                header('Location: ../pages/common/Gestion_peripherique.php?erreur=ID manquant');
                exit;
            }

            // Récupération données existantes
            $stmt = $pdo->prepare("
                SELECT p.*, pp.id_poste 
                FROM peripherique p
                LEFT JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique
                WHERE p.id_peripherique = ?
            ");
            $stmt->execute([$id]);
            $periph = $stmt->fetch();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation
                $required = ['type', 'id_marque'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        header("Location: ../pages/common/Gestion_peripherique.php?erreur=Champs obligatoires manquants&id=$id");
                        exit;
                    }
                }

                // Vérification numéro inventaire
                $check = $pdo->prepare("SELECT id_peripherique FROM peripherique WHERE numero_inventaire = ? AND id_peripherique != ?");
                $check->execute([$_POST['numero_inventaire'], $id]);
                if ($check->fetch()) {
                    header("Location: ../pages/common/Gestion_peripherique.php?erreur=Numéro existant&id=$id");
                    exit;
                }

                // Mise à jour
                $stmt = $pdo->prepare("UPDATE peripherique SET
                    type = ?,
                    id_marque = ?,
                    numero_inventaire = ?,
                    etat = ?
                    WHERE id_peripherique = ?
                ");
                
                $stmt->execute([
                    $_POST['type'],
                    $_POST['id_marque'],
                    $_POST['numero_inventaire'],
                    $_POST['etat'] ?? 'Operationnel',
                    $id
                ]);

                // Gestion affectation poste
                if (!empty($_POST['id_poste'])) {
                    $stmt = $pdo->prepare("REPLACE INTO poste_peripherique (id_poste, id_peripherique) VALUES (?, ?)");
                    $stmt->execute([$_POST['id_poste'], $id]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM poste_peripherique WHERE id_peripherique = ?");
                    $stmt->execute([$id]);
                }

                header('Location: ../pages/common/Gestion_peripherique.php?succes=1');
                exit;
            }

            // Affichage formulaire
            afficherHeader('Modifier périphérique');
            echo '<h2>Modifier le périphérique</h2>';
            ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="gestion.php?action=modifier_peripherique&id=<?= $periph['id_peripherique'] ?>" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label>Type :</label>
                            <select class="form-control" name="type" required>
                                <option value="Clavier" <?= $periph['type'] === 'Clavier' ? 'selected' : '' ?>>Clavier</option>
                                <option value="Souris" <?= $periph['type'] === 'Souris' ? 'selected' : '' ?>>Souris</option>
                                <option value="Ecran" <?= $periph['type'] === 'Ecran' ? 'selected' : '' ?>>Écran</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Marque :</label>
                            <select class="form-control" name="id_marque" required>
                                <?php 
                                $marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque");
                                foreach ($marques as $m) {
                                    echo '<option value="'.$m['id_marque'].'" '.($m['id_marque'] == $periph['id_marque'] ? 'selected' : '').'>'
                                        .htmlspecialchars($m['nom_marque']).'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>État :</label>
                            <select class="form-control" name="etat">
                                <option value="Operationnel" <?= $periph['etat'] === 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                <option value="En panne" <?= $periph['etat'] === 'En panne' ? 'selected' : '' ?>>En panne</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Affecter au poste :</label>
                            <select class="form-control" name="id_poste">
                                <option value="">Non affecté</option>
                                <?php
                                $postes = $pdo->prepare("
                                    SELECT p.id_poste, p.nom_poste, s.nom_salle
                                    FROM poste p
                                    JOIN salles s ON p.id_salle = s.id_salle
                                    WHERE p.id_poste NOT IN (
                                        SELECT id_poste
                                        FROM poste_unite
                                    )
                                    ORDER BY p.nom_poste
                                ");
                                $postes->execute([]); // Pas de paramètre à passer ici pour l'ajout
                                while ($poste = $postes->fetch()) {
                                ?>
                                <option value="<?= $poste['id_poste'] ?>" <?= $poste['id_poste'] == $periph['id_poste'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($poste['nom_poste'] . ' - ' . $poste['nom_salle']) ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="../pages/common/Gestion_peripherique.php" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
            <?php
            afficherFooter();
            break;

        case 'supprimer_peripherique':
            if (!$id) {
                header('Location: ../pages/common/Gestion_peripherique.php?erreur=ID manquant');
                exit;
            }

            // Suppression physique pour activer le trigger d'archivage
            $stmt = $pdo->prepare("DELETE FROM peripherique WHERE id_peripherique = ?");
            $stmt->execute([$id]);

            header('Location: ../pages/common/Gestion_peripherique.php?succes=1');
            exit;
            break;

        case 'ajouter_poste':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation
                if (empty($_POST['nom_poste']) || empty($_POST['id_salle'])) {
                    header('Location: ../pages/common/gestion_poste.php?erreur=Champs obligatoires manquants');
                    exit;
                }

                // Insertion poste
                $stmt = $pdo->prepare("INSERT INTO poste (nom_poste, id_salle, etat) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['nom_poste'], $_POST['id_salle'], $_POST['etat']]);
                $id_poste = $pdo->lastInsertId();

                // Gestion périphériques
                if (!empty($_POST['peripheriques'])) {
                    $stmt = $pdo->prepare("INSERT INTO poste_peripherique (id_poste, id_peripherique) VALUES (?, ?)");
                    foreach ($_POST['peripheriques'] as $id_periph) {
                        $stmt->execute([$id_poste, $id_periph]);
                    }
                }

                // Gestion unité
                if (!empty($_POST['id_unite'])) {
                    $stmt = $pdo->prepare("INSERT INTO poste_unite (id_poste, id_unite) VALUES (?, ?)");
                    $stmt->execute([$id_poste, $_POST['id_unite']]);
                }

                header('Location: ../pages/common/gestion_poste.php?succes=1');
                exit;
            }

            // Affichage formulaire
            $salles = $pdo->query("SELECT * FROM salles ORDER BY nom_salle")->fetchAll();
            // En mode ajout, ne pas référencer $id (non défini). On propose uniquement les périphériques non déjà affectés.
            $peripheriques = $pdo->query("\n                SELECT p.* \n                FROM peripherique p\n                LEFT JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique\n                WHERE p.supprime_le IS NULL \n                AND pp.id_poste IS NULL\n            ")->fetchAll();
            // Proposer uniquement les unités non déjà affectées à un poste
            $unites = $pdo->query("\n                SELECT u.*\n                FROM unite u\n                LEFT JOIN poste_unite pu ON u.id_unite = pu.id_unite\n                WHERE u.supprime_le IS NULL\n                AND pu.id_poste IS NULL\n                ORDER BY u.numero_inventaire\n            ")->fetchAll();

            afficherHeader('Ajouter un poste');
            echo '<h2>Ajouter un nouveau poste</h2>';
            ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="gestion.php?action=ajouter_poste">
                        <div class="form-group">
                            <label>Nom du poste :</label>
                            <input type="text" name="nom_poste" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Salle :</label>
                            <select name="id_salle" class="form-control" required>
                                <?php foreach ($salles as $salle): ?>
                                    <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom_salle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                         <div class="form-group">
                            <label>État :</label>
                            <select name="etat" class="form-control" required>
                                <option value="opérationnel">Opérationnel</option>
                                <option value="en_panne">En panne</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Périphériques :</label>
                            <select name="peripheriques[]" class="form-control" multiple style="height: 200px">
                                <?php foreach ($peripheriques as $periph): ?>
                                    <option value="<?= $periph['id_peripherique'] ?>">
                                        <?= htmlspecialchars($periph['type'] . ' - ' . $periph['numero_inventaire']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Unité centrale :</label>
                            <select name="id_unite" class="form-control">
                                <option value="">Non affectée</option>
                                <?php foreach ($unites as $unite): ?>
                                    <option value="<?= $unite['id_unite'] ?>">
                                        <?= htmlspecialchars($unite['numero_inventaire']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="../pages/common/gestion_poste.php" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
            <?php
            afficherFooter();
            break;

        case 'modifier_poste':
            if (!$id) {
                header('Location: ../pages/common/gestion_poste.php?erreur=ID manquant');
                exit;
            }

            // Correction de la récupération des données existantes
            $stmt = $pdo->prepare("SELECT * FROM poste WHERE id_poste = ?");
            $stmt->execute([$id]);
            $poste = $stmt->fetch();

            if (!$poste) {
                header('Location: ../pages/common/gestion_poste.php?erreur=Poste introuvable');
                exit;
            }

            // Récupération des périphériques et unités sélectionnés
            $periphSelected = $pdo->prepare("SELECT id_peripherique FROM poste_peripherique WHERE id_poste = ?");
            $periphSelected->execute([$id]);
            $periphSelected = $periphSelected->fetchAll(PDO::FETCH_COLUMN);

            $uniteStmt = $pdo->prepare("SELECT id_unite FROM poste_unite WHERE id_poste = ?");
            $uniteStmt->execute([$id]);
            $uniteSelected = $uniteStmt->fetchColumn();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation
                if (empty($_POST['nom_poste']) || empty($_POST['id_salle'])) {
                    header('Location: ../pages/common/gestion_poste.php?erreur=Champs obligatoires manquants');
                    exit;
                }

                // Mise à jour poste
                $stmt = $pdo->prepare("UPDATE poste SET nom_poste = ?, id_salle = ?, etat = ? WHERE id_poste = ?");
                $stmt->execute([$_POST['nom_poste'], $_POST['id_salle'],$_POST['etat'], $id]);

                // Mise à jour périphériques
                $pdo->prepare("DELETE FROM poste_peripherique WHERE id_poste = ?")->execute([$id]);
                if (!empty($_POST['peripheriques'])) {
                    $stmt = $pdo->prepare("INSERT INTO poste_peripherique (id_poste, id_peripherique) VALUES (?, ?)");
                    foreach ($_POST['peripheriques'] as $id_periph) {
                        $stmt->execute([$id, $id_periph]);
                    }
                }

                // Mise à jour unité
                $pdo->prepare("DELETE FROM poste_unite WHERE id_poste = ?")->execute([$id]);
                if (!empty($_POST['id_unite'])) {
                    $pdo->prepare("INSERT INTO poste_unite (id_poste, id_unite) VALUES (?, ?)")->execute([$id, $_POST['id_unite']]);
                }

                header('Location: ../pages/common/gestion_poste.php?succes=1');
                exit;
            }

            // Récupération des données pour le formulaire
            $salles = $pdo->query("SELECT * FROM salles ORDER BY nom_salle")->fetchAll();
            $peripheriques = $pdo->query("
                SELECT p.* 
                FROM peripherique p
                LEFT JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique
                WHERE p.supprime_le IS NULL AND pp.id_poste IS NULL
            ")->fetchAll();
            $unites = $pdo->query("
                SELECT u.* 
                FROM unite u 
                LEFT JOIN poste_unite pu ON u.id_unite = pu.id_unite 
                WHERE u.supprime_le IS NULL 
                AND (pu.id_poste IS NULL OR pu.id_poste = $id)
                ORDER BY u.numero_inventaire")->fetchAll();

            afficherHeader('Modifier un poste');
            ?>
            <div class="container mt-4">
                <h2>Modifier le poste</h2>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="gestion.php?action=modifier_poste&id=<?= $id ?>">
                            <div class="form-group">
                                <label>Nom du poste :</label>
                                <input type="text" name="nom_poste" class="form-control" value="<?= htmlspecialchars($poste['nom_poste']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Salle :</label>
                                <select name="id_salle" class="form-control" required>
                                    <?php foreach ($salles as $salle): ?>
                                        <option value="<?= $salle['id_salle'] ?>" <?= $salle['id_salle'] == $poste['id_salle'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($salle['nom_salle']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                             <div class="form-group">
                                <label>État :</label>
                                <select name="etat" class="form-control" required>
                                    <option value="opérationnel" <?= $poste['etat'] === 'opérationnel' ? 'selected' : '' ?>>Opérationnel</option>
                                    <option value="en_panne" <?= $poste['etat'] === 'en_panne' ? 'selected' : '' ?>>En panne</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Périphériques :</label>
                                <select name="peripheriques[]" class="form-control" multiple style="height: 200px">
                                    <?php foreach ($peripheriques as $periph): ?>
                                        <option value="<?= $periph['id_peripherique'] ?>" <?= in_array($periph['id_peripherique'], $periphSelected) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($periph['type'] . ' - ' . $periph['numero_inventaire']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Unité centrale :</label>
                                <select name="id_unite" class="form-control">
                                    <option value="">Non affectée</option>
                                    <?php foreach ($unites as $unite): ?>
                                        <option value="<?= $unite['id_unite'] ?>" <?= $unite['id_unite'] == $uniteSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($unite['numero_inventaire']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="../pages/common/gestion_poste.php" class="btn btn-secondary">Annuler</a>
                        </form>
                    </div>
                </div>
            </div>
            <?php
            afficherFooter();
            break;

        case 'supprimer_poste':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                header('Location: ../pages/common/gestion_poste.php?erreur=ID manquant');
                exit;
            }

            // Suppression (les relations seront supprimées via ON DELETE CASCADE)
            $pdo->prepare("DELETE FROM poste WHERE id_poste = ?")->execute([$id]);
            header('Location: ../pages/common/gestion_poste.php?succes=1');
            break;

        case 'restaurer_appareil':
            try {
                restaurerAppareil($_GET['id_archive']);
                header('Location: ../pages/common/gestion_appareil.php?succes=1');
            } catch (Exception $e) {
                header('Location: ../pages/archives/archives_appareil.php?erreur=' . urlencode($e->getMessage()));
            }
            exit;

        case 'restaurer_peripherique':
            try {
                restaurerPeripherique($_GET['id_archive']);
                header('Location: ../pages/common/gestion_peripherique.php?succes=1');
            } catch (Exception $e) {
                header('Location: ../pages/archives/archives_peripherique.php?erreur=' . urlencode($e->getMessage()));
            }
            exit;

        case 'restaurer_unite':
            try {
                restaurerUnite($_GET['id_archive']);
                header('Location: ../pages/common/gestion_unit.php?succes=1');
            } catch (Exception $e) {
                header('Location: ../pages/archives/archives_unite.php?erreur=' . urlencode($e->getMessage()));
            }
            exit;

        /* CAS PAR DÉFAUT
        */
        default:
            header('Location: ../pages/common/gestion_appareil.php'); // Redirection si action inconnue
            exit;
    }

    if (isset($_GET['erreur'])) {
        echo '<div class="alert alert-danger">'.htmlspecialchars($_GET['erreur']).'</div>';
    }
} catch (PDOException $e) { // Capture des erreurs SQL
    if (strpos($e->getMessage(), 'Un périphérique de ce type est déjà affecté') !== false) {
        header('Location: ../pages/common/gestion_poste.php?erreur=Un périphérique de ce type est déjà affecté à ce poste');
    } 
    elseif (strpos($e->getMessage(), 'Ce poste a déjà une unité centrale') !== false) {
        header('Location: ../pages/common/gestion_poste.php?erreur=Ce poste a déjà une unité centrale');
    }
    elseif (strpos($e->getMessage(), 'Cette unité centrale est déjà affectée') !== false) {
        header('Location: ../pages/common/gestion_poste.php?erreur=Cette unité centrale est déjà affectée à un autre poste');
    }
    else {
        header('Location: ../pages/common/gestion_poste.php?erreur=Erreur lors de l\'affectation');
    }
    exit;
}
?> <!-- Fin du script PHP -->
