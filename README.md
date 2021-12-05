# OngouaSync
Déployez votre site ou application web avec un simple git push.

## Fonctionnement
A chaque push, les fichiers existants du site sont écrasés et remplacés par ceux provenant de Github.

## Installation
- Télécharger le script **OngouaSync.php**
- Téléverser le script sur le serveur à la racine du site/application
- Récupérer l’URL pointant sur le script
- Créer un webhook sur Github  avec l’URL du script

## Avantages
1. Déploiement rapide : bande passante de l’hébergeur utilisée
2. Versionning du projet
3. Travail collaboratif : possibilité de travailler à plusieurs sur un projet

## Prérequis
- PHP 5.6.0 ou supérieur
- Le module PHP **zip** activée
- Droits en écriture

## A venir
- Installations des dépendances &#9989; 
- Tests : Run des tests de l’appli
- Config : 
    - Ignorer des dossiers et fichiers
    - Vider le dossier avant de copier les fichiers
- Définir des variables d’environnement
- Logs : Afficher les logs de déploiement
- Mettre à jour uniquement les fichiers modifiés

## TODO
- [x] (03/12/2021) Récupérer le nom du dépôt provenant de la requête
- [x] (03/12/2021) Vérifier la version de PHP
- [x] (05/12/2021) Empêcher l’accès direct au script
- [x] (05/12/2021) Vérifier les droits d'écriture
- [x] (05/12/2021) Vérifier la prise en charge de ZipArchive
- [x] (05/12/2021) Vérifier si la fonction shell_exec est prise en charge
- [ ] Empêcher des clients non autorisés d'exécuter le script
