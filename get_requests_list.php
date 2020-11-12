<?php
require("./dbconnection.php");

$data = array("get" => $_GET, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "GET"):
	$uid = intval($_GET["uid"]);
	
	$requests = array();
	$sql = "SELECT r.`ID` `requestID`, r.`DocumentsApproved`, c.*, DATE(r.`CreateDate`) `RequestCreateDate`, rl.`RequestCME`, rl.`ID` `rowID`
	FROM `requests` r LEFT JOIN `courses` c ON c.`ID` = r.`CourseID`
	LEFT JOIN `requests_listeners` rl ON r.`ID` = rl.`RequestID`
	WHERE r.`IsDeleted` = 0 AND rl.`UserID` = '".$uid."'
	ORDER BY c.`BeginDate`";
	$data["sql"] = $sql;
	if($dbResult = $link->query($sql)):
		$data["response"] = 1;
		if($dbResult->num_rows > 0):
			while($arResult = $dbResult->fetch_assoc()):
				if(!strlen($arResult["RequestCME"])) $arResult["RequestCME"] = null;
				$requests[] = $arResult;
			endwhile;
		endif;
		$data["requests"] = $requests;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;