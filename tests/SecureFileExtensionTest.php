<?php
class SecureFileExtensionTest extends SapphireTest {

	static $fixture_file = 'SecureFileExtensionTest.yml';

	public function testAnyonePermissions() {
		$folder = $this->objFromFixture('Folder', 'anyone');
		$this->assertEquals('Anyone', $folder->CanViewType);
		$this->assertTrue($folder->canView());

		Session::set('loggedInAs', null);
		$this->assertTrue($folder->canView());
	}

	public function testInheritPermissions() {
		$folder = $this->objFromFixture('Folder', 'child-viewergroups-restricted');
		Session::set('loggedInAs', null);
		$this->assertEquals('Inherit', $folder->CanViewType);
		$this->assertFalse($folder->canView());

		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-1'));
		$this->assertTrue($folder->canView());

		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-2'));
		$this->assertFalse($folder->canView());
	}

	public function testLoggedInPermissions() {
		$folder = $this->objFromFixture('Folder', 'loggedin');
		Session::set('loggedInAs', null);
		$this->assertEquals('LoggedInUsers', $folder->CanViewType);
		$this->assertFalse($folder->canView());

		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-2'));
		$this->assertTrue($folder->canView());
	}
	
	public function testDefaultPermissions() {
		$folder = $this->objFromFixture('Folder', 'default-root');
		Session::set('loggedInAs', null);
		$this->assertEquals('Inherit', $folder->CanViewType);
		$this->assertTrue($folder->canView());

		$subfolder = $this->objFromFixture('Folder', 'default-child');
		$this->assertEquals('Inherit', $subfolder->CanViewType);
		$this->assertTrue($subfolder->canView());
	}

	public function testRestrictedDefaultPermissions() {
		$folder = $this->objFromFixture('Folder', 'default-root');
		Session::set('loggedInAs', null);
		//Default access, logged out user
		$this->assertTrue($folder->canView());

		Config::inst()->update('SecureAssets', 'Defaults', array('Permission' => 'LoggedInUsers'));
		//Only logged in users access, logged out user
		$this->assertFalse($folder->canView());
		//Only logged in users access, logged in as Joe
		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-1'));
		$this->assertTrue($folder->canView());

		//Set access to OnlyTheseUsers and groups to test-group
		Config::inst()->update('SecureAssets',
			'Defaults',
			array('Permission' => 'OnlyTheseUsers',
				'Groups' => array('test-group')
			)
		);
		//member-1 is in test-group
		$this->assertTrue($folder->canView());
		//member-2 is not in test-group
		Session::set('loggedInAs', $this->idFromFixture('Member', 'member-2'));
		$this->assertFalse($folder->canView());

		//Test inheritance of a default permission
		$subfolder = $this->objFromFixture('Folder', 'default-child');
		$this->assertEquals('Inherit', $subfolder->CanViewType);
		$this->assertFalse($subfolder->canView());
	}

}
