<?php

/**
 * Pico Admin plugin 2.0.1 for Pico CMS
 *
 * @author Kay Stenschke
 * @link http://www.coexec.com
 * @version 2.0.1
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
        $this->plugin_path 	= dirname(__FILE__);

        require_once($this->plugin_path . '/lib/helper_strings.php');
        require_once($this->plugin_path . '/lib/helper_server.php');
        require_once($this->plugin_path . '/lib/helper_files.php');

        $this->is_admin		= false;
        $this->is_logout 	= false;
		$this->password 	= '';

        $this->is_preview = Pico_Admin_Helper_Strings::endsWith($_SERVER['REQUEST_URI'], 'preview.php');
        if( ! $this->is_preview && session_id() == '' ) {
            session_start();
        }
		
		if(file_exists($this->plugin_path .'/admin_config.php')){
			global $pico_admin_password;

			include_once($this->plugin_path .'/admin_config.php');
			$this->password = $pico_admin_password;
		} else {
            die('Fatal error: admin_config.php missing.');
        }

        $this->basePath = ltrim(str_replace(array($_SERVER['DOCUMENT_ROOT'], '/content'), '', CONTENT_DIR), '/');
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

            case 'admin/metatitle':
                $this->do_metatitle();
                break;
            case 'admin/metadescription':
                $this->do_metadescription();
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

			} elseif(!isset($_SESSION['pico_logged_in']) || !$_SESSION['pico_logged_in']) {
				if(isset($_POST['password'])){
					if(    sha1($_POST['password']) == $this->password
                        && ( ! array_key_exists('pico_logged_in', $_SESSION) || $_SESSION['pico_logged_in'] !== true)
                    ){
						$_SESSION['pico_logged_in']     = true;
                        $twig_vars['is_initial_login']  = true;
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

			echo Pico_Admin_Helper_Strings::translate($twig_editor->render('templates/' . $template . '.html', $twig_vars)); // Render admin.html or login.html
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
     * Extend the "pages" array with more data
     *
     * @param   array   $pages
     * @return  array
     */
    private function extendPageTree(array $pages){
        $pages = $this->add404ToPageTree($pages);

        $protocol = Pico_Admin_Helper_Server::getProtocol();
        $basePath = Pico_Admin_Helper_Server::getCurrentUrl(true);
        $urlRoot  = Pico_Admin_Helper_Server::getUrlRoot($this->basePath);

            // List paths NOT to be displayed in the tree up-front
        $listedPaths            = array(
            '/', '/' . $protocol . '://', '/' . $protocol . ':/',
            '/' . $protocol . '://' . $_SERVER['SERVER_NAME'] . '/',
            '/' . $basePath, '/' . $basePath . '/', $basePath . '/'
        );

        // Add parent paths for rendering directories in the tree, add more path variations per page
        $pagesWithDirectories   = array();
        foreach($pages as $pageData) {
            $page  = str_replace($urlRoot, substr(CONTENT_DIR, 0, strlen(CONTENT_DIR)-1), $pageData['url']);

            $page .= ! is_file($page . CONTENT_EXT) ? 'index' . CONTENT_EXT : CONTENT_EXT;

            $pathRelFromContentRoot = str_replace(CONTENT_DIR, '', $page);    // path rel. to /content/ w/ filename

            $pathWithoutMdFile  = substr($pathRelFromContentRoot, 0, strrpos($pathRelFromContentRoot, '/'));
            $pageData['path']   = str_replace(  // path rel. to /content/ w/o current md file
                array($basePath . '/', $basePath),
                '',
                $pathWithoutMdFile
            );

            $pageData['path_full']      = $pathRelFromContentRoot;    // path rel. to /content/ with .md file
            $pageData['path_absolute']  = $page;
            $pageData['level']          = substr_count($page, '/') - 4;

            if( Pico_Admin_Helper_Strings::endsWith($page, 'index.md') ) {
                // is index page of root or a subdirectory
                if(! Pico_Admin_Helper_Strings::endsWith($pageData['url'], '/')) {
                    $pageData['url'] .= '/';
                }

                //if( !file_exists() ) {
                //    $pageData[ 'url' ] .= 'index';
                //}
            }

            $pageData['parent_directories'] = array();
            if(!empty($pageData['path'])) {
                // Add parent directories
                $directoriesAbove = explode('/', $pageData['path']);

                $currentParentDirectory = '/';
                foreach($directoriesAbove as $parentDirectory) {
                    $currentParentDirectory .= $parentDirectory . '/';
                    if( ! in_array($currentParentDirectory, $listedPaths, false) ) {
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
 		$file = Pico_Admin_Helper_Strings::slugify(basename($title));
		if(!$file) die(json_encode(array('error' => 'Error: Invalid file name')));
		
		$error = '';
        if( ! Pico_Admin_Helper_Strings::endsWith($file, CONTENT_EXT)) {
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

        $path = Pico_Admin_Helper_Strings::endsWith($file_url, CONTENT_EXT)
            ? $file_url
            : (CONTENT_DIR . $pathFile . $file );

        $path = $this->correctPagePath($path);

		if(file_exists($path)) {
            $_SESSION['openpost'] = $path;
            die( file_get_contents( $path ) );
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

        $file = $this->correctPagePath($file);

        file_put_contents(CONTENT_DIR . $pathFile . $file, $content);
		die($content);
	}

    /**
     * Detect if the given path points to a content-directory, add "/index.md" than
     * Ensure page path to end on the correct content extension (.md)
     *
     * @param   String  $path
     * @return  String
     */
    private static function correctPagePath($path) {
        if( is_dir($path) ) {
            $path .= '/index';
        }

        if(! Pico_Admin_Helper_Strings::endsWith($path, CONTENT_EXT)) {
            $path .= CONTENT_EXT;
        }

        while( Pico_Admin_Helper_Strings::endsWith($path, CONTENT_EXT . CONTENT_EXT) ) {
            $path = substr($path, 0, strlen($path) - 3);
        }

        return $path;
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
            $baseSuffix = Pico_Admin_Helper_Server::getUrlBaseSuffix();
            $pathDirectory = str_replace($baseSuffix, $baseSuffix . '/content', $pathDirectory);

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
        Pico_Admin_Helper_Files::download($pathFile, substr($pathFile, strrpos($pathFile, '/') + 1 ));
	}

	/**
	 * Rename file or directory
	 */
	private function do_rename()
	{
        $this->checkLoggedIn();

        if( $_POST['file'] === $_POST['renameTo']) {
            // don't rename a file to its identical name
            die();
        }

        $path = urldecode($_POST['file']);
        $pathContainsContentDir = strstr($path, CONTENT_DIR) !== false;
        if( $path[0] === '/' ) {
            $path = substr($path, 1);
        }
        $len = strlen($path);
        if( $path[$len - 1] === '/' ) {
            $path = substr($path, 0, $len - 1);
        }

        $filename = basename($path);

        $pathAbsoluteOld = $pathContainsContentDir ? '/' . $path : CONTENT_DIR . $path;
        $filenameOld = basename($pathAbsoluteOld);

        $pathAbsoluteNew = substr($pathAbsoluteOld, 0, strrpos($pathAbsoluteOld, '/') + 1) . urldecode($_POST['renameTo']);
        $filenameNew = basename($pathAbsoluteNew);

        if( $pathAbsoluteNew != $pathAbsoluteOld ) {
            if( ! is_dir($pathAbsoluteOld) ) {
                $extensionOriginal = strtolower(substr($pathAbsoluteOld, strrpos($pathAbsoluteOld, '.') + 1 ));
                if( strtolower(substr($pathAbsoluteNew, strlen($pathAbsoluteNew) - strlen($extensionOriginal))) != $extensionOriginal ) {
                    die('Error - Changing file extension is not allowed.');
                }
            }
            rename($pathAbsoluteOld, $pathAbsoluteNew);

            $pathAbsoluteThumbOld = str_replace($filename, 'thumbs/' . $filename, $pathAbsoluteOld);
            $pathAbsoluteThumbNew = str_replace($filename, 'thumbs/' . $filename, $pathAbsoluteNew);
            rename($pathAbsoluteThumbOld, $pathAbsoluteThumbNew);
        }

        // Update meta.php
        $path     = str_replace($filename, '', $pathAbsoluteOld);
        if( file_exists($path . 'meta.php') ) {
            $meta = array();
            include($path . 'meta.php');

            if( array_key_exists($filenameOld, $meta)) {
                $attributes = $meta[$filenameOld];
                unset($meta[$filenameOld]);
                $meta[$filenameNew] = $attributes;
                file_put_contents($path . 'meta.php', '<?php $meta = ' . var_export($meta, true) . ';');
            }
        }

        die();
	}

	/**
	 * Store file's meta title
	 */
	private function do_metatitle()
	{
        $this->do_meta_attribute('title', 'title');
	}

	/**
	 * Store file's meta title
	 */
	private function do_metadescription()
	{
        $this->do_meta_attribute('description', 'description');
	}

    /**
     * Stores a meta value from (read from the given post-var) into the given meta key
     *
     * @param   String  $postVarKey
     * @param   String  $metaKey
     */
    private function do_meta_attribute($postVarKey = 'title', $metaKey = 'title')
    {
        $this->checkLoggedIn();

        $metaValue = urldecode($_POST[$postVarKey]);

        $pathFile = urldecode($_POST['file']);
        $filename = substr($pathFile, strrpos($pathFile, '/') + 1 );
        $path     = str_replace($filename, '', $pathFile);

        /** @var array  $meta */
        include($path . 'meta.php');

        $meta[$filename][$metaKey]    = $metaValue;

        file_put_contents($path . 'meta.php', '<?php $meta = ' . var_export($meta, true) . ';');
    }

    /**
     * @return array
     */
    private function getFileParamsFromPost()
    {
		$file_url   = isset($_POST['file']) && $_POST['file'] ? $_POST['file'] : '';

        $pathFile   = Pico_Admin_Helper_Files::getFilePath( Pico_Admin_Helper_Server::getCurrentUrl(true), $file_url );
		$pathFile   = ltrim( str_replace($_SERVER[ 'SERVER_NAME' ] . '/' . $this->basePath, '', $pathFile), '/');

        $file       = basename(strip_tags($file_url));

		if(!$file) {
            die('Error - Invalid file: ' . $file);
        }

        return array($file_url, $pathFile, $file);
    }

    /**
	 * Render assets manager
	 */
	private function do_render_assetsmanager()
	{
        $this->checkLoggedIn();

        if( ! is_dir(CONTENT_DIR . 'images') ) {
            if(! mkdir(CONTENT_DIR . 'images') ) {
                die('Error (check file permissions) - create directory failed at: ' . CONTENT_DIR . 'images');
            }
        }

        $pathAssets     = array_key_exists('file', $_POST) ? $_POST['file'] : CONTENT_DIR . 'images/';
        $pathAssetsRel  = str_replace(dirname(getcwd()) , '', $pathAssets);

        /** @var  array $meta */
        $assets = $this->scanAssets($pathAssets);
        if( file_exists($pathAssets . '/meta.php')) {
            include($pathAssets . '/meta.php');
        } else {
            // there are no images in the path, and thus no 'meta.php' file
            $meta = array();
        }

        $loader = new Twig_Loader_Filesystem($this->plugin_path);
        $twig_editor_assets = new Twig_Environment($loader);
        $twig_vars = array(
            'base_path'         => $this->basePath,
            'can_browse_up'     => strlen($pathAssetsRel) < 7 || ! substr($pathAssetsRel, -7) === 'images/',  // ends w/ "images/"?
            'path_assets_full'  => $pathAssets,
            'path_assets_rel'   => $pathAssetsRel,
            'assets'            => $assets,
            'assets_meta'       => $meta
        );

		die(Pico_Admin_Helper_Strings::translate($twig_editor_assets->render('templates/assetsmanager.html', $twig_vars)));
	}

    /**
     * Get listing of files and directories (excluding thumbnail-directories) in given path
     * Create thumbnails for all images (not having thumbs yet) for previewing
     * Create meta.php file for title and descriptions of images
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
        $metaCode     = "<?php\n" . '   $meta = array(';
        $imagesCount  = 0;

        foreach($files as $filename) {
            if( strlen($filename) > 2 && $filename !== '.DS_Store' && $filename !== 'meta.php' ) {
                $filePathFull = $path . $filename;
                $isDirectory    = is_dir($filePathFull);

                if( !$isDirectory || $filename !== 'thumbs' ) {  // hide thumbs in backend, otherwise there would be thumbs of thumbs of thumbs..
                    $mimeType       = $isDirectory ? 'directory' : finfo_file($fileInfoMime, $filePathFull);

                    $assets[ ($isDirectory ? '0' : '' /* list directories topmost */) . $filename ] = array(
                        'is_root'           => Pico_Admin_Helper_Strings::endsWith($filePathFull, 'content/images') ? 1 : 0,
                        'is_directory'      => $isDirectory,
                        'size'              => $isDirectory ? '' : Pico_Admin_Helper_Strings::formatBytes(filesize($filePathFull)),
                        'filename'          => $filename,
                        'path_file_full'    => $filePathFull,
                        'path_file_relative'=> str_replace(getcwd(), '', $filePathFull),
                        'url_file'          => Pico_Admin_Helper_Server::getUrlRoot($this->basePath) . '/' . str_replace(ROOT_DIR, '', $filePathFull),
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

                        $metaCode     .= ($imagesCount > 0 ? ',' : '') .
                            "\n" .
                            "       '$filename' => array(\n" .
                            "           'title'         => '',\n" .
                            "           'description'   => ''\n" .
                            '       )';

                        $imagesCount++;
                    }
                }
            }
        }
        $metaCode     .= ');';

        ksort($assets);

        // Create (if missing) meta.php file w/ title and description per image
        if( $imagesCount > 0 && ! file_exists($path . '/meta.php') ) {
            $handle = fopen($path . '/meta.php', 'w');
            fwrite($handle, $metaCode);
            fclose($handle);
        }

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

        $image          = null;
        $fileExtension  = Pico_Admin_Helper_Files::getFileExtension($filenameLower);
        switch($fileExtension) {
            case 'png':
                $image = imagecreatefrompng($pathImage);
                break;

            case 'jpg':case 'jpeg':
                $image = imagecreatefromjpeg($pathImage);
                break;

            default:
                $image = imagecreatefromgif($pathImage);
        }

        if( $image_p && $image != null ) {
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

            // Output in same format (gif, jpg, gif)
            if( $fileExtension === 'gif' ) {
                imagegif($image_p, $pathThumb);
            } elseif( $fileExtension === 'jpg' || $fileExtension === 'jpeg' ) {
                imagejpeg($image_p, $pathThumb, 92);
            } else {
                imagepng($image_p, $pathThumb);
            }
        }
    }

    /**
     * Ensure user is logged in, otherwise stop here.
     */
    private function checkLoggedIn()
    {
        if( !isset( $_SESSION[ 'pico_logged_in' ] ) || !$_SESSION[ 'pico_logged_in' ] ) {
            if( $this->is_preview ) {
                die(json_encode(array('error' => 'Error: Unathorized')));
            }
            header('Location: admin');
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

    /**
     * @param array $pages
     * @return array
     */
    private function add404ToPageTree(array $pages)
    {
        if( file_exists(CONTENT_DIR . '404.md') ) {
            // Make '404.md' editable by including it (topmost) in the page tree
            $page404 = CONTENT_DIR . '404.md';

            return array_merge(array(
               array(
                   'title'  => '404',
                   'url'    => str_replace(CONTENT_EXT, '', str_replace('index' . CONTENT_EXT, '', str_replace(CONTENT_DIR, Pico_Admin_Helper_Server::getUrlRoot($this->basePath) . '/', $page404))), 'content' => file_get_contents($page404))
            ), $pages);
        }

        return $pages;
    }
}