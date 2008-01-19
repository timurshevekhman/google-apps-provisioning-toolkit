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
session_destroy();
require('../admin/config.php');
require('acctfunctions.php');
$cMsg = "";

if (DB_TYPE == "sso") {
	$SAML_req = gen_SAML();
	header("Location: " . SSO_URL . "?SAMLRequest=" . $SAML_req);
}


if(isset($_POST["username"]) && isset($_POST["usernamepword"])) {
	$cUserName = $_POST["username"];
	$cPassword = $_POST["usernamepword"];
	$users = array();
	switch (DB_TYPE) {
		case 'mysql' :
			$users_raw_query = mysql_query("select " . DB_COL_USERNAME . " as username, " . DB_COL_FIRSTNAME . " as firstname, " . DB_COL_LASTNAME . " as lastname, " . DB_COL_PASSWORD . " as password from " . DB_TABLE . " WHERE " . DB_COL_USERNAME . " = '" . $cUserName . "' AND " . DB_COL_PASSWORD . " = '" . $cPassword . "'" );
			$i=0;
			while ($users_raw = mysql_fetch_array($users_raw_query)) {
				$users[$i]['username'] = $users_raw['username'];
				$users[$i]['firstname'] = $users_raw['firstname'];
				$users[$i]['lastname'] = $users_raw['lastname'];
				$users[$i]['password'] = $users_raw['password'];
				$i++;
			}
			break;
		case 'mssql' :
			$users_raw_query = odbc_exec($db, "select " . DB_COL_USERNAME . " as username, " . DB_COL_FIRSTNAME . " as firstname, " . DB_COL_LASTNAME . " as lastname, " . DB_COL_PASSWORD . " as password from " . DB_TABLE . " WHERE " . DB_COL_USERNAME . " = '" . $cUserName . "' AND " . DB_COL_PASSWORD . " = '" . $cPassword . "'" );
			$i=0;
			while ($users_raw = odbc_fetch_array($users_raw_query)) {
				$users[$i]['username'] = $users_raw['username'];
				$users[$i]['firstname'] = $users_raw['firstname'];
				$users[$i]['lastname'] = $users_raw['lastname'];
				$users[$i]['password'] = $users_raw['password'];
				$i++;
			}
			break;
		
		case 'ldap' :
			$LDAP_Base_DN = "CN=" . $_POST["username"] . ", " . LDAP_BASE_DN;
			$LDAP_Password = $_POST["usernamepword"];
			if ($db) {
				ldap_set_option($db, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL);
				ldap_set_option($db, LDAP_OPT_REFERRALS, LDAP_REFERRALS);
				$ldapbind = ldap_bind($db, $LDAP_Base_DN, $LDAP_Password);
				if ($ldapbind) {
					$UserSearch = ldap_search($db, LDAP_BASE_DN, LDAP_USERNAME . '=' . $_POST["username"]);
					if ($UserSearch) {
						$result = ldap_get_entries($db, $UserSearch);
						for ($i = 0; $i < $result['count']; $i++) {
							$users[$i]['username'] = "";
							$users[$i]['firstname'] = "";
							$users[$i]['lastname'] = "";
							if (isset($result[$i][LDAP_USERNAME][0]))
								$users[$i]['username'] = $result[$i][LDAP_USERNAME][0];
							if (isset($result[$i][LDAP_FIRSTNAME][0]))
								$users[$i]['firstname'] =$result[$i][LDAP_FIRSTNAME][0];
							if (isset($result[$i][LDAP_LASTNAME][0]))
								$users[$i]['lastname'] = $result[$i][LDAP_LASTNAME][0];
						}
					}
				}
			}
	}
	$users_count = count($users);
	if ($users_count < 1) {
		$cMsg = "Incorrect login, please try again";
	}
	if ($users_count == 1) {
		session_start();
		$_SESSION["gUser"] = $users;
		header("Location: account.php");
	}
	if ($users_count > 1) {
		$cMsg = "Duplicate user error.";
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>SADA Systems, Inc.</title>
<link href="../css/toolkit_styles.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
//Frameset Break Away
function deframe(){
	if (top.location != self.location) 
	top.location.href=self.location;
}

//Set focus to NetID text box
function sf(){
	document.login.netid.focus();
}
</script>

</head>

<body style="background-color: #EFEFEF;">
<!-- Login Form -->
<form action="<?php echo $_SERVER["PHP_SELF"] ?>" method="post" name="login">
  <table id="login" class="tbl_login" align="center">
          	 <tr>
    <td colspan="2" style="padding: 0px; text-align: center;"><img src="../img/logo.gif" alt="SADA Logo" width="361" height="74" /><br /><br />
      <div style="padding: 10px; font-family: Arial, Verdana, Helvetica, sans-serif; color:#FF0000; font-size:12px;"><?php echo $cMsg; ?></div></td>
  </tr>
            <tr>
              <td align="right">Username:</td>
              <td><input name="username" type="text" class="txt"></td>
            </tr>
            <tr>
              <td align="right">Password:</td>
              <td><input name="usernamepword" type="password" class="txt"></td>
            </tr>                    
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td><input type="submit" name="Submit" value="Login">
                <label>
                <input type="reset" name="Reset" value="Reset" />
                </label>
                <br />
              <br /></td>
            </tr>
  </table>
</form>
</body>
</html>
