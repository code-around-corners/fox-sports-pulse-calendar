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

    include_once('simple_html_dom.php');

    define('FSPC_DEFAULT_CACHE_TIME', 86400);
    define('FSPC_GET_CONTENTS', 1);
    define('FSPC_GET_HTML', 2);

	function fspc_cache_get($key, $group) {
        global $FSPC_DB_SERVER;
        global $FSPC_DB_NAME;
	    global $FSPC_DB_USER;
	    global $FSPC_DB_PASS;

		$data = null;
		$isCached = false;
		$conn = new mysqli($FSPC_DB_SERVER, $FSPC_DB_USER, $FSPC_DB_PASS, $FSPC_DB_NAME);

        $sql = "Select cacheId, cacheExpires, data From cache Where cacheKey = '" . $key . "' And cacheGroup = '" . $group . "';";
        $result = $conn->query($sql);
		
		if ( $result->num_rows > 0 ) {
		    $row = $result->fetch_assoc();
		    $cacheExpires = $row["cacheExpires"];
			$data = $row["data"];

			mysqli_free_result($result);
			    
		    if ( $cacheExpires > time() ) {
				$isCached = true;
				$data = $row['data'];
		   	} else {
				$sql = "Delete From cache Where cacheId = " . $row["cacheId"] . ";";
				$conn->query($sql);
		    }
		}

		$conn->close();

		return $data;
	}

	function fspc_cache_set($key, $group, $data, $cacheTime) {
        global $FSPC_DB_SERVER;
        global $FSPC_DB_NAME;
	    global $FSPC_DB_USER;
	    global $FSPC_DB_PASS;
		
		$conn = new mysqli($FSPC_DB_SERVER, $FSPC_DB_USER, $FSPC_DB_PASS, $FSPC_DB_NAME);
		
		$sql = "Insert Into cache ( cacheKey, cacheGroup, data, cacheExpires ) Values ( '" . $key . "', '" . $group . "', '" .
			$conn->real_escape_string($data) . "', " . (time() + $cacheTime) . " );";
		$conn->query($sql);
		$conn->close();
	}

    function fspc_cache_file_get($url, $mode = FSPC_GET_CONTENTS, $cacheTime = FSPC_DEFAULT_CACHE_TIME, $category = '') {
        //echo "REQUEST: " . $url . "\r\n";

        $noCache = isset($_GET['nocache']);
		if ( $category != 'ics' ) $noCache = true;
		$data = null;
		$isCached = false;

		if ( ! $noCache ) {
			$data = fspc_cache_get($url, $category);
			if ( $data ) $isCached = true;
		}
		
		if ( ! $isCached || $noCache ) {
			$data = file_get_contents($url);
			
			if ( $data && ! $noCache ) {
				fspc_cache_set($url, $category, $data, $cacheTime);
			}
		}

		if ( $data ) {
			if ( $mode == FSPC_GET_CONTENTS ) return $data;
			if ( $mode == FSPC_GET_HTML ) return str_get_html($data);
		}
		
		return null;
	}

    function fspc_cache_get_timezone($timezone) {
        $timezone = str_replace('.', '', $timezone);
        $url = 'http://tzurl.org/zoneinfo-outlook/' . $timezone . '.ics';
        
        $ics = fspc_cache_file_get($url, FSPC_GET_CONTENTS, 86400 * 365, 'timezone');

		if ( $ics ) {
	        $startPos = strpos($ics, 'BEGIN:VTIMEZONE');
	        $endPos = strpos($ics, 'END:VTIMEZONE');	
	        $ics = substr($ics, $startPos, $endPos - $startPos + 15);
	    }
	       
        return $ics;
    }

?>
