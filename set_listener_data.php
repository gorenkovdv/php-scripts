<?php
require("./dbconnection.php");
require("./save_file.php");

$blocks = array("reg_address", "fact_address", "passport", "work", "education", "sertificates", "others");

parse_str(file_get_contents("php://input"), $_PUT);
$data = array("put" => $_PUT, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "PUT"):
	foreach($_PUT as $key => $value):
		if(!is_null($value)) $_PUT[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_PUT["uid"]);
	$sql = "SELECT `username` FROM `users` WHERE `id` = '{$uid}'";
	$data["sql"][] = $sql;
	$dbResult = $link->query($sql);
	$arUser = $dbResult->fetch_row();
	$username = strtolower($arUser[0]);
			
	$dir = $saveFilesPath.$username."/";
	
	$index = intval($_PUT["block"]);
	$block = $blocks[$index];
	
	if(in_array($index, array(3, 4, 5, 6)) && strlen($_PUT["fileURL"]) > 0):
		if(strlen($_PUT["newFile"]) > 0):
			if(!(file_exists($dir) && is_dir($dir))) mkdir($dir);
			$fileURL = $dir.$_PUT["fileURL"];
			savePdfFile($fileURL, $_PUT["newFile"]);
			
			$data["dir"] = $dir; $data["src"] = $fileURL;
		endif;
	endif;
	
	$db = $_PUT; unset($db["uid"], $db["block"]);
	if(isset($db["newFile"])):
		unset($db["newFile"]);
	endif;
	
	$put = json_encode($db, JSON_UNESCAPED_UNICODE);
	$put = addslashes($put);
	$put = preg_replace('/&quot;([^"]*)&quot;/', '«$1»', $put);

	switch($index):
		case 0:
		case 1:
		case 2:
		case 3:
			$sql = "UPDATE `users` SET `".$block."` = '".$put."' WHERE `id` = '{$uid}'";
			$data["sql"][] = $sql;
			break;
		case 4:
		case 5:
		case 6:
			$isDocumentNew = false;
			
			$firstDate = ($_PUT["firstDate"] != "") ? "'".date("Y-m-d", strtotime($_PUT["firstDate"]))."'" : "NULL";
			$secondDate = ($index == 4 && $_PUT["secondDate"] != "") ? "'".date("Y-m-d", strtotime($_PUT["secondDate"]))."'" : "NULL";
			$fullname = ($index == 4 && $_PUT["level"] < 4) ? "'".$_PUT["fullName"]."'" : "NULL";
			$hours = ($index == 4 && $_PUT["level"] > 3) ? "'".$_PUT["hours"]."'" : "NULL";
			$level = ($index == 4) ? "'".$_PUT["level"]."'" : "NULL";
			$documentName = ($index == 6) ? "'".$_PUT["documentName"]."'" : "NULL";
			$comment = ($index == 6) ? $_PUT["comment"] : "";
			$fileURL = (!is_null($_PUT["fileURL"]) && $_PUT["fileURL"] != "" && file_exists($dir.$_PUT["fileURL"]))
			? "'".$_PUT["fileURL"]."'"
			: "NULL";
			
			$query = "SELECT * FROM `documents` WHERE `id` = '".$_PUT["id"]."'";
			$data["sql"][] = $sql;
			if($dbResult = $link->query($query)):
				if($dbResult->num_rows > 0):
					$sql = "UPDATE `documents` SET `organization` = '".$_PUT["organization"]."', `speciality` = '".$_PUT["speciality"]."', `fullName` = ".$fullname.", `documentName` = ".$documentName.", `comment` = '".$comment."', `firstDate` = ".$firstDate.", `secondDate` = ".$secondDate.", `serial` = '".$_PUT["serial"]."', `hours` = ".$hours.", `fileURL` = ".$fileURL." WHERE `id` = '".$_PUT["id"]."'";
				else:
					$isDocumentNew = true;
					$sql = "INSERT INTO `documents` (`uid`, `type`, `level`, `organization`, `speciality`, `fullName`, `documentName`, `comment`, `firstDate`, `secondDate`, `serial`, `hours`, `fileURL`) VALUES ('".$uid."', '".$index."', ".$level.", '".$_PUT["organization"]."', '".$_PUT["speciality"]."', ".$fullname.", ".$documentName.", '".$comment."', ".$firstDate.", ".$secondDate.", '".$_PUT["serial"]."', ".$hours.", ".$fileURL.")";
				endif;
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
			endif;
			break;
		default: break;
	endswitch;
	
	$data["sql"][] = $sql;
	if($link->query($sql)):
		$data["response"] = 1;
		$data["output"] = json_decode(stripslashes($put), true);
		if(in_array($index, array(4, 5, 6)) && $isDocumentNew):
			$data["output"]["id"] = $link->insert_id;
		endif;

		$currentDatetime = date("Y-m-d H:i:s");
		$sql = "UPDATE `users` SET `last_update` = '".$currentDatetime."' WHERE `id` = '".$uid."'";
		$data["sql"][] = $sql;
		$link->query($sql);
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;