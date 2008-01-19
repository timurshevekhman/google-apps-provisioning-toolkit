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


#******* GoogleApps Domain Variables *******#
//domain name
$domain = 'yourgoogleappsdomain.com';
#domain administrator account and password
$admin = 'admin';
$password = '********';

//Permanently delete user accounts
// Valid entries: 'yes' or 'no'
// A selection of 'no' suspends the user account instead of deleting the account
// Please note that a selection of 'yes' will delete all user account information (i.e. email, contacts, etc.)
// Once a user account is deleted it cannot be restored.
$allow_account_deletion = 'no';

#******* Server Variables *******#
//directory where files are to be uploaded
//$dir_upload ='../../../Data/GoogleToolkit/'; 
$dir_upload = '../logs/';

//log files for self provisioning functions
$log_file = $dir_upload . "selfprovisioning.log";
$log_errors = $dir_upload . "selfprovisioning_errors.log";


#******* Database Settings *******#
// What type of database?
// Valid entries:
// csv: CSV
// mysql: MySQL
// mssql: Microsoft SQL
// ldap: LDAP
// sso: Single Sign-On   //Selfprovisioning only.
DEFINE('DB_TYPE', 'csv');


#******* SSO Settings *******#
//server/domain name hosting provisioning toolkit
DEFINE('THIS_SERVER', $_SERVER['HTTP_HOST']);
//SSO login page
DEFINE('SSO_URL', 'http://yourssoserver.com/login/');
//Service url, url processing SAML response
DEFINE('SERVICE_URL', 'http://' . $_SERVER['HTTP_HOST'] . str_replace('index.php', 'SAML.php', $_SERVER['PHP_SELF']));
//Public certificate
DEFINE('PUB_KEY', '../keys/cert.pem');


#******* MySQL/MSSQL Settings *******#
//Server name/ip
DEFINE('DB_SERVER', 'yourdatabaseserver.com');

//Server port
//default mySQL port: 3306
//default MS SQL port: 1433
DEFINE('DB_PORT', '3306');
//Username
DEFINE('DB_UNAME', 'admin');

//Password

DEFINE('DB_PWORD', '*******');
//Database
DEFINE('DB_NAME', 'database');
// what table are users in the selected database
DEFINE('DB_TABLE', 'users');


// SQL field definitions
// username columN
DEFINE('DB_COL_USERNAME', 'username');
// first name column
DEFINE('DB_COL_FIRSTNAME', 'firstname');
// last name column
DEFINE('DB_COL_LASTNAME', 'lastname');
// password column
DEFINE('DB_COL_PASSWORD', 'password');


#******* LDAP Settings *******#
// ldap server
DEFINE('LDAP_SERVER', 'yourldapserver.com');  // use ldaps://hostname/ for connetion over SSL
// ldap port
DEFINE('LDAP_PORT', '389'); // 389 is default
// ldap protocol option
DEFINE('LDAP_PROTOCOL', '3'); //  3 is default
// ldap referrals option
DEFINE('LDAP_REFERRALS', '0'); //  0 is default
// bind rdn
DEFINE('LDAP_BIND_RDN', 'CN=admin,OU=Users,DC=myldapserver,DC=com');
// bind password
DEFINE('LDAP_BIND_PASSWORD', '********');
// base dn for search
DEFINE('LDAP_BASE_DN', 'OU=Users,DC=myldapserver,DC=com');
// search filter
// example: '(&(objectclass=person)(mailnickname=*)(cn=*)(sn=*))')
DEFINE('LDAP_FILTER', '(&(objectclass=person)(memberOf=CN=Google Apps,OU=Users,DC=myldapserver,DC=com))');
// username
DEFINE('LDAP_USERNAME', 'samaccountname');
// first name
DEFINE('LDAP_FIRSTNAME', 'givenname');
// last name
DEFINE('LDAP_LASTNAME', 'sn');

// Define how Password wil be generated when LDAP is used
// Valid entries:
// default: One default password for all users, defined in the DEFAULT_PASSWORD variable
// field: One field or a combination of fields, defined in the $field variable bellow
DEFINE('LDAP_PASSWORD', 'default');

DEFINE('LDAP_DEFAULT_PASSWORD', 'default_password'); // Default password for all users if LDAP_PASSWORD = 'default' is 'default_password'

if (LDAP_PASSWORD == 'field') {
	// Add LDAP field names to the array bellow if you wish to use a readable field or a combination of readable fields.  List in order to be combined.
	// Alternative, you can generate $field by writting a custom function.
	$field = array('sn');
}

#******* Database Connections *******#
// fill in the connection settings applicable to your database.  settings for other databases will be ignored.
switch (DB_TYPE) {
	case 'mysql' :
		$db = mysql_connect (DB_SERVER.":".DB_PORT, DB_UNAME, DB_PWORD) or die ('Cannot connect to the MySQL database because: ' . mysql_error());
		mysql_select_db (DB_NAME); 	
		break;
	case 'mssql' :
		putenv('TDSVER=70');
		$db = mssql_connect (DB_SERVER.":".DB_PORT, DB_UNAME, DB_PWORD) or die ('Cannot connect to the MS SQL database because: '. mssql_get_last_message());
		mssql_select_db (DB_NAME, $db);
		break;
	case 'ldap':
		$db=ldap_connect(LDAP_SERVER.":".LDAP_PORT)  or die ('Cannot connect to LDAP.' );
		if ($db) {
			ldap_set_option($db, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL);
			ldap_set_option($db, LDAP_OPT_REFERRALS, LDAP_REFERRALS);
			$ldapbind = ldap_bind($db, LDAP_BIND_RDN, LDAP_BIND_PASSWORD) or die ('Cannot bind to LDAP.' );
		}
		break;
}

?>
