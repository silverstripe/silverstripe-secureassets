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

	// We calculate the timelimit based on the filesize. Set to 0 to give unlimited timelimit.
	// The calculation is: give enough time for the user with x kB/s connection to donwload the entire file.
	// E.g. The default 50kB/s equates to 348 minutes per 1GB file.
	private static $min_download_bandwidth = 50; // [in kilobytes per second]

	/**
	 * @var string The Content-disposition for any files served by secureassets.
	 * @config
	 *
	 * The default is 'attachment', which will force the browser to treat any file as a download. Other useful values
	 * are 'inline' which will force the browser to treat any file as embeddable (e.g. display a JPG file in the
	 * browser). Note that this has security implications for files that the browser can render (e.g. SWF files) that
	 * can do malicious things in the website's context. Other less typical Content-disposition options are outlined by
	 * the IANA here: http://www.iana.org/assignments/cont-disp/cont-disp.xhtml
	 */
	private static $content_disposition = 'attachment';

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

		$this->extend('onBeforeInit');

		// Init
		$this->baseInitCalled = false;
		$this->init();
		if(!$this->baseInitCalled) {
			user_error("init() method on class '$this->class' doesn't call Controller::init()."
				. "Make sure that you have parent::init() included.", E_USER_WARNING);
		}

		$this->extend('onAfterInit');

		$url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];

		// remove any relative base URL and prefixed slash that get appended to the file path
		// e.g. /mysite/assets/test.txt should become assets/test.txt to match the Filename field on File record
		$url = Director::makeRelative(ltrim(str_replace(BASE_URL, '', $url), '/'));
		$self = $this;

		return $this->disableFilters(function () use ($url, $self) {
			$file = File::find($url);

			if ($self->canDownloadFile($file)) {
				// If we're trying to access a resampled image.
				if (preg_match('/_resampled\//', $url)) {
					// File::find() will always return the original image, but we still want to serve the resampled version.
					$file = new Image();
					$file->Filename = $url;
				}

				$self->extend('onBeforeSendFile', $file);

				return $self->sendFile($file);
			} else {
				if ($file instanceof File) {
					// Permission failure
					Security::permissionFailure($self, 'You are not authorised to access this resource. Please log in.');
				} else {
					// File doesn't exist
					return $self->httpError(404);
				}
			}

			return $self->getResponse();
		});
	}

	/**
	 * Execute a method with any filters disabled (Subsites, etc)
	 *
	 * @param string $callback
	 * @return mixed Result of the $callback being executed
	 */
	protected function disableFilters($callback) {
		// Backup and disable subsites
		$hasSubsites = class_exists('Subsite', false);
		$origSubsiteDisabled = null;
		if($hasSubsites) {
			$origSubsiteDisabled = Subsite::$disable_subsite_filter;
			Subsite::disable_subsite_filter(true);
		}

		// Backup and disable translatable
		$hasTranslatable = class_exists('Translatable', false);
		$origTranslatableEnabled = null;
		if($hasTranslatable) {
			$origTranslatableEnabled = Translatable::disable_locale_filter();
		}

		// Callback
		$result = call_user_func($callback);

		// Restore translatable
		if($hasTranslatable) {
			Translatable::enable_locale_filter($origTranslatableEnabled);
		}

		// Restore subsites
		if($hasSubsites) {
			Subsite::disable_subsite_filter($origSubsiteDisabled);
		}

		return $result;
	}

	/**
	 * Output file to the browser.
	 * For performance reasons, we avoid SS_HTTPResponse and just output the contents instead.
	 */
	public function sendFile($file) {
		$path = $file->getFullPath();

		if(!file_exists($path)) {
			return $this->httpError(404);
		}

		if(class_exists('SapphireTest', false) && SapphireTest::is_running_test()) {
			return file_get_contents($path);
		}

		$disposition = $this->config()->content_disposition;
		if(!$disposition) $disposition = 'attachment';

		header('Content-Description: File Transfer');
		// Quotes needed to retain spaces (http://kb.mozillazine.org/Filenames_with_spaces_are_truncated_upon_download)
		header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, basename($path)));
		header('Content-Length: ' . $file->getAbsoluteSize());
		header('Content-Type: ' . HTTP::get_mime_type($file->getRelativePath()));
		header('Content-Transfer-Encoding: binary');

		// Ensure we enforce no-cache headers consistently, so that files accesses aren't cached by CDN/edge networks
		header('Pragma: no-cache');
		header('Cache-Control: private, no-cache, no-store');

		if ($this->config()->min_download_bandwidth) {
			// Allow the download to last long enough to allow full download with min_download_bandwidth connection.
			increase_time_limit_to((int)(filesize($path)/($this->config()->min_download_bandwidth*1024)));
		} else {
			// Remove the timelimit.
			increase_time_limit_to(0);
		}

		// Clear PHP buffer, otherwise the script will try to allocate memory for entire file.
		while (ob_get_level() > 0) {
			ob_end_flush();
		}

		// Prevent blocking of the session file by PHP. Without this the user can't visit another page of the same
		// website during download (see http://konrness.com/php5/how-to-prevent-blocking-php-requests/)
		session_write_close();

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

