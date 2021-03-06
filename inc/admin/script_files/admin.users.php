<?php
/****************************************************************************/
/*  						< MangosWeb v4 >  								*/
/*              Copyright (C) <2017> <Mistvale.com>   		                */
/*					  < http://www.mistvale.com >							*/
/*																			*/
/*			Original MangosWeb Enhanced (C) 2010-2011 KeysWow				*/
/*			Original MangosWeb (C) 2007, Sasha, Nafe, TGM, Peec				*/
/****************************************************************************/

//========================//
if(INCLUDED!==true) {
	echo "Not Included!"; exit;
}
//=======================//

// For the search bar
if(isset($_POST['action']))
{
	if($_POST['action'] == 'sort')
	{
		redirect('?p=admin&sub=users&sortby='.$_POST['sortby'],1);
	}
}

//====== Pagination Code ======/
$limit = 50; // Sets how many results shown per page	
if(!isset($_GET['page']) || (!is_numeric($_GET['page'])))
{
    $page = 1;
} 
else 
{
	$page = $_GET['page'];
}
$limitvalue = $page * $limit - ($limit);	// Ex: (2 * 25) - 25 = 25 <- data starts at 25

//===== Filter ==========// 
if($_GET['sortby'] && preg_match("/[a-z]/", $_GET['sortby']))
{
	$filter = "WHERE `username` LIKE '" . $_GET['sortby'] . "%'";
}
elseif($_GET['sortby'] == 1)
{
	$filter = "WHERE `username` REGEXP '^[^A-Za-z]'";
}
else
{
	$filter = '';
}
	
// Get all users
$getusers = $DB->select("SELECT * FROM account $filter ORDER BY `username` ASC LIMIT $limitvalue, $limit;");
$totalrows = $DB->num_rows("SELECT id FROM `account` $filter");

//===== Start of functions =====/

// Change password admin style :p
// Change Pass. Buffer function for the SDL
function changePass()
{
	global $lang, $Account;
	$newpass = trim($_POST['password']);
	if(strlen($newpass)>3)
	{
		if($Account->setPassword($_GET['id'], $newpass) == TRUE)
		{
			output_message('success','<b>Password set successfully! Please wait while you are redirected...</b>
			<meta http-equiv=refresh content="3;url=?p=admin&sub=users&id='.htmlspecialchars($_GET['id']).'">');
		}
		else
		{
			output_message('error', '<b>Change Password Failed!');
		}
	}
	else
	{
		output_message('error','<b>'.$lang['change_pass_short'].'</b>
		<meta http-equiv=refresh content="3;url=?p=admin&sub=users&id='.htmlspecialchars($_GET['id']).'">');
	}
}

function changeDetails()
{
	global $lang, $Account;
	$success = 0;
	
	if($Account->setEmail($_GET['id'], $_POST['email']) == TRUE)
	{
		$success++;
	}
	else
	{
		output_message('error', 'Unable to set email!');
	}
	
	if($Account->setLock($_GET['id'], $_POST['locked']) == TRUE)
	{
		$success++;
	}
	else
	{
		output_message('error', 'Unable to set the account lock!');
	}
	
	if($Account->setExpansion($_GET['id'], $_POST['expansion']) == TRUE)
	{
		$success++;
	}
	else
	{
		output_message('error', 'Unable to set the expansion!');
	}
	
	if($success == 3)
	{
		output_message('success', 'Users details updated successfully! Redirecting... 
			<meta http-equiv=refresh content="3;url=?p=admin&sub=users&id='.htmlspecialchars($_GET['id']).'">');
		return TRUE;
	}
}

function editUser()
{
	global $DB, $user;
	if($user['account_level'] <= $_POST['account_level'] && $user['account_level'] != '4')
	{
		output_message('error', 'You do not have permission to make this change. You cannot raise someone else\'s account level equal or higher then your own.');
	}
	else
	{
		$DB->query("UPDATE `mw_account_extend` SET 
			`account_level`='".$_POST['account_level']."',
			`theme`='".$_POST['theme']."',
			`web_points`='".$_POST['web_points']."',
			`total_donations`='".$_POST['total_donations']."'
		  WHERE `account_id`='".$_GET['id']."'
		");
		output_message('success','User Updated Successfully! Please wait while you are redirected...
			<meta http-equiv=refresh content="3;url=?p=admin&sub=users&id='.htmlspecialchars($_GET['id']).'">');
	}
}

// Unban user
function unBan($unbanid) 
{
	global $DB, $Account;
	if($Account->unbanAccount($unbanid) == TRUE)
	{
		output_message('success','Success. Account #'.$unbanid.' Successfully Un-Banned!
			Please wait while you are redirected... <meta http-equiv=refresh content="3;url=?p=admin&sub=users&id='.htmlspecialchars($_GET['id']).'"');
	}	
}

// Delete user's account
function deleteUser($did)
{
	global $DB;

        $DB->query("DELETE FROM `account` WHERE `id`='$did'");

        output_message('success', 'Success. Account successfully deleted.<meta http-equiv=refresh content="3;url=?p=admin&sub=users">');
}

// Ban user
function banUser($bannid, $banreason, $banduration, $banip) 
{
	global $DB, $user, $Account;
	if(!$banreason) 
	{
		$banreason = "Not Specified";
	}
	if($Account->banAccount($bannid, $banreason, $banduration, $user['username'], $banip) == TRUE)
	{
		output_message('success','Success. Account #'.$bannid.' Successfully banned. Reason: '.$banreason.'');
	}
}


// Show ban form is used to input a Ban reason, before acutally banning
function showBanForm($banid) 
{
	global $DB;
	$unme = $DB->selectCell("SELECT username FROM account WHERE id='".$banid."'");
	?>
		<div class="content">	
			<div class="content-header">
				<h4><a href="?p=admin">Main Menu</a> / <a href="?p=admin&sub=users">Manage Users</a> / <a href="?p=admin&sub=users&id=<?= htmlspecialchars($_GET['id']);?>"><?= $unme;?></a> / Ban</h4>
			</div> <!-- .content-header -->				
		<div class=\"main-content\">
	<?php
	if(isset($_POST['ban_reason'])) 
	{
		banUser($_GET['id'], $_POST['ban_reason'], $_POST['ban_duration'], isset($_POST['ban_ip']));
	}
	?>
		<form method="POST" name="adminform" class="form label-inline">
			<table>
				<thead>
					<th><center><b>Ban Account #<?= $banid;?> (<?= $unme;?>)</b></center></th>
				</thead>
			</table>
			<br />
			<div class='field'>
				<label for='ban_reason'>Ban Reason: </label>
				<input id='ban_reason' name='ban_reason' size='20' type='text' class='large' />
			</div>
			<div class='field'>
				<label for='ban_duration'>Ban Duration: </label>
				<select id='ban_duration' name='ban_duration' class='large'>
					<option value="0" selected="selected">Indefinite</option>
					<option value="3600">1 hour</option>
					<option value="14400">4 hours</option>
					<option value="28800">8 hours</option>
					<option value="86400">1 day</option>
					<option value="172800">2 days</option>
					<option value="259200">3 days</option>
					<option value="345600">4 days</option>
					<option value="640800">7 days</option>
				</select>
			</div>
			<div class='field'>
				<label for='ban_ip'>Ban IP Address: </label>
				<input id='ban_ip' name='ban_ip' type='checkbox' value='1' />
			</div>
			<div class="buttonrow-border">								
				<center><button><span>Ban User</span></button></center>			
			</div>

		</form>
	</div>
	<?php
}
?>
