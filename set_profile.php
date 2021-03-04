<?php
require("./dbconnection.php");
require("./functions.php");

parse_str(file_get_contents("php://input"), $_PUT);
$data = array("put" => $_PUT, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "PUT"):
	foreach($_PUT as $key => $value):
		$_PUT[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_PUT["uid"]);
	
	$birthdate = ($_PUT["birthdate"] != "") ? "'".formatDate("Y-m-d", $_PUT["birthdate"])."'" : "NULL";
	
	$sql = "UPDATE `users` SET `email` = '".$_PUT["email"]."', `birthdate` = ".$birthdate.", `phone` = '".$_PUT["phone"]."',
		`snils` = '".$_PUT["snils"]."' WHERE `id` = '{$uid}'";
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