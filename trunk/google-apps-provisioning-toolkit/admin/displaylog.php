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

if (!isset($_SESSION['file_log'])) {
	header("Location: index.php");  
}

//import config file
require( 'config.php' );

//define files to read
$file = $_SESSION['file_log'];
$file_errors = $_SESSION['file_errors'];

//make sure that file exist
function filereader($file, $tries){
	$go = 0;
	$fileread = fopen($file, 'r');
	if ($fileread){
		$go = 1;
	}
	else {
		sleep(1);
		$tries += 1;
		if ($tries < 10) {
			filereader($file, $tries);
		}
	}
	return $go;
}

//Function to display possible errors
function error_dict($error){
	$error_msg = "";
	switch ($error) {
		case "1000":
			$error_msg = "unknown error";
			break;
		case "1100":
			$error_msg = "user recently deleted";
			break;
		case "1101":
			$error_msg = "suspended user";
			break;
		case "1200":
			$error_msg = "domain limit exceeded";
			break;
		case "1201":
			$error_msg = "domain alias exceeded";
			break;
		case "1202":
			$error_msg = "domain suspended";
			break;
		case "1203":
			$error_msg = "feauture unavailable";
			break;
		case "1300":
			$error_msg = "duplicate entity";
			break;
		case "1301":
			$error_msg = "nonexistant entity";
			break;
		case "1302":
			$error_msg = "reserved entity";
			break;
		case "1303":
			$error_msg = "invalid entity";
			break;
		case "1400":
			$error_msg = "invalid name";
			break;
		case "1401":
			$error_msg = "invalid family name";
			break;
		case "1402":
			$error_msg = "invalid password";
			break;
		case "1403":
			$error_msg = "invalid username";
			break;
		case "1404":
			$error_msg = "invalid hash function";
			break;
		case "1405":
			$error_msg = "invalid digest length";
			break;
		case "1406":
			$error_msg = "invalid email address";
			break;
		case "1407":
			$error_msg = "invalid query parameter";
			break;
		case "1500":
			$error_msg = "too many recipients";
			break;
		default:
			$error_msg = "unknown error";
			break;
		}
	return " - <a href=\"http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference.html#appendix_d\" target=\"_blank\">$error_msg</a>";
}

//function to highligh fields containing possible errors
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
function csvtable($file, $display_error){
	
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
	$tbl = "";
	if ($display_error == 1){
		$tbl = "l";
	}
	else {
		$tbl = "e";
	}
	
	if ($display_error == 1){
		echo "<span class=\"log_title\">Process Log</span>";
	}
	else {
		echo "<span class=\"log_title\">Errors Log</span>";
	}
	
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
	$error_count = 0;
	
	//Output the data, looping through the number of lines of data and also looping through the number of cells in each line, as this is a dynamic number the header length has to be reread.
	while($y<$numlines){
		$x=0;
		$fields = explode($sep, $lines[$y]);
		$trstyle = "";
		if ($y > 50) {
			$trstyle = " class=\"h\"";
		}
		echo "<tr id=\"". $tbl . $y . "\"" . $trstyle . ">\r\n";
		echo "<td>$y</td>\r\n";
	
		while($x<$numheaders){
			$field = trim(str_replace("\"", "", $fields[$x]));
			$fieldstyle = bad_char($field, $x);
			if ($x == 5 && $field !="OK"){
				$errormsg = error_dict($field);
				$field .= $errormsg;
				if ($display_error == 1){
					$error_count += 1;
				}
			}
			echo "<td" . $fieldstyle . ">".$field."</td>\r\n";
			$x++;
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
	echo "<select name=\"Records\" onchange=\"showtr($y, (this.value-1)*50, this.value*50, '$tbl');\">\r\n";
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
	if ($display_error == 1){
		$cUsersAdded = $y - $error_count;
		echo "<p>Batch process completed.<br /> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Successful transactions: <strong>" . $cUsersAdded . "</strong><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Errors: <strong>" . $error_count ."</strong><br /><br /><a href=\"log.php?f=l\">Download log file</a></p>";
	} 
	else {
		echo "<p><a href=\"log.php?f=e\">Download errors log file</a></p>";
	}
		
	if ($error_count > 0) {
		echo "<p>Please review <a href=\"http://code.google.com/apis/apps/gdata_provisioning_api_v2.0_reference.html#appendix_d\" target=\"_blank\">Appendix D</a>, GDATA Error Codes, of the Google Apps Provisioning API V2.0 Reference guide for more information on why errors where received.</p><p>&nbsp;</p>";
		$display_error = 0;
		global $file_errors;
		csvtable($file_errors, $display_error);
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

function showtr(total, start, end, tbl) {
	for (i=1; i<=total; i++) {
		var tr = document.getElementById(tbl + String(i));
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
<?php
//start ob if not started
if (ob_get_level() == 0) {
	ob_start();
}
$tries = 0;
$process = filereader($file, $tries);
if ($process == 1){
	$display_error = 1;
	csvtable($file, $display_error);
	flush();
	ob_flush();
}

ob_end_flush();
?>
<br />
<p><a href="index.php">Run another batch process</a></p>
<p>&nbsp;</p>
</body>
</html>
