<?php

$title = $params->get('title');

$range = $params->get('range');

$projects = GanttReaderParser::getProjects($gan); 

$constraints = GanttReaderParser::getConstraints($gan);

$vacations = GanttReaderParser::getVacations($gan);

/*--------------------------------*/

$earliest = GanttReaderDate::earliestMonth($range); //Les mois les plus étendus à parcourir parmi les projets
$lastest = GanttReaderDate::lastestMonth($range);

?>