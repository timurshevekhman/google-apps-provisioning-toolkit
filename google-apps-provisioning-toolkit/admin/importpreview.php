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

//error_reporting(E_ERROR);
set_time_limit(200000);
session_start();

if (!isset($_SESSION['filename'])) {
	header("Location: index.php");  
}

//import config file
require( 'config.php' );


//define file to read
$file = $_SESSION['filename'];

$file_root = str_replace(".csv", "", $file);
//define log file
$file_log = $file_root . "_log.csv";
$_SESSION['file_log'] = $file_log;
//define errors log file
$file_errors = $file_root . "_errors.csv";
$_SESSION['file_errors'] = $file_errors;

if (isset($_POST['import']) && $_POST['import'] = '1') {

	//start formatting output
	$command = "python batch_adduser.py --domain $domain --admin $admin --password $password --file $file";
	//--domain [domain.com] --admin [admin] --password [password] --file [/path/file.csv]
	echo "command: " . $command;
	function system_o($command)
	{
		$i = 0;
		//execute command
		exec($command, $resp);
		if ($resp && $resp[0] == "OK") {
			header("Location: displaylog.php");  
			//var_dump($resp); 
		}
		else {
			echo "ERROR: <br />";
			echo "<blockquote>";
			foreach ($resp as $msg){
				echo $msg . "<br />";
			} 
			echo "</blockquote>";
		}
	}
	
	system_o($command);
}

//function to highlight possible errors
function bad_char($string, $type) {
	$highlight = "";
	$bad_char_array="";
	switch ($type) {
		//username - allow alphanumeric and - . _
		case 0:
			$bad_char_array = "/[^a-zA-Z0-9-._]/";
			break;
		//allow alphanumberic and - . and spaces
		default:
			$bad_char_array = "/[^a-zA-Z0-9-.\/'* ]/";
			break;
	}
	
	$str_n = preg_replace($bad_char_array, "", $string);
	
	if ($type > 2) {
		$str_n = $string;
		
		//check password to be >= 6 characters
		if ($type == 3 && strlen($str_n) < 6 ) {
			$str_n = "short";
		}
	}
	
	if ($string == "" || $string != $str_n)
		$highlight = " class=\"hlight\" ";
		
	return $highlight;
}



//function to display csv file as a table
function csvtable($file){
	
	//Define what you want the seperator to be, this could be new line, (\n) a tab (\t) or any other char, for obvious reasons avoid using chars that will be present in the string.  Id suggest a comma, or semicolon.
	$sep = ",";
	
	//read the file into an array
	$lines = file($file);
	
	//count the array
	$numlines = count($lines);
	
	//explode the first (0) line which will be the header line
	$headers = explode($sep, $lines[0]);
	
	//count the number of headers
	$numheaders = count($headers);
	
	if ($numheaders < 4) {
		echo "<h1>Bad CSV file! Header must contain at least 4 columns: username, first name, last name and password. </h1>";
	}
	
	//Var to output error messages
	$error_message = "";
	
	//start formatting output
	echo "<table class=\"sample\">\r\n<tr>\r\n";
	echo "<th>&nbsp;</th>\r\n";
	//loop through the headers outputting them into their own <TD> cells
	$i = 0;
	while($i<$numheaders){
		$headers = str_replace("\"", "", $headers);
		echo "<th>".trim($headers[$i])."</th>\r\n";
		$i++;
	}
	echo "</tr>\r\n";
	
	$y = 1;
	
	$create_count = 0;
	$update_count = 0;
	$delete_count = 0;
	$suspend_count = 0;

	//Output the data, looping through the number of lines of data and also looping through the number of cells in each line, as this is a dynamic number the header length has to be reread.
	while($y<$numlines){
		$x=0;
		$fields = explode($sep, $lines[$y]);
		$trstyle = "";
		if ($y > 50) {
			$trstyle = " class=\"h\"";
		}
		echo "<tr id=\"". $y . "\"" . $trstyle . ">\r\n";
		echo "<td>$y</td>\r\n";
	
		while($x<$numheaders){
			$field = trim(str_replace("\"", "", $fields[$x]));
			//check field for possible errors
			$fieldstyle = bad_char($field, $x);
			if ($fieldstyle != "") {
				if ($error_message == "") {
					$error_message .= " $y";
				}
				else {
					$error_message .= ", $y";
				}
			}
			echo "<td" . $fieldstyle . ">".$field."</td>\r\n";
			$x++;
		}
		
		switch (trim($fields[4])) {
			case "Create":
				$create_count++;
				break;
			case "Update":
				$update_count++;
				break;
			case "Delete":
				$delete_count++;
				break;
			case "Suspend":
				$suspend_count++;
				break;
		}
		
		$y++;
		echo "</tr>\r\n";
		flush();
		ob_flush();
	}
	
	$y -= 1;
	//Place records total in session
	//$_SESSION['records'] = $y;
	echo "<tr>\r\n<th colspan=\"" . ($numheaders + 1) . "\" style=\"text-align: left;\">Records: ";
	//build ddl for pagination
	echo "<select name=\"Records\" onchange=\"showtr($y, (this.value-1)*50, this.value*50);\">\r\n";
	for ($i=1; $i<=ceil($y/50); $i++) {
		echo "<option value=\"" . $i . "\">";
		echo ($i*50-49);
		echo "-";
		if (($i*50) < $y){
			echo ($i*50);
		}
		else {
			echo ($y);
		}
		echo "</option>\r\n";
	}
	echo "</select>";
	echo "</th>\r\n</tr>\r\n";
	//close the table.
	echo "</table><br />";
	
	echo "<p>The following actions will be carried out:";
	echo "<blockquote>";
	if ($create_count > 0)
		echo "<strong>" . $create_count . "</strong> accounts will be created<br />";
	if ($update_count > 0)
		echo "<strong>" . $update_count . "</strong> accounts will be updated<br />";
	if ($delete_count > 0)
		echo "<strong>" . $delete_count . "</strong> accounts will be deleted<br />";
	if ($suspend_count > 0)
		echo "<strong>" . $suspend_count . "</strong> accounts will be suspended<br />";
	echo "</blockquote>";
	echo "</p>";
	//display error messages
	if ($error_message != "") {
		echo "<p>Errors found in record(s): " . $error_message . ".</p>";
		echo '<p>Please make the appropriate corrections and make sure to follow these guidelines before proceeding:
			<ul>
		    <li>Usernames may contain letters (a-z), numbers (0-9), dashes (-), underscores (_), and periods (.).</li>
			<li>Passwords may contain any combination of characters, with a minimum of 6.</li>
			<li>First and last names support unicode/UTF-8 characters, and may contain spaces, letters (a-z), numbers (0-9), dashes (-), forward slashes (/), and periods (.), with a maximum of 40 characters.</li></ul></p>';
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
	c += .5;
	var d = document.getElementById(bar); //Progress bar
	d.style.width = c+'px'; //Progress bar width
	if (c < 500) {
		t=setTimeout('bar_animation(bar)', c);  //Increase progress bar width
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

function showtr(total, start, end) {
	for (i=1; i<=total; i++) {
		var tr = document.getElementById(String(i));
		if (i > start && i <= end) {
      		tr.className = '';      
    	} else {
			tr.className = 'h';      
   		}
	}
}

</script>

</head>

<body>
<h2>Google Apps Provisioning Toolkit</h2>
<p>Make sure your list looks okay.</p>
<?php
//start ob if not started
if (ob_get_level() == 0) {
	ob_start();
}
//generate preview table
csvtable($file);
ob_end_flush();
?>
<br />
<form id="upload" name="upload" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" onsubmit="progress();">
<input name="import" type="hidden" value="1"/>
<div id="notice" style="display:none">
    <div style="border: 1px solid #666; width: 503px; height: 20px; background-image:url(../img/progress_bg.gif); background-repeat: no-repeat;">
        <div id="bar" style=" background-image:url(../img/bar.gif); background-repeat: no-repeat; width:100px; height: 20px;"></div>
    </div>
	<p style="color:#000099; font-weight: bold;">Batch process in progress.</p>
</div>
<div id="activate" style="display: block;">
 <p>   <input type="submit" name="continue" id="continue" value="Confirm and Run Batch Process" /> or <a href="index.php">Cancel Batch Process</a></p>
 </div>
</form>

<p>&nbsp;</p>
<p>&nbsp;</p>
</body>
</html>
