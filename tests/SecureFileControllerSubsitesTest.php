<?php

/**
 * Test secure files that belong to subsites
 */
class SecureFileControllerSubsitesTest extends FunctionalTest
{
    protected static $fixture_file = 'SecureFileControllerSubsitesTest.yml';

    public function setUp() {
        // Reset to default
        if(class_exists('Subsite', false)) {
            Subsite::$use_session_subsiteid = true;
            Subsite::$force_subsite = null;
        } else {
            $this->skipTest = true;
        }

		parent::setUp();

        if($this->skipTest) {
            $this->markTestSkipped('This test requires subsites');
        }

		if(!file_exists(ASSETS_PATH)) {
            mkdir(ASSETS_PATH);
        }

		/* Create a test files for each of the fixture references */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
            $path = $file->getFullPath();
			$fh = fopen($path, "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	}

	public function tearDown() {
		parent::tearDown();

        // Reset to default
        if(class_exists('Subsite', false)) {
            Subsite::$use_session_subsiteid = true;
            Subsite::$force_subsite = null;
        }

		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
            $path = $file->getFullPath();
			if(file_exists($path)) {
                unlink($path);
            }
		}
	}

    /**
     * Avoid subsites filtering on fixture fetching.
     */
    public function objFromFixture($class, $id)
    {
        Subsite::disable_subsite_filter(true);
        $obj = parent::objFromFixture($class, $id);
        Subsite::disable_subsite_filter(false);

        return $obj;
    }

    public function testFileSubsiteAccessible() {
        // Files are accessible in public subsite
        Subsite::changeSubsite(0);
        $subsite1 = $this->idFromFixture('Subsite', 'subsite1');
        $subsite2 = $this->idFromFixture('Subsite', 'subsite2');
        $this->logInWithPermission('ADMIN');

        /** @var File $file1 */
        $file1 = $this->objFromFixture('File', 'file1');
        /** @var File $file2 */
        $file2 = $this->objFromFixture('File', 'file1');

        // Both files accessible
		$content = $this->get($file1->Link());
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);
		$content = $this->get($file2->Link());
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);

        // Test with subsite1
        Subsite::changeSubsite($subsite1);

        // Both files accessible
		$content = $this->get($file1->Link());
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);
		$content = $this->get($file2->Link());
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);

        // Test with subsite1
        Subsite::changeSubsite($subsite2);

        // Both files accessible
		$content = $this->get($file1->Link());
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);
		$content = $this->get($file2->Link());
		$this->assertContains('xxxxx', $content->getBody());
		$this->assertTrue($content->getStatusCode() === 200);
    }

}
