<?php
/**
* @version		mod_dynamic_menu
* @package		Joomla
* @copyright	Copyright (C) 2008 Jan Zikmund - info@janzikmund.cz
* @license		GNU/GPL, see LICENSE.php
* created using source codes of mod_placeHere by Eike Pierstorff eike@diebesteallerzeiten.de - thanks !
*
* File last changed 26.05.2008
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');


class modAutgenMenuHelper
{	
	function getList(&$params)
	{
		global $mainframe;
		JPluginHelper::importPlugin('content');
		$limitstart	= JRequest::getVar('limitstart', 0, '', 'int');
		// $dispatcher	   =& JEventDispatcher::getInstance();
		$dispatcher	   =& JDispatcher::getInstance();
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$userId		= (int) $user->get('id');
		$aid		= $user->get('aid', 0);
		$count		= (int) $params->get('limit', false);
		// Id of currently displayed article
		$curid		= JRequest::getVar('id', 0, '', 'int');

		$generateFrom = $params->get('generate_from', 'category');
		//  ID or comma separated lists of ids
		$showbysecid		= trim( $params->get('showbysecid') );
		$showbycatid		= trim( $params->get('showbycatid') );		

		$contentConfig = &JComponentHelper::getParams( 'com_content' );
		$access		= !$contentConfig->get('shownoauth');

		$nullDate	= $db->getNullDate();

		$now		= date('Y-m-d H:i:s', time());
		// $date = new JDate();
		// $now = $date->toMySQL();
		//jimport('joomla.utilities.date');  
		//$now = JFactory::getDate()->toFormat('%Y-%m-%d %H:%M:%S');

		$where		= 'a.state = 1'
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
		
		// filter to set categories /section (taken as List, so in future will be easy to extend to choose from more than one category/section)
		switch ($generateFrom) {
			case 'category':
				$ids = explode( ',',  $showbycatid );
				JArrayHelper::toInteger( $ids );
				$Condition = ' AND (cc.id=' . implode( ' OR cc.id=', $ids ) . ')';		
				break;
			case 'section':
				$ids = explode( ',',  $showbysecid );
				JArrayHelper::toInteger( $ids );
				$Condition = ' AND (s.id=' . implode( ' OR s.id=', $ids ) . ')';						
				break;
		}

		$query = 'SELECT a.*, ' .
		' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'.
		' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'.
		' , cc.id as catid, cc.title as category, cc.ordering as cat_order ' .
		' , s.id as sectionid, s.title as section' .
		' , u.name AS author '	.		
		' , a.ordering as art_order ' .
		' , a.title as article ' .
		' FROM #__content AS a' .
		' INNER JOIN #__categories AS cc ON cc.id = a.catid' .
		' INNER JOIN #__sections AS s ON s.id = a.sectionid' .
		' LEFT JOIN #__users AS u ON u.id = a.created_by '.
		' WHERE '. $where .' AND s.id > 0' .
		($access ? ' AND a.access <= ' .(int) $aid. ' AND cc.access <= ' .(int) $aid. ' AND s.access <= ' .(int) $aid : '').
		$Condition .
		' AND s.published = 1' .
		' AND cc.published = 1 ' .
		$ordering;
		
		if($count) {
		 $db->setQuery($query, 0, $count);
		} else {
		 $db->setQuery($query);
		}
		$rows = $db->loadObjectList();

		for($i=0;$i<count($rows);$i++) {
		 $rows[$i]->url = ContentHelperRoute::getArticleRoute($rows[$i]->slug, $rows[$i]->catslug, $rows[$i]->sectionid);
		 
		 $rows[$i]->parameters	= new JParameter( $rows[$i]->attribs );	      	
		 $plugins	= $params->get('plugins', 0);
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
		    	$results = $dispatcher->trigger('onPrepareContent', array (& $rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$results = $dispatcher->trigger('onAfterDisplayTitle', array ($rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$rows[$i]->event->afterDisplayTitle = trim(implode("\n", $results));
		    	$results = $dispatcher->trigger('onBeforeDisplayContent', array (& $rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$rows[$i]->event->beforeDisplayContent = trim(implode("\n", $results));
		    	$results = $dispatcher->trigger('onAfterDisplayContent', array (& $rows[$i], & $rows[$i]->parameters,$limitstart));
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
		$limitstart	= JRequest::getVar('limitstart', 0, '', 'int');
		// $dispatcher	   =& JEventDispatcher::getInstance();
		$dispatcher	   =& JDispatcher::getInstance();
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$userId		= (int) $user->get('id');
		$aid		= $user->get('aid', 0);
		$count		= (int) $params->get('limit', false);
		// Id of currently displayed article
		$curid		= JRequest::getVar('id', 0, '', 'int');

		$generateFrom = $params->get('generate_from', 'category');
		//  ID or comma separated lists of ids
		$showbysecid		= trim( $params->get('showbysecid') );
		$showbycatid		= trim( $params->get('showbycatid') );		

		$contentConfig = &JComponentHelper::getParams( 'com_content' );
		$access		= !$contentConfig->get('shownoauth');

		$nullDate	= $db->getNullDate();

		$now		= date('Y-m-d H:i:s', time());
		// $date = new JDate();
		// $now = $date->toMySQL();
		jimport('joomla.utilities.date');  
		//$now = JFactory::getDate()->toFormat('%Y-%m-%d %H:%M:%S');
/*
		$where		= 'a.state = 1'
		. ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
		. ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
		;
*/
		// ordering
		$sort = array();
		if ($params->get("category_order", 'none') != 'none')
			$ordering = 'ORDER BY '.$params->get("category_order", 'category');
		else
			$ordering = '';
		
		// filter to set categories /section (taken as List, so in future will be easy to extend to choose from more than one category/section)
		switch ($generateFrom) {
			case 'category':
				$ids = explode( ',',  $showbycatid );
				JArrayHelper::toInteger( $ids );
				$Condition = ' AND (cc.id=' . implode( ' OR cc.id=', $ids ) . ')';		
				break;
			case 'section':
				$ids = explode( ',',  $showbysecid );
				JArrayHelper::toInteger( $ids );
				$Condition = ' AND (s.id=' . implode( ' OR s.id=', $ids ) . ')';						
				break;
		}

		$query = 'SELECT ' .
//		' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'.
		' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'.
		' , cc.id as catid, cc.title as category, cc.ordering as cat_order ' .
		' , s.id as sectionid, s.title as section' .
//		' , u.name AS author '	.		
//		' , a.ordering as art_order ' .
//		' , a.title as article ' .
		' FROM #__categories AS cc ' .
		' INNER JOIN #__sections AS s ON cc.section = s.id' .
//		' LEFT JOIN #__users AS u ON u.id = a.created_by '.
		' WHERE s.id > 0' .
//		($access ? ' AND a.access <= ' .(int) $aid. ' AND cc.access <= ' .(int) $aid. ' AND s.access <= ' .(int) $aid : '').
		$Condition .
		' AND s.published = 1' .
		' AND cc.published = 1 ' .
		$ordering;
		
		if($count) {
		 $db->setQuery($query, 0, $count);
		} else {
		 $db->setQuery($query);
		}
		$rows = $db->loadObjectList();

		for($i=0;$i<count($rows);$i++) {
//		 $rows[$i]->url = ContentHelperRoute::getArticleRoute($rows[$i]->slug, $rows[$i]->catslug, $rows[$i]->sectionid);
		 
		 $rows[$i]->parameters	= new JParameter( $rows[$i]->attribs );	      	
		 $plugins	= $params->get('plugins', 0);
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
		    	$results = $dispatcher->trigger('onPrepareContent', array (& $rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$results = $dispatcher->trigger('onAfterDisplayTitle', array ($rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$rows[$i]->event->afterDisplayTitle = trim(implode("\n", $results));
		    	$results = $dispatcher->trigger('onBeforeDisplayContent', array (& $rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$rows[$i]->event->beforeDisplayContent = trim(implode("\n", $results));
		    	$results = $dispatcher->trigger('onAfterDisplayContent', array (& $rows[$i], & $rows[$i]->parameters,$limitstart));
		    	$rows[$i]->event->afterDisplayContent = trim(implode("\n", $results));
		   break;
		 }	
		}
	return $rows;
	}	
	
}
