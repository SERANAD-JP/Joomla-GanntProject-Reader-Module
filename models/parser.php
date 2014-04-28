<?php

defined('_JEXEC') or die('Restricted access');

/************************************************************************************
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Parsing utility that allows to gather the needed informations from the GanttProject file
 * The following elements are gathered :
 *	-> Projects exhaustive list and their properties
 *	-> Chaining constraints between these projects
 *	-> Vacations ranges
 ************************************************************************************/
 
class GanttReaderParser{
	
	/**
	 * Returns the projects list from the GanttProject file
	 * @param (SimpleXMLElement) $gan the parser instance (already contains the informations wich were in the GanttProject file)
	 * @param (array) $vacations the vacations frames list
	 * @param (String) $defaultColor the color to adopt if the projects color isn't specified
	 * @params (timestamp) $earliest & $lastest the first and last days of the view
	 * @return (array) the projects list as key => value
	 * @see http://php.net/manual/fr/book.simplexml.php
	 */
	static function getProjects(&$gan, &$vacations, $defaultColor, $earliest, $lastest){

		$projects = NULL;	//null by default, avoids empty diagrams
		
		if(isset($gan->tasks)){
			foreach($gan->tasks->task as $task){
				GanttReaderParser::getProjectProperties($task, $gan, $defaultColor, $projects, $vacations, $earliest, $lastest);
			}
		}
		
		return $projects;
	}
	
	/**
	 * @param (SimpleXMLElement) $task the task to insert
	 * @param (SimpleXMLElement) $gan the XML parser instance
	 * @param (String) $defaultColor, default projects color if not specified
	 * @param (array) $projects, the projects list to complete with the current project
	 * How : Add the current project's data to the list, then recursively adds its son's data (if any)
	 */
	 static function getProjectProperties($task, &$gan, $defaultColor,&$projects, &$vacations, $earliest, $lastest){
		 
		 		$id = $task->attributes()->id->__toString(); //project properties are casted from XMLElement to String
				$name = $task->attributes()->name->__toString();

				$color = isset($task->attributes()->color) ? //if no color is set, use the default color
					$task->attributes()->color->__toString() : 
					$defaultColor; 
					
				$start = $task->attributes()->start->__toString();
				$duration = $task->attributes()->duration->__toString(); //duration according to GanttProject : worked days
				$meeting = $task->attributes()->meeting->__toString()==='true';

				$length = GanttReaderDate::projectLength($start, $duration, $vacations); //actual project length

				$progress = $task->attributes()->complete->__toString();
				$hasChild = isset($task->task); // If at least a subTask is detected, the current project has a child

				
				/* Overflowing projects are truncated */
				$end = strtotime('+'.($length).' days', strtotime($start));

				if(strtotime($start)<$earliest && $end>$earliest){ // If begins too soon
					$length = GanttReaderDate::gap($end, $earliest); // Remove surplus
					
					$start = date('Y-m-d', $earliest); // Set start date to the beginning of the view
					
				}
				
				if($end>$lastest && strtotime($start)<$lastest){// If ends too late
					$length = GanttReaderDate::gap(strtotime($start), $lastest)+1; // reduce its length
				}
				
			
				$projects[] = array(
								'id' => $id,
								'name' => $name,
								'color' => $color,
								'start' => $start,
								'length' => $length,
								'progress' => $progress,
								'meeting' =>$meeting,
								'hasChild' => $hasChild,
								);
								
						
				if(isset($task->task)){ // if a subtask exists
					foreach($task->task as $subTask){
						GanttReaderParser::getProjectProperties($subTask, $gan, $defaultColor, $projects, $vacations, $earliest, $lastest);
					}
				}
				
	 }
	
	/**
	 * @params (SimpleXMLElement) $gan the parser instance, $projects the projects list
	 * @return (array) the constraints list as
	 * 		['from']=> constraint's origin project
	 *		['to']=> constraint's destination project
	 * How : In the $indexes array, we associate the project id and its order of appearance in the parser
	 */
	static function getConstraints(&$gan, &$projects){
		
		$constraints=NULL; // null by default, avoids problems if diagram doesn't contain any constraint
	
		if(!isset($projects)){ // avoids problems due to empty diagrams
			return NULL;
		}
		
		$i=0; // order of appearance
		
		foreach($projects as $project){ // for each project, establish link id => order
			$indexes[$project['id']] = $i++;
		}
		
		
		foreach($gan->tasks->task as $task){
			GanttReaderParser::getConstraintProperties($task, $gan, $projects, $constraints, $indexes);
		}
		
		return $constraints;
	}
	
	
	/**
	 * Gathers the task's constraints properties and recursively for each of its chidren
	 * @params (SimpleXMLElement) $task the task to process, $gan the parser instance
	 */
	static function getConstraintProperties(&$task, &$gan, &$projects, &$constraints, &$indexes){
		
		if(isset($task->task)){
				foreach($task->task as $sousTache){
					GanttReaderParser::getConstraintProperties($sousTache, $gan, $projects, $constraints, $indexes);
				}
		}
			
			foreach($task->depend as $dep){ //récupérer les constraints (dépendances) avec les id
				//seulement si la source et la cible de la constraint s'affichent dans le diagramme
				if(isset($indexes[$task->attributes()->id->__toString()], $indexes[$dep->attributes()->id->__toString()])){
					$constraints[]= array(	
										'from' => $indexes[$task->attributes()->id->__toString()],
										'to' => $indexes[$dep->attributes()->id->__toString()]
										);
				}
			}
	}
	
	
	/*
	 * @return (array) the vacations frames list, with their begin and end dates
	 * @param (SimpleXMLElement) $gan the parser instance
	 */
	static function getVacations(&$gan){
		
		$vacations=NULL;// avoids problems if there's no vacations frame
		
		if(isset($gan->vacations->vacation)){
		
			foreach ($gan->vacations->vacation as $range){
				$start = $range->attributes()->start->__toString();
				$end = $range->attributes()->end->__toString();
				$vacations[] = array(
									'start' => $start,
									'end' => $end
									);
			}
			
		}
		return $vacations;
	}
}


?>