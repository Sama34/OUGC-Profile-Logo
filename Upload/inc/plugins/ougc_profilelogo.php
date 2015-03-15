<?php

/***************************************************************************
 *
 *   OUGC Profile Logo plugin (/inc/plugins/ougc_profilelogo.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2015 Omar Gonzalez
 *   
 *   Based on: Profile Picture plugin
 *	 By: Starpaul20 (PaulBender)
 *   
 *   Website: http://omarg.me
 *
 *   Allows your users to upload an logo to use as profile logo.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Tell MyBB when to run the hooks
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_style_templates_set', create_function('&$args', 'global $lang;	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo", true);'));

	$plugins->add_hook("admin_user_users_delete_commit", "ougc_profilelogo_user_delete");
	$plugins->add_hook("admin_formcontainer_end", "ougc_profilelogo_usergroup_permission");
	$plugins->add_hook("admin_user_groups_edit_commit", "ougc_profilelogo_usergroup_permission_commit");
	$plugins->add_hook("admin_tools_system_health_output_chmod_list", "ougc_profilelogo_chmod");
}
else
{
	$plugins->add_hook("global_intermediate", "ougc_profilelogo_global");
	$plugins->add_hook("usercp_start", "ougc_profilelogo_run");
	$plugins->add_hook("usercp_menu_built", "ougc_profilelogo_nav");
	$plugins->add_hook("fetch_wol_activity_end", "ougc_profilelogo_online_activity");
	$plugins->add_hook("build_friendly_wol_location_end", "ougc_profilelogo_online_location");
	$plugins->add_hook("modcp_do_editprofile_start", "ougc_profilelogo_removal");
	$plugins->add_hook("modcp_editprofile_start", "ougc_profilelogo_removal_lang");

	// Neat trick for caching our custom template(s)
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	if(THIS_SCRIPT == 'usercp.php')
	{
		$templatelist .= 'ougcprofilelogo_usercp,ougcprofilelogo_usercp_auto_resize_auto,ougcprofilelogo_usercp_auto_resize_user,ougcprofilelogo_usercp_current,ougcprofilelogo_usercp_description,ougcprofilelogo_usercp_nav,ougcprofilelogo_usercp_remove,ougcprofilelogo_usercp_upload';
	}

	if(THIS_SCRIPT == 'private.php')
	{
		$templatelist .= 'ougcprofilelogo_usercp_nav';
	}

	if(THIS_SCRIPT == 'member.php')
	{
		$templatelist .= 'ougcprofilelogo_profile,ougcprofilelogo_profile_description,ougcprofilelogo_profile_img';
	}

	if(THIS_SCRIPT == 'modcp.php')
	{
		$templatelist .= 'ougcprofilelogo_modcp,ougcprofilelogo_modcp_description';
	}
}

// The information that shows up on the plugin manager
function ougc_profilelogo_info()
{
	global $lang;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo", true);

	return array(
		"name"				=> 'OUGC Profile Logo',
		"description"		=> $lang->setting_group_ougc_profilelogo_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"codename"			=> "ougc_profilelogo",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is installed.
function ougc_profilelogo_install()
{
	global $db, $cache;
	ougc_profilelogo_uninstall();

	$db->add_column("users", "ougc_profilelogo", "varchar(200) NOT NULL default ''");
	$db->add_column("users", "ougc_profilelogo_dimensions", "varchar(10) NOT NULL default ''");
	$db->add_column("users", "ougc_profilelogo_type", "varchar(10) NOT NULL default ''");
	$db->add_column("users", "ougc_profilelogo_description", "varchar(255) NOT NULL default ''");

	$db->add_column("usergroups", "ougc_profilelogo_canuse", "tinyint(1) NOT NULL default '1'");
	$db->add_column("usergroups", "ougc_profilelogo_canupload", "tinyint(1) NOT NULL default '1'");
	$db->add_column("usergroups", "ougc_profilelogo_maxsize", "int unsigned NOT NULL default '40'");
	$db->add_column("usergroups", "ougc_profilelogo_maxdimensions", "varchar(10) NOT NULL default '200x200'");
	$db->add_column("usergroups", "ougc_profilelogo_mindimensions", "varchar(10) NOT NULL default '200x200'");

	$cache->update_usergroups();
}

// Checks to make sure plugin is installed
function ougc_profilelogo_is_installed()
{
	global $db;
	if($db->field_exists("ougc_profilelogo", "users"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function ougc_profilelogo_uninstall()
{
	global $db, $cache;
	$PL or require_once PLUGINLIBRARY;

	if($db->field_exists("ougc_profilelogo", "users"))
	{
		$db->drop_column("users", "ougc_profilelogo");
	}

	if($db->field_exists("ougc_profilelogo_dimensions", "users"))
	{
		$db->drop_column("users", "ougc_profilelogo_dimensions");
	}

	if($db->field_exists("ougc_profilelogo_type", "users"))
	{
		$db->drop_column("users", "ougc_profilelogo_type");
	}

	if($db->field_exists("ougc_profilelogo_description", "users"))
	{
		$db->drop_column("users", "ougc_profilelogo_description");
	}

	if($db->field_exists("ougc_profilelogo_canuse", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profilelogo_canuse");
	}

	if($db->field_exists("ougc_profilelogo_canupload", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profilelogo_canupload");
	}

	if($db->field_exists("ougc_profilelogo_maxsize", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profilelogo_maxsize");
	}

	if($db->field_exists("ougc_profilelogo_maxdimensions", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profilelogo_maxdimensions");
	}

	if($db->field_exists("ougc_profilelogo_mindimensions", "usergroups"))
	{
		$db->drop_column("usergroups", "ougc_profilelogo_mindimensions");
	}

	$cache->update_usergroups();

	$PL->settings_delete('ougc_profilelogo');
	$PL->templates_delete('ougcprofilelogo');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['profilelogo']))
	{
		unset($plugins['profilelogo']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// This function runs when the plugin is activated.
function ougc_profilelogo_activate()
{
	global $db, $PL, $cache, $lang;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo", true);
	$PL or require_once PLUGINLIBRARY;

	// Add settings group
	$PL->settings('ougc_profilelogo', $lang->setting_group_ougc_profilelogo, $lang->setting_group_ougc_profilelogo_desc, array(
		'uploadpath'		=> array(
		   'title'			=> 'Profile logos Upload Path',
		   'description'	=> 'This is the path where profile logos will be uploaded to. This directory <strong>must be chmod 777</strong> (writable) for uploads to work.',
		   'optionscode'	=> 'text',
		   'value'			=> './uploads/ougc_profilelogos'
		),
		'resizing'		=> array(
		   'title'			=> 'Profile logos Resizing Mode',
		   'description'	=> 'If you wish to automatically resize all large profile logos, provide users the option of resizing their profile logo, or not resize profile logos at all you can change this setting.',
		   'optionscode'	=> 'select
auto=Automatically resize large profile logos
user=Give users the choice of resizing large profile logos
disabled=Disable this feature',
		   'value'			=> 'auto'
		),
		'description'		=> array(
		   'title'			=> 'Profile logos Description',
		   'description'	=> 'If you wish allow your users to enter an optional description for their profile logo, set this option to yes.',
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
	));

	// Add template group
	$PL->templates('ougcprofilelogo', '<lang:setting_group_ougc_profilelogo>', array(
		'usercp' => '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->change_ougc_profilelogoture}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
	{$usercpnav}
	<td valign="top">
		{$ougc_profilelogo_error}
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead" colspan="2"><strong>{$lang->change_ougc_profilelogoture}</strong></td>
			</tr>
			<tr>
				<td class="trow1" colspan="2">
					<table cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td>{$lang->ougc_profilelogo_note}{$ougc_profilelogomsg}
							{$currentougc_profilelogo}
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="tcat" colspan="2"><strong>{$lang->custom_profile_pic}</strong></td>
			</tr>
			<form enctype="multipart/form-data" action="usercp.php" method="post">
			<input type="hidden" name="action" value="ougc_profilelogo" />
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			{$ougc_profilelogoupload}
			<tr>
				<td class="trow2" width="40%">
					<strong>{$lang->ougc_profilelogo_url}</strong>
					<br /><span class="smalltext">{$lang->ougc_profilelogo_url_note}</span>
				</td>
				<td class="trow2" width="60%">
					<input type="text" class="textbox" name="ougc_profilelogourl" size="45" value="{$ougc_profilelogourl}" />
				</td>
			</tr>
			{$ougc_profilelogo_description}
		</table>
		<br />
		<div align="center">
			<input type="submit" class="button" name="submit" value="{$lang->change_logo}" />
			{$removeougc_profilelogoture}
		</div>
	</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>',
		'usercp_auto_resize_auto' => '<br /><span class="smalltext">{$lang->ougc_profilelogo_auto_resize_note}</span>',
		'usercp_auto_resize_user' => '<br /><span class="smalltext"><input type="checkbox" name="auto_resize" value="1" checked="checked" id="auto_resize" /> <label for="auto_resize">{$lang->ougc_profilelogo_auto_resize_option}</label></span>',
		'usercp_current' => '<td width="150" align="right"><img src="{$userougc_profilelogoture[\'logo\']}" alt="{$lang->profile_logo_mine}" title="{$lang->profile_logo_mine}" {$userougc_profilelogoture[\'width_height\']} /></td>',
		'usercp_remove' => '<input type="submit" class="button" name="remove" value="{$lang->remove_logo}" />',
		'profile' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->users_ougc_profilelogo}</strong></td>
</tr>
<tr>
<td class="trow1" align="center">{$ougc_profilelogo_img}<br />
{$description}</td>
</tr>
</table>
<br />',
		'profile_description' => '<span class="smalltext"><em>{$memprofile[\'ougc_profilelogo_description\']}</em></span>',
		'profile_img' => '<img src="{$userougc_profilelogoture[\'logo\']}" alt="" {$userougc_profilelogoture[\'width_height\']} />',
		'usercp_description' => '<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->ougc_profilelogo_description}</strong>
		<br /><span class="smalltext">{$lang->ougc_profilelogo_description_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="text" class="textbox" name="ougc_profilelogo_description" size="100" value="{$description}" />
	</td>
</tr>',
		'usercp_upload' => '<tr>
	<td class="trow1" width="40%">
		<strong>{$lang->ougc_profilelogo_upload}</strong>
		<br /><span class="smalltext">{$lang->ougc_profilelogo_upload_note}</span>
	</td>
	<td class="trow1" width="60%">
		<input type="file" name="ougc_profilelogoupload" size="25" class="fileupload" />
		{$auto_resize}
	</td>
</tr>',
		'usercp_nav' => '<div><a href="usercp.php?action=ougc_profilelogo" class="usercp_nav_item" style="padding-left:40px; background:url(\'images/ougc_profilelogo.png\') no-repeat left center;">{$lang->ucp_nav_change_ougc_profilelogo}</a></div>',
		'modcp' => '<tr><td colspan="3"><span class="smalltext"><label><input type="checkbox" class="checkbox" name="remove_ougc_profilelogo" value="1" /> {$lang->remove_profile_logo}</label></span></td></tr>{$ougc_profilelogo_description}',
		'modcp_description' => '<tr>
<td colspan="3"><span class="smalltext">{$lang->ougc_profilelogo_description}</span></td>
</tr>
<tr>
<td colspan="3"><textarea name="ougc_profilelogo_description" id="ougc_profilelogo_description" rows="4" cols="30">{$user[\'ougc_profilelogo_description\']}</textarea></td>
</tr>',
	));

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_profilelogo_info();

	if(!isset($plugins['profilelogo']))
	{
		$plugins['profilelogo'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['profilelogo'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('{$changesigop}')."#i", '{$changesigop}<!-- ougc_profilelogo -->');
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$lang->remove_avatar}</label></span></td>
										</tr>')."#i", '{$lang->remove_avatar}</label></span></td>
										</tr>{$ougc_profilelogo}');
}

// This function runs when the plugin is deactivated.
function ougc_profilelogo_deactivate()
{
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_nav_profile", "#".preg_quote('<!-- ougc_profilelogo -->')."#i", '', 0);
	find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$ougc_profilelogo}')."#i", '', 0);
}

// User CP Nav link
function ougc_profilelogo_nav()
{
	global $db, $mybb, $lang, $templates, $usercpnav;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo");

	if($mybb->usergroup['ougc_profilelogo_canuse'] == 1)
	{
		eval("\$ougc_profilelogo_nav = \"".$templates->get("ougcprofilelogo_usercp_nav")."\";");
		$usercpnav = str_replace("<!-- ougc_profilelogo -->", $ougc_profilelogo_nav, $usercpnav);
	}
}

// Modify the logo globally for current user
function ougc_profilelogo_global()
{
	global $theme, $mybb;

	if(THIS_SCRIPT == 'member.php' && $mybb->get_input('action') == 'profile' && $mybb->usergroup['canviewprofiles'])
	{
		if($uid = $mybb->get_input('uid', 1))
		{
			$memprofile = get_user($uid);
		}
		else
		{
			$memprofile = $mybb->user;
		}
	}
	else
	{
		$memprofile = $mybb->user;
	}

	if($memprofile['ougc_profilelogo'])
	{
		$memprofile['ougc_profilelogo'] = htmlspecialchars_uni($memprofile['ougc_profilelogo']);

		if($memprofile['ougc_profilelogo_type'] == 'upload')
		{
			$theme['logo'] = $mybb->get_asset_url($memprofile['ougc_profilelogo']);
		}
		else
		{
			$theme['logo'] = $memprofile['ougc_profilelogo'];
		}
	}

	if($theme['logo'] == $mybb->get_asset_url(''))
	{
		$theme['logo'] = '';
	}
}

// The UserCP profile logo page
function ougc_profilelogo_run()
{
	global $mybb;

	if($mybb->input['action'] == "ougc_profilelogo")
	{
		global $db, $lang, $templates, $theme, $headerinclude, $usercpnav, $header, $ougc_profilelogo, $footer;
		isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo");
		require_once MYBB_ROOT."inc/functions_ougc_profilelogo.php";

		if($mybb->request_method == "post")
		{
			// Verify incoming POST request
			verify_post_check($mybb->get_input('my_post_key'));

			if($mybb->usergroup['ougc_profilelogo_canuse'] == 0)
			{
				error_no_permission();
			}

			$ougc_profilelogo_error = "";

			if(!empty($mybb->input['remove'])) // remove profile logo
			{
				$updated_ougc_profilelogo = array(
					"ougc_profilelogo" => "",
					"ougc_profilelogo_dimensions" => "",
					"ougc_profilelogo_type" => "",
					"ougc_profilelogo_description" => ""
				);
				$db->update_query("users", $updated_ougc_profilelogo, "uid='{$mybb->user['uid']}'");
				remove_ougc_profilelogo($mybb->user['uid']);
			}
			elseif($_FILES['ougc_profilelogoupload']['name']) // upload profile logo
			{
				if($mybb->usergroup['ougc_profilelogo_canupload'] == 0)
				{
					error_no_permission();
				}

				// See if profile logo description is too long
				if(my_strlen($mybb->input['ougc_profilelogo_description']) > 255)
				{
					$ougc_profilelogo_error = $lang->error_descriptiontoobig;
				}

				$ougc_profilelogo = upload_ougc_profilelogo();

				if($ougc_profilelogo['error'])
				{
					$ougc_profilelogo_error = $ougc_profilelogo['error'];
				}
				else
				{
					if($ougc_profilelogo['width'] > 0 && $ougc_profilelogo['height'] > 0)
					{
						$ougc_profilelogo_dimensions = $ougc_profilelogo['width']."|".$ougc_profilelogo['height'];
					}
					$updated_ougc_profilelogo = array(
						"ougc_profilelogo" => $ougc_profilelogo['ougc_profilelogo'].'?dateline='.TIME_NOW,
						"ougc_profilelogo_dimensions" => $ougc_profilelogo_dimensions,
						"ougc_profilelogo_type" => "upload",
						"ougc_profilelogo_description" => $db->escape_string($mybb->input['ougc_profilelogo_description'])
					);
					$db->update_query("users", $updated_ougc_profilelogo, "uid='{$mybb->user['uid']}'");
				}
			}
			elseif($mybb->input['ougc_profilelogourl']) // remote profile logo
			{
				$mybb->input['ougc_profilelogourl'] = trim($mybb->get_input('ougc_profilelogourl'));

				$mybb->input['ougc_profilelogourl'] = preg_replace("#script:#i", "", $mybb->input['ougc_profilelogourl']);
				$ext = get_extension($mybb->input['ougc_profilelogourl']);

				// Copy the profile logo to the local server (work around remote URL access disabled for getimagesize)
				$file = fetch_remote_file($mybb->input['ougc_profilelogourl']);
				if(!$file)
				{
					$ougc_profilelogo_error = $lang->error_invalidougc_profilelogourl;
				}
				else
				{
					$tmp_name = $mybb->settings['ougc_profilelogo_uploadpath']."/remote_".md5(uniqid(rand(), true));
					$fp = @fopen($tmp_name, "wb");
					if(!$fp)
					{
						$ougc_profilelogo_error = $lang->error_invalidougc_profilelogourl;
					}
					else
					{
						fwrite($fp, $file);
						fclose($fp);
						list($width, $height, $type) = @getimagesize($tmp_name);
						@unlink($tmp_name);
						if(!$type)
						{
							$ougc_profilelogo_error = $lang->error_invalidougc_profilelogourl;
						}
					}
				}

				// See if profile logo description is too long
				if(my_strlen($mybb->input['ougc_profilelogo_description']) > 255)
				{
					$ougc_profilelogo_error = $lang->error_descriptiontoobig;
				}

				if(empty($ougc_profilelogo_error))
				{
					if($width && $height && $mybb->usergroup['ougc_profilelogo_maxdimensions'] != "")
					{
						list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profilelogo_maxdimensions']));
						if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
						{
							$lang->error_ougc_profilelogotoobig = $lang->sprintf($lang->error_ougc_profilelogotoobig, $maxwidth, $maxheight);
							$ougc_profilelogo_error = $lang->error_ougc_profilelogotoobig;
						}
					}
					if($width && $height && $mybb->usergroup['ougc_profilelogo_mindimensions'] != "")
					{
						list($minwidth, $minheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profilelogo_mindimensions']));
						if(($minwidth && $width > $minwidth) || ($minheight && $height > $minheight))
						{
							$lang->error_ougc_profilelogotoosmall = $lang->sprintf($lang->error_ougc_profilelogotoosmall, $minwidth, $minheight);
							$ougc_profilelogo_error = $lang->error_ougc_profilelogotoosmall;
						}
					}
				}

				if(empty($ougc_profilelogo_error))
				{
					if($width > 0 && $height > 0)
					{
						$ougc_profilelogo_dimensions = (int)$width."|".(int)$height;
					}
					$updated_ougc_profilelogo = array(
						"ougc_profilelogo" => $db->escape_string($mybb->input['ougc_profilelogourl'].'?dateline='.TIME_NOW),
						"ougc_profilelogo_dimensions" => $ougc_profilelogo_dimensions,
						"ougc_profilelogo_type" => "remote",
						"ougc_profilelogo_description" => $db->escape_string($mybb->input['ougc_profilelogo_description'])
					);
					$db->update_query("users", $updated_ougc_profilelogo, "uid='{$mybb->user['uid']}'");
					remove_ougc_profilelogo($mybb->user['uid']);
				}
			}
			else // just updating profile logo description
			{
				// See if profile logo description is too long
				if(my_strlen($mybb->input['ougc_profilelogo_description']) > 255)
				{
					$ougc_profilelogo_error = $lang->error_descriptiontoobig;
				}

				if(empty($ougc_profilelogo_error))
				{
					$updated_ougc_profilelogo = array(
						"ougc_profilelogo_description" => $db->escape_string($mybb->input['ougc_profilelogo_description'])
					);
					$db->update_query("users", $updated_ougc_profilelogo, "uid='{$mybb->user['uid']}'");
				}
			}

			if(empty($ougc_profilelogo_error))
			{
				redirect("usercp.php?action=ougc_profilelogo", $lang->redirect_ougc_profilelogoupdated);
			}
			else
			{
				$mybb->input['action'] = "ougc_profilelogo";
				$ougc_profilelogo_error = inline_error($ougc_profilelogo_error);
			}
		}
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->change_ougc_profilelogoture, "usercp.php?action=ougc_profilelogo");

		// Show main profile logo page
		if($mybb->usergroup['ougc_profilelogo_canuse'] == 0)
		{
			error_no_permission();
		}

		$ougc_profilelogomsg = $ougc_profilelogourl = '';

		if($mybb->user['ougc_profilelogo_type'] == "upload" || stristr($mybb->user['ougc_profilelogo'], $mybb->settings['ougc_profilelogo_uploadpath']))
		{
			$ougc_profilelogomsg = "<br /><strong>".$lang->already_uploaded_ougc_profilelogo."</strong>";
		}
		elseif($mybb->user['ougc_profilelogo_type'] == "remote" || my_strpos(my_strtolower($mybb->user['ougc_profilelogo']), "http://") !== false)
		{
			$ougc_profilelogomsg = "<br /><strong>".$lang->using_remote_ougc_profilelogo."</strong>";
			$ougc_profilelogourl = htmlspecialchars_uni($mybb->user['ougc_profilelogo']);
		}

		if(!empty($mybb->user['ougc_profilelogo']))
		{
			$userougc_profilelogoture = format_profile_logo(htmlspecialchars_uni($mybb->user['ougc_profilelogo']), $mybb->user['ougc_profilelogo_dimensions'], '200x200');
			eval("\$currentougc_profilelogo = \"".$templates->get("ougcprofilelogo_usercp_current")."\";");
		}

		if($mybb->usergroup['ougc_profilelogo_maxdimensions'] != "")
		{
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profilelogo_maxdimensions']));
			$lang->ougc_profilelogo_note .= "<br />".$lang->sprintf($lang->ougc_profilelogo_note_dimensions, $maxwidth, $maxheight);
		}
		if($mybb->usergroup['ougc_profilelogo_mindimensions'] != "")
		{
			list($minwidth, $minheight) = explode("x", my_strtolower($mybb->usergroup['ougc_profilelogo_mindimensions']));
			$lang->ougc_profilelogo_note .= "<br />".$lang->sprintf($lang->ougc_profilelogo_note_mindimensions, $minwidth, $minheight);
		}
		if($mybb->usergroup['ougc_profilelogo_maxsize'])
		{
			$maxsize = get_friendly_size($mybb->usergroup['ougc_profilelogo_maxsize']*1024);
			$lang->ougc_profilelogo_note .= "<br />".$lang->sprintf($lang->ougc_profilelogo_note_size, $maxsize);
		}

		$auto_resize = '';
		if($mybb->settings['ougc_profilelogo_resizing'] == "auto")
		{
			eval("\$auto_resize = \"".$templates->get("ougcprofilelogo_usercp_auto_resize_auto")."\";");
		}
		else if($mybb->settings['ougc_profilelogo_resizing'] == "user")
		{
			eval("\$auto_resize = \"".$templates->get("ougcprofilelogo_usercp_auto_resize_user")."\";");
		}

		$ougc_profilelogoupload = '';
		if($mybb->usergroup['ougc_profilelogo_canupload'] == 1)
		{
			eval("\$ougc_profilelogoupload = \"".$templates->get("ougcprofilelogo_usercp_upload")."\";");
		}

		$description = htmlspecialchars_uni($mybb->user['ougc_profilelogo_description']);

		$ougc_profilelogo_description = '';
		if($mybb->settings['ougc_profilelogo_description'] == 1)
		{
			eval("\$ougc_profilelogo_description = \"".$templates->get("ougcprofilelogo_usercp_description")."\";");
		}

		$removeougc_profilelogoture = '';
		if(!empty($mybb->user['ougc_profilelogo']))
		{
			eval("\$removeougc_profilelogoture = \"".$templates->get("ougcprofilelogo_usercp_remove")."\";");
		}

		if(!isset($ougc_profilelogo_error))
		{
			$ougc_profilelogo_error = '';
		}

		eval("\$ougc_profilelogoture = \"".$templates->get("ougcprofilelogo_usercp")."\";");
		output_page($ougc_profilelogoture);
	}
}

// Online location support
function ougc_profilelogo_online_activity($user_activity)
{
	global $user;
	if(my_strpos($user['location'], "usercp.php?action=ougc_profilelogo") !== false)
	{
		$user_activity['activity'] = "usercp_ougc_profilelogo";
	}

	return $user_activity;
}

function ougc_profilelogo_online_location($plugin_array)
{
	global $db, $mybb, $lang, $parameters;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo");

	if($plugin_array['user_activity']['activity'] == "usercp_ougc_profilelogo")
	{
		$plugin_array['location_name'] = $lang->changing_ougc_profilelogo;
	}

	return $plugin_array;
}

// Mod CP removal function
function ougc_profilelogo_removal()
{
	global $mybb, $db, $user;
	require_once MYBB_ROOT."inc/functions_ougc_profilelogo.php";

	if($mybb->input['remove_ougc_profilelogo'])
	{
		$updated_ougc_profilelogo = array(
			"ougc_profilelogo" => "",
			"ougc_profilelogo_dimensions" => "",
			"ougc_profilelogo_type" => ""
		);
		remove_ougc_profilelogo($user['uid']);

		$db->update_query("users", $updated_ougc_profilelogo, "uid='{$user['uid']}'");
	}

	// Update description if active
	if($mybb->settings['ougc_profilelogo_description'] == 1)
	{
		$updated_ougc_profilelogo = array(
			"ougc_profilelogo_description" => $db->escape_string($mybb->input['ougc_profilelogo_description'])
		);
		$db->update_query("users", $updated_ougc_profilelogo, "uid='{$user['uid']}'");
	}
}

// Mod CP language
function ougc_profilelogo_removal_lang()
{
	global $mybb, $lang, $user, $templates, $ougc_profilelogo_description, $ougc_profilelogo;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo");

	$user['ougc_profilelogo_description'] = htmlspecialchars_uni($user['ougc_profilelogo_description']);

	if($mybb->settings['ougc_profilelogo_description'] == 1)
	{
		eval("\$ougc_profilelogo_description = \"".$templates->get("ougcprofilelogo_modcp_description")."\";");
	}

	eval("\$ougc_profilelogo = \"".$templates->get("ougcprofilelogo_modcp")."\";");
}

// Delete profile logo if user is deleted
function ougc_profilelogo_user_delete()
{
	global $db, $mybb, $user;

	if($user['ougc_profilelogo_type'] == "upload")
	{
		// Removes the ./ at the beginning the timestamp on the end...
		@unlink("../".substr($user['ougc_profilelogo'], 2, -20));
	}
}

// Admin CP permission control
function ougc_profilelogo_usergroup_permission()
{
	global $mybb, $lang, $form, $form_container, $run_module;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo", true);

	if($run_module == 'user' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc)
	{
		$ougc_profilelogo_options = array(
	 		$form->generate_check_box('ougc_profilelogo_canuse', 1, $lang->can_use_ougc_profilelogo, array("checked" => $mybb->input['ougc_profilelogo_canuse'])),
			$form->generate_check_box('ougc_profilelogo_canupload', 1, $lang->can_upload_ougc_profilelogo, array("checked" => $mybb->input['ougc_profilelogo_canupload'])),
			"{$lang->profile_pic_size}<br /><small>{$lang->profile_pic_size_desc}</small><br />".$form->generate_text_box('ougc_profilelogo_maxsize', $mybb->input['ougc_profilelogo_maxsize'], array('id' => 'ougc_profilelogo_maxsize', 'class' => 'field50')). "KB",
			"{$lang->profile_pic_dims}<br /><small>{$lang->profile_pic_dims_desc}</small><br />".$form->generate_text_box('ougc_profilelogo_maxdimensions', $mybb->input['ougc_profilelogo_maxdimensions'], array('id' => 'ougc_profilelogo_maxdimensions', 'class' => 'field')),
			"{$lang->profile_pic_mindims}<br /><small>{$lang->profile_pic_mindims_desc}</small><br />".$form->generate_text_box('ougc_profilelogo_mindimensions', $mybb->input['ougc_profilelogo_mindimensions'], array('id' => 'ougc_profilelogo_mindimensions', 'class' => 'field'))
		);
		$form_container->output_row($lang->profile_logo, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $ougc_profilelogo_options)."</div>");
	}
}

function ougc_profilelogo_usergroup_permission_commit()
{
	global $db, $mybb, $updated_group;
	$updated_group['ougc_profilelogo_canuse'] = (int)$mybb->input['ougc_profilelogo_canuse'];
	$updated_group['ougc_profilelogo_canupload'] = (int)$mybb->input['ougc_profilelogo_canupload'];
	$updated_group['ougc_profilelogo_maxsize'] = (int)$mybb->input['ougc_profilelogo_maxsize'];
	$updated_group['ougc_profilelogo_maxdimensions'] = $db->escape_string($mybb->input['ougc_profilelogo_maxdimensions']);
	$updated_group['ougc_profilelogo_mindimensions'] = $db->escape_string($mybb->input['ougc_profilelogo_mindimensions']);
}

// Check to see if CHMOD for profile logos is writable
function ougc_profilelogo_chmod()
{
	global $mybb, $lang, $table, $message_profile_logo;
	isset($lang->setting_group_ougc_profilelogo) or $lang->load("ougc_profilelogo", true);

	if(is_writable('../'.$mybb->settings['ougc_profilelogo_uploadpath']))
	{
		$message_profile_logo = "<span style=\"color: green;\">{$lang->writable}</span>";
	}
	else
	{
		$message_profile_logo = "<strong><span style=\"color: #C00\">{$lang->not_writable}</span></strong><br />{$lang->please_chmod_777}";
		++$errors;
	}

	$table->construct_cell("<strong>{$lang->profile_logo_upload_dir}</strong>");
	$table->construct_cell($mybb->settings['ougc_profilelogo_uploadpath']);
	$table->construct_cell($message_profile_logo);
	$table->construct_row();
}