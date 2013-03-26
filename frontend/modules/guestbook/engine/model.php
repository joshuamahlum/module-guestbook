<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * In this file we store all generic functions that we will be using with the guestbook.
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class FrontendGuestbookModel
{
	/**
	 * Get all the comments
	 *
	 * @return array
	 */
	public static function getAllComments()
	{
		// get the comments
		$comments = (array) FrontendModel::getContainer()->get('database')->getRecords(
			'SELECT c.id, UNIX_TIMESTAMP(c.created_on) AS created_on, c.author,
			 	c.email, c.website, c.text
			 FROM guestbook AS c
			 WHERE c.status = ? AND c.language = ?
			 ORDER BY c.id ASC',
			array('published', FRONTEND_LANGUAGE)
		);

		// set gravatar id
		foreach($comments as $key => $comment) $comments[$key]['gravatar_id'] = md5($comment['email']);

		// return
		return $comments;
	}

	/**
	 * Get the number of items
	 *
	 * @return int
	 */
	public static function getAllCount()
	{
		return (int) FrontendModel::getContainer()->get('database')->getVar(
			'SELECT COUNT(i.id) AS count
			 FROM guestbook AS i
			 WHERE i.status = ? AND i.language = ?',
			array('published', FRONTEND_LANGUAGE)
		);
	}

	/**
	 * Get the comments
	 *
	 * @param int[optional] $limit The number of items to get.
	 * @param int[optional] $offset The offset.
	 * @return array
	 */
	public static function getComments($limit = 10, $offset = 0)
	{
		// get the comments
		$comments = (array) FrontendModel::getContainer()->get('database')->getRecords(
			'SELECT c.id, UNIX_TIMESTAMP(c.created_on) AS created_on, c.author,
			 	c.email, c.website, c.text
			 FROM guestbook AS c
			 WHERE c.status = ? AND c.language = ?
			 ORDER BY c.id DESC
			 LIMIT ?,?',
			array('published', FRONTEND_LANGUAGE, (int) $offset, (int) $limit)
		);

		// set gravatar id
		foreach($comments as $key => $comment) $comments[$key]['gravatar_id'] = md5($comment['email']);

		// return
		return $comments;
	}

	/**
	 * Insert a comment
	 *
	 * @param array $comment The inserted comment id.
	 * @return int
	 */
	public static function insertComment(array $comment)
	{
		$comment['id'] = (int) FrontendModel::getContainer()->get('database')->insert('guestbook', $comment);
		return $comment['id'];
	}

	/**
	 * See if comment is moderated
	 *
	 * @param string $author The name from the author.
	 * @param string $email The email from the author.
	 * @return bool
	 */
	public static function isModerated($author, $email)
	{
		return (bool) FrontendModel::getContainer()->get('database')->getVar(
			'SELECT COUNT(c.id)
			 FROM guestbook AS c
			 WHERE c.author = ? AND c.email = ? AND c.status = ?',
			array($author, $email, 'published')
		);
	}

	/**
	 * Notify admin about a new entry
	 *
	 * @param array $comment The comment that was submitted.
	 */
	public static function notifyAdmin(array $comment)
	{
		// don't notify when a comment is marked as spam
		if($comment['status'] == 'spam') return;

		// build data for push notification
		if($comment['status'] == 'moderation') $alert = array('loc-key' => 'NEW_COMMENT_TO_MODERATE');
		else $alert = array('loc-key' => 'NEW_COMMENT');

		// get number of moderations to show in the badge
		$badge = (int) FrontendModel::getContainer()->get('database')->getVar(
			'SELECT COUNT(b.id)
			 FROM guestbook AS b
			 WHERE b.status = ? AND b.language = ?
			 GROUP BY b.status',
			array('moderation', FRONTEND_LANGUAGE)
		);

		if($badge == 0) $badge == null;

		// build data
		$data = array('data' => array('endpoint' => SITE_URL . '/api/1.0', 'comment_id' => $comment['id']));

		// push to apple device
		FrontendModel::pushToAppleApp($alert, $badge, null, $data);

		// get settings
		$notifyByEmailModeration = FrontendModel::getModuleSetting('guestbook', 'notify_by_email_on_new_comment_to_moderate', false);
		$notifyByEmailNewComment = FrontendModel::getModuleSetting('guestbook', 'notify_by_email_on_new_comment', false);

		// create urls
		$viewURL = SITE_URL . FrontendNavigation::getURLForBlock('guestbook') . '#comment-' . $comment['id'];
		$backendURL = SITE_URL . FrontendNavigation::getBackendURLForBlock('index', 'guestbook') . '#tabModeration';

		if($notifyByEmailModeration && $comment['status'] == 'moderation')
		{
			// set variables
			$variables['message'] = vsprintf(FL::msg('GuestbookEmailNotificationsNewCommentToModerate'), array($comment['author'], $viewURL, FL::lbl('Guestbook'), $backendURL));

			// send email
			FrontendMailer::addEmail(FL::msg('NotificationSubject'), FRONTEND_CORE_PATH . '/layout/templates/mails/notification.tpl', $variables);
		}
		elseif($notifyByEmailNewComment)
		{
			if($comment['status'] == 'published')
			{
				// set variables
				$variables['message'] = vsprintf(FL::msg('GuestbookEmailNotificationsNewComment'), array($comment['author'], $viewURL, FL::lbl('Guestbook')));
			}
			elseif($comment['status'] == 'moderation')
			{
				// set variables
				$variables['message'] = vsprintf(FL::msg('GuestbookEmailNotificationsNewCommentToModerate'), array($comment['author'], $viewURL, FL::lbl('Guestbook'), $backendURL));
			}

			// send email
			FrontendMailer::addEmail(FL::msg('NotificationSubject'), FRONTEND_CORE_PATH . '/layout/templates/mails/notification.tpl', $variables);
		}
	}
}
