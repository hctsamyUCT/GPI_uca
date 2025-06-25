<?php
function getFilterForm($type, $filters = []) {
    $pdo = connectDB();
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row">';
    
    // Filtres communs
    $html .= '<div class="col-md-3 mb-3">
        <label>État</label>
        <select name="etat" class="form-control">
            <option value="">Tous</option>
            <option value="opérationnel" ' . (isset($filters['etat']) && $filters['etat'] == 'opérationnel' ? 'selected' : '') . '>Opérationnel</option>
            <option value="en_panne" ' . (isset($filters['etat']) && $filters['etat'] == 'en_panne' ? 'selected' : '') . '>En panne</option>
        </select>
    </div>';

    // Filtres spécifiques selon le type
    switch($type) {
        case 'appareil':
            $marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque")->fetchAll();
            $html .= '<div class="col-md-3 mb-3">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="">Tous</option>
                    <option value="Imprimante" ' . (isset($filters['type']) && $filters['type'] == 'Imprimante' ? 'selected' : '') . '>Imprimante</option>
                    <option value="Projecteur" ' . (isset($filters['type']) && $filters['type'] == 'Projecteur' ? 'selected' : '') . '>Projecteur</option>
                </select>
            </div>';
            
            $html .= '<div class="col-md-3 mb-3">
                <label>Marque</label>
                <select name="id_marque" class="form-control">
                    <option value="">Toutes</option>';
            foreach($marques as $marque) {
                $html .= '<option value="' . $marque['id_marque'] . '" ' . 
                         (isset($filters['id_marque']) && $filters['id_marque'] == $marque['id_marque'] ? 'selected' : '') . '>' . 
                         htmlspecialchars($marque['nom_marque']) . '</option>';
            }
            $html .= '</select></div>';
            break;

        case 'unite':
            $html .= '<div class="col-md-3 mb-3">
                <label>RAM</label>
                <select name="ram" class="form-control">
                    <option value="">Tous</option>
                    <option value="2 GB" ' . (isset($filters['ram']) && $filters['ram'] == '2 GB' ? 'selected' : '') . '>2 GB</option>
                    <option value="4 GB" ' . (isset($filters['ram']) && $filters['ram'] == '4 GB' ? 'selected' : '') . '>4 GB</option>
                    <option value="8 GB" ' . (isset($filters['ram']) && $filters['ram'] == '8 GB' ? 'selected' : '') . '>8 GB</option>
                    <option value="16 GB" ' . (isset($filters['ram']) && $filters['ram'] == '16 GB' ? 'selected' : '') . '>16 GB</option>
                    <option value="32 GB" ' . (isset($filters['ram']) && $filters['ram'] == '32 GB' ? 'selected' : '') . '>32 GB</option>
                </select>
            </div>';

            $html .= '<div class="col-md-3 mb-3">
                <label>Processeur</label>
                <select name="processeur" class="form-control">
                    <option value="">Tous</option>
                    <option value="Intel_i3" ' . (isset($filters['processeur']) && $filters['processeur'] == 'Intel_i3' ? 'selected' : '') . '>Intel i3</option>
                    <option value="Intel_i5" ' . (isset($filters['processeur']) && $filters['processeur'] == 'Intel_i5' ? 'selected' : '') . '>Intel i5</option>
                    <option value="Intel_i7" ' . (isset($filters['processeur']) && $filters['processeur'] == 'Intel_i7' ? 'selected' : '') . '>Intel i7</option>
                    <option value="AMD_Ryzen_5" ' . (isset($filters['processeur']) && $filters['processeur'] == 'AMD_Ryzen_5' ? 'selected' : '') . '>AMD Ryzen 5</option>
                    <option value="AMD_Ryzen_7" ' . (isset($filters['processeur']) && $filters['processeur'] == 'AMD_Ryzen_7' ? 'selected' : '') . '>AMD Ryzen 7</option>
                </select>
            </div>';

            $html .= '<div class="col-md-3 mb-3">
                <label>Type Disque 1</label>
                <select name="disque_dur_1_type" class="form-control">
                    <option value="">Tous</option>
                    <option value="SSD" ' . (isset($filters['disque_dur_1_type']) && $filters['disque_dur_1_type'] == 'SSD' ? 'selected' : '') . '>SSD</option>
                    <option value="HDD" ' . (isset($filters['disque_dur_1_type']) && $filters['disque_dur_1_type'] == 'HDD' ? 'selected' : '') . '>HDD</option>
                </select>
            </div>';
            break;

        case 'peripherique':
            $marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque")->fetchAll();
            $html .= '<div class="col-md-3 mb-3">
                <label>Type de périphérique</label>
                <select name="type" class="form-control">
                    <option value="">Tous</option>
                    <option value="Clavier" ' . (isset($filters['type']) && $filters['type'] == 'Clavier' ? 'selected' : '') . '>Clavier</option>
                    <option value="Souris" ' . (isset($filters['type']) && $filters['type'] == 'Souris' ? 'selected' : '') . '>Souris</option>
                    <option value="Écran" ' . (isset($filters['type']) && $filters['type'] == 'Écran' ? 'selected' : '') . '>Écran</option>
                </select>
            </div>';

            $html .= '<div class="col-md-3 mb-3">
                <label>Marque</label>
                <select name="id_marque" class="form-control">
                    <option value="">Toutes</option>';
            foreach($marques as $marque) {
                $html .= '<option value="' . $marque['id_marque'] . '" ' . 
                         (isset($filters['id_marque']) && $filters['id_marque'] == $marque['id_marque'] ? 'selected' : '') . '>' . 
                         htmlspecialchars($marque['nom_marque']) . '</option>';
            }
            $html .= '</select></div>';
            break;
    }

    // Ajout des salles pour tous les types
    $salles = $pdo->query("SELECT * FROM salles ORDER BY nom_salle")->fetchAll();
    $html .= '<div class="col-md-3 mb-3">
        <label>Salle</label>
        <select name="id_salle" class="form-control">
            <option value="">Toutes</option>';
    foreach($salles as $salle) {
        $html .= '<option value="' . $salle['id_salle'] . '" ' . 
                 (isset($filters['id_salle']) && $filters['id_salle'] == $salle['id_salle'] ? 'selected' : '') . '>' . 
                 htmlspecialchars($salle['nom_salle']) . '</option>';
    }
    $html .= '</select></div>';

    $html .= '<div class="col-12">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="?" class="btn btn-secondary ml-2">Réinitialiser</a>
        </div>
        </form>
    </div>
    </div>';

    return $html;
}

function buildFilterQuery($type, $filters) {
    $where = [];
    $params = [];

    if (!empty($filters['etat'])) {
        switch($type) {
            case 'appareil':
                $where[] = "a.etat = ?";
                break;
            case 'unite':
                $where[] = "u.etat = ?";
                break;
            case 'peripherique':
                $where[] = "p.etat = ?";
                break;
            case 'poste':
                $where[] = "p.etat = ?";
                break;
            default:
                $where[] = "etat = ?";
        }
        $params[] = $filters['etat'];
    }

    if (!empty($filters['id_salle'])) {
        switch($type) {
            case 'appareil':
                $where[] = "a.id_salle = ?";
                break;
            case 'unite':
                $where[] = "p.id_salle = ?";
                break;
            case 'peripherique':
                $where[] = "po.id_salle = ?";
                break;
            case 'poste':
                $where[] = "p.id_salle = ?";
                break;
            default:
                $where[] = "id_salle = ?";
        }
        $params[] = $filters['id_salle'];
    }

    switch($type) {
        case 'appareil':
            if (!empty($filters['type'])) {
                $where[] = "a.type = ?";
                $params[] = $filters['type'];
            }
            if (!empty($filters['id_marque'])) {
                $where[] = "a.id_marque = ?";
                $params[] = $filters['id_marque'];
            }
            break;

        case 'unite':
            if (!empty($filters['ram'])) {
                $where[] = "u.ram = ?";
                $params[] = $filters['ram'];
            }
            if (!empty($filters['processeur'])) {
                $where[] = "u.processeur = ?";
                $params[] = $filters['processeur'];
            }
            if (!empty($filters['disque_dur_1_type'])) {
                $where[] = "u.disque_dur_1_type = ?";
                $params[] = $filters['disque_dur_1_type'];
            }
            break;

        case 'peripherique':
            if (!empty($filters['type'])) {
                $where[] = "p.type = ?";
                $params[] = $filters['type'];
            }
            if (!empty($filters['id_marque'])) {
                $where[] = "p.id_marque = ?";
                $params[] = $filters['id_marque'];
            }
            break;
    }

    $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
    
    return ['clause' => $whereClause, 'params' => $params];
}

function getSearchForm($search = '', $columns = []) {
    if ($search === null) {
        $search = '';
    }
    $html = '<div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Rechercher..." 
                           value="' . htmlspecialchars($search) . '">
                </div>
                <div class="col-md-4">
                    <select name="search_columns[]" class="form-control" multiple>';
    
    foreach($columns as $key => $label) {
        $selected = isset($_GET['search_columns']) && in_array($key, $_GET['search_columns']) ? 'selected' : '';
        $html .= '<option value="' . $key . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }
    
    $html .= '</select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                </div>
            </form>
        </div>
    </div>';
    
    return $html;
} 