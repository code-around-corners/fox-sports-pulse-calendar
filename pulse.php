<?php

// Current script version. Major version is for fairly major overhauls of the script.
// Minor version is for enhancements to the current script, and revisions are for any
// bug fixes.
$version = "1.0.0001";

// We're using Simple HTML DOM to parse the HTML output from the Fox Sport Pulse page.
// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

// Debugging options. I'm leaving these on by default currently as the script is still
// in development, however at some point they'll need to get turned off so debugging
// output doesn't show up in user's calendars.
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

// This is the number of competitions we'll check each iteration of the check. This is
// to reduce the visible delay when you try to access a calendar where the full comp
// check required.
$maxcompcheck = 10;

// This function outputs the main page header details. This gets called any time we create
// a page that actually requires a HTML header. iCal output does not.
function fspc_html_header() {
?>
<html>

<head>

<title>Fox Sports Pulse Calendar Subscription Tool</title>

<meta name="description" content="Fox Sports Pulse Calendar Subscription Tool" />
<meta name="keywords" content="ics,icalendar,webcal" />
<meta name="author" content="Tim Crockford" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=0;" />
<meta name="apple-mobile-web-app-capable" content="yes" />

<link rel="stylesheet" type="text/css" href="sinorcaish-screen.css" />

<script language="javascript">
function fspc_fix_ics(field) {
	field.value = field.value.replace('http://', '');
	field.value = field.value.replace('https://', '');
	field.value = field.value.replace('webcal://', '');
}
</script>

</head>

<body>

<h1>Fox Sports Pulse Calendar Subscription Tool</h1>

<?php
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

// This function is for mode 0 where we collect the data to generate the calendar link.
function fspc_html_get_calendar() {
?>
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

<form id="fspc_form" action="<?php echo basename(__FILE__); ?>" method="post">
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
	<input id="ics1" name="ics1" type="text" style="width: 100%;" onChange="fspc_fix_ics(this);" /><br />
	<input id="ics2" name="ics2" type="text" style="width: 100%;" onChange="fspc_fix_ics(this);" /><br />
	<input id="ics3" name="ics3" type="text" style="width: 100%;" onChange="fspc_fix_ics(this);" />
</p>

<p>
	How do you want to handle timing clashes with your external calendar?<br />
	<select name="cl">
		<option value="1" selected>Show only first scheduled event</option>
		<option value="2">Show all scheduled events, regardless of any clashes</option>
		<option value="3">Show only Fox Sports Pulse events, or all external events (ignore clashing external events)</option>
	</select>
</p>

<input type="submit" />
</form>

<?php
}

function fspc_html_missing_data($assocID, $clubID, $compID, $teamID) {
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
}

function fspc_html_no_comps($cluburl) {
?>
<p>
No competitions were found for this team. You may have not specified a team URL, or they
may not be using Fox Sports Pulse for their fixtures.
</p>

<?php
	if ( $cluburl != '' ) {
?>

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
?>

<p>
Because the link you provided did not contain a club ID, this tool is unable to generate
the link to your club page. Please try running the tool again with a different URL.
</p>
<?php
	}
}

// This function calls the YOURLS instance on my cdac.link server and returns a shortened
// url for use in other functions. It only returns the short code, not the full URL.
function fspc_yourls_shorten($url, $title = '') {
	$yourlsurl = 'http://cdac.link/yourls-api.php?signature=4266e70d6e&action=shorturl&url=';
	$yourlsurl .= urlencode($url);
	
	if ( $title != '' ) {
		$yourlsurl .= '&title=' . urlencode($title);
	}

	$yourls = file_get_html($yourlsurl);
	return $yourls->find('shorturl', 0)->plaintext;
}

// This function gets the keyword associated with a URL. Used when updating URL data.
function fspc_yourls_get($url) {
	$yourlsurl = 'http://cdac.link/yourls-api.php?signature=4266e70d6e&action=geturl&url=';
	$yourlsurl .= urlencode($url);
	$yourls = file_get_html($yourlsurl);
	return $yourls->find('keyword', 0)->plaintext;
}

// This function updates a YOURLS shortcode with a new URL. Used when the competitions
// stored against a team have changed.
function fspc_yourls_update($shorturl, $url, $title = '') {
	$yourlsurl = 'http://cdac.link/yourls-api.php?signature=4266e70d6e&action=update&url=';
	$yourlsurl .= urlencode($url);
	$yourlsurl .= '&shorturl=' . $shorturl;

	if ( $title != '' ) {
		$yourlsurl .= '&title=' . urlencode($title);
	}

	$yourls = file_get_contents($yourlsurl);
	return;
}

// This will return FSP URLs based on input parameters.
// Type:
// 0 = Club team list, only works if you have a club ID specified
// 1 = Team info page, used to extract team names and data
// 2 = Competition page for an association
// 3 = Competition ladder
// 4 = Full competition fixture
function fspc_gen_link($assocID, $clubID, $compID, $teamID, $type) {
	if ( $type == 0 ) {
		$id = '0-' . $assocID . '-' . $clubID . '-0-0';
		$url = 'http://www.foxsportspulse.com/club_info.cgi?c=' . $id . '&a=TEAMS';
	} elseif ( $type == 1 ) {
                $id = '0-' . $assocID . '-' . $clubID . '-' . $compID . '-' . $teamID;
                $url = 'http://www.foxsportspulse.com/team_info.cgi?c=' . $id;
	} elseif ( $type == 2 ) {
		$id = '0-' . $assocID . '-0-0-0';
		$url = 'http://www.foxsportspulse.com/assoc_page.cgi?c=' . $id . '&a=COMPS';
	} elseif ( $type == 3 ) {
		$id = '0-' . $assocID . '-0-' . $compID . '-0';
		$url = 'http://www.foxsportspulse.com/comp_info.cgi?c=' . $id . '&a=LADDER';
	} elseif ( $type == 4 ) {
		$id = '0-' . $assocID . '-0-' . $compID . '-0';
		$url = 'http://www.foxsportspulse.com/comp_info.cgi?c=' . $id . '&a=ROUND&round=-1';
	}
	
	return $url;
}

// This function checks the passed URL for the presence of a club ID parameter so if the
// original URL did not contain one, it can still be extracted for competition details.
function fspc_get_clubid($url) {
	$findclubid = file_get_contents($_POST['url']);

	if ( preg_match('/clubID=[0-9]*/i', $findclubid, $matched) == 1 ) {
		return substr($matched[0], 7, strlen($matched[0]) - 7);
	} else {
		return 0;
	}
}

// This will grab all the competition IDs for a specified team. As FSP only shows active
// competitions, this means that if you run the same URL through this in two different
// seasons, you'll get two different IDs.
function fspc_get_comps($assocID, $clubID, $teamID) {
	$cluburl = fspc_gen_link($assocID, $clubID, 0, 0, 0);
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

// This will look up the team page and extract the team name.
function fspc_get_team_name($url) {
        $teamhtml = file_get_html($url);

        $teamname = $teamhtml->find('h2', 0)->plaintext;
        $teamname = preg_replace('/[^:]*:([^\(]*).*/i', '$1', $teamname);
        $teamname = preg_replace('/&nbsp;/i', ' ', $teamname);
        $teamname = preg_replace('/[^A-Za-z0-9 ]*/i', '', $teamname);
        $teamname = preg_replace('/^ */i', '', $teamname);
        $teamname = preg_replace('/ *$/i', '', $teamname);
	
	return $teamname;
}

// This is the function we use to display the calendar links to the user. It also generates
// a QR code using Google to allow them to scan the calendar link.
function fspc_html_show_links($shorturl, $texturl, $teamtweet) {
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

function fspc_html_footer() {
?>
</body>
</html>	
<?php
}

function fspc_main() {
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
		if ( $clubID == 0 ) {
			$clubID = fspc_get_clubid($_POST['url']);
		}
		
		// If at this point we're missing any key bits of information, we display a page to
		// the user indicating their link doesn't have enough data to display the calendar.
		if ( $assocID == 0 || $teamID == 0 ) {
			fspc_html_missing_data($assocID, $clubID, $compID, $teamID);
		} else {
			// If by this stage we still don't have a club ID, we'll try and proceed without it,
			// however we can only do that if the specified URL also contains a comp ID.
			$complist = '';
			$cluburl = '';
			
			if ( $clubID != '0' ) {
				$complist = fspc_get_comps($assocID, $clubID, $teamID);
			} else {
				$complist = $compID;
			}

			if ( $complist == '' ) {
				$complist = fspc_get_all_comps($assocID);
				$comparray = explode('-', $complist);
				$complist = '';
				
				foreach($comparray as $comp) {
					if ( fspc_team_in_comp($assocID, $comp, $teamID) ) {
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

				// Now we generate the full URL that we'll eventually shorten that links to the calendar.
				$baseurl = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
				$fullurl = $baseurl . '?assoc=' . $assocID . '&club=' . $clubID . '&team=' . $teamID . '&comps=' . $complist . '&tz=';
				$fullurl .= $_POST['tz'] . '&gl=' . $gamelength;

				// We'll grab the first team competition page to get the full team name.
				$compID = explode('-', $complist);
				$teamname = fspc_get_team_name(fspc_gen_link($assocID, $clubID, $compID[0], $teamID, 1));
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
							$icsurl = 'http://' . $_POST['ics' . $x];
							$icshtml = file_get_html($icsurl);
	
							// If we don't find a valid iCal header, we try instead using the https://
							// protocol. Most calendars don't usually use HTTPS but just in case it does,
							// we check for that too.
							if ( strpos($icshtml->plaintext, 'BEGIN:VCALENDAR') === false ) {
								$icsurl = 'https://' . $_POST['ics' . $x];
								$icshtml = file_get_html($icsurl);
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
				$shorturl = str_replace('http://', '', fspc_yourls_shorten($fullurl, $teamname . ' (FSPC)'));
				
				// Finally, we show the generated data to the user.
				fspc_html_show_links($shorturl, $texturl, $teamtweet);
			}
		}
	} else if ( fspc_get_mode() == 2 ) {
		$timecheck = array();
		$complist = $_GET['comps'];
		$teamname = '';
		
		// Default start and end ranges if they're not specified.
		$startdate = '1900-01-01';
		$enddate = '2099-01-01';
	
		// Now we grab the ranges from the URL if they exist.
		if ( isset($_GET['sd']) ) $startdate = $_GET['sd'];
		if ( isset($_GET['ed']) ) $enddate = $_GET['ed'];
	
		// And we create some date variables.
		$dstart = strtotime($startdate);
		$dend = strtotime($enddate . ' + 1 day - 1 second');

		$gamedata = fspc_parse_calendar($timecheck, $complist, $teamname, $dstart, $dend);

		// We check if we have any elements returned from the calendar. If we don't, then they've
		// probably moved onto their next season. We need to rerun our competition check in that
		// case and try again. This only works when we have a club ID.
		if ( count($gamedata) == 0 ) {
			$assocID = $_GET['assoc'];
			$clubID = $_GET['club'];
			$teamID = $_GET['team'];

			// First, we'll check the club page to see if the team is listed there. If they
			// are, we'll use that competition ID to determine the calendar events.
			$complist = '';
			if ( $clubID != '0' ) {
				$complist = fspc_get_comps($assocID, $clubID, $teamID);
			}

			// If we don't have any identified competitions (or we don't have a valid club ID)
			// we'll then check the raw competition page for the association. This step takes a
			// couple of seconds per comp, so for large pages this will stall the refresh for
			// a couple of minutes.
			if ( $complist == '' ) {
				$complist = fspc_get_all_comps($assocID);
				$comparray = explode('-', $complist);
				$complist = '';
				$checked = '';
				
				global $maxcompcheck;
				$curcompcheck = 0;
				
				if ( isset($_GET['check']) ) $checked = $_GET['check'];
				if ( isset($_GET['valid']) ) $complist = $_GET['valid'];
				
				foreach($comparray as $comp) {
					if ( strpos($checked, $comp) !== false ) {
						$curcompcheck++;
						$checked .= $comp . '-';
						
						if ( fspc_team_in_comp($assocID, $comp, $teamID) ) {
							$complist .= $comp . '-';
						}
					}
					
					// If we've hit the maximum competition checks on this pass, we add what's been checked
					// to the URL and reload the calendar to continue with the next pass.
					if ( $curcompcheck == $maxcompcheck ) {
						$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						if ( strpos($url, '&check=') !== false ) {
							$url = substr($url, 0, strpos($url, '&check='));
						}
						
						$url .= '&check=' . $checked . '&valid=' . $complist;
						header('Location: ' . $url);
						return;
					}
				}
				
				if ( $complist != '' ) {
					$complist = substr($complist, 0, strlen($complist) - 1);
					$gamedata = fspc_parse_calendar($timecheck, $complist, $teamname, $dstart, $dend);
				}
			}
			
			// If we've actually got some gamedata now, we'll update the stored short URL
			// with the new compID values to avoid having to do this again.
			if ( count($gamedata) != 0 ) {
				// First we get the current short URL. Because YOURLS returns the same
				// short URL if the URL already exists, we can use the existing shorten
				// function to get this data.
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				if ( strpos($url, '&check=') !== false ) {
					$url = substr($url, 0, strpos($url, '&check='));
				}

				$url = str_replace('&t=1', '', $url);
				$url = str_replace('?t=1&', '?', $url);
				$shorturl = fspc_yourls_get($url);
				
				$timezone = '';
				if ( isset($_GET['tz']) ) $timezone = '&tz=' . $_GET['tz'];
				$gamelength = '';
				if ( isset($_GET['gl']) ) $gamelength = '&gl=' . $_GET['gl'];
				
				$baseurl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
				$fullurl = $baseurl . '?assoc=' . $assocID . '&club=' . $clubID . '&team=' . $teamID . '&comps=' . $complist;
				$fullurl .= $timezone . $gamelength;
				
				// If the short URL didn't match anything then don't update it.
				if ( $shorturl != '' ) fspc_yourls_update($shorturl, $fullurl, $teamname);
				
				// Now we redirect to the new URL. We check if the t variable is set to facilitate
				// testing and ensure debugging data still shows up.
				if ( isset($_GET['t']) ) {
					header('Location: ' . $fullurl . '&t=1');
				} else {
					header('Location: ' . $fullurl);
				}
				
				return;
			}
		}
		
		// Now we grab the timezone data. If nothign is specified, we'll default to Melbourne.
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
				$extdata[$priority] = fspc_parse_external_calendar($timecheck, $shortics, $exttz, $timezone, $priority, $dstart, $dend);
				$priority++;
			}
		}
		
		// Now we output the calendar based on whatever fields we have available.
		fspc_output_calendar($timecheck, $gamedata, $teamname, $timezone, $extdata, $ezttz);
	}

	// Finally, we show the footer, but only if we're not in iCal mode.
	if ( fspc_get_mode() != 2 ) {
		fspc_html_footer();
	}
}

// This function will grab all the competitions associated with the selected association.
// This can be used when you're dealing with teams not properly associated with clubs to
// try and find which competition they're currently assigned to. Should not be used as
// the first check since it can generate a large list.
function fspc_get_all_comps($assocID) {
	$url = fspc_gen_link($assocID, 0, 0, 0, 2);
	$html = file_get_html($url);
	$complist = '';
	
	foreach($html->find('table[class=tableClass]') as $comps) {
		foreach($comps->find('td[class=flr-list-nav]') as $comp) {
			$compurl = 'http://www.foxsportspulse.com/' . str_replace('&amp;', '&', $comp->find('a', 0)->href);
			$compdata = parse_url($compurl, PHP_URL_QUERY);
			parse_str($compdata, $cparams);

			$complist = $complist . $cparams['compID'] . '-';
		}
	}
	
	// We trim the last "-" character off our competition list.
	if ( $complist != '' ) {
		$complist = substr($complist, 0, strlen($complist) - 1);
	}
	
	return $complist;
}

// This function checks if a specified team is in a comp.
function fspc_team_in_comp($assocID, $compID, $teamID) {
	$url = fspc_gen_link($assocID, 0, $compID, 0, 4);
	$html = file_get_contents($url);
	if ( strpos($html, 'id=' . $teamID) !== false ) return true;
	return false;
}

// This function parses the FSP calendar and returns all the events in an array.
function fspc_parse_calendar(&$timecheck, $complist, &$teamname, $dstart, $dend) {
	$assocID = $_GET['assoc'];
	$clubID = $_GET['club'];
	$teamID = $_GET['team'];
	$compID = explode("-", $complist);
	
	$gamedata = array();
	$gamelength = '45 minutes';

	if ( isset($_GET['gl']) ) {
		$gamelength = $_GET['gl'] . ' minutes';
	}

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
	}
	
	return $gamedata;
}

// This function parses an external calendar URL and stores it's data into an array.
function fspc_parse_external_calendar(&$timecheck, $shorturl, &$exttz, $timezone, $priority, $dstart, $dend) {
	// First we need to get the full URL.
	$url = 'http://cdac.link/' . $shorturl;
	
	// Now we pull down the calendar. We expand it based on the \n character, but we
	// also strip out \r characters. This allows for calendars that don't use \r\n
	// for the end of line (technically against spec, but generally still works).
	$html = file_get_contents($url);
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

// This function spits out the iCal calendar for the fixture. It also adds in events from external
// calendars if they've been specified.
function fspc_output_calendar($timecheck, $gamedata, $teamname, $timezone, $extdata, $exttz) {
	global $version;
	$assocID = $_GET['assoc'];
	
	if ( isset($_GET['t']) ) {
		header("Content-Type: text/plain");
	} else {
		header('Content-type: text/calendar; charset=utf-8');
	}
	
	$clash = 1;
	if ( isset($_GET['cl']) ) $clash = $_GET['cl'];

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

			$location = fspc_get_location($assocID, $game['venue']);

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
			echo "URL:http://www.foxsportspulse.com/" . str_replace('&amp;', '&', $game['gameurl']) . "\r\n";
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

// This function decodes some of the locations specified in FSP into actual addresses.
// Primarily used for the WCBA.
function fspc_get_location($assocID, $location) {
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
			$location = 'Epping Leisure Centre (Court ' . $court . '), 41-53 Miller Rd, Epping, VIC, 3076';
		}
	}

	$location = preg_replace('/,/i', '\\,', $location);
	return $location;
}

fspc_main();
?>
