# Système de Gestion de Tickets - Documentation

## Modifications apportées pour une approche sans JavaScript

Nous avons modifié le système de gestion de tickets pour qu'il fonctionne sans JavaScript, en utilisant uniquement PHP, HTML et CSS. Voici les principales modifications apportées :

### 1. Formulaire d'ajout d'intervention

- Remplacement de la fenêtre modale JavaScript par un formulaire PHP intégré directement dans la page
- Le formulaire s'affiche uniquement lorsque l'utilisateur clique sur le bouton "Intervention"
- Utilisation de paramètres GET pour déterminer quel ticket est concerné par l'intervention
- Ajout de styles CSS spécifiques pour améliorer l'apparence du formulaire

### 2. Formulaire de modification de ticket

- Remplacement du système de modification basé sur JavaScript par un formulaire PHP intégré
- Le formulaire s'affiche uniquement lorsque l'utilisateur clique sur le bouton "Modifier"
- Utilisation de paramètres GET pour déterminer quel ticket est en cours de modification
- Styles CSS cohérents avec le formulaire d'intervention

### 3. Alertes et notifications

- Remplacement des alertes JavaScript par des alertes PHP statiques
- Suppression des boutons de fermeture qui nécessitaient JavaScript
- Utilisation de variables de session pour afficher les messages de succès et d'erreur

### 4. Styles CSS

- Ajout de styles CSS spécifiques pour les formulaires d'intervention et de modification
- Amélioration de l'apparence des tickets et des badges
- Optimisation de la timeline pour l'historique des interventions

### 5. Scripts JavaScript

- Conservation uniquement des scripts JavaScript minimaux nécessaires pour Bootstrap
- Utilisation de la version "slim" de jQuery pour réduire la dépendance au JavaScript

## Fonctionnalités principales

### Page de gestion des tickets (`manage_tickets.php`)

- Affichage de la liste des tickets avec filtres
- Possibilité de modifier l'état, la priorité et la catégorie d'un ticket
- Ajout d'interventions rapides directement depuis la liste des tickets
- Lien vers la page de détails du ticket

### Page de détails du ticket (`ticket_details.php`)

- Affichage des informations détaillées du ticket
- Historique des interventions
- Formulaire d'ajout d'intervention avec mise à jour de l'état du ticket

## Sécurité

- Validation des données côté serveur
- Protection contre les injections SQL avec des requêtes préparées
- Échappement des données affichées avec `htmlspecialchars()`
- Vérification des autorisations utilisateur

## Base de données

Le système utilise une base de données MySQL avec les tables suivantes :
- `tickets` : stocke les informations des tickets
- `interventions` : stocke les interventions liées aux tickets
- `users` : stocke les informations des utilisateurs
- `salles`, `poste`, `appareils`, `peripherique`, `unite` : stockent les informations sur le matériel 