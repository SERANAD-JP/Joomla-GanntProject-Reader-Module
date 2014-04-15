<?php

defined('_JEXEC') or die('Restricted access');

/********************************************************************************
 * Date management model
 * Provides several methods to handle a calendar, dates and durations
 * Notice : the timestamp format referes to a timestamp-formatted int
 ********************************************************************************/
 
class GanttReaderDate{

	/**
	 * @return (timestamp) the first day of the month, $frame months before the current month
	 * @param (int) $frame how many months shall be counted to give the first day of that month
	 * @param (timestamp) $current (default = null) a date of the month to consider as the 'current' month
	 */
	static function earliestMonth($frame, $current=NULL){
		
		if($current==NULL){
			$current = time(); //default value is today
		}
		
		$year = date('Y', $current);
		$month = date('m', $current);
		
		$month = $month-$frame;
		
		if($month<0){
			$month = 12+$month;
			$year--;
		}
		
		return strtotime($year.'-'.$month.'-01');
	}
	
	/**
	 * @return (timestamp) the last day of the month, $frame months after the current month
	 * @param (int) $frame how many months shall be counted to give the last day of that month
	 * @param (timestamp) $current (default = null) a day of the month to consider as the 'current' month
	 */
	static function lastestMonth($frame, $current=NULL){
		
		if($current==NULL){
			$current = time(); //default value is today
		}
		
		$year = date('Y', $current);
		$month = date('m', $current);
		
		$month = $month+$frame;
		
		if($month>12){
			$month = $month-12;
			$year++;
		}
		
		$firstDay = strtotime($year.'-'.$month.'-01');
		$lastDay = date('Y-m-t', $firstDay);
		
		return strtotime($lastDay);	
	}
	
	
	/**
	 * @return (boolean) true if the given day is a Saturday or a Sunday
	 * @param (timestamp) the day to process
	 */
	static function isWeekEnd($timestamp){
		$day = date('N', $timestamp);
		return ($day>5); //true if the 5th day of the week is already past
	}
	
	/**
	 * @return true if it's a day of vacation (i.e. it belongs to a vacation frame)
	 * @param (timestamp) $day the day to process
	 * @param (array) $vacations the list of vacations frames
	 * How : partial scan, return true as soon as a surrounding vacation frame is found, 
	 * 		 If no matching frame were scanned, return false
	 */
	static function isVacation($day, &$vacations){
		if(isset($vacations)){
			foreach($vacations as $vacation){
				$start = strtotime($vacation['start']);
				$end = strtotime('-1 day', strtotime($vacation['end'])); //GanttProject sets the end date one day AFTER the actual last day of break
				
				if(GanttReaderDate::inSight($day, $start, $end)){ //if the given date is between start and end
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @params (timestamp) $day the day to process
	 * @return (boolean) true if the day is off
	 */
	static function inRest($jour, &$vacations){
		return (GanttReaderDate::isVacation($jour, $vacations) || GanttReaderDate::isWeekEnd($jour));	
	}
	
	
	/**
	 * @params (timestamp) $dateA et $dateB  the days between wich you measure the gap
	 * @return (int) the number of days between the two days
	 * @see www.php.net/manual/en/book.datetime.php
	 */
	static function gap($dateA, $dateB){
		$start = new DateTime(date('Y-m-d', $dateA));
		$end = new DateTime(date('Y-m-d', $dateB)); 
		$diff = $start->diff($end);
		return $diff->days;
	}
	
	/**
	 * @params (timestamp) $day the day to process, $earliest & $lastest the first & the last days of the view
	 * @return true if the given day is between $earliest & $lastest, false otherwise
	 */
	static function inSight($day, $earliest, $lastest){
		return ($day>=$earliest && $day<=$lastest);
	}
	
	/**
	 * @param (array) $project the processed project
	 * @param (int) $cell the index this project's cell (0 is the first one, $project['length']-1 is the last one)
	 * @return (boolean) true if the cell has a completed style, false otherwise
	 */
	 static function completed($project, $cell){
		 
		$duration = $project['length'];
		
		if($duration==0){
			return $project['progress']==100; //One-day projects cells are completed at 100%, never before
		}
		
		$ratio = round((($cell+1)/$duration)*100); //Ration of relative progress of the cell over global progress

		return ($ratio<=($project['progress'])); // True if the progress 'covers' the cell
	 }
	
	
	/**
	 * @params (timestamp) $earliest & $lastest, the first and the last months of the view
	 * @return (array) name months between $earliest et $lastest (included) & their length in days
	 * ex. ['name'] => 'January'; ['length'] => 31 ... ['name'] => 'February'; ['length'] => 28 etc.
	 * NB : months are translated in user's language by Joomla
	 * @see http://docs.joomla.org/JText/_()
	 * How : full scan
	 */
	static function listMonths($earliest, $lastest){
		
		$current = $earliest; //Point at the first month of the frame
		
		do{
			$name = JText::_(strtoupper(date('F', $current))); //ready-to-translate format for Joomla API
			
			$length = date('t', $current);
			
			$months[]= array(
							'name' => $name, 
							'length' => $length
							);
			
			$current = GanttReaderDate::lastestMonth(1, $current); //jump to the next month
			
		} while($current<=$lastest);
		
		return $months;
		
	}
	
	/**
	 * @params (timestamp) $earliest & $lastest, the days between you want the numbers
	 * @param (array) $vacations the vacations frames
	 * @return (array) the numbers of the days between $earliest & $lastest (included) as
	 *		['day'] => (int) number
	 *		['vacation'] => (boolean) is off
	 *		['today'] => (boolean) is the current day
	 * How : full scan
	 */
	static function listDays($earliest, $lastest, &$vacations){
		
		$current = $earliest; //point at the first day
		
		do{
			$days[] = array(
							'day' => date('d', $current),
							'vacation' => GanttReaderDate::inRest($current, $vacations),
							'today' => (strtotime(date('Y-m-d', time()))==$current) //true if time()==today
							);
							
			$current = strtotime('+1 day', $current);//jump to next day
			
		} while($current<=$lastest);
		
		return $days;
	}
	
	/**
	 * @param (int) $frame number of months on both sides of the current month
	 * @param (array) $project to process
	 * @return (boolean) true if the project shall be rendered (i.e at lesat a part of it appears in the view), false otherwise
	 */
	 static function inWindow($frame, $project){
		 
		$earliest = GanttReaderDate::earliestMonth($frame);
		$lastest = GanttReaderDate::lastestMonth($frame);
		
		
			$start = strtotime($project['start']);	// start date of project
			$end = strtotime('+'.($project['length']-1).' days', $start); // project's last day
			
			return(
				GanttReaderDate::inSight($start, $earliest, $lastest)||	//If start included
				GanttReaderDate::inSight($end, $earliest, $lastest)|| 	//or end included
				($start==$earliest && $end==$lastest)||					//or overlaps the view
				GanttReaderDate::inSight($earliest, $start, $end));		//or exactly covers the view : return true, false otherwise 
	}
	
	
	/**
	 * @param (array) $projects the original projects list
	 * @param (int) $frame how many months shalll be displayed on both sides of the current month
	 * @return (array) the filtered projects list (i.e that shall be rendered)
	 */
	static function filterProjects(&$projects, $frame){	
	  	$out=null;
		if(isset($projects)){
			foreach($projects as &$project){	
				if(GanttReaderDate::inWindow($frame, $project)){
					$out[]=$project;
				}
			}
		}
		
		return $out;
	}
	
	/**
	 * @param (array) $project to process
	 * @param (array) $vacations the vacations frames
	 * @return (int) the calculated length of the project, i.e. how many days the project will be occupied
	 * How : GanttProject provides worked days, just add the off days
	 */
	 static function projectLength($start, $duration, $vacations){
		 
		 if($duration==0){ // A meeting lasts 0 day according to GanttProject, patch it by returning 1
			return 1;
		}
		 
		$current = strtotime($start); //point at the first day
		
		$off = 0; //Prepare to count the off days
		
		$worked = $duration; // how many worked days ?
		 
		 do{
			 			 
			 $current = strtotime('+1 day', $current);
			 
			 if(!GanttReaderDate::inRest($current, $vacations)){
				$worked--; 
			 } else{
				$off++; 
			 }
			 
		 }while($worked>1);
		 

		 return ($duration+$off);
	 }
}


?>