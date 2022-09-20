# OngouaSync
Déployez votre site ou application web avec un simple git push.

## Fonctionnement
A chaque push, les fichiers existants du site sont écrasés et remplacés par ceux provenant de Github.

## Installation
- Télécharger le script [**OngouaSync.php**](https://raw.githubusercontent.com/mendoc/ongoua-sync/main/OngouaSync.php)
- Téléverser le script sur le serveur à la racine du site/application
- Récupérer l’URL pointant sur le script
- Créer un webhook dans les paramètres du dépôt Github avec l’URL du script

## Pise en main
Des vidéos sont disponibles pour une prise en main rapide :
[Playlist Youtube](https://www.youtube.com/watch?v=JC2mT7BLbyg&list=PL7rafFfvik9WRt7sMNCxzJK4fJb8i7D1b)

## Avantages
1. Déploiement rapide : bande passante de l’hébergeur utilisée
2. Versionning du projet : Le projet suit l'évolution des modifications du dépôt
3. Travail collaboratif : possibilité de travailler à plusieurs sur un projet
4. Utilisation de Composer : installation des dépendances Composer lors du déploiement

## Prérequis
- PHP 5.6.0 ou supérieur
- Le module PHP **zip** activé
- Droits en écriture
- Un dépôt public ou privé
- Un [**Personal Access Token**](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token) si le dépôt est privé

## TODO
- [x] (03/12/2021) Récupérer le nom du dépôt provenant de la requête
- [x] (03/12/2021) Vérifier la version de PHP
- [x] (05/12/2021) Empêcher l’accès direct au script
- [x] (05/12/2021) Vérifier les droits d'écriture
- [x] (05/12/2021) Vérifier la prise en charge de ZipArchive
- [x] (05/12/2021) Vérifier si la fonction shell_exec est prise en charge
- [x] (05/12/2021) Installation des dépendances composer
- [x] (05/12/2021) Logs : Afficher les logs de déploiement
- [x] (18/09/2022) Ecouter uniquement les push d'une branche précise
- [x] (19/09/2022) Empêcher des clients non autorisés d'exécuter le script
- [ ] Empêcher des push provenant de plusieurs dépôts différents
- [ ] Ignorer des dossiers et fichiers
- [ ] Préciser un sous-dossier pour le déploiement
- [ ] Vider ou pas le dossier avant de copier les fichiers
- [ ] Mettre à jour uniquement les fichiers modifiés
- [ ] Définir des variables d’environnement
- [ ] Tests : Exécuter des tests de l’application
