
<?php

class GanttReaderParser{
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
	 * @return array() le tableau des projets organisées selon clé => valeur
	 */
	static function getProjects(&$gan){
		foreach($gan->tasks->task as $task){//pour chaque tâche du document
			
			$id = $task->attributes()->id->__toString();
			$nom = $task->attributes()->name->__toString();
			$couleur = $task->attributes()->color->__toString();
			$debut = $task->attributes()->start->__toString();
			$meeting = $task->attributes()->meeting->__toString();
			$meeting = $meeting==='true';
			$duree = $task->attributes()->duration->__toString();
			$avancement = $task->attributes()->complete->__toString();
			$notes = $task->notes->__toString();
			
			$projects[] = array(
							'id' => $id,
							'nom' => $nom,
							'couleur' => $couleur,
							'debut' => $debut,
							'duree' => $duree,
							'avancement' => $avancement,
							'meeting' =>$meeting,
							'notes' => $notes
							);
			
		}
		
		return $projects;
	}
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
	 * @return array() le tableau des contraintes inter tâches selon $maTache ==(a pour successeur)==> $monAutreTache
	 */
	static function getConstraints(&$gan){
		foreach($gan->tasks->task as $task){ //pour chaque tâche du document
			foreach($task->depend as $dep){
				$constraints[] = array($task->attributes()->id->__toString() => $dep->attributes()->id->__toString());
			}
		}
		return $constraints;
	}
	
	static function getVacations(&$gan){
		foreach ($gan->vacations->vacation as $vacation){
			$start = $vacation->attributes()->start->__toString();
			
			$end = $vacation->attributes()->end->__toString();
			
			$vacations[] = array(
								'start' => $start,
								'end' => $end
								);
		}
		return $vacations;
	}
}


?>