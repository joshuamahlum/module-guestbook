<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * Installer for the guestbook module
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class GuestbookInstaller extends ModuleInstaller
{
	/**
	 * Install the module
	 */
	public function install()
	{
		// load install.sql
		$this->importSQL(dirname(__FILE__) . '/data/install.sql');

		// add 'guestbook' as a module
		$this->addModule('guestbook', 'The guestbook module.');

		// import locale
		$this->importLocale(dirname(__FILE__) . '/data/locale.xml');

		// module rights
		$this->setModuleRights(1, 'guestbook');

		// action rights
		$this->setActionRights(1, 'guestbook', 'edit_comment');
		$this->setActionRights(1, 'guestbook', 'index');
		$this->setActionRights(1, 'guestbook', 'mass_comment_action');
		$this->setActionRights(1, 'guestbook', 'settings');

		// set navigation
		$navigationModulesId = $this->setNavigation(null, 'Modules');
		$this->setNavigation($navigationModulesId, 'Guestbook', 'guestbook/index', array('guestbook/edit_comment'));

		// settings navigation
		$navigationSettingsId = $this->setNavigation(null, 'Settings');
		$navigationModulesId = $this->setNavigation($navigationSettingsId, 'Modules');
		$this->setNavigation($navigationModulesId, 'Guestbook', 'guestbook/settings');

		// general settings
		$this->setSetting('guestbook', 'allow_comments', true);
		$this->setSetting('guestbook', 'requires_akismet', true);
		$this->setSetting('guestbook', 'spamfilter', false);
		$this->setSetting('guestbook', 'moderation', false);
		$this->setSetting('guestbook', 'overview_num_items', 10);

		// add extra's
		$guestbookID = $this->insertExtra('guestbook', 'block', 'guestbook', null, null, 'N', 1000);

		// loop languages
		foreach($this->getLanguages() as $language)
		{
			// feedburner URL
			$this->setSetting('guestbook', 'feedburner_url_' . $language, '');

			// RSS settings
			$this->setSetting('guestbook', 'rss_meta_' . $language, true);
			$this->setSetting('guestbook', 'rss_title_' . $language, 'RSS');
			$this->setSetting('guestbook', 'rss_description_' . $language, '');

			// check if a page for guestbook already exists in this language
			$existsPage = (bool) $this->getDB()->getVar(
				'SELECT COUNT(p.id)
				 FROM pages AS p
				 INNER JOIN pages_blocks AS b ON b.revision_id = p.revision_id
				 WHERE b.extra_id = ? AND p.language = ?',
				array($guestbookID, $language)
			);

			if(!$existsPage)
			{
				// insert page
				$this->insertPage(array(
					'title' => 'Guestbook', 'language' => $language),
					null, array('extra_id' => $guestbookID)
				);
			}

			// install example data if requested
			if($this->installExample()) $this->installExampleData($language);
		}
	}

	/**
	 * Install example data
	 *
	 * @param string $language The language to use.
	 */
	private function installExampleData($language)
	{
		// get db instance
		$db = $this->getDB();

		// check if guestbook comment already exist in this language
		$commentExists = (bool) $db->getVar(
			'SELECT COUNT(id)
			 FROM guestbook
			 WHERE language = ?',
			array($language)
		);

		if(!$commentExists)
		{
			// insert example comment 1
			$db->insert('guestbook', array(
				'language' => $language,
				'created_on' => gmdate('Y-m-d H:i:00'),
				'author' => 'Matthias Mullie',
				'email' => 'forkcms-sample@mullie.eu',
				'website' => 'http://www.mullie.eu',
				'text' => 'cool!',
				'status' => 'published',
				'data' => null)
			);

			// insert example comment 2
			$db->insert('guestbook', array(
				'language' => $language,
				'created_on' => gmdate('Y-m-d H:i:00'),
				'author' => 'Davy Hellemans',
				'email' => 'forkcms-sample@spoon-library.com',
				'website' => 'http://www.spoon-library.com',
				'text' => 'awesome!',
				'status' => 'published',
				'data' => null)
			);

			// insert example comment 3
			$db->insert('guestbook', array(
				'language' => $language,
				'created_on' => gmdate('Y-m-d H:i:00'),
				'author' => 'Tijs Verkoyen',
				'email' => 'forkcms-sample@sumocoders.be',
				'website' => 'http://www.sumocoders.be',
				'text' => 'wicked!',
				'status' => 'published',
				'data' => null)
			);
		}
	}
}

