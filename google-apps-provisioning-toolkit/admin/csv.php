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

set_time_limit(200000);
$cMsg = "";
session_start();
//Unset session if they exist
unset($_SESSION['filename']);
unset($_SESSION['file_log']);

//import config file
require( 'config.php' );

if (isset($_FILES['file'])){
	//Time stamp
	$cTime = date("Ymd_His");
	$cfilename = str_replace(".csv", "", basename($_FILES['file']['name']));
	$rootfile =  $dir_upload . $cfilename . "_" . $cTime;
	//new file
	$uploadfile = $rootfile . ".csv";
	//batch file
	$batchfile = $rootfile . "_batch.csv";
	//echo $_FILES['file']['type'];
	
	//Check that file is a csv file
	if ($_FILES['file']['type'] == "text/csv" || $_FILES['file']['type'] == "application/csv" || $_FILES['file']['type'] == "application/vnd.ms-excel") {
		if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)){
			//Grab type of batch process
			$action = $_POST['action'];
			//Generate command to execute python script
			$command = "python domain_sync.py --domain $domain --admin $admin --password $password --file $uploadfile --action $action";
			if ($allow_account_deletion == 'yes') {
				$command .= " --permanentdel 1";
			}
			//$command = "python domain_sync.py";
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
		else {
			//error message regarding file upload
			$cMsg = "Error: " . $_FILES['file']['error'];
		}
	}
	else {
		$cMsg = "<p style=\"color: #FF0000;\">Error: File is not a CSV file.  Please select a CSV file to upload. <br /> ";
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

function upload() {
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
<form id="form_upload" name="form_upload" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" onsubmit="upload();">
    <table width="100%" border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td valign="top">1.</td>
    <td valign="top"><strong>Make a list of user accounts</strong>
    <p>You'll need to create a CSV (comma separated value) file with the user account information. Spreadsheet programs like Microsoft Excel make it easy to create and edit CSV files.</p>
    <p>Your CSV file should be formatted as a table and must include a header, or first line, that defines the fields in your table. The headers should be: username, first name, last name, password. </p><br />
    <p style="color: #666; font-style:italic;">Example:</p>
    <table class="sample"> 
      <tbody>  <tr> <th></th> <th>A</th> <th>B</th> <th>C</th> <th>D</th>  </tr> <tr> <th>1</th> <td> <b>username</b> </td> <td> <b>first name</b> </td> <td> <b>last name</b> </td> <td> <b>password</b> </td>  </tr> <tr> <th>2</th> <td>picasso</td> <td>Pablo</td> <td>Picasso</td> <td>59h731</td>  </tr> <tr> <th>3</th> <td>claude.monet</td> <td>Claude</td> <td>Monet</td> <td>6d8945</td>  </tr> <tr> <th>4</th> <td>lilies</td> <td>Georgia</td> <td>O'Keeffe</td> <td>319w56</td>  </tr>  </tbody> </table>
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
      Delete/Suspend user accounts only</p>
    <p><br />
    </p></td>
  </tr>
  <tr>
    <td valign="top">3.</td>
    <td valign="top"><strong>Upload list of user accounts in CSV format</strong></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
<?php echo $cMsg; ?>

<div id="notice" style="display:none">
    <div style="border: 1px solid #666; width: 503px; height: 20px; background-image:url(../img/progress_bg.gif); background-repeat: no-repeat;">
        <div id="bar" style="background-image:url(../img/bar.gif); background-repeat: no-repeat; width:100px; height: 20px;"></div>
    </div>
	<p style="color:#000099; font-weight: bold;">Uploading...</p>
</div>
<div id="activate" style="display: block;">

      <input type="file" name="file" id="file" /><br />
<br />
<input name="Upload" type="submit" value="Continue" />
</div>   
    </td>
  </tr>
</table>
</form>
</body>
</html>
