<?php
require("./dbconnection.php");
require("./functions.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	$courseID = intval($_POST["courseID"]);
	$listeners = htmlspecialchars($_POST["listeners"]);
	$arListeners = json_decode($listeners, true);
	$currentDatetime = date("Y-m-d H:i:s");
	$data["arListeners"] = $arListeners;

	$data["response"] = 1;

	$users = array();
	foreach($arListeners as $uid):
		$sql = "INSERT INTO `requests` (`Contractor`, `CourseID`, `CourseGUID`, `CourseName`, `UpdDate`, `CreateDate`)
		SELECT '".$uid."', '".$courseID."', `GUID`, `Name`, '".$currentDatetime."', '".$currentDatetime."' FROM `courses` WHERE `ID` = '".$courseID."'";
		$data["sql"][] = $sql;
		if($link->query($sql)):
			$requestID = $link->insert_id;
			$sql = "INSERT INTO `requests_listeners` (`RequestID`, `CourseID`, `UserID`) VALUES ('".$requestID."', '".$courseID."', '".$uid."')";
			$data["sql"][] = $sql;
			if($link->query($sql)):
				$sql = "SELECT CONCAT_WS(' ', `lastname`, `firstname`, `middlename`) `fullname`, `username`, `last_update` `lastUpdate` FROM `users` WHERE `id` = '".$uid."'";
				$data["sql"][] = array();
				if($dbResult = $link->query($sql)):
					while($arUser = $dbResult->fetch_assoc()):
						$users[] = array(
							"id" => $uid,
							"requestID" => $requestID,
							"username" => $arUser["username"],
							"lastUpdate" => formatDate("d.m.Y H:i:s", $arResult["lastUpdate"]),
							"fullname" => $arUser["fullname"],
							"comment" => "",
							"cathedraAllow" => 0,
							"instituteAllow" => 0,
							"checks" => array(
								"cathedra" => array(
									"date" => null,
									"comment" => ""
								),
								"institute" => array(
									"date" => null,
									"comment" => ""
								)
							)
						);
					endwhile;
				else:
					$data["error"] = "Ошибка при выполнении запроса";
					$data["sqlerror"] = $link->error;
				endif;
				
				$data["users"] = $users;
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
			endif;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	endforeach;
endif;

if(isset($data["error"])) $data["response"] = 0;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;