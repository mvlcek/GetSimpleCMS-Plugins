<?php
/**
 * Allows the switching of themes by the visitor.
 * (all templates configured in any page must exist in all themes)
 * (if there is a functions.php in a theme, it must be the same in all themes)
 * 
 * To temporarily (user session) switch to a theme (here Cardinal), use a link 
 *     ...?settheme&theme=Cardinal 
 * To permanently (default 30 days via cookie) switch to a theme (here Cardinal), use a link 
 *     ...?settheme&theme=Cardinal&persistent=1 
 * To switch to default theme (and clear cookie), use a link
 *     ...?settheme
 * You can also use a form with GET or POST.
 */

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

define('THEME_SWITCHER_COOKIE_TIME',30*24*60*60); # default cookie expiration 30 days

# register plugin
register_plugin(
  $thisfile, 
  'Theme Switcher',   
  '0.1',    
  'Martin Vlcek',
  'http://mvlcek.bplaced.net', 
  'Allows the visitor to switch themes',
  '',
  ''  
);

# activate filter
add_action('index-pretemplate','theme_switcher_process');

function theme_switcher_process() {
  if (isset($_REQUEST['settheme'])) {
    set_theme(@$_REQUEST['theme'],@$_REQUEST['persistent']);
  } else if (@$_SESSION['theme']) {
    set_theme($_SESSION['theme']); 
  } else if (@$_COOKIE['theme']) {
    set_theme($_COOKIE['theme'], true);
  }
}

function set_theme($theme, $persistent=false) {
  global $TEMPLATE, $SITEURL;
  if (!$theme || !file_exists(GSTHEMESPATH .$theme)) {
    if (@$_SESSION['theme']) unset($_SESSION['theme']);
    if (@$_COOKIE['theme']) $expires = time() - 24*60*60;
  } else {
    $TEMPLATE = $theme;
    @session_start();
    $_SESSION['theme'] = $theme;
    if ($persistent) $expires = time() + THEME_SWITCHER_COOKIE_TIME; 
    else if (@$_COOKIE['theme']) $expires = time() - 24*60*60;
  }
  if (@$expires) {
    if (preg_match('@^\w+://([^/]+)(/.*)$@', $SITEURL, $match)) {
      setcookie('theme', $theme, $expires, $match[2], $match[1]);
    } else {
      setcookie('theme', $theme, $expires, '/');
    }  
  }
}