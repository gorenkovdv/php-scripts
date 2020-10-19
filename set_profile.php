<?php
require("./dbconnection.php");

parse_str(file_get_contents("php://input"), $_PUT);
$data = array("put" => $_PUT, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "PUT"):
	foreach($_PUT as $key => $value):
		$_PUT[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_PUT["uid"]);
	
	$birthdate = ($_PUT["birthDate"] != "") ? "'".date("Y-m-d", strtotime($_PUT["birthDate"]))."'" : "NULL";
	
	$sql = "UPDATE `users` SET `lastname` = '".$_PUT["lastname"]."', `firstname` = '".$_PUT["firstname"]."', `middlename` = '".$_PUT["middlename"]."', 
		`email` = '".$_PUT["email"]."', `birthdate` = ".$birthdate.", `phone` = '".$_PUT["phone"]."', `snils` = '".$_PUT["snils"]."' WHERE `id` = '{$uid}'";
	
	if($link->query($sql)):
		$data["response"] = 1;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;