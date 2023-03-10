<?php
/**
  Simplifies the use of SCSS (ScssPHP) 
*/

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'SCSS', 	
	'0.1', 		
	'Martin Vlcek',
	'http://mvlcek.bplaced.net', 
	'Easy use of SCSS to customize CSS',
	'',
	''  
);

$scss_parameters = array();

/**
 * Compiles a SCSS file to a css file, if the SCSS file is newer or the parameters are changed ($multipleCSS = false).
 * Compiles a SCSS file to a parameter specific css file, if the SCSS file is newer ($multipleCSS = true). 
 * 
 * @param string $themeRelativeScssFile  the name of the SCSS file, e.g. "default.scss" or "css/default.scss"
 * @param array  $params                 an associative array with the parameters for the SCSS file or null, use param('paramName') in the SCSS file
 * @param bool   $multipleCSS            if true, than for each parameter set a new CSS file is compiled 
 */
function return_scss_css($themeRelativeScssFile, $params=null, $multipleCSS=false) {
  global $SITEURL, $TEMPLATE, $scss_parameters;
  $scssFile = GSTHEMESPATH.$TEMPLATE."/".$themeRelativeScssFile;
  $scss_parameters = $params;
  if ($params) {
    $paramStr = "";
    foreach ($params as $key => $value) {
      $paramStr .= $key."=".$value.",";
    }
    $hash = md5($paramStr);
  } else {
    $hash = 0;
  }
  $scssTime = filemtime($scssFile);
  if ($multipleCSS) {
    $cssFile = substr($scssFile,0,strrpos($scssFile,'.')).$hash.".css";
    $doCompile = !file_exists($cssFile) || filemtime($cssFile) <= $scssTime;
  } else {
    $cssFile = substr($scssFile,0,strrpos($scssFile,'.')).".css";
    $hashFile = substr($scssFile,0,strrpos($scssFile,'.')).".hash";
    $doCompile = !file_exists($hashFile) || !file_exists($cssFile) || file_get_contents($hashFile) != $hash || filemtime($cssFile) <= $scssTime;
  }
  if ($doCompile) {
    require_once(GSPLUGINPATH.'scss/scss.inc.php');
    $scssc = new scssc;
    $scssc->setImportPaths(dirname($scssFile));
    $scssc->registerFunction('param', 'return_scss_parameter');
    $css = $scssc->compile(file_get_contents($scssFile));
    file_put_contents($cssFile, $css);
    if (!$multipleCSS) file_put_contents($hashFile, $hash);
  }
  return trim($SITEURL."theme/".$TEMPLATE)."/".substr($themeRelativeScssFile,0,strrpos($themeRelativeScssFile,'.')).($multipleCSS ? $hash : '').".css";
}

/**
 * outputs the CSS file name, generated by return_scss_css.
 */
function get_scss_css($themeRelativeScssFile, $params=null, $multipleCSS=false) {
  echo return_scss_css($themeRelativeScssFile, $params, $multipleCSS);
}

/**
 * helper function to access parameters from inside the SCSS file
 */
function return_scss_parameter($args, $scssc) {
  global $scss_parameters;
  list($arg) = $args;
  $name = $arg[1];
  return isset($scss_parameters[$name]) ? $scss_parameters[$name] : '';
}