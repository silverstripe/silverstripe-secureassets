<?php
class SecureFileControllerTest extends FunctionalTest {
	static $fixture_file = 'SecureFileControllerTest.yml';

	public function testCanDownloadPermissivePermission() {
		File::add_extension('SecureFileExtensionTest_PermissiveFileExtension');

		$file = $this->objFromFixture('File', 'permissive');
		Session::set('loggedInAs', null); // Logout, this file is only for LoggedInUsers, but extension should override
		$content = $this->get($file->Filename);
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);

		File::remove_extension('SecureFileExtensionTest_PermissiveFileExtension');
	}

	public function testCanDownloadRestrictivePermission() {
		File::add_extension('SecureFileExtensionTest_RestrictiveFileExtension');

		$oldAFR = $this->autoFollowRedirection;
		$this->autoFollowRedirection = false;

		$file = $this->objFromFixture('File', 'restrictive');
		$content = $this->get($file->Filename); // This file lets anyone access it, but our extension should override
		$this->assertTrue($content->getStatusCode() === 302); // Should redirect to Security/login
		$this->assertContains('Security/login', $content->getHeader('Location'));

		$this->autoFollowRedirection = $oldAFR;

		File::remove_extension('SecureFileExtensionTest_RestrictiveFileExtension');
	}

	public function setUp() {
		parent::setUp();

		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		/* Create a test files for each of the fixture references */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			$fh = fopen(BASE_PATH."/$file->Filename", "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	}

	public function tearDown() {
		parent::tearDown();

		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		// Remove left over folders and any files that may exist
		if(file_exists('../assets/FileTest.txt')) unlink('../assets/FileTest.txt');
	}
}

class SecureFileExtensionTest_PermissiveFileExtension extends DataExtension implements TestOnly {
	public function canDownload() {
		return true;
	}
}

class SecureFileExtensionTest_RestrictiveFileExtension extends DataExtension implements TestOnly {
	public function canDownload() {
		return false;
	}
}