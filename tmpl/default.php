<!-- Affichage : default -->
<?php
$diagrammes = modXmlReaderHelper::getDiagrams($gan);
$contraintes = modXmlReaderHelper::getConstraints($gan);

$dateMin = modXmlReaderHelper::getMinDate($diagrammes);
$dateMax = modXmlReaderHelper::getMaxDate($diagrammes);
$etendue = modXmlReaderHelper::getDateRange($diagrammes);
$noms = modXmlReaderHelper::getTasksList($diagrammes); //la liste des noms

echo($title . '<br />');

echo('<hr>');
echo('Date la plus ancienne : ' . $dateMin . '<br />');
echo('Date la plus avancée : ' . $dateMax . '<br />');
echo('La différence fait ' . $etendue . '(soit un peu plus de '. ($etendue/30).' mois) <br />');

echo('<hr>');

echo($noms);




?>
</div>