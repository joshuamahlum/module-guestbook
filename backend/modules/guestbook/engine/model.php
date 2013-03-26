<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * In this file we store all the generic functions for the backend of the guestbook module
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class BackendGuestbookModel
{
	const QRY_DATAGRID_BROWSE_COMMENTS =
		'SELECT c.id, UNIX_TIMESTAMP(c.created_on) AS created_on, c.author, c.text
		 FROM guestbook AS c
		 WHERE status = ? AND language = ?';

	/**
	 * Deletes one or more comments
	 *
	 * @param array $ids The id(s) of the items(s) to delete.
	 */
	public static function deleteComments($ids)
	{
		// make sure $ids is an array
		$ids = (array) $ids;

		// loop and cast to integers
		foreach($ids as &$id) $id = (int) $id;

		// create an array with an equal amount of questionmarks as ids provided
		$idPlaceHolders = array_fill(0, count($ids), '?');

		// get db
		$db = BackendModel::getContainer()->get('database');

		// update record
		$db->delete(
			'guestbook',
			'language = ? AND id IN (' . implode(', ', $idPlaceHolders) . ')',
			array_merge(array(BL::getWorkingLanguage()), $ids)
		);
	}

	/**
	 * Get all data for a given id
	 *
	 * @param int $id The comment id.
	 */
	public static function getComment($id)
	{
		return (array) BackendModel::getContainer()->get('database')->getRecord(
			'SELECT i.*, UNIX_TIMESTAMP(i.created_on) AS created_on
			 FROM guestbook AS i
			 WHERE i.id = ?
			 LIMIT 1',
			array((int) $id)
		);
	}

	/**
	 * Get multiple comments at once
	 *
	 * @param array $ids The id(s) of the comment(s).
	 * @return array
	 */
	public static function getComments(array $ids)
	{
		return (array) BackendModel::getContainer()->get('database')->getRecords(
			'SELECT *
			 FROM guestbook AS i
			 WHERE i.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')',
			$ids
		);
	}

	/**
	 * Update a comment
	 *
	 * @param array $item The new data.
	 * @return int
	 */
	public static function updateComment(array $item)
	{
		return (int) BackendModel::getContainer()->get('database')->update(
			'guestbook',
			$item,
			'id=?',
			array((int) $item['id'])
		);
	}

	/**
	 * Updates one or more comments' status
	 *
	 * @param array $ids The id(s) of the comment(s) to change the status for.
	 * @param string $status The new status.
	 */
	public static function updateCommentStatuses($ids, $status)
	{
		// make sure $ids is an array
		$ids = (array) $ids;

		// loop and cast to integers
		foreach($ids as &$id) $id = (int) $id;

		// create an array with an equal amount of questionmarks as ids provided
		$idPlaceHolders = array_fill(0, count($ids), '?');

		// update record
		BackendModel::getContainer()->get('database')->execute(
			'UPDATE guestbook
			 SET status = ?
			 WHERE id IN (' . implode(', ', $idPlaceHolders) . ')',
			array_merge(array((string) $status), $ids)
		);
	}
}
