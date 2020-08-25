<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "POST"):
	$uid = intval($_POST["uid"]);
	$ITN = intval($_POST["ITN"]);
	
	$sql = "SELECT * FROM `entities` WHERE `ITN` = '".$ITN."'";
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			$arResult = $dbResult->fetch_assoc();
			$entity = $arResult["id"];
		else:
			$sql = "INSERT INTO `entities` (`ITN`) VALUES ('".$ITN."')";
			if($link->query($sql)):
				$entity = $link->insert_id;
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
			endif;
		endif;
		
		if($link->query("INSERT INTO `entity_representatives` (`user`, `entity`, `confirmed`) VALUES ('".$uid."',  '".$entity."', '0')")):
			$data["response"] = 1;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;