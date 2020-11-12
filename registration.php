<?php
require("./dbconnection.php");
require("./generate_token.php");
require("./functions.php");

$data = array("post" => $_POST, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "POST"):
	foreach($_POST as $key => $value):
		$_POST[$key] = htmlspecialchars($value);
	endforeach;

	$lastname = $_POST["lastname"];
	$firstname = $_POST["firstname"];
	$middlename = $_POST["middlename"];
	$phone = $_POST["phone"];
	$snils = $_POST["snils"];
	$email = $_POST["email"];
	$birthdate = (strlen($_POST["birthdate"])) ? "'".formatDate("Y-m-d", $_POST["birthdate"])."'" : "NULL";
	$ip = $_SERVER['REMOTE_ADDR'];

	$birthdateQuery = ""; if(strlen($_POST["birthdate"])) $birthdateQuery = " AND `birthdate` = ".$birthdate;
	$sql = "SELECT COUNT(*) FROM `users` WHERE `firstname` = '".$firstname."' AND `lastname` = '".$lastname."' AND `middlename` = '".$middlename."' ".$birthdateQuery;
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		$num_rows = $dbResult->num_rows;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;

	if(!$num_rows):
		$object = new GenerateToken();
		$key = $object->generate($ip);

		$sql = "INSERT INTO `users` (`lastname`, `firstname`, `middlename`, `phone`, `email`, `snils`, `birthdate`, `ip`, `firstKey`, `reg_address`, `fact_address`, `passport`)
			VALUES ('{$lastname}', '{$firstname}', '{$middlename}', '{$phone}', '{$email}', '{$snils}', {$birthdate}, '{$ip}', '{$key}', '', '', '')";
		
		$data["sql"][] = $sql;
			
		if($result = $link->query($sql)):
			$id = $link->insert_id;
			
			$data["key"] = $key;
			$data["id"] = $id;
			
			$message = "";
			$message .= "<html><head><title>Подтверждение учётной записи</title></head><body>";
			$message .= "<p>Для подтверждения учётной записи перейдите по ссылке:</p>";
			$message .= "<p><a href=\"{$baseURL}confirm/{$id}/{$key}\" target=\"_blank\">{$baseURL}confirm/{$id}/{$key}</a></p>";
			$message .= "</body></html>";
			
			$headers = "";
			$headers .= "MIME-Version: 1.0"."\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8"."\r\n";
			$headers .= "From: AGMU.RU <nomail@agmu.ru>";
			
			$mail = "dmitrygorenkov95@gmail.com";
			
			if(mail($mail, "Подтверждение учётной записи", $message, $headers)):
				$data["response"] = 1;
			else: $data["error"] = "Ошибка при отправке сообщения";
			endif;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	else: $data["error"] = "Пользователь с такими учётными данными уже существует";
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;