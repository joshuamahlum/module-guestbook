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
class BackendGuestbookEditComment extends BackendBaseActionEdit
{
	/**
	 * Execute the action
	 */
	public function execute()
	{
		parent::execute();
		$this->getData();

		$this->loadForm();
		$this->validateForm();

		$this->parse();
		$this->display();
	}

	/**
	 * Get the data
	 */
	private function getData()
	{
		// get the record
		$this->id = $this->getParameter('id', 'int');
		$this->record = (array) BackendGuestbookModel::getComment($this->id);

		// no item found, throw an exceptions, because somebody is fucking with our URL
		if(empty($this->record)) $this->redirect(BackendModel::createURLForAction('index') . '&error=non-existing');
	}

	/**
	 * Load the form
	 */
	private function loadForm()
	{
		// create form
		$this->frm = new BackendForm('editComment');
		$this->frm->addText('author', $this->record['author']);
		$this->frm->addText('email', $this->record['email']);
		$this->frm->addText('website', $this->record['website'], null);
		$this->frm->addTextarea('text', $this->record['text']);

		// assign URL
		$this->tpl->assign('itemURL', BackendModel::getURLForBlock($this->getModule()) . '#comment-' . $this->record['id']);
	}

	/**
	 * Validate the form
	 */
	private function validateForm()
	{
		// is the form submitted?
		if($this->frm->isSubmitted())
		{
			// cleanup the submitted fields, ignore fields that were added by hackers
			$this->frm->cleanupFields();

			// validate fields
			$this->frm->getField('author')->isFilled(BL::err('AuthorIsRequired'));
			$this->frm->getField('email')->isEmail(BL::err('EmailIsInvalid'));
			$this->frm->getField('text')->isFilled(BL::err('FieldIsRequired'));
			if($this->frm->getField('website')->isFilled()) $this->frm->getField('website')->isURL(BL::err('InvalidURL'));

			// no errors?
			if($this->frm->isCorrect())
			{
				// build item
				$item['id'] = $this->id;
				$item['status'] = $this->record['status'];
				$item['author'] = $this->frm->getField('author')->getValue();
				$item['email'] = $this->frm->getField('email')->getValue();
				$item['website'] = ($this->frm->getField('website')->isFilled()) ? $this->frm->getField('website')->getValue() : null;
				$item['text'] = $this->frm->getField('text')->getValue();

				// insert the item
				BackendGuestbookModel::updateComment($item);

				// trigger event
				BackendModel::triggerEvent($this->getModule(), 'after_edit_comment', array('item' => $item));

				// everything is saved, so redirect to the overview
				$this->redirect(BackendModel::createURLForAction('index') . '&report=edited-comment&id=' . $item['id'] . '&highlight=row-' . $item['id'] . '#tab' . SpoonFilter::toCamelCase($item['status']));
			}
		}
	}
}
