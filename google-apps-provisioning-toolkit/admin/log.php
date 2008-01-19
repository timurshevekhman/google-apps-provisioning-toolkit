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

if (!isset($_REQUEST['f'])){
	header("Location: index.php");  
}
if ($_REQUEST['f'] == "l") {
	$filename = $_SESSION['file_log'];
}
if ($_REQUEST['f'] == "e") {
	$filename = $_SESSION['file_errors'];
}

$file_array = split("/",$filename);

$file = $file_array[count($file_array)-1];

header("Content-Length: " . filesize($filename));
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename='. $file);

readfile($filename); 

?>