<?php

/*
 * TODO :
 * methode qui retourne les mois utilisés par le diagramme
 * methode qui liste les jours d'un mois
 * methode qui liste les jours utilisés par le diagramme
 * methode qui repere un jour de Week End
 * methode qui affiche une contrainte de A sur B (gantt project fait a moins au lendemain)
 *  
 */

class modXmlReaderHelper{
	
	function getColor($params){
		return ($params->get('color'));
	}

	function getTitle($params){
		return ($params->get('title'));
	}
	
	/*
	 * @param array() le tableau des tâches
	 * @return la date (YYYY-mm-dd) la plus petite de la liste (aujourd'hui compris)
	 */
	function getMinDate($diagramme){
		$minDate = date("Y-m-d"); //par défaut, la date du jour
		foreach($diagramme as $tache){
			$dates[] = $tache['debut']; //on recense les dates de début
		}
		
		$minDate = ($minDate < min($dates)) ? $minDate : min($dates);
		return $minDate;
	}
	
	/*
	 * @param array() les taches du diagramme
	 * @return int la date la plus tardive des taches de la liste
	 */
	function getMaxDate($diagramme){
			foreach($diagramme as $tache){
				$dates[] = date('Y-m-d' ,strtotime($tache['debut'])+3600*24*$tache['duree']); // date de début + durée (en secondes)
			}
			$maxDate = (max($dates) > date('Y-m-d')) ? max($dates): date('Y-m-d');
			return $maxDate;
	}
	
	/*
	 * @param array() les tâches du diagramme
	 * @return l'étendue (en jours) entre la date max et la date min dans le diagramme
	 */
	function getDateRange($diagramme){
		$min = strtotime(modXmlReaderHelper::getMinDate($diagramme));
		$max = strtotime(modXmlReaderHelper::getMaxDate($diagramme));
		$diff = (($max - $min)/3600)/24; //passage de la différence des secondes en jours
		return intval($diff);
	}
	
	function getTasksList($diagramme){
		$out = '<ul class="tasks">';
		foreach($diagramme as $tache){
			$out.='<li>' . $tache['nom'] . '</li>';	
		}
		$out.='</ul>';
		
		return $out;
	}
	
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
	 * @return array() le tableau des tâches organisées selon clé => valeur
	 */
	function getDiagrams($gan){
		
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
			
			$diagrammes[] = array(
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
		
		return $diagrammes;
	}
	
	/*
	 * @param SimpleXMLElement $gan l'instance du parseur du diagramme de gantt à traiter
	 * @return array() le tableau des contraintes inter tâches selon $maTache ==(a pour successeur)==> $monAutreTache
	 */
	function getConstraints($gan){
		foreach($gan->tasks->task as $task){ //pour chaque tâche du document
			foreach($task->depend as $dep){
				$constraints[] = array($task->attributes()->id->__toString() => $dep->attributes()->id->__toString());
			}
		}
		return $constraints;
	}

	/*
	 * @param SmpleXMLElement l'instance du parseur du diagramme de gantt à traiter
	 * @return String la chaîne brute représentant le diagramme de Gantt
	 */
	function toString($gan){
		
		$taches = modXmlReaderHelper::getDiagrams($gan);
		$contraintes = modXmlReaderHelper::getConstraints($gan);
		
		$out='<p>===TACHES===</p>';
		foreach($taches as $tache){
			foreach($tache as $key => $value){
				$out.=($key . ' => ' . $value . '<br />');	
			}
			$out.=('-------------------- <br />');
		}
		
		$out.='<p>===CONTRAINTES===</p>';
		$out.=('[Clé] == a pour successeur ==> valeur <br/>');
		foreach($contraintes as $dep){
			foreach($dep as $key => $value){
				$out.=($key . '====> ' . $value . '<br />');	
			}
		}
			
		return $out;
	}
	
}

?>