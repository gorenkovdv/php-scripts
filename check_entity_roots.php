<?php
require("./dbconnection.php");

$data = array("get" => $_GET, "response" => 0, "entities" => array());

if($_SERVER["REQUEST_METHOD"] == "GET"):
	$uid = intval($_GET["uid"]);
	
	$entities = array();
	$sql = "SELECT * FROM `entity_representatives` WHERE `user` = '".$uid."'";
	$data["sql"] = $sql;
	if($dbResult = $link->query($sql)):
		$data["response"] = 1;
		if($dbResult->num_rows > 0):
			while($arResult = $dbResult->fetch_assoc()):
				$entities[] = $arResult["id"];
			endwhile;
		endif;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
	
	$data["entities"] = array_values($entities);
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;