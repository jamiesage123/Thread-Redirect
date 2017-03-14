<?php

/**
  * Disallow direct access to this file for security reasons
  */
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

/**
  * Define our hooks
  */
$plugins->add_hook("newthread_start", "threadredirect_newthread_start");
$plugins->add_hook("newthread_do_newthread_start", "threadredirect_newthread_do_newthread_start");
$plugins->add_hook("newthread_do_newthread_end", "threadredirect_newthread_do_newthread_end");
$plugins->add_hook("showthread_start", "threadredirect_showthread_start");

/**
  * Returns an array of information about this plugins-
  */
function threadredirect_info()
{
	global $lang;
	$lang->load("threadredirect", true);
	
    return array(
        "name"			=> "Thread Redirect",
        "description"	=> $lang->threadredirect_desc,
        "website"		=> "http://www.jamiesage.co.uk",
        "author"		=> "Jamie Sage",
        "authorsite"	=> "http://www.jamiesage.co.uk",
        "version"		=> "0.2",
        "guid" 			=> "",
        "codename"		=> str_replace('.php', '', basename(__FILE__)),
        "compatibility"	=>	"18*"
    );
}

/**
  * Called when the plugin is installed
  */
function threadredirect_install()
{
	global $db, $lang;
	$lang->load("threadredirect", true);
	
	$db->add_column("threads", "redirect_url", "VARCHAR(225) NULL DEFAULT NULL");
	
	// Settings
	$setting_group = array(
		'name' 			=> 'threadredirect',
		'title' 		=> 'Thread Redirect',
		'description' 	=> $lang->threadredirect_desc,
		'disporder' 	=> 5,
		'isdefault' 	=> 0
	);
	$gid = $db->insert_query("settinggroups", $setting_group);
	
	$setting = array(
		"name"			=> "threadredirect_groups",
		"title"			=> $lang->threadredirect_allowed_groups,
		"description"	=> $lang->threadredirect_allowed_groups_desc,
		"optionscode"	=> "groupselect",
		"value"			=> '',
		"disporder"		=> 1,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);
	
	// Rebuild settings
	rebuild_settings();
}

/**
  * Check if this plugin is installed
  */
function threadredirect_is_installed()
{
    global $db;
	
	// Check if the redirect_url field exists on the threads table
    if($db->field_exists("redirect_url", "threads"))
    {
        return true;
    }
    return false;
}

/**
  * Called when the plugin is uninstalled
  */
function threadredirect_uninstall()
{
	global $db;
	
	// Drop the redirect url field on the threads table
	$db->drop_column("threads", "redirect_url"); 
	
	$db->delete_query('settings', "name IN ('threadredirect_groups')");
	$db->delete_query('settinggroups', "name = 'threadredirect'");

	// Rebuild settings
	rebuild_settings();
}

/**
  * Called when the plugin is activated
  */
function threadredirect_activate()
{
	global $db, $lang;
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	$lang->load("threadredirect", true);
	
	// Add the threadredirect template
	$insert_array = array(
		'title'		=> 'threadredirect',
		'template'	=> $db->escape_string('<tr>
		<td class="trow2"><strong>' . $lang->threadredirect_redirect_url . '</strong></td>
		<td class="trow2"><input type="text" class="textbox" name="threadredirect" size="40" maxlength="225" value="{$threadredirect}" tabindex="2" /></td>
		</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
	find_replace_templatesets("newthread", "#".preg_quote('{$posticons}')."#i", '{$threadredirect}{$posticons}');
}

/**
  * Check if $user has permission to create a redirecting thread
  */
function has_permission($mybb, $user)
{
	$user_groups = explode(',', $user['usergroup']);
	$allowed_groups = explode(',', $mybb->settings['threadredirect_groups']);
	
	// All groups are allowed
	if ($mybb->settings['threadredirect_groups'] == -1) {
		return true;
	}
	
	// Include any additional groups
	if (!empty($user['additionalgroups'])) {
		$user_groups = array_merge($user_groups, explode(',', $user['additionalgroups']));
	}
	
	// Check if the user is included in $allowed_groups
	foreach ($user_groups as $group) {
		if (array_search($group, $allowed_groups) !== false) {
			return true;
		}
	}
	
	return false;
}

/**
  * Called when the plugin is deactived
  */
function threadredirect_deactivate()
{
	global $db;
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	// Delete the threadredirect template
	$db->delete_query("templates", "title IN('threadredirect')");
	
	find_replace_templatesets("newthread", "#".preg_quote('{$threadredirect}')."#i", '', 0);
}

/**
  * Insert the thread redirect input onto the new thread page
  */
function threadredirect_newthread_start()
{
	global $lang, $mybb, $templates, $post_errors, $thread, $threadredirect;
	
	if (has_permission($mybb, $mybb->user)) {
		eval("\$threadredirect = \"".$templates->get("threadredirect")."\";");
	}
}

/**
  * Fired when a new thread request was sent
  */
function threadredirect_newthread_do_newthread_start()
{
	global $db, $mybb, $tid, $new_thread;
		
	// If the user is creating a redirect thread and the message body is empty, allow them to continue
	if (empty($mybb->get_input('message')) && !empty($mybb->get_input('threadredirect')) && has_permission($mybb, $mybb->user)) {
		// Seems like validation for the message body is hard-coded, add a placeholder to get around the validation
		$mybb->input["message"] = $mybb->get_input('threadredirect');
	}
}

/**
  * Update the redirect_url field when a new thread is created
  */
function threadredirect_newthread_do_newthread_end()
{
	global $db, $mybb, $tid, $new_thread;

	if (has_permission($mybb, $mybb->user)) {
		$redirect_url = array(
			"redirect_url" => $db->escape_string($mybb->get_input('threadredirect'))
		);
		$db->update_query("threads", $redirect_url, "tid='{$tid}'");
	}
}

/**
  * Fired when a thread is shown
  */
function threadredirect_showthread_start()
{
	global $db, $mybb, $thread, $templates;
	
	// Only run if a redirect url was provided
	if ($thread['redirect_url']) {
		require_once MYBB_ROOT."/inc/class_parser.php";
		
		// We don't want to redirect the user who just created the thread, instead redirect them to the category the thread is in
		$referer = $_SERVER['HTTP_REFERER'];
		$query   = null;
		
		// Verify they came from the correct place
		if (!empty($referer)) {
			parse_str(parse_url($referer)['query'], $query);
			
			if (isset($query['processed'])) {
				// The user just created the thread, redirect back to the forum category
				return header("Location: " . get_forum_link($thread['fid']));
			}
		}
		
		// If the user didn't supply http or https, we will manually add http so that the url works correctly
		$url = $thread['redirect_url'];
		$parsed = parse_url($thread['redirect_url']);
		if (empty($parsed['scheme'])) {
			$url = 'http://' . $url;
		}
	 
		// If the thread has a custom message body
		$query = $db->simple_select("posts", "message", "replyto = 0 AND tid = " . intval($thread['tid']) . " AND fid = " . intval($thread['fid']));
		$post  = $db->fetch_array($query);
		
		// Custom body - Show the redirect page
		if ($post['message'] != $thread['redirect_url']) {
			// We now need to increase the view count as we redirect away before the count can be added
			$db->update_query('threads', ['views' => ($thread['views'] + 1)], 'tid = ' . intval($thread['tid']));
		
			// Parse the message
			$parser_options = array(
				'allow_html' => 'no',
				'allow_mycode' => 'yes',
				'allow_smilies' => 'yes',
				'allow_imgcode' => 'yes',
				'filter_badwords' => 'yes',
				'nl2br' => 'yes'
			);

			$parser = new postParser();
			$message = $parser->parse_message($post['message'], $parser_options);
			
			return redirect($url, $message, $thread['subject'], true);
		}
		
		// Redirect the user if the thread has a redirect URL
		header("Location: " . $url);
	}
}