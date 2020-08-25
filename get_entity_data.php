<?php
require("./dbconnection.php");

$data = array("get" => $_GET, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "GET"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_GET["uid"]);
	$entity = intval($_GET["entity"]);
	
	$roots = 0;
	
	$sql = "SELECT * FROM `entity_representatives` WHERE `user` = '".$uid."'";
	if($dbResult = $link->query($sql)):
		$data["response"] = 1;
		if($dbResult->num_rows > 0):
			$arResult = $dbResult->fetch_assoc();
			$entity = $arResult["entity"];
			$roots = intval($arResult["confirmed"]);
		endif;
		
		if($roots && isset($entity)):
			$sql = "SELECT * FROM `entities` WHERE `ID` = '".$entity."'";
			$data["sql"] = $sql;
			if($dbResult = $link->query($sql)):
				$data["num_rows"] = $dbResult->num_rows;
				if($dbResult->num_rows > 0):
					$arResult = $dbResult->fetch_assoc();
					$data["entity"] = $arResult;
				else: $data["entity"] = new stdClass();
				endif;
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
			endif;
		endif;
		
		$data["roots"] = $roots;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;