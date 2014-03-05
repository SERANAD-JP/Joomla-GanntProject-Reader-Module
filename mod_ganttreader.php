<?php

defined('_JEXEC') or die('Restricted access');

//Picks-up media files
$gan = simplexml_load_file(JPATH_SITE.'/media/mod_ganttreader/gantt.gan');

$cssFile = JURI::root().'media/mod_ganttreader/mod_ganttreader.css';

$document = JFactory::getDocument();


//Defines CSS by default
$document->addStyleSheet($cssFile);


//Loads models
require_once(dirname(__FILE__).'/models/parser.php');

require_once(dirname(__FILE__).'/models/date.php');

require_once(dirname(__FILE__).'/models/project.php');

require_once(dirname(__FILE__).'/models/drawer.php');

require_once(dirname(__FILE__).'/helper.php');



//Finally, loads the view
require( JModuleHelper::getLayoutPath( 'mod_ganttreader', $params->get('layout') ) );



?>