<?php
/* 
 * gCal Extension - Magazine Theme
 * Abe Yang (c) 2011
 * http://code.google.com/p/gcal-php-framework/
/* ---------------------------------------------------------------------------------- */

// very similar to gcalweb.class.php
// precondition: events (array) must be fed in to desired function
// postcondition: string to be displayed will be concatenated with all events

/* Standard Display
 * Used for most websites (including main website)
 *
 * =Additional Params=
 * 'displaytag' = displays tag in subject line (default = 0)
 *
 * Blog Display
 * Display events in blog style
/* ---------------------------------------------------------------------------------- */
require_once('gcal.class.php');

class GPCal extends gCal {
	// rss feeds
	function GPCal($cal, $options = array()) {
		parent :: gCal($cal, $options);
	} // end gCalWeb()
	
	// requires:
	// jquery - http://jquery.com
	function display() {
		$events = $this->events;
		if (!$events) return '';
		$olddate = '';
		$displaystring .= '<div class="gw-post">';

		foreach($events as $event) {
			$start = $event['starttime'];
			// check for new dates
			$newdate = date('l, n/j/y', $start);
			if (strcmp($newdate, $olddate)) {
				// $newdate != $olddate
				$displaystring .= '<div class="gw-date-wrap"><div class="gw-date">' . $newdate . '</div></div>';
				$olddate = $newdate;
			}
			if ($event['isfeatured']) {
			    $displaystring .= '<div class="gw-featured">';
			}
			if ($event['post_link']) {
			    $displaystring .= '<a href="'.$event['post_link'].'">';
			}
			$displaystring .= '<div class="gw-event ' . $event['tag'] . '">';
			$displaystring .= '<span class="gw-bullet">&nbsp;</span><div class="gw-time">' . date('g:i a', $start) . '</div>';
			$displaystring .= '<div class="gw-title">' . $event['title'];
			if ($event['location']) {
				$displaystring .= ' @ ' . $event['location'] . $this->displayMap($event['address']);
			}
			$displaystring .=  '</div>'; //close .gw-title
			$displaystring .= '</div>'; // close .gw-event 
			if ($event['post_link']) {
			    $displaystring .= '</a>';
			}
			if ($event['isfeatured']) {
			    $displaystring .= '</div>';
			}
			

		} // end foreach
		
		$displaystring .= '</div>'; // close .textwidget
		return $displaystring;
	} // end display()

	
	// ----------------------------------------------------------------------------------
	// helper functions
	// ----------------------------------------------------------------------------------
	
	function displayTime($event, $dayformat = 'D n/j', $timeformat = 'g:ia') {
		$str = '';
		$start = $event['starttime'];
		$end = $event['endtime'];
		
		if ($event['allday']) {
			$str .= date($dayformat, $start);
			if ($start != $end)
				$str.= ' - '.date($dayformat, $end);
		}
		else if ($start == $end) 
			$str .= date($dayformat .', ' . $timeformat, $start);
		else if (date($dayformat, $start) == date($dayformat, $end)) 
			$str .= date($dayformat .', ' . $timeformat, $start).date('-' . $timeformat, $end);
		else 	$str .= date($dayformat, $start).' - '.date($dayformat, $end).', '.date($timeformat, $start);
		
		return $str;
	} // end displayTime()
	
	function displayMap($address, $maptext = 'map', $prefix = '(', $suffix = ')', $alwaysshowtext = false) {
		$str = '';
		
		if ($address)  {
			$str .= ' <span class="gcal-map">' . $prefix . '<a href="http://maps.google.com/maps?f=q&hl=en&q=';
			$str .= urlencode($address).'" title="'.$address;
			$str .= '" target="_new">' . $maptext . '</a>' . $suffix . '</span>';
		}
		else if ($alwaysshowtext) $str = $maptext;
		
		return $str;
	} // end displayMap()
	
} // end GPCal
?>

