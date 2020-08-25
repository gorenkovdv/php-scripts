<?php
require("./dbconnection.php");
require("./generate_token.php");

$data = array("get" => $_GET, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "GET"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_GET["uid"]);
	
	$sql = "SELECT `id`, `username`, `lastname`, `firstname`, `middlename`, `email`, `birthdate`, `phone`, `snils`, `ip`, `accounts`
		FROM `users` WHERE `id` = '{$uid}'";
	$data["sql"] = $sql;
	if($dbResult = $link->query($sql)):
		$arUser = $dbResult->fetch_assoc();
		
		$data["response"] = 1;
		$data["profile"] = array(
			"lastName" => $arUser["lastname"],
			"firstName" => $arUser["firstname"],
			"middleName" => $arUser["middlename"],
			"email" => $arUser["email"],
			"phone" => $arUser["phone"],
			"snils" => $arUser["snils"],
			"birthDate" => $arUser["birthdate"]
		);
	else: $data["error"] = "Ошибка при выполнении запроса";
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;