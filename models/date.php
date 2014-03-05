<?php
class GanttReaderDate{

	/*
	 * @param $range la taille de la fenêtre avant la date $current
	 * @return timestamp la date du premier jour d'il y a $range mois
	 */
	function earliestMonth($range, $current=NULL){
		if($current==NULL){
			$current = time(); //par défaut : aujourd'hui	
		}
		
		$year = date('Y', $current);
		$month = date('m', $current);
		
		$month = $month-$range;
		if($month<0){
			$month = 12+$month;
			$year--;
		}
		
		return strtotime($year.'-'.$month.'-01');
	}
	
	/*
	 * @param $range la taille de la fenêtre après aujourd'hui
	 * @return timestamp la date du dernier jour du mois dans $range mois
	 */
	function lastestMonth($range, $current=NULL){
		if($current==NULL){
			$current = time();	
		}
		
		$year = date('Y', $current);
		$month = date('m', $current);
		
		$month = $month+$range;
		if($month>12){
			$month = $month-12;
			$year++;
		}
		
		$firstDay = strtotime($year.'-'.$month.'-01');
		$lastDay = date('Y-m-t', $firstDay);
		
		return strtotime($lastDay);	
	}
	
	
	/*
	 * @param timestamp du jour a checker
	 * @return true si le jour concerné est un WE, faux sinon
	 */
	function isWeekEnd($timestamp){
		$day = date('N', $timestamp);
		return ($out>5); //true si 5eme jour de la semaine déjà passé
	}
	
	/*
	 * @params le timestamp du jour a inspecter,  le tableau des congés
	 * @return true s'il s'agit d'un jour en congé, faux sinon
	 */
	function isVacation($time, &$vacations){
		foreach($vacations as $vacation){
			$start = strtotime($vacation['start']);
			$end = strtotime($vacation['end']);
			
			if(GanttReaderDate::inSight($time, $start, $end)){
				return true;
			}
		}
		return false;
	}
	
	/*
	 * @param timestamp du jour a étudier, timestamp dateFenetreMinimale , timestamp dateFenetreMaximale
	 * @return true si le timestamp est compris entre dateA et dateB, faux sinon
	 */
	function inSight($timestamp, $dateA, $dateB){
		return ($timestamp>$dateA && $timestamp<$dateB);
	}
	
	
	/*
	 * @param timestampA, timestampB les deux timestamp à comparer
	 * @return int le nombre de jours qui séparent les deux timestamp
	 */
	function gap($timeA, $timeB){
		$diff = abs($timeA-$timeB);
		$jours = $diff/3600/24;
		return $jours;
	}
	
	
	/*
	 * @param $timeA et $timeB, les  timestamps des mois entre lesquels il faut donner les noms
	  *@return array les noms des mois situés entre timeA et timeB (inclus)
	 */
	function listMonths($timeA, $timeB){
		
		$current = $timeA;
		do{
			$months[]= date('M',$current);
			$current = GanttReaderDate::lastestMonth(1, $current); //sauter au mois suivant
			
		} while($current<=$timeB);
		return $months;
		
	}
	
	/*
	 * @param $timeA et $timeB, les  timestamps des jours entre lesquels il faut donner les numeros
	  *@return array les numéros des jours situés entre timeA et timeB (inclus)
	 */
	function listDays($timeA, $timeB){
		$current = $timeA;
		do{
			$days[] = date('d', $current);
			$current = $current+86400;//+1 day
		} while($current<=$timeB);
		return $days;
	}
	
	/*
	 * @param la taille originelle de la fenêtre, le tableau des projets
	 * @return true si le projet est censé être affiché dans le rendu, false sinon (i.e au moins une partie se trouve dans la fenêtre)
	 */
	 function inWindow($range, $project){
		$earliest = GanttReaderDate::earliestMonth($range);
		$lastest = GanttReaderDate::lastestMonth($range);
		
		
			$start = strtotime($project['debut']);	//début et fin de la frontière originelle
			$end = $start+($project['duree'])*24*3600;
			
			return(
				GanttReaderDate::inSight($start, $earliest, $lastest)||	//Si début inclus
				GanttReaderDate::inSight($end, $earliest, $lastest)|| 	//ou si fin incluse
				GanttReaderDate::inSight($earliest, $start, $end));				//ou si recouvre la fenêtre
			
		
	}
	
	/*
	 * @param la taille originelle de la fenêtre, le tableau des projets
	 * @return true si le projet est censé être affiché dans le rendu, false sinon
	 * Pré-requis : avoir filtré les projets complètement hors champ au préalable.
	 */
	 function windowRange($range, $projects){
		$earliest = GanttReaderDate::earliestMonth($range); //limites d'origine de la fenêtre
		$lastest = GanttReaderDate::lastestMonth($range);
		
		$min = $earliest; //nouvelles limites, à adapter
		$max = $lastest;
		
		foreach($projects as $project){
			$start = strtotime($project['debut']);	//début et fin de la frontière originelle
			$end = $start+($project['duree'])*24*3600;
			

			if(GanttReaderDate::inSight($lastest, $start, $end)){//si plus tard que la fenêtre
				$max = $end;
			}
			if(GanttReaderDate::inSight($earliest, $start, $end)){//si plus tôt que la fenêtre
				$min = $start;
			}
		}
		return array(
					'min' => $min,
					'max' => $max
					);
	}
	
	/*
	 * @params l'array des projets source, $range le nombre de mois autour de la date courante à conserver
	 * @return l'array des projets, filtrés
	 */
	function filterProjects($projects, $range){
		
		foreach($projects as $project){
			if(GanttReaderDate::inWindow($range, $project)){
				$out[]=$project;	
			}
		}
		return $out;
		
	}
	
}


?>