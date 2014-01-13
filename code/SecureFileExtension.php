<?php
class SecureFileExtension extends DataExtension {

	private static $db = array(
		'CanViewType' => 'Enum("Anyone,LoggedInUsers,OnlyTheseUsers,Inherit","Inherit")',
	);

	private static $many_many = array(
		'ViewerGroups' => 'Group',
	);

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

	function needsHTAccess() {
		if(SapphireTest::is_running_test()) {
			return false;
		}

		switch ($this->owner->CanViewType) {
			case 'LoggedInUsers':
			case 'OnlyTheseUsers':
				return true;
			case 'Inherit':
				// We don't need .htaccess if access is 'inherit', because apache also uses parent directories .htaccess files, all the way up 
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
	 * For folders, will need to add or remove the htaccess rules
	 * CAUTION: This will not work properly in the presence of third-party .htaccess file
	 */
	function onAfterWrite() {
		parent::onAfterWrite();

		if($this->owner instanceof Folder) {
			$htaccessFile = Config::inst()->get('SecureFileController', 'htaccess_file');
			$htaccess = $this->owner->getFullPath() . $htaccessFile;
			if($this->owner->needsHTAccess()) {
				if(!file_exists($htaccess)) file_put_contents($htaccess, SecureFileController::htaccess_rules());
			} else {
				if(file_exists($htaccess)) unlink($htaccess);				
			}
		}
	}

}

