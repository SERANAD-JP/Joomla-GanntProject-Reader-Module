<?php
/**************************************************************************
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Default module view
 * Defines how the previously gathered informations shall be displayed
 **************************************************************************/
defined('_JEXEC') or die('Restricted access');

if(!empty($errors)){
	echo('<div style="color:red;">'.$errors.'</div>');
} else{
	
GanttReaderDrawer::drawDiagram($title, $projects, $vacations, $constraints, $earliest, $lastest);
}
?>