<html>
<head>
<title>Fox Sports Pulse Calendar Viewer</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=0;" />
<meta name="apple-mobile-web-app-capable" content="yes" />
</head>

<style>
body {
	background-attachment: fixed;
	background-color: rgb(219, 219, 219);
	background-image: url(body-tail.gif);
	background-position: 50% 0px;
	background-repeat: repeat;
	color: rgb(51, 51, 51);
	display: block;
	margin: 0px;
	padding: 0px;
	position: relative;
}

h1 {
	font-size: 14px;
	text-align: center;
}

table {
	width: 100%;
	font-size: 12px;
}

td {
	-webkit-text-size-adjust: 100%;
	padding: 5px;
	font-size: 12px;
}

a {
	color: rgb(205, 27, 0);
	text-decoration: none;
}

.box {
	background-color: rgb(255, 255, 255);
	border-bottom-color: rgb(153, 153, 153);
	border-bottom-style: solid;
	border-bottom-width: 1px;
	border-left-color: rgb(153, 153, 153);
	border-left-style: solid;
	border-left-width: 1px;
	border-right-color: rgb(153, 153, 153);
	border-right-style: solid;
	border-right-width: 1px;
	border-top-color: rgb(153, 153, 153);
	border-top-style: solid;
	border-top-width: 1px;
	color: rgb(51, 51, 51);
	display: block;
	font-family: Sans-Serif;
	font-size: 9px;
	font-weight: normal;
	margin: 10px;
	padding: 10px;
}

.outer {
	max-width: 800px;
	margin: 0 auto;
}

</style>

<body>
<?php

include_once("yourls.php");
include_once("cache.php");

date_default_timezone_set("UTC");

error_reporting(E_ALL);
ini_set('display_errors', '1');

$shortcode = $_GET['code'];
$fullurl = str_replace("&amp;", "&", fspc_yourls_expand($shortcode));

$ical = fspc_cache_get($fullurl . "&cache", 'ics');
if ( ! $ical ) {
	$ical = file_get_contents($fullurl);
}

$array = explode("\r\n", $ical);

$inEvent = false;

$events = array();

for ( $x = 0; $x < count($array); $x++ ) {
	if ( $array[$x] == 'BEGIN:VEVENT' ) {
		$inEvent = true;
		$summary = "";
		$startDate = "";
		$endDate = "";
		$location = "";
		$url = "";
	} elseif ( $inEvent && $array[$x] == 'END:VEVENT' ) {
		$inEvent = false;

		if ( strlen($startDate) == 8 ) $startDate .= 'T000000';
		if ( strlen($endDate) == 8 ) $endDate .= 'T000000';

		$event = array();
		$event['summary'] = $summary;
		$event['startDate'] = $startDate;
		$event['endDate'] = $endDate;
		$event['location'] = $location;
		$event['url'] = $url;
		$events[count($events)] = $event;

	} elseif ( $inEvent && strpos($array[$x], 'SUMMARY') !== false ) {
		$summary = substr($array[$x], strpos($array[$x], ':') + 1);
	} elseif ( $inEvent && strpos($array[$x], 'LOCATION') !== false ) {
		$location = substr($array[$x], strpos($array[$x], ':') + 1);
		$location = str_replace("\,", ",", $location);
	} elseif ( $inEvent && strpos($array[$x], 'URL') !== false ) {
		$url = substr($array[$x], strpos($array[$x], ':') + 1);
	} elseif ( $inEvent && strpos($array[$x], 'DTSTART') !== false ) {
		$startDate = substr($array[$x], strpos($array[$x], ':') + 1);
	} elseif ( $inEvent && strpos($array[$x], 'DTEND') !== false ) {
		$endDate = substr($array[$x], strpos($array[$x], ':') + 1);
	}
}
?>
<div class="outer">
<div class="box">
<h1>Upcoming Games</h1>
<hr />

<table>
<?php

$today = date("Ymd\THis");

for ( $x = 0; $x < count($events); $x++ ) {
	if ( $events[$x]['endDate'] > $today ) {
		echo '<tr>' . "\n";

		echo '<td width="30%">';
		if ( strpos($events[$x]['summary'], '(Bye)') === false ) {
			  echo date('D, M j, Y \a\t g:ia', strtotime($events[$x]['startDate']));
		} else {
			echo date('D, M j, Y', strtotime($events[$x]['startDate']));
		}
		echo '</td>' . "\n";

		if ( $events[$x]['url'] != '' ) {
			  echo '<td><b><a href="' . $events[$x]['url'] . '">' . $events[$x]['summary'] . '</a></b><br />';
		} else {
			echo '<td><b>' . $events[$x]['summary'] . '</b><br />';
		}

		echo $events[$x]['location'] . '</td>' . "\n";
		echo '</tr>' . "\n";
	}
}

?>
</table>
</div>

<div class="box">
<h1>Previous Games</h1>
<hr />

<table>
<?php

for ( $x = 0; $x < count($events); $x++ ) {
	if ( $events[$x]['endDate'] <= $today ) {
		echo '<tr>' . "\n";

		echo '<td width="30%">';
		if ( strpos($events[$x]['summary'], '(Bye)') === false ) {
			  echo date('D, M j, Y \a\t g:ia', strtotime($events[$x]['startDate']));
		} else {
			echo date('D, M j, Y', strtotime($events[$x]['startDate']));
		}
		echo '</td>' . "\n";

		if ( $events[$x]['url'] != '' ) {
			  echo '<td><b><a href="' . $events[$x]['url'] . '">' . $events[$x]['summary'] . '</a></b><br />';
		} else {
			echo '<td><b>' . $events[$x]['summary'] . '</b><br />';
		}

		echo $events[$x]['location'] . '</td>' . "\n";
		echo '</tr>' . "\n";
	}
}

?>
</table>
</div>
</div>
</body>
</html>
