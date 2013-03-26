<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This is the RSS-feed with all the guestbook comments
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class FrontendGuestbookRSS extends FrontendBaseBlock
{
	/**
	 * The comments
	 *
	 * @var	array
	 */
	private $items;

	/**
	 * Execute the extra
	 */
	public function execute()
	{
		parent::execute();
		$this->getData();
		$this->parse();
	}

	/**
	 * Load the data, don't forget to validate the incoming data
	 */
	private function getData()
	{
		$this->items = FrontendGuestbookModel::getAllComments();
	}

	/**
	 * Parse the data into the template
	 */
	private function parse()
	{
		// get vars
		$title = ucfirst(FL::msg('GuestbookAllComments'));
		$link = SITE_URL . FrontendNavigation::getURLForBlock('guestbook');
		$detailLink = SITE_URL . FrontendNavigation::getURLForBlock('guestbook');
		$description = null;

		// create new rss instance
		$rss = new FrontendRSS($title, $link, $description);

		// loop articles
		foreach($this->items as $item)
		{
			// init vars
			$title = $item['author'];
			$link = $detailLink . '#comment-' . $item['id'];
			$description = $item['text'];

			// create new instance
			$rssItem = new FrontendRSSItem($title, $link, $description);

			// set item properties
			$rssItem->setPublicationDate($item['created_on']);
			$rssItem->setAuthor($item['author']);

			// add item
			$rss->addItem($rssItem);
		}

		// output
		$rss->parse();
	}
}
