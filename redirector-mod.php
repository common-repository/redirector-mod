<?php
/*
Plugin Name: Redirector Mod
Plugin URL: http://www.mindsharestudios.com/downloads/
Description: Redirect any page to an internal or external URL
Version: 1.0
Author: Ralf Hortt, Mindshare Studios
Author URL: http://www.mindsharstudios.com/

Notes: 

This plugin is a fork of Redirector by Ralf Hortt. It has been updated 
for WP 3.0 and does not automatically insert 'http:' before any custom
redirect thus enabling use of partial URIs and query string as your
redirect rather than fully qualified URLs.

Forked at version 1.1.

*/

//======================================
// @Description: 
function re_adminhead()
{	// Add meta_box
	add_meta_box('redirect', __('Redirect','redirector'), 're_metabox', 'page');
	// Add DOM juice
		?>
		<link href="<?php echo get_bloginfo('url').'/'.PLUGINDIR ?>/redirector/redirector.css" rel="stylesheet" type="text/css"/>
		<script src="<?php echo get_bloginfo('url').'/'.PLUGINDIR ?>/redirector/redirector.js" type="text/javascript"></script>
		<?php
}

//======================================
// @Description: returns the menu level of the current page
// @Optional: int $post_ID ID to start with
// @Optional: int $i counter
// @Return: int $i returns the menu level of the current page
function re_get_pagelevel($post_ID = '', $i = '0')
{
	global $wpdb, $post;
	$i++;
	
	$start = ($post_ID) ? $post_ID : $post->post_parent;
	$sql = "SELECT ID, post_parent FROM $wpdb->posts WHERE ID = '$start'";
	$row = $wpdb->get_row($sql);

	if ($row->post_parent != 0)
		$i = re_get_pagelevel($row->post_parent, $i);
		
	return $i;
}

//======================================
// @Description: If page as children
// @Optional: int $post_ID;
// @Return: bool TRUE / FALSE
function re_has_child_pages($post_ID = "")
{
	global $wpdb, $post;
	$ID = ($post_ID) ? $post_ID : $post->ID;
	$sql = "SELECT ID FROM $wpdb->posts WHERE post_parent = '$ID' AND post_type = 'page'";
	$IDs = $wpdb->get_col($sql);
	
	if ($IDs)
		return TRUE;
	else
		return FALSE;
}

//======================================
// @Description: 
// @Require: 
// @Optional: 
// @Return: 
function re_metabox()
{
	$redirect = get_post_meta($_GET['post'], 'redirector', TRUE);
	$redirect_url = (!is_numeric($redirect)) ? $redirect : '';
	$redirect_url = ($redirect_url != 'child') ? $redirect_url : '';
	$checked_child = ($redirect == 'child') ? 'checked="checked"' : '';
	$cecked_url = ($redirect != 'child') ? 'checked="checked"' : '';
	$redirect_page = (is_numeric($redirect)) ? 'checked="checked"' : '';
	
	?>
	<h4><?php _e('Redirect Type', 'redirector'); ?></h4>
	<div id="redirect_type">
		<input type="radio" id="no_redirection" name="redirect_type" value="none" onchange="redirecttoggle('none')" checked="checked"> <label for="no_redirection"><?php _e('None', 'redirector'); ?></label> <span>|</span> 
		<input type="radio" id="redirect_page" name="redirect_type" value="redirect_page" onchange="redirecttoggle('#redirect_settings_page')" <?php echo $redirect_page ?>> <label for="redirect_page"><?php _e('Redirect to a page', 'redirector'); ?></label> <span>|</span> 
		<input type="radio" id="redirect_url" name="redirect_type" value="redirect_url" onchange="redirecttoggle('#redirect_settings_url')" <?php echo $cecked_url ?>> <label for="redirect_url"><?php _e('Redirect to a URL', 'redirector'); ?></label> <span>|</span> 
		<input type="radio" id="redirect_child" name="redirect_type" value="redirect_child" onchange="redirecttoggle('#redirect_settings_child')" <?php echo $checked_child ?>> <label for="redirect_child"><?php _e('Redirect to the first child page', 'redirector'); ?></label>
		<input type="hidden" id="redirector_type_set" name="redirector_type_set" value="<?php echo $redirect; ?>" />
	</div>
	
	<div class="redirect_settings">
		<p class="redirect_settings" id="redirect_settings_page">
			<label for="redirector"><?php _e('Redirect to:','redirector'); ?></label><br />
			<select id="redirector" name="redirector">
				<option value=""><?php _e('No redirection','redirector'); ?></option>
				<?php re_page_tree_run('', $redirect) ?>
			</select>
		</p>
		
		<p class="redirect_settings" id="redirect_settings_url">
			<label for="redirector_url"><?php _e('URL:', 'redirector'); ?></label><br />
			<input id="redirector_url" name="redirector_url" value="<?php echo $redirect_url; ?>" type="text" size="35" />
		</p>
	
	</div>
	<br clear="all" />
	<?php
}

//======================================
// @Description: Save as meta value
function re_meta_save()
{
	// Redirect to a WordPress page
	if ($_POST['redirect_type'] == 'redirect_page' && is_numeric($_POST['redirector']))
	{
		$redirect_to = $_POST['redirector'];
	}
	
	// Redirect to any url
	elseif ($_POST['redirect_type'] == 'redirect_url' && $_POST['redirector_url'])
	{
		$redirect_to = $_POST['redirector_url'];
	}
	
	// Redirect to first child
	elseif ($_POST['redirect_type'] == 'redirect_child')
	{
		$redirect_to = 'child';
	}
	
	// Save as a meta_key value
	if ($redirect_to)
		update_post_meta($_POST['ID'], 'redirector', $redirect_to);		
	else
		delete_post_meta($_POST['ID'], 'redirector');
}

//======================================
// @Description: Recursive to create the page tree
// @Optional: int/obj $parent post object or page ID to start 
// @Optional: int $highlight ID to highlight
function re_page_tree_run($parent = '', $highlight = ''){
global $wpdb;
	$parent = (is_object($parent)) ? $parent->ID : $parent;
	$start = ($parent) ? $parent : '0';
	$sql = "SELECT * FROM $wpdb->posts WHERE post_parent = '$start' AND post_type = 'page' ORDER BY menu_order, post_title";
	$posts = $wpdb->get_results($sql);
	
	foreach($posts as $post)
	{
		$devider = '';
		$level = re_get_pagelevel($post->ID, '0');
		for($i = 1; $i < $level; $i++)
		{
			$devider.= '&mdash;';
		}
		$selected = ($post->ID == $highlight) ? 'selected="selected"' : ''; ?>
		<option <?php echo $selected ?> value="<?php echo $post->ID ?>"><?php echo $devider.' '.$post->post_title; ?></option>
		<?php
		if (re_has_child_pages($post->ID))
		{
			re_page_tree_run($post, $highlight);
		}
	}
}

//======================================
// @Description: Redirect
function re_redirector($null)
{
	global $wp_query, $wpdb, $post;
	if (is_page())
	{
		$redirect = get_post_meta($wp_query->post->ID, 'redirector', true);
		$redirect = (is_numeric($redirect)) ? get_permalink($redirect) : $redirect;
		if ($redirect == 'child')
		{
			$sql = "SELECT ID FROM $wpdb->posts WHERE post_parent = '$post->ID' AND post_type = 'page' AND post_status = 'publish' ORDER BY menu_order LIMIT 1";
			$child = $wpdb->get_var($sql);
			if ($child)
				$redirect = get_permalink($child);
		}
		if ($redirect != '')
		{
			wp_redirect($redirect);
			header("Status: 302");
			exit;
		}
	}
}

add_action('admin_head', 're_adminhead');
add_action('template_redirect', 're_redirector');
add_action('save_post', 're_meta_save');

load_plugin_textdomain('redirector', PLUGINDIR.'/redirector');

?>