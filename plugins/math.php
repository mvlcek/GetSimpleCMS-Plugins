<?php
/**
  Displays mathematical formulas 
  Just enter the formulas in LaTeX notation between $$ and $$.
*/

$math_mode = 2; // 1 = mathTeX (image), 2 = MathJax (javascript), 3 = MathJax with fallback mathTeX (requires jQuery)

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'Math', 	
	'0.3', 		
	'Martin Vlcek',
	'http://mvlcek.bplaced.net', 
	'Displays mathematical formulas',
	'',
	''  
);

# activate filter
add_filter('content','math_replace');
add_action('theme-header','math_theme_header');

function math_theme_header() {
  global $math_mode, $content;
  if (strpos($content, '$$') === false) return;
  if ($math_mode == 2 || $math_mode == 3) {
?>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS_HTML"></script>
<?php
  }
  if ($math_mode == 3) {
?>
  <script type="text/javascript">
    $(function() { $('span.math').show(); });
  </script>
  <style type="text/css">
    noscript.math {
      display: block;
      text-align: center;
    }
  </style>
<?php
  }
}

function math_replace($content) {
  global $math_mode;
  if ($math_mode == 1 || $math_mode == 3) {
    return preg_replace_callback('/\$\$\s*(.*?)\s*\$\$/','math_replace_match',$content);
  } else {
    return $content;
  }
}

function math_replace_match($match) {
  global $math_mode;
  $formula = html_entity_decode($match[1],ENT_COMPAT,'UTF-8');
  $image = '<img src="http://latex.codecogs.com/gif.latex?'.rawurlencode($formula).'" />';
  if ($math_mode == 1) {
    return $image;
  } else if ($math_mode == 3) {
    return '<span class="math" style="display:none">$$ '.$match[1].' $$</span><noscript class="math">'.$image.'</noscript>';
  }
}

