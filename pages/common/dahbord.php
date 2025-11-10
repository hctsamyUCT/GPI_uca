<?php
require_once '../../includes/functions.php';

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

// Niveau 2 car nous sommes dans pages/common
afficherHeader('Tableau de Bord GPI', 2);

// Connexion à la base de données
$pdo = connectDB();

// Récupération des statistiques des unités centrales
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN etat = 'Operationnel' THEN 1 ELSE 0 END) as operationnelles, 
                     SUM(CASE WHEN etat = 'En panne' THEN 1 ELSE 0 END) as en_panne FROM unite WHERE supprime_le IS NULL");
$unites = $stmt->fetch();

// Récupération des statistiques des périphériques
$stmt = $pdo->query("SELECT COUNT(*) as total, 
                     SUM(CASE WHEN type = 'Ecran' THEN 1 ELSE 0 END) as ecrans,
                     SUM(CASE WHEN type = 'Clavier' THEN 1 ELSE 0 END) as claviers,
                     SUM(CASE WHEN type = 'Souris' THEN 1 ELSE 0 END) as souris
                     FROM peripherique WHERE supprime_le IS NULL");
$peripheriques = $stmt->fetch();

// Récupération des statistiques des appareils
$stmt = $pdo->query("SELECT COUNT(*) as total, 
                     SUM(CASE WHEN type = 'Imprimante' THEN 1 ELSE 0 END) as imprimantes,
                     SUM(CASE WHEN type = 'Projecteur' THEN 1 ELSE 0 END) as projecteurs
                     FROM appareils WHERE supprime_le IS NULL");
$appareils = $stmt->fetch();

// Récupération des statistiques des postes
$stmt = $pdo->query("SELECT COUNT(*) as total, 
                     SUM(CASE WHEN etat = 'opérationnel' OR etat = 'Opérationnel' THEN 1 ELSE 0 END) as operationnels,
                     SUM(CASE WHEN etat = 'en_panne' OR etat = 'En panne' THEN 1 ELSE 0 END) as en_panne
                     FROM poste");
$postes = $stmt->fetch();

// Récupération des statistiques des tickets
$stmt = $pdo->query("SELECT COUNT(*) as total, 
                     SUM(CASE WHEN etat = 'Ouvert' THEN 1 ELSE 0 END) as ouverts,
                     SUM(CASE WHEN etat = 'En cours' THEN 1 ELSE 0 END) as en_cours,
                     SUM(CASE WHEN etat LIKE '%Résolu%' OR etat LIKE '%résolu%' OR etat LIKE '%fermé%' THEN 1 ELSE 0 END) as resolus
                     FROM tickets");
$tickets = $stmt->fetch();

// Récupération des statistiques des tickets par catégorie
$stmt = $pdo->query("SELECT categorie, COUNT(*) as total FROM tickets GROUP BY categorie");
$tickets_par_categorie = $stmt->fetchAll();

// Récupération des statistiques des tickets par priorité
$stmt = $pdo->query("SELECT priorite, COUNT(*) as total FROM tickets GROUP BY priorite");
$tickets_par_priorite = $stmt->fetchAll();

// Récupération de l'évolution des tickets (derniers mois)
$stmt = $pdo->query("SELECT DATE_FORMAT(date_creation, '%Y-%m') as mois, COUNT(*) as total FROM tickets GROUP BY mois ORDER BY mois DESC LIMIT 6");
$evolution_tickets = $stmt->fetchAll();

// Ajout du lien vers le fichier CSS du tableau de bord (niveau 2)
echo '<link rel="stylesheet" href="../../assets/css/dashboard.css">';
?>

<!-- Conteneur principal avec classe main-content pour une meilleure séparation -->
<div class="main-content" style="padding-top: 80px;">
    <h1 class="text-center mb-4"><i class="bi bi-speedometer2 mr-2"></i>Gestion du Parc Informatique</h1>
    
    <!-- Section Vue d'ensemble -->
    <div class="dashboard-card">
        <h3>Vue d'ensemble</h3>
        <div class="row">
            <!-- Unités Centrales -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-item stat-unites">
                    <div class="stat-number count-animate" data-count="<?= (int)$unites['total'] ?>">0</div>
                    <div class="stat-label"><i class="bi bi-cpu mr-1"></i>Unités Centrales</div>
                    <div class="sub-stats">
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-success"></span><span>Opér.</span></div>
                            <span class="sub-stat-number text-success"><?= $unites['operationnelles'] ?></span>
                        </div>
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-danger"></span><span>Panne</span></div>
                            <span class="sub-stat-number text-danger"><?= $unites['en_panne'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Périphériques -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-item stat-peripheriques">
                    <div class="stat-number count-animate" data-count="<?= (int)$peripheriques['total'] ?>">0</div>
                    <div class="stat-label"><i class="bi bi-keyboard mr-1"></i>Périphériques</div>
                    <div class="sub-stats">
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-info"></span><span>Écrans</span></div>
                            <span class="sub-stat-number"><?= $peripheriques['ecrans'] ?></span>
                        </div>
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-warning"></span><span>Claviers</span></div>
                            <span class="sub-stat-number"><?= $peripheriques['claviers'] ?></span>
                        </div>
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-success"></span><span>Souris</span></div>
                            <span class="sub-stat-number"><?= $peripheriques['souris'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Appareils -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-item stat-appareils">
                    <div class="stat-number count-animate" data-count="<?= (int)$appareils['total'] ?>">0</div>
                    <div class="stat-label"><i class="bi bi-hdd-network mr-1"></i>Appareils</div>
                    <div class="sub-stats">
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-info"></span><span>Imprim.</span></div>
                            <span class="sub-stat-number"><?= $appareils['imprimantes'] ?></span>
                        </div>
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-warning"></span><span>Proj.</span></div>
                            <span class="sub-stat-number"><?= $appareils['projecteurs'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Postes de travail -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-item stat-postes">
                    <div class="stat-number count-animate" data-count="<?= (int)$postes['total'] ?>">0</div>
                    <div class="stat-label"><i class="bi bi-pc-display mr-1"></i>Postes de travail</div>
                    <div class="sub-stats">
                        <div class="sub-stat">
                            <div class="badge"><span class="dot dot-success"></span><span>Configurés</span></div>
                            <span class="sub-stat-number text-success"><?= $postes['operationnels'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section Tickets et Support -->
    <div class="dashboard-card">
        <h3>Tickets et Support</h3>
        <div class="row">
            <!-- État des tickets -->
            <div class="col-md-4">
                        <div class="card h-100">
                    <div class="card-header bg-light"><i class="bi bi-ticket-perforated mr-1"></i>État des tickets</div>
                    <div class="card-body">
                        <div class="ticket-total mb-4"><?= $tickets['total'] ?></div>
                        <div class="ticket-status">
                            <div class="ticket-status-item">
                                <span class="status-dot status-open"></span>
                                <span>Ouverts: <b><?= $tickets['ouverts'] ?></b></span>
                            </div>
                            <div class="ticket-status-item">
                                <span class="status-dot status-in-progress"></span>
                                <span>En cours: <b><?= $tickets['en_cours'] ?></b></span>
                            </div>
                            <div class="ticket-status-item">
                                <span class="status-dot status-resolved"></span>
                                <span>Résolus: <b><?= $tickets['resolus'] ?></b></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Répartition par catégorie -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-light"><i class="bi bi-pie-chart mr-1"></i>Types de problèmes</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Répartition par priorité -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-light"><i class="bi bi-bullseye mr-1"></i>Niveaux d'urgence</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="prioritiesChart"></canvas>
                        </div>
                        <div class="chart-legend mt-2">
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #e74a3b;"></span>
                                <span>Haute</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #f6c23e;"></span>
                                <span>Moyenne</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #1cc88a;"></span>
                                <span>Faible</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section Graphiques et Analyses -->
    <div class="dashboard-card">
        <h3>Graphiques et Analyses</h3>
        <div class="row">
            <!-- Évolution des tickets -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-light"><i class="bi bi-graph-up-arrow mr-1"></i>Évolution des tickets</div>
                    <div class="card-body">
                        <div class="evolution-chart">
                            <canvas id="ticketsEvolutionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ajout de Chart.js pour les graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Données pour les graphiques
document.addEventListener('DOMContentLoaded', function() {
    // Données pour l'évolution des tickets
    const evolutionCtx = document.getElementById('ticketsEvolutionChart').getContext('2d');
    const evolutionChart = new Chart(evolutionCtx, {
        type: 'line',
        data: {
            labels: [<?php 
                $months = [];
                $counts = [];
                foreach ($evolution_tickets as $ticket) {
                    // Convertir le format YYYY-MM en nom de mois
                    $date = DateTime::createFromFormat('Y-m', $ticket['mois']);
                    if ($date) {
                        $months[] = '"' . $date->format('M Y') . '"';
                        $counts[] = $ticket['total'];
                    }
                }
                echo implode(', ', $months);
            ?>],
            datasets: [{
                label: 'Nombre de tickets',
                data: [<?php echo implode(', ', $counts); ?>],
                backgroundColor: 'rgba(78, 115, 223, 0.2)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                tension: 0.3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 10,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    usePointStyle: true
                }
            }
        }
    });
    
    // Données pour les catégories de tickets
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChart = new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php 
                $categories = [];
                $categoryData = [];
                foreach ($tickets_par_categorie as $cat) {
                    if (!empty($cat['categorie'])) {
                        $categories[] = '"' . $cat['categorie'] . '"';
                        $categoryData[] = $cat['total'];
                    }
                }
                echo implode(', ', $categories);
            ?>],
            datasets: [{
                data: [<?php echo implode(', ', $categoryData); ?>],
                backgroundColor: ['#4e73df', '#f6c23e', '#36b9cc', '#1cc88a', '#e74a3b'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            cutout: '65%'
        }
    });
    
    // Données pour les priorités de tickets
    const prioritiesCtx = document.getElementById('prioritiesChart').getContext('2d');
    const prioritiesChart = new Chart(prioritiesCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php 
                $priorities = [];
                $priorityData = [];
                $priorityColors = [];
                
                // Tableau associatif pour stocker les données par priorité
                $priorityMap = [];
                foreach ($tickets_par_priorite as $prio) {
                    if (!empty($prio['priorite'])) {
                        $priorityMap[$prio['priorite']] = $prio['total'];
                    }
                }
                
                // Définir l'ordre des priorités et leurs couleurs associées
                $priorityOrder = ['Haute', 'Moyenne', 'Faible'];
                $colorMap = [
                    'Haute' => '#e74a3b',    // Rouge pour priorité haute
                    'Moyenne' => '#f6c23e',  // Jaune pour priorité moyenne
                    'Faible' => '#1cc88a'    // Vert pour priorité faible
                ];
                
                // Parcourir les priorités dans l'ordre souhaité
                foreach ($priorityOrder as $priority) {
                    if (isset($priorityMap[$priority])) {
                        $priorities[] = '"' . $priority . '"';
                        $priorityData[] = $priorityMap[$priority];
                        $priorityColors[] = $colorMap[$priority];
                    }
                }
                
                echo implode(', ', $priorities);
            ?>],
            datasets: [{
                data: [<?php echo implode(', ', $priorityData); ?>],
                backgroundColor: [<?php echo "'" . implode("', '", $priorityColors) . "'"; ?>],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php
afficherFooter();
?>
<script>
// Animation des compteurs
document.addEventListener('DOMContentLoaded', function() {
	const counters = document.querySelectorAll('.count-animate');
	const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
	const animateCounter = (el) => {
		const target = parseInt(el.getAttribute('data-count') || '0', 10);
		const duration = 900;
		let start = null;
		const step = (ts) => {
			if (!start) start = ts;
			const progress = Math.min((ts - start) / duration, 1);
			el.textContent = Math.floor(easeOutCubic(progress) * target);
			if (progress < 1) requestAnimationFrame(step);
		};
		requestAnimationFrame(step);
	};
	if ('IntersectionObserver' in window) {
		const io = new IntersectionObserver((entries, obs) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					animateCounter(entry.target);
					obs.unobserve(entry.target);
				}
			});
		}, { threshold: 0.4 });
		counters.forEach(c => io.observe(c));
	} else {
		counters.forEach(animateCounter);
	}
});
</script>