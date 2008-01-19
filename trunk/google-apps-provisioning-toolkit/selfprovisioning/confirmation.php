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

session_start();
if (!isset($_SESSION["gUserName"])){ 
	header("Location: index.php");
	exit;
}
require('../admin/config.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SADA Systems, Inc.</title>
<link href="../css/toolkit_styles.css" rel="stylesheet" type="text/css" />
</head>

<body>
<h2>Congratulations!</h2>
<p>Your account was successfully created and is ready to use.
<p>Username: <strong><?php echo $_SESSION["gUserName"]; ?></strong><br />
Email: <strong><?php echo $_SESSION["gUserName"] . "@". $domain ; ?></strong>
<p>To sign in to your account, go to <a href="http://mail.google.com/a/<?php echo $domain; ?>">http://mail.google.com/a/<?php echo $domain; ?></a>.
</body>
</html>
