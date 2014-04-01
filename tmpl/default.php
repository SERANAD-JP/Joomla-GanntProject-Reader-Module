<?php
/**************************************************************************
 * Vue par défaut du module
 * Définis comment les données récupérées dans le helper seront affichées
 **************************************************************************/

if(!empty($errors)){
	echo('<div style="color:red;">'.$errors.'</div>');
} else{
	
GanttReaderDrawer::drawDiagram($title, $projects, $vacations, $constraints, $earliest, $lastest);
}
?>