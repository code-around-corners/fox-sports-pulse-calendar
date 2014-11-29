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

    // Outputs the HTML header. Only needed when we're not generating a calendar.
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
    
    // This function outputs the initial screen where the user can enter their FSP calendar URL,
    // game lengths, time zones and external calendars.
    function fspc_html_get_calendar() {
        global $FSPC_YOURLS_ENABLE;
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
<li>You can select the time zone you want events to show up in using the drop down box
below. This list can be expanded if required, please use the Tweet button if you want
to request an additional time zone be added.
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

<form id="fspc_form" action="pulse.php" method="post">
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
Would you like to show games as starting early? This is really handy if you have problems
getting people to your games on time! Select how many minutes early you want games to
start from (15 minutes for example would mean a 1:00pm event should show as starting at
12:45pm). This only applies to your Fox Sport Pulse times, not your external calendars.<br />
<select name="so">
<option value="0" selected>Start on time</option>
<option value="5">5 minutes early</option>
<option value="10">10 minutes early</option>
<option value="15">15 minutes early</option>
<option value="20">20 minutes early</option>
<option value="25">25 minutes early</option>
<option value="30">30 minutes early</option>
</select>
</p>

<?php
        if ( $FSPC_YOURLS_ENABLE ) {
?>
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
<?php
        }
?>
<input type="submit" />
</form>

<?php
    }
    
    // This is the screen we show when we couldn't find any matching data for the specified
    // URL. It's less likely now with the extra checks, but still useful.
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
Any fields above showing as zero are fields that were missing. In the meantime, please
try a different link.
</p>
<?php
    }
    
    // This function we use when there aren't any matching competitions for a team.
    // Generally this means this association doesn't use FSP, however it could just be
    // the team isn't active anymore.
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

    // This function outputs the common footer.
    function fspc_html_footer() {
?>
</body>
</html>
<?php
    }    
?>
