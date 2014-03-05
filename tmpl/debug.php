
<?php
echo('INFOS DIVERSES : <br />');
echo('Titre : '.$title.'<br />');
echo('Range : '.$range.'<hr>');

echo('PROJETS : <br />');
$i=0;
foreach($projects as $project){
	echo(++$i.' - '.$project['id'].' '.$project['debut'].' '.$project['meeting'].' '.$project['duree'].' '.$project['avancement'].'<br />');	
}
$i=0;
echo('<hr>');

echo('PROJETS APRES FILTRAGE : <br />');
$projects = GanttReaderDate::filterProjects($projects, $range);
$i=0;
foreach($projects as $project){
	echo(++$i.' - '.$project['id'].' '.$project['debut'].' '.$project['meeting'].' '.$project['duree'].' '.$project['avancement'].'<br />');	
}
$i=0;
echo('<hr>');




echo('CONGES <br />');
foreach($vacations as $vacation){
	echo($vacation['start'].' --> '.$vacation['end'].'<br />');	
}
echo('<hr>');


echo('Différence aujourd\'hui - une semaine : <br />');
echo(GanttReaderDate::gap(time(), time()+3600*24*7));
echo('<hr>');

echo("Timestamps min et max : <br />");
echo("Earliest : $earliest soit ".date('Y-m',$earliest)." <br/>");
echo("Lastest : $lastest soit ".date('Y-m',$lastest)." <hr>");

echo("Recalcul des limites... <br />");
$newLimits = GanttReaderDate::windowRange($range, $projects);
$earliest = $newLimits['min'];
$lastest = $newLimits['max'];
echo("Nouvelles limites : ".date('Y-m', $earliest).' - '.date('Y-m',$lastest));
echo('<hr>');

echo('Liste des mois de la fenetre : <br />');
foreach(GanttReaderDate::listMonths($earliest, $lastest) as $month)
	echo $month.'<br />';
echo'<hr>';

echo('Liste des jours entre aujourd\'hui et + une semaine <br />');
foreach(GanttReaderDate::listDays(time(), time()+86400*7) as $day){
	echo $day.'<br />';	
}
echo '<hr>';





?>
