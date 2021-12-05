<?php

/**
 * Ce script permet de synchroniser un dépôt Github et 
 * un dossier distant héberger dans un serveur supportant PHP
 * 
 * A chaque push sur la branche main, il récupère les fichiers sur Github et les met
 * à jour dans le dossier où il se trouve.
 * 
 * @author Dimitri ONGOUA
 */
 
/**
 * =========================================================
 *	TRAITEMENT PRINCIPAL
 * =========================================================
 */
 
// Vérification de la version de PHP
if(version_compare(PHP_VERSION, '5.6.0', '<')) {
    die("PHP 5.6.0 ou supérieur est requis.");
}

// Vérification de la prise en charge de la classe ZipArchive
if (!class_exists("ZipArchive")) {
    die("La classe ZipArchive n'est pas supportée par votre serveur.");
}

// Vérification de la prise en charge de la fonction shell_exec
if (!function_exists("shell_exec")) {
	die("La fonction shell_exec n'est pas supportée par votre serveur.");
}

// Vérification des droits en écriture dans le dossier courant
if(!is_writable('.')) {
	die("Besoin des droits en écriture dans ce dossier pour continuer.");
}

// Récupration des en-têtes de la requête
$headers   = getallheaders();
$signature = $headers["X-Hub-Signature-256"];

if(!isset($signature)) {
	header('HTTP/1.0 403 Forbidden');
	die("Vous n'êtes pas autorisé à accéder à ce script.");
}

$payload = file_get_contents("php://input");
$hash    = "sha256=" . hash_hmac('sha256', $payload, 'OngouaSync');

// Si les données ne proviennent pas de Github on ne continue pas.
if (!hash_equals($signature, $hash)) die("Signature incorrecte.");

// Récupération de l'évènement Github
$evenement = $headers["X-GitHub-Event"];

// Si ce n'est pas un push on ne continue pas.
if ($evenement !== 'push') die("Evènement ($evenement) non pris en charge.");

$infos = json_decode($payload, TRUE);
$depot = $infos["repository"]["full_name"];

// Synchronisation du dossier
OngouaSync($depot);

/**
 * =========================================================
 *	FONCTIONS DE TRAVAIL
 * =========================================================
 */
 
function OngouaSync($depot)
{
    if (!strpos($depot, "/")) die("Nom du dépôt incorrect.");

    $infos = explode("/", $depot);

    if (count($infos) !== 2) die("Format du dépôt incorrect. Exemple: utilisateur/depot");

    $branche          = "main";
    $nom_depot        = $infos[1];
    $nom_dossier_temp = "$nom_depot-$branche";
    $fichier_zip      = get_archive("https://github.com/$depot/archive/refs/heads/main.zip");

    // Récupération du chemin absolu du dossier de travail
    $chemin_dossier_travail = pathinfo($fichier_zip, PATHINFO_DIRNAME);

    $archive = new ZipArchive();
    $res      = $archive->open($fichier_zip);
    if ($res === TRUE) {
        // Extraction de l'archive dans le dossier de travail
        $archive->extractTo($chemin_dossier_travail);
        $archive->close();

		// Copie des fichiers extraits dans le dossier de travail
        rcopy($nom_dossier_temp, realpath("."));
        
        // Suppression du dossier temporaire
        rrmdir($nom_dossier_temp);
        
        // Suppression de l'archive
        unlink($fichier_zip);
        
        // Installation des dépendances
        installer_deps();
        
        die("Dossier mis à jour.");
    } else {
        die("Problème lors de l'extraction du l'archive. Code erreur : " . $res);
    }
}

function get_archive($url)
{
    $content = "";

    if (function_exists('curl_init')) {
        $opts                                   = array();
        $http_headers                           = array();
        $http_headers[]                         = 'Expect:';

        $opts[CURLOPT_URL]                      = $url;
        $opts[CURLOPT_HTTPHEADER]               = $http_headers;
        $opts[CURLOPT_CONNECTTIMEOUT]           = 10;
        $opts[CURLOPT_TIMEOUT]                  = 60;
        $opts[CURLOPT_HEADER]                   = FALSE;
        $opts[CURLOPT_BINARYTRANSFER]           = TRUE;
        $opts[CURLOPT_VERBOSE]                  = FALSE;
        $opts[CURLOPT_SSL_VERIFYPEER]           = FALSE;
        $opts[CURLOPT_SSL_VERIFYHOST]           = 2;
        $opts[CURLOPT_RETURNTRANSFER]           = TRUE;
        $opts[CURLOPT_FOLLOWLOCATION]           = TRUE;
        $opts[CURLOPT_MAXREDIRS]                = 2;
        $opts[CURLOPT_IPRESOLVE]                = CURL_IPRESOLVE_V4;

        # Initialize PHP/CURL handle
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $content = curl_exec($ch);

        # Close PHP/CURL handle
        curl_close($ch);
    }

    $filename = __DIR__ . "/" . time() . ".zip";

    file_put_contents($filename, $content);

    return $filename;
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
