
<?php

class GanttReaderParser{
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
	 * @return array() le tableau des projets organisées selon clé => valeur
	 */
	static function getProjects(&$gan){
		$projects = NULL;	//valeur par défaut, contre les diagrammes vides
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
		$constraints=NULL; //null par défaut, contre l'abse,ce de contraintes
		foreach($gan->tasks->task as $task){ //pour chaque tâche du document
			foreach($task->depend as $dep){
				$constraints[] = array($task->attributes()->id->__toString() => $dep->attributes()->id->__toString());
			}
		}
		return $constraints;
	}
	
	static function getVacations(&$gan){
		$vacations=NULL;//null par défaut, contre l'absence de congés
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