# Système de Gestion du Parc Informatique (GPI)

## Vue d'ensemble

Le GPI est une application web complète conçue pour gérer l'inventaire du matériel informatique d'une organisation, suivre les incidents techniques et faciliter la maintenance du parc informatique. Cette solution permet aux administrateurs IT, techniciens et utilisateurs de collaborer efficacement pour maintenir l'infrastructure informatique.

## Architecture du système

- **Frontend** : HTML, CSS (Bootstrap), JavaScript minimal (jQuery)
- **Backend** : PHP
- **Base de données** : MySQL
- **Structure** :
  - Organisation en dossiers (pages, includes, assets)
  - Séparation des fonctionnalités par rôles (admin, user, common)
  - Utilisation de fonctions réutilisables pour les opérations courantes

## Fonctionnalités principales

### 1. Gestion des utilisateurs et authentification
- Système de connexion avec différents niveaux d'accès (admin, technicien, utilisateur standard)
- Contrôle d'accès basé sur les rôles
- Sessions sécurisées pour maintenir l'état de connexion

### 2. Gestion du matériel informatique
- **Appareils** : Suivi des imprimantes et projecteurs
- **Unités centrales** : Gestion des ordinateurs avec leurs caractéristiques (RAM, processeur, disques)
- **Périphériques** : Gestion des claviers, souris, écrans, etc.
- **Postes de travail** : Regroupement d'unités et périphériques

### 3. Système de tickets
- Création de tickets pour signaler des problèmes
- Suivi et résolution des incidents
- Catégorisation des problèmes (matériel, réseau, etc.)
- Priorités et statuts de traitement

### 4. Gestion des emplacements
- Organisation du matériel par salles
- Affectation des équipements à des emplacements spécifiques

### 5. Archivage et restauration
- Conservation des données des équipements supprimés
- Possibilité de restaurer des éléments archivés

### 6. Exportation de données
- Export en CSV et PDF des différentes listes
- Rapports personnalisables

### 7. Statistiques et tableaux de bord
- Vue d'ensemble de l'état du parc informatique
- Statistiques sur les pannes et l'utilisation
- Suivi des tendances et problèmes récurrents

## Système de Gestion de Tickets

### Modifications apportées pour une approche sans JavaScript

Nous avons modifié le système de gestion de tickets pour qu'il fonctionne sans JavaScript, en utilisant uniquement PHP, HTML et CSS. Voici les principales modifications apportées :

#### 1. Formulaire d'ajout d'intervention
- Remplacement de la fenêtre modale JavaScript par un formulaire PHP intégré directement dans la page
- Le formulaire s'affiche uniquement lorsque l'utilisateur clique sur le bouton "Intervention"
- Utilisation de paramètres GET pour déterminer quel ticket est concerné par l'intervention
- Ajout de styles CSS spécifiques pour améliorer l'apparence du formulaire

#### 2. Formulaire de modification de ticket
- Remplacement du système de modification basé sur JavaScript par un formulaire PHP intégré
- Le formulaire s'affiche uniquement lorsque l'utilisateur clique sur le bouton "Modifier"
- Utilisation de paramètres GET pour déterminer quel ticket est en cours de modification
- Styles CSS cohérents avec le formulaire d'intervention

#### 3. Alertes et notifications
- Remplacement des alertes JavaScript par des alertes PHP statiques
- Suppression des boutons de fermeture qui nécessitaient JavaScript
- Utilisation de variables de session pour afficher les messages de succès et d'erreur

#### 4. Styles CSS
- Ajout de styles CSS spécifiques pour les formulaires d'intervention et de modification
- Amélioration de l'apparence des tickets et des badges
- Optimisation de la timeline pour l'historique des interventions

#### 5. Scripts JavaScript
- Conservation uniquement des scripts JavaScript minimaux nécessaires pour Bootstrap
- Utilisation de la version "slim" de jQuery pour réduire la dépendance au JavaScript

### Pages principales du système de tickets

#### Page de gestion des tickets (`manage_tickets.php`)
- Affichage de la liste des tickets avec filtres
- Possibilité de modifier l'état, la priorité et la catégorie d'un ticket
- Ajout d'interventions rapides directement depuis la liste des tickets
- Lien vers la page de détails du ticket

#### Page de détails du ticket (`ticket_details.php`)
- Affichage des informations détaillées du ticket
- Historique des interventions
- Formulaire d'ajout d'intervention avec mise à jour de l'état du ticket

## Sécurité

- Validation des données côté serveur
- Protection contre les injections SQL avec des requêtes préparées
- Échappement des données affichées avec `htmlspecialchars()`
- Vérification des autorisations utilisateur
- Régénération des ID de session pour prévenir les attaques de fixation de session
- Paramètres de cookies sécurisés

## Base de données

Le système utilise une base de données MySQL avec les tables suivantes :

### Tables principales
- `users` : stocke les informations des utilisateurs et leurs rôles
- `tickets` : stocke les informations des tickets d'incident
- `interventions` : stocke les interventions liées aux tickets

### Tables de gestion du matériel
- `appareils` : stocke les informations sur les imprimantes et projecteurs
- `peripherique` : stocke les informations sur les périphériques (claviers, souris, écrans)
- `unite` : stocke les informations sur les unités centrales
- `poste` : stocke les informations sur les postes de travail
- `poste_unite` : table de liaison entre postes et unités
- `salles` : stocke les informations sur les emplacements physiques

### Tables d'archives
- `archives_appareils` : stocke les appareils supprimés
- `archives_peripherique` : stocke les périphériques supprimés
- `archives_unite` : stocke les unités supprimées

## Points forts du système

1. **Interface utilisateur intuitive** avec filtres de recherche avancés
2. **Système de gestion complet** couvrant tous les aspects du parc informatique
3. **Traçabilité** des équipements et des interventions
4. **Sécurité** avec contrôle d'accès basé sur les rôles
5. **Fonctionnalités d'archivage** pour conserver l'historique
6. **Approche minimaliste en JavaScript** pour une meilleure compatibilité et performance