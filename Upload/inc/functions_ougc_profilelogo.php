<?php

/***************************************************************************
 *
 *   OUGC Profile Logo plugin (/inc/functions_ougc_profilelogo.php)
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

/**
 * Remove any matching profile pic for a specific user ID
 *
 * @param int The user ID
 * @param string A file name to be excluded from the removal
 */
function remove_ougc_profilelogo($uid, $exclude="")
{
	global $mybb;

	if(defined('IN_ADMINCP'))
	{
		$ougc_profilelogopath = '../'.$mybb->settings['ougc_profilelogo_uploadpath'];
	}
	else
	{
		$ougc_profilelogopath = $mybb->settings['ougc_profilelogo_uploadpath'];
	}

	$dir = opendir($ougc_profilelogopath);
	if($dir)
	{
		while($file = @readdir($dir))
		{
			if(preg_match("#ougc_profilelogo_".$uid."\.#", $file) && is_file($ougc_profilelogopath."/".$file) && $file != $exclude)
			{
				@unlink($ougc_profilelogopath."/".$file);
			}
		}

		@closedir($dir);
	}
}

/**
 * Upload a new profile pic in to the file system
 *
 * @param srray incoming FILE array, if we have one - otherwise takes $_FILES['ougc_profilelogoupload']
 * @param string User ID this profile pic is being uploaded for, if not the current user
 * @return array Array of errors if any, otherwise filename of successful.
 */
function upload_ougc_profilelogo($ougc_profilelogo=array(), $uid=0)
{
	global $db, $mybb, $lang;

	if(!$uid)
	{
		$uid = $mybb->user['uid'];
	}

	if(!$ougc_profilelogo['name'] || !$ougc_profilelogo['tmp_name'])
	{
		$ougc_profilelogo = $_FILES['ougc_profilelogoupload'];
	}

	if(!is_uploaded_file($ougc_profilelogo['tmp_name']))
	{
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check we have a valid extension
	$ext = get_extension(my_strtolower($ougc_profilelogo['name']));
	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) 
	{
		$ret['error'] = $lang->error_ougc_profilelogotype;
		return $ret;
	}

	if(defined('IN_ADMINCP'))
	{
		$ougc_profilelogopath = '../'.$mybb->settings['ougc_profilelogo_uploadpath'];
		$lang->load("messages", true);
	}
	else
	{
		$ougc_profilelogopath = $mybb->settings['ougc_profilelogo_uploadpath'];
	}

	$filename = "ougc_profilelogo_".$uid.".".$ext;
	$file = upload_ougc_profilelogofile($ougc_profilelogo, $ougc_profilelogopath, $filename);
	if($file['error'])
	{
		@unlink($ougc_profilelogopath."/".$filename);		
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}	

	// Lets just double check that it exists
	if(!file_exists($ougc_profilelogopath."/".$filename))
	{
		$ret['error'] = $lang->error_uploadfailed;
		@unlink($ougc_profilelogopath."/".$filename);
		return $ret;
	}

	// Check if this is a valid logo or not
	$img_dimensions = @getimagesize($ougc_profilelogopath."/".$filename);
	if(!is_array($img_dimensions))
	{
		@unlink($ougc_profilelogopath."/".$filename);
		$ret['error'] = $lang->error_uploadfailed;
		return $ret;
	}

	// Check profile logo dimensions
	if($mybb->usergroup['ougc_profilelogo_maxdimensions'] != '')
	{
		list($maxwidth, $maxheight) = @explode("x", $mybb->usergroup['ougc_profilelogo_maxdimensions']);

		if(($maxwidth && $img_dimensions[0] > $maxwidth) || ($maxheight && $img_dimensions[1] > $maxheight))
		{
			// Automatic resizing enabled?
			if($mybb->settings['ougc_profilelogo_resizing'] == "auto" || ($mybb->settings['ougc_profilelogo_resizing'] == "user" && $mybb->input['auto_resize'] == 1))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$thumbnail = generate_thumbnail($ougc_profilelogopath."/".$filename, $ougc_profilelogopath, $filename, $maxheight, $maxwidth);
				if(!$thumbnail['filename'])
				{
					$ret['error'] = $lang->sprintf($lang->error_ougc_profilelogotoobig, $maxwidth, $maxheight);
					$ret['error'] .= "<br /><br />".$lang->error_ougc_profilelogoresizefailed;
					@unlink($ougc_profilelogopath."/".$filename);
					return $ret;				
				}
				else
				{
					// Reset filesize
					$ougc_profilelogo['size'] = filesize($ougc_profilelogopath."/".$filename);
					// Reset dimensions
					$img_dimensions = @getimagesize($ougc_profilelogopath."/".$filename);
				}
			}
			else
			{
				$ret['error'] = $lang->sprintf($lang->error_ougc_profilelogotoobig, $maxwidth, $maxheight);
				if($mybb->settings['ougc_profilelogo_resizing'] == "user")
				{
					$ret['error'] .= "<br /><br />".$lang->error_ougc_profilelogouserresize;
				}
				@unlink($ougc_profilelogopath."/".$filename);
				return $ret;
			}			
		}
	}
	if($mybb->usergroup['ougc_profilelogo_mindimensions'] != '')
	{
		list($minwidth, $minheight) = @explode("x", $mybb->usergroup['ougc_profilelogo_mindimensions']);

		if(($minwidth && $img_dimensions[0] < $minwidth) || ($minheight && $img_dimensions[1] < $minheight))
		{
			$ret['error'] = $lang->sprintf($lang->error_ougc_profilelogotoosmall, $minwidth, $minheight);
			@unlink($ougc_profilelogopath."/".$filename);
			return $ret;	
		}
	}

	// Next check the file size
	if($ougc_profilelogo['size'] > ($mybb->usergroup['ougc_profilelogo_maxsize']*1024) && $mybb->usergroup['ougc_profilelogo_maxsize'] > 0)
	{
		@unlink($ougc_profilelogopath."/".$filename);
		$ret['error'] = $lang->error_uploadsize;
		return $ret;
	}	

	// Check a list of known MIME types to establish what kind of profile logo we're uploading
	switch(my_strtolower($ougc_profilelogo['type']))
	{
		case "image/gif":
			$img_type =  1;
			break;
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
			$img_type = 2;
			break;
		case "image/png":
		case "image/x-png":
			$img_type = 3;
			break;
		default:
			$img_type = 0;
	}

	// Check if the uploaded file type matches the correct logo type (returned by getimagesize)
	if($img_dimensions[2] != $img_type || $img_type == 0)
	{
		$ret['error'] = $lang->error_uploadfailed;
		@unlink($ougc_profilelogopath."/".$filename);
		return $ret;		
	}
	// Everything is okay so lets delete old profile logo for this user
	remove_ougc_profilelogo($uid, $filename);

	$ret = array(
		"ougc_profilelogo" => $mybb->settings['ougc_profilelogo_uploadpath']."/".$filename,
		"width" => intval($img_dimensions[0]),
		"height" => intval($img_dimensions[1])
	);
	return $ret;
}

/**
 * Actually move a file to the uploads directory
 *
 * @param array The PHP $_FILE array for the file
 * @param string The path to save the file in
 * @param string The filename for the file (if blank, current is used)
 */
function upload_ougc_profilelogofile($file, $path, $filename="")
{
	if(empty($file['name']) || $file['name'] == "none" || $file['size'] < 1)
	{
		$upload['error'] = 1;
		return $upload;
	}

	if(!$filename)
	{
		$filename = $file['name'];
	}

	$upload['original_filename'] = preg_replace("#/$#", "", $file['name']); // Make the filename safe
	$filename = preg_replace("#/$#", "", $filename); // Make the filename safe
	$moved = @move_uploaded_file($file['tmp_name'], $path."/".$filename);

	if(!$moved)
	{
		$upload['error'] = 2;
		return $upload;
	}
	@my_chmod($path."/".$filename, '0644');
	$upload['filename'] = $filename;
	$upload['path'] = $path;
	$upload['type'] = $file['type'];
	$upload['size'] = $file['size'];
	return $upload;
}

/**
 * Formats a profile logo to a certain dimension
 *
 * @param string The profile logo file name
 * @param string Dimensions of the profile logo, width x height (e.g. 44|44)
 * @param string The maximum dimensions of the formatted profile logo
 * @return array Information for the formatted profile logo
 */
function format_profile_logo($ougc_profilelogoture, $dimensions = '', $max_dimensions = '')
{
	global $mybb;
	static $ougc_profilelogotures;

	if(!isset($ougc_profilelogotures))
	{
		$ougc_profilelogotures = array();
	}

	if(!$ougc_profilelogoture)
	{
		// Default profile logo
		$ougc_profilelogoture = '';
		$dimensions = '';
	}

	if(isset($ougc_profilelogotures[$ougc_profilelogoture]))
	{
		return $ougc_profilelogotures[$ougc_profilelogoture];
	}

	if(!$max_dimensions)
	{
		$max_dimensions = $mybb->usergroup['ougc_profilelogo_maxdimensions'];
	}

	if($dimensions)
	{
		$dimensions = explode("|", $dimensions);

		if($dimensions[0] && $dimensions[1])
		{
			list($max_width, $max_height) = explode('x', $max_dimensions);

			if($dimensions[0] > $max_width || $dimensions[1] > $max_height)
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$scaled_dimensions = scale_image($dimensions[0], $dimensions[1], $max_width, $max_height);
				$ougc_profilelogoture_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
			}
			else
			{
				$ougc_profilelogoture_width_height = "width=\"{$dimensions[0]}\" height=\"{$dimensions[1]}\"";
			}
		}
	}

	$ougc_profilelogotures[$ougc_profilelogoture] = array(
		'logo' => $mybb->get_asset_url($ougc_profilelogoture),
		'width_height' => $ougc_profilelogoture_width_height
	);

	return $ougc_profilelogotures[$ougc_profilelogoture];
}