<?php
/**************************************************************************
 * Default module view
 * Defines how the previously gathered informations shall be displayed
 **************************************************************************/

if(!empty($errors)){
	echo('<div style="color:red;">'.$errors.'</div>');
} else{
	
GanttReaderDrawer::drawDiagram($title, $projects, $vacations, $constraints, $earliest, $lastest);
}
?>