<?php
/**
 * Handles requests to a file path, checking if the file can be viewed given
 * it's access settings.
 *
 * {@link SecureFileController::handleRequest()} will determine whether the file
 * can be served, by checking the file path against the currently logged in {@link Member}
 *
 * See {@link SecureFileExtension} for how the access file is setup in a secured directory.
 */
class SecureFileController extends Controller {

	/**
	 * Process all incoming requests passed to this controller, checking
	 * that the file exists and passing the file through if possible.
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		$url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];
		$file = File::find(Director::makeRelative($url));

		if($file instanceof File) {
			if($file->canView()) {
				return $this->sendFile($file);
			}
			Security::permissionFailure($this, 'You are not authorised to access this resource. Please log in.');
		} else {
			$this->response = new SS_HTTPResponse('Not Found', 404);
		}

		return $this->response;
	}

	/**
	 * Output file to the browser.
	 * For performance reasons, we avoid SS_HTTPResponse and just output the contents instead.
	 */
	public function sendFile($file) {
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

