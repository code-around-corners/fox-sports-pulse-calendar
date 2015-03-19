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

    include_once("simple_html_dom.php");
    
    // Theoretically this should be a constant, however I've added it as a variable
    // since previously the URL was http://www.sportingpulse.com/. In case it
    // changes again, we can update it in a single location.
    $FSPC_FSP_BASE_URL = 'http://www.foxsportspulse.com/';
    
    // This function accepts a number of different parameters and will return the
    // URL on Fox Sports Pulse for the requested data.
    //
    // sportID = The ID number of the associated sport (can use 0 if required)
    // assocID = The association we're looking up data for. This must be specified
    //           in all cases or lookups will fail.
    // clubID  = The club we're looking up. This is only required for club and team
    //           data lookups.
    // compID  = The competition we're looking up. Every season and every division
    //           has a unique competition ID.
    // teamID  = The team we're looking up. Whilst this is included for future
    //           expansion of this function, currently it is not needed.
    //
    // Types:
    // 0 = The list of teams available for a specific club; for some reason does
    //     not always include everything.
    // 1 = The team details page for a specific team. Normally used to get team
    //     statistics.
    // 2 = The main competition page for an association. Used to get all the compID
    //     values for a specific association.
    // 3 = The ladder for a specific competition.
    // 4 = The full fixture for a specific competition.
    //
    // Returns a URL as a string.
    
    function fspc_fsp_gen_link($sportID, $assocID, $clubID, $compID, $teamID, $type) {
        global $FSPC_FSP_BASE_URL;
        
        if ( $type == 0 ) {
            $id = '0-' . $assocID . '-' . $clubID . '-0-0';
            $url = 'club_info.cgi?c=' . $id . '&a=TEAMS';
        } elseif ( $type == 1 ) {
            $id = '0-' . $assocID . '-' . $clubID . '-' . $compID . '-' . $teamID;
            $url = 'team_info.cgi?c=' . $id . '&a=SFIX';
        } elseif ( $type == 2 ) {
            $id = '0-' . $assocID . '-0-0-0';
            $url = 'assoc_page.cgi?c=' . $id . '&a=COMPS';
        } elseif ( $type == 3 ) {
            $id = '0-' . $assocID . '-0-' . $compID . '-0';
            $url = 'comp_info.cgi?c=' . $id . '&a=LADDER';
        } elseif ( $type == 4 ) {
            $id = '0-' . $assocID . '-0-' . $compID . '-0';
            $url = 'comp_info.cgi?c=' . $id . '&a=ROUND&round=-1&pool=-1';
        }

        return ($FSPC_FSP_BASE_URL . $url);
    }
    
    // This function checks the passed URL for the presence of a club ID parameter so if the
    // original URL did not contain one, it can still be extracted for competition details.
    // We don't bother building a full DOM object here, we can just parse the raw HTML.
    //
    // Returns the club ID if found, or a zero if it doesn't.
    function fspc_fsp_get_clubid($url) {
        $findclubid = file_get_contents($url);
        
        if ( preg_match('/clubID=[0-9]*/i', $findclubid, $matched) == 1 ) {
            return substr($matched[0], 7, strlen($matched[0]) - 7);
        } else {
            return 0;
        }
    }
    
    // This will grab all the competition IDs for a specified team. As FSP only shows active
    // competitions, this means that if you run the same URL through this in two different
    // seasons, you'll get two different IDs. This is expected behaviour.
    //
    // Returns an empty string if no matches, or a - delimited string of competition IDs
    // if matches are found.
    function fspc_fsp_get_comps($sportID, $assocID, $clubID, $teamID) {
        $cluburl = fspc_fsp_gen_link($sportID, $assocID, $clubID, 0, 0, 0);
        $clubhtml = file_get_html($cluburl);
        $elem = $clubhtml->find('div[class=club-team-list]', 0);
        
        $complist = '';
        
        if ( isset($elem) ) {
            foreach($elem->find('div[class=club-team-row]') as $teamrow) {
                $teamurl = str_replace('&amp;', '&', $teamrow->find('h3', 0)->find('a', 0)->href);
                $teamdata = parse_url($teamurl, PHP_URL_QUERY);
                parse_str($teamdata, $tparams);
                
                if ( $tparams['id'] == $teamID ) $complist = $complist . $tparams['compID'] . '-';
            }
            
            // We trim the last "-" character off our competition list.
            if ( $complist != '' ) {
                $complist = substr($complist, 0, strlen($complist) - 1);
            }
        }
        
        return $complist;
    }
    
    // This function looks up the name of the specified club. Useful when you can't get
    // specific details on the team for various reasons.
    //
    // Returns an empty string if it can't find the name, or the club name if it can.
    function fspc_fsp_get_club_name($sportID, $assocID, $clubID) {
        if ( $clubID == '0' ) {
            $teamname = '';
        } else {
            $url = fspc_fsp_gen_link($sportID, $assocID, $clubID, 0, 0, 1);
            $html = file_get_html($url);
            
            $div = $html->find("div[class=historybar-left]", 0);
            
            $teamname = '';
            if ( isset($div) ) {
                $teamname = $div->find("a", 1)->plaintext;
            }
        }
        
        return $teamname;
    }
    
    // This will look up the team page and extract the team name. You need all the
    // details for this one, as we're looking for a specific team in a specific comp.
    // We also strip out certain characters to avoid additional formatting.
    //
    // Returns the team name as a string if found, or a blank string if it's not.
    function fspc_fsp_get_team_name($sportID, $assocID, $clubID, $compID, $teamID) {
        $url = fspc_fsp_gen_link($sportID, $assocID, $clubID, $compID, $teamID, 1);
        $teamhtml = file_get_html($url);
        
        return fspc_fsp_get_team_name_from_html($teamhtml);
    }

    // This helper function looks up a DOM object for the team name and formats it. Used
    // by other functions where needed.
    function fspc_fsp_get_team_name_from_html($html) {
        $teamname = $html->find('h2', 0)->plaintext;
        $teamname = preg_replace('/[^:]*:([^\(]*).*/i', '$1', $teamname);
        $teamname = preg_replace('/&nbsp;/i', ' ', $teamname);
        $teamname = preg_replace('/[^A-Za-z0-9 ]*/i', '', $teamname);
        $teamname = preg_replace('/^ */i', '', $teamname);
        $teamname = preg_replace('/ *$/i', '', $teamname);
        $teamname = preg_replace('/  */i', ' ', $teamname);
        
        return $teamname;
    }
    
    // This function will grab all the competitions associated with the selected association.
    // This can be used when you're dealing with teams not properly associated with clubs to
    // try and find which competition they're currently assigned to. Should not be used as
    // the first check since it can generate a large list.
    //
    // Returns an empty string if there aren't any comps, or a - delimited string of
    // competition IDs if some are found.
    function fspc_fsp_get_all_comps($sportID, $assocID) {
        global $FSPC_FSP_BASE_URL;

        $url = fspc_fsp_gen_link($sportID, $assocID, 0, 0, 0, 2);
        $html = file_get_html($url);
        $complist = '';
        
        $seasonlist = '0';
        $seasons = $html->find('select[id=complist_seasonID]', 0);
        
        if ( $seasons ) {
            foreach($seasons->find('option') as $season) {
                $seasonlist .= '-' . $season->value;
            }
        }
        
        $seasonids = explode('-', $seasonlist);
        
        foreach($seasonids as $seasonid) {
            if ( $seasonid != '0' && sizeof($seasonids) > 1 ) {
                $html = file_get_html($url . '&seasonID=' . $seasonid);
            }
            
            foreach($html->find('table[class=tableClass]') as $comps) {
                foreach($comps->find('td[class=flr-list-nav]') as $comp) {
                    $compurl = $FSPC_FSP_BASE_URL . str_replace('&amp;', '&', $comp->find('a', 0)->href);
                    $compdata = parse_url($compurl, PHP_URL_QUERY);
                    parse_str($compdata, $cparams);
                
                    $complist = $complist . $cparams['compID'] . '-';
                }
            }
        }
        
        // We trim the last "-" character off our competition list.
        if ( $complist != '' ) {
            $complist = substr($complist, 0, strlen($complist) - 1);
        }
        
        return $complist;
    }
    
    // This function checks if a specified team is in a competition. Used when rerunning team
    // checks against a full association list.
    //
    // Returns true if the team is in the comp, or false if they're not.
    function fspc_fsp_team_in_comp($sportID, $assocID, $compID, $teamID) {
        $url = fspc_fsp_gen_link($sportID, $assocID, 0, $compID, 0, 4);
        $html = file_get_contents($url);
        if ( strpos($html, 'id=' . $teamID) !== false ) return true;
        return false;
    }
    
    // This function parses a fixture and returns an array of data for that fixture than can be
    // parsed for a calendar.
    //
    // &timecheck  - an external array that uses the start time of an event as an index to
    //               indicate an event is happening at that time. Used to assist with conflicts
    //               on the calendar.
    // complist    - a dash delimited list of competition IDs to include in the calendar
    // &teamname   - a string variable to store the team name; since we have to parse fixture
    //               data anyway, this is done as a performance tweak, since we can grab the
    //               team name at the same time for virtually no cost
    // dstart      - yyyy-mm-dd date format to indicate the starting date for events
    // dend        - yyyy-mm-dd date format to indicate the ending date for events
    // startoffset - an integer indicating the number of minutes to subtract from start times
    // sportID     - the sport ID (or zero)
    // assocID     - ID of the association
    // clubID      - ID of the club (not essential, but helps)
    // teamID      - ID of the team (required)
    // gamelength  - an integer representing the length of games in minutes (integer)
    // &inpast     - an external boolean used to indicate if all the found events occur in the
    //              past (designed to allow a recheck of the calendar if required)
    //
    // The date checks are used to strip events out outside of the date range.
    //
    // Returns an array with the following format:
    // round        - the round number as specified on FSP (integer)
    // rawdate      - the raw date extracted from the fixture (string)
    // rawtime      - the raw time extracted from the fixture (string)
    // uid          - the UID for this event (generated by the routine) (string)
    // dtstamp      - the timestamp of the event, the start time is usually used (string)
    // allday       - 0 or 1, indicating if the event is all day or not (integer)
    // gamestart    - date variable containing the start time (date)
    // gameend      - date variable containing the end time (date)
    // venueurl     - the URL specified for the venue (string)
    // venue        - the name of the venue (string)
    // scorefor     - formatted score for the home team (string)
    // opponenturl  - the URL for the opposition team (string)
    // opponent     - the name of the opponent (string)
    // scoreagainst - formatted score for the opposition team (string)
    // gameurl      - the FSP URL to the game statistics (string)
    
    function fspc_fsp_parse_calendar(&$timecheck, $complist, &$teamname, $dstart, $dend, $startoffset,
                                     $sportID, $assocID, $clubID, $teamID, $gamelength, &$inpast) {
        $compID = explode("-", $complist);
        
        $gamedata = array();
        $inpast = true;
        
    // If the team ID is zero, we just return an empty array. This allows a URL to have
    // a placeholder value in the event that the external calendars are ready but the
    // FSP calendar isn't ready.
    if ( $teamID == 0 ) return $gamedata;

        foreach($compID as $comp) {
            $teamurl = fspc_fsp_gen_link($sportID, $assocID, $clubID, $comp, $teamID, 1);
            $teamhtml = file_get_html($teamurl);
            
            if ( $teamname == '' ) $teamname = fspc_fsp_get_team_name_from_html($teamhtml);
            
            foreach($teamhtml->find('table[class=tableClass]', 0)->find('tr') as $game) {
                if ( ! $game->find('th') && ! $game->find('h3') ) {
                    $round = $game->find('td', 0)->plaintext;
                    $rawdate = $game->find('td', 1)->plaintext;
                    $rawtime = $game->find('td', 2)->plaintext;
                    
                    if ( $rawtime != 'BYE' ) {
                        $venueurl = $game->find('td', 3)->find('a', 0)->href;
                        $venue = $game->find('td', 3)->find('a', 0)->plaintext;
                        $scorefor = $game->find('td', 4)->plaintext;
                        $opponenturl = $game->find('td', 6)->find('a', 0)->href;
                        $opponent = $game->find('td', 6)->find('a', 0)->plaintext;
                        $scoreagainst = $game->find('td', 7)->plaintext;
                        $gameurl = $game->find('td', 8)->find('a', 0)->href;
                        
                        // Some game results aren't just a simple score, like AFL football. So we do
                        // some additional checks to work out what kind of score we're working with,
                        // and fall back to just stripping non-numeric characters out if it doesn't
                        // match another known pattern.
                        
                        if ( preg_match('/[0-9*]\.[0-9]*-[0-9]*/', $scorefor) ) {
                            // AFL style scores
                            $scorefor = str_replace('.', '-', $scorefor);
                            $scoreagainst = str_replace('.', '-', $scoreagainst);
                            
                            $scorefor = preg_replace('/[^0-9\-]/i', '', $scorefor);
                            $scoreagainst = preg_replace('/[^0-9\-]/i', '', $scoreagainst);
                        } else {
                            $scorefor = preg_replace('/[^0-9]/i', '', $scorefor);
                            $scoreagainst = preg_replace('/[^0-9]/i', '', $scoreagainst);
                        }
                    } else {
                        $venueurl = '';
                        $venue = '';
                        $scorefor = '';
                        $opponenturl = '';
                        $opponent = '';
                        $scoreagainst = '';
                        $gameurl = '';
                    }
                    
                    $allday = 0;
                    $strdate = '20' . substr($rawdate, 6, 2) . '-' . substr($rawdate, 3, 2) . '-' . substr($rawdate, 0, 2);
                    
                    if ( $rawtime == 'BYE' ) {
                        $allday = 1;
                        $strtime = '00:00:00';
                    } else {
                        $strtime = substr($rawtime, 0, 2) . ':' . substr($rawtime, 3, 2) . ':00';
                    }
                    
                    $startdate = strtotime($strdate . ' ' . $strtime . ' - ' . $startoffset . ' minutes');
                    if ( $startdate > strtotime("now") ) $inpast = false;
                    
                    if ( $allday == 0 ) {
                        if ( $gamelength != '' ) {
                            $enddate = strtotime($strdate . ' ' . $strtime . ' + ' . $gamelength . ' minutes');
                        } else {
                            $enddate = $startdate;
                        }
                    } else {
                        $enddate = strtotime($strdate . ' ' . $strtime . ' + 1 day');
                    }
                    
                    if ( ! ($startdate < $dstart || $startdate > $dend) ) {
                        $uid = date('YmdHis', $startdate);
                        $dtstamp = date('Ymd\THis', $startdate);
                        
                        $rounddata = array();
                        $rounddata['round'] = $round;
                        $rounddata['rawdate'] = $rawdate;
                        $rounddata['rawtime'] = $rawtime;
                        $rounddata['uid'] = $uid;
                        $rounddate['dtstamp'] = $dtstamp;
                        $rounddata['allday'] = $allday;
                        $rounddata['gamestart'] = $startdate;
                        $rounddata['gameend'] = $enddate;
                        $rounddata['venueurl'] = $venueurl;
                        $rounddata['venue'] = fspc_fsp_get_location($assocID, $venue);
                        $rounddata['scorefor'] = $scorefor;
                        $rounddata['opponenturl'] = $opponenturl;
                        $rounddata['opponent'] = $opponent;
                        $rounddata['scoreagainst'] = $scoreagainst;
                        $rounddata['gameurl'] = $gameurl;
                        
                        $gamedata[$dtstamp] = $rounddata;
                        $timecheck[$dtstamp] = 1;
                    }
                }
            }
        }
        
        return $gamedata;
    }

    // This function decodes some of the locations specified in FSP into actual addresses.
    // Primarily used for the WCBA.
    //
    // Returns a string, either the original location if it couldn't match anything, or the reformatted
    // location if it can.
    function fspc_fsp_get_location($assocID, $location) {
        $location = preg_replace('/ *$/i', '', $location);
        
        if ( $assocID == 4972 ) {
            if ( strpos($location, 'Mill Park Stadium') !== false ) {
                $court = substr($location, strlen($location) - 1, 1);
                $location = 'Mill Park Basketball Stadium (Court ' . $court . '), 1 Redleap Ave, Mill Park, VIC, 3082';
            } else if ( strpos($location, 'Lalor East Primary') !== false ) {
                $court = 11;
                $location = 'Lalor East Primary School (Court ' . $court . '), Cleveland St, Thomastown, VIC, 3074';
            } else if ( strpos($location, 'Mary Mede Catholic') !== false ) {
                $court = substr($location, strlen($location) - 1, 1);
                $location = 'Marymede Catholic College (Court ' . $court . '), 60 Williamsons Rd, South Morang, VIC, 3752';
            } else if ( strpos($location, 'Epping Leisure Centre') !== false ) {
                $court = substr($location, strlen($location) - 2, 2);
                $location = 'Epping Leisure Centre (Court ' . $court . '), 41-53 Miller St, Epping, VIC, 3076';
            } else if ( strpos($location, 'Darebin Community Sports Stadium') !== false ) {
                $court = preg_replace('/[^0-9]/i', '', substr($location, strlen($location) - 2, 2));
                $location = 'Darebin Community Sports Stadium (Court ' . $court . '), 857 Plenty Rd, Reservoir, VIC, 3073';
            } else if ( strpos($location, 'Keon Park Youth Club') !== false ) {
                $court = 14;
                $location = 'Keon Park Youth Club (Court ' . $court . '), Dole Avenue, Reservoir, VIC, 3073';
            }
        }
        
        $location = preg_replace('/,/i', '\\,', $location);
        return $location;
    }

?>
