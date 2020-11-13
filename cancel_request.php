<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	if(isset($_POST["requestID"])): $requestID = intval($_POST["requestID"]); endif;
	if(isset($_POST["rowID"])): $rowID = intval($_POST["rowID"]); endif;
	
	if(isset($requestID)): $sql = "UPDATE `requests` SET `IsDeleted` = 1 WHERE `ID` = '".$requestID."'";
	elseif(isset($rowID)): $sql = "DELETE FROM `requests_listeners` WHERE `ID` = '".$rowID."'";
	endif;

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