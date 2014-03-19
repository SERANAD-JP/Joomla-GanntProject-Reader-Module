
<?php
echo('INFOS DIVERSES : <br />');
echo('Titre : '.$title.'<br />');
echo('Range : '.$range.'<hr>');
//----------------------------
echo('PROJETS : <br />');
$i=0;
if(isset($projects))
foreach($projects as $project){
	echo('Id : '.$project['id'].' | Debut : '.$project['debut'].' | Meeting : '.$project['meeting'].' | Duree : '.$project['duree'].' | Avancement : '.$project['avancement'].'<br />');
}
$i=0;
echo('<hr>');

//---------------------------------
echo('PROJETS APRES FILTRAGE : <br />');
$projects = GanttReaderDate::filterProjects($projects, $range);
$i=0;
if(isset($projects))
foreach($projects as $project){
	echo('Id : '.$project['id'].' | Debut : '.$project['debut'].' | Meeting : '.$project['meeting'].' | Duree : '.$project['duree'].' | Avancement : '.$project['avancement'].'<br />');
}
$i=0;
echo('<hr>');

//---------------------------------
echo('CONGES <br />');
foreach($vacations as $vacation){
	echo($vacation['start'].' --> '.$vacation['end'].'<br />');	
}
echo('<hr>');

//-------------------
echo("Timestamps min et max : <br />");
echo("Earliest : $earliest soit ".date('Y-m',$earliest)." <br/>");
echo("Lastest : $lastest soit ".date('Y-m',$lastest)." <hr>");

//-------------------------------------

echo('Fin de projet : <br />');
echo('Projet 3 : début '.$projects[0]['debut'].' durée : '.$projects[0]['duree'].'<br />');
echo('<hr>');
//----------------------------------
echo("Recalcul des limites... <br />");
$newLimits = GanttReaderDate::windowRange($range, $projects);
$earliest = $newLimits['min'];
$lastest = $newLimits['max'];
echo("Nouvelles limites : ".date('Y-m-d', $earliest).' - '.date('Y-m-d',$lastest));
echo('<hr>');

//------------------------------------------
echo('Liste des mois de la fenetre : <br />');
$months = GanttReaderDate::listMonths($earliest, $lastest);

foreach($months as $month)
		echo $month['name'].' : durée : '.$month['length'].' jours<br />';
echo'<hr>';
//----------------------------------------------

echo('Liste des jours entre aujourd\'hui et + une semaine <br />');
$days = GanttReaderDate::listDays(time(), time()+86400*7, $vacations);
foreach($days as $num => $isOff){
	echo $num.'<br />';	
}
echo '<hr>';
echo('<br /> $earliest : '.date('d F Y', $earliest));

//---------------------------
echo('<hr>CONTRAINTES :  <br />');
if(isset($constraints))
foreach($constraints as $constraint)
	echo($constraint['from'].' --> '.$constraint['to'].'<br />');
echo('<hr>');

echo('DUREES EFFECTIVES <br />');
if(isset($projects))
	foreach($projects as $project)
		echo  $project['nom'].' : '.GanttReaderDate::ProjectLength($project, $vacations).'<br />';
echo('<hr>');

echo('<div id="ganttDiagram">');
echo GanttReaderDrawer::drawHeader($title, $vacations, $earliest, $lastest, $constraints);
echo GanttReaderDrawer::drawProjects($projects, $vacations, $earliest, $lastest, $constraints);
echo('</div>');


?>
