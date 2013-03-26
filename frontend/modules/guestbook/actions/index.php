<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This is the overview action
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class FrontendGuestbookIndex extends FrontendBaseBlock
{
	/**
	 * Guestbook comments
	 *
	 * @var	Array
	 */
	private $comments;

	/**
	 * Form instance
	 *
	 * @var FrontendForm
	 */
	private $form;

	/**
	 * Guestbook settings
	 *
	 * @var	Array
	 */
	private $settings;

	/**
	 * Execute the extra
	 */
	public function execute()
	{
		parent::execute();
		$this->loadTemplate();

		$this->getData();

		$this->loadForm();
		$this->validateForm();

		$this->parse();
	}

	/**
	 * Get the guestbook data
	 */
	private function getData()
	{
		// requested page
		$requestedPage = $this->URL->getParameter('page', 'int', 1);

		$this->pagination['url'] = FrontendNavigation::getURLForBlock('guestbook');
		$this->pagination['limit'] = FrontendModel::getModuleSetting('guestbook', 'overview_num_items', 10);
		$this->pagination['num_items'] = FrontendGuestbookModel::getAllCount();
		$this->pagination['num_pages'] = (int) ceil($this->pagination['num_items'] / $this->pagination['limit']);
		$this->pagination['requested_page'] = $requestedPage;
		$this->pagination['offset'] = ($this->pagination['requested_page'] * $this->pagination['limit']) - $this->pagination['limit'];

		if($this->pagination['num_pages'] == 0) $this->pagination['num_pages'] = 1;
		if($requestedPage > $this->pagination['num_pages'] || $requestedPage < 1) $this->redirect(FrontendNavigation::getURL(404));

		$this->settings = FrontendModel::getModuleSettings('guestbook');
		$this->comments = FrontendGuestbookModel::getComments($this->pagination['limit'], $this->pagination['offset']);
	}

	/**
	 * Load the form
	 */
	private function loadForm()
	{
		$author = (SpoonCookie::exists('comment_author')) ? SpoonCookie::get('comment_author') : null;
		$email = (SpoonCookie::exists('comment_email')) ? SpoonCookie::get('comment_email') : null;
		$website = (SpoonCookie::exists('comment_website')) ? SpoonCookie::get('comment_website') : 'http://';

		$this->form = new FrontendForm('guestbook_comment');
		$this->form->setAction($this->form->getAction() . '#' . FL::act('Comment'));
		$this->form->addText('author', $author);
		$this->form->addText('email', $email);
		$this->form->addText('website', $website);
		$this->form->addTextarea('message');
	}

	/**
	 * Parse the data into the template
	 */
	private function parse()
	{
		// get RSS-link
		$rssLink = FrontendModel::getModuleSetting('guestbook', 'feedburner_url_' . FRONTEND_LANGUAGE);
		if($rssLink == '') $rssLink = FrontendNavigation::getURLForBlock('guestbook', 'rss');

		// add RSS-feed
		$this->header->addLink(array(
			'rel' => 'alternate',
			'type' => 'application/rss+xml',
			'title' => FrontendModel::getModuleSetting('guestbook', 'rss_title_' . FRONTEND_LANGUAGE),
			'href' => $rssLink
		), true);

		if($this->URL->getParameter('comment', 'string') == 'moderation') $this->tpl->assign('commentIsInModeration', true);
		if($this->URL->getParameter('comment', 'string') == 'spam') $this->tpl->assign('commentIsSpam', true);
		if($this->URL->getParameter('comment', 'string') == 'true') $this->tpl->assign('commentIsAdded', true);

		// assign settings
		$this->form->parse($this->tpl);
		$this->tpl->assign('comments', $this->comments);
		$this->tpl->assign('settings', $this->settings);
		$this->parsePagination();
	}

	/**
	 * Validate the form
	 */
	private function validateForm()
	{
		// is the form submitted
		if($this->form->isSubmitted())
		{
			// cleanup the submitted fields
			$this->form->cleanupFields();

			if(SpoonSession::exists('guestbook_comment'))
			{
				// calculate the time difference
				$diff = time() - (int) SpoonSession::get('guestbook_comment');

				// if the time difference isn't 10 seconds, tell the user to slow down
				if($diff < 10 && $diff != 0) $this->form->getField('message')->addError(FL::err('CommentTimeout'));
			}

			// validate the form fields
			$this->form->getField('author')->isFilled(FL::err('AuthorIsRequired'));
			$this->form->getField('email')->isEmail(FL::err('EmailIsRequired'));
			$this->form->getField('message')->isFilled(FL::err('MessageIsRequired'));

			// validate optional fields
			if($this->form->getField('website')->isFilled() && $this->form->getField('website')->getValue() != 'http://')
			{
				$this->form->getField('website')->isURL(FL::err('InvalidURL'));
			}

			// no errors
			if($this->form->isCorrect())
			{
				// settings
				$spamFilterEnabled = (isset($this->settings['spamfilter']) && $this->settings['spamfilter']);
				$moderationFilterEnabled = (isset($this->settings['moderation']) && $this->settings['moderation']);

				// reformat data
				$author = $this->form->getField('author')->getValue();
				$email = $this->form->getField('email')->getValue();
				$website = $this->form->getField('website')->getValue();
				$website = (trim($website) == '' || $website == 'http://') ? null : $website;
				$message = $this->form->getField('message')->getValue();

				// build data array
				$comment['language'] = FRONTEND_LANGUAGE;
				$comment['author'] = $author;
				$comment['email'] = $email;
				$comment['website'] = $website;
				$comment['text'] = $message;
				$comment['created_on'] = FrontendModel::getUTCDate();
				$comment['status'] = 'published';
				$comment['data'] = serialize(array('server' => $_SERVER));

				// create links
				$permaLink = FrontendNavigation::getURLForBlock('guestbook');
				$redirectLink = $permaLink;

				// is moderation enabled
				if($moderationFilterEnabled)
				{
					if(!FrontendGuestbookModel::isModerated($author, $email)) $comment['status'] = 'moderation';
				}

				// is spamfilter enabled
				if($spamFilterEnabled)
				{
					if(FrontendModel::isSpam($message, SITE_URL . $permaLink, $author, $email, $website)) $comment['status'] = 'spam';
				}

				// insert comment
				$comment['id'] = FrontendGuestbookModel::insertComment($comment);
				FrontendGuestbookModel::notifyAdmin($comment);

				// trigger event
				FrontendModel::triggerEvent('guestbook', 'after_add_comment', array('comment' => $comment));

				// set a comment session to block excesive usage
				SpoonSession::set('guestbook_comment', time());

				// append a parameter to the URL so we can show moderation
				$redirectPrefix = '&';
				if(strpos($redirectLink, '?') === false) $redirectPrefix = '?';
				if($comment['status'] == 'moderation') $redirectLink .= $redirectPrefix . 'comment=moderation#' . FL::act('Comment');
				if($comment['status'] == 'spam') $redirectLink .= $redirectPrefix . 'comment=spam#' . FL::act('Comment');
				if($comment['status'] == 'published') $redirectLink .= $redirectPrefix . 'comment=true#comment-' . $comment['id'];

				// save author-data in cookies
				try
				{
					SpoonCookie::set('comment_author', $author, (30 * 24 * 60 * 60), '/', '.' . $this->URL->getDomain());
					SpoonCookie::set('comment_email', $email, (30 * 24 * 60 * 60), '/', '.' . $this->URL->getDomain());
					SpoonCookie::set('comment_website', $website, (30 * 24 * 60 * 60), '/', '.' . $this->URL->getDomain());
				}
				catch(Exception $e)
				{
					// setting the cookies failed but because this isn't important, don't show an error.
				}

				// rederict back to guestbook
				$this->redirect($redirectLink);
			}
		}
	}
}
