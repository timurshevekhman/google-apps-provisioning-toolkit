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

// get users from the selected database and store them in an array
function get_users($preview=false) {
	global $db, $field;
	
	$limit = "";
	$SQLtop = "";
	$ldap_limit = 2000;
	if ($preview){
		$limit = " limit 0,10";
		$SQLtop = "TOP 10 ";
		$ldap_limit = 2000;
	}
	
	
	$users = array();
	switch (DB_TYPE) {
		case 'mysql' :
			$users_raw_query = mysql_query("select " . DB_COL_USERNAME . " as username, " . DB_COL_FIRSTNAME . " as firstname, " . DB_COL_LASTNAME . " as lastname, " . DB_COL_PASSWORD . " as password from " . DB_TABLE . $limit. "");
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
			$users_raw_query = odbc_exec($db, "select " . $SQLtop . DB_COL_USERNAME . " as username, " . DB_COL_FIRSTNAME . " as firstname, " . DB_COL_LASTNAME . " as lastname, " . DB_COL_PASSWORD . " as password from " . DB_TABLE . "");
			$i=0;
			while ($users_raw = odbc_fetch_array($users_raw_query)) {
				$users[$i]['username'] = $users_raw['username'];
				$users[$i]['firstname'] = $users_raw['firstname'];
				$users[$i]['lastname'] = $users_raw['lastname'];
				$users[$i]['password'] = $users_raw['password'];
				$i++;
			}
			break;
		case 'ldap':
			$justthese = array(LDAP_USERNAME, LDAP_FIRSTNAME, LDAP_LASTNAME); // attributes to be returned
			$users_raw = ldap_search($db, LDAP_BASE_DN, LDAP_FILTER, $justthese, '', $ldap_limit);
			if ($users_raw) {
				$result = ldap_get_entries($db, $users_raw);
				//echo $result['count'];
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
					if (is_array($field)) { // combine fields to get password
						$password = '';
						foreach ($field as $key) {
							$password .= $result[$i][$key][0];
						}
						$users[$i]['password'] = $password;
					}
					else {
						$users[$i]['password'] = LDAP_DEFAULT_PASSWORD;
					}
				}
			}
			break;
		case 'ad';
			break;
	}	
	
	return $users;
}

// create dummy accounts
/*
function create_dummies($limit) {
	global $db;

	switch (DB_TYPE) {
		case 'mysql' :
			for ($i = 0; $i < $limit; $i++) {
				mysql_query("insert into " . DB_TABLE . " (" . DB_COL_USERNAME . ", " . DB_COL_FIRSTNAME . ", " . DB_COL_LASTNAME . ", " . DB_COL_PASSWORD . ") values ('user" . $i . "', 'firstname" . $i . "', 'lastname" . $i . "', 'lnas0gononag')");
			}
			break;
		case 'mssql' :
			for ($i = 0; $i < $limit; $i++) {
				odbc_exec($db, "insert into " . DB_TABLE . " (" . DB_COL_USERNAME . ", " . DB_COL_FIRSTNAME . ", " . DB_COL_LASTNAME . ", " . DB_COL_PASSWORD . ") values ('user" . $i . "', 'firstname" . $i . "', 'lastname" . $i . "', 'lnas0gononag')");
			}
			break;
		case 'ldap';
			break;
		case 'ad';
			break;
	}
}
*/
?>