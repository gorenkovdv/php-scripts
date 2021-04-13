<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "error" => null, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "POST"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;
	
	$id = intval($_POST["id"]);
	$password = $_POST["password"];
	$key = $_POST["key"];
	
	$sql = "SELECT * FROM `users` WHERE `id` = '".$id."'";
	if($dbResult = $link->query($sql)):
		$arResult = $dbResult->fetch_assoc();
		$dbKey = $arResult["firstKey"];
		$username = $arResult["username"];
			
		if($key != $dbKey):
			$data["error"] = "Несовпадение ключей";
		else:
			$post_data = array("login" => $username, "password" => $password);
			$ch = curl_init("http://accounts.asmu.local/api/public/users/password");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$output = curl_exec($ch);
			curl_close($ch);
			
			$data["response"] = 1;
		endif;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;