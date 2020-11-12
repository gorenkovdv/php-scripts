<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	$requestID = intval($_POST["requestID"]);
	
	$sql = "UPDATE `requests` SET `IsDeleted` = 1 WHERE `ID` = '".$requestID."'";
	$data["sql"][] = $sql;
	if($link->query($sql)):
		$data["response"] = 1;
		$data["users"] = array();
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;