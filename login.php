<?php
require("./dbconnection.php");
require("./generate_token.php");

$username = $_POST["username"];
$data = array("username" => $username, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	foreach($_POST as $key => $value):
		$_POST[$key] = htmlspecialchars($value);
	endforeach;

	$currentDate = date("Y-m-d H:i:s");
	$expires = strtotime("+1 hour", strtotime($currentDate));
	$ip = $_SERVER['REMOTE_ADDR'];
	$object = new GenerateToken();

	$key = $object->generate($ip);
	$refreshKey = $object->generate($username);
	$token = array("key" => $key, "secondKey" => $refreshKey, "expires" => $expires, "isPasswordSet" => 0);

	// Проверка, есть ли данный логин в БД
	$isUserExist = false;
	$sql = "SELECT * FROM `users` WHERE `username` = '{$username}'";
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			$isUserExist = true;
			$arUser = $dbResult->fetch_assoc();
			$dbRefreshKey = $arUser["secondKey"];
			$data["arUser"] = $arUser;
		endif;
	else:
		$data["error"] = "Ошибка при выполнении запроса 1";
		$data["sqlerror"] = $link->error;
	endif;

	if(isset($_POST["password"])):
		$password = $_POST["password"];
		if(strlen($password) > 0):
			$ldaphost = "len40-ldap.asmu.local";
			$ldapport = "389";
			$ldaprdn = "cn=read,dc=agmu,dc=ru";
			$ldappass = "K@peik0";

			$ldapconn = ldap_connect($ldaphost, $ldapport);
			if($ldapconn):
				ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
				$userDN = sprintf("uid=%s,ou=users,%s", $username, "dc=agmu,dc=ru");
				
				if(@ldap_bind($ldapconn, $userDN, $password)):
					if($isUserExist): $sql = "UPDATE `users` SET `firstKey` = '{$key}', `secondKey` = '{$refreshKey}', `ip` = '{$ip}' WHERE `username` = '{$username}'";
					else: $sql = "INSERT INTO `users` (`firstKey`, `secondKey`, `ip`, `username`, `reg_address`, `fact_address`, `passport`) VALUES ('{$key}', '{$refreshKey}', '{$ip}', '{$username}', '', '', '')";
					endif;
					
					$data["sql"][] = $sql;
					
					$token["isPasswordSet"] = 1;
				
					if($link->query($sql)):
						$data["uid"] = (!$isUserExist) ? $link->insert_id : $arUser["id"];
						if(!$isUserExist) $isUserExist = true;
						
						$data["response"] = 1;
						$data["token"] = $token;
					else:
						$data["error"] = "Ошибка при выполнении запроса 2";
						$data["sqlerror"] = $link->error;
					endif;
				else: $data["error"] = "Неверный логин/пароль";
				endif;
				
				$currentDate = date("Y-m-d H:i:s");
				$sql = "INSERT INTO `log` (`date_auth`, `username`, `ip`, `success`) VALUES ('".$currentDate."', '".$username."', '".$ip."', '".$data["response"]."')";
				$data["sql"][] = $sql;
				$link->query($sql);
				
			else: $data["error"] = "Нет соединения c LDAP";
			endif;
		else:
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_URL, "https://accounts.asmu.local/api/public/users/check/init/{$username}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$jsonOutput = curl_exec($ch);
			$output = json_decode($jsonOutput, true);
			curl_close($ch);
			
			if($output["reset"]):
				if($isUserExist): $sql = "UPDATE `users` SET `firstKey` = '{$key}', `secondKey` = '{$refreshKey}', `ip` = '{$ip}' WHERE `username` = '{$username}'";
				else: $sql = "INSERT INTO `users` (`firstKey`, `secondKey`, `ip`, `username`, `reg_address`, `fact_address`, `passport`) VALUES ('{$key}', '{$refreshKey}', '{$ip}', '{$username}', '', '', '')";
				endif;
				
				$data["sql"][] = $sql;
				
				$token["isPasswordSet"] = 0;
				
				if($link->query($sql)):
					$data["uid"] = (!$isUserExist) ? $link->insert_id : $arUser["id"];
					if(!$isUserExist) $isUserExist = true;
					
					$data["response"] = 1;
					$data["token"] = $token;
				else:
					$data["error"] = "Ошибка при выполнении запроса 3";
					$data["sqlerror"] = $link->error;
				endif;
			else: $data["error"] = "Неверный логин/пароль";
			endif;
		endif;
	elseif(isset($_POST["refreshKey"])):
		$requestRefreshKey = $_POST["refreshKey"];
		
		$data["requestRefreshKey"] = $requestRefreshKey;
		$data["dbRefreshKey"] = $dbRefreshKey;

		if($requestRefreshKey == $dbRefreshKey):
			$sql = "UPDATE `users` SET `firstKey` = '{$key}', `secondKey` = '{$refreshKey}' WHERE `username` = '{$username}'";
			$data["sql"][] = $sql;
			if($link->query($sql)):
				$data["user"] = $arUser;
				$data["uid"] = $arUser["id"];
				
				$token["isPasswordSet"] = 1;
				$token["secondKey"] = $refreshKey;
				
				$data["response"] = 1;
				$data["token"] = $token;
			else:
				$data["error"] = "Ошибка при выполнении запроса 4";
				$data["sqlerror"] = $link->error;
			endif;
		else: $data["error"] = "Несоответствие ключей доступа";
		endif;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;