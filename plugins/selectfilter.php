<?php
/**
  Allows the filtering of selects in browsers (except Chrome) 
*/

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'SelectFilter', 	
	'0.1', 		
	'Martin Vlcek',
	'http://mvlcek.bplaced.net', 
	'Filtering of selects with many entries',
	'',
	''  
);

add_action('edit-extras', 'selectfilter_editextras');

function selectfilter_editextras() {
	$minOptions = defined('SELECTFILTER_MIN') ? SELECTFILTER_MIN : 10;
	$exclude = defined('SELECTFILTER_EXCLUDE') ? SELECTFILTER_EXCLUDE : '#post-private, #post-template';
?>
<script type="text/javascript">
  // <![CDATA[
  (function() {
	  function filterSelect(selector) {
		  var options = $(selector).children('option');
		  var text = '';
		  function reset() {
			  if (text != '') {
				  text = '';
				  var value = $(selector).val();
					$(selector).children('option').detach();
					$(selector).append(options);
					$(selector).prop('value', value).change();
			  }
			}
		  function onKeypress(e) {
				if (e.which >= 32 && String.fromCharCode(e.which) != "'" && $(selector).children('option').length > 0) {
					text = (text + String.fromCharCode(e.which)).toLowerCase();
					var value = $(selector).val();
					var valueFound = false;
					$(selector).children('option').filter(function(i, e) {
						var remove = $(e).text().toLowerCase().indexOf(text) < 0;
						if (!remove && $(e).val() == value) valueFound = true;
						return remove;
					}).detach();
					var opt1value = $(selector).children('option:eq(0)').val();
					$(selector).prop('value', valueFound ? value : opt1value).change();
					e.preventDefault();
				}
			}
			function onKeydown(e) {
				if (e.which == 8 && text.length > 0) {
					text = text.substring(0, text.length-1);
					var value = $(selector).val();
					$(selector).children('option').detach();
					$(selector).append(options.filter(function(i,e) {
						return i == 0 || $(e).text().toLowerCase().indexOf(text) >= 0;
					}));
					$(selector).prop('value', value).change();
					e.preventDefault();
				} else if (e.which == 27) {
					reset();
					e.preventDefault();
				}
			}
			function onBlur() {
				reset();
				$(selector).off('keypress', onKeypress);
				$(selector).off('keydown', onKeydown);
				$(selector).off('blur', onBlur);
			}
			$(selector).on('keypress', onKeypress);
			$(selector).on('keydown', onKeydown);
			$(selector).on('blur', onBlur);
		}
		$(document).on('focus', 'select', function(e) {
			if ($(e.target).filter(':not(' + <?php echo json_encode($exclude); ?> + ')').length > 0) {
				if ($(e.target).children('option').length > <?php echo json_encode($minOptions); ?>) {
					filterSelect(e.target);
				}
			}
		});
  })();
	// ]]>
</script>
<?php
}

