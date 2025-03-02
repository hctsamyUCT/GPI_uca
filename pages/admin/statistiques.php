<?php
/*------------------------------
/* INCLUSION DES DÉPENDANCES
/*------------------------------
*/
require_once '../../includes/functions.php';

// Configuration de la session avant d'afficher l'en-tête
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    // Redirection explicite avec un message d'erreur
    header('Location: ../../index.php?erreur=Vous devez être connecté');
    exit();
}

// Vérifier si l'utilisateur a le rôle approprié (admin uniquement)
if ($_SESSION['user_role'] != 1) {
    // Redirection vers la page de création de ticket pour les utilisateurs standard
    header('Location: ../user/create_ticket.php?erreur=Accès non autorisé');
    exit();
}

// Connexion à la base de données
$pdo = connectDB();

/*------------------------------
/* RÉCUPÉRATION DES DONNÉES
/*------------------------------
*/

// 1. Statistiques par type de matériel
$stmt = $pdo->query("
    SELECT type, COUNT(*) as total, 
           SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
           SUM(CASE WHEN etat = 'Operationnel' THEN 1 ELSE 0 END) as operationnel
    FROM appareils
    GROUP BY type
");
$stats_type_appareils = $stmt->fetchAll();

// 2. Statistiques par marque
$stmt = $pdo->query("
    SELECT m.nom_marque, COUNT(*) as total, 
           SUM(CASE WHEN a.etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
           SUM(CASE WHEN a.etat = 'Operationnel' THEN 1 ELSE 0 END) as operationnel
    FROM appareils a
    JOIN marque m ON a.id_marque = m.id_marque
    GROUP BY m.nom_marque
    ORDER BY total DESC
");
$stats_marque = $stmt->fetchAll();

// 3. Statistiques par salle
$stmt = $pdo->query("
    SELECT s.nom_salle, COUNT(*) as total, 
           SUM(CASE WHEN a.etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
           SUM(CASE WHEN a.etat = 'Operationnel' THEN 1 ELSE 0 END) as operationnel
    FROM appareils a
    JOIN salles s ON a.id_salle = s.id_salle
    GROUP BY s.nom_salle
    ORDER BY en_panne DESC
");
$stats_salle = $stmt->fetchAll();

// 4. Statistiques des périphériques
$stmt = $pdo->query("
    SELECT type, COUNT(*) as total, 
           SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
           SUM(CASE WHEN etat = 'Operationnel' THEN 1 ELSE 0 END) as operationnel
    FROM peripherique
    WHERE supprime_le IS NULL
    GROUP BY type
");
$stats_peripheriques = $stmt->fetchAll();

// 5. Statistiques des unités centrales
$stmt = $pdo->query("
    SELECT etat, COUNT(*) as total
    FROM unite
    WHERE supprime_le IS NULL
    GROUP BY etat
");
$stats_unites = $stmt->fetchAll();

// 6. Statistiques globales
$stmt = $pdo->query("
    SELECT 'Appareils' as categorie, COUNT(*) as total, 
           SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne
    FROM appareils
    UNION
    SELECT 'Périphériques' as categorie, COUNT(*) as total,
           SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne
    FROM peripherique
    WHERE supprime_le IS NULL
    UNION
    SELECT 'Unités' as categorie, COUNT(*) as total,
           SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne
    FROM unite
    WHERE supprime_le IS NULL
");
$stats_globales = $stmt->fetchAll();

// 7. Taux de panne par marque (pourcentage d'équipements en panne par marque)
$stmt = $pdo->query("
    SELECT 
        m.nom_marque, 
        COUNT(*) as total,
        SUM(CASE WHEN a.etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN a.etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM appareils a
    JOIN marque m ON a.id_marque = m.id_marque
    GROUP BY m.nom_marque
    HAVING COUNT(*) > 0
    ORDER BY taux_panne DESC
    LIMIT 10
");
$stats_taux_panne_marque = $stmt->fetchAll();

// 8. Statistiques des marques de périphériques en panne
$stmt = $pdo->query("
    SELECT 
        m.nom_marque, 
        COUNT(*) as total,
        SUM(CASE WHEN p.etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN p.etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM peripherique p
    JOIN marque m ON p.id_marque = m.id_marque
    WHERE p.supprime_le IS NULL
    GROUP BY m.nom_marque
    HAVING COUNT(*) > 0
    ORDER BY taux_panne DESC
    LIMIT 10
");
$stats_taux_panne_peripheriques = $stmt->fetchAll();

// 9. Statistiques des pannes par type d'équipement et par marque
$stmt = $pdo->query("
    SELECT 
        a.type,
        m.nom_marque,
        COUNT(*) as total,
        SUM(CASE WHEN a.etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN a.etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM appareils a
    JOIN marque m ON a.id_marque = m.id_marque
    WHERE a.supprime_le IS NULL
    GROUP BY a.type, m.nom_marque
    HAVING en_panne > 0
    ORDER BY taux_panne DESC
");
$stats_panne_type_marque = $stmt->fetchAll();

// 10. Évolution des pannes dans le temps (basé sur les tickets)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(date_creation, '%Y-%m') as mois,
        COUNT(*) as nombre_tickets
    FROM tickets
    WHERE categorie = 'Matériel' OR description LIKE '%panne%'
    GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
    ORDER BY mois ASC
    LIMIT 12
");
$stats_evolution_pannes = $stmt->fetchAll();

// 11. Comparaison des taux de panne par catégorie d'équipement
$stmt = $pdo->query("
    SELECT 
        'Imprimantes' as categorie,
        COUNT(*) as total,
        SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM appareils
    WHERE type = 'Imprimante'
    
    UNION
    
    SELECT 
        'Projecteurs' as categorie,
        COUNT(*) as total,
        SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM appareils
    WHERE type = 'Projecteur'
    
    UNION
    
    SELECT 
        'Périphériques' as categorie,
        COUNT(*) as total,
        SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM peripherique
    WHERE supprime_le IS NULL
    
    UNION
    
    SELECT 
        'Unités centrales' as categorie,
        COUNT(*) as total,
        SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne,
        ROUND((SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as taux_panne
    FROM unite
    WHERE supprime_le IS NULL
    
    ORDER BY taux_panne DESC
");
$stats_comparaison_categories = $stmt->fetchAll();

/*------------------------------
/* CONFIGURATION DE LA PAGE
/*------------------------------
*/
afficherHeader('Statistiques du parc informatique', 2);
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4">Statistiques du parc informatique</h1>
    
    <!-- Débogage des données -->
    <?php if ($_SESSION['user_role'] == 1): ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0">Débogage des données (visible uniquement pour l'admin)</h5>
        </div>
        <div class="card-body">
            <h6>Données pour l'analyse des pannes par type d'équipement et marque:</h6>
            <pre><?php print_r($stats_panne_type_marque); ?></pre>
            
            <h6>Données pour l'évolution des pannes dans le temps:</h6>
            <pre><?php print_r($stats_evolution_pannes); ?></pre>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Inclusion de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <div class="row">
        <!-- Statistiques globales -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Vue d'ensemble du parc</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartGlobal" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Statistiques par type d'appareil -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Répartition par type d'appareil</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTypeAppareils" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Statistiques par marque -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Statistiques par marque</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartMarques" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Statistiques par salle -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Salles avec le plus de pannes</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartSalles" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Statistiques des périphériques -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">État des périphériques</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartPeripheriques" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Statistiques des unités centrales -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">État des unités centrales</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartUnites" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- NOUVELLES STATISTIQUES SUR LES PANNES -->
        <div class="col-12 mb-4">
            <h2 class="text-center">Analyse détaillée des pannes par marque</h2>
            <hr>
        </div>
        
        <!-- Taux de panne par marque (appareils) -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Marques d'appareils avec le plus haut taux de panne</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTauxPanneMarque" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Taux de panne par marque (périphériques) -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Marques de périphériques avec le plus haut taux de panne</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTauxPannePeripheriques" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Pannes par type d'équipement et marque -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Analyse des pannes par type d'équipement et marque</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Type d'équipement</th>
                                    <th>Marque</th>
                                    <th>Total</th>
                                    <th>En panne</th>
                                    <th>Taux de panne (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats_panne_type_marque as $stat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stat['type']) ?></td>
                                    <td><?= htmlspecialchars($stat['nom_marque']) ?></td>
                                    <td><?= $stat['total'] ?></td>
                                    <td><?= $stat['en_panne'] ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                 style="width: <?= $stat['taux_panne'] ?>%;" 
                                                 aria-valuenow="<?= $stat['taux_panne'] ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?= $stat['taux_panne'] ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Évolution des pannes dans le temps -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Évolution des signalements de pannes dans le temps</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartEvolutionPannes" width="800" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Comparaison des taux de panne par catégorie -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Comparaison des taux de panne par catégorie d'équipement</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="chartComparaisonCategories" width="600" height="300"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Total</th>
                                            <th>En panne</th>
                                            <th>Taux (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($stats_comparaison_categories as $stat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($stat['categorie']) ?></td>
                                            <td><?= $stat['total'] ?></td>
                                            <td><?= $stat['en_panne'] ?></td>
                                            <td><strong><?= $stat['taux_panne'] ?>%</strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Préparation des données pour les graphiques
    document.addEventListener('DOMContentLoaded', function() {
        // Couleurs pour les graphiques
        const colors = {
            blue: 'rgba(54, 162, 235, 0.8)',
            green: 'rgba(75, 192, 192, 0.8)',
            red: 'rgba(255, 99, 132, 0.8)',
            orange: 'rgba(255, 159, 64, 0.8)',
            purple: 'rgba(153, 102, 255, 0.8)',
            yellow: 'rgba(255, 205, 86, 0.8)',
            grey: 'rgba(201, 203, 207, 0.8)'
        };
        
        // 1. Graphique global
        new Chart(document.getElementById('chartGlobal'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats_globales, 'categorie')) ?>,
                datasets: [
                    {
                        label: 'Total',
                        data: <?= json_encode(array_column($stats_globales, 'total')) ?>,
                        backgroundColor: colors.blue
                    },
                    {
                        label: 'En panne',
                        data: <?= json_encode(array_column($stats_globales, 'en_panne')) ?>,
                        backgroundColor: colors.red
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'État global du parc informatique'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // 2. Graphique par type d'appareil
        new Chart(document.getElementById('chartTypeAppareils'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($stats_type_appareils, 'type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats_type_appareils, 'total')) ?>,
                    backgroundColor: [colors.blue, colors.green, colors.red, colors.orange, colors.purple]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Répartition des appareils par type'
                    }
                }
            }
        });
        
        // 3. Graphique par marque
        new Chart(document.getElementById('chartMarques'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats_marque, 'nom_marque')) ?>,
                datasets: [
                    {
                        label: 'Opérationnel',
                        data: <?= json_encode(array_column($stats_marque, 'operationnel')) ?>,
                        backgroundColor: colors.green
                    },
                    {
                        label: 'En panne',
                        data: <?= json_encode(array_column($stats_marque, 'en_panne')) ?>,
                        backgroundColor: colors.red
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'État des appareils par marque'
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
        
        // 4. Graphique par salle
        new Chart(document.getElementById('chartSalles'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats_salle, 'nom_salle')) ?>,
                datasets: [{
                    label: 'Appareils en panne',
                    data: <?= json_encode(array_column($stats_salle, 'en_panne')) ?>,
                    backgroundColor: colors.red
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Salles classées par nombre de pannes'
                    }
                }
            }
        });
        
        // 5. Graphique des périphériques
        new Chart(document.getElementById('chartPeripheriques'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats_peripheriques, 'type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats_peripheriques, 'total')) ?>,
                    backgroundColor: [colors.blue, colors.green, colors.orange]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Répartition des périphériques par type'
                    }
                }
            }
        });
        
        // 6. Graphique des unités
        const unitesData = <?= json_encode($stats_unites) ?>;
        const unitesLabels = unitesData.map(item => item.etat);
        const unitesValues = unitesData.map(item => item.total);
        
        new Chart(document.getElementById('chartUnites'), {
            type: 'pie',
            data: {
                labels: unitesLabels,
                datasets: [{
                    data: unitesValues,
                    backgroundColor: [colors.green, colors.red]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'État des unités centrales'
                    }
                }
            }
        });
        
        // 7. Graphique du taux de panne par marque (appareils)
        new Chart(document.getElementById('chartTauxPanneMarque'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats_taux_panne_marque, 'nom_marque')) ?>,
                datasets: [{
                    label: 'Taux de panne (%)',
                    data: <?= json_encode(array_column($stats_taux_panne_marque, 'taux_panne')) ?>,
                    backgroundColor: colors.red,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Marques avec le plus haut taux de panne (%)'
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const data = <?= json_encode($stats_taux_panne_marque) ?>;
                                return `Total: ${data[index].total} | En panne: ${data[index].en_panne}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Taux de panne (%)'
                        }
                    }
                }
            }
        });
        
        // 8. Graphique du taux de panne par marque (périphériques)
        new Chart(document.getElementById('chartTauxPannePeripheriques'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats_taux_panne_peripheriques, 'nom_marque')) ?>,
                datasets: [{
                    label: 'Taux de panne (%)',
                    data: <?= json_encode(array_column($stats_taux_panne_peripheriques, 'taux_panne')) ?>,
                    backgroundColor: colors.orange,
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Marques de périphériques avec le plus haut taux de panne (%)'
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const data = <?= json_encode($stats_taux_panne_peripheriques) ?>;
                                return `Total: ${data[index].total} | En panne: ${data[index].en_panne}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Taux de panne (%)'
                        }
                    }
                }
            }
        });
        
        // 9. Graphique de l'évolution des pannes dans le temps
        new Chart(document.getElementById('chartEvolutionPannes'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($stats_evolution_pannes, 'mois')) ?>,
                datasets: [{
                    label: 'Nombre de signalements de pannes',
                    data: <?= json_encode(array_column($stats_evolution_pannes, 'nombre_tickets')) ?>,
                    fill: false,
                    backgroundColor: colors.red,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Évolution des signalements de pannes par mois'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de tickets'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Mois'
                        }
                    }
                }
            }
        });
        
        // 10. Graphique de la comparaison des taux de panne par catégorie d'équipement
        new Chart(document.getElementById('chartComparaisonCategories'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats_comparaison_categories, 'categorie')) ?>,
                datasets: [{
                    label: 'Taux de panne (%)',
                    data: <?= json_encode(array_column($stats_comparaison_categories, 'taux_panne')) ?>,
                    backgroundColor: colors.red,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Comparaison des taux de panne par catégorie d\'équipement'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Taux de panne (%)'
                        }
                    }
                }
            }
        });
    });
    </script>
</div>

<?php
afficherFooter();
?> 