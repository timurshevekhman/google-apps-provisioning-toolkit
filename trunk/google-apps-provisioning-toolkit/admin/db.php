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

error_reporting(E_ALL);
set_time_limit(200000);
$cMsg = "";
session_start();
//Unset session if they exist
unset($_SESSION['filename']);
unset($_SESSION['file_log']);

//import config file
require( 'config.php' );
require('dbfunctions.php');

function db_preview() {
	$preview = 1;
	$db_users = get_users($preview);
	$display_count = count($db_users);
	$cTable = "<table class=\"sample\">\r\n";
	$cTable .= "<tr>\r\n";
	$cTable.="\t<th>&nbsp</th>";
	$cTable.="<th>username</th>";
	$cTable.="<th>firstname</th>";
	$cTable.="<th>lastname</th>";
	$cTable.="<th>password</th>\r\n";
	$cTable .= "</tr>\r\n";
	for ($i = 0; $i < $display_count; $i++) {
		$cTable .= "<tr>\r\n";
		$cTable.="\t<td>". ($i+1) ."</td>";
		$cTable.="<td>". $db_users[$i]['username'] ."</td>";
		$cTable.="<td>". $db_users[$i]['firstname'] ."</td>";
		$cTable.="<td>". $db_users[$i]['lastname'] ."</td>";
		$cTable.="<td>". $db_users[$i]['password'] ."</td>\r\n";
		$cTable .= "</tr>\r\n";
	}
	$cTable .= "</table>\r\n";
	return $cTable;
}

if (isset($_POST['submitform'])) {

	$users = get_users();
	
	$rootfile = $dir_upload . md5(time()); 
	$filename = $rootfile . '.csv';
	//batch file
	$batchfile = $rootfile . "_batch.csv";
	
	//$file = fopen($filename,"w");
	$file = fopen($filename,"x");
	
	$contents = '';

	$contents.="\"username\",";
	$contents.="\"first name\",";
	$contents.="\"last name\",";
	$contents.="\"password\"\n";

	for ($i = 0; $i < count($users); $i++) {
		$contents.="\"". $users[$i]['username'] ."\",";
		$contents.="\"". $users[$i]['firstname'] ."\",";;
		$contents.="\"". $users[$i]['lastname'] ."\",";;
		$contents.="\"". $users[$i]['password'] ."\"\n";
	}

	fwrite($file,$contents);
	if (fclose($file) == true) {
		#header('location: importpreviewdb.php?filename=' . $dir_upload . $filename);
		//Grab type of batch process
		$action = $_POST['action'];
		//Generate command to execute python script
		$command = "python domain_sync.py --domain $domain --admin $admin --password $password --file $filename --action $action";
		if ($allow_account_deletion == 'yes') {
				$command .= " --permanentdel 1";
		}

			exec($command, $resp);
			if ($resp && $resp[0] == "OK") {
				$_SESSION['filename'] = $batchfile;
				header("Location: importpreview.php");  
			}
			else {
				//display python script errors
				echo "ERROR: <br />";
				echo "<blockquote>";
				foreach ($resp as $msg){
					echo $msg . "<br />";
				} 
				echo "</blockquote>";
			}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>GoogleApps Provisioning Toolkit</title>
<link href="../css/toolkit_styles.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
var c=0;
var t;

//Progress bar function
function bar_animation(bar)
{
	c += 2;
	var d = document.getElementById(bar); //Progress bar
	d.style.width = c+'px'; //Progress bar width
	if (c < 500) {
		t=setTimeout('bar_animation(bar)', c*.5);  //Increase progress bar width
	} else { 
		clearTimeout(t);  //Stop progress bar
	}
}

function progress() {
	c=0;
	bar_animation(bar='bar');  //Progress bar
	var d_n = document.getElementById('notice');	
	var d_b = document.getElementById('activate');	
	d_n.style.display = '';  //Display progress bar and message
	d_b.style.display = 'none';  //hide button
}

</script>
</head>

<body>
<h2>Google Apps Provisioning Toolkit</h2>

<?php
echo "<p>";
echo "Selected DB Type: <strong>" . DB_TYPE . "</strong><br />";

if (DB_TYPE == 'mysql' || DB_TYPE == 'mssql') {
	echo "Server: <strong>" . DB_SERVER . "</strong><br />" ;
	echo "Database: <strong>" . DB_NAME . "</strong><br />";
	echo "Table: <strong>" . DB_TABLE . "</strong><br />";
}
if (DB_TYPE == 'ldap') {
	echo "Server: <strong>" . LDAP_SERVER . "</strong><br />" ;
	echo "Base DN: <strong>" . LDAP_BASE_DN . "</strong><br />";
	echo "Filter: <strong>" . LDAP_FILTER . "</strong><br />";
}
echo "</p>";

echo db_preview();
?>
<br />
<br />

<form name="import" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" onsubmit="progress();"><input type="hidden" name="submitform" value="true">
<table width="100%" border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td valign="top">1.</td>
	<td valign="top"><strong>Review  table preview above</strong>
	  <p> Above is a preview of the table you have selected to employ during this batch process.  Please make sure that the fields username, first name, last name and password are correctly mapped. If anything appears to be incorrect please revise the configuration file and reload this page.</p>
	  <p>&nbsp;</p></td>
  </tr>
  <tr>
    <td valign="top">2.</td>
    <td valign="top"><strong>Choose an action</strong>
    <p>
      <input name="action" type="radio" id="action" value="add" checked="checked" />
      Create user accounts only<br />
      <input type="radio" name="action" id="action" value="addupdate" />
      Create user accounts and update existing accounts<br />
      <input type="radio" name="action" id="action" value="update" />
      Update accounts only<br />
      <input type="radio" name="action" id="action" value="sync" />
      Synchronize - create user accounts, update existing accounts and delete/suspend accounts that are not present in the file<br />
      <input type="radio" name="action" id="action" value="delete" />
      Delete/suspend user accounts only</p>

    <div id="notice" style="display:none">
    <div style="border: 1px solid #666; width: 503px; height: 20px; background-image:url(../img/progress_bg.gif); background-repeat: no-repeat;">
        <div id="bar" style=" background-image:url(../img/bar.gif); background-repeat: no-repeat; width:100px; height: 20px;"></div>
    </div>
	<p style="color:#000099; font-weight: bold;">Uploading...</p>
</div>
<div id="activate" style="display: block;">
      <input name="continue" type="submit" value="Continue" />
      <br />
</div></td>
  </tr>
  
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
</table>
</form>
</body>
</html>
