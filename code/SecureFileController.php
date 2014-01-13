<?php
class SecureFileController extends Controller {

	/**
	 * The htaccess file name.
	 * Abstracted to allow configuration when
	 * this is overridden by apache config
	 */
	private static $htaccess_file = '.htaccess';

	/**
	 * Secure files htaccess rules
	 */
	public static function htaccess_rules() {
		$rewrite = array();
		$rewrite[] = 'RewriteEngine On';
		$rewrite[] = 'RewriteBase ' . (BASE_URL ? BASE_URL : '/');
		$rewrite[] = 'RewriteCond %{REQUEST_URI} ^(.*)$';
		$rewrite[] = 'RewriteRule .* ' . FRAMEWORK_DIR . '/main.php?url=%1 [QSA]';
		return implode("\n", $rewrite);
	}

	public function hasAction($action) {
		return true;
	}

	public function checkAccessAction($action) {
		return true;
	}

	/**
	 * Process all incoming requests passed to this controller, checking
	 * that the file exists and passing the file data with MIME type if possible.
	 */
	protected function handleAction($request, $action) {
		$url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];
		$file = File::find(Director::makeRelative($url));

		if($file instanceof File) {
			return $file->canView()
				? $this->sendFile($file)
				: Security::permissionFailure($this, 'You are not authorised to access this resource. Please log in.');
		} else {
			return new SS_HTTPResponse('Not Found', 404);
		}
	}

	/**
	 * Output file to the browser.
	 * We avoid SilverStripe's SS_HTTPResponse stuff here, to try and get this out as fast as possible
	 */
	function sendFile($file) {
		$path = $file->getFullPath();

		if(SapphireTest::is_running_test()) {
			return file_get_contents($path);
		}

		header('Content-Description: File Transfer');
		header('Content-Disposition: inline; filename=' . basename($path));
		header('Content-Length: ' . $file->getAbsoluteSize());
		header('Content-Type: ' . HTTP::get_mime_type($file->getRelativePath()));
		header('Content-Transfer-Encoding: binary');
		header('Pragma: '); // Fixes IE6,7,8 file downloads over HTTPS bug (http://support.microsoft.com/kb/812935)

		readfile($path);
		die();
	}

}

