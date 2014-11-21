<?php
/* 
 * gCal PHP Framework
 * version 1.1
 * Abe Yang <abeyang@cal.berkeley.edu> (c) 2006 7
 * http://code.google.com/p/gcal-php-framework/
 * 
 * gCal PHP Framework is freely distributable under the terms of an MIT-style license.                                                                              
 * Please visit http://code.google.com/p/gcal-php-framework/ for more details.  
 *
 * =Options=
 * 'cals' = calendar var; possible values:
 * 'shows' = with respective calendar, reveal:
 *      all - include private events
 *      normal - show events with '!' prepended to titles (same as '', or null)
 *      featured - show events with '!!' prepended to titles
 * 'tags' = tag or union of tags associated with each respective calendar
 *      (if no tag is specified, then it will pull everything):
 *      for KAIROS: kairos, kairos1, kairos2, kairos3, kairos4, kairosw
 *
 * =Other Options=
 * 'debug' = verbose options (useful for debugging)
 * 'startdate' = starting date in YYYY-MM-DD format (default to 'today')
 * 'numdays' = time frame in days (default = 30): inclusive
 *
 * =TODO=
 * multi-tags per event(?)
 *
/* ---------------------------------------------------------------------------------- */

define('GCAL_PATH', dirname(__FILE__) . '/');
// include files
require_once(GCAL_PATH . '../google-api-php-client/autoload.php');

class gCal {
    // ----------------------------------------------------------------------------------
    // variables
    // ----------------------------------------------------------------------------------
    var $version = 1.1;
    
    var $calendar;
    var $shows;
    var $tags;
    var $events;            // ultimately, need to fill this array
    var $exceptions;        // temporary storage array for exception events
        
    // time vars
    var $cal_timeMin;     // $startdate
    var $cal_timeMax;       // $enddate
    var $cal_windowsec;     // $windowsec

    // misc
    var $id = 0;                // for unique div id's
    var $debug;
    var $url = 'http://www.google.com/calendar/feeds/';

    var $service;
    var $client;
    
    // ----------------------------------------------------------------------------------
    // initialize
    // ----------------------------------------------------------------------------------
    function gCal($cal, $options = array()) {
        // error check
        if (!$cal) {
            echo 'Calendar not supplied';
            return;
        }
        
        $this->debug = $options['debug'];

        // set calendars, shows, and tags arrays
        $this->calendar = $cal;
        $this->shows = $options['shows'];
        $this->tags = $options['tags'];
    
        if ($this->debug) echo 'tags: '.$options['tags'].' <br />';

        // time stuff
        $this->cal_timeMin = date(DateTime::ATOM, time());
        $timeSpan = $options['numdays'] ? ($options['numdays'] + 1) * 86400 : 2678400;  // 86400 sec = 1 day; 2678400 sec = 30 days (inclusive)
        $this->cal_timeMax = date(DateTime::ATOM, time() + $timeSpan);
        
        // kick off main()

        $this->client = new Google_Client();
        $this->client->setApplicationName("GP Calendar Widget");
        $this->client->setDeveloperKey($cal["calendar_apikey"]);

        $this->service = new Google_Service_Calendar($this->client);

        $this->main();
        
    } // end gCal

    // ----------------------------------------------------------------------------------
    // engine
    // ----------------------------------------------------------------------------------
    
    // eventarray is an array of events (that are associative arrays)
    function getCalendar($cal, $show = '', $tagstring = '') {
        $userid = $cal['calendar_userid'];
        $apikey = $cal['calendar_apikey'];
        $calname = $cal['calendar_name'];

        $optParams = array("timeMin" => $this->cal_timeMin, "timeMax" => $this->cal_timeMax, "singleEvents" => TRUE);
        if ($this->debug) echo $this->cal_timeMin.' -- '.$this->cal_timeMax;
        $events = $this->service->events->listEvents($userid, $optParams);

        if ($this->debug) {
            echo "<p /><strong>$calname</strong><br />tagString: $tagstring<br/>show: $show <br />";
        }

        while(true) {
            foreach ($events->getItems() as $event) {
                $this->parseEvent($event, $calname, $show, $tagstring);
            }
            $pageToken = $events->getNextPageToken();
            if ($pageToken) {
                $newOptParam = $optParam;
                $newOptParam["pageToken"] = $pageToken;
                $events = $service->events->listEvents($userid, $newOptParam);
            } else {
                break;
            }
        }

                //$this->getEvent($xmlevent, $calname, $show, $tagstring);
    }  // end getCalendar

    function parseEvent($googleEvent, $calname, $show, $tagstring) {
        $showevent = true;

        /* parse title field */
        // '! ...'  denotes normal event
        // '!! ...'     denotes featured event
        $title = $googleEvent->getSummary();
        if (substr($title, 0, 1) == '!') {
            $isnormal = true;
            $title = substr($title, 1);
            if (substr($title, 0, 1) == '!') {
                $isfeatured = true;
                $title = substr($title, 1);
            }
        }
        // check public/important events
        if (($show == '' || $show == 'normal') && !$isnormal) {
            $showevent = false;
            if (!$this->debug) return;
        }
        else if ($show == 'featured' && !$isfeatured) {
            $showevent = false;
            if (!$this->debug) return;
        }

        // parse for tags:
        // possible examples:
        // "Bible Study"
        // "kairos2: Bible Study"
        list($event_tag_string, $title) = explode(':', $title,2);
        $event_tags = null;
        if (!$title) {
            // in the case of no tags
            $title = $event_tag_string;
        } else {
            $event_tags = explode(',', $event_tag_string);
            $event_tags = array_map('strtolower', $event_tags);
            $event_tags = array_map('trim', $event_tags);
        }
        if ($tagstring) {
            $search_tags = explode(',', $tagstring);
            $search_tags = array_map('strtolower', $search_tags);
            $search_tags = array_map('trim', $search_tags);
            // check if this tag ($eventtag) matches any tag listed in $tagstring
            if (!$event_tags) {
                $showevent = false;
                if (!$this->debug) return;
            } else {
                $common_tags = array_intersect($event_tags, $search_tags);
                if (!$common_tags) {
                    $showevent = false;
                    if (!$this->debug){
                        return;
                    }
                } 
            }
        }
        
        /* get ID */
        $id = $googleEvent->getId();
        
        /* get status */
        $status = $googleEvent->getStatus();
        if ($status == 'canceled') {
            $showevent = false;
            if (!$this->debug) return;
        }

        /* parse location/address */
        // possible text examples:
        // "getactive"
        // "getactive @ 2855 telegraph ave, berkeley 94705"
        list($location,$address) = explode('@', $googleEvent->getLocation());

        /* get content */
        $content = $googleEvent->getDescription();
        list($pre_open_bracket,$post_open_bracket) = explode('[', $content);
        list($inside_brackets,$post_close_bracket) = explode(']', $post_open_bracket);
        $content = $pre_open_bracket.$post_close_bracket;
        
        list ($starttime, $endtime, $allday) = $this->parseTimes($googleEvent->getStart(), $googleEvent->getEnd());

        // if event doesn't exist (a recurring event might already exist w/n scope)
        if (!$event) {
            // create particular event 
            $event = array(
                'id'        => $id,
                'title'     => $title, 
                'location'  => $location, 
                'address'   => $address, 
                'content'   => $content, 
                'starttime' => $starttime, 
                'endtime'   => $endtime, 
                'allday'    => $allday, 
                'isnormal'  => $isnormal, 
                'isfeatured'=> $isfeatured, 
                'tag'       => $event_tags, 
                'cal'       => $calname,
                'status'    => $status, 
                'showevent' => $showevent,
                'post_link' => $inside_brackets
            );
        
        }
        
        /* add event to events array */
        $this->events[] = $event;

    } // end getEvent
    
    // ----------------------------------------------------------------------------------
    // helper functions
    // ----------------------------------------------------------------------------------

    // given start and end times (in gCal format), return into unix timestamp
    // return array: [starttime, endtime, allday]
    function parseTimes($eventStartTime, $eventEndTime, $typeofarray = 'regular') {

        if ($eventStartTime->getDate() != null) {
            $allday = true;
            $starttime = strtotime($eventStartTime->getDate());
            $endtime = strtotime($eventEndTime->getDate());
        } else {
            $allday = false;
            $starttime = $this->gCalTime($eventStartTime->getDateTime());
            $endtime = $this->gCalTime($eventEndTime->getDateTime());
        }
        
        if ($typeofarray == 'assoc') $arr = array('starttime' => $starttime, 'endtime' => $endtime, 'allday' => $allday);
        else $arr = array($starttime, $endtime, $allday);
        return $arr;
    }
    
    // convert gCalTime to standard unix time
    // gCalTime format: 2006-07-04T18:00:00.000-07:00
    function gCalTime($time) {
        list($day, $timecode) = explode('T', $time);
        $hours = substr($timecode, 0, 2) * 3600;
        $minutes = substr($timecode, 3, 2) * 60;
        return (strtotime($day) + $hours + $minutes);
    }
    
    function main() {
        // fill array
        $showcal = is_null($this->shows) ? '' : $this->shows;
        // reset array of exceptions; only local to its resepctive calendar
        if ($this->exceptions) unset($this->exceptions);    
        // get calendar info
        $this->getCalendar($this->calendar, $showcal, $this->tags);

        // sort all events (across all calendars)
        if ($this->events) usort($this->events, sortByTime);        
        
        if ($this->debug) {
            echo '<p />';
            print_r($this->events);
        }
        
    } // end main
    
} // end class

// customized sort (sort by timestamp)
function sortByTime($a, $b) {
    if ($a['starttime'] == $b['starttime']) return 0;
    return ($a['starttime'] > $b['starttime']) ? 1 : -1;
}

?>
