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

require('../admin/config.php');
require('acctfunctions.php');

if (isset($_GET['SAML_Response'])) {
	$type = "Get";
	$SAML_response = decode_msg($_GET['SAML_Response'], $type);
}

if (isset($_POST['SAML_Response'])) {
	$type = "Post";
	$SAML_response = decode_msg($_POST['SAML_Response'], $type);
}

//echo $SAML_response;

$valid_SAML = verify_response($SAML_response, PUB_KEY);

//echo $valid_SAML;

if ($valid_SAML == "OK") {
	$xml = new SimpleXMLElement($SAML_response); 
	//Crawl SAML response to obtain username.
	foreach ($xml->Assertion as $Assertion) {
		foreach ($Assertion->Subject as $Subject){
			foreach ($Subject->NameID as $User) {
				$users[0]['username'] = (string) $User;
			}
		}
	}
	
	//Continue to account creation page.	
	if ($users[0]['username']  != "") {
		session_start();
		$_SESSION["gUser"] = $users;
		header("Location: account.php");
	}

} 

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>GoogleApps Provisioning Toolkit</title>
<link href="../css/toolkit_styles.css" rel="stylesheet" type="text/css" />
</head>
<?php
if ($valid_SAML != "OK")
	echo "There was an error with you login request.  Please try again.  <a href=\"index.php\">Login</a>";
?>
<body>
</body>
</html>
