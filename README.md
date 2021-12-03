# OngouaSync
Déployez votre site ou application web avec un simple git push.

## Fonctionnement
Les fichiers existants sont écrasés et remplacés par ceux provenant de Github.

## Installation
- Télécharger le script OngouaSync.php
- Téléverser le script sur le serveur à la racine du site/application
- Récupérer l’URL pointant sur le script
- Créer un webhook sur Github  avec l’URL du script

## Avantages
1. Déploiement rapide : bande passante de l’hébergeur utilisée
2. Versionning du projet
3. Travail collaboratif: possibilité de travailler à plusieurs sur un projet

## Prérequis
PHP 5.4 ou supérieur
L’extension ZipArchive activée
Droits en écriture

## A venir
- Tests: Run des tests de l’appli
- Config: Ignorer des dossiers et fichiers
- Définir des variables d’environnement
- Installations des dépendances
- Logs: Afficher les logs de déploiement
- Mettre à jour uniquement les fichiers modifiés

## TODO
- [x] Récupérer le nom du dépôt provenant de la requête
- [x] Vérifier la version de PHP
- [ ] Vérifier les droits d'écriture
- [ ] Vérifier la prise en charge de ZipArchive
- [ ] Empêcher des clients non autorisés à exécuter le script
- [ ] Empêcher l’accès direct au script
