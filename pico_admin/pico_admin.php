<?php

/**
 * Pico Admin plugin 2.0 for Pico CMS
 *
 * @author Kay Stenschke
 * @link http://www.coexec.com
 * @version 2.0.0
 */
class Pico_Admin {
    private $is_devMode = true;  // true: merge CSS files and JS files new on every request

    private $basePath;

	private $is_admin;
	private $is_preview;
	private $is_logout;

	private $plugin_path;
	private $password;

    const PLUGIN_DIRECTORY_NAME = 'pico_admin';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->is_admin		= false;
		$this->is_logout 	= false;
		$this->plugin_path 	= dirname(__FILE__);
		$this->password 	= '';

        $this->is_preview = $this->endsWith($_SERVER['REQUEST_URI'], 'preview.php');
        if( ! $this->is_preview ) {
            session_start();
        }
		
		if(file_exists($this->plugin_path .'/admin_config.php')){
			global $pico_admin_password;

			include_once($this->plugin_path .'/admin_config.php');
			$this->password = $pico_admin_password;
		} else {
            die('Fatal error: admin_config.php missing.');
        }

        $this->basePath = str_replace(array($_SERVER['DOCUMENT_ROOT'], '/content'), '', CONTENT_DIR);
        if( $this->basePath[0] === '/' ) $this->basePath = substr($this->basePath, 1);
	}

	/**
	 * @param   string  $url
	 */
	public function request_url(&$url)
	{
		switch($url) {
            case 'admin':   // login into admin area?
                $this->is_admin = true;
                break;

            case 'admin/new':
                $this->do_new();
                break;

            case 'admin/open':
                $this->do_open();
                break;

            case 'admin/save':
                $this->do_save();
                break;

            case 'admin/remove':
                $this->do_delete();
                break;

            case 'admin/deletedirectory':
                $this->do_delete(true);
                break;

            case 'admin/download':
                $this->do_download();
                break;

            case 'admin/rename':
                $this->do_rename();
                break;

            case 'admin/assets':
                $this->do_render_assetsmanager();
                break;

            case 'admin/upload':
                $this->do_upload();
                break;

            case 'admin/mkdir':
                $this->do_mkdir();
                break;

            case 'admin/logout':
                $this->is_logout = true;
                break;
        }

	}

	/**
	 * @param   array               $twig_vars
	 * @param   Twig_Environment    $twig
	 */
	public function before_render(&$twig_vars, &$twig)
	{
		if($this->is_logout){
			session_destroy();
			header('Location: '. $twig_vars['base_url'] .'/admin');
			exit;
		}

        $template = 'admin';
		if($this->is_admin){
			header($_SERVER['SERVER_PROTOCOL'].' 200 OK'); // Override 404 header

			$loader = new Twig_Loader_Filesystem($this->plugin_path);
			$twig_editor = new Twig_Environment($loader, $twig_vars);

			if(!$this->password){
				$twig_vars['login_error'] = 'No password set for the Pico Editor.';
				$template = 'login';

			} else if(!isset($_SESSION['pico_logged_in']) || !$_SESSION['pico_logged_in']){
				if(isset($_POST['password'])){
					if(sha1($_POST['password']) == $this->password
                        && ! array_key_exists('pico_logged_in', $_SESSION) || $_SESSION['pico_logged_in'] !== true
                    ){
						$_SESSION['pico_logged_in'] = true;
                        $twig_vars['is_initial_login'] = true;
					} else {
						$twig_vars['login_error'] = 'Invalid password.';
                        $template = 'login';
					}
				} else {
                    $template = 'login';
				}
			}

			$twig_vars['pages']     = $this->extendPageTree( $twig_vars['pages'] );
            $twig_vars['openpost']  = array_key_exists('openpost', $_SESSION) && file_exists($_SESSION['openpost']) ? $_SESSION['openpost'] : false;

            $this->mergeCssFiles();
            $this->mergeJsFiles();

			echo $this->translate($twig_editor->render('templates/' . $template . '.html', $twig_vars)); // Render admin.html or login.html
			exit; // Don't continue to render template
		}
	}

    /**
     * Merge CSS files into 'pico_admin.all.css'
     *
     * @note relative paths, eg. to background-images, are not remapped
     */
    private function mergeCssFiles(){
        $files = array(
            'assets/css/pico_admin.css',
            'assets/css/markitup-style.css',
            'assets/css/markdown-set-style.css',
            'assets/css/introjs.css'
        );
        $this->mergeFiles($files, $this->plugin_path . '/assets/css/pico_admin.MERGED.css');
    }

    private function mergeJsFiles(){
        $files = array(
            'assets/js/jquery.1.10.1.min.js',
            'markitup/jquery.markitup.min.js',
            'assets/js/introjs.min.js',
            'assets/js/jquery-ui.min.js',
            'assets/js/pico_admin.js'
        );
        $this->mergeFiles($files, $this->plugin_path . '/assets/js/pico_admin.MERGED.js');
    }

    /**
     * Concatenate contents of given files (paths are given relative to pico admin plugin directory) and save result to a new file
     * Is run only if the merge-file does not exist yet, or Pico_Admin::is_devMode is true
     *
     * @param   array   $filePaths
     * @param   string  $mergedFile
     */
    private function mergeFiles(array $filePaths, $mergedFile){
        if( $this->is_devMode || ! file_exists($mergedFile) ) {
            $merged = '';
            foreach( $filePaths as $file ) {
                $merged .= file_get_contents($this->plugin_path . '/' . $file) . "\n";
            }

            $handle = fopen($mergedFile, 'w');
            fwrite($handle, $merged);
            fclose($handle);
        }
    }

    /**
     * Detect client locale and translate (if resp. language is available, otherwise fallback to english)
     *
     * @param   String  $html
     * @return  String
     */
    public static function translate($html) {
        $locale = Locale::acceptFromHttp($_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ]);
        $localeKey = substr($locale, 0, 2);

        $translationFile = dirname(__FILE__) .'/templates/translations/' . $localeKey . '.php';
        if( ! file_exists($translationFile)) {
            $translationFile = dirname(__FILE__) .'/templates/translations/en.php';
        }
        include($translationFile); // defines $translations as associative array of translation keys and labels
        foreach($translations as $key => $label) {
            $html = str_replace('trans.' . $key, $label, $html);
        }

        return $html;
    }

    /**
     * Extend the "pages" array with more data
     *
     * @param   array   $pages
     * @return  array
     */
    private function extendPageTree(array $pages){
        $pagesWithDirectories   = array();
        $listedPaths            = array();
        $urlRoot                = $this->getUrlRoot();

        if( file_exists(CONTENT_DIR . '404.md')) {
                // Make '404.md' editable by including it (topmost) in the page tree
            $page404    = CONTENT_DIR . '404.md';
            $pages = array_merge( array(array(
                'title'     => '404',
                'url'       => str_replace(CONTENT_EXT, '', str_replace('index'. CONTENT_EXT, '', str_replace(CONTENT_DIR, $this->getUrlRoot() . '/', $page404))),
                'content'   => file_get_contents($page404)
            )), $pages);
        }

        // Add parent paths for rendering directories in the tree, add more path variations per page
        foreach($pages as $pageData) {
            $page     = str_replace($this->getUrlRoot(), substr(CONTENT_DIR, 0, strlen(CONTENT_DIR)-1), $pageData['url']);
            $page .= ! is_file($page . CONTENT_EXT) ? 'index' . CONTENT_EXT : CONTENT_EXT;

            $pathRelFromContentRoot = str_replace(CONTENT_DIR, '', $page);    // path rel. to /content/ w/ filename

            $pageData['path']           = substr($pathRelFromContentRoot, 0, strrpos($pathRelFromContentRoot, '/') ); // path rel. to /content/ w/o current md file
            $pageData['path_full']      = $pathRelFromContentRoot;    // path rel. to /content/ with .md file
            $pageData['path_absolute']  = $page;
            $pageData['level']          = substr_count($page, '/') - 6;

            if( $this->endsWith($page, 'index.md') ) {
                $pageData['url'] .= 'index';
            }

            $pageData['parent_directories'] = array();
            if(!empty($pageData['path'])) {
                $directoriesAbove = explode('/', $pageData['path']);

                $currentParentDirectory = '/';
                foreach($directoriesAbove as $parentDirectory) {
                    $currentParentDirectory .= $parentDirectory . '/';
                    if( ! in_array($currentParentDirectory, $listedPaths) ) {
                        $pageData['parent_directories'][] = array(
                            'name'      => $currentParentDirectory,
                            'level'     => substr_count($currentParentDirectory, '/') - 1,
                            'data_url'  => $urlRoot . $currentParentDirectory
                        );

                        $listedPaths[] = $currentParentDirectory;
                    }
                }
            }
            if( empty($pageData['parent_directories']) ) {
                $pageData['parent_directories'] = false;
            }
            $pagesWithDirectories[] = $pageData;
        }

        return $pagesWithDirectories;
    }

    /**
     * @return  string
     */
    private function getUrlRoot() {
        return
            'http' . ( array_key_exists('HTTPS', $_SERVER) && $_SERVER[ 'HTTPS' ] == 'on' ? 's' : '' )
          . '://' . $_SERVER[ 'SERVER_NAME' ]
          . ( $_SERVER[ 'SERVER_PORT' ] != '80' ? ":" . $_SERVER[ 'SERVER_PORT' ] : '' )
          . '/'
          .  ($this->endsWith($this->basePath, '/') ? substr($this->basePath, 0, strlen($this->basePath) - 1) : $this->basePath )
        ;

    }

	/**
	 * Create new post
	 */
	private function do_new()
	{
        $this->checkLoggedIn();

		$title  = isset($_POST['title']) && $_POST['title'] ? strip_tags(urldecode($_POST['title'])) : '';
        $folders= array();
        if( strpos($title, '/')) {
            $folders= explode('/', $title);
            $title = array_pop($folders);
        }
 		$file = $this->slugify(basename($title));
		if(!$file) die(json_encode(array('error' => 'Error: Invalid file name')));
		
		$error = '';
        if( ! $this->endsWith($file, CONTENT_EXT)) {
            $file .= CONTENT_EXT;
        }
        $content = '';

            // create resp. directories from slashes in filename
        $pathFolders = implode('/', $folders);
		if(file_exists(CONTENT_DIR . $pathFolders . $file)){
			$error = 'Error: A post already exists with this title';
		} else {

            $pathFolder = array();
            foreach( $folders as $folder ) {
                $pathFolder []= $folder;
                $pathCurrent = CONTENT_DIR . implode('/', $folders);
                if( ! is_dir( $pathCurrent) ) {
                    if( ! mkdir($pathCurrent)) {
                        die('error - failed creating directory: "' . $pathCurrent . '".');
                    } else {
                        file_put_contents($pathCurrent . '/index.md', $this->getDefaultContent());
                    }
                }
            }

            $content = $this->getDefaultContent($title);
            if( ! file_exists(CONTENT_DIR . $pathFolders . '/' . $file) ) {
                file_put_contents(CONTENT_DIR . $pathFolders . '/' . $file, $content);
            }
		}
		
		die(json_encode(array(
			'title'		=> $title,
			'content' 	=> $content,
			'file' 		=> basename(str_replace(CONTENT_EXT, '', $file)),
			'error' 	=> $error
		)));
	}

    /**
     * @param   string  $title
     * @return  string
     */
    private function getDefaultContent($title = 'New Post'){
        return '/*
Title: '. $title .'
Placing: ' . $this->getNextPlacingNumber() . '
*/';

    }

	/**
	 * Get next "placing" number for a new page
	 */
	private function getNextPlacingNumber() {
		return count(scandir(CONTENT_DIR)) + 1;
	}

	/**
	 * Load post
	 */
	private function do_open()
	{
        $this->checkLoggedIn();
        list($file_url, $pathFile, $file) = $this->getFileParamsFromPost();

        $path = $this->endsWith($file_url, CONTENT_EXT)
            ? $file_url
            : (CONTENT_DIR . $pathFile . $file . CONTENT_EXT);

		if(file_exists($path)) {
            $_SESSION['openpost'] = $path;
            die(file_get_contents($path));
        }

        die('Error: Invalid file - ' . $path );
	}

	/**
	 * Save post
	 */
	private function do_save()
	{
        $this->checkLoggedIn();
        list($file_url, $pathFile, $file) = $this->getFileParamsFromPost();

		$content = isset($_POST['content']) && $_POST['content'] ? $_POST['content'] : '';
		if(!$content) die('Error: Invalid content');
		
		$file .= CONTENT_EXT;
		file_put_contents(CONTENT_DIR . $pathFile . $file, $content);
		die($content);
	}

    /**
     * Delete post (*.md), asset file (image) or directory (recursively)
     *
     * @param   bool    $isDirectory
     */
	private function do_delete($isDirectory = false)
	{
        $this->checkLoggedIn();
        list($file_url, $pathFile, $file) = $this->getFileParamsFromPost();

        if( !$isDirectory ) {
            if( strstr($file_url, '.') === false ) {
                    // Delete markdown file
                $fileMarkdown = $file . CONTENT_EXT;
                if( file_exists(CONTENT_DIR . $pathFile . $fileMarkdown) ) {
                    die( unlink(CONTENT_DIR . $pathFile . $fileMarkdown) );
                }
            } else {
                    // Delete asset file
                if( is_file($file_url)) {
                    unlink($file_url);
                }
            }
        } else {
                // Delete directory recursively
            if( strstr($file_url, $_SERVER[ 'DOCUMENT_ROOT' ]) === false ) {
                $pathDirectory = $_SERVER[ 'DOCUMENT_ROOT' ] . str_replace(array( 'http://', 'https://', ( $_SERVER[ 'SERVER_PORT' ] != '80' ? ":" . $_SERVER[ 'SERVER_PORT' ] : '' ), $_SERVER[ 'SERVER_NAME' ] ), '', $file_url);
                $pathDirectory = rtrim($pathDirectory, '/');
            } else {
                $pathDirectory = $file_url;
            }

            $this->removeDirectoryRecursive($pathDirectory);
        }
        die();
	}

    private function do_mkdir(){
        $this->checkLoggedIn();

        $path    = trim($_POST['path']);
        $dirName = trim($_POST['dirname']);

        if( !empty($path) && !empty($dirName) && is_dir($path) && ! is_dir($path . $dirName) ) {
            mkdir($path . $dirName);
        }

        die();
    }

    /**
     * Recursively delete given directory and everything in it
     *
     * @param   string  $pathDirectory
     */
    private function removeDirectoryRecursive($pathDirectory)
    {
        if( is_dir($pathDirectory) ) {
            $objects = scandir($pathDirectory);
            foreach( $objects as $object ) {
                if( $object != '.' && $object != '..' ) {
                    $rmPath = $pathDirectory . '/' . $object;
                    if( filetype($rmPath) == 'dir' ) {
                        $this->removeDirectoryRecursive($rmPath);
                    } else {
                        unlink($rmPath);
                    }
                }
            }
            reset($objects);
            rmdir($pathDirectory);
        } else {
            echo 'Error - not a directory: ' . $pathDirectory;
        }
    }

	/**
	 * Download asset
	 */
	private function do_download()
	{
        $this->checkLoggedIn();

        $pathFile = urldecode($_GET['file']);
        $filename = substr($pathFile, strrpos($pathFile, '/') + 1 );

        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        die( file_get_contents($pathFile) );
	}

	/**
	 * Rename file or directory
	 */
	private function do_rename()
	{
        $this->checkLoggedIn();
        if( $_POST['file'] === $_POST['renameTo']) die();

        $path = urldecode($_POST['file']);
        $pathContainsContentDir = strstr($path, CONTENT_DIR) !== false;
        if( $path[0] == '/' ) $path = substr($path, 1);
        $len = strlen($path);
        if( $path[$len - 1] == '/' ) $path = substr($path, 0, $len - 1);

        $pathAbsoluteOld = $pathContainsContentDir ? '/' . $path : CONTENT_DIR . $path;
        $pathAbsoluteNew = substr($pathAbsoluteOld, 0, strrpos($pathAbsoluteOld, '/') + 1) . urldecode($_POST['renameTo']);

        if( $pathAbsoluteNew != $pathAbsoluteOld ) {
            if( ! is_dir($pathAbsoluteOld) ) {
                $extensionOriginal = strtolower(substr($pathAbsoluteOld, strrpos($pathAbsoluteOld, '.') + 1 ));
                if( strtolower(substr($pathAbsoluteNew, strlen($pathAbsoluteNew) - strlen($extensionOriginal))) != $extensionOriginal ) {
                    die('Error - Changing file extension is not allowed.');
                }
            }
            rename($pathAbsoluteOld, $pathAbsoluteNew);
        }
        die();
	}

	/**
	 * @param   string          $text
	 * @return  mixed|string
	 */
	private function slugify($text)
	{ 
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
		
		// trim
		$text = trim($text, '-');
		
		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		
		// lowercase
		$text = strtolower($text);
		
		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);
		
		return empty($text) ? 'n-a' : $text;
	}

    /**
     * @return array
     */
    private function getFileParamsFromPost()
    {
		$file_url   = isset($_POST['file']) && $_POST['file'] ? $_POST['file'] : '';
        $pathFile   = $this->getFilePath($file_url);
		$file       = basename(strip_tags($file_url));

		if(!$file) die('Error: Invalid file');

        return array($file_url, $pathFile, $file);
    }

    /**
     * @param   string  $file_url
     * @return  mixed|string
     */
    private function getFilePath($file_url)
    {
        $pathFile = str_replace(array( 'http://', 'https://', $_SERVER[ 'SERVER_NAME' ] . ( $_SERVER[ 'SERVER_PORT' ] != '80' ? ":" . $_SERVER[ 'SERVER_PORT' ] : '' ) ), '', dirname(strip_tags($file_url)));
        $pathFile = substr($pathFile, strlen($this->basePath) + 1);
        if( !empty( $pathFile ) ) $pathFile .= '/';

        return $pathFile;
    }

    /**
	 * Render assets manager
	 */
	private function do_render_assetsmanager()
	{
        $this->checkLoggedIn();

        $pathAssets     = array_key_exists('file', $_POST) ? $_POST['file'] : CONTENT_DIR . 'images/';
        $pathAssetsRel  = str_replace(dirname(getcwd()) , '', $pathAssets);

        $assets = $this->scanAssets($pathAssets);

        $loader = new Twig_Loader_Filesystem($this->plugin_path);
        $twig_editor_assets = new Twig_Environment($loader);
        $twig_vars = array(
            'base_path'         => $this->basePath,
            'can_browse_up'     => strlen($pathAssetsRel) < 7 || ! substr($pathAssetsRel, -7) === 'images/',  // ends w/ "images/"?
            'path_assets_full'  => $pathAssets,
            'path_assets_rel'   => $pathAssetsRel,
            'assets'            => $assets
        );

		die($this->translate($twig_editor_assets->render('templates/assetsmanager.html', $twig_vars)));
	}

    /**
     * Get listing of files and directories (excluding thumbnail-directories) in given path
     * Create thumbnails for all images (not having thumbs yet) for previewing
     *
     * @param   string  $path
     * @return  array
     */
    private function scanAssets($path)
    {
        if(! is_dir($path) ) return array();

        $pathThumbs = $path . '/thumbs';
        if(! is_dir($pathThumbs)) {
            // create thumbs directory if missing
            mkdir($pathThumbs);
        }

        $assets       = array();
        $files        = scandir($path);
        $fileInfoMime = finfo_open(FILEINFO_MIME_TYPE);

        foreach($files as $filename) {
            if( strlen($filename) > 2 && $filename != '.DS_Store' ) {
                $filePathFull = $path . $filename;
                $isDirectory    = is_dir($filePathFull);

                if( !$isDirectory || $filename !== 'thumbs' ) {  // hide thumbs in backend, otherwise there would be thumbs of thumbs of thumbs..
                    $mimeType       = $isDirectory ? 'directory' : finfo_file($fileInfoMime, $filePathFull);

                    $assets[ ($isDirectory ? '0' : '' /* list directories topmost */) . $filename ] = array(
                        'is_root'           => $this->endsWith($filePathFull, 'content/images') ? 1 : 0,
                        'is_directory'      => $isDirectory,
                        'size'              => $isDirectory ? '' : $this->formatBytes(filesize($filePathFull)),
                        'filename'          => $filename,
                        'path_file_full'    => $filePathFull,
                        'path_file_relative'=> str_replace(getcwd(), '', $filePathFull),
                        'url_file'          => $this->getUrlRoot() . '/' . str_replace(ROOT_DIR, '', $filePathFull),
                        'path'              => $path,
                        'mime'              => $mimeType,
                        'icon'              => $isDirectory ? 'folder' : (strpos($mimeType, 'image') !== false ? 'picture-o' : 'file')
                    );

                    if( strstr($mimeType, 'image')) {
                        list($width, $height) = getimagesize( $filePathFull );
                        $assets[$filename]['width']     = $width;
                        $assets[$filename]['height']    = $height;

                        // Generate thumbnail if not there yet
                        $pathThumbFile = $pathThumbs . '/' . $filename;
                        if( ! file_exists($pathThumbFile) ) {
                            $this->generateThumb($filePathFull, $pathThumbFile);
                        }
                    }
                }
            }
        }

        ksort($assets);

        return $assets;
    }

    /**
     * @param   string  $pathImage
     * @param   string  $pathThumb
     * @param   int     $width
     */
    private function generateThumb($pathImage, $pathThumb, $width = 200)
    {
        $height = $width;
        list( $width_orig, $height_orig ) = getimagesize($pathImage);
        $ratio_orig = $width_orig / $height_orig;

        if( $width / $height > $ratio_orig ) {
            $width = $height * $ratio_orig;
        } else {
            $height = $width / $ratio_orig;
        }

        $image_p= imagecreatetruecolor($width, $height);
        $filenameLower = strtolower($pathImage);
        $image  = $this->endsWith($filenameLower, 'png') ? imagecreatefrompng($pathImage) : imagecreatefromjpeg($pathImage);

        if( $image_p && $image ) {
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

            // Output in same format (gif, jpg, gif)
            if( $this->endsWith($filenameLower, 'gif') ) {
                imagegif($image_p, $pathThumb);
            } else if( $this->endsWith($filenameLower, 'jpg') || $this->endsWith($filenameLower, 'jpeg') ) {
                imagejpeg($image_p, $pathThumb, 92);
            } else {
                imagepng($image_p, $pathThumb);
            }
        }
    }

    /**
     * @param   string      $haystack
     * @param   string      $needle
     * @return  boolean     Given string ends w/ given needle?
     */
    private function endsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * @param   int     $bytes
     * @param   int     $precision
     * @return  string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(( $bytes ? log($bytes) : 0 ) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[ $pow ];
    }

    /**
     * Ensure user is logged in, otherwise stop here.
     */
    private function checkLoggedIn()
    {
        if( !isset( $_SESSION[ 'pico_logged_in' ] ) || !$_SESSION[ 'pico_logged_in' ] ) {
            die( json_encode(array( 'error' => 'Error: Unathorized' )) );
        }
    }

    private function do_upload() {
        $this->checkLoggedIn();

        try {
            // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it as invalid.
            if( !isset( $_FILES['upfile']['error'] ) || is_array($_FILES['upfile']['error'])) {
                throw new RuntimeException('Invalid parameters.');
            }

            switch( $_FILES['upfile']['error'] ) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }

            // Check file size
            if( $_FILES[ 'upfile' ][ 'size' ] > 1000000 ) {
                throw new RuntimeException('Exceeded filesize limit.');
            }

            // Check MIME type (allowed: jpg, png, gif)
            $fInfo = new finfo(FILEINFO_MIME_TYPE);
            if( false === $ext = array_search($fInfo->file($_FILES[ 'upfile' ][ 'tmp_name' ]), array(
                 'jpg' => 'image/jpeg',
                 'png' => 'image/png',
                 'gif' => 'image/gif',
            ), true) ) {
                throw new RuntimeException('Invalid file format.');
            }

            $destination = $_POST['uppath'] . $_FILES['upfile']['name'];
            if( !move_uploaded_file( $_FILES['upfile']['tmp_name'], $destination) ) {
                throw new RuntimeException('Failed to move uploaded file.');
            }
        } catch( RuntimeException $e ) {
            echo $e->getMessage();
        }

        header('Location: /' . $this->basePath . '/admin', true, 302);
        die();
    }
}