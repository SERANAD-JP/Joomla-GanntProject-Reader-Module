<?php
class GanttReaderDate{

	/*
	 * @param $range la taille de la fenêtre avant/après aujourd'hui
	 * @return timestamp la date du premier jour d'il y a $range mois
	 */
	function earliestMonth($range=5){
		$year = date('Y', time());
		$month = date('m', time());
		
		$month = $month-$range;
		if($month<0){
			$month = 12+$month;
			$year--;
		}
		
		return strtotime($year.'-'.$month.'-01');	
	}
	
	/*
	 * @param $range la taille de la fenêtre avant/après aujourd'hui
	 * @return timestamp la date du dernier jour du mois dans $range mois
	 */
	function lastestMonth($range=5){
		$year = date('Y', time());
		$month = date('m', time());
		
		$month = $month+$range;
		if($month>12){
			$month = $month-12;
			$year++;
		}
		
		$firstDay = strtotime($year.'-'.$month.'-01');
		$lastDay = date('Y-m-t', $firstDay);
		
		return $lastDay;	
	}
	
	
	/*
	 * @param timestamp du jour a checker
	 * @ return true si le jour concerné est un WE, faux sinon
	 */
	function isWeekEnd($timestamp){
		$day = date('D', $timestamp);
		$out = $day==='Sat' || $day==='Sun';
		return $out;
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
	
	function listMonth(){
		
	}
	
	function listDays(){
		
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
				GanttReaderDate($earliest, $start, $end));				//ou si recouvre la fenêtre
			
		
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
			if(GanttReaderDate($earliest, $start, $end)){//si plus tôt que la fenêtre
				$min = $start;
			}
		}
		return array(
					'min' => $min,
					'max' => $max
					);
	}
	
}


?>