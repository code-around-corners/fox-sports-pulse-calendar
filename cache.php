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
    include_once('includes/cache.php');

    define('FSPC_BASE_CACHE_DIR', 'cache');
    define('FSPC_DEFAULT_CACHE_TIME', 10800);
    define('FSPC_CACHE_RETRY', 3);

    define('FSPC_GET_CONTENTS', 1);
    define('FSPC_GET_HTML', 2);

    function fspc_cache_file_get($url, $mode = FSPC_GET_CONTENTS, $cacheTime = FSPC_DEFAULT_CACHE_TIME, $category = '') {
    	$cacheData = Cache::getInstance();
    	$cacheData->setBaseDirectory(FSPC_BASE_CACHE_DIR);
    	$cacheData->setCacheTime(FSPC_DEFAULT_CACHE_TIME);
    	$cacheData->setRetryCount(FSPC_CACHE_RETRY);
    	$cacheData->setCacheDrift(600);

        $noCache = isset($_GET['nocache']);
        if ( $noCache ) {
            $cacheTime = 0;
            $cacheData->setCacheDrift(0);
        }

		$data = $cacheData->getFile($url, false, $category, $cacheTime);
		
		if ( $data ) {
			if ( $mode == FSPC_GET_CONTENTS ) return $data;
			if ( $mode == FSPC_GET_HTML ) return str_get_html($data);
		}
		
		return null;
	}

    function fspc_cache_get_timezone($timezone) {
        $timezone = str_replace('.', '', $timezone);
        $cacheDir = 'tz/' . substr($timezone, 0, strrpos($timezone, '/'));
        $cacheFile = 'tz/' . $timezone . '.ics';

        $isFileCached = file_exists($cacheFile);

        if ( $isFileCached && ( (time() - 86400) < filemtime($cacheFile) ) ) {
            $ics = file_get_contents($cacheFile);
        } else {
            $ics = null;
            $retryCount = FSPC_CACHE_RETRY;

            while ( ! $ics && $retryCount > 0 ) {
                $ics = file_get_contents('http://tzurl.org/zoneinfo-outlook/' . $timezone . '.ics');
                $retryCount--;
            }

            if ( $ics ) {
                $startPos = strpos($ics, 'BEGIN:VTIMEZONE');
                $endPos = strpos($ics, 'END:VTIMEZONE');

                $ics = substr($ics, $startPos, $endPos - $startPos + 15);

                if ( ! is_dir($cacheDir) ) mkdir($cacheDir, 0755, true);
                file_put_contents($cacheFile, $ics);
            } elseif ( $isFileCached ) {
                $ics = file_get_contents($cacheFile);
            }
        }

        return $ics;
    }

?>
