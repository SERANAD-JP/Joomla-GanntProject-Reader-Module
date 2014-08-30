<?php

defined('_JEXEC') or die('Restricted access');

/*******************************************************************************************************************************
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Data gathering utility
 * The helper aims to gather the parameters given in backend, extract data via the parser , then look for errors
 *******************************************************************************************************************************/

$errors=''; // Initiate the errors to potentially display later
global $earliest, $lastest, $vacations, $constraints, $lastModified, $showLast ;

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

$showLast = $params->get('lastModified'); // (bool) display last modified date ?

/* @params  $color the color (hex) to check;
 *          $errorMessage (String) the message to display if the color is incorrect,
 * @how check wether if the given color matrches the regex pattern for an hexadecimal-coded color (e.g. #ABCDEF)
 */
 function checkHexColor($color, $errorMessage){
    $colorPattern='(#{1}(?:[A-F0-9]){6})(?![0-9A-F])';	// Pattern regex for an hex-coded color

    if (!preg_match_all ("/".$colorPattern."/is", $color, $matches))
    {
       global $errors;
        $res=JText::_($errorMessage).'<br />';
        $errors.=$res;
    }
}
/* Are the given color correct (match with the "#ABCDEF" regex mask) ?*/

checkHexColor($defaultColor, 'MOD_GANTTREADER_DEFAULTCOLOR_ERROR');
checkHexColor($dayBoxColor, 'MOD_GANTTREADER_BOXCOLOR_ERROR');
checkHexColor($dayOffColor, 'MOD_GANTTREADER_OFFCOLOR_ERROR');
checkHexColor($constraintColor, 'MOD_GANTTREADER_CONSTRAINTCOLOR_ERROR');
checkHexColor($titleColor, 'MOD_GANTTREADER_TITLECOLOR_ERROR');
checkHexColor($textColor, 'MOD_GANTTREADER_TEXTCOLOR_ERROR');
checkHexColor($todayColor, 'MOD_GANTTREADER_TODAYCOLOR_ERROR');

global $errors; // Make $errors global again


switch(JFactory::getLanguage()->getTag()){ //In which language shall the title be displayed ? English is the default
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
	$ganttPath =(JPATH_SITE.'/'.$path);//Define the file path from the Joomla root
	
} else{ //If external file, download it in the temp dir
    /*IMPORTANT : the www-data user must be allowed to read/write in the temp folder*/
	
		@copy($path, sys_get_temp_dir().'/temp.gan'); // @ voids the possible 404 error to be thrown
		@$ganttPath = sys_get_temp_dir().'/temp.gan';
	}

if(!@$gan=simplexml_load_file($ganttPath)){//If loading fails, add the 404 error to the errors list
	$errors.=JText::_('MOD_GANTTREADER_404_ERROR').'<br />';
}


$lastModified = filemtime($ganttPath);


if(!$isLocal){
	unlink($ganttPath); // delete the temporary file when reading is complete
}

// Add styles according to the parameters
if(empty($errors)){

    $styles ='
			#ganttDiagram, .dayBox{
    			background-color:'.$dayBoxColor.';
				color:'.$textColor.';
			}
			
			.ganttReinforced{
				color:'.$titleColor.';
			}
			
			.time{
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
			
			.ganttProject{
				background-color:'.$defaultColor.';
			}

			.ganttName:hover{
			    color:'.$titleColor.'
			}

			
			.complete{
				background:url('.$stripesPic.');
			}
			
			';		
	JFactory::getDocument()->addStyleDeclaration($styles); //Apply new styleSheet to the document
}



$earliest = GanttReaderDate::earliestMonth($range); // oldest month to display
$lastest = GanttReaderDate::lastestMonth($range); // most advanced month to display


/*  Extract data from GanttProject file  */

$vacations = GanttReaderParser::getVacations($gan); // extract vacation periods

$projects = GanttReaderParser::getProjects($gan, $vacations, $defaultColor, $earliest, $lastest); // raw extracting of projects list

if(empty($projects)){ // If there is no project to display
	$errors.=JText::_('MOD_GANTTREADER_EMPTYDIAGRAM_ERROR').'<br />';
} else{
	$projects = GanttReaderDate::filterProjects($projects, $range); // filter projects, only keep projects to display

	if(empty($projects)){ // If each project is too far to be displayed in the view
		$errors.=Jtext::_('MOD_GANTTREADER_NOTHINGTODISPLAY_ERROR').'<br />';
	}
}

$constraints = GanttReaderParser::getConstraints($gan, $projects); // extracting constraints



?>