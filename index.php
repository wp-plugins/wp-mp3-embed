<?php
/*
  Plugin Name: WP-MP3 Embed
  Plugin URI: https://wordpress.org/plugins/wp-mp3-embed/
  Description: Search and embed mp3 from mp3.zing.vn (top 1 website in Vietnam).
  Version: 1.0.1
  Author: lonuongsalamander
  Author URI: http://anybuy.vn/lo-nuong-salamander.htm
 */

add_action('media_buttons_context', 'wpmp3_add_button');
function wpmp3_add_button($context) {
    $context = '<a href="#TB_inline?width=400&inlineId=wpmp3_popup_container" id="wpmp3-btn" class="thickbox button add_media" title="WP-MP3 Embed"><span class="wp-media-buttons-icon"></span>WP-MP3 Embed</a>';
    return $context;
}

add_action('admin_enqueue_scripts', 'wpmp3_enqueue');
function wpmp3_enqueue($hook) {
    if (('edit.php' != $hook) && ('post-new.php' != $hook) && ('post.php' != $hook))
        return;
    wp_enqueue_style('wpmp3style', plugins_url('css/style.css', __FILE__));
}

function wpmp3_get_remote_html( $url ) {
	$response = wp_remote_get( $url );
	if ( is_wp_error( $response ) ) {
		return ( '' );
	}
	$data = wp_remote_retrieve_body( $response );
	if ( is_wp_error( $data ) ) {
		return ( '' );
	}
	return $data;
}
add_action('wp_ajax_wpmp3_search_action', 'wpmp3_search');
function wpmp3_search(){
	$retsult = '';
	$html = wpmp3_get_remote_html('http://m.mp3.zing.vn/tim-kiem/bai-hat.html?q='.$_POST['keyword'].'&c=apps');
	if($html == ''){ 
		$retsult = 'No result! Please try again!';
	}
	else {				
		$start = strpos($html, '<a href="/bai-hat/');
		while ( $start ) {
			$end = strpos($html, '</a>', $start);
			$item = substr($html, $start , $end - $start);

			$song_url_start = strpos($item, '<a href="/bai-hat/') + 18;
			$song_url_end = strpos($item, '.html"', $song_url_start);
			$song_url = substr($item, $song_url_start , $song_url_end - $song_url_start);			
			
			$song_ids = explode('/',$song_url);		
			$song_id = 	$song_ids[1];
			$song_url = 'http://m.mp3.zing.vn/bai-hat/' . $song_url . '.html';
					
			$song_name_start = strpos($item, '<h3>') + 4;
			$song_name_end = strpos($item, '</h3>', $song_name_start);
			$song_name = substr($item, $song_name_start , $song_name_end - $song_name_start);

			$song_sing_start = strpos($item, '<h4>') + 4;
			$song_sing_end = strpos($item, '</h4>', $song_sing_start);
			$song_sing = substr($item, $song_sing_start , $song_sing_end - $song_sing_start);
					
			$song_view_start = strpos($item, '<li class="icon-luot-nghe fn-number">') + 37;
			$song_view_end = strpos($item, '"', $song_view_start);
			$song_view = substr($item, $song_view_start , $song_view_end - $song_view_start);
			
			$song_image = 'http://static.mp3.zdn.vn/skins/mp3_mobile/images/avatar_default_82x82.jpg';												
					
			$retsult .= '<div class="wpmp3-item"><div class="wpmp3-item-link"><a href="'. $song_url .'" target="_blank" title="View this image in new windows">View</a><a class="wpmp3-item-use" wpmp3url="'.$song_url.'" wpmp3image="'.$song_image.'" wpmp3name="'.$song_name.'" wpmp3id="'.$song_id.'" wpmp3view="'.$song_view.'" href="#">Use this song</a></div><div class="wpmp3-item-overlay"></div><img src="'. $song_image.'"><span>'.$song_name.'</span></div>';
			$start = strpos($html, '<a href="/bai-hat/', $end );
		}//endwhile
	}//endif
	echo $retsult;
	die();
}

add_action('admin_footer', 'wpmp3_add_inline_popup_content');
function wpmp3_add_inline_popup_content() {
    ?>
    <style>
	#TB_ajaxContent {
		width: 750px !important;
		overflow-x: hidden;
		height: 100% !important;
	}
        .wpmp3-loading{display: inline-block; height: 20px; line-height: 20px; min-width:20px; padding-left: 25px; background: url("<?php echo plugin_dir_url(__FILE__) . '/spinner.gif'; ?>") no-repeat;}
    </style>
    <div id="wpmp3_popup_container" style='display:none'>
            <select name="wpmp3type" id="wpmp3type" style="float:left">
            	<option value="flash">Flash Player</option>
                <option value="html5">HTML5</option>                
            </select>
            <select name="wpmp3border" id="wpmp3border" style="float:left">
            	<option value="none">none</option>
                <option value="border">border = 1px dotted #ddd</option>
                <option value="quote">use [quote]</option>
                <option value="code">use [code]</option>
                <option value="hr">use [hr] end</option>
            </select>
            <div style="width:98%; display: inline-block; margin-top: 5px; height:28px; line-height: 28px;"><span style="float:left; margin-right: 10px;">Insert keyword here:</span> <input type="text" id="wpmp3input" name="wpmp3input" value="" size="30"/> <input type="button" id="wpmp3search" class="button" value="Search"/> <span id="wpmp3spinner" style="display:none" class="wpmp3-loading"> </span></div>
            <div id="wpmp3-container" class="wpmp3-container"></div>
            <div id="wpmp3-use-image" class="wpmp3-use-image"></div>
            <div style="text-align:right; padding-top: 5px;"><input type="button" id="wpmp3insert" class="button button-primary" value="Insert into post"></div>
    </div>
    <script>
        function insertAtCaret(areaId, text) {
            var txtarea = document.getElementById(areaId);
            var scrollPos = txtarea.scrollTop;
            var strPos = 0;
            var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
                "ff" : (document.selection ? "ie" : false));
            if (br == "ie") {
                txtarea.focus();
                var range = document.selection.createRange();
                range.moveStart('character', -txtarea.value.length);
                strPos = range.text.length;
            }
            else if (br == "ff")
                strPos = txtarea.selectionStart;

            var front = (txtarea.value).substring(0, strPos);
            var back = (txtarea.value).substring(strPos, txtarea.value.length);
            txtarea.value = front + text + back;
            strPos = strPos + text.length;
            if (br == "ie") {
                txtarea.focus();
                var range = document.selection.createRange();
                range.moveStart('character', -txtarea.value.length);
                range.moveStart('character', strPos);
                range.moveEnd('character', 0);
                range.select();
            }
            else if (br == "ff") {
                txtarea.selectionStart = strPos;
                txtarea.selectionEnd = strPos;
                txtarea.focus();
            }
            txtarea.scrollTop = scrollPos;
        }
		//search mp3
        jQuery("#wpmp3search").click(function() {
            if(jQuery("#wpmp3input").val() == '') {
                alert('Please enter keyword to search!');
            } else {
                jQuery('#wpmp3spinner').show();
                jQuery('#wpmp3-container').html('');
				var data = {
					action: 'wpmp3_search_action',
					keyword: jQuery("#wpmp3input").val()
				};
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('#wpmp3-container').html(response);
					jQuery('#wpmp3spinner').hide();	
				});
            }
        });
		//insert mp3
        jQuery("#wpmp3insert").live("click", function() {
            //if(jQuery(".wpmp3-item-use").html() != '') {
                vinsert = jQuery("#wpmp3-use-image").html();
                if (!tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden()) {
                    insertAtCaret('content', vinsert);
                } else {
                    tinyMCE.activeEditor.execCommand('mceInsertContent', 0, vinsert);
                }
                jQuery.colorbox.close();
        });
        jQuery(".wpmp3-item-use").live("click", function() {
            jQuery("#wpmp3-use-image").show();
			html1 = '<object width="410" height="100"><param name="movie" value="http://mp3.zing.vn/embed/song/'+  jQuery(this).attr('wpmp3id') + '?autostart=false" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="allowfullscreen" value="true"/><param name="allowscriptaccess" value="always"/><embed allowscriptaccess="always" allowfullscreen="true" width="410" height="100" src="http://mp3.zing.vn/embed/song/' +  jQuery(this).attr('wpmp3id') + '?autostart=false" quality="high" wmode="transparent"></embed></object><br />';
			if(jQuery("#wpmp3type").val() == 'flash'){//if type
			html = '<div class="product-entry" style="clear: both; height: 110px; padding: 3px; overflow: hidden;';
			if (jQuery("#wpmp3border").val() == 'border'){
				html += 'border: 1px dotted #ddd;';
			}
			html += '">' + html1 + '</div>';
			switch(jQuery("#wpmp3border").val()) {
				case 'hr':
					html += '<hr />';
					break;
				case 'quote':
					html = '<blockquote>' + html + '</blockquote>';
					break;
				case 'code':
					html = '<code>' + html + '</code>';
					break;					
				default:
					html = html;
			}
			}else{// if type
			html = '<iframe width="410" height="100" src="http://mp3.zing.vn/embed/song/' +  jQuery(this).attr('wpmp3id') + '?autostart=false" frameborder="0" allowfullscreen="true"></iframe>';
			}// end if type
			jQuery("#wpmp3-use-image").html(html);
        });
    </script>
    <?php
}
?>