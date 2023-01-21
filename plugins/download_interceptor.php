<?php
# intercepts download and offers a veto filter and an action for other plugins 
#
# you need to add a line like the following to your root .htaccess (probably at the end)
#    RewriteRule ^data/uploads/(.*)$ plugins/download_interceptor/intercept.php?file=$1 [L]
#
# provides filter 'download-veto' to veto adownload (vetoed downloads result in an error)
#  - parameter $file (full path of file to be downloaded)
#  - must return true, if the download should be forbidden
#
# provides action 'pre-download' for doing something before a download: 
#  - global variable $file is available (full path of file to be downloaded)

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'Download Interceptor', 	
	'1.0', 		
	'Martin Vlcek',
	'http://mvlcek.bplaced.net', 
	'Intercept downloads and provide hooks for other plugins',
	'',
	''  
);

##Example for filter and action
#
#add_action('pre-download', 'di_action');
#add_filter('download-veto', 'di_veto');
#
#function di_veto($file) {
#  return false; // allow all downloads
#}
#
#function di_action() {
#  global $file;
#  // do something with $file
#}
