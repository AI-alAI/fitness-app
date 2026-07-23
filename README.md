Smart Fitness App

Plateforme web de gestion fitness développée en architecture 3-tiers, avec trois espaces utilisateurs distincts et un module Coach IA basé sur un LLM (via OpenRouter).

Projet de Fin d'Année (PFA) — EMSI Fès, cycle ingénieur Informatique et Réseaux.

✨ Fonctionnalités
👤 Utilisateur
Tableau de bord personnel, calendrier d'entraînement
Suivi des séances, historique et logs de workout
Définition et suivi d'objectifs
Consultation des programmes disponibles et des exercices
Chat avec le Coach IA (assistant intelligent alimenté par un modèle de langage)
Messagerie et réclamations
🧑‍🏫 Coach
Tableau de bord coach, gestion des programmes et exercices
Suivi des utilisateurs assignés, conseils personnalisés
Gestion des assignations, messages et réclamations
🛠️ Admin
Gestion des utilisateurs et des coachs
Gestion des programmes, exercices et réclamations
Vue d'ensemble via un dashboard admin
🧱 Architecture

Architecture 3-tiers classique :

fitness-app/
├── admin/          # Espace administrateur
├── coach/          # Espace coach
├── utilisateur/    # Espace utilisateur
├── auth/           # Authentification (login/register par rôle)
├── config/         # Connexion base de données
├── include/        # Header, footer, fonctions communes
├── ajax_*.php      # Endpoints AJAX (chat IA, messagerie)
└── vendor/         # Dépendances Composer
🛠️ Stack technique
Backend : PHP
Base de données : MySQL
Frontend : HTML5, CSS3, Bootstrap, JavaScript
IA : intégration LLM via l'API OpenRouter
Dépendances : vlucas/phpdotenv (gestion des variables d'environnement)
