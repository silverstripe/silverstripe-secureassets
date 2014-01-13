<?php
class SecureFileExtensionTest extends SapphireTest {

	static $fixture_file = 'SecureFileExtensionTest.yml';

	public function testAnyonePermissions() {
		$folder = $this->objFromFixture('Folder', 'anyone');
		$this->assertTrue($folder->canView());

		Session::set('loggedInAs', null);
		$this->assertTrue($folder->canView());
	}

	public function testInheritPermissions() {
		$folder = $this->objFromFixture('Folder', 'child-viewergroups-restricted');
		Session::set('loggedInAs', null);
		$this->assertFalse($folder->canView());

		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-1'));
		$this->assertTrue($folder->canView());

		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-2'));
		$this->assertFalse($folder->canView());
	}

	public function testLoggedInPermissions() {
		$folder = $this->objFromFixture('Folder', 'loggedin');
		Session::set('loggedInAs', null);
		$this->assertFalse($folder->canView());

		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-2'));
		$this->assertTrue($folder->canView());
	}

}
