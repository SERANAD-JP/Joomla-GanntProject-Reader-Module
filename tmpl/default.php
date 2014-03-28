<!-- Affichage : default -->
<?php
/*
 * Définis comment les données récupérées dans le helper seront affichéesw
 */

if(!empty($errors)){
	echo('<div style="color:red;">'.$errors.'</div>');
} else{
	
GanttReaderDrawer::drawDiagram($title, $projects, $vacations, $constraints, $earliest, $lastest);
}
?>