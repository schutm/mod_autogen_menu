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
$mode = $params->get("outputmode",1);
$catfunction = $params->get("category_function","separators");
$artfunction = $params->get("article_function","links");

// Include the syndicate functions only once
require_once (dirname(__FILE__).DS.'helper.php');
if ($artfunction == 'links')
	$list = modAutgenMenuHelper::getList($params);
elseif ($artfunction == 'hidden')
	$list = modAutgenMenuHelper::getCategoryList($params);

$layout = JModuleHelper::getLayoutPath('mod_autgen_menu');

require($layout);
?>