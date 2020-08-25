<?php
require("./dbconnection.php");
require("./generate_token.php");

$data = array("get" => $_GET, "error" => null, "response" => 0, "login" => null);

if($_SERVER["REQUEST_METHOD"] == "GET"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;

	$id = $_GET["id"];
	$key = $_GET["key"];

	$sql = "SELECT * FROM `users` WHERE `id` = '".$id."'";
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			$arResult = $dbResult->fetch_assoc();
			$dbKey = $arResult["firstKey"];
			
			if($key != $dbKey):
				$data["error"] = "Несоответствие ключей доступа";
			else:
				if(!is_null($arResult["username"])):
					$object = new GenerateToken();
					$refreshKey = $object->generate($username);
					
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_URL, "https://accounts.asmu.local/api/public/users/check/init/".$arResult["username"]);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$jsonOutput = curl_exec($ch);
					$output = json_decode($jsonOutput, true);
					curl_close($ch);
					
					$data["response"] = 1;
					$data["url"] = "https://accounts.asmu.local/api/public/users/check/init/".$arResult["username"];
					$data["reset"] = ($output["reset"]) ? 1 : 0;
					$data["login"] = $arResult["username"];
				else: $data["error"] = "Не удалось получить логин. Попробуйте снова или обратитесь к администратору сайта";
				endif;
			endif;
		else: $data["error"] = "Пользователя с данным id не найдено";
		endif;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;