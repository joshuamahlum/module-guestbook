<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This is the index action, it will display the overview of guestbook comments
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class BackendGuestbookIndex extends BackendBaseActionIndex
{
	/**
	 * DataGrids
	 *
	 * @var	BackendDataGridDB
	 */
	private $dgPublished, $dgModeration, $dgSpam;

	/**
	 * Execute the action
	 */
	public function execute()
	{
		parent::execute();
		$this->loadDataGrids();

		$this->parse();
		$this->display();
	}

	/**
	 * Loads the datagrids
	 */
	protected function loadDataGrids()
	{
		/*
		 * DataGrid for the published comments.
		 */
		$this->dgPublished = new BackendDataGridDB(BackendGuestbookModel::QRY_DATAGRID_BROWSE_COMMENTS, array('published', BL::getWorkingLanguage()));
		$this->dgPublished->setActiveTab('tabPublished');
		$this->dgPublished->setPagingLimit(30);
		$this->dgPublished->setHeaderLabels(array('created_on' => ucfirst(BL::lbl('Date')), 'text' => ucfirst(BL::lbl('Comment'))));
		$this->dgPublished->setMassActionCheckboxes('checkbox', '[id]');
		$this->dgPublished->setColumnFunction(array('BackendDataGridFunctions', 'getTimeAgo'), '[created_on]', 'created_on', true);
		$this->dgPublished->setColumnFunction(array('BackendDataGridFunctions', 'cleanupPlaintext'), '[text]', 'text', true);
		$this->dgPublished->setSortingColumns(array('created_on', 'text', 'author'), 'created_on');
		$this->dgPublished->setSortParameter('desc');
		$this->dgPublished->addColumn('edit', null, BL::lbl('Edit'), BackendModel::createURLForAction('edit_comment') . '&amp;id=[id]', BL::lbl('Edit'));
		$this->dgPublished->addColumn('mark_as_spam', null, BL::lbl('MarkAsSpam'), BackendModel::createURLForAction('mass_comment_action') . '&amp;id=[id]&amp;from=published&amp;action=spam', BL::lbl('MarkAsSpam'));
		$ddmMassAction = new SpoonFormDropdown('action', array('moderation' => BL::lbl('MoveToModeration'), 'spam' => BL::lbl('MoveToSpam'), 'delete' => BL::lbl('Delete')), 'spam');
		$ddmMassAction->setAttribute('id', 'actionPublished');
		$ddmMassAction->setOptionAttributes('delete', array('data-message-id' => 'confirmDeletePublished'));
		$ddmMassAction->setOptionAttributes('spam', array('data-message-id' => 'confirmSpamPublished'));
		$this->dgPublished->setMassAction($ddmMassAction);

		/*
		 * DataGrid for the comments that need moderation.
		 */
		$this->dgModeration = new BackendDataGridDB(BackendGuestbookModel::QRY_DATAGRID_BROWSE_COMMENTS, array('moderation', BL::getWorkingLanguage()));
		$this->dgModeration->setActiveTab('tabModeration');
		$this->dgModeration->setPagingLimit(30);
		$this->dgModeration->setHeaderLabels(array('created_on' => ucfirst(BL::lbl('Date')), 'text' => ucfirst(BL::lbl('Comment'))));
		$this->dgModeration->setMassActionCheckboxes('checkbox', '[id]');
		$this->dgModeration->setColumnFunction(array('BackendDataGridFunctions', 'getTimeAgo'), '[created_on]', 'created_on', true);
		$this->dgModeration->setColumnFunction(array('BackendDataGridFunctions', 'cleanupPlaintext'), '[text]', 'text', true);
		$this->dgModeration->setSortingColumns(array('created_on', 'text', 'author'), 'created_on');
		$this->dgModeration->setSortParameter('desc');
		$this->dgModeration->addColumn('edit', null, BL::lbl('Edit'), BackendModel::createURLForAction('edit_comment') . '&amp;id=[id]', BL::lbl('Edit'));
		$this->dgModeration->addColumn('approve',null, BL::lbl('Approve'), BackendModel::createURLForAction('mass_comment_action') . '&amp;id=[id]&amp;from=moderation&amp;action=published', BL::lbl('Approve'));
		$ddmMassAction = new SpoonFormDropdown('action', array('published' => BL::lbl('MoveToPublished'), 'spam' => BL::lbl('MoveToSpam'), 'delete' => BL::lbl('Delete')), 'published');
		$ddmMassAction->setAttribute('id', 'actionModeration');
		$ddmMassAction->setOptionAttributes('delete', array('data-message-id' => 'confirmDeleteModeration'));
		$ddmMassAction->setOptionAttributes('spam', array('data-message-id' => 'confirmSpamModeration'));
		$this->dgModeration->setMassAction($ddmMassAction);

		/*
		 * DataGrid for the comments that are marked as spam
		 */
		$this->dgSpam = new BackendDataGridDB(BackendGuestbookModel::QRY_DATAGRID_BROWSE_COMMENTS, array('spam', BL::getWorkingLanguage()));
		$this->dgSpam->setActiveTab('tabSpam');
		$this->dgSpam->setPagingLimit(30);
		$this->dgSpam->setHeaderLabels(array('created_on' => ucfirst(BL::lbl('Date')), 'text' => ucfirst(BL::lbl('Comment'))));
		$this->dgSpam->setMassActionCheckboxes('checkbox', '[id]');
		$this->dgSpam->setColumnFunction(array('BackendDataGridFunctions', 'getTimeAgo'), '[created_on]', 'created_on', true);
		$this->dgSpam->setColumnFunction(array('BackendDataGridFunctions', 'cleanupPlaintext'), '[text]', 'text', true);
		$this->dgSpam->setSortingColumns(array('created_on', 'text', 'author'), 'created_on');
		$this->dgSpam->setSortParameter('desc');
		$this->dgSpam->addColumn('approve',null, BL::lbl('Approve'), BackendModel::createURLForAction('mass_comment_action') . '&amp;id=[id]&amp;from=spam&amp;action=published', BL::lbl('Approve'));
		$ddmMassAction = new SpoonFormDropdown('action', array('published' => BL::lbl('MoveToPublished'), 'moderation' => BL::lbl('MoveToModeration'), 'delete' => BL::lbl('Delete')), 'published');
		$ddmMassAction->setAttribute('id', 'actionSpam');
		$ddmMassAction->setOptionAttributes('delete', array('data-message-id' => 'confirmDeleteSpam'));
		$this->dgSpam->setMassAction($ddmMassAction);
	}

	/**
	 * Parse & display the page
	 */
	protected function parse()
	{
		// published datagrid and num results
		$this->tpl->assign('dgPublished', ($this->dgPublished->getNumResults() != 0) ? $this->dgPublished->getContent() : false);
		$this->tpl->assign('numPublished', $this->dgPublished->getNumResults());

		// moderaton datagrid and num results
		$this->tpl->assign('dgModeration', ($this->dgModeration->getNumResults() != 0) ? $this->dgModeration->getContent() : false);
		$this->tpl->assign('numModeration', $this->dgModeration->getNumResults());

		// spam datagrid and num results
		$this->tpl->assign('dgSpam', ($this->dgSpam->getNumResults() != 0) ? $this->dgSpam->getContent() : false);
		$this->tpl->assign('numSpam', $this->dgSpam->getNumResults());
	}
}
