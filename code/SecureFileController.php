<?php
/**
 * Handles requests to a file path, checking if the file can be viewed given
 * it's access settings.
 *
 * {@link SecureFileController::handleRequest()} will determine whether the file
 * can be served, by checking {@link SecureFileExtension::canView()}
 *
 * See {@link SecureFileExtension} for how the access file is setup in a secured directory.
 */
class SecureFileController extends Controller {

	/**
	 * Process all incoming requests passed to this controller, checking
	 * that the file exists and passing the file through if possible.
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		// Copied from Controller::handleRequest()
		$this->pushCurrent();
		$this->urlParams = $request->allParams();
		$this->request = $request;
		$this->response = new SS_HTTPResponse();
		$this->setDataModel($model);

		$url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];

		// remove any relative base URL and prefixed slash that get appended to the file path
		// e.g. /mysite/assets/test.txt should become assets/test.txt to match the Filename field on File record
		$url = ltrim(str_replace(BASE_URL, '', $url), '/');
		$file = File::find(Director::makeRelative($url));

		if($this->canDownloadFile($file)) {
			return $this->sendFile($file);
		} else {
			if($file instanceof File) {
				// Permission failure
				Security::permissionFailure($this, 'You are not authorised to access this resource. Please log in.');
			} else {
				// File doesn't exist
				$this->response = new SS_HTTPResponse('File Not Found', 404);
			}
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

	public function canDownloadFile(File $file = null) {
		if($file instanceof File) {
			// Implement a FileExtension with canDownload(), and we'll test that first
			$results = $file->extend('canDownload');
			if($results && is_array($results)) {
				if(!min($results)) return false;
				else return true;
			}

			// If an extension with canDownload() can't be found, fallback to using canView
			if($file->canView()) {
				return true;
			}

			return false;
		}

		return false;
	}

}

