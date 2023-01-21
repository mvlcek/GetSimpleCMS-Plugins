<?php
/*
Plugin Name: FAQ
Description: Display FAQs
Version: 1.3
Author: Martin Vlcek
Author URI: http://mvlcek.bplaced.net

To use, create a page, enter your questions as headers (h1, h2, h3, ...) and your answers as anything else and add a tag _faq
(or _faq autoclose, if the sibling questions should be automatically closed, when a question is opened).
You have to include jquery 1.4+ in your template.
To customize, add custom rules to your style sheet (the faq is wrapped in css class faq-wrapper) and/or upload two images
faq_open.jpg and faq_closed.jpg to your data/uploads directory
*/

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'FAQ', 	
	'1.3', 		
	'Martin Vlcek',
	'http://mvlcek.bplaced.net', 
	'Display frequently asked questions (FAQs)',
	'',
	''  
);

# activate filter
add_action('theme-header','faq_header');
add_filter('content','faq_content');

$faq_parameters = false;

function faq_header() {
  global $metak, $faq_parameters, $SITEURL;
  // check tags
  $tags = preg_split("/\s*,\s*/", $metak);
  foreach ($tags as $tag) {
    if ($tag == '_faq' || substr($tag,0,5) == '_faq ') { 
      $faq_parameters = preg_split('/\s+/', strtolower(substr($tag,5))); 
      break; 
    }
  }
  if ($faq_parameters === false) return;
  $img_open = file_exists(GSDATAUPLOADPATH.'faq_open.jpg') ? 'data/uploads/faq_open.jpg' : 'plugins/faq/images/faq_open.jpg';
  $img_closed = file_exists(GSDATAUPLOADPATH.'faq_closed.jpg') ? 'data/uploads/faq_closed.jpg' : 'plugins/faq/images/faq_closed.jpg';
?>
  <style type="text/css">
    .faq-wrapper, .faq-wrapper .faq-container {
      margin: 0;
      padding: 0;
      border: 0 none;
    }
    .faq-wrapper .faq-question {
      padding-left: 25px;
      cursor: pointer;
    }
    .faq-wrapper .faq-question.closed {
      background: url(<?php echo $SITEURL.$img_closed; ?>) no-repeat left center;
    }
    .faq-wrapper .faq-question.open {
      background: url(<?php echo $SITEURL.$img_open; ?>) no-repeat left center;
    }
    .faq-wrapper .faq-question + * {
      margin-left: 25px;
    }
  </style>
  <script type="text/javascript">
    var faq_autoclose = <?php echo in_array('autoclose',$faq_parameters) ? 'true' : 'false'; ?>;
    function processHeaders(which, until) {
      $(which).each(function(i,h) {
        var $answer = $(h).nextUntil(until);
        if ($answer.length > 1) {
          $(h).after($('<div/>').addClass('faq-container').addClass('faq-answer').append($answer.detach()));
        } else if ($answer.length == 1) {
          $answer.addClass('faq-answer');
        } else {
          $(h).removeClass('faq-question');
        }
      });
    }
    $(function() {
      $('.faq-wrapper h1, .faq-wrapper h2, .faq-wrapper h3, .faq-wrapper h4, .faq-wrapper h5, .faq-wrapper h6').addClass('faq-question').addClass('closed');
      processHeaders('.faq-wrapper h1','h1, .nofaq');
      processHeaders('.faq-wrapper h2','h1, h2, .nofaq');
      processHeaders('.faq-wrapper h3','h1, h2, h3, .nofaq');
      processHeaders('.faq-wrapper h4','h1, h2, h3, h4, .nofaq');
      processHeaders('.faq-wrapper h5','h1, h2, h3, h4, h5, .nofaq');
      processHeaders('.faq-wrapper h6','h1, h2, h3, h4, h5, h6, .nofaq');
      $('.faq-answer').hide();
      // open all topics containing marked text (normally marked by I18N Search)
      $('.faq-wrapper span.mark').parents('h1, h2, h3, h4, h5, h6').removeClass('closed').addClass('open').next().show();
      $('.faq-wrapper span.mark').parents('.faq-answer').show().prev().removeClass('closed').addClass('open');
      $('.faq-question').click(function(e) {
        if (faq_autoclose && $(e.target).hasClass('closed')) {
          $(e.target).siblings('.faq-question.open').removeClass('open').addClass('closed').next().slideToggle();
        }
        $(e.target).toggleClass('open').toggleClass('closed').next().slideToggle();
      });
    });
  </script>
<?php
}

function faq_content($content) {
  global $faq_parameters;
  if ($faq_parameters !== false) {
    return '<div class="faq-wrapper">'.$content.'</div>';
  } else {
    return $content;
  }
}

