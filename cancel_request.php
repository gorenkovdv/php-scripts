<?php
require("./dbconnection.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	$courseID = intval($_POST["courseID"]);
	if($_POST["userID"] > 0) $userID = intval($_POST["userID"]);
	if($_POST["rowID"] > 0) $rowID = intval($_POST["rowID"]);

	$selectRows = "";
	if(isset($rowID)) $selectRows = "AND `ID` IN (SELECT `RequestID` FROM `requests_listeners` WHERE `ID` = '".$rowID."')";
	if(isset($userID)) $selectRows = "AND `ID` IN (SELECT `RequestID` FROM `requests_listeners` WHERE `userID` = '".$userID."')";
	
	$sql = "UPDATE `requests` SET `IsDeleted` = 1 WHERE `CourseID` = '".$courseID."' ".$selectRows;
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