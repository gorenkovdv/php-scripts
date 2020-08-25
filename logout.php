<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "POST"):
	foreach($_POST as $key => $value):
		$_POST[$key] = htmlspecialchars($value);
	endforeach;

	$username = $_POST["username"];


	$sql = "UPDATE `users` SET `firstKey` = NULL, `secondKey` = NULL WHERE `username` = '{$username}'";
	$data["sql"] = $sql;

	if($link->query($sql)):
		$data["response"] = 1;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;