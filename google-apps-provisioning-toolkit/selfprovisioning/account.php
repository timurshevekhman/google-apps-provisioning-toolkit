<?php
#============================================================================
#
#	Copyright 2007 Google Inc.
#
#	Licensed under the Apache License, Version 2.0 (the "License");
#	you may not use this file except in compliance with the License.
#
#	You may obtain a copy of the License at
#	http://www.apache.org/licenses/LICENSE-2.0
#
#	Unless required by applicable law or agreed to in writing, software
#	distributed under the License is distributed on an "AS IS" BASIS,
#	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#	See the License for the specific language governing permissions and
#	limitations under the License.
#
#	Originally developed by SADA Systems, Inc. http://www.sadasystems.com
#
#============================================================================
error_reporting(E_ERROR);

session_start();
if (!isset($_SESSION["gUser"])){ 
	header("Location: index.php");
	exit;
}

if (isset($_SESSION["gUserName"])){ 
	header("Location: confirmation.php");
	exit;
}

$users = $_SESSION["gUser"];
require('../admin/config.php');
require('acctfunctions.php');
require('php-captcha.inc.php');	

$cMsg = "";
$cCaptcha_Error = "";

$cfile = fopen($log_file,"r");

while (($line = fgets($cfile)) != false ) {
	$line = trim($line);
	$log_user = explode("|",$line);
	//example log line: username|first|last|gusername|password|IP|date time
	if ($log_user[0] == $users[0]['username']) {
		$account_exists = true;
		break;
	}
}

$cIP = $_SERVER["REMOTE_ADDR"];
$cDateTime = date("m-d-Y H:i:s");

$cFName = "";
$cLName = "";
$cUser =  "";

if (isset($users[0]['firstname']))
	$cFName = $users[0]['firstname'];
if (isset($users[0]['lastname']))
	$cLName = $users[0]['lastname'];

if (isset($users[0]['firstname']) && isset($users[0]['lastname']))	
	$cUser = $users[0]['firstname'] . "." . $users[0]['lastname'];
	
if ($cUser == "" || $cUser == ".")
	$cUser = $users[0]['username'];

if(isset($_POST["gname"])) {
	
	$cFName = $_POST["firstname"];
	$cLName = $_POST["lastname"];
	$cUser = $_POST["gname"];
	$cPword = $_POST["gpassword2"];
		
	if (PhpCaptcha::Validate($_POST["user_code"])) {
		$cCaptcha_Error = "OK";
	} else {
		$cCaptcha_Error = "Invalid code entered";
	}
	
	if ($cCaptcha_Error == "OK") {
		if (!isset($_SESSION["auth_token"])) {
			get_token($domain, $admin, $password);
		}
		
		//$cFName = $users[0]['firstname'];
		//$cLName = $users[0]['lastname'];
		$cNic = "";
		$auth_token = $_SESSION["auth_token"];
	
		$Type = "create";
	
		$cResult = API_Post($Type, $cFName, $cLName, $cUser, $cPword, $cNic, $auth_token, $domain);
		 //$cMsg = $cResult;
		
		if ($cResult == "OK") {
				
			$data = $users[0]['username'] . "|" . $users[0]['firstname'] . "|" . $users[0]['lastname'] . "|" . $cUser . "|" . $cPword . "|" . $cIP . "|" . $cDateTime . "\r\n";
	
			if (!$file_handle = fopen($log_file,"a+")) { echo "Cannot open log file"; }  
			if (!fwrite($file_handle, $data)) { 
				echo "Cannot write to file"; 
				fclose($file_handle);
			}else {
				fclose($file_handle);
			} 
			
			$_SESSION["gUserName"] = $cUser;
			header("Location: confirmation.php");
		}
		if ($cResult == "1300" || $cResult == "1100" || $cResult == 1101 || $cResult == 1302) {
			$cMsg = "<br />The username you entered is already in use. Please choose a different username.";
		}
		else {
			$error_msg = error_dict($cResult);
			$data =  $error_msg. "|" . $users[0]['username'] . "|" . $cIP . "|" . $cDateTime . "\r\n";
			
			if (!$file_handle = fopen($log_errors,"a+")) { echo "Cannot open log file"; }  
			if (!fwrite($file_handle, $data)) { 
				echo "Cannot write to file"; 
				fclose($file_handle);
			}else {
				fclose($file_handle);
			} 
			
			$cMsg = "An error occurred while processing your request. Please contact your system administrator.  Error code: " . $cResult;
		}
	}
} 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SADA Systems, Inc.</title>
<link href="../css/toolkit_styles.css" rel="stylesheet" type="text/css" />
<script language="JavaScript" type="text/javascript">
<!--
var c=0;
var t;

function timedCount()
{
	c += 2;
	var d = document.getElementById('d_bar');
	d.style.width = c+'px';
	if (c < 500) {
		t=setTimeout("timedCount()", c*2);
	} else { 
		clearTimeout(t);
	}
}

function check_form(){
	var cErr = 0;
	var cErrMsg = '';
	if (document.form.firstname.value.length < 1) {
		cErr += 1;
		cErrMsg += 'A first name is required.\r\n';
	}
	if (document.form.lastname.value.length < 1) {
		cErr += 1;
		cErrMsg += 'A last name is required.\r\n';
	}
	if (document.form.gname.value.length < 1) {
		cErr += 1;
		cErrMsg += 'A username is required.\r\n';
	}
	if (document.form.gpassword.value.length < 6) {
		cErr += 1;
		cErrMsg += 'Password must be at least 6 characters in length.\r\n';
	}
	if (document.form.gpassword.value != document.form.gpassword2.value) {
		cErr += 1;
		cErrMsg += 'Passwords do not match.\r\n';
	}

	if (cErr > 0) {
		alert(cErrMsg);
		return false;
	}
	c=0;
	timedCount();
	var d_n = document.getElementById('notice');	
	var d_b = document.getElementById('activate');	
	d_n.style.display = '';
	d_b.style.display = 'none';
	
	return true;
	
}
-->
</script>
</head>
<body>
<h2>Create a Google Apps Account</h2>
<?php 
if (isset($account_exists)) {  
?>
<p>Our records indicate that you have a Google Apps account.  </p>
<p>Username: <strong><?php echo $log_user[3]; ?></strong><br />
Email: <strong><?php echo $log_user[3] . "@". $domain ; ?></strong>
<p>To sign in to your account, go to <a href="http://mail.google.com/a/<?php echo $domain; ?>">http://mail.google.com/a/<?php echo $domain; ?></a>.</p>
<?php
}
else {
?>
<span style="color:#FF0000; font-weight:bold"><?php echo $cMsg; ?></span>
<form id="form" name="form" method="post" action="<?php echo $_SERVER["PHP_SELF"] ?>" onsubmit="return check_form(this);">
<table cellpadding="0" cellspacing="0" class="tbl_acct">
	 <tr>
    	<td colspan="2"></td>
    </tr>
	<tr>
    	<td><strong>First name:</strong></td>
      <td><input name="firstname" type="text" value="<?php  echo $cFName; ?>" /></td>
    </tr>
    <tr>
    	<td><strong>Last name:</strong></td>
      <td><input name="lastname" type="text" value="<?php echo $cLName; ?>" /></td>
    </tr>
    <tr>
    	<td><strong>Desired username:</strong></td>
      <td><input name="gname" type="text" value="<?php echo $cUser; ?>" /><?php echo "@". $domain; ?></td>
    </tr>
    <tr>
    	<td></td>
        <td style="padding-top: 0px;">Usernames may contain letters (a-z), numbers (0-9), dashes (-), underscores (_), and periods (.).</td>
    </tr>
<tr>
    	<td nowrap="nowrap"><strong>Choose a password:</strong></td>
    <td><input name="gpassword" type="password" /></td>
    </tr>
    <tr>
    	<td></td>
        <td style="padding-top: 0px;">Passwords may contain any combination of characters, with a minimum of 6.
          <p class="open"><b>Tips for creating a secure password:</b> </p>
          <ul>
            <li> Include a minimum of six (6) characters. </li>
            <li> Include numbers. </li>
            <li> Mix capital and lowercase letters. </li>
            <li> Consider using a passphrase. </li>
            <li> Include phonetic replacements, such as 'Luv2Laf' for 'Love to Laugh'. </li>
          </ul>
          <p> <b>Remember:</b> </p>
          <ul>
            <li> Don't use a password that is listed as an example of how to pick a good password. </li>
            <li> Don't use a password that contains personal information (name, birth date, etc.). </li>
            <li> Don't use words or acronyms that can be found in a dictionary. </li>
            <li> Don't use keyboard patterns (asdf) or sequential numbers (1234). </li>
            <li> Don't make your password all numbers, uppercase letters, or lowercase letters. </li>
            <li> Don't use repeating characters (aa11). </li>
          </ul> </td>
    </tr>
<tr>
    	<td><strong>Re-enter password:</strong></td>
    <td><input name="gpassword2" type="password" /></td>
    </tr>
    <tr>
    	<td></td>
        <td></td>
    </tr>
    <tr>
      <td></td>
      <td>
     <img src="captcha.php" width="200" height="60" alt="Visual CAPTCHA" />      </td>
    </tr>
    <tr>
      <td></td>
      <td>Type the characters you see in the picture above.<br />
      <input name="user_code" type="text" /> <?php echo $cCaptcha_Error ?></td>
    </tr>
    <tr>
    	<td></td>
        <td>
        <div id="notice" style="display:none">
<div style="border: 1px solid #666; width: 503px; height: 20px; background-image:url(../img/progress_bg.gif); background-repeat: no-repeat;"><div id="d_bar" style=" background-image:url(../img/bar.gif); background-repeat: no-repeat; width:100px; height: 20px;"></div></div><br />
<p style="color:#000099; font-weight: bold;">Your account is now being activated. Please wait for a confirmation screen with your new account name before leaving this page. If you close or refresh your browser during this process your account may not be completely activated.  Thank you for your patience.</p>
</div>
<div id="activate" style="display: block;">
	<input type="submit" value="Continue" name="Submit" /> 
</div></td>
    </tr>
</table>
</form>
<?php
}
?>
</body>
</html>
