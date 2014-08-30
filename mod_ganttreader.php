<?php

/*
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Feel free to fork this project !
 *
 *******************************************************************
 * CHANGELOG :
 *
 *  1.1.0
 *  Project's progress status is from now on accurate within the percent
 *
 * Rendering methods were refactored in order to allow a dynamic client implementation
 *
 * Rendering methods are lighter
 *
 * Loading has been improved : bye bye +30-seconds loading time when tons of projects are loaded across 13 months
 *
 * Module now fits it's width according to the available space
 *
 * A legend were added with the aim of providing useful tools to the user
 *
 * The last diagrams's change date is given within the legend, it can be hidden in back-end
 *
 * Heavily long project's names are truncated, they fully appear when mouse-hovered.
 *
 *------------------------------------------------------------------
 *
 * 1.0.0 :
 *  First release for SERANAD's facility, IGMM, CNRS
 *******************************************************************
 * Module's entry point, sets the files paths, loads the required files, launches the data processing and proceed to rendering
 */


//Joomla community constraint : each PHP file must check this value to be accepted
defined('_JEXEC') or die('Restricted access');

/* media files gathering */
$cssFile = JURI::root().'media/mod_ganttreader/mod_ganttreader.css';

$stripesPic = JURI::root().'media/mod_ganttreader/stripes.png';

$jsUtils = JURI::root().'media/mod_ganttreader/ganttReaderUtils.js';


/*Scripts inclusion*/
JFactory::getDocument()->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
JFactory::getDocument()->addScript($jsUtils);


/* Static CSS file declaration */
JFactory::getDocument()->addStyleSheet($cssFile);


/*Models loading*/
require_once(dirname(__FILE__).'/models/parser.php');

require_once(dirname(__FILE__).'/models/date.php');

require_once(dirname(__FILE__).'/models/drawer.php');


/* Data processing */
/*@see http://www.phpeveryday.com/articles/Joomla-Module-Using-Helper-Part-5--P82.html (up to date on 2014/08/01)*/
require(dirname(__FILE__).'/helper.php'); // Launch the data analysis directed by the helper file


/* View loading */
require( JModuleHelper::getLayoutPath( 'mod_ganttreader', $params->get('layout') ) ); //Selects the right template & proceed to rendering

?>