<?php

/**
 * Ce script permet de synchroniser un dépôt Github et
 * un dossier distant héberger dans un serveur supportant PHP
 *
 * A chaque push sur la branche surveillée, il récupère les fichiers sur Github
 * et les met à jour dans le dossier où il se trouve.
 *
 * A chaque création ou mise à jour d'une pull request, il récupère les fichiers
 * de la branche source et les dépose dans un sous-dossier portant le nom de
 * la branche (les "/" sont remplacés par "-").
 *
 * @author Dimitri ONGOUA
 */

/**
 * =========================================================
 * SECURITE : Cle de synchronisation
 * 
 * Définition d'une clé pour éviter que des clients non autorisés
 * n'exécutent le script
 * =========================================================
 */
define('ONGOUA_SYNC_KEY', "OngouaSync");

/**
 * =========================================================
 * SECURITE : Personal Access Token
 * 
 * Définition d'un jeton permettant de copier un depot prive
 * La creation de jeton se fait dans les parametres du compte Github
 * Settings > Developer settings > Personal access tokens
 * =========================================================
 */
define('ONGOUA_PERSONAL_ACCESS_TOKEN', "");

/**
 * =========================================================
 * DEPOT
 * 
 * Définition du depot github
 * 
 * Definir un depot pour eviter de recevoir des push d'autres depots, 
 * =========================================================
 */
define('ONGOUA_DEPOT', "");

/**
 * =========================================================
 * BRANCHE
 * 
 * Définition de la branche à surveiller
 * Exemples :
 * define('ONGOUA_BRANCH', "main");
 * define('ONGOUA_BRANCH', "master");
 * 
 * Si auncune branche n'est precisee, 
 * la branche par defaut du depot sera consideree
 * =========================================================
 */
define('ONGOUA_BRANCH', "");

/**
 * =========================================================
 *	TRAITEMENT PRINCIPAL
 * =========================================================
 */

// Vérification de la version de PHP
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    die("PHP 5.6.0 ou supérieur est requis.");
}

// Vérification de la prise en charge de la classe ZipArchive
if (!class_exists("ZipArchive")) {
    die("Le module PHP zip n'est pas activé sur votre serveur.");
}

// Vérification de la prise en charge de la fonction shell_exec
if (file_exists("composer.json") and !function_exists("shell_exec")) {
    die("La fonction shell_exec n'est pas supportée par votre serveur.");
}

// Vérification des droits en écriture dans le dossier courant
if (!is_writable('.')) {
    die("Besoin des droits en écriture dans ce dossier pour continuer.");
}

// Récupration des en-têtes de la requête
$headers   = getallheaders();
$signature = isset($headers["X-Hub-Signature-256"]) ? $headers["X-Hub-Signature-256"] : "";

if (!isset($signature)) {
    header('HTTP/1.0 403 Forbidden');
    die("Vous n'êtes pas autorisé à accéder à ce script.");
}

$payload = file_get_contents("php://input");
$hash    = "sha256=" . hash_hmac('sha256', $payload, ONGOUA_SYNC_KEY);

// Si les données ne proviennent pas de Github on ne continue pas.
if (!hash_equals($signature, $hash)) die("Signature incorrecte.");

// Récupération de l'évènement Github
$evenement = isset($headers["X-GitHub-Event"]) ? $headers["X-GitHub-Event"] : $headers["X-Github-Event"];

// Si ce n'est pas un push ou une pull_request on ne continue pas.
if ($evenement !== 'push' && $evenement !== 'pull_request')
    die("Evènement ($evenement) non pris en charge.");

$infos      = json_decode($payload, TRUE);
$depot      = $infos["repository"]["full_name"];
$visibilite = $infos["repository"]["visibility"];

if ($evenement === 'push') {

    // Comportement existant : déploiement à la racine
    $branche = $infos["repository"]["default_branch"];

    if (strlen(ONGOUA_BRANCH) > 0)
        if ($branche !== ONGOUA_BRANCH) die("Les modifications de cette branche ($branche) sont ignorées.");

    OngouaSync($depot, $branche, $visibilite);

} else {

    // Gestion des pull requests : opened (création) et synchronize (nouveau push sur la branche)
    $action = $infos["action"];

    if ($action !== 'opened' && $action !== 'synchronize')
        die("Action PR ($action) non prise en charge.");

    $branche_pr  = $infos["pull_request"]["head"]["ref"];
    $nom_dossier = str_replace("/", "-", $branche_pr);

    OngouaSync($depot, $branche_pr, $visibilite, $nom_dossier);

}

/**
 * =========================================================
 *	FONCTIONS DE TRAVAIL
 * =========================================================
 */

function OngouaSync($depot, $branche, $visibilite, $sous_dossier = null)
{
    $nom_fichier_zip  = __DIR__ . DIRECTORY_SEPARATOR . time() . ".zip";
    $url_depot_prive  = "https://api.github.com/repos/$depot/zipball/$branche";
    $url_depot_public = "https://github.com/$depot/archive/refs/heads/$branche.zip";

    // Récupération des fichiers du dépôt
    if ($visibilite === "public")
        copyPublic($url_depot_public, $nom_fichier_zip);
    else
        copyPrivate($url_depot_prive, $nom_fichier_zip);

    // Récupération du chemin absolu du dossier de travail
    $chemin_dossier_travail = pathinfo($nom_fichier_zip, PATHINFO_DIRNAME);

    $archive = new ZipArchive();
    $res     = $archive->open($nom_fichier_zip);
    if ($res === TRUE) {
        // Extraction de l'archive dans le dossier de travail
        $archive->extractTo($chemin_dossier_travail);
        $archive->close();

        // Récupération du nom du dossier temporaire
        $nom_dossier_temp = getDossierTemp($depot, $branche, $visibilite);

        // Détermination du dossier de destination
        $destination = is_null($sous_dossier)
            ? realpath(".")
            : realpath(".") . DIRECTORY_SEPARATOR . $sous_dossier;

        // Copie des fichiers extraits dans le dossier de destination
        rcopy($nom_dossier_temp, $destination);

        // Suppression du dossier temporaire
        rrmdir($nom_dossier_temp);

        // Installation des dépendances
        installer_deps();

        echo "Dossier mis à jour.";
    } else {
        echo "Problème lors de l'extraction du l'archive. Code erreur :  $res";
    }

    // Suppression de l'archive
    unlink($nom_fichier_zip);
}

function getDossierTemp($depot, $branche, $visibilite)
{
    if (!strpos($depot, "/")) die("Nom du dépôt incorrect.");

    $infos = explode("/", $depot);

    if (count($infos) !== 2) die("Format du dépôt incorrect. Exemple: utilisateur/depot");

    $nom_depot = $infos[1];

    // GitHub remplace les "/" par "-" dans le nom du dossier extrait
    $branche_sanitized = str_replace("/", "-", $branche);

    if ($visibilite === "public") {
        return "$nom_depot-$branche_sanitized";
    } else {
        $liste_depot_dossiers = glob(str_replace("/", "-", $depot) . "-*");
        if (count($liste_depot_dossiers) > 0) {
            return $liste_depot_dossiers[0];
        }
    }
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file)
            if ($file != "." && $file != "..") rrmdir("$dir/$file");
        rmdir($dir);
    } else if (file_exists($dir)) unlink($dir);
}

function rcopy($src, $dst)
{
    if (is_dir($src)) {
        @mkdir($dst);
        $files = scandir($src);
        foreach ($files as $file)
            if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file");
    } else if (file_exists($src)) copy($src, $dst);
}

function copyPublic($url, $dest)
{
    return copy($url, $dest);
}

function copyPrivate($url, $dest)
{
    $header = array();
    $header[] = "Accept: application/vnd.github+json";
    $header[] = "User-Agent: mendoc-ongoua-sync";
    $header[] = "Authorization: Bearer " . ONGOUA_PERSONAL_ACCESS_TOKEN;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $reponse = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Erreur CURL - " . curl_error($ch);
    } else {
        $status = curl_getinfo($ch);
        if ($status["http_code"] == 302) {
            $depot_down_url = getURL($reponse);
            if ($depot_down_url === false)
                echo "Erreur lors de la recuperation de l'URL du depot";
            else
                copyPublic($depot_down_url, $dest);
        } else {
            echo $status["http_code"] == 200 ? "OK" : "Erreur " . $status["http_code"] . " : $url | ";
        }
    }
    curl_close($ch);
}

function getURL($text)
{
    $parts = explode("ocation:", $text);
    if (count($parts) < 2) return false;

    $url = substr($parts[1], 0, strpos($parts[1], PHP_EOL));

    return trim($url);
}

function installer_deps()
{
    if (file_exists("composer.json")) {

        $composerFilename = "composer-setup.php";

        if (!file_exists("composer.phar")) {
            copy("https://mendoc.github.io/ongoua-sync/$composerFilename", $composerFilename);
            require_once $composerFilename;
        }
        $output = shell_exec('php composer.phar update');
        echo "Output: " . $output;

        if (file_exists($composerFilename)) unlink($composerFilename);
    }
}
