<?php
/*
 * Utilitaire de récupération des données
 * Helper chargé de retrouver les paramètres fournis dans le backEnd et de fournir les informations du diagramme via le parser
 */


/*  Récupération des paramètres back-end  */
$title = $params->get('title');

$range = $params->get('range');

/*  Extraction des infos depuis le fichier GanttProject  */
$projects = GanttReaderParser::getProjects($gan); 

$constraints = GanttReaderParser::getConstraints($gan);

$vacations = GanttReaderParser::getVacations($gan);

$earliest = GanttReaderDate::earliestMonth($range); //Les mois les plus étendus à parcourir parmi les projets
$lastest = GanttReaderDate::lastestMonth($range);

?>