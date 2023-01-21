<?php

function i18n_customfields_customize_ckeditor($editorvar) { // copied and modified from ckeditor_add_page_link()
	echo "
	// modify existing Link dialog
	CKEDITOR.on( 'dialogDefinition', function( ev )	{
		if ((ev.editor != " . $editorvar . ") || (ev.data.name != 'link')) return;

		// Overrides definition.
		var definition = ev.data.definition;
		definition.onFocus = CKEDITOR.tools.override(definition.onFocus, function(original) {
			return function() {
				original.call(this);
					if (this.getValueOf('info', 'linkType') == 'localPage') {
						this.getContentElement('info', 'localPage_path').select();
					}
			};
		});

		// Overrides linkType definition.
		var infoTab = definition.getContents('info');
		var content = getById(infoTab.elements, 'linkType');

		content.items.unshift(['Link to local page', 'localPage']);
		content['default'] = 'localPage';
		infoTab.elements.push({
			type: 'vbox',
			id: 'localPageOptions',
			children: [{
				type: 'select',
				id: 'localPage_path',
				label: 'Select page:',
				required: true,
				items: " . list_pages_json() . ",
				setup: function(data) {
					if ( data.localPage )
						this.setValue( data.localPage );
				}
			}]
		});
		content.onChange = CKEDITOR.tools.override(content.onChange, function(original) {
			return function() {
				original.call(this);
				var dialog = this.getDialog();
				var element = dialog.getContentElement('info', 'localPageOptions').getElement().getParent().getParent();
				if (this.getValue() == 'localPage') {
					element.show();
					if (" . $editorvar . ".config.linkShowTargetTab) {
						dialog.showPage('target');
					}
					var uploadTab = dialog.definition.getContents('upload');
					if (uploadTab && !uploadTab.hidden) {
						dialog.hidePage('upload');
					}
				}
				else {
					element.hide();
				}
			};
		});
		content.setup = function(data) {
			if (!data.type || (data.type == 'url') && !data.url) {
				data.type = 'localPage';
			}
			else if (data.url && !data.url.protocol && data.url.url) {
				if (path) {
					data.type = 'localPage';
					data.localPage_path = path;
					delete data.url;
				}
			}
			this.setValue(data.type);
		};
		content.commit = function(data) {
			data.type = this.getValue();
			if (data.type == 'localPage') {
				data.type = 'url';
				var dialog = this.getDialog();
				dialog.setValueOf('info', 'protocol', '');
				dialog.setValueOf('info', 'url', dialog.getValueOf('info', 'localPage_path'));
			}
		};
  });";
}

  global $SITEURL,$TEMPLATE;
	global $data_edit; // SimpleXML to read from
  $isV3 = substr(i18n_customfields_gsversion(),0,1) == '3';
  $isI18N = function_exists('return_i18n_languages');
  $creDate = @$data_edit->creDate ? (string) $data_edit->creDate : (string) @$data_edit->pubDate;
  $defs = i18n_customfield_defs();
  if (!$defs || count($defs) <= 0) {
    if ($isV3) {
      echo '<input type="hidden" name="creDate" value="'.$creDate.'"/>';
    } else {
      echo '<tr style="border:0 none;margin:0;padding:0;"><td colspan="2" style="border:0 none;margin:0;padding:0;"><input type="hidden" name="creDate" value="'.$creDate.'"/></td></tr>';
    }
    return;
  } 
	$id = @$_GET['id'];
  if ($isV3) echo '<table class="formtable" style="clear:both;"><tbody>';
	echo '<tr><td><h2>Custom Fields</h2><input type="hidden" name="creDate" value="'.$creDate.'"/></td></tr>';
  // Editor settings (copied from edit.php)
  if (defined('GSEDITORLANG')) { $EDLANG = GSEDITORLANG; } else {	$EDLANG = $isV3 ? i18n_r('CKEDITOR_LANG') : 'en'; }
  if (defined('GSEDITORTOOL')) { $EDTOOL = GSEDITORTOOL; } else {	$EDTOOL = 'basic'; }
  if (defined('GSEDITOROPTIONS') && trim(GSEDITOROPTIONS)!="") { $EDOPTIONS = ", ".GSEDITOROPTIONS; } else {	$EDOPTIONS = ''; }
  if ($EDTOOL == 'advanced') {
    $toolbar = "
		    ['Bold', 'Italic', 'Underline', 'NumberedList', 'BulletedList', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Table', 'TextColor', 'BGColor', 'Link', 'Unlink', 'Image', 'RemoveFormat', 'Source'],
        '/',
        ['Styles','Format','Font','FontSize']
    ";
  } elseif ($EDTOOL == 'basic') {
    $toolbar = "['Bold', 'Italic', 'Underline', 'NumberedList', 'BulletedList', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Link', 'Unlink', 'Image', 'RemoveFormat', 'Source']";
  } else {
    $toolbar = GSEDITORTOOL;
  }
  // Editor settings end
  $col = 0; $i = 0;
  foreach ($defs as $def) {
    $i++;
    $key = strtolower($def['key']);
    $label = $def['label'];
		$type = $def['type'];
    $value = htmlspecialchars($id ? (isset($data_edit->$key) ? $data_edit->$key : '') : (isset($def['value']) ? $def['value'] : ''), ENT_QUOTES);
		if ($col == 0) {
      echo '<tr>';
    } else if ($type == 'textfull' || $type == 'textarea' || $type == 'image' || $type == 'link') {
      echo '<td></td></tr><tr>';
    }
		switch ($type){
			case 'textfull': // draw a full width TextBox
				echo '<td colspan="2"><b>'.$label.':</b><br />';
				echo '<input class="text" type="text" style="width:513px;" id="post-'.$key.'" name="post-'.$key.'" value="'.$value.'" /></td>'; 
        $col += 2;
			  break; 
			case 'dropdown':
				echo '<td><b>'.$label.':</b><br />';
				echo '<select id="post-'.$key.'" name="post-'.$key.'" class="text">';
        foreach ($def['options'] as $option) {
          $attrs = $value == $option ? ' selected="selected"' : '';
          echo '<option'.$attrs.'>'.$option.'</option>';
        }
        echo '</select></td>';
        $col++;
				break;
      case 'checkbox':
				echo '<td><b>'.$label.'?</b> &nbsp;&nbsp;&nbsp;';
        echo '<input type="checkbox" id="post-'.$key.'" name="post-'.$key.'" value="on" '.($value ? 'checked="checked"' : '').'/></td>'; 
        $col++;
  			break; 
      case "textarea":
        echo '<td colspan="2"><b>'.$label.':</b><br />';
        echo '<textarea id="post-'.$key.'" name="post-'.$key.'" style="width:513px;height:200px;border: 1px solid #AAAAAA;">'.$value.'</textarea></td>';
?>
<script type="text/javascript">
  // missing border around text area, too much padding on left side, ...
  $(function() {
    var editor_<?php echo $i; ?> = CKEDITOR.replace( 'post-<?php echo $key; ?>', {
	        skin : 'getsimple',
	        forcePasteAsPlainText : true,
	        language : '<?php echo $EDLANG; ?>',
	        defaultLanguage : 'en',
<?php if ($isV3 && file_exists(GSTHEMESPATH .$TEMPLATE."/editor.css")) { 
	$fullpath = suggest_site_path();
?>
            contentsCss: '<?php echo $fullpath; ?>theme/<?php echo $TEMPLATE; ?>/editor.css',
<?php } ?>
	        entities : true,
	        uiColor : '#FFFFFF',
			    height: '200px',
			    baseHref : '<?php echo $SITEURL; ?>',
	        toolbar : [ <?php echo $toolbar; ?> ]
			    <?php echo $EDOPTIONS; ?>
<?php if ($isV3) { ?>
          ,
					tabSpaces:10,
	        filebrowserBrowseUrl : 'filebrowser.php?type=all',
					filebrowserImageBrowseUrl : 'filebrowser.php?type=images',
	        filebrowserWindowWidth : '730',
	        filebrowserWindowHeight : '500'
<?php } ?>
    });
    <?php if ($isV3) i18n_customfields_customize_ckeditor('editor_'.$i); ?>
  });
</script>
<?php
        $col +=2;
        break;
      case 'link':
        $w = $isV3 ? 500 : 513;
				echo '<td colspan="2"><b>'.$label.':</b><br />';
				echo '<input class="text" type="text" style="width:'.$w.'px;" id="post-'.$key.'" name="post-'.$key.'" value="'.$value.'" />';
        if ($isV3) echo ' <span class="edit-nav"><a id="browse-'.$key.'" href="#">'.i18n_r('i18n_customfields/BROWSE_PAGES').'</a></span>';
        echo '</td>'; 
        $col++;
?>
<script type="text/javascript">
  function fill_<?php echo $i; ?>(url) {
    $('#post-<?php echo $key; ?>').val(url);
  }
  $(function() { 
    $('#browse-<?php echo $key; ?>').click(function(e) {
      window.open('<?php echo $SITEURL; ?>plugins/i18n_customfields/browser/pagebrowser.php?func=fill_<?php echo $i; ?>&i18n=<?php echo $isI18N; ?>', 'browser', 'width=800,height=500,left=100,top=100,scrollbars=yes');
    });
  });
</script>
<?php
  			break; 
      case 'image':
        $w = $isV3 ? 500 : 513;
				echo '<td colspan="2"><b>'.$label.':</b><br />';
				echo '<input class="text" type="text" style="width:'.$w.'px;" id="post-'.$key.'" name="post-'.$key.'" value="'.$value.'" />';
        if ($isV3) echo ' <span class="edit-nav"><a id="browse-'.$key.'" href="#">'.i18n_r('i18n_customfields/BROWSE_IMAGES').'</a></span>';
        echo '</td>'; 
        $col++;
?>
<script type="text/javascript">
  function fill_<?php echo $i; ?>(url) {
    $('#post-<?php echo $key; ?>').val(url);
  }
  $(function() { 
    $('#browse-<?php echo $key; ?>').click(function(e) {
      window.open('<?php echo $SITEURL; ?>plugins/i18n_customfields/browser/filebrowser.php?func=fill_<?php echo $i; ?>&type=images', 'browser', 'width=800,height=500,left=100,top=100,scrollbars=yes');
    });
  });
</script>
<?php
  			break; 
			case 'text':
      default:
				echo '<td><b>'.$label.':</b><br />';
				echo '<input class="text short" type="text" style="width:295px;" id="post-'.$key.'" name="post-'.$key.'" value="'.$value.'" /></td>'; 
        $col++;
  			break; 
		}
		if ($col >= 2) {
      echo "</tr>";
      $col = 0;
    }
	}		
  if ($col == 1) echo '<td></td></tr>';
  if ($isV3) echo "</tbody></table>";

