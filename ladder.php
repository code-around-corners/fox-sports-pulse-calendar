<html>
<head>
<title>Fox Sports Pulse Ladder Viewer</title>
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
    margin: 5px;
    padding: 2px;
    font-size: 12px;
    text-align: center;
}

.myteam {
	background-color: #f0f0f0;
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
include_once("simple_html_dom.php");

$code = $_GET['code'];
$foundUrl = false;
    
if ( $code != '' ) {
	$url = html_entity_decode(fspc_yourls_expand($code));
	parse_str(substr($url, strpos($url, '?') + 1), $array);
	$assoc = $array['assoc'];
	$club = $array['club'];
	$team = $array['team'];
	$comp = $array['comps'];
}

if ( $assoc == 0 && isset($_GET['assoc']) ) $assoc = $_GET['assoc'];
if ( $team == 0 && isset($_GET['teamid']) ) $team = $_GET['teamid'];
if ( $comp == 0 && isset($_GET['comp']) ) $comp = $_GET['comp'];

if ( $assoc == 0 || $team == 0 || $comp == 0 ) {
	$foundUrl = false;
} else {
	$foundUrl = true;
}

$compName = "";

if ( $foundUrl ) {
	$foundData = false;
	$compIds = explode("-", $comp);
	$ldata = null;
	
	for ( $y = 0; $y < count($compIds) && ! $foundData; $y++ ) {
	    $url = "http://websites.sportstg.com/team_info.cgi?c=1-$assoc-$club-" . $compIds[$y] . "-$team&a=LADDER";
	    
	    $ladderHtml = file_get_contents($url);
	    $ladder = str_get_html($ladderHtml);
	    
	    $tables = $ladder->find('table[class=tableClass]');
	    $ldata = array();
	
	    foreach ( $tables as $table ) {
	        $rows = $table->find('tr');
	
	        $skipfirst = true;
	
	        foreach ( $rows as $row ) {
	            if ( $skipfirst ) {
	                $skipfirst = false;
	            } else if ( $row->find('td', 1) != null ) {
		            $foundData = true;
		            
	                for ( $x = 0; $x < 16; $x++ ) {
	                    if ( $x == 0 ) {
	                        $pos = $row->find('td', $x)->plaintext;
	                        $ldata[$pos] = array();
	                        $ldata[$pos]['position'] = $pos;
	                    } else if ( $x == 1 ) {
	                        $ldata[$pos]['team'] = $row->find('td', $x)->plaintext;
	                        $ldata[$pos]['teamurl'] = $row->find('td', $x)->find('a', 0)->href;
	
	                        if ( strpos($ldata[$pos]['teamurl'], "id=" . $team) !== false ) $foundData = true;
	                    } else if ( $x > 1 ) {
	                        $value = $row->find('td', $x)->plaintext;
	
	                        if ( strpos($value, '-') !== false ) {
	                            $value = substr($value, 0, strpos($value, '-') - 1);
	                        }
	                        $value = preg_replace('/[^0-9\.]/i', '', $value);
	
	                        if ( $x == 2 ) $ldata[$pos]['games'] = $value;
	                        if ( $x == 3 ) $ldata[$pos]['win'] = $value;
	                        if ( $x == 4 ) $ldata[$pos]['loss'] = $value;
	                        if ( $x == 5 ) $ldata[$pos]['draw'] = $value;
	                        if ( $x == 6 ) $ldata[$pos]['bye'] = $value;
	                        if ( $x == 7 ) $ldata[$pos]['forfeitfor'] = $value;
	                        if ( $x == 8 ) $ldata[$pos]['forfeitag'] = $value;
	                        if ( $x == 9 ) $ldata[$pos]['for'] = $value;
	                        if ( $x == 10 ) $ldata[$pos]['against'] = $value;
	                        if ( $x == 11 ) $ldata[$pos]['percentage'] = $value;
	                        if ( $x == 13 ) $ldata[$pos]['points'] = $value;
	                    }
	                }
	            }
	        }
	    }
	    
	    $compName = $ladder->find("div.othercomps h2.blockHeading", 0)->plaintext;
	}
}

?>
<div class="outer">
<div class="box">
<h1><?php echo ($compName == "" || $compName == null ? "Ladder" : $compName); ?></h1>
<hr />

<table>
<thead>
<tr>
<th data-type="numeric">#</th>
<th>Team</th>
<th data-hide="phone" data-type="numeric">W</th>
<th data-hide="phone,tablet" data-type="numeric">L</th>
<th data-hide="phone,tablet" data-type="numeric">D</th>
<th data-hide="phone,tablet" data-type="numeric">B</th>
<th data-hide="phone,tablet" data-type="numeric">F</th>
<th data-hide="phone,tablet" data-type="numeric">For</th>
<th data-hide="phone,tablet" data-type="numeric">Ag</th>
<th data-hide="phone" data-type="numeric">%</th>
<th data-hide="phone" data-type="numeric">Pts</th>
</tr>
</thead>
<tbody>

<?php

if ( $foundData ) {
    ksort($ldata);

    foreach ( $ldata as $litem ) {
        $myTeam = false;
        if ( strpos($litem['teamurl'], 'id=' . $team) !== false ) $myTeam = true;

        if ( $myTeam ) {
            echo '<tr class=myteam>';
        } else {
            echo '<tr>';
        }

        echo '<td>' . $litem['position'] . '</td>';
        echo '<td><a' . $hrefstyle . ' href="' . $litem['teamurl'] .'" target="_new">' . $litem['team'] . '</a></td>';
        echo '<td>' . $litem['win'] . '</td>';
        echo '<td>' . $litem['loss'] . '</td>';
        echo '<td>' . $litem['draw'] . '</td>';
        echo '<td>' . $litem['bye'] . '</td>';
        echo '<td>' . $litem['forfeitfor'] . '</td>';
        echo '<td>' . $litem['for'] . '</td>';
        echo '<td>' . $litem['against'] . '</td>';
        echo '<td>' . $litem['percentage'] . '</td>';
        echo '<td>' . $litem['points'] . '</td>';
        echo '</tr>';
    }
}

?>
</table>
</div>
</div>
</body>
</html>