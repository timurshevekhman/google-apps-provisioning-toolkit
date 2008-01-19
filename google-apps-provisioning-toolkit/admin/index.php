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

$cMsg = "";
session_start();
//Unset session if they exist
unset($_SESSION['filename']);
unset($_SESSION['file_log']);

//import config file
require( 'config.php' );

switch (DB_TYPE) {
	case "sso":
		break;
	case "csv":
		header("Location: csv.php"); 
		break;
	default:
		header("Location: db.php"); 
		break;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>GoogleApps Provisioning Toolkit</title>
<link href="../css/toolkit_styles.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
//Script 
</script>

</head>

<body>
<h2>Google Apps Provisioning Toolkit</h2>
<?php 
if (DB_TYPE == "sso") {
	echo "Please note that your <strong>DB_TYPE</strong> variable is set to \"sso\".  The Single Sign-On option is only available in the self provisioning section.  Please modify your config file to continue. ";
} else {
?>
<ol>
  <li><a href="csv.php">Use a CSV file</a></li>
  <li><a href="db.php">Use a database (mySQL, LDAP, MS SQL)</a></li>
</ol>
<?php
}
?>
</body>
</html>
