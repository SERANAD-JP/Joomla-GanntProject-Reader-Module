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

class GanttReaderHelper{

	static function getTitle($params){
		return ($params->get('title'));
	}
	
	static function getRange($params){
		return ($params->get('range'));
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
	
	
	
	
	
	
}

?>