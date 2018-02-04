<?php

    /*
     This file is part of Fox Sports Calendar Subcription.

     Fox Sports Calendar Subcription is free software: you can redistribute
     it and/or modify it under the terms of the GNU General Public License
     as published by the Free Software Foundation, either version 3 of the
     License, or (at your option) any later version.

     Fox Sports Calendar Subcription is distributed in the hope that it will
     be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
     of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with Fox Sports Calendar Subcription.  If not,
     see <http://www.gnu.org/licenses/>.
     */

    include_once("yourls.php");
    include_once("version.php");
    include_once("config.php");
    include_once("cache.php");

    // This function parses an external calendar URL and stores it's data into an array.
    // Inputs:
    // &timecheck - an external array indexed by the start date to indicate if an event
    //              occurs on a particular date; used to resolve clashes
    // shorturl   - the short url assigned to this external calendar
    // &exttz     - an external string array used to hold the timezone data from the external
    //              calendar; only included if the timezone is different to $timezone
    // timezone   - the actual timezone we plan to output on our calendar
    // priority   - the priority this calendar has when dealing with clashes
    // dstart     - yyyy-mm-dd date format used to exclude events starting prior to this date
    // dend       - yyyy-mm-dd date format used to exclude events starting after this date
    //
    // Returns a string array with the VEVENT data from the external calendar.

    function fspc_cal_parse_ext_calendar(&$timecheck, $url, &$exttz, $timezone, $priority, $dstart, $dend) {
        global $FSPC_YOURLS_ENABLE;

        // First we need to get the full URL.
        if ( $FSPC_YOURLS_ENABLE ) $url = fspc_yourls_expand($url);

        // Now we pull down the calendar. We expand it based on the \n character, but we
        // also strip out \r characters. This allows for calendars that don't use \r\n
        // for the end of line (technically against spec, but generally still works).
        $html = fspc_cache_file_get($url, FSPC_GET_CONTENTS, FSPC_DEFAULT_CACHE_TIME, 'extcal');

        if ( substr($html, 0, 2) == "\x1f\x8b" ) {
            $html = gzdecode($html);
        }

        $lines = explode("\n", str_replace("\r", "", $html));

        $intz = 0;
        $inevent = 0;
        $tzdata = '';
        $tzname = '';
        $eventdata = '';
        $dtstamp = '';
        $extdata = array();
        $skipevent = false;

        // We iterate through each line of the iCal, and look for tags to extract data.
        foreach($lines as $line) {
            $skipline = false;

            // If we've found a timezone block, we start tracking it so we can include
            // the timezone in the output.
            if ( strpos($line, 'BEGIN:VTIMEZONE') !== false ) {
                $intz = 1;
                $tzdata = '';
                $tzname = '';
            }

            // Events are somewhat important (it's what we want out of this after all!).
            if ( strpos($line, 'BEGIN:VEVENT') !== false ) {
                $inevent = 1;
                $eventdata = '';
                $dtstamp = '';
                $skipevent = false;
            }

            // If we're in a timezone block, we extract the data as is.
            if ( $intz == 1 ) {
                $tzdata .= $line . "\r\n";

                // We also extract the timezone name when we come across it.
                if ( strpos($line, 'TZID') !== false ) {
                    $tzname = substr($line, strpos($line, ':') + 1);
                }
            }

            // Events are extract as is. Here is where we'll modify the start/end times
            // once that functionality is implemented.
            if ( $inevent == 1 ) {
                $eventdata .= $line . "\r\n";

                // We extract the start time to use in conflict resolution.
                if ( strpos($line, 'DTSTART') !== false ) {
                    $dtstamp = substr($line, strpos($line, ':') + 1);
                    if ( strlen($dtstamp) == 8 ) $dtstamp .= 'T000000';

                    // We don't filter events based on the time, so we only need the date portion of the start date.
                    $fdtstamp = strtotime(substr($dtstamp, 0, 4) . '-' . substr($dtstamp, 4, 2) . '-' . substr($dtstamp, 6, 2));
                    if ( $fdtstamp < $dstart || $fdtstamp > $dend ) $skipevent = true;
                }
            }

            // If we find the end of a timezone block, we store it in our exttz array.
            if ( strpos($line, 'END:VTIMEZONE') !== false ) {
                $intz = 0;

                if ( $tzname != $timezone ) {
                    $exttz[] = $tzdata;
                }
            }

            // We also store our events in the event array. We also add the calendar priority
            // into the timecheck array.
            if ( strpos($line, 'END:VEVENT') !== false ) {
                $inevent = 0;

                if ( ! $skipevent ) {
                    $extdata[$dtstamp] = $eventdata;

                    if ( isset($timecheck[$dtstamp]) ) {
                        $timecheck[$dtstamp] += (1 << $priority);
                    } else {
                        $timecheck[$dtstamp] = (1 << $priority);
                    }
                }
            }
        }

        return $extdata;
    }

	// This function sends out the appropriate headers. We've split this out to allow the calendar
	// content itself to be cached.
	function fspc_cal_output_headers($teamname, $text = false) {
        if ( $text ) {
            header("Content-Type: text/plain");
        } else {
            header('Content-type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $teamname . '.ics"');
//            header("Cache-Control: no-store, no-cache, must-revalidate");
//            header("Cache-Control: post-check=0, pre-check=0", false);
//            header("Pragma: no-cache");
        }
        
        return;		
	}
	
    // This function spits out the iCal calendar for the fixture. It also adds in events from external
    // calendars if they've been specified. It does not return any data, but outputs the calendar to the
    // web page.
    function fspc_cal_output_calendar($timecheck, $gamedata, $teamname, $timezone, $extdata, $exttz,
                                      $clash, $text = false) {
        global $FSPC_FSP_BASE_URL;

        // As a precaution, we strip out any periods in the timezone. It shouldn't need them regardless,
        // and avoids someone trying to access the contents of the filesystem.
        $timezone = str_replace('.', '', $timezone);

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Code Around Corners//Fox Sports Pulse Calendar Subscription Tool v" . fspc_version() . "//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "X-WR-CALNAME:" . $teamname . "\r\n";
        echo "X-WR-CALDESC:Fox Sports Pulse Team Calendar\r\n";
        echo "X-WR-TIMEZONE:" . $timezone . "\r\n";

        echo fspc_cache_get_timezone($timezone);

        foreach($exttz as $exttzdata) {
            echo $exttzdata;
        }

        ksort($timecheck);
        $extcount = count($extdata);

        foreach($timecheck as $dtstamp => $clashcheck) {
            $prioritycheck = array();
            $outputcheck = array();

            // Priority 0 is the FSP calendar. 1+ are the priorities against the external calendars.
            // First we check if a calendar actually has an event against the current time.
            for ( $x = 0; $x <= $extcount; $x++ ) {
                $prioritycheck[$x] = (((1 << $x) & $clashcheck) > 0);
                $outputcheck[$x] = false;
            }

            // Then we check if we're displaying that event.
            $matched = false;
            for ( $x = 0; $x <= $extcount; $x++ ) {
                if ( $clash == 1 ) {
                    if ( ! $matched && $prioritycheck[$x] ) $outputcheck[$x] = true;
                } else if ( $clash == 2 ) {
                    if ( $prioritycheck[$x] ) $outputcheck[$x] = true;
                } else if ( $clash == 3 ) {
                    if ( $x == 0 && $prioritycheck[$x] ) $outputcheck[$x] = true;
                    if ( ! $prioritycheck[0] && $prioritycheck[$x] ) $outputcheck[$x] = true;
                }

                if ( $prioritycheck[$x] ) $matched = true;
            }

            if ( $outputcheck[0] ) {
                $game = $gamedata[$dtstamp];

                $location = $game['venue'];

                $fstartdate = date('Ymd\THis', $game['gamestart']);
                $fenddate = date('Ymd\THis', $game['gameend']);

                $summary = '';

                if ( $game['rawtime'] == 'BYE' ) {
                    $summary = $teamname . ' (Bye)';
                } else if ( $game['scorefor'] == '' ) {
                    $summary = $teamname . ' v ' . $game['opponent'];
                } else {
                    $summary = $teamname . ' (' . $game['scorefor'] . ') v ' . $game['opponent'] . ' (' . $game['scoreagainst'] . ')';
                }

                echo "BEGIN:VEVENT\r\n";
                echo "UID:" . $game['uid'] . "\r\n";
                echo "DTSTAMP:" . $dtstamp . "Z\r\n";
                echo "SUMMARY:" . $summary . "\r\n";

                if ( $game['allday'] == 0 ) {
                    echo "DTSTART;TZID=" . $timezone . ":" . $fstartdate . "\r\n";

                    if ( $fstartdate != $fenddate ) {
                        echo "DTEND;TZID=" . $timezone . ":" . $fenddate . "\r\n";
                    }
                } else {
                    echo "DTSTART;VALUE=DATE:" . date('Ymd', $game['gamestart']) . "\r\n";
                    echo "DTEND;VALUE=DATE:" . date('Ymd', $game['gameend']) . "\r\n";
                }

                echo "LOCATION:" . $location . "\r\n";

                if ( $game['gameurl'] != '' ) {
                    echo "URL:" . $FSPC_FSP_BASE_URL . str_replace('&amp;', '&', $game['gameurl']) . "\r\n";
                }

                echo "END:VEVENT\r\n";
            }

            for ( $x = 1; $x <= $extcount; $x++ ) {
                if ( $outputcheck[$x] ) {
                    echo $extdata[$x][$dtstamp];
                }
            }
        }

        echo "END:VCALENDAR\r\n";
    }

?>
