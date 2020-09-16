<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	$courseID = intval($_POST["courseID"]);
	$rowID = intval($_POST["rowID"]);
	
	$sql = "UPDATE `requests` SET `IsDeleted` = 1 WHERE `CourseID` = '".$courseID."' AND `ID` IN (SELECT `RequestID` FROM `requests_listeners` WHERE `ID` = '".$rowID."')";
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