<?php
/**
 * @version 1.0 $Id$
 * @package Joomla
 * @subpackage FLEXIcontent
 * @subpackage plugin.weblink
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.event.plugin');

class plgFlexicontent_fieldsWeblink extends JPlugin
{
	static $field_types = array('weblink');
	
	// ***********
	// CONSTRUCTOR
	// ***********
	
	function plgFlexicontent_fieldsWeblink( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		JPlugin::loadLanguage('plg_flexicontent_fields_weblink', JPATH_ADMINISTRATOR);
	}
	
	
	
	// *******************************************
	// DISPLAY methods, item form & frontend views
	// *******************************************
	
	// Method to create field's HTML display for item form
	function onDisplayField(&$field, &$item)
	{
		// execute the code only if the field type match the plugin type
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		$field->label = JText::_($field->label);
		$use_ingroup = $field->parameters->get('use_ingroup', 0);
		if ($use_ingroup) $field->formhidden = 3;
		if ($use_ingroup && empty($field->ingroup)) return;
		
		// initialize framework objects and other variables
		$document = JFactory::getDocument();
		
		
		// ****************
		// Number of values
		// ****************
		$multiple   = $use_ingroup || $field->parameters->get( 'allow_multiple', 0 ) ;
		$max_values = $use_ingroup ? 0 : (int) $field->parameters->get( 'max_values', 0 ) ;
		$required   = $field->parameters->get( 'required', 0 ) ;
		$required   = $required ? ' required' : '';
		
		
		// ***
		// URL
		// ***
		
		// Default value
		$link_usage   = $field->parameters->get( 'link_usage', 0 ) ;
		$default_link = ($item->version == 0 || $link_usage > 0) ? $field->parameters->get( 'default_value_link', '' ) : '';
		$default_link = $default_link ? JText::_($default_link) : '';
		
		
		// ***********************************
		// URL title & linking text (optional)
		// ***********************************
		
		// Default value
		$title_usage   = $field->parameters->get( 'title_usage', 0 ) ;
		$default_title = ($item->version == 0 || $title_usage > 0) ? JText::_($field->parameters->get( 'default_value_title', '' )) : '';
		$default_title = $default_title ? JText::_($default_title) : '';
		$usetitle      = $field->parameters->get( 'use_title', 0 ) ;
		
		// Input field display size & max characters
		$size       = (int) $field->parameters->get( 'size', 30 ) ;
		
		// Initialise property with default value
		if ( !$field->value ) {
			$field->value = array();
			$field->value[0]['link']  = $default_link;
			$field->value[0]['title'] = $default_title;
			$field->value[0]['hits']  = 0;
			$field->value[0] = serialize($field->value[0]);
		}
		
		// Field name and HTML TAG id
		$fieldname = 'custom['.$field->name.']';
		$elementid = 'custom_'.$field->name;
		
		$js = "";
		$css = "";
		
		if ($multiple) // handle multiple records
		{
			// Add the drag and drop sorting feature
			if (!$use_ingroup) $js .="
			jQuery(document).ready(function(){
				jQuery('#sortables_".$field->id."').sortable({
					handle: '.fcfield-drag',
					containment: 'parent',
					tolerance: 'pointer'
				});
			});
			";
			
			if ($max_values) FLEXI_J16GE ? JText::script("FLEXI_FIELD_MAX_ALLOWED_VALUES_REACHED", true) : fcjsJText::script("FLEXI_FIELD_MAX_ALLOWED_VALUES_REACHED", true);
			$js .= "
			var uniqueRowNum".$field->id."	= ".count($field->value).";  // Unique row number incremented only
			var rowCount".$field->id."	= ".count($field->value).";      // Counts existing rows to be able to limit a max number of values
			var maxValues".$field->id." = ".$max_values.";
			
			function addField".$field->id."(el, groupval_box, fieldval_box, params)
			{
				remove_previous = (typeof params!== 'undefined' && typeof params.remove_previous !== 'undefined') ? params.remove_previous : 0;
				scroll_visible  = (typeof params!== 'undefined' && typeof params.scroll_visible  !== 'undefined') ? params.scroll_visible  : 1;
				animate_visible = (typeof params!== 'undefined' && typeof params.animate_visible !== 'undefined') ? params.animate_visible : 1;
				
				if((rowCount".$field->id." >= maxValues".$field->id.") && (maxValues".$field->id." != 0)) {
					alert(Joomla.JText._('FLEXI_FIELD_MAX_ALLOWED_VALUES_REACHED') + maxValues".$field->id.");
					return 'cancel';
				}
				
				var lastField = fieldval_box ? fieldval_box : jQuery(el).prev().children().last();
				var newField  = lastField.clone();
				
				theInput = newField.find('input.urllink').first();
				theInput.val('".$default_link."');
				theInput.attr('name','".$fieldname."['+uniqueRowNum".$field->id."+'][link]');
				theInput.attr('id','".$elementid."_'+uniqueRowNum".$field->id.");
				";
				
			if ($usetitle) $js .= "
				theInput = newField.find('input.urltitle').first();
				theInput.val('".$default_title."');
				theInput.attr('name','".$fieldname."['+uniqueRowNum".$field->id."+'][title]');
				";
				
				
			$js .= "
				theInput = newField.find('input.urlhits').first();
				theInput.val('0');
				theInput.attr('name','".$fieldname."['+uniqueRowNum".$field->id."+'][hits]');
				
				// Set hits to zero for new row value
				newField.find('span span').html('0');
				newField.css('display', 'none');
				";
			
			// Add new field to DOM
			$js .= "
				newField.insertAfter( lastField );
				if (remove_previous) lastField.remove();
			";
			
			
			// Add new element to sortable objects (if field not in group)
			if (!$use_ingroup) $js .= "
				jQuery('#sortables_".$field->id."').sortable({
					handle: '.fcfield-drag',
					containment: 'parent',
					tolerance: 'pointer'
				});
			";
			
			// Show new field, increment counters
			$js .="
				//newField.fadeOut({ duration: 400, easing: 'swing' }).fadeIn({ duration: 200, easing: 'swing' });
				if (scroll_visible) fc_scrollIntoView(newField, 1);
				if (animate_visible) newField.css({opacity: 0.1}).animate({ opacity: 1 }, 800);
				
				// Enable tooltips on new element
				".( FLEXI_J30GE ? "
					newField.find('.hasTooltip').tooltip({'html': true,'container': newField});
				" : "
					var tipped_elements = newField.find('.hasTip');
					tipped_elements.each(function() {
						var title = this.get('title');
						if (title) {
							var parts = title.split('::', 2);
							this.store('tip:title', parts[0]);
							this.store('tip:text', parts[1]);
						}
					});
					var ajax_JTooltips = new Tips($(#sortables_".$field->id."').getNext().getElements('.hasTip'), { maxTitleChars: 50, fixed: false});
				")."
				
				rowCount".$field->id."++;       // incremented / decremented
				uniqueRowNum".$field->id."++;   // incremented only
			}

			function deleteField".$field->id."(el, groupval_box, fieldval_box)
			{
				// Find field value container
				var row = fieldval_box ? fieldval_box : jQuery(el).closest('li');
				
				// Add empty container if last element, instantly removing the given field value container
				if(rowCount".$field->id." == 1)
					addField".$field->id."(null, groupval_box, fieldval_box, {remove_previous: 1, scroll_visible: 0, animate_visible: 0});
				
				// Remove if not last one, if it is last one, we issued a replace (copy,empty new,delete old) above
				if(rowCount".$field->id." > 1) {
					// Destroy the remove button, so that it is not reclicked again, while we do the hide effect (before DOM removal)
					if (el) jQuery(el).remove();
					// Do hide effect then remove from DOM
					row.slideUp(400, function(){ this.remove(); });
					rowCount".$field->id."--;
				}
			}
			";
			
			$css .= '
			#sortables_'.$field->id.' li:only-child span.fcfield-drag, #sortables_'.$field->id.' li:only-child input.fcfield-button { display:none; }
			';
			
			$remove_button = '<input class="fcfield-button" type="button" value="'.JText::_( 'FLEXI_REMOVE_VALUE' ).'" onclick="deleteField'.$field->id.'(this);" />';
			$move2 	= '<span class="fcfield-drag">'.JHTML::image( JURI::base().'components/com_flexicontent/assets/images/move2.png', JText::_( 'FLEXI_CLICK_TO_DRAG' ) ) .'</span>';
		} else {
			$remove_button = '';
			$move2 = '';
			$js .= '';
			$css .= '';
		}
		
		if ($js)  $document->addScriptDeclaration($js);
		if ($css) $document->addStyleDeclaration($css);
		
		$field->html = array();
		$n = 0;
		//if ($use_ingroup) {print_r($field->value);}
		foreach ($field->value as $value)
		{
			// Compatibility for unserialized values or for NULL values in a field group
			$v = !empty($value) ? @unserialize($value) : false;
			if ( $v !== false || $v === 'b:0;' ) {
				$value = $v;
			} else {
				$value = array('link' => $value, 'title' => '', 'hits'=>0);
			}
			if ( empty($value['link']) && !$use_ingroup && $n) continue;  // If at least one added, skip empty if not in field group
			
			$fieldname_n = $fieldname.'['.$n.']';
			$elementid_n = $elementid.'_'.$n;
			
			$value['link'] = !empty($value['link']) ? $value['link'] : $default_link;
			$value['link'] = htmlspecialchars(
				(FLEXI_J30GE ? JStringPunycode::urlToUTF8($value['link']) : $value['link']),
				ENT_COMPAT, 'UTF-8'
			);
			$link = '
				<div class="nowrap_box">
					<label class="label">'.JText::_( 'FLEXI_FIELD_URL' ).'</label>
					<input class="urllink fcfield_textval '.$required.'" name="'.$fieldname_n.'[link]" id="'.$elementid_n.'" type="text" size="'.$size.'" value="'.$value['link'].'" />
				</div>';
			
			$title = '';
			if ($usetitle) {
				$value['title'] = !empty($value['title']) ? $value['title'] : $default_title;
				$value['title'] = htmlspecialchars($value['title'], ENT_COMPAT, 'UTF-8');
				$title = '
				<div class="nowrap_box">
					<label class="label">'.JText::_( 'FLEXI_FIELD_URLTITLE' ).'</label>
					<input class="urltitle fcfield_textval" name="'.$fieldname_n.'[title]" type="text" size="'.$size.'" value="'.$value['title'].'" />
				</div>';
			}
			
			$hits = (int) @ $value['hits'];
			$hits = '
				<div class="nowrap_box">
					<label class="label hits">'.JText::_( 'FLEXI_FIELD_HITS' ).'</label>
					<span class="hitcount">'.$hits.'</span> 
					<input class="urlhits" name="'.$fieldname_n.'[hits]" type="hidden" value="'.$hits.'" />
				</div>';
			
			$field->html[] = '
				'.($use_ingroup ? '' : $move2).'
				'.($use_ingroup ? '' : $remove_button).'
				'.$link.'
				'.$title.'
				'.$hits.'
				';
			
			$n++;
			if (!$multiple) break;  // multiple values disabled, break out of the loop, not adding further values even if the exist
		}
		
		if ($use_ingroup) { // do not convert the array to string if field is in a group
		} else if ($multiple) { // handle multiple records
			$field->html =
				'<li class="fcfieldval_container valuebox fcfieldval_container_'.$field->id.'">'.
					implode('</li><li class="fcfieldval_container valuebox fcfieldval_container_'.$field->id.'">', $field->html).
				'</li>';
			$field->html = '<ul class="fcfield-sortables" id="sortables_'.$field->id.'">' .$field->html. '</ul>';
			$field->html .= '<input type="button" class="fcfield-addvalue" style="float:left; clear:both;" onclick="addField'.$field->id.'(this);" value=" -- '.JText::_( 'FLEXI_ADD_VALUE' ).' -- " />';
		} else {  // handle single values
			$field->html = '<div class="fcfieldval_container valuebox fcfieldval_container_'.$field->id.'">' . $field->html[0] .'</div>';
		}
	}
	
	
	// Method to create field's HTML display for frontend views
	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		// execute the code only if the field type match the plugin type
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		// Get isMobile / isTablet Flags
		static $isMobile = null;
		static $isTablet = null;
		static $useMobile = null;
		if ($useMobile===null) 
		{
			$cparams = JComponentHelper::getParams( 'com_flexicontent' );
			$force_desktop_layout = $cparams->get('force_desktop_layout', 0 );
			//$start_microtime = microtime(true);
			$mobileDetector = flexicontent_html::getMobileDetector();
			$isMobile = $mobileDetector->isMobile();
			$isTablet = $mobileDetector->isTablet();
			$useMobile = $force_desktop_layout  ?  $isMobile && !$isTablet  :  $isMobile;
			//$time_passed = round(1000000 * 10 * (microtime(true) - $start_microtime)) / 10;
			//printf('<br/>-- [Detect Mobile: %.3f s] ', $time_passed/1000000);
		}
		
		$field->label = JText::_($field->label);
		
		// Some variables
		$use_ingroup = $field->parameters->get('use_ingroup', 0);
		$add_enclosers = !$use_ingroup || $field->parameters->get('add_enclosers_ingroup', 0);
		$view = JRequest::getVar('flexi_callview', JRequest::getVar('view', FLEXI_ITEMVIEW));
		
		// Value handling parameters
		$lang_filter_values = 0;//$field->parameters->get( 'lang_filter_values', 1);
		$multiple = $use_ingroup || $field->parameters->get( 'allow_multiple', 0 ) ;
		
		// some parameter shortcuts
		$target = $field->parameters->get( 'targetblank', 0 );
		$target_param   = $target ? ' target="_blank"' : '';
		$display_hits = $field->parameters->get( 'display_hits', 0 ) ;
		$add_hits_img = $display_hits == 1 || $display_hits == 3;
		$add_hits_txt = $display_hits == 2 || $display_hits == 3 || $isMobile;
		$rel_nofollow = $field->parameters->get( 'add_rel_nofollow', 0 ) ? ' rel="nofollow"'    : '';
		
		// URL value
		$link_usage   = $field->parameters->get( 'link_usage', 0 ) ;
		$default_link = ($link_usage == 2) ? $field->parameters->get( 'default_value_link', '' ) : '';
		$default_link = $default_link ? JText::_($default_link) : '';
		
		// URL title & linking text (optional)
		$usetitle      = $field->parameters->get( 'use_title', 0 ) ;
		$title_usage   = $field->parameters->get( 'title_usage', 0 ) ;
		$default_title = ($title_usage == 2) ? JText::_($field->parameters->get( 'default_value_title', '' )) : '';
		$default_title = $default_title ? JText::_($default_title) : '';
		
		// Get field values
		$values = $values ? $values : $field->value;
		
		// Load default value
		if ( empty($values) ) {
			if (!strlen($default_link)) {
				$field->{$prop} = $use_ingroup ? array() : '';
				return;
			}
			$values = array();
			$values[0]['link']  = $default_link;
			$values[0]['title'] = $default_title;
			$values[0]['hits']  = 0;
			$values[0] = serialize($values[0]);
		}
		
		// (* BECAUSE OF THIS, the value display loop expects unserialized values)
		foreach ($values as &$value)
		{
			// Compatibility for unserialized values or for NULL values in a field group
			$v = !empty($value) ? @unserialize($value) : false;
			if ( $v !== false || $v === 'b:0;' ) {
				$value = $v;
			} else {
				$value = array('link' => $value, 'title' => '', 'hits'=>0);
			}
		}
		unset($value); // Unset this or you are looking for trouble !!!, because it is a reference and reusing it will overwrite the pointed variable !!!
		
		
		// Prefix - Suffix - Separator parameters, replacing other field values if found
		$remove_space = $field->parameters->get( 'remove_space', 0 ) ;
		$pretext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'pretext', '' ), 'pretext' );
		$posttext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'posttext', '' ), 'posttext' );
		$separatorf	= $field->parameters->get( 'separatorf', 1 ) ;
		$opentag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'opentag', '' ), 'opentag' );
		$closetag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'closetag', '' ), 'closetag' );
		
		if($pretext)  { $pretext  = $remove_space ? $pretext : $pretext . ' '; }
		if($posttext) { $posttext = $remove_space ? $posttext : ' ' . $posttext; }
		
		switch($separatorf)
		{
			case 0:
			$separatorf = '&nbsp;';
			break;

			case 1:
			$separatorf = '<br />';
			break;

			case 2:
			$separatorf = '&nbsp;|&nbsp;';
			break;

			case 3:
			$separatorf = ',&nbsp;';
			break;

			case 4:
			$separatorf = $closetag . $opentag;
			break;

			case 5:
			$separatorf = '';
			break;

			default:
			$separatorf = '&nbsp;';
			break;
		}
		
		// Optimization, do some stuff outside the loop
		static $hits_icon = null;
		if ($hits_icon===null && ($display_hits==1 || $display_hits==3)) {
			$_hits_tip = flexicontent_html::getToolTip(null, '%s '.JText::_( 'FLEXI_HITS', true ), 0, 0);
			$_attribs = $display_hits==1 ? 'class="'.(FLEXI_J30GE ? 'hasTooltip' : 'hasTip').'" title="'.$_hits_tip.'"' : '';
			$hits_icon = FLEXI_J16GE ?
				JHTML::image('components/com_flexicontent/assets/images/'.'user.png', JText::_( 'FLEXI_HITS' ), $_attribs) :
				JHTML::_('image.site', 'user.png', 'components/com_flexicontent/assets/images/', NULL, NULL, JText::_( 'FLEXI_HITS' ), $_attribs);
		}
		
		// initialise property
		$field->{$prop} = array();
		$n = 0;
		foreach ($values as $value)
		{
			if ( empty($value['link']) && !$use_ingroup ) continue;  // no link ...
			if ( empty($value['link']) ) {
				$field->{$prop}[$n++]	= '';
				continue;
			}
			
			// If not using property or property is empty, then use default property value
			// NOTE: default property values have been cleared, if (propertyname_usage != 2)
			$title    = ($usetitle && @$value['title']   )  ?  $value['title']    : $default_title;
			$linktext = '';  // no linktext for weblink for extended web link field if this is needed
			$hits     = (int) @ $value['hits'];
			
			$link_params  = $title ? ' title="'.$title.'"' : '';
			$link_params .= $target_param;
			$link_params .= $rel_nofollow;
			
			if ( $field->parameters->get( 'use_direct_link', 0 ) )
				// Direct access to the web-link, hits counting not possible
				$href = $value['link'];
			else 
				// Indirect access to the web-link, via calling FLEXIcontent component, thus counting hits too
				$href = JRoute::_( 'index.php?option=com_flexicontent&fid='. $field->id .'&cid='.$field->item_id.'&ord='.($n+1).'&task=weblink' );
			
			// Create indirect link to web-link address with custom displayed text
			if( empty($linktext) )
				$linktext = $title ? $title : $this->cleanurl(
					(FLEXI_J30GE ? JStringPunycode::urlToUTF8($value['link']) : $value['link'])    // If using URL convert from Punycode to UTF8
				);
			$html = '<a href="' .$href. '" '.$link_params.'>' .$linktext. '</a>';
			
			// HITS: either as icon or as inline text or both
			$hits_html = '';
			if ($display_hits && $hits)
			{
				$hits_html = '<span class="fcweblink_hits">';
				if ( $add_hits_img && @ $hits_icon ) {
					$hits_html .= sprintf($hits_icon, $hits);
				}
				if ( $add_hits_txt ) {
					$hits_html .= '('.$hits.'&nbsp;'.JTEXT::_('FLEXI_HITS').')';
				}
				$hits_html .= '</span>';
				if ($prop == 'display_hitsonly')
					$html = $hits_html;
				else
					$html .= ' '. $hits_html;
			}
			
			// Add prefix / suffix
			$field->{$prop}[$n]	= !$add_enclosers ? $html : $pretext . $html . $posttext;
			
			$n++;
			if (!$multiple) break;  // multiple values disabled, break out of the loop, not adding further values even if the exist
		}
		
		if (!$use_ingroup)  // do not convert the array to string if field is in a group
		{
			// Apply separator and open/close tags
			$field->{$prop} = implode($separatorf, $field->{$prop});
			if ( $field->{$prop}!=='' ) {
				$field->{$prop} = $opentag . $field->{$prop} . $closetag;
			} else {
				$field->{$prop} = '';
			}
		}
	}
	
	
	
	// **************************************************************
	// METHODS HANDLING before & after saving / deleting field events
	// **************************************************************
	
	// Method to handle field's values before they are saved into the DB
	function onBeforeSaveField( &$field, &$post, &$file, &$item )
	{
		// execute the code only if the field type match the plugin type
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		$use_ingroup = $field->parameters->get('use_ingroup', 0);
		if ( !is_array($post) && !strlen($post) && !$use_ingroup ) return;
		
		$is_importcsv = JRequest::getVar('task') == 'importcsv';
		$host = JURI::getInstance('SERVER')->gethost();
		
		
		// Make sure posted data is an array 
		$post = !is_array($post) ? array($post) : $post;
		
		// Reformat the posted data
		$newpost = array();
		$new = 0;
		foreach ($post as $n => $v)
		{
			// support for basic CSV import / export
			if ( $is_importcsv && !is_array($post[$n]) ) {
				if ( @unserialize($post[$n])!== false || $post[$n] === 'b:0;' ) {  // support for exported serialized data)
					$post[$n] = unserialize($post[$n]);
				} else {
					$post[$n] = array('link' => $post[$n], 'title' => '', 'id' => '', 'class' => '', 'linktext' => '', 'hits'=>0);
				}
			}
			
			
			// ***********************************************************
			// Validate URL, skipping URLs that are empty after validation
			// ***********************************************************
			
			$link = flexicontent_html::dataFilter($post[$n]['link'], 0, 'URL', 0);  // Clean bad text/html
			if ( empty($link) && !$use_ingroup ) continue;  // Skip empty values if not in field group
			
			// Sanitize the URL as absolute or relative
			// Has protocol nothing to do
			if ( parse_url($link, PHP_URL_SCHEME) ) $prefix = '';
			// Has current domain but no protocol just add http://
			else if (strpos($link, $host) === 0) $prefix = 'http://';
			// URLs are absolute, for relative URL support you need extended web link field
			else {
				if (substr($link, 0, 10) == '/index.php')  $link = substr($link, 1);
				$prefix = (substr($link, 0, 9) == 'index.php') ? JURI::root() : 'http://';
			}
			
			$newpost[$new] = array();
			$newpost[$new]['link'] = $prefix.$link;
			
			// Validate other value properties
			$newpost[$new]['title']   = flexicontent_html::dataFilter(@$post[$n]['title'], 0, 'STRING', 0);
			$newpost[$new]['hits']    = (int) @ $post[$n]['hits'];
			
			$new++;
		}
		$post = $newpost;
		
		// Serialize multi-property data before storing them into the DB
		foreach($post as $i => $v) {
			$post[$i] = serialize($v);
		}
		/*if ($use_ingroup) {
			$app = JFactory::getApplication();
			$app->enqueueMessage( print_r($post, true), 'warning');
		}*/
	}
	
	
	// Method to take any actions/cleanups needed after field's values are saved into the DB
	function onAfterSaveField( &$field, &$post, &$file, &$item ) {
	}
	
	
	// Method called just before the item is deleted to remove custom item data related to the field
	function onBeforeDeleteField(&$field, &$item) {
	}
	
	
	
	// *********************************
	// CATEGORY/SEARCH FILTERING METHODS
	// *********************************
	
	// Method to display a search filter for the advanced search view
	function onAdvSearchDisplayFilter(&$filter, $value='', $formName='searchForm')
	{
		if ( !in_array($filter->field_type, self::$field_types) ) return;
		
		$filter->parameters->set( 'display_filter_as_s', 1 );  // Only supports a basic filter of single text search input
		FlexicontentFields::createFilter($filter, $value, $formName);
	}
	
	
 	// Method to get the active filter result (an array of item ids matching field filter, or subquery returning item ids)
	// This is for search view
	function getFilteredSearch(&$field, $value)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		$field->parameters->set( 'display_filter_as_s', 1 );  // Only supports a basic filter of single text search input
		return FlexicontentFields::getFilteredSearch($field, $value, $return_sql=true);
	}
	
	
	
	// *************************
	// SEARCH / INDEXING METHODS
	// *************************
	
	// Method to create (insert) advanced search index DB records for the field values
	function onIndexAdvSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		if ( !$field->isadvsearch && !$field->isadvfilter ) return;
		
		// a. Each of the values of $values array will be added to the advanced search index as searchable text (column value)
		// b. Each of the indexes of $values will be added to the column 'value_id',
		//    and it is meant for fields that we want to be filterable via a drop-down select
		// c. If $values is null then only the column 'value' will be added to the search index after retrieving 
		//    the column value from table 'flexicontent_fields_item_relations' for current field / item pair will be used
		// 'required_properties' is meant for multi-property fields, do not add to search index if any of these is empty
		// 'search_properties'   containts property fields that should be added as text
		// 'properties_spacer'  is the spacer for the 'search_properties' text
		// 'filter_func' is the filtering function to apply to the final text
		FlexicontentFields::onIndexAdvSearch($field, $post, $item, $required_properties=array('link','title'), $search_properties=array('title'), $properties_spacer=' ', $filter_func=null);
		return true;
	}
	
	
	// Method to create basic search index (added as the property field->search)
	function onIndexSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		if ( !$field->issearch ) return;
		
		// a. Each of the values of $values array will be added to the basic search index (one record per item)
		// b. If $values is null then the column value from table 'flexicontent_fields_item_relations' for current field / item pair will be used
		// 'required_properties' is meant for multi-property fields, do not add to search index if any of these is empty
		// 'search_properties'   containts property fields that should be added as text
		// 'properties_spacer'  is the spacer for the 'search_properties' text
		// 'filter_func' is the filtering function to apply to the final text
		FlexicontentFields::onIndexSearch($field, $post, $item, $required_properties=array('link','title'), $search_properties=array('title'), $properties_spacer=' ', $filter_func=null);
		return true;
	}
	
	
	
	// **********************
	// VARIOUS HELPER METHODS
	// **********************
	
	// Get a url without the protocol
	function cleanurl($url)
	{
		$prefix = array("http://", "https://", "ftp://");
		$cleanurl = str_replace($prefix, "", $url);
		return $cleanurl;
	}
	
}
