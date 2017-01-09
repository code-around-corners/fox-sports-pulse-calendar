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

    include_once('config.php');
    include_once('simple_html_dom.php');
    include_once('yourls.php');
    include_once('foxsports.php');
    include_once('html.php');
    include_once('calendar.php');
    include_once('cache.php');
    
    date_default_timezone_set("UTC");

    // Debugging options. I'm leaving these on by default currently as the script is still
    // in development, however at some point they'll need to get turned off so debugging
    // output doesn't show up in user's calendars.
    if ( $FSPC_DEBUG ) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    // This checks the current "mode" we're using for the page.
    // Mode 0 - We prompt the user for their FSP calendar link
    // Mode 1 - We couldn't find the appropriate links from the passed in data, so we clarify
    //          the information we have with the user.
    // Mode 2 - This is the iCal output, and should not contain any HTML.
    function fspc_get_mode() {
        if ( ! isset($_POST['url']) && ! isset($_GET['team']) ) {
            $mode = 0;
        } else if ( isset($_POST['url']) ) {
            $mode = 1;
        } else {
            $mode = 2;
        }

        return $mode;
    }

    function fspc_main() {
        global $FSPC_YOURLS_ENABLE;
        global $FSPC_DEFAULT_TEAM_NAME;

        // If we're using anything other than mode 2, we need to display the HTML headers.
        if ( fspc_get_mode() != 2 ) {
            fspc_html_header();
        }

        // If we're in mode 0, we show the page requesting the inputs to generate the calendar.
        if ( fspc_get_mode() == 0 ) {
            fspc_html_get_calendar();
        } else if ( fspc_get_mode() == 1 ) {
            // First we parse the URL passed in from our form and check which elements have
            // been specified. FSP seems to have one of two ways to specify data, either a
            // "client" or "c" parameter which is a combination of all the fields (of which
            // some can be zero if they are not specified), or alternatively the parameters
            // can be passed in individually (although can still be zero).
            $url = parse_url($_POST["url"], PHP_URL_QUERY);
            parse_str($url, $params);

            $sportID = 0;
            $assocID = 0;
            $clubID = 0;
            $compID = 0;
            $teamID = 0;

            // Here we check for the "global" parameters and set up the initial variables
            // based on that.
            if ( isset($params['client']) || isset($params['c']) ) {
                if ( isset($params['client']) ) {
                    $client = explode('-', $params['client']);
                } else {
                    $client = explode('-', $params['c']);
                }

                $sportID = $client[0];
                $assocID = $client[1];
                $clubID = $client[2];
                $compID = $client[3];
                $teamID = $client[4];
            }

            // We also check for the team ID and comp ID using the individual variables.
            if ( isset($params['id']) && $params['id'] != '0' ) $teamID = $params['id'];
            if ( isset($params['compID']) && $params['compID'] != '0' ) $compID = $params['compID'];

            // If we don't have a club ID here (which seems common when trying to access a team page
            // through an association fixture), we'll parse the URL we received directly and look for
            // the clubID parameter. Since we want to look at raw HTML here, not parse the DOM, we
            // only use the file_get_contents function, not file_get_html.
            if ( $clubID == 0 ) $clubID = fspc_fsp_get_clubid($_POST['url']);

            // If at this point we're missing any key bits of information, we display a page to
            // the user indicating their link doesn't have enough data to display the calendar.
            if ( $assocID == 0 || $teamID == 0 ) {
                fspc_html_missing_data($sportID, $assocID, $clubID, $compID, $teamID);
            } else {
                // If by this stage we still don't have a club ID, we'll try and proceed without it,
                // however we can only do that if the specified URL also contains a comp ID.
                $complist = '';
                $cluburl = '';

                if ( $clubID != '0' ) {
                    $complist = fspc_fsp_get_comps($sportID, $assocID, $clubID, $teamID);
                } else {
                    $complist = $compID;
                }

                if ( $complist == '' ) {
                    $complist = fspc_fsp_get_all_comps($sportID, $assocID);
                    $comparray = explode('-', $complist);
                    $complist = '';

                    foreach($comparray as $comp) {
                        if ( fspc_fsp_team_in_comp($sportID, $assocID, $comp, $teamID) ) {
                            $complist .= $comp . '-';
                        }
                    }

                    if ( $complist != '' ) {
                        $complist = substr($complist, 0, strlen($complist) - 1);
                    }
                }

                if ( $complist == '' ) {
                    fspc_html_no_comps($cluburl);
                } else {
                    // Validation of the game length, we strip out everything but the numbers, and if
                    // we don't have anything left, we set the game length to zero.
                    $gamelength = preg_replace('/[^0-9]/i', '', $_POST['gl']);
                    if ( $gamelength == '' ) $gamelength = 0;

                    $startoffset = preg_replace('/[^0-9]/i', '', $_POST['so']);
                    if ( $startoffset == '' ) $startoffset = 0;

                    // Now we generate the full URL that we'll eventually shorten that links to the calendar.
                    $baseurl = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
                    $fullurl = $baseurl . '?assoc=' . $assocID . '&club=' . $clubID . '&team=' . $teamID . '&comps=' . $complist . '&tz=';
                    $fullurl .= $_POST['tz'] . '&gl=' . $gamelength . '&so=' . $startoffset;

                    // We'll grab the first team competition page to get the full team name.
                    $compID = explode('-', $complist);
                    $teamname = fspc_fsp_get_team_name($sportID, $assocID, $clubID, $compID[0], $teamID, 1);
                    $teamtweet = '#' . preg_replace('/[ \'!@#$%\^&\*\(\)]/', '', $teamname);

                    // Now we'll check if an external calendar URL was provided, and that the URL
                    // does contain iCal data. Currently this is fixed at 3 calendars, we'll change this to
                    // be variable in the future.
                    $shorticsid = '';

                    for ( $x = 1; $x <= 3; $x++ ) {
                        // We only need to process a calendar if the field was used.
                        if ( isset($_POST['ics' . $x]) ) {
                            // We also double check the URL isn't blank. Theoretically the above check should
                            // be sufficient, but this is just a safety net.
                            if ( $_POST['ics' . $x] != '' ) {
                                // Because we strip the protocol off the URLs we pass through due to
                                // some strange issue I haven't been able to solve yet, we add back the
                                // http:// part of the URL.
                                $icsurl = 'https://' . $_POST['ics' . $x];
                                $icshtml = fspc_cache_file_get($icsurl, FSPC_GET_CONTENTS, FSPC_DEFAULT_CACHE_TIME, 'extcal');

                                if ( substr($icshtml, 0, 2) == "\x1f\x8b" ) {
                                    $icshtml = gzdecode($icshtml);
                                }

                                $icshtml = str_get_html($icshtml);

                                // If we don't find a valid iCal header, we try instead using the https://
                                // protocol. Most calendars don't usually use HTTPS but just in case it does,
                                // we check for that too.
                                if ( strpos($icshtml->plaintext, 'BEGIN:VCALENDAR') === false ) {
                                    $icsurl = 'http://' . $_POST['ics' . $x];
                                    $icssrc = fspc_cache_file_get($icsurl, FSPC_GET_CONTENTS, FSPC_DEFAULT_CACHE_TIME, 'extcal');

                                    if ( substr($icshtml, 0, 2) == "\x1f\x8b" ) {
                                        $icshtml = gzdecode($icshtml);
                                    }

                                    $icshtml = str_get_html($icshtml);
                                }

                                // If we discover an iCal header in the passed URL, we assume that this is a
                                // valid calendar.
                                if ( strpos($icshtml->plaintext, 'BEGIN:VCALENDAR') !== false ) {
                                    $shortics = fspc_yourls_shorten($icsurl);
                                    $shorticsid .= $shortics . '-';
                                }
                            }
                        }
                    }

                    // If we don't have a blank $shorticsid then we found some valid additional calendars. We'll
                    // add these to the calendar URL.
                    if ( $shorticsid != '' ) {
                        $shorticsid = substr($shorticsid, 0, strlen($shorticsid) - 1);
                        $fullurl .= '&ics=' . $shorticsid . '&cl=' . $_POST['cl'];
                    }

                    $texturl = $fullurl . '&t=1';

                    if ( $FSPC_YOURLS_ENABLE ) {
                        $shorturl = str_replace('http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://', '', fspc_yourls_shorten($fullurl, $teamname . ' (FSPC)'));
                    } else {
                        $shorturl = str_replace('http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://', '', $fullurl);
                    }

                    // Finally, we show the generated data to the user.
                    fspc_html_show_links($shorturl, $texturl, $teamtweet);
                }
            }
        } else if ( fspc_get_mode() == 2 ) {
            $sportID = 0;
            $assocID = $_GET['assoc'];
            $clubID = $_GET['club'];
            $teamID = $_GET['team'];
            $complist = $_GET['comps'];

            $timezone = '';
            if ( isset($_GET['tz']) ) $timezone = '&tz=' . $_GET['tz'];
            $gamelength = '';
            if ( isset($_GET['gl']) ) $gamelength = '&gl=' . $_GET['gl'];
            $startoffset = '';
            if ( isset($_GET['so']) ) $gamelength = '&so=' . $_GET['so'];
            $extics = '';
            if ( isset($_GET['ics']) ) $extics = '&ics=' . $_GET['ics'];
            $clashmode = '';
            if ( isset($_GET['cl']) ) $clashmode = '&cl=' . $_GET['cl'];
            $startdate = '';
            if ( isset($_GET['sd']) ) $startdate = '&sd=' . $_GET['sd'];
            $enddate = '';
            if ( isset($_GET['ed']) ) $enddate = '&ed=' . $_GET['ed'];

            $baseurl = 'http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
            $cacheUrl = $baseurl . '?assoc=' . $assocID . '&club=' . $clubID . '&team=' . $teamID . '&comps=' . $complist;
            $cacheUrl .= $timezone . $gamelength . $extics . $clashmode . $startdate . $enddate;

			if ( isset($_GET["assoc"]) && ! isset($_GET["cache"]) ) {
	            $teamname = fspc_fsp_get_club_name($sportID, $assocID, $clubID);
	            if ( $teamname == '' ) $teamname = $FSPC_DEFAULT_TEAM_NAME;
				$text = isset($_GET["t"]);
				
				$data = fspc_cache_file_get($cacheUrl . "&cache", FSPC_GET_CONTENTS, 3600, 'ics');

				if ( $data ) {
					fspc_cal_output_headers($teamname, $text);
					echo $data;
				} else {
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					header('Retry-After: 30');
					
					return;
				}
			} else {
				if ( ! isset($_GET["continue"]) ) {
					if ( fspc_get_active_pids() <= 3 ) {
					    fspc_set_pid_file();
					} else {
						header('HTTP/1.1 503 Service Temporarily Unavailable');
						header('Status: 503 Service Temporarily Unavailable');
						header('Retry-After: 30');
						
						return;
					}
				}
				
	            $sportID = 0;
	            $assocID = $_GET['assoc'];
	            $clubID = $_GET['club'];
	            $teamID = $_GET['team'];
	
	            $timecheck = array();
	            $complist = $_GET['comps'];
	            $teamname = '';
	
	            // Default start and end ranges if they're not specified.
	            $startdate = '1900-01-01';
	            $enddate = '2099-01-01';
	
	            // Now we grab the ranges from the URL if they exist.
	            if ( isset($_GET['sd']) ) $startdate = $_GET['sd'];
	            if ( isset($_GET['ed']) ) $enddate = $_GET['ed'];
	
	            // And we do the same for the game length.
	            $gamelength = '';
	            if ( isset($_GET['gl']) ) $gamelength = preg_replace('/[^0-9]*/', '', $_GET['gl']);
	            if ( $gamelength == '' ) $gamelength = 45;
	
	            // And we do the same for the start offset.
	            $startoffset = '';
	            if ( isset($_GET['so']) ) $startoffset = preg_replace('/[^0-9]*/', '', $_GET['so']);
	            if ( $startoffset == '' ) $startoffset = 0;
	
	            // And also our text variable for debugging.
	            $text = false;
	            if ( isset($_GET['t']) ) $text = true;
	
	            // And we create some date variables.
	            $dstart = strtotime($startdate);
	            $dend = strtotime($enddate . ' + 1 day - 1 second');
	
	            // This reference variable is used to check if all events occur in the past.
	            // If they do, we'll force an update of the competition ID list in case a
	            // team has been moved to a different division.
	            $inpast = false;
	
	            $gamedata = fspc_fsp_parse_calendar($timecheck, $complist, $teamname, $dstart, $dend, $startoffset,
	                                                $sportID, $assocID, $clubID, $teamID, $gamelength, $inpast);
	
	            // If at this point the team name is blank, and we have a club ID available, we'll check the
	            // club name listed on the club page. Generally at this point it means we've got a data
	            // issue anyway, but we still want some data showing if possible.
	            if ( $teamname == '' ) $teamname = fspc_fsp_get_club_name($sportID, $assocID, $clubID);
	            if ( $teamname == '' ) $teamname = $FSPC_DEFAULT_TEAM_NAME;
	
	            // We check if we have any elements returned from the calendar. If we don't, then they've
	            // probably moved onto their next season. We need to rerun our competition check in that
	            // case and try again. This only works when we have a club ID.
	            $checkpast = true;
	            if ( isset($_GET['s']) ) $checkpast = false;
	
	            if ( ( count($gamedata) == 0 || ($inpast && $checkpast) ) && $teamID > 0 ) {
	                // First, we'll check the club page to see if the team is listed there. If they
	                // are, we'll use that competition ID to determine the calendar events.
	                $complist = '';
	                if ( $clubID != '0' ) {
	                    $complist = fspc_fsp_get_comps($sportID, $assocID, $clubID, $teamID);
	                }
	
	                // If we don't have any identified competitions (or we don't have a valid club ID)
	                // we'll then check the raw competition page for the association. This step takes a
	                // couple of seconds per comp, so for large pages this will stall the refresh for
	                // a couple of minutes.
	                if ( $complist == '' ) {
	                    $complist = fspc_fsp_get_all_comps($sportID, $assocID);
	                    $comparray = explode('-', $complist);
	                    $complist = '';
	                    $checked = '';
	
	                    global $FSPC_MAX_COMP_CHECK;
	                    $curcompcheck = 0;
	
	                    if ( isset($_GET['check']) ) $checked = $_GET['check'];
	                    if ( isset($_GET['valid']) ) $complist = $_GET['valid'];
	
	                    foreach($comparray as $comp) {
	                        if ( strpos($checked, $comp) === false ) {
	                            $curcompcheck++;
	                            $checked .= $comp . '-';
	
	                            if ( fspc_fsp_team_in_comp($sportID, $assocID, $comp, $teamID) ) {
	                                $complist .= $comp . '-';
	                            }
	                        }
	
	                        // If we've hit the maximum competition checks on this pass, we add what's been checked
	                        // to the URL and reload the calendar to continue with the next pass.
	                        if ( $curcompcheck == $FSPC_MAX_COMP_CHECK ) {
	                            $url = 'http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	                            if ( strpos($url, '&check=') !== false ) {
	                                $url = substr($url, 0, strpos($url, '&check='));
	                            }
	
	                            $url .= '&check=' . $checked . '&valid=' . $complist . '&cache&continue';
	                            header('Location: ' . $url);
	                            return;
	                        }
	                    }
	
	                    if ( $complist != '' ) {
	                        $complist = substr($complist, 0, strlen($complist) - 1);
	                    }
	                }
	
	                if ( $complist != '' ) {
	                    $teamname = '';
	                    $gamedata = fspc_fsp_parse_calendar($timecheck, $complist, $teamname, $dstart, $dend, $startoffset,
	                                                        $sportID, $assocID, $clubID, $teamID, $gamelength, $inpast);
	                    if ( $teamname == '' ) $teamname = fspc_fsp_get_club_name($sportID, $assocID, $clubID);
	                    if ( $teamname == '' ) $teamname = $FSPC_DEFAULT_TEAM_NAME;
	                }
	
	                // If we've actually got some gamedata now, we'll update the stored short URL
	                // with the new compID values to avoid having to do this again.
	                if ( count($gamedata) != 0 && $FSPC_YOURLS_ENABLE) {
	                    // First we get the current short URL. Because YOURLS returns the same
	                    // short URL if the URL already exists, we can use the existing shorten
	                    // function to get this data.
	                    $url = 'http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	                    if ( strpos($url, '&check=') !== false ) {
	                        $url = substr($url, 0, strpos($url, '&check='));
	                    }
	
	                    $url = str_replace('&t=1', '', $url);
	                    $url = str_replace('?t=1&', '?', $url);
	
	                    $url = str_replace('%2F', '/', $url);
	                    $shorturl = fspc_yourls_get($url);
	
	                    $timezone = '';
	                    if ( isset($_GET['tz']) ) $timezone = '&tz=' . $_GET['tz'];
	                    $gamelength = '';
	                    if ( isset($_GET['gl']) ) $gamelength = '&gl=' . $_GET['gl'];
	                    $startoffset = '';
	                    if ( isset($_GET['so']) ) $gamelength = '&so=' . $_GET['so'];
	                    $extics = '';
	                    if ( isset($_GET['ics']) ) $extics = '&ics=' . $_GET['ics'];
	                    $clashmode = '';
	                    if ( isset($_GET['cl']) ) $clashmode = '&cl=' . $_GET['cl'];
	                    $startdate = '';
	                    if ( isset($_GET['sd']) ) $startdate = '&sd=' . $_GET['sd'];
	                    $enddate = '';
	                    if ( isset($_GET['ed']) ) $enddate = '&ed=' . $_GET['ed'];
	
	                    $baseurl = 'http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
	                    $fullurl = $baseurl . '?assoc=' . $assocID . '&club=' . $clubID . '&team=' . $teamID . '&comps=' . $complist;
	                    $fullurl .= $timezone . $gamelength . $extics . $clashmode . $startdate . $enddate;
	
	                    // If the short URL didn't match anything then don't update it.
	                    if ( $shorturl != '' ) fspc_yourls_update($shorturl, $fullurl, $teamname . ' (FSPC)');
	
	                    // Now we redirect to the new URL. We check if the text variable is set to facilitate
	                    // testing and ensure debugging data still shows up.
	                    if ( $text ) {
	                        header('Location: ' . $fullurl . '&s=1&t=1&cache&continue');
	                    } else {
	                        header('Location: ' . $fullurl . '&s=1&cache&continue');
	                    }
	
	                    return;
	                }
	            }
	
	            // Now we grab the timezone data. If nothing is specified, we'll default to Melbourne.
	            if ( isset($_GET['tz']) ) {
	                $timezone = $_GET['tz'];
	            } else {
	                $timezone = 'Australia/Melbourne';
	            }
	
	            // Next we'll get any external calendars, if we have them.
	            $priority = 1;
	            $extdata = array();
	            $exttz = array();
	
	            if ( isset($_GET['ics']) ) {
	                $shorticsid = explode('-', $_GET['ics']);
	
	                foreach ( $shorticsid as $shortics ) {
	                    $extdata[$priority] = fspc_cal_parse_ext_calendar($timecheck, $shortics, $exttz, $timezone, $priority, $dstart, $dend);
	                    $priority++;
	                }
	            }
	
	            // Now we output the calendar based on whatever fields we have available.
	            $clash = '';
	            if ( isset($_GET['cl']) ) $clash = preg_replace('/[^0-9]*/', '', $_GET['cl']);
	            if ( $clash == '' ) $clash = 1;
	
				fspc_cal_output_headers($teamname, $text);
	            fspc_cal_output_calendar($timecheck, $gamedata, $teamname, $timezone, $extdata, $exttz, $clash, $text);
			    fspc_clear_pid_file();
	        }
		}
		
        // Finally, we show the footer, but only if we're not in iCal mode.
        if ( fspc_get_mode() != 2 ) {
            fspc_html_footer();
        }
    }
    
    function fspc_is_pid_running($pid) {
	    $isRunning = false;
	    exec("ps -A | grep -i $pid | grep -v grep", $pids);

	    if ( count($pids) > 0 ) $isRunning = true;
	    return $isRunning;
    }
    
    function fspc_get_active_pids() {
		if ( ! is_dir("pid") ) {
			mkdir("pid", 0755, true);
    		return 0;
    	}
    	
    	$activeCount = 0;
    	
    	foreach ( glob("pid/*.pid") as $pidFile ) {
    		if ( fspc_is_pid_running(substr($pidFile, 4, strlen($pidFile) - 8)) ) {
    			$activeCount++;
    		} else {
    			unlink($pidFile);
    		}
    	}
    	
    	return $activeCount;
    }
    
    function fspc_set_pid_file() {
    	$pidFile = "pid/" . posix_getpid() . ".pid";
    	file_put_contents($pidFile, posix_getpid());
    }
    
    function fspc_clear_pid_file() {
    	$pidFile = "pid/" . posix_getpid() . ".pid";
		unlink($pidFile);    	
    }

	fspc_main();

?>
