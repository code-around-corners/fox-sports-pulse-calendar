<?php

class Cache {
	protected $baseDirectory = "cache";
	protected $cacheTime = 3600;
	protected $retryCount = 3;
	protected $cacheDrift = 0;
	protected $lockWaitTime = 10;
	protected $lockedReturnNull = true;
	protected $lockTimeout = 60;
	
	private static $instance = null;
	
	public static function getInstance() {
		if ( is_null(self::$instance) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function setBaseDirectory($newBaseDirectory) {
		$this->baseDirectory = $newBaseDirectory;
		return;
	}
	
	public function setCacheTime($newCacheTime) {
		$this->cacheTime = $newCacheTime;
		return;
	}
	
	public function setRetryCount($newRetryCount) {
		$this->retryCount = $newRetryCount;
		return;
	}
	
	public function setCacheDrift($newCacheDrift) {
		$this->cacheDrift = $newCacheDrift;
		return;
	}
	
	public function setLockWaitTime($newLockWaitTime) {
		$this->lockWaitTime = $newLockWaitTime;
		return;
	}
	
	public function setLockedReturnNull($newLockedReturnNull) {
		$this->lockedReturnNull = $newLockedReturnNull;
		return;
	}
	
	public function setLockTimeout($newLockTimeout) {
		$this->lockTimeout = $newLockTimeout;
		return;
	}
	
    public function getFile($url, $disableRefresh = false, $category = '', $cacheTime = -1) {
    	// We build the filename from a combination of the base directory for the cache, an optional
    	// category (which is just a subdirectory in the main cache directory, useful for splitting
    	// out cache records into different categories), and an md5 hash of whatever the url is.
        $cacheDir = $this->baseDirectory . '/';
        if ( $category != '' ) $cacheDir .= $category . '/';
        $cacheFile = $cacheDir . md5($url);
        $lockFile = $cacheFile . '.lock';
        
        // Now we check if there's currently a lock on this cache item. If there is it means another
        // process is probably updating it and we should wait for it to finish. If the lock file is
        // over $lockTimeout old then we assume another process has failed and we delete it.
        if ( file_exists($lockFile) ) {
        	$lockExpires = time() - $this->lockTimeout;
        	
        	if ( $lockExpires > filemtime($lockFile) ) {
        		unlink($lockFile);
        	} else {
	        	$lockWaitTime = $this->lockWaitTime;
	        	$isLocked = true;
	        	
	        	while ( $isLocked && $lockWaitTime > 0 ) {
	        		$lockWaitTime--;
	        		sleep(1);
	        		$isUnlocked = file_exists($lockFile);
	        	}
	        	
	        	if ( $isLocked && $this->lockedReturnNull ) return null;
        	}
        }

        if ( $cacheTime == -1 ) $cacheTime = $this->cacheTime;

		// We also check if the file already exists in the cache, and determine what the current expiry
		// time would be based on the cache settings.
        $isFileCached = file_exists($cacheFile);
		$cacheExpires = time() - $cacheTime - (rand(0, $this->cacheDrift));

		// If the forceRefresh option is set, we always grab a new copy of the file. Otherwise, we
		// check the last modified time for the file against the expiry time above, and if it hasn't
		// passed we simply return our current copy of the file.
        if ( $isFileCached && ( $cacheExpires < filemtime($cacheFile) ) ) {
            $data = file_get_contents($cacheFile);
		} elseif ( $disableRefresh ) {
			$data = null;
        } else {
        	// First we create a lock file to indicate we're working on this cache item.
            if ( ! is_dir($cacheDir) ) mkdir($cacheDir, 0755, true);
        	file_put_contents($lockFile, $url);
        	
            $data = null;
            $retryCount = $this->retryCount;

			// Sometimes sites refuse to load on the first try - this gives us a couple of attempts
			// to grab content in the event the first attempt fails. We always break the loop the
			// first time we actually get content back from the server.
            while ( ! $data && $retryCount > 0 ) {
                $data = @file_get_contents($url);
                $retryCount--;
            }

			// If the data isn't null, we'll store a copy of the content returned into the cache file
			// on disk. If the cache directory doesn't exist we create that as well. If the data is
			// still null, but we actually have a previous cached file, we'll still return that file.
            if ( $data ) {
                file_put_contents($cacheFile, $data);
            } elseif ( $isFileCached ) {
                $data = file_get_html($cacheFile);
            }
            
            // Now that we've finished working on the file we'll remove our lock.
            unlink($lockFile);
        }

        return $data;
    }

	public function putFile($url, $data, $category = '') {
		global $cacheSettings;

        $cacheDir = $this->baseDirectory . '/';
        if ( $category != '' ) $cacheDir .= $category . '/';
        $cacheFile = $cacheDir . md5($url);
        $lockFile = $cacheFile . '.lock';

        if ( file_exists($lockFile) ) {
        	$lockWaitTime = $this->lockWaitTime;
        	$isLocked = true;
        	
        	while ( $isLocked && $lockWaitTime > 0 ) {
        		$lockWaitTime--;
        		sleep(1);
        		$isUnlocked = file_exists($lockFile);
        	}
        	
        	if ( $isLocked && $this->lockedReturnNull ) return false;
        }

        if ( ! is_dir($cacheDir) ) mkdir($cacheDir, 0755, true);
	    file_put_contents($cacheFile, $data);
	    
	    return true;
	}
}

?>
