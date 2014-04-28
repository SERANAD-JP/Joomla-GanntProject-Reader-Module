<?php

defined('_JEXEC') or die('Restricted access');

/*******************************************************************************************************************************
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Data gathering utility
 * The helper aims to gather the parameters given in backend, extract data via the parser , then look for errors *******************************************************************************************************************************/

$errors = ''; // Init the errors to potentially display later

/*  Gather the backend params  */

$isLocal = $params->get('isLocal'); //Is the file in Joomla tree ?

$path = $params->get('path'); //path to the GanttProject file

$range = $params->get('range'); //how many months shall be displayed on both sides of the current month

$defaultColor = $params->get('defaultColor'); //default projects color

$dayBoxColor = $params->get('dayBoxColor'); //basic cells color

$dayOffColor = $params->get('dayOffColor'); //off days color

$constraintColor = $params->get('constraintColor'); //arrows color

$titleColor = $params->get('titleColor');

$textColor = $params->get('textColor'); 

$todayColor = $params->get('todayColor'); //today vertical marker color



/* Are the given color correct (match with the "#ABCDEF" regex mask)*/

$colorPattern='(#{1}(?:[A-F0-9]){6})(?![0-9A-F])';	// Pattern regex for an hex-coded color

if (!preg_match_all ("/".$colorPattern."/is", $defaultColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_DEFAULTCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $dayBoxColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_BOXCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $dayOffColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_OFFCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $constraintColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_CONSTRAINTCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $titleColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_TITLECOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $textColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_TEXTCOLOR_ERROR').'<br />';
}
if (!preg_match_all ("/".$colorPattern."/is", $todayColor, $matches))
{
	$errors.=JText::_('MOD_GANTTREADER_TODAYCOLOR_ERROR').'<br />';
}


switch(JFactory::getLanguage()->getTag()){ //In wich language shall the title be displayed ? English is the default
/* switch/case structured to easily implement more languages later */
	case 'fr-FR':
		$title = $params->get('frenchTitle');
	break;
	
	default:
		$title = $params->get('englishTitle');
}

if(empty($title)){
	$errors.=JText::_('MOD_GANTTREADER_MISSINGTITLE_ERROR').'<br />';
}

/* Defining the parser */
if($isLocal){
	$ganttPath =(JPATH_SITE.'/'.$path);
	
} else{ //If external file, download it in the temp dir
	
		@copy($path, sys_get_temp_dir().'/temp.gan'); // @ voids the possible 404 error to be thrown
		@$ganttPath = sys_get_temp_dir().'/temp.gan';
	}

if(!@$gan=simplexml_load_file($ganttPath)){//If loading fails, add the 404 error to the list
	$errors.=JText::_('MOD_GANTTREADER_404_ERROR').'<br />';
}

if(!$isLocal){
	unlink($ganttPath); // delete the temporary file when reading is complete
}


// Add styles according to the parameters
if(empty($errors)){
$styles = 	'
			#ganttDiagram, .dayBox{
    			background-color:'.$dayBoxColor.';
				color:'.$textColor.';
			}
			
			.ganttEmbed{
				color:'.$titleColor.';
			}
			
			#time{
				background-color:'.$todayColor.';
			}
			
			path{
				stroke:'.$constraintColor.';
			}
			
			marker path{
				fill:'.$constraintColor.';	
			}
			
			td.dayOff{
				background-color:'.$dayOffColor.';
			}
			
			.ganttProject, .ganttProjectEnd, .ganttProjectStart{
				background-color:'.$defaultColor.';
				
			}
			
			.complete{
				background:url('.$stripesPic.');
			}
			
			';		
	JFactory::getDocument()->addStyleDeclaration($styles);	
}


$earliest = GanttReaderDate::earliestMonth($range); // oldest month to display
$lastest = GanttReaderDate::lastestMonth($range); // most advanced month to display


/*  Extract data from GanttProject file  */

$vacations = GanttReaderParser::getVacations($gan); // extract break ranges

$projects = GanttReaderParser::getProjects($gan, $vacations, $defaultColor, $earliest, $lastest); // raw extracting of projects list

if(empty($projects)){
	$errors.=JText::_('MOD_GANTTREADER_EMPTYDIAGRAM_ERROR').'<br />';
} else{
	$projects = GanttReaderDate::filterProjects($projects, $range); // filtering projects, only keeps projects to be displayed

	if(empty($projects)){
		$errors.=Jtext::_('MOD_GANTTREADER_NOTHINGTODISPLAY_ERROR').'<br />';
	}
}

$constraints = GanttReaderParser::getConstraints($gan, $projects); // extracting constraints

?>