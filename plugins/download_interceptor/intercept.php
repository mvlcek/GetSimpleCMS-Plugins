<?php

$load['plugin'] = true;
if (file_exists('../../gsconfig.php')) {
	require_once('../../gsconfig.php');
}
$GSADMIN = '../../' . (defined('GSADMIN') ? GSADMIN : 'admin');

include($GSADMIN.'/inc/common.php');

$file = GSDATAUPLOADPATH . preg_replace('/\.+\//', '', @$_GET['file']);
if (!file_exists($file)) error404();
foreach ($filters as $filter)  {
  if ($filter['filter'] == 'download-veto') {
    if (call_user_func_array($filter['function'], array($file))) error404();
  }
}
exec_action('pre-download');

header('Content-Type: '.mime_content_type($file));
readfile($file);

function error404() {
  header('HTTP/1.1 404 Not Found');
  header('Content-Type: text/plain');
  echo '404 File not found';
  exit(0);
}
