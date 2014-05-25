<?php
$version = "0.6.0015";

include('simple_html_dom.php');

error_reporting(E_ALL);
ini_set('display_errors', '1');

$mode = 0;

if ( ! isset($_POST['url']) && ! isset($_GET['team']) ) {
	$mode = 0;
} else if ( isset($_POST['url']) ) {
	$mode = 1;
} else {
	$mode = 2;
}

if ( $mode != 2 ) {
?>
<html>

<head>
<title>Fox Sports Pulse Calendar Subscription Tool</title>
<meta name="description" content="iCal Subscription Tool" />
<meta name="keywords" content="ics,icalendar,webcal" />
<meta name="author" content="Tim Crockford" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=0;" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="stylesheet" type="text/css" href="sinorcaish-screen.css" />
</title>

<?php
}

if ( $mode == 0 ) {
?>
<script>
function fixics1() {
		var ics1 = document.getElementById('ics1');
		ics1.value = ics1.value.replace('http://', '');
		ics1.value = ics1.value.replace('https://', '');
		ics1.value = ics1.value.replace('webcal://', '');
}

function fixics2() {
		var ics2 = document.getElementById('ics2');
		ics2.value = ics2.value.replace('http://', '');
		ics2.value = ics2.value.replace('https://', '');
		ics2.value = ics2.value.replace('webcal://', '');
}

function fixics3() {
		var ics3 = document.getElementById('ics3');
		ics3.value = ics3.value.replace('http://', '');
		ics3.value = ics3.value.replace('https://', '');
		ics3.value = ics3.value.replace('webcal://', '');
}
</script>
<?php
}

if ( $mode != 2 ) {
?>
<body>
<?php
}

if ( $mode == 0 ) {
?>

<h1>Fox Sports Pulse Calendar Subscription Tool</h1>

<p>
<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://cdac.link/pulse" data-text="Unofficial @foxsportspulse #calendar subscription tool" data-via="TimCrockfordAU" data-related="FoxSportsPulse">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
</p>

<p>
This tool will take a team URL from Fox Sports Pulse and return a URL to an iCal
calendar link you can use to subscribe for it. I've developed this primarily as a way
to allow my basketball club to access their game times on their iPhones, however
others may find it useful as well. There are a couple of things to note though.
</p>

<ul>
<li>This tool is not developed, endorsed or supported by Fox Sports Pulse. Please don't
 ask them for help with it.
</li>
<li>This tool is offered as is - if it works for you, that's great! If not, whilst I can't
 guarantee I can fix your particular problem, use the Tweet button above if you're having
 problems getting your calendar.
</li>
<li>You need to specify how long your games run for in minutes. Fox Sport Pulse only
 lists start times. If you leave this out, it will default to 45 minutes.
</li>
<li>You can select the time zone you want events to show up in using the drop down box
 below. This list can be expanded if required, please use the Tweet button if you want
 to request an additional time zone be added. Calendars created before this feature
 was added will default to Melbourne time.
</li>
<li>The tool interrogates the club's team page to determine if a team has moved divisions
 to ensure all the games in the current season are captured. However it does this when you
 create the link, so if your team gets moved, come back here and generate a new calendar
 link.
</li>
</ul>

<p>
To begin, enter a URL from your team's site in the box below and submit. Please note
it has to be a team URL, not a club URL. The easiest way to get this is to search
for your team's name on the Fox Sports Pulse front page, and then copy the link directly
from the search resuls. Make sure you select the 'Team' tab first.
</p>

<p>
The timezone you select is used to determine which timezone your games will show up in.
Fox Sports Pulse only shows start times, not time zones, so we can't automatically
determine which timezone to show your games in. The game length is used to set the end
time for your games. If you leave this out, the games will start and end at the same time.
You will still see them on your calendar but they will have a length of 0 minutes.
</p>

<p>
You can optionally specify a URL to another ICS calendar to include those events in
this link. This means for example if you use Google Calendar for your training or
event times, you can combine those into the Fox Sports Pulse calendar to have everything
for your sports team in one place.
</p>

<form id="fspform" action="<?php echo basename(__FILE__); ?>" method="post">
<p>
	Enter a Fox Sports Pulse website:<br />
	<input name="url" type="text" style="width: 100%;" />
</p>

<p>
	Select a time zone for the calendar:<br />
	<select name="tz">
		<option value="Australia/Adelaide">Adelaide</option>
		<option value="Australia/Brisbane">Brisbane</option>
		<option value="Australia/Darwin">Darwin</option>
		<option value="Australia/Hobart">Hobart</option>
		<option value="Australia/Melbourne" selected>Melbourne</option>
		<option value="Australia/Perth">Perth</option>
		<option value="Australia/Sydney">Sydney</option>
	</select>
</p>

<p>
	Enter how long your games run for in minutes:<br />
	<input name="gl" type="text" maxlength="3" value="45" size="3" />
</p>

<p>
	Enter up to 3 external calendar URLs (optional):<br />
	<input id="ics1" name="ics1" type="text" style="width: 100%;" onChange="javascript:fixics1();" /><br />
	<input id="ics2" name="ics2" type="text" style="width: 100%;" onChange="javascript:fixics2();" /><br />
	<input id="ics3" name="ics3" type="text" style="width: 100%;" onChange="javascript:fixics3();" />
</p>

<p>
	How do you want to handle timing clashes with your external calendar?<br />
	<select name="cl">
		<option value="1" selected>Show only first scheduled event</option>
		<option value="2">Show all scheduled events, regardless of any clashes</option>
		<option value="3">Show only Fox Sports Pulse events, or all external events (ignore clashing external events)</option>
		<option value="4">Show only first external event (ignore Fox Sports Pulse event and other external events)</option>
		<option value="5">Show all external events (ignore Fox Spors Pulse event)</option>
	</select>
</p>

<input type="submit" />
</form>

<?php
} else if ( $mode == 1 ) {
$url = parse_url($_POST["url"], PHP_URL_QUERY);
parse_str($url, $params);

if ( isset($params['client']) || isset($params['c']) ) {
	if ( isset($params['client']) ) {
		$client = explode('-', $params['client']);
	} else {
		$client = explode('-', $params['c']);
	}

	$mode = $client[0];
	$assocID = $client[1];
	$clubID = $client[2];
	$compID = $client[3];
	$teamID = $client[4];
}

if ( isset($params['id']) && $params['id'] != '0' ) $teamID = $params['id'];
if ( isset($params['compID']) && $params['compID'] != '0' ) $compID = $params['compID'];

// If we don't have a club ID here (which seems common when trying to access a team page
// through an association fixutre), we'll parse the URL we received directly and look for
// the clubID parameter. Since we want to look at raw HTML here, not parse the DOM, we
// only use the file_get_contents function, not file_get_html.
if ( $clubID == 0 ) {
	$findclubid = file_get_contents($_POST['url']);

	if ( preg_match('/clubID=[0-9]*/i', $findclubid, $matched) == 1 ) {
		$clubID = substr($matched[0], 7, strlen($matched[0]) - 7);
	}
}
?>

<h1>Fox Sports Pulse Calendar Subscription Tool</h1>

<?php
if ( $assocID == '0' || $compID == '0' || $teamID == '0' ) {
?>
<p>
Sorry, from the URL you've provided we weren't able to determine all the necessary
information about your team. This is often the case if you've tried to copy a URL directly
from your association fixture. Try using the search feature on the main Fox Sport Pulse
page instead to get a link with all the necessary info.
</p>

<p>Discovered Information:</p>

<ul>
	<li>Association ID: <?php echo $assocID; ?></li>
	<li>Club ID: <?php echo $clubID; ?></li>
	<li>Competition ID: <?php echo $compID; ?></li>
	<li>Team ID: <?php echo $teamID; ?></li>
</ul>

<p>
Any fields above showing as zero are fields that were missing. This page is going to be
updated in the future to try and determine this information directly from the team page,
but until then, please try a different link.
</p>

<?php
	} else {
		if ( $clubID != '0' ) {
			$clubuid = $mode . '-' . $assocID . '-' . $clubID . '-0-0';
			$cluburl = 'http://www.foxsportspulse.com/club_info.cgi?c=' . $clubuid . '&a=TEAMS';

			$clubhtml = file_get_html($cluburl);
			$elem = $clubhtml->find('div[class=club-team-list]', 0);
		
			$complist = '';

			foreach($elem->find('div[class=club-team-row]') as $teamrow) {
				$teamurl = str_replace('&amp;', '&', $teamrow->find('h3', 0)->find('a', 0)->href);
				$teamdata = parse_url($teamurl, PHP_URL_QUERY);
				parse_str($teamdata, $tparams);

				if ( $tparams['id'] == $teamID ) $complist = $complist . $tparams['compID'] . '-';
			}
		} elseif ( $compID != '0' ) {
			$complist = $compID . '-';
		}

		if ( $complist == '' ) {
?>

<p>
No competitions were found for this team. You may have not specified a team URL, or they
may not be using Fox Sports Pulse for their fixtures.
</p>

<p>
You can click on the link below to go directly to the team list for your selected club.
They may have additional information available on the Fox Sports Pulse page that you can
use.
</p>

<ul>
	<li><a href="<?php echo $cluburl; ?>">Club Teams List Page</a></li>
</ul>

<?php
		} else {
			$complist = substr($complist, 0, strlen($complist) - 1);

			$gamelength = preg_replace('/[^0-9]/i', '', $_POST['gl']);
			if ( $gamelength == '' ) $gamelength = 0;

			$baseurl = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$fullurl = $baseurl . '?assoc=' . $assocID . '&club=' . $clubID . '&team=' . $teamID . '&comps=' . $complist . '&tz=';
			$fullurl .= $_POST['tz'] . '&gl=' . $gamelength;

			// We'll grab the first team competition page to get the full team name.
			$compID = explode('-', $complist);
	                $teamuid = '0-' . $assocID . '-' . $clubID . '-' . $compID[0] . '-' . $teamID;
	                $teamurl = 'http://www.foxsportspulse.com/team_info.cgi?c=' . $teamuid;
	                $teamhtml = file_get_html($teamurl);

	                $teamname = $teamhtml->find('h2', 0)->plaintext;
	                $teamname = preg_replace('/[^:]*:([^\(]*).*/i', '$1', $teamname);
	                $teamname = preg_replace('/&nbsp;/i', ' ', $teamname);
	                $teamname = preg_replace('/[^A-Za-z0-9 ]*/i', '', $teamname);
	                $teamname = preg_replace('/^ */i', '', $teamname);
	                $teamname = preg_replace('/ *$/i', '', $teamname);

			$teamtweet = '#' . str_replace(' ', '', $teamname);

			// Now we'll check if an external calendar URL was provided, and that the URL
			// does contain ICS data.
			$shorticsid = '';
			
			for ( $x = 1; $x <= 3; $x++ ) {
				if ( isset($_POST['ics' . $x]) ) {
					if ( $_POST['ics' . $x] != '' ) {
						$icsurl = 'http://' . $_POST['ics' . $x];
						$icshtml = file_get_html($icsurl);
	
						if ( strpos($icshtml->plaintext, 'BEGIN:VCALENDAR') === false ) {
							$icsurl = 'https://' . $_POST['ics' . $x];
							$icshtml = file_get_html($icsurl);
						}
						
						if ( strpos($icshtml->plaintext, 'BEGIN:VCALENDAR') !== false ) {
							$shortics = 'http://cdac.link/yourls-api.php?signature=4266e70d6e&action=shorturl&url=';
							$shortics .= urlencode($icsurl);
		
							$yourls = file_get_html($shortics);
							$shortics = str_replace('http://cdac.link/', '', $yourls->find('shorturl', 0)->plaintext);	
							$shorticsid .= $shortics . '-';
						}
					}
				}
			}
			
			if ( $shorticsid != '' ) {
				$shorticsid = substr($shorticsid, 0, strlen($shorticsid) - 1);
				$fullurl .= '&ics=' . $shorticsid . '&cl=' . $_POST['cl'];
			}

			$texturl = $fullurl . '&t=1';
			$shorturl = 'http://cdac.link/yourls-api.php?signature=4266e70d6e&action=shorturl&url=';
			$shorturl .= urlencode('http://' . $fullurl) . '&title=' . urlencode($teamname);

			$yourls = file_get_html($shorturl);
			$shorturl = str_replace('http://', '', $yourls->find('shorturl', 0)->plaintext);

?>

<p>Use the following URLs to access the calendar for this team (URLs are now shortened using the cdac.link URL):</p>

<ul>
	<li><a href="webcal://<?php echo $shorturl; ?>">WebCal Subscription Link</a> - Use this link to subscribe to this calendar</li>
	<li><a href="http://<?php echo $shorturl; ?>">Download/Export Calendar</a> - Use this link to download a copy of this calendar to your computer</li>
	<li><a href="http://<?php echo $texturl; ?>">View Calendar Online</a> - View the iCal file online, this is more of a debug function, and most users won't need it</li>
</ul>

<p>You can also scan the QR code below to quickly access the calendar on your phone or tablet:</p>

<img src="http://<? echo $shorturl . '.qr'; ?>" />

<p>
If you happen to find this useful, please use the Tweet button below so I can get some idea of
how many people are actually using this tool, and which teams/clubs/sports they're using it for!
</p>

<p>
<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://<?php echo $shorturl; ?>" data-text="Got my live @foxsportspulse #calendar link for <?php echo $teamtweet; ?>!" data-via="TimCrockfordAU">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
</p>

<?php
		}
	}
} else if ( $mode == 2 ) {
	$assocID = $_GET['assoc'];
	$clubID = $_GET['club'];
	$teamID = $_GET['team'];
	$compID = explode("-", $_GET['comps']);

	$gamedata = array();
	$teamname = '';
	$gamelength = '45 minutes';

	if ( isset($_GET['tz']) ) {
		$timezone = $_GET['tz'];
	} else {
		$timezone = 'Australia/Melbourne';
	}

	if ( isset($_GET['gl']) ) {
		$gamelength = $_GET['gl'] . ' minutes';
	}

	$timecheck = array();

	foreach($compID as $comp) {
		$teamuid = '0-' . $assocID . '-' . $clubID . '-' . $comp . '-' . $teamID;
		$teamurl = 'http://www.foxsportspulse.com/team_info.cgi?c=' . $teamuid;

		$teamhtml = file_get_html($teamurl);

		if ( $teamname == '' ) {
			$teamname = $teamhtml->find('h2', 0)->plaintext;
			$teamname = preg_replace('/[^:]*:([^\(]*).*/i', '$1', $teamname);
			$teamname = preg_replace('/&nbsp;/i', ' ', $teamname);
			$teamname = preg_replace('/[^A-Za-z0-9 ]*/i', '', $teamname);
			$teamname = preg_replace('/^ */i', '', $teamname);
			$teamname = preg_replace('/ *$/i', '', $teamname);
		}

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

				$startdate = strtotime($strdate . ' ' . $strtime);

				if ( $allday == 0 ) {
					if ( $gamelength != '' ) {
						$enddate = strtotime($strdate . ' ' . $strtime . ' + ' . $gamelength);
					} else {
						$enddate = $startdate;
					}
				} else {
					$enddate = strtotime($strdate . ' ' . $strtime . ' + 1 day');
				}

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
				$rounddata['venue'] = $venue;
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
	
	// Now we parse the external calendar, if one was specified. This uses the cdac.link
	// URL shortening service to store a token to the calendar instead of the full link.
	$clash = 1;
	$extdata = array();
	$exttz = array();
	$calid = 2;
	
	if ( isset($_GET['ics']) ) {
		$shorticsid = explode('-', $_GET['ics']);
		$extdata[$calid] = array();
		$clash = $_GET['cl'];
		
		foreach ( $shorticsid as $shortics ) {
			$icsurl = 'http://cdac.link/' . $shortics;
		
			$icshtml = file_get_contents($icsurl);
			$icslines = explode("\n", str_replace("\r", "", $icshtml));
		
			$intz = 0;
			$inevent = 0;
			$tzdata = '';
			$tzname = '';
			$eventdata = '';
			$dtstamp = '';
		
			foreach($icslines as $line) {
				if ( strpos($line, 'BEGIN:VTIMEZONE') !== false ) {
					$intz = 1;
					$tzdata = '';
					$tzname = '';
				}
			
				if ( strpos($line, 'BEGIN:VEVENT') !== false ) {
					$inevent = 1;
					$eventdata = '';
					$dtstamp = '';
				}
			
				if ( $intz == 1 ) {
					$tzdata .= $line . "\r\n";
					
					if ( strpos($line, 'TZID') !== false ) {
						$tzname = substr($line, strpos($line, ':') + 1);
					}
				}
			
				if ( $inevent == 1 ) {
					$eventdata .= $line . "\r\n";
				
					if ( strpos($line, 'DTSTART') !== false ) {
						$dtstamp = substr($line, strpos($line, ':') + 1);
						if ( strlen($dtstamp) == 8 ) $dtstamp .= 'T000000';
					}
				}
			
				if ( strpos($line, 'END:VTIMEZONE') !== false ) {
					$intz = 0;
					
					if ( $tzname != $timezone ) {
						$exttz[] = $tzdata;
					}
				}
				
				if ( strpos($line, 'END:VEVENT') !== false ) {
					$inevent = 0;
					$extdata[$calid][$dtstamp] = $eventdata;
					
					if ( isset($timecheck[$dtstamp]) ) {
						$timecheck[$dtstamp] += $calid;
					} else {
						$timecheck[$dtstamp] = $calid;
					}
				}
			}
			
			$calid *= 2;
		}
	}

	if ( isset($_GET['t']) ) {
		header("Content-Type: text/plain");
	} else {
		header('Content-type: text/calendar; charset=utf-8');
	}

	echo "BEGIN:VCALENDAR\r\n";
	echo "VERSION:2.0\r\n";
	echo "PRODID:-//Code Around Corners//Fox Sports Pulse Calendar Subscription Tool v" . $version . "//EN\r\n";
	echo "CALSCALE:GREGORIAN\r\n";
	echo "METHOD:PUBLISH\r\n";
	echo "X-WR-CALNAME:" . $teamname . "\r\n";
	echo "X-WR-CALDESC:Fox Sports Pulse Team Calendar\r\n";
	echo "X-WR-TIMEZONE:" . $timezone . "\r\n";

	echo file_get_contents('tz/' . $timezone . '.ics');

	foreach($exttz as $exttzdata) {
		echo $exttzdata;
	}
	
	ksort($timecheck);

	foreach($timecheck as $dtstamp => $clashcheck) {
		$outputfcs = false;
		$outputext1 = false;
		$outputext2 = false;
		$outputext3 = false;
		
		if ( $clashcheck == 1 ) {
			$outputfcs = true;
		} else if ( $clashcheck == 2 ) {
			$outputext1 = true;
		} else if ( $clashcheck == 4 ) {
			$outputext2 = true;
		} else if ( $clashcheck == 8 ) {
			$outputext3 = true;
		} else {
			if ( ($clashcheck & 1) == 1 ) $outputfcs = true;
			if ( ($clashcheck & 2) == 2 ) $outputext1 = true;
			if ( ($clashcheck & 4) == 4 ) $outputext2 = true;
			if ( ($clashcheck & 8) == 8 ) $outputext3 = true;

			if ( $clash == 1 ) {
				if ( $outputfcs ) {
					$outputext1 = false;
					$outputext2 = false;
					$outputext3 = false;
				} else if ( $outputext1 ) {
					$outputext2 = false;
					$outputext3 = false;
				} else if ( $outputext2 ) {
					$outputext3 = false;
				}
			} else if ( $clash == 2 ) {
				// Nothing needed here, we show everything
			} else if ( $clash == 3 ) {
				if ( $outputfcs ) {
					$outputext1 = false;
					$outputext2 = false;
					$outputext3 = false;
				}
			} else if ( $clash == 4 ) {
				$outputfcs = false;
				
				if ( $outputext1 ) {
					$outputext2 = false;
					$outputext3 = false;
				} else if ( $outputext2 ) {
					$outputext3 = false;
				}
			} else if ( $clash == 5 ) {
				$outputfcs = false;
			}
		}
		
		if ( $outputfcs ) {
			$game = $gamedata[$dtstamp];

			$location = preg_replace('/ *$/i', '', $game['venue']);

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
					$location = 'Epping Leisure Centre (Court ' . $court . '), 41-53 Miller Rd, Epping, VIC, 3076';
				}
			}

			$fstartdate = date('Ymd\THis', $game['gamestart']);
			$fenddate = date('Ymd\THis', $game['gameend']);
		
			$location = preg_replace('/,/i', '\\,', $location);
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
				
				if ( $gamelength != '' ) {
					echo "DTEND;TZID=" . $timezone . ":" . $fenddate . "\r\n";
				}
			} else {
				echo "DTSTART;VALUE=DATE:" . date('Ymd', $game['gamestart']) . "\r\n";
				echo "DTEND;VALUE=DATE:" . date('Ymd', $game['gameend']) . "\r\n";
			}

			echo "LOCATION:" . $location . "\r\n";
			echo "END:VEVENT\r\n";
		}
		
		if ( $outputext1 ) echo $extdata[2][$dtstamp];
		if ( $outputext2 ) echo $extdata[4][$dtstamp];
		if ( $outputext3 ) echo $extdata[8][$dtstamp];
	}
	
	echo "END:VCALENDAR\r\n";
}

if ( $mode != 2 ) {
?>
</body>
</html>
<?php
}
?>
