<?php

/**
 * edropub – An Editorially → Dropbox → Leanpub editing and publishing workflow
 *
 * @category	Jkphl
 * @package		Jkphl_Edropub
 * @author		Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright	Copyright © 2014 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license		http://opensource.org/licenses/MIT	The MIT License (MIT)
 */

namespace Jkphl;

/***********************************************************************************
 *  The MIT License (MIT)
 *  
 *  Copyright © 2014 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

// Make sure the script is run on the command line
if (PHP_SAPI !== 'cli') {
	echo 'This program was meant to be run from the command-line and not as a web app. Bad value for PHP_SAPI. Expected \'cli\', given \''.PHP_SAPI.'\'.';
	exit(2);
}

// Make sure the Dropbox SDK has been installed
if (!@is_dir(__DIR__.'/vendor/dropbox/dropbox-sdk')) {
	echo 'Please install the Dropbox SDK by running \'composer install\' in the edropub root directory.';
	exit(3);
}

// Require common settings
require_once __DIR__.'/config/common.php';

// Include the composer autoloader
require_once __DIR__.'/vendor'.DIRECTORY_SEPARATOR.'autoload.php';

/**
 * Edropub
 *
 * @category	Jkphl
 * @package		Jkphl_Edropub
 * @author		Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright	Copyright © 2014 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license		http://opensource.org/licenses/MIT	The MIT License (MIT)
 */
class Edropub {
	/**
	 * Dropbox access properties
	 * 
	 * @var \stdClass
	 */
	protected $_access = null;
	/**
	 * Editorially dropbox path prefix
	 * 
	 * @var \string
	 */
	protected $_editoriallyPathPrefix = null;
	/**
	 * Dropbox delta cursor
	 * 
	 * @var \string
	 */
	protected $_deltaCursor = false;
	/**
	 * Dropbox client
	 * 
	 * @var \Dropbox\Client
	 */
	protected $_dropboxClient = null;
	
	/************************************************************************************************
	 * PUBLIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Poll and process the changes in the Editorially dropbox folder
	 * 
	 * @param \boolean $reset			Reset the dropbox delta cursor
	 * @return \boolean					Success
	 */
	public function run($reset = false) {
		$break						=
		$modified					= 0;
		
		// Loop through all deltas
		do {
			$dbxDelta				= $this->_dropboxClient->getDelta($this->_getDeltaCursor($reset), $this->_editoriallyPathPrefix);
			$this->_setDeltaCursor($dbxDelta['cursor']);
		
			// Run through all changed entries
			foreach ($dbxDelta['entries'] as $entry) {
				if (is_array($entry) && (count($entry) == 2) && is_array($entry[1]) && !$entry[1]['is_dir'] && (strtolower(pathinfo($entry[1]['path'], PATHINFO_EXTENSION)) == 'md') && $this->_processMarkdownFile($entry[1])) {
					++$modified;
				}
			}
		
		} while (!empty($dbxDelta['has_more']) && (++$break < 100));
		
		// If files have been modified and a Leanpub action should be triggered
		if ($modified && isset($this->_access->leanpub_trigger)) {
			switch ($this->_access->leanpub_trigger) {

				// If the Leanpub book should be published
				case LEANPUB_PUBLISH:
					return $this->_publishLeanpubBook();
					break;
					
				// If a preview of the book should be created
				case LEANPUB_PREVIEW:
					return $this->_previewLeanpubBook();
					break;
					
				// Else: Invalid Leanpub action
				default:
					return false;
					break;
			}
			
		// Else: Success
		} else {
			return true;
		}
	}

	/************************************************************************************************
	 * PRIVATE METHODS
	 ***********************************************************************************************/
	
	/**
	 * Constructor
	 * 
	 * @return \Jkphl\Edropub
	 * @throws \Exception			If the access token is invalid
	 */
	private function __construct() {
		
		// Read the access token (and start the authorization process if an access token is not yet available)
		if (!@is_file(__DIR__.'/config/access.json')) {
			include __DIR__.'/util/authorize.php';
		}
		
		// If the access token is not available
		if (!@is_file(__DIR__.'/config/access.json') || !(($this->_access = @json_decode(@file_get_contents(__DIR__.'/config/access.json'))) instanceof \stdClass) || !isset($this->_access->access_token) || empty($this->_access->access_token)) {
			@unlink(__DIR__.'/config/access.json');
			throw new \Exception('Invalid access token. Please re-run the authorization process.', 3);
		}
		
		// Create the Dropbox client
		$this->_dropboxClient			= new \Dropbox\Client($this->_access->access_token, "edropub/1.0");
		$this->_editoriallyPathPrefix	= isset($this->_access->editorially_prefix) ? $this->_access->editorially_prefix : EDITORIALLY_editorially_prefix;
	}
	
	/**
	 * Process a markdown file
	 * 
	 * @param \array $entry				File entry
	 * @return \boolean					Success
	 */
	protected function _processMarkdownFile(array $entry) {
		try {
		
			// Download the Markdown file from Dropbox and create a temporary file
			$markdown						= '';
			$markdownFile					= tmpfile();
			$this->_dropboxClient->getFile($entry['path'], $markdownFile);
			fseek($markdownFile, 0);
			while(!feof($markdownFile)) {
				$markdown					.= fread($markdownFile, 4096);
			}
			fclose($markdownFile);
			
			// Parse the Markdown file
			$htmlDom						= new \DOMDocument();
			$htmlDom->loadHTML(\Michelf\MarkdownExtra::defaultTransform($markdown));
			$htmlXPath						= new \DOMXPath($htmlDom);
			
			// Run through all images
			foreach ($htmlXPath->query('//img[@src]') as $image) {
				$src						= $image->getAttribute('src');
				$tempImage					= tempnam(sys_get_temp_dir(), 'edropub_');
				
				// Fetch the image to a temporary local file
				if (@file_put_contents($tempImage, $this->_fetch($src))) {
					
					// Create a hashed file name for use with Leanpub
					$tempMD5				= md5_file($tempImage);
					$imageFileName			= rtrim(preg_replace("%[^a-zA-Z0-9\_\-]+%", '_', pathinfo($src, PATHINFO_FILENAME)), '_').'_'.$tempMD5.'.'.strtolower(pathinfo($src, PATHINFO_EXTENSION));
					$imagePath				= '/'.rtrim($this->_access->leanpub_book_slug, '/').LEANPUB_MANUSCRIPT_SUFFIX.LEANPUB_IMAGES_SUFFIX.$imageFileName;
					
					// Upload the images to the Leanpub Dropbox folder
					$image					= fopen($tempImage, 'rb'); 
					$this->_dropboxClient->uploadFile($imagePath, \Dropbox\WriteMode::force(), $image);
					fclose($image);
					
					// Destroy the temporary image
					@unlink($tempImage);
					
					// Replace the original image name with the hashed one in the Markdown file
					$markdown				= str_replace($src, LEANPUB_IMAGES_SUFFIX.$imageFileName, $markdown);
				}
			}
			
			// Upload the Markdown file to the Leanpub Dropbox directory
			$this->_dropboxClient->uploadFileFromString('/'.rtrim($this->_access->leanpub_book_slug, '/').LEANPUB_MANUSCRIPT_SUFFIX.pathinfo($entry['path'], PATHINFO_FILENAME).'.txt', \Dropbox\WriteMode::force(), $markdown);
			
			return true;

		// Error
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Read and return the dropbox delta cursor
	 * 
	 * @param \boolean $reset			Reset the dropbox delta cursor
	 * @return \string					Dropbox delta cursor
	 */
	protected function _getDeltaCursor($reset = false) {
		if ($this->_deltaCursor === false) {
			$this->_deltaCursor	= (!$reset && @is_file(__DIR__.'/config/delta.cursor')) ? @file_get_contents(__DIR__.'/config/delta.cursor') : null;
		}
		return $this->_deltaCursor;
	}
	
	/**
	 * Set the delta cursor
	 * 
	 * @param \string $cursor
	 * @return void
	 */
	protected function _setDeltaCursor($cursor) {
		$this->_deltaCursor		= $cursor;
		@file_put_contents(__DIR__.'/config/delta.cursor', $this->_deltaCursor);
	}
	
	/**
	 * Request an URL via GET or POST (HTTP 1.1)
	 *
	 * @param \string $url				Remote URL
	 * @param \array $params			Parameters (POST)
	 * @return \string					Response content
	 */
	protected function _fetch($url, array $params = null) {
		
		$curl						= curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_ENCODING		=> '',
			CURLOPT_USERAGENT		=> 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.466.4 Safari/534.3',
			CURLOPT_AUTOREFERER		=> true,
			CURLOPT_CONNECTTIMEOUT	=> 120,
			CURLOPT_TIMEOUT			=> 120,
			CURLOPT_MAXREDIRS		=> 10,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_HTTP_VERSION	=> CURL_HTTP_VERSION_1_1,
		));
		
		// Send POST variables
		if ($params !== null) {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		
		$response					= curl_exec($curl);
		curl_close($curl);
	
		return $response;
	}
	
	/**
	 * Publish a Leanpub book
	 * 
	 * @return \boolean					Success
	 */
	protected function _publishLeanpubBook() {
		if (isset($this->_access->leanpub_api_key) && !empty($this->_access->leanpub_api_key)) {
			$publishUrl						= sprintf(LEANPUB_PUBLISH_URL, $this->_access->leanpub_book_slug);
			$publish						= $this->_fetch($publishUrl, array('api_key' => $this->_access->leanpub_api_key));
			$publish						= strlen($publish) ? @json_decode($publish) : null;
			return ($publish instanceof \stdClass) && isset($publish->success) && $publish->success;
		}
		return false;
	}
	
	/**
	 * Create a Leanpub book preview
	 *
	 * @return void
	 */
	protected function _previewLeanpubBook() {
		if (isset($this->_access->leanpub_api_key) && !empty($this->_access->leanpub_api_key)) {
			$previewUrl						= sprintf(LEANPUB_PREVIEW_URL, $this->_access->leanpub_book_slug);
			$preview						= $this->_fetch($previewUrl, array('api_key' => $this->_access->leanpub_api_key));
			$preview						= strlen($preview) ? @json_decode($preview) : null;
			return ($preview instanceof \stdClass) && isset($preview->success) && $preview->success;
		}
		return false;
	}
	
	/************************************************************************************************
	 * STATIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Instantiator
	 * 
	 * @return \Jkphl\Edropub
	 */
	public static function instance() {
		return new self();
	}
}

try {
	exit((!Edropub::instance()->run(($GLOBALS['argc'] > 1) && ($GLOBALS['argv'][1] == 'reset'))) * 1);
} catch(\Exception $e) {
	echo $e->getMessage()."\n";
	exit($e->getCode());
}