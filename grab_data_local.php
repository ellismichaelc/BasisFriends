<?
$path = "/Users/admin/Library/Application Support/Basis Sync/";
$_dir = "backup";

chdir($path);

$cmd  = "ls";

while(true) {
	$list = scandir('.');
	
	if(!isset($first)) $first = $list;
	else {
		if(count($first) !== count($list)) {
			// Let's make a backup!
			
			// Dir to copy to is 'backup'
			$dir = $_dir;
			
			// If it exists, let's increment until it doesn't
			while(is_dir($dir)) {
			
				// If it's empty we'll use it anyways
				if(count(scandir($dir)) == 2) break;
				
				// Otherwise let's increment
				if(!isset($num)) $num = 1;
				else $num++;
				
				$dir = $_dir . $num;
			}
			
			// Make it
			if(!is_dir($dir)) mkdir($dir) or die("Couldn't make backup dir: '{$dir}'");
			
			// Loop through the files and copy
			foreach($list as $file) {
				if(substr($file, 0, 1) == ".") continue;
				if(is_dir($file)) continue;
				
				echo "Copying: {$file}\n";
				
				if(file_exists($file)) {
					if(filesize($file) === 0) {
						echo "\t-Waiting & locking file.. ";
					
						$fp = @fopen($file, "r");
						$fl = @flock($fp, LOCK_SH);
						
						if(!$fl) echo "Couldn't lock. Still waiting.. ";
					
						while(true) {
							// Wait until the file is populated
							
							// Clear stat cache to avoid being ignored
							clearstatcache();
							
							// Check the size
							$size = @filesize($file);
							
							if(!$size && !file_exists($file)) {
								die("Missed it, file is gone. Exiting.\n\n");
							}
							
							if($size > 0) break;
						}
						
						echo "Done.\n";
					}
				} else {
					die("File was removed before it was populated. Possibly empty sync. Exiting.\n\n");
				}
				
				copy($file, realpath($dir) . "/" . $file);
				
				// Unlock the file if it was locked
				if(isset($fl)) @flock($fp, LOCK_UN);
			}
			
			break;
		}
	}
}

echo "\nDone.\n\n";
?>