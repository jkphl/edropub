<?php

/**
 * Default Editorially App folder path on Dropbox
 * 
 * @var \string
 */
define('EDITORIALLY_PATH_PREFIX', '/Apps/Editorially');
/**
 * Manuscript folder in Leanpub dropbox folder
 * 
 * @var \string
 */
define('LEANPUB_MANUSCRIPT_SUFFIX', '/manuscript/');
/**
 * Images folder in Leanpub dropbox manuscript folder
 * 
 * @var \string
 */
define('LEANPUB_IMAGES_SUFFIX', 'images/');
/**
 * Publish a Leanpub book
 * 
 * @var \string
 */
define('LEANPUB_PUBLISH', 'publish');
/**
 * Leanpub publish API URL
 * 
 * @var \string
 */
define('LEANPUB_PUBLISH_URL', 'https://leanpub.com/%s/publish.json');
/**
 * Create a Leanpub book preview
 * 
 * @var \string
 */
define('LEANPUB_PREVIEW', 'preview');
/**
 * Leanpub preview API URL
 *
 * @var \string
 */
define('LEANPUB_PREVIEW_URL', 'https://leanpub.com/%s/preview.json');