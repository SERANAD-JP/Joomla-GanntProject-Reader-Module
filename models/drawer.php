<?php

/***********************************************************************************************************************************
 * @author Theo KRISZT
 * @copyright (C) 2014 - Theo Kriszt
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Rendering model
 * Provides several methods allowing the needed elements to be displayed
 * @return by default for all methods of this class, if nothing else is specified : (String) $out the HTML render of the element
 * Note : the timestamp Object refers to a timestamp formatted int.
 ***********************************************************************************************************************************/
defined('_JEXEC') or die('Restricted access');

/**
 * Class GanttReaderDrawer
 */
class GanttReaderDrawer{

	static function drawDiagram($title, &$projects){
		
	    $out=('<div id="ganttDiagram">');
	
        $out.= GanttReaderDrawer::drawTitle($title);

        $out.= GanttReaderDrawer::drawHeader();

        $out.= GanttReaderDrawer::drawSider($projects);

        $out.= GanttReaderDrawer::drawProjects($projects);

        $out.= GanttReaderDrawer::drawLegend();

        $out.=('</div>');

        echo $out;
	}

    /**
     * Draws the projects and their containers
     * @How : in the ganttDays container (whose scrollbars are synced to the matching elements : ganttHeader & ganttSider),
     *        for each project , draw its line (left padding, the project itself, then right padding)
     * @param $projects the array of projects to render
     * @return String
     */
	static function drawProjects(&$projects){
        global $earliest;

		$out='<div id="ganttDays" onscroll="'.

				'document.getElementById(\'ganttSider\').scrollTop=this.scrollTop; '. //Vertically sync. with top bloc

				'document.getElementById(\'ganttHeader\').scrollLeft=this.scrollLeft;"'.//Horizontally sync. with left bloc

				'><table>';

		//Draws the projects themselves, provide their order in which they appear
        $index = 0;
        foreach($projects as $project){
            $out.= GanttReaderDrawer::drawLine($project, $index++);
        }

		//Then, draws the "diagram objects" (i.e of the current day's marker & constraints arrows)
		$out.= GanttReaderDrawer::drawObjects($projects);

		$out.='</table></div>';

		//How many pixels are counted between the left border of the container and the today marker ?
		$scroll = (round((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)-232));

        //Now JS will right-scroll to the today marker
		$out.='<script type="text/javascript">'.

			'document.getElementById(\'ganttDays\').scrollLeft = '.$scroll.';'.

        	'</script>';

		return $out;
	}

	/**
	 * Draws the left block (projects names and progress percentage)
	 * @param (array) the projects list
	 */
	static function drawSider(&$projects){
		
		$out='<div id="ganttSider"><table>';

        foreach($projects as $project){

            $out.='<tr><td>';

            $out.='<div class="ganttName">'.$project['name'].' ('.$project['progress'].'%)</div>';

            $out.='</td></tr>';

        }

		$out.='</table></div>';
		
		return $out;
	}
	
	/**
	 * Draws the diagram's title
	 * @param (String) the $title
	 */
	static function drawTitle($title){
		$out= '<div id="ganttTitle" class="ganttReinforced">'.$title.'</div>';
		return $out;
	}


    /**
     * Draws the top block (Months & days listing)
     * @return string
     * @internal param $ (array) $vacations the vacations frames list
     * @params (timestamp) the $earliest & $lastest dates of the view
     */
	static function drawHeader(){
        global $vacations, $earliest, $lastest;
		$out='<div id="ganttHeader">'.
			 '<table>'.
			 '<tr>';
				
		$out.=GanttReaderDrawer::drawMonths($earliest, $lastest);
		
		$out.='	</tr>
				<tr>';
				
		$out.=GanttReaderDrawer::drawDays($earliest, $lastest, $vacations);
		
		$out.='</tr></table></div>';
		
		return $out;
	}
	
	/**
	 * Draws the months list between two given dates
	 * @params (timestamp) the $earliest & $lastest between which list the names
	 */
	static function drawMonths($earliest, $lastest){
		
		$months = GanttReaderDate::listMonths($earliest, $lastest);
		
		$out='';
		
		foreach($months as $month){
			$out.='<td colspan="'.($month['length']).'" class="dayBox">'.$month['name'].'</td>';
		}
		
		return $out;
	}

    /**
     * Draws the diagram's floating objects, like constraints & the today marker
     * @param (timestamp) $earliest the first date of the view
     * @params (array) $constraints, $projects the parsed informations of the same name
     * @return string
     */
	static function drawObjects($projects){
		global $earliest, $constraints;
		$out='<div id="ganttToday"'.
		'style="height:'.(count($projects)*36).'px; '.
		'left:'.(round((GanttReaderDate::gap($earliest, strtotime(date('Y-m-d', time())))*36)+35/2)).'px;" '.
		'class="time" ></div>';
		
		for($i=0; $i<count($constraints); $i++){
			$out.=GanttReaderDrawer::drawConstraint($constraints[$i], $projects, $earliest);
		}
		
		return $out;	
	}
	
	/**
	 * Draws the following constraints between the tasks
	 * @params (array) $constraint to draw, the $projects list & the $paddings list
	 * @param (timestamp) the $earliest date of the view
	 * How : Define the end date of the source project via its length
	 * 		 Define the start coordinates of the constraint (xA, yA) & of end (xB, yB)
	 *		 X axis = count of days since $earliest * cells width
	 *		 Y axis = index of project's appearance * cells height + 2 cells (+0.5 cell if starts at mid-height)
	 *		 Each constraint is modelled through a SVG graph according to its properties
	 * @see www.w3.org/Graphics/SVG/
	 */
	static function drawConstraint($constraint, $projects, $earliest){
		
		$projA = $projects[$constraint['from']];
		$projB = $projects[$constraint['to']];
		
		$cellSize = 35;
				
		$endA = strtotime($projA['start'].'+'.($projA['length']).'days'); //source project's end : start date + length
		
		$startB = strtotime($projB['start']);
		
		$xA = GanttReaderDate::gap($earliest, $endA)*($cellSize+1);
		$yA = $constraint['from']*($cellSize+1)+$cellSize*0.5;
		
		$xB = GanttReaderDate::gap($earliest, $startB)*($cellSize+1)+$cellSize/2;
		$yB = $constraint['to']*($cellSize+1);//+$cellSize*2;
		

		
		if($endA > $startB){ //if start and end are the same day (for instance with meetings)
			$xA = $xB; 		//so don't move the X axis
			
			/* Corrects the start and end heights */
			if($constraint['from']>$constraint['to']){ //if comes from below
				$yA = $yA -15;
				$yB = $yB -15;

			}elseif($constraint['to']>$constraint['from']){ //if comes from above
				$yA = $yA +10;
				$yB = $yB + 10;
			}

		}
		
		if($constraint['from']>$constraint['to']){ //if comes from below (but from a different day)
			$yB = $yB + 31+10; //arrival point from below
		}
		
		
		$out='	<?xml version="1.0" encoding="utf-8"?>
				<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					
				<svg
					class="ganttObject nawak"
					style="
						min-width:'.($xB+10).'px;
						height:'.(max(array($yA, $yB))+10).'px;
						left:;
						top:0;"
					xml:lang="fr" 
					xmlns="http://www.w3.org/2000/svg">
				
				<defs>
				
					<!-- End layout\'s marker : constraints points with an arrow at their end -->
					<marker id="arrow" markerWidth="20" markerHeight="15" refX="2.5" refY="7.5" markerUnits="userSpaceOnUse" orient="auto">
						<!-- arrow -->
						<path
       						d="M 7.5,7.5 0,0 0,15 z"
       						style="fill-rule:evenodd; 
							stroke-width:1px; stroke-linecap:round; stroke-linejoin:round; stroke-opacity:1" />
					</marker>
					
				</defs>
								
				<!-- the constraint layout itself -->
				<path 
					class="ganttObject" 
					d="
					M '.$xA.','.$yA. 	// place the drawing tool at ($xA, $yA)
					'H '.$xB.			// move horizontally to $xB
					'V'.$yB.'" '.		// then move vertically to $yB
					'style="marker-end:url(#arrow)" />'. // end path with an arrowhead
			'</svg>';
		
		return $out;
	}
	
	/**
	 * Draws the list days between two dates
	 * ex: 01, 02, 03 ... 28, 29, 30, 01, 02, 03, ...
	 * @params (timestamp) the $earliest & $lastest dates of the view
	 */
	static function drawDays($earliest, $lastest, &$vacations){
		
		$out='';
		
		$days = GanttReaderDate::listDays($earliest, $lastest, $vacations);
		
		foreach($days as $day){
			
			$out.='<td class="dayBox';
			
			if($day['vacation']){
				$out.=' dayOff';
			}
			
			if($day['today']){
				$out.=' ganttReinforced';
			}
			
			$out.='">'.$day['day'];
			
			$out.='</td>';
			
		}
		
		return $out;
	}

    /**
     * Draws the empty days (special style for of days) from a date to another
     * @params (timestamp) the $earliest & $lastest dates to fill up
     * @return string
     * @internal param $ (array) the $vacations frames list
     */
	static function drawPadding(){
        global $earliest, $lastest, $vacations;
		
		if($earliest>=$lastest){ //If error with parameters or off-zone padding (i.e latest is before earliest)
			return ''; //then return nothing : no padding
		}
		
		$out='';
		
		$days = GanttReaderDate::listDays($earliest, $lastest, $vacations);

		foreach($days as $day){
			$out.='<td class="dayBox';
			
			if($day['vacation']){
				$out.=', dayOff';	
			}
			
			$out.='"></td>';
			
		}
		
		return $out;
	}

    /**
     * Draws a project line : the project's floating rendering & a ready-to-fill table line
     * @param $project to process
     * @param $index number of project's appearance
     * @return string
     */
	static function drawLine($project, $index){

		$out='<tr>';

        if($index == 0){
            $out.= GanttReaderDrawer::drawPadding(); //draws a whole empty line, only for first line, the others will be jQuery filled
        }


		$out.= GanttReaderDrawer::drawProject($project, $index);// the project itself
		
		$out.='</tr>';

		return $out;
	}

    /**
     * Draws the cells of the given project
     * @params the $project & the $vacations frames
     * How :
     *        Process the special cases (meetings, one-day projects, father projects) with a specific procedure
     *        The special cases past, draw successively the first day, the middle cells and then the last day
     * @see www.w3.org/Graphics/SVG/
     * @param $project
     * @param $index
     * @internal param $vacations
     * @internal param $earliest
     * @internal param $lastest
     * @return string
     */
	static function drawProject($project, $index){

		if($project['hasChild']){
			return ganttReaderDrawer::drawFather($project, $index); // If the project has a child or more
		} elseif($project['meeting']){
			/* Vectorial drawing of a star with SVG */
			return GanttReaderDrawer::drawStar($project, $index);

		} else{ // classic case : the project lasts several days
            global $earliest;

            $startDate = strtotime($project['start']);
            $left = ganttReaderDate::gap($earliest, $startDate);
            $length = $project['length'];

            $out='';

            $out.='<div class="ganttObject" style="top: '.(36*($index)).'px; left: '.($left*36).'px; width:'.($length*36).'px; height:35px">';//Project container
            $out.='<div class="ganttProject" style="background-color: '.$project['color'].'">';//Project with style & color
            global $stripesPic;

            $out.='<div class="complete" style="height: 100%; width: '.$project['progress'].'%;">'; //Progress background

            $out.='</div></div></div>';

		}
		  
		return $out;
	}

    /**
     * Draws a star of the given color in the current cell
     * @param $project
     * @param $index
     * @internal param $color
     * @return string
     * @internal param $ (String) the hex-coded color of the star (#ABC123)
     */
	static function drawStar($project, $index){
        $color = $project['color'];
        global $earliest;

        $startDate = strtotime($project['start']);
        $left = ganttReaderDate::gap($earliest, $startDate);


        $out='<div class="ganttObject" style="top: '.(($index)*36).'px; left:'.(36*$left).'px">';
		$out.= '	<?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					<svg 
						xmlns:svg="http://www.w3.org/2000/svg"
   						xmlns="http://www.w3.org/2000/svg"
   						version="1.1"
   						width="35px"
   						height="30px">
							
    						<path
       							d="m
								16.518376,1.0831728 
								3.788202,7.6757353 
								8.470677,1.2308622 
								-6.129439,5.9747267 
								1.446963,8.43645 
								-7.576404,-3.983151 
								-7.576404,3.983151 
								1.446964,-8.43645 
								
								L 
								4.2594961,9.9897697 
								12.730173,8.7589082 
								
								z"
						
       						style="
								fill:'.$color.';
								fill-rule:evenodd; 
								stroke:#000000; 
								stroke-width:1px; 
								stroke-linecap:butt; 
								stroke-linejoin:round; 
								stroke-opacity:1" />
  					
					</svg>';
        return $out;
	}
	
	/*
	 * Draws a father project (a project that surrounds other projects as sub-projects)
	 * @params (array) the $project to process, $vacations the vacations frames
	 */
	static function drawFather(&$project, $index){
        global $earliest;

        $startDate = strtotime($project['start']);
        $left = ganttReaderDate::gap($earliest, $startDate);
        $length = $project['length'];



        $out='';
        $out.='<div class="ganttFatherProject" style="top: '.(($index)*36).'px; left:'.(36*$left).'px">';

		$out.='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
					<svg
						xmlns:svg="http://www.w3.org/2000/svg"
   						xmlns="http://www.w3.org/2000/svg"
   						version="1.1"
   						width="'.(36*$length).'px"
   						height="30px"
						top="0">
                        <polygon points="0,30 0,0 '.(36*$length).',0 '.(36*$length).',30 '.(36*$length-23).',10 23,10" style="fill:'.$project['color'].';stroke:none;" />
					</svg>';
        $out.='</div>';

		 return $out;
	}//end drawFather

    static function drawLegend(){
        global $lastModified, $showLast;


        $out='<div id="ganttLegend">';

            /*"Scroll-back to today" followed by the jQuery button*/
            $out.='<div class = "ganttLegendInformation ganttReinforced">';
                $out.=Jtext::_('MOD_GANTTREADER_LEGEND_BACKTOTODAY') . '&nbsp;';//"Scroll-back to today_"

                $out.='<div class="ganttLegendButton" id="ganttBackToday">';//button container
                    $out.='<div  class="time" style="height:30px;"></div>'; //time bar
                $out.='</div>';//end legendButton

            $out.='</div>';//end legendInfo

        $out.='<div class="ganttLegendInformation ganttReinforced" id="ganttExpander">';//full screen
        $out.=Jtext::_('MOD_GANTTREADER_LEGEND_EXPAND').'&nbsp;';
        $out.='<div class="ganttLegendButton" id="ganttBackToday">';//button container
        $out.='<img src="'.JURI::root().'media/mod_ganttreader/expand.png'.'">';
        $out.='</div>';//end legendButton
        $out.='</div>';

        $out.='<div class="ganttLegendInformation ganttReinforced" id="ganttRetracter">';//exit full screen
        $out.=Jtext::_('MOD_GANTTREADER_LEGEND_RETRACT').'&nbsp;';
        $out.='<div class="ganttLegendButton" id="ganttBackToday">';//button container
        $out.='<img src="'.JURI::root().'media/mod_ganttreader/retract.png'.'">';
        $out.='</div>';//end legendButton
        $out.='</div>';


        if($showLast){ // "Show last modification date" : ON
            $date = date('d / m / Y', $lastModified);

            $out.='<div class="ganttReinforced ganttLegendInformation">';
            $out.=Jtext::_('MOD_GANTTREADER_LASTMODIFIED_LABEL');
            $out.=' : '.$date.'</div>';
        }

        $out.='</div>';//end ganttLegend

        return $out;
    }
}//end class

?>