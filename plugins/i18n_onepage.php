<?php
/**
Helper functions for combining multiple page.
  
Public functions:
  set_onepage_content($slug=null) 
    sets the content of the page to all pages in the menu.
    With the I18N plugin you can specify a $slug to specify all child pages of this page.
  next_onepage_content() 
    prepares the data for the next page and returns false, if there are no more pages.
    use standard function like get_page_content() and get_page_title() to output the page.
  is_first_onepage_content(), is_last_onepage_content()
    returns true, if currently processing the first/last page
  get_onepage_anchor()
    will output an anchor for the current page
  get_onepage_navigation() 
    will output a navigation containing all pages specified with set_onepage_content
    
Basic usage:
  At the beginning of the template (before get_header, etc.):
    <?php set_onepage_content(); ?>
  For the navigation:
    <?php get_onepage_navigation(); ?>
  In the content part e.g.:
    <?php while (next_onepage_content()) { ?>
      <h2><?php get_onepage_anchor(); ?><?php get_page_title(); ?></h2>
      <div><?php get_page_content(); ?></div>
    <?php } ?>
    
The plugin support I18N Custom Fields and I18N Special Pages (with the exception of the page header custom code)
*/

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'I18n OnePage', 	
	'0.9', 		
	'Martin Vlcek',
	'http://mvlcek.bplaced.net', 
	'Helper functions for combining multiple pages into one page, e.g. for single-page themes',
	'',
	''  
);

$onepage_index = 0;
$onepage_datas = array();

function set_onepage_content($slug=null, $menuOnly=true) {
	global $content, $onepage_datas;
	$slugs = array();
	if (function_exists('return_i18n_page_structure')) {
		$pages = return_i18n_page_structure($slug, $menuOnly);
		foreach ($pages as $page) {
			$slugs[] = $page['url'];
		}		
	} else {
		$pages = menu_data();
		foreach ($pages as $page) {
			$slugs[] = $page['slug'];
		}
	}
	$content = '';
	foreach ($slugs as $slug) {
		if (function_exists('return_i18n_page_data')) {
			$data = return_i18n_page_data($slug);
		} else {
			$data = getXML(GSDATAPAGESPATH . $slug .'.xml');
		}
		$content .= (string) $data->content;
		$onepage_datas[] = $data;
	}
}

function next_onepage_content() {
	global $onepage_datas, $onepage_index;
  global $data_index, $url, $title, $date, $metak, $metad, $content, $parent;
	if ($onepage_index < count($onepage_datas)) {
		$data = $onepage_datas[$onepage_index];
		$data_index = $data;
		$url = $data->url;
		$title = $data->title;
		$date = $data->pubDate;
		$content = $data->content;
		// $parent stays the same
		$metak = $data->meta;
		$metad = $data->metad;
		// $template stays the same
		// $private stays the same
		$onepage_index++;
		if (function_exists('i18n_get_custom_fields_from')) {
			i18n_get_custom_fields_from($data_index);
		}
		if (function_exists('i18n_specialpages_init')) {
			i18n_specialpages_init();
		}
		return true;
	}
	return false;
}

function is_first_onepage_content() {
	global $onepage_index;
	return $onepage_index <= 1;
}

function is_last_onepage_content() {
	global $onepage_index, $onepage_datas;
	return $onepage_index >= count($onepage_datas);
}

function get_onepage_anchor() {
	global $url;
	echo '<a name="'.htmlspecialchars($url).'"></a>';
}

function get_onepage_navigation($show_titles=false) {
	global $onepage_datas;
	$html = '';
	foreach ($onepage_datas as $data) {
		$url = htmlspecialchars((string) $data->url);
    $text = (string) $data->menu ? (string) $data->menu : (string) $data->title;
    $title = (string) $data->title ? (string) $data->title : (string) $data->menu;
    if ($show_titles) {
			$html .= '<li class="' . $url . '"><a href="#' . $url . '" title="' . htmlspecialchars(html_entity_decode($title, ENT_QUOTES, 'UTF-8')) . '">' . $text . '</a></li>' . "\n";
    } else {
    	$html .= '<li class="' . $url . '"><a href="#' . $url . '">' . $text . '</a></li>' . "\n";
    }
	}
	echo exec_filter('menuitems', $html);
}
