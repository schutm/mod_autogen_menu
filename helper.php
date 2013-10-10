<?php
/**
* @version      mod_dynamic_menu
* @package      Joomla
* @copyright    Copyright (C) 2008 Jan Zikmund - info@janzikmund.cz
* @license      GNU/GPL, see LICENSE.php
* created using source codes of mod_placeHere by Eike Pierstorff
* eike@diebesteallerzeiten.de - thanks !
*
* File last changed 18.11.2011
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');

class modAutgenMenuHelper
{
	/**
	* Returns just the list of categories - when user selected to display articles
	*
	*/
	function getList(&$params)
	{
		global $mainframe;
		JPluginHelper::importPlugin('content');
		$limitstart    = JRequest::getVar('limitstart', 0, '', 'int');
		$dispatcher    =& JDispatcher::getInstance();
		$db            =& JFactory::getDBO();
		$user          =& JFactory::getUser();
		$userId        = (int) $user->get('id');
		
		//AuthLevels array
		$aid           = $user->get('_authLevels', 0);
		$count         = (int) $params->get('limit', false);
		// Id of currently displayed article
		$curid         = JRequest::getVar('Itemid', 0, '', 'int');

		//  Array of ids
		$showbycatid   =  $params->get('showbycatid') ;

		$contentConfig = &JComponentHelper::getParams('com_content');
		$access        = !$contentConfig->get('shownoauth');

		$nullDate      = $db->getNullDate();

		$now           = date('Y-m-d H:i:s', time());

		$where         = 'a.state = 1'
		               . ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
		               . ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
		;

		// ordering
		$sort = array();
		if ($params->get("category_order", 'none') != 'none')
			$sort[] = $params->get("category_order", 'none');
		if ($params->get("article_order", 'none') != 'none')
			$sort[] = $params->get("article_order", 'none');
		if (count($sort))
			$ordering = 'ORDER BY '.implode(' , ',$sort);
		else
			$ordering = '';
		
		// filter to set categories (taken as List, so in future will be easy to
		// extend to choose from more than one category/section)
		$ids = $showbycatid;
		JArrayHelper::toInteger( $ids );
		$Condition = ' AND (cc.id=' . implode( ' OR cc.id=', $ids ) . ')';

		$query = 'SELECT a.*, '
		       . ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'
		       . ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'
		       . ' , cc.id as catid, cc.title as category, cc.lft as cat_order '
		       . ' , u.name AS author '
		       . ' , a.ordering as art_order '
		       . ' , a.title as article '
		       . ' FROM jos_content AS a'
		       . ' INNER JOIN jos_categories AS cc ON cc.id = a.catid'
		       . ' LEFT JOIN jos_users AS u ON u.id = a.created_by '
		       . ' WHERE '. $where
		       . ($access ? ' AND a.access <= ' .(int) $aid. ' AND cc.access <= ' .(int) $aid : '')
		       . $Condition
		       . ' AND cc.published = 1 '
		       . $ordering
		       ;

		if($count) {
			$db->setQuery($query, 0, $count);
		} else {
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		for($i=0;$i<count($rows);$i++) {
			$rows[$i]->url = ContentHelperRoute::getArticleRoute($rows[$i]->slug,
			                                                     $rows[$i]->catslug,
			                                                     $rows[$i]->sectionid);
			$plugins       = $params->get('plugins', 0);

			switch($plugins) {
				case(1):
					$rows[$i]->event = new stdClass();
					$row->event->afterDisplayTitle = NULL;
					$rows[$i]->event->afterDisplayTitle = NULL;
					$rows[$i]->event->beforeDisplayContent = NULL;
					$rows[$i]->event->afterDisplayContent = NULL;
					break;
				case(0):
				default:
					$rows[$i]->event = new stdClass();
					$results = $dispatcher->trigger('onPrepareContent',
					                                array(& $rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$rows[$i]->event->preparContent = trim(implode("\n", $results));
					$results = $dispatcher->trigger('onAfterDisplayTitle',
					                                array($rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$rows[$i]->event->afterDisplayTitle = trim(implode("\n", $results));
					$results = $dispatcher->trigger('onBeforeDisplayContent',
					                                array(& $rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$rows[$i]->event->beforeDisplayContent = trim(implode("\n", $results));
					$results = $dispatcher->trigger('onAfterDisplayContent',
					                                array(& $rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$rows[$i]->event->afterDisplayContent = trim(implode("\n", $results));
					break;
			}
		}
		return $rows;
	}

	/**
	* Returns just the list of categories - when user selected not to display articles
	*
	*/
	function getCategoryList(&$params)
	{
		global $mainframe;
		JPluginHelper::importPlugin('content');
		$limitstart    = JRequest::getVar('limitstart', 0, '', 'int');
		$dispatcher    =& JDispatcher::getInstance();
		$db            =& JFactory::getDBO();
		$user          =& JFactory::getUser();
		$userId        = (int) $user->get('id');
		$aid           = $user->get('aid', 0);
		$count         = (int) $params->get('limit', false);
		// Id of currently displayed article
		$curid         = JRequest::getVar('id', 0, '', 'int');

		//  Array of ids
		$showbycatid   = $params->get('showbycatid');

		$contentConfig = &JComponentHelper::getParams( 'com_content' );
		$access        = !$contentConfig->get('shownoauth');

		$nullDate      = $db->getNullDate();

		$now           = date('Y-m-d H:i:s', time());

		jimport('joomla.utilities.date');

		// ordering
		$sort = array();
		if ($params->get("category_order", 'none') != 'none')
			$ordering = 'ORDER BY '.$params->get("category_order", 'category');
		else
			$ordering = '';

		// filter to set categories /section (taken as List, so in future will be
		// easy to extend to choose from more than one category/section)
		$ids = $showbycatid;
		JArrayHelper::toInteger( $ids );
		$Condition = ' (cc.id=' . implode( ' OR cc.id=', $ids ) . ')';

		$query = 'SELECT '
		       . ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'
		       . ' , cc.id as catid, cc.title as category, cc.lft as cat_order '
		       . ' FROM jos_categories AS cc '
		       . ' WHERE '
		       . $Condition
		       . ' AND cc.published = 1 '
		       . $ordering
		       ;
		
		if($count) {
			$db->setQuery($query, 0, $count);
		} else {
			$db->setQuery($query);
		}

		$rows = $db->loadObjectList();

		for($i=0;$i<count($rows);$i++) {
			$plugins = $params->get('plugins', 0);
			switch($plugins) {
				case(1):
					$rows[$i]->event = new stdClass();
					$row->event->afterDisplayTitle = NULL;
					$rows[$i]->event->afterDisplayTitle = NULL;
					$rows[$i]->event->beforeDisplayContent = NULL;
					$rows[$i]->event->afterDisplayContent = NULL;
					break;
				case(0):
				default:
					$rows[$i]->event = new stdClass();
					$results = $dispatcher->trigger('onPrepareContent',
					                                array(& $rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$results = $dispatcher->trigger('onAfterDisplayTitle',
					                                array($rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$rows[$i]->event->afterDisplayTitle = trim(implode("\n", $results));
					$results = $dispatcher->trigger('onBeforeDisplayContent',
					                                 array(& $rows[$i],
					                                       & $rows[$i]->parameters,
					                                       $limitstart));
					$rows[$i]->event->beforeDisplayContent = trim(implode("\n", $results));
					$results = $dispatcher->trigger('onAfterDisplayContent',
					                                array(& $rows[$i],
					                                      & $rows[$i]->parameters,
					                                      $limitstart));
					$rows[$i]->event->afterDisplayContent = trim(implode("\n", $results));
					break;
			}
		}
		return $rows;
	}
}
