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


#******* Global Functions *******#

//Function to parse header.
function decode_header ( $str )
{
    $part = preg_split ( "/\r?\n/", $str, -1, PREG_SPLIT_NO_EMPTY );

    $out = array ();

    for ( $h = 0; $h < sizeof ( $part ); $h++ )
    {
        if ( $h != 0 )
        {
            $pos = strpos ( $part[$h], ':' );

            $k = strtolower ( str_replace ( ' ', '', substr ( $part[$h], 0, $pos ) ) );

            $v = trim ( substr ( $part[$h], ( $pos + 1 ) ) );
        }
        else
        {
            $k = 'status';

            $v = explode ( ' ', $part[$h] );

            $v = $v[1];
        }

        if ( $k == 'set-cookie' )
        {
                $out['cookies'][] = $v;
        }
        else if ( $k == 'content-type' )
        {
            if ( ( $cs = strpos ( $v, ';' ) ) !== false )
            {
                $out[$k] = substr ( $v, 0, $cs );
            }
            else
            {
                $out[$k] = $v;
            }
        }
        else
        {
            $out[$k] = $v;
        }
    }

    return $out;
}


//Function to parse body.
function decode_body ( $info, $str, $eol = "\r\n" )
{
    $tmp = $str;

    $add = strlen ( $eol );

    $str = '';

    if ( isset ( $info['transfer-encoding'] ) && $info['transfer-encoding'] == 'chunked' )
    {
        do
        {
            $tmp = ltrim ( $tmp );

            $pos = strpos ( $tmp, $eol );

            $len = hexdec ( substr ( $tmp, 0, $pos ) );

            if ( isset ( $info['content-encoding'] ) )
            {
                $str .= gzinflate ( substr ( $tmp, ( $pos + $add + 10 ), $len ) );
            }
            else
            {
                $str .= substr ( $tmp, ( $pos + $add ), $len );
            }

            $tmp = substr ( $tmp, ( $len + $pos + $add ) );

            $check = trim ( $tmp );

        } while ( ! empty ( $check ) );
    }
    else if ( isset ( $info['content-encoding'] ) )
    {
        $str = gzinflate ( substr ( $tmp, 10 ) );
    }
	 else if ( !isset ( $info['content-encoding'] ) )
    {
        $str = trim( $tmp );
    }

    return $str;
}


//Replace illegal characters
function replace_str($cStr)
{
    $cIn = array ("<", ">", "&", "'", '"');
    $cOut = array ("&lt;", "&gt;", "&amp;", "&apos;", "&quot;");
    return str_replace($cIn, $cOut, $cStr);
}


//Security Token Function
function get_token($gDomain, $gLogin, $gPassword) {

	# working vars
	$host_auth = "www.google.com";
	$service_uri = "/accounts/ClientLogin";
	$vars_auth ="&Email=" . $gLogin . "@" . $gDomain . "&Passwd=" . $gPassword . "&accountType=HOSTED&service=apps";
	
	# compose HTTP request header
	$header_auth = "Host: $host_auth\r\n";
	$header_auth .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header_auth .= "Content-Length: ".strlen($vars_auth)."\r\n";
	$header_auth .= "Connection: close\r\n\r\n";

	$fp = pfsockopen("ssl://".$host_auth, 443, $errno, $errstr);
	if (!$fp) {
		echo "$errstr ($errno)<br/>\n";
		echo $fp;
	} else {
		fputs($fp, "POST $service_uri  HTTP/1.1\r\n");
		fputs($fp, $header_auth.$vars_auth);
		while ($fp && !feof($fp)) {
			$cString = fgets($fp);
			$cStringArray = explode("=", $cString);
			if ($cStringArray[0] == "Auth") {
				$_SESSION["auth_token"] = $cStringArray[1];
				//writetoken($cStringArray[1]);
			}
		}
	}
	fclose($fp);
}


//Google API Function
function API_Post($Type, $cFName, $cLName, $cUser, $cPword, $cNic, $auth_token, $gDomain) {

	$host_g = "www.google.com";
	$cMsg = "";
	$cAddName = "";
	
	$cFName = replace_str($cFName);
	$cLName = replace_str($cLName);
	$cUser = replace_str($cUser);
	$cPword = replace_str($cPword);
	$cNic = replace_str($cNic);
	
	switch ($Type) {
		case "create":
			$post_type = "POST";
			$feed = "user";
			$vars_g = '<?xml version="1.0" encoding="UTF-8"?>
						<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:apps="http://schemas.google.com/apps/2006">
							<atom:category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/apps/2006#user"/>
							<apps:login userName="' . $cUser . '" password="' . $cPword . '" suspended="false"/>
							<apps:quota limit="2048"/>
							<apps:name familyName="' . $cLName . '" givenName="' . $cFName . '"/>
						</atom:entry>';	
			break;
			
		case "nickname":
			$post_type = "POST";
			$feed = "nickname";
			$vars_g = '<?xml version="1.0" encoding="UTF-8"?>
						<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:apps="http://schemas.google.com/apps/2006">
							<atom:category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/apps/2006#nickname"/>
							<apps:nickname name="' . $cNic .'"/>
							<apps:login userName="' . $cUser . '"/>
						</atom:entry>';
			break;
			
		case "displaynick":
			$post_type = "GET";
			$feed = "nickname";
			$vars_g = ""; //"?username=" . $cUser;
			$cAddName = "?username=" . $cUser;
			break;
	}
	
	$service_urg = "/a/feeds/" . $gDomain . "/" . $feed . "/2.0" . $cAddName;
	$cYear = date("Y");

	# compose HTTP request header
	$header_g = "Host: $host_g\r\n";
	$header_g .= "Content-Type: application/atom+xml; charset=UTF-8\r\n";
	$header_g .= "Content-Length: ".strlen($vars_g)."\r\n";
	$header_g .= "Authorization: GoogleLogin auth=".$auth_token."\r\n";
	//$header_g .= "Connection: close\r\n\r\n";  

	$fpg = pfsockopen("ssl://".$host_g, 443, $errno, $errstr);
	if (!$fpg) {
   		echo "$errstr ($errno)<br/>\n";
   		echo $fpg;
	} else {
    	fputs($fpg, "$post_type $service_urg  HTTP/1.1\r\n");
    	fputs($fpg, $header_g.$vars_g);
		
		$cResponse = "";
		while (!feof($fpg) && strpos($cResponse, "\r\n\r\n") === false) {
			$cResponse .= fgets($fpg, 1024);
		}
		//echo $cResponse . "<br />";
		$cHeader = decode_header($cResponse);
		$cResponse = "";
		while ($fpg && !feof($fpg)) {
			$cResponse .= fgets($fpg, 1024);
		}
		//echo $cResponse . "<br />";
		fclose($fpg);
		
		$cXML = decode_body ($cHeader, $cResponse);
		$cXML = trim($cXML);

		$xml = new SimpleXMLElement($cXML);  //load xml
		
		$cErrors = 0;
		$cErrorMsg = "";
		$cExists = 0;
		
		if ($Type == "create" || $Type == "nickname") {
		
			foreach ($xml->error as $node) {
				$cErrors += 1;
				$cErrorCode = $node["errorCode"];
			}
		
			if ($cErrors > 0) {
				$cMsg .= $cErrorCode;
			} else {
				$cMsg = "OK";
			}
		}
		
		
		if ($Type == "displaynick") {
		
			foreach ($xml->error as $node) {
				$cErrors += 1;
				$cErrorCode = $node["errorCode"];
				if ($node["errorCode"] == "1301") { //Entity does not exist
					$cErrorMsg .= "Account does not exists.";
				}
			}
		
			if ($cErrors > 0) {
				$cMsg .= $cErrorCode;
		
			} else {
				$cMsg = array();
				foreach ($xml->entry as $entry) {
					foreach ($entry->children("http://schemas.google.com/apps/2006") as $node => $nodevalue) {
						//echo $node . ": " . $nodevalue . " <br />";
						foreach($nodevalue->attributes() as $attribute => $attributevalue) {
							//echo $attribute . ": " . $attributevalue . " <br />";	
							if ($node == "nickname" && $attribute == "name") {
								$cNickName = $attributevalue;
								$cMsg[] = $cNickName;
							}
						}
					}
				}
			}
		}
		
		return $cMsg;
	
	}
	
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
	return $error . "|" . $error_msg;
}


//function to encode SAML request
function encode_msg($msg) {
	$cmsg = gzdeflate($msg);
	$cmsg = base64_encode($cmsg);
	$cmsg = urlencode($cmsg);
	return $cmsg;
}

//function to decode base64 encoded SAML response
function decode_msg($msg, $type) {
	if ($type == "Post")
		$msg = urldecode($msg);
	$b64_msg = base64_decode($msg);
	$cmsg = gzinflate($b64_msg);
	if ($cmsg === FALSE) {
	// gzinflate failed, try gzuncompress
	$cmsg = gzuncompress($b64_msg);
	}
	return $cmsg;
}


// function to generate SAML ID
function saml_id() {
  $chars = "abcdefghijklmnop";
  $cid = "";
  
  for ($i = 0; $i < 40; $i++ ) {
    $cid .= $chars[rand(0,strlen($chars)-1)];
  }
  
  return $cid;
}

//function to generate SAML request
function gen_SAML() {
	$SAML_ID = saml_id();
	$SAML_timestamp = gmdate('Y-m-d\TH:i:s\Z', time());
	$SAML_req = '<?xml version="1.0" encoding="UTF-8"?>
    <samlp:AuthnRequest 
            xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
            ID="' . $SAML_ID . '" 
            Version="2.0" 
            IssueInstant="' . $SAML_timestamp . '" 
            ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" 
            ProviderName="' . THIS_SERVER . '" 
            AssertionConsumerServiceURL="' . SERVICE_URL . '" 
            IsPassive="false">
        <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . THIS_SERVER .'</saml:Issuer>
        <samlp:NameIDPolicy AllowCreate="true" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified" />
    </samlp:AuthnRequest>';
	
	$SAML_req = encode_msg($SAML_req);
	
	return $SAML_req;
}

function verify_response($responseXmlString, $pubKey) {
	global $dir_upload;
	//Time stamp
	$cTime = date("Ymd_His");
	
	//generate unique temporary filename
	$tempFileName = $dir_upload . 'SAML_Response_' . $cTime . '.xml';
	while (file_exists($tempFileName)) 
		 $tempFileName = 'SAML_Response_' . $cTime . '.xml';
	if (!$handle = fopen($tempFileName, 'w')) {
		echo 'Cannot open temporary file (' . $tempFileName . ')';
		exit;
	} 
	if (fwrite($handle, $responseXmlString) === FALSE) {
		echo 'Cannot write to temporary file (' . $tempFileName . ')';
		exit;
	}
	
	fclose($handle);
	
	//verify SAML response via xmlsec
	$cmd = 'xmlsec1 --verify --pubkey-cert-pem ' . $pubKey . ' ' . $tempFileName;
	exec($cmd, $resp);
	//var_dump($resp);	 
	//unlink($tempFileName);
	$result = "ERROR";
	if ($resp && $resp[0] == "OK") {
		$result = "OK";
	}
	return $result;
}

?>