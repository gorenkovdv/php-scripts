<?php
require("./dbconnection.php");

parse_str(file_get_contents("php://input"), $_DELETE);
$data = array("delete" => $_DELETE, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "DELETE"):
	$uid = intval($_DELETE["uid"]);
	$dbResult = $link->query("SELECT `username` FROM `users` WHERE `id` = '{$uid}'");
	$arUser = $dbResult->fetch_row();
	$username = strtolower($arUser[0]);
	
	$dir = $saveFilesPath.$username."/";
	$data["dir"] = $dir;
	
	if($_DELETE["block"] == "documents"):
		$sql = "SELECT * FROM `documents` WHERE `id` = '".$_DELETE["id"]."'";
		if($dbResult = $link->query($sql)):
			if($dbResult->num_rows > 0):
				$arResult = $dbResult->fetch_assoc();
				if(!is_null($arResult["fileURL"])):
					$fileURL = $dir.$arResult["fileURL"];
					$data["fileURL"] = $fileURL;
					unlink($fileURL);
				endif;
				
				if($_DELETE["type"] == "document"):
					$sql = "DELETE FROM `documents` WHERE `id` = '".$_DELETE["id"]."'";
				elseif($_DELETE["type"] == "file"):
					$sql = "UPDATE `documents` SET `fileURL` = NULL WHERE `id` = '".$_DELETE["id"]."'";
				endif;
				
				if($link->query($sql)):
					$data["response"] = 1;
				else:
					$data["error"] = "Ошибка при выполнении запроса";
					$data["sqlerror"] = $link->error;
				endif;
			endif;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	elseif($_DELETE["block"] == "work"):
		$sql = "SELECT * FROM `users` WHERE `id` = '{$uid}'";
		if($dbResult = $link->query($sql)):
			$arResult = $dbResult->fetch_assoc();
			$work = json_decode($arResult["work"], true);
			
			if(!is_null($work["fileURL"])):
				$fileURL = $dir.$work["fileURL"];
				$data["fileURL"] = $fileURL;
				unlink($fileURL);
			endif;
			
			$work["fileURL"] = null;
			$work = json_encode($work, JSON_UNESCAPED_UNICODE);
			
			$sql = "UPDATE `users` SET `work` = '{$work}' WHERE `id` = '{$uid}'";
			if($link->query($sql)):
				$data["response"] = 1;
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
				$data["sqlerror"] = $link->error;
			endif;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;