<?php
require("./dbconnection.php");
require("./generate_token.php");

$data = array("get" => $_GET, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "GET"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;
	
	$value = $_GET["value"];
	
	$sql = "SELECT * FROM `users` WHERE `username` = '{$value}' OR `email` = '{$value}'";
	$data["sql"] = $sql;
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			$arResult = $dbResult->fetch_assoc();
			$id = $arResult["id"];
			$key = $arResult["firstKey"];
			
			if(is_null($key)):
				$object = new GenerateToken();
				$key = $object->generate($ip);
				
				$link->query("UPDATE `users` SET `firstKey` = '{$key}' WHERE `id` = '{$id}'");
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
			endif;
			
			$message = "";
			$message .= "<html><head><title>Подтверждение учётной записи</title></head><body>";
			$message .= "<p>Для подтверждения учётной записи перейдите по ссылке:</p>";
			$message .= "<p><a href=\"{$baseURL}changepassword/{$id}/{$key}\" target=\"_blank\">{$baseURL}changepassword/{$id}/{$key}</a></p>";
			$message .= "</body></html>";
			
			$headers = "";
			$headers .= "MIME-Version: 1.0"."\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8"."\r\n";
			$headers .= "From: AGMU.RU <nomail@agmu.ru>";
			
			$mail = $arResult["email"];
			
			if($mail != ""):
				if(mail($mail, "Подтверждение учётной записи", $message, $headers)):
					$data["response"] = 1;
					$data["email"] = $mail;
				else: $data["error"] = "Ошибка при отправке сообщения";
				endif;
			else:
				$data["error"] = "У пользователя не указана электронная почта";
			endif;
		else: $data["error"] = "Пользователь не найден";
		endif;
	endif;

	$data["sql"] = $sql;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;