<?php

/*
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Module's entry point, sets the files paths, loads the required files, launches the data processing and proceed to rendering
 */

defined('_JEXEC') or die('Restricted access');

/* media files gathering */
$cssFile = JURI::root().'media/mod_ganttreader/mod_ganttreader.css';

$stripesPic = JURI::root().'media/mod_ganttreader/stripes.png';


/* Static CSS file declaration */
JFactory::getDocument()->addStyleSheet($cssFile);


/*Models loading*/
require_once(dirname(__FILE__).'/models/parser.php');

require_once(dirname(__FILE__).'/models/date.php');

require_once(dirname(__FILE__).'/models/drawer.php');


/* Data processing */
require(dirname(__FILE__).'/helper.php');


/* View loading */
require( JModuleHelper::getLayoutPath( 'mod_ganttreader', $params->get('layout') ) ); //Selectionne le template choisi dans le backend

?>