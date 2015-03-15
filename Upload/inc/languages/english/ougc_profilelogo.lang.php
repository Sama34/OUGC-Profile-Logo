<?php

/***************************************************************************
 *
 *   OUGC Profile Logo plugin (/inc/languages/english/ougc_profilelogo.lang.php)
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

$l['setting_group_ougc_profilelogo'] = "OUGC Profile Logo";
$l['setting_group_ougc_profilelogo_desc'] = "Allows users to upload a picture to display in their profile.";

$l['profile_logo'] = "Profile logo";
$l['users_ougc_profilelogo'] = "{1}'s profile Logo";
$l['changing_ougc_profilelogo'] = "<a href=\"usercp.php?action=ougc_profilelogo\">Changing Profile Logo</a>";
$l['remove_profile_logo'] = "Remove user's profile logo?";
$l['can_use_ougc_profilelogo'] = "Can use profile logo?";
$l['can_upload_ougc_profilelogo'] = "Can upload profile logo?";
$l['profile_pic_size'] = "Maximum File Size:";
$l['profile_pic_size_desc'] = "Maximum file size of an uploaded profile logo in kilobytes. If set to 0, there is no limit.";
$l['profile_pic_dims'] = "Maximum Dimensions:";
$l['profile_pic_dims_desc'] = "Maximum dimensions a profile logo can be, in the format of width<strong>x</strong>height. If this is left blank then there will be no dimension restriction.";
$l['profile_pic_mindims'] = "Minimum Dimensions:";
$l['profile_pic_mindims_desc'] = "Minimum dimensions a profile logo can be, in the format of width<strong>x</strong>height. If this is left blank then there will be no dimension restriction.";

$l['profile_logo_upload_dir'] = "Profile Logo Uploads Directory";

$l['nav_usercp'] = "User Control Panel";
$l['ucp_nav_change_ougc_profilelogo'] = "Change Profile Logo";
$l['change_ougc_profilelogoture'] = "Change Profile Logo";
$l['change_logo'] = "Change Logo";
$l['remove_logo'] = "Remove Logo";
$l['ougc_profilelogo_url'] = "Profile Logo URL:";
$l['ougc_profilelogo_url_note'] = "Enter the URL of a profile logo on the internet.";
$l['ougc_profilelogo_description'] = "Profile Logo Description:";
$l['ougc_profilelogo_description_note'] = "(Optional) Add a brief description of your profile logo.";
$l['ougc_profilelogo_upload'] = "Upload Profile Logo:";
$l['ougc_profilelogo_upload_note'] = "Choose a profile logo on your local computer to upload.";
$l['ougc_profilelogo_note'] = "A profile logo is a small identifying logo shown in a user's profile.";
$l['ougc_profilelogo_note_dimensions'] = "The maximum dimensions for profile logos are: {1}x{2} pixels.";
$l['ougc_profilelogo_note_mindimensions'] = "The minimum dimensions for profile logos are: {1}x{2} pixels.";
$l['ougc_profilelogo_note_size'] = "The maximum file size for profile logos is {1}.";
$l['custom_profile_pic'] = "Custom Profile Logo";
$l['already_uploaded_ougc_profilelogo'] = "You are currently using an uploaded profile logo. If you upload another one, your old one will be deleted.";
$l['ougc_profilelogo_auto_resize_note'] = "If your profile logo is too large, it will automatically be resized.";
$l['ougc_profilelogo_auto_resize_option'] = "Try to resize my profile logo if it is too large.";
$l['redirect_ougc_profilelogoupdated'] = "Your profile logo has been changed successfully.<br />You will now be returned to your User CP.";
$l['using_remote_ougc_profilelogo'] = "You are currently using an remote profile logo.";
$l['profile_logo_mine'] = "This is your Profile Logo";

$l['error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['error_ougc_profilelogotype'] = "Invalid file type. An uploaded profile logo must be in GIF, JPEG, or PNG format.";
$l['error_invalidougc_profilelogourl'] = "The URL you entered for your profile logo does not appear to be valid. Please ensure you enter a valid URL.";
$l['error_ougc_profilelogotoobig'] = "Sorry but we cannot change your profile logo as the new logo you specified is too big. The maximum dimensions are {1}x{2} (width x height)";
$l['error_ougc_profilelogotoosmall'] = "Sorry but we cannot change your profile logo as the new logo you specified is too small. The minimum dimensions are {1}x{2} (width x height)";
$l['error_ougc_profilelogoresizefailed'] = "Your profile logo was unable to be resized so that it is within the required dimensions.";
$l['error_ougc_profilelogouserresize'] = "You can also try checking the 'attempt to resize my profile logo' check box and uploading the same logo again.";
$l['error_uploadsize'] = "The size of the uploaded file is too large.";
$l['error_descriptiontoobig'] = "Your profile logo description is too long. The maximum length for descriptions is 255 characters.";