<?php

/**
 * edropub – An Editorially → Dropbox → Leanpub editing and publishing workflow
 * 
 * Authorization setup
 *
 * @category	Jkphl
 * @package		Jkphl_Edropub
 * @author		Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright	Copyright © 2014 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license		http://opensource.org/licenses/MIT	The MIT License (MIT)
 */

try {

	// Make sure the script is run on the command line
	if (PHP_SAPI !== 'cli') {
	    throw new \Exception('This program was meant to be run from the command-line and not as a web app. Bad value for PHP_SAPI. Expected \'cli\', given \''.PHP_SAPI.'\'.', 1);
	}
	
	// Make sure the Dropbox SDK has been installed
	if (!@is_dir(dirname(__DIR__).'/vendor/dropbox/dropbox-sdk')) {
		throw new \Exception('Please install the Dropbox SDK by running \'composer install\' in the edropub root directory.', 2);
	}
	
	// Require common settings
	require_once dirname(__DIR__).'/config/common.php';
	
	// Activate error reporting
	require_once dirname(__DIR__).'/vendor/dropbox/dropbox-sdk/lib/Dropbox/strict.php';
	
	// Include the Dropbox autoloader in case the composer autoloader is not already active
	if (!class_exists('\Dropbox\AppInfo')) {
		echo "auto\n\n";
		require_once dirname(__DIR__).'/vendor/dropbox/dropbox-sdk/lib/Dropbox/autoload.php';
	}
	
	list($appInfoJson, $appInfo)	= \Dropbox\AppInfo::loadFromJsonFileWithRaw(dirname(__DIR__).'/config/config.json');
	
	// This is a command-line tool (as opposed to a web app), so we can't supply a redirect URI.
	$webAuth						= new \Dropbox\WebAuthNoRedirect($appInfo, "examples-authorize", "en");
	$authorizeUrl					= $webAuth->start();
	
	echo "1. Go to: $authorizeUrl\n";
	echo "2. Click \"Allow\" (you might have to log in first).\n";
	echo "3. Copy the authorization code.\n";
	echo "Enter the authorization code here: ";
	$authCode						= \trim(\fgets(STDIN));
	
	list($accessToken, $userId)		= $webAuth->finish($authCode);
	
	echo "Authorization complete.\n";
	echo "- User ID: $userId\n";
	echo "- Access Token: $accessToken\n";
	
	$authArr						= array(
	    'access_token'				=> $accessToken,
		'editorially_prefix'		=> '/Apps/Editorially',
		'leadpub_api_key'			=> '<YOUR_ACCOUNT_API_KEY>',
		'leadpub_prefix'			=> '/<YOUR_BOOKS_SLUG>',
		'leanpub_trigger'			=> 'preview',
	);
	
	if (array_key_exists('host', $appInfoJson)) {
	    $authArr['host']			= $appInfoJson['host'];
	}
	
	$argAuthFileOutput				= dirname(__DIR__).'/config/access.json';
	$json							= json_encode($authArr, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0);
	
	if (file_put_contents($argAuthFileOutput, $json) !== false) {
	    echo "Saved authorization information to \"$argAuthFileOutput\".\n";
	} else {
		throw new \Exception("Error saving to \"$argAuthFileOutput\".\nDumping to stderr instead:\n$json\n", 3);
	}
	
// Configuration loading error
} catch (\Dropbox\AppInfoLoadException $ex) {
	echo "Error loading 'config/config.json': ".$ex->getMessage()."\n";
	exit(100);
	
// Other error
} catch (\Exception $e) {
	echo $e->getMessage()."\n";
	exit($e->getCode());
}