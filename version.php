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
    
    $FSPC_MAJOR_VERSION = 1;
    $FSPC_MINOR_VERSION = 3;
    $FSPC_REVISION = 1;
    
    // This function returns the version number. If a git branch is found,
    // we'll also pull in the last git commit ID.
    function fspc_version() {
        global $FSPC_MAJOR_VERSION;
        global $FSPC_MINOR_VERSION;
        global $FSPC_REVISION;

        $major = $FSPC_MAJOR_VERSION;
        $minor = '00' . $FSPC_MINOR_VERSION;
        $revision = '0000' . $FSPC_REVISION;
        
        $minor = substr($minor, -2, 2);
        $revision = substr($revision, -4, 4);
       
        $version = $major . '.' . $minor . '.' . $revision;
 
        if ( ($git = file_get_contents('.git/HEAD')) !== false ) {
            $file = substr($git, 5, strlen($git) - 6);
            $gitid = file_get_contents('.git/' . $file);
            $version .= '-' . substr($gitid, 0, 10);
        }

        return $version;
    }

?>
