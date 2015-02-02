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
    
    // We need to include the config here to ensure we've got the YOURLS settings.
    include_once("config.php");
    include_once("simple_html_dom.php");

    // This function is the generic API function. Other functions use this as a
    // wrapper.
    function fspc_yourls_api($action, $url = '', $shorturl = '', $title = '') {
        global $FSPC_YOURLS_URL;
        global $FSPC_YOURLS_KEY;
        
        $yurl = $FSPC_YOURLS_URL;
        if ( substr($FSPC_YOURLS_URL, -1, 1) != '/' ) $yurl .= '/';
        $yurl .= 'yourls-api.php?signature=' . $FSPC_YOURLS_KEY;
        
        // Next we add the action to the URL. This has to be specified.
        $yurl .= '&action=' . $action;
        
        // Now we check the other parameters and add them if they're specified.
        if ( $url != '' )      $yurl .= '&url=' . urlencode($url);
        if ( $shorturl != '' ) $yurl .= '&shorturl=' . $shorturl;
        if ( $title != '' )    $yurl .= '&title=' . urlencode($title);

        // Finally we return a DOM object with the result.
        return file_get_html($yurl);
    }
    
    // This function returns the URL for a short code.
    function fspc_yourls_build_url($shorturl) {
        global $FSPC_YOURLS_URL;
        $yurl = $FSPC_YOURLS_URL;
        if ( substr($FSPC_YOURLS_URL, -1, 1) != '/' ) $yurl .= '/';
        return $yurl . $shorturl;
    }
    
    // This wrapper function will shorten a URL and return the short code, including the
    // YOURLS URL.
    function fspc_yourls_shorten($url, $title = '', $retkey = false) {
        $yourls = fspc_yourls_api('shorturl', $url, NULL, $title);

        if ( $retkey ) {
            return $yourls->find('keyword', 0)->plaintext;
        } else {
            return $yourls->find('shorturl', 0)->plaintext;
        }
    }
    
    // This wrapper function will return a short code only if it exists for the specified URL.
    // It depends on the geturl function to be available on the YOURLS API.
    function fspc_yourls_get($url) {
        $yourls = fspc_yourls_api('geturl', $url, NULL, NULL);
        return $yourls->find('keyword', 0)->plaintext;
    }
    
    // This wrapper function will update the URL of the specified short code.
    function fspc_yourls_update($shorturl, $url, $title = '') {
        $yourls = fspc_yourls_api('update', $url, $shorturl, $title);
        return;
    }
   
    // This wrapper function will return the full URL of a keyword using the YOURLS
    // API rather than the build_url function.
    function fspc_yourls_expand($shorturl) {
        $yourls = fspc_yourls_api('expand', NULL, $shorturl, NULL);
        return $yourls->find('longurl', 0)->plaintext;
    } 
?>
