<?php
/**
 * Extension that allows a CMS user to define view
 * access to a particular {@link Folder} and the {@link File}s within.
 *
 * An access file with rewrite rules is written into the {@link Folder}
 * once it saved in the CMS (see {@link SecureFileExtension::onAfterWrite()}),
 * so that the webserver will force a rewrite on the requested assets file path,
 * turning it into a SilverStripe request so the file can be checked against
 * currently set access settings.
 *
 * Beware that this will have a performance impact on file requests for {@link Folder}
 * that have been secured, as the file request will no longer be passed through directly
 * by the webserver.
 */
class SecureFileExtension extends DataExtension {

	private static $db = array(
		'CanViewType' => 'Enum("Anyone,LoggedInUsers,OnlyTheseUsers,Inherit","Inherit")',
	);

	private static $many_many = array(
		'ViewerGroups' => 'Group',
	);

	private static $current_access_config = null;

	private static $access_config = array();

	/**
	 * Tries to autodetect the current webserver and match it against a registered
	 * webserver configuration through access_config. Check _config.php
	 * in this module for an example of how those access files are registered through the
	 * Config system.
	 *
	 * You can manually set the config by setting current_access_config yourself.
	 *
	 * @return array
	 */
	public function getAccessConfig() {
		$currentConfig = Config::inst()->get('SecureFileExtension', 'current_access_config');
		if($currentConfig) return $currentConfig;

		$registeredConfigs = Config::inst()->get('SecureFileExtension', 'access_config');
		if(!empty($_SERVER['SERVER_SOFTWARE'])) {
			if(strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
				return $registeredConfigs['Apache'];
			} elseif(strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
				return $registeredConfigs['IIS'];
			}
		}

		// fallback to Apache
		return $registeredConfigs['Apache'];
	}

	function canView($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// admin override
		if($member && Permission::checkMember($member, "ADMIN")) return true;

		if ($this->owner instanceof Folder) {
			switch ($this->owner->CanViewType) {
				case 'LoggedInUsers':
					return (bool)$member;
				case 'Inherit':
					if ($this->owner->ParentID) return $this->owner->Parent()->canView($member);
					else return true;
				case 'OnlyTheseUsers':
					if ($member && $member->inGroups($this->owner->ViewerGroups())) return true;
					else return false;
				case 'Anyone':
				default:
					return true;
			}
		}

		// File DataObjects created by SearchForm don't have a ParentID, which we need
		// We fix this by re-getting the File object by it's ID if the ParentID is missing and use that
		$file = $this->owner;
		if (!$file->ParentID) $file = DataObject::get_by_id('File', $file->ID);

		// If we still don't have a ParentID, give up and assume no access. Otherwise inherit permissions from the parent
		return $file->ParentID ? $file->Parent()->canView($member) : false;
	}

	function needsAccessFile() {
		if(SapphireTest::is_running_test()) {
			return false;
		}

		switch ($this->owner->CanViewType) {
			case 'LoggedInUsers':
			case 'OnlyTheseUsers':
				return true;
			case 'Inherit':
				// We don't need an access file if access is set to 'inherit', because Apache also uses parent directories .htaccess files
			case 'Anyone':
			default:
				return false;
		}
	}

	/**
	 * Access tab, copied from SiteTree
	 */
	public function updateCMSFields(FieldList $fields) {
		if(($this->owner instanceof Folder) && $this->owner->ID) {
			$options = array();
			if($this->owner->ParentID) $options['Inherit'] = _t('SecureFile.INHERIT', "Inherit from parent folder");
			$options['Anyone'] = _t('SiteTree.ACCESSANYONE', 'Anyone');
			$options['LoggedInUsers'] = _t('SiteTree.ACCESSLOGGEDIN', 'Logged-in users');
			$options['OnlyTheseUsers'] = _t('SiteTree.ACCESSONLYTHESE', 'Only these people (choose from list)');

			$fields->push(
				new HeaderField(
					'WhoCanViewHeader',
					_t('SecureFile.ACCESSHEADER', 'Who can view files in this folder?'),
					2
				)
			);

			$fields->push(
				new OptionsetField(
					'CanViewType',
					'',
					$options
				)
			);

			$fields->push(
				new TreeMultiselectField(
					'ViewerGroups',
					$this->owner->fieldLabel('ViewerGroups')
				)
			);
		}
	}

	/**
	 * Add or remove access rules to the filesystem path.
	 * CAUTION: This will not work properly in the presence of third-party .htaccess file
	 */
	function onAfterWrite() {
		parent::onAfterWrite();

		if($this->owner instanceof Folder) {
			$config = $this->getAccessConfig();
			$accessFilePath = $this->owner->getFullPath() . $config['file'];

			if($this->needsAccessFile()) {
				if(!file_exists($accessFilePath)) file_put_contents($accessFilePath, $config['content']);
			} else {
				if(file_exists($accessFilePath)) unlink($accessFilePath);
			}
		}
	}

}

