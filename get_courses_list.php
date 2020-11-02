<?php
require("./dbconnection.php");
require("./functions.php");

$data = array("post" => $_POST, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "POST"):
	foreach($_POST as $key => $value):
		if(!is_array($_POST[$key])): $_POST[$key] = htmlspecialchars(trim($value));
		else:
			foreach($_POST[$key] as $skey => $svalue):
				$_POST[$key][$skey] = htmlspecialchars(trim($svalue));
			endforeach;
		endif;
	endforeach;
	
	$uid = intval($_POST["uid"]);
	$sql = "SELECT d.`role`, d.`GUID` FROM `users_departments` ud
		LEFT JOIN `departments` d ON d.`GUIDKadri` = ud.`departmentGUID` WHERE ud.`uid` = '".$uid."'";
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		$arResult = $dbResult->fetch_assoc();
		$data["rootGroup"] = $arResult["role"];
		if($arResult["role"] == 3) $data["rootCathedra"] = $arResult["GUID"];
	endif;
	
	$userRequests = array();
	$sql = "SELECT rl.`CourseID` FROM `requests_listeners` rl LEFT JOIN `requests` r ON r.`ID` = rl.`RequestID`
		WHERE rl.`UserID` = '".$uid."' AND r.`IsDeleted` = 0";
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			while($arResult = $dbResult->fetch_row()):
				$userRequests[$arResult[0]] = 1;
			endwhile;
		endif;
	endif;
	
	$data["volumeList"] = array();
	$sql = "SELECT DISTINCT `Volume` FROM `courses` ORDER BY `Volume`";
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			while($arResult = $dbResult->fetch_row()):
				$data["volumeList"][] = $arResult[0];
			endwhile;
		endif;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
	
	$arFilters = $_POST["filters"];
	$arFilters["enrolPossible"] = toBoolean($arFilters["enrolPossible"]);
	$arFilters["CME"] = toBoolean($arFilters["CME"]);
	$arFilters["traditional"] = toBoolean($arFilters["traditional"]);
	$arFilters["budgetaryOnly"] = toBoolean($arFilters["budgetaryOnly"]);
	$arFilters["nonBudgetaryOnly"] = toBoolean($arFilters["nonBudgetaryOnly"]);
	$arFilters["retraining"] = toBoolean($arFilters["retraining"]);
	$arFilters["skillsDevelopment"] = toBoolean($arFilters["skillsDevelopment"]);
	$arFilters["forDoctors"] = toBoolean($arFilters["forDoctors"]);
	$arFilters["forNursingStaff"] = toBoolean($arFilters["forNursingStaff"]);
	
	$filters = "";
	if(isset($arFilters["searchString"])):
		$arFilters["searchString"] = str_replace("'", "", $arFilters["searchString"]);
		$arFilters["searchString"] = str_replace("&quot;", "", $arFilters["searchString"]);
		$filters .= " AND (`Name` LIKE '%".$arFilters["searchString"]."%'
			OR `Speciality` LIKE '%".$arFilters["searchString"]."%'
			OR `AdditionalSpecialities` LIKE '%".$arFilters["searchString"]."%')";
	endif;
	if($arFilters["enrolPossible"]): $filters .= " AND YEAR(`BeginDate`) = YEAR(NOW()) AND `RequestDate` >= DATE(NOW())"; endif;
	if($arFilters["budgetaryOnly"]): $filters .= " AND `Price` = 0"; endif;
	if($arFilters["nonBudgetaryOnly"]): $filters .= " AND `Price` != 0"; endif;
	if(!$arFilters["CME"] && $arFilters["traditional"]): $filters .= " AND `IsCME` = 0";
	elseif($arFilters["CME"] && !$arFilters["traditional"]): $filters .= " AND `IsCME` = 1";
	endif;
	if($arFilters["currentVolume"] > 0): $filters .= " AND `Volume` = '".$arFilters["currentVolume"]."'"; endif;
	if(isset($arFilters["startDate"])): $filters .= " AND `BeginDate` > '".$arFilters["startDate"]."'"; endif;
	if(isset($arFilters["endDate"])): $filters .= " AND `BeginDate` < '".$arFilters["endDate"]."'"; endif;
	if(!$arFilters["retraining"]): $filters .= "AND `ProfEducationType` != 'Профессиональная переподготовка'"; endif;
	if(!$arFilters["skillsDevelopment"]): $filters .= "AND `ProfEducationType` != 'Повышение квалификации'"; endif;
	
	if($arFilters["forDoctors"] && !$arFilters["forNursingStaff"]): $filters .= " AND `ListenersGroup` = 'Врачи'"; endif;
	if(!$arFilters["forDoctors"] && $arFilters["forNursingStaff"]): $filters .= " AND `ListenersGroup` = 'Средний медицинский персонал'"; endif;

	if(!is_null($arFilters["searchUser"]) && strlen($arFilters["searchUser"])):
		$filters .= "AND `ID` IN (SELECT r.`CourseID` FROM `requests` r
		LEFT JOIN `requests_listeners` rl ON rl.`RequestID` = r.`ID`
		LEFT JOIN `users` u ON u.`ID` = rl.`UserID`
		WHERE r.`IsDeleted` = 0 AND u.id = '".$arFilters["searchUser"]."')";
	endif;
	
	$sql = "SELECT COUNT(*) `count` FROM `courses` с WHERE 1 = 1 ".$filters."";
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			$arResult = $dbResult->fetch_row();
			$totalCount = $arResult[0];
			$data["totalCount"] = $arResult[0];
			
			$page = 1; $pageSize = 5;
			if(isset($_POST["page"])) $page = intval($_POST["page"]);
			if(isset($_POST["count"])) $pageSize = intval($_POST["count"]);
			
			$pagesCount = ceil($totalCount / $pageSize);
			if($page > $pagesCount) $page = ($pagesCount != 0) ? $pagesCount : 1;
			
			$data["page"] = $page;
			
			$firstElement = ($page - 1) * $pageSize;
			$data["pageSize"] = $pageSize;
			$data["pagesCount"] = $pagesCount;
			$data["firstElement"] = $firstElement;
			
			$sql = "SELECT * FROM `courses` WHERE 1 = 1 ".$filters." ORDER BY `BeginDate` LIMIT ".$firstElement.", ".$pageSize;
			$data["sql"][] = $sql;
			
			$courses = array();
			$coursesIDs = array();
			if($dbResult = $link->query($sql)):
				$data["response"] = 1;
				$data["num_rows"] = $dbResult->num_rows;
				if($dbResult->num_rows > 0):
					while($arResult = $dbResult->fetch_assoc()):
						$arResult["users"] = array();
						$coursesIDs[] = $arResult["ID"];
						
						$arResult["BeginDateMonth"] = date('m', strtotime($arResult["RequestDate"]));
						if($arResult["BeginDateMonth"] < 8): $arResult["StartDateTooltip"] = "проводится дистанционно";
						else:
							if(!is_null($arResult["FullTimeBeginDate"])):
								$arResult["StartDateTooltip"] = "очная часть с ".$arResult["FullTimeBeginDate"]." по ".$arResult["FullTimeEndDate"];
							endif;
						endif;
						$courses[$arResult["ID"]] = $arResult;
					endwhile;
					
					$data["coursesIDs"] = $coursesIDs;
					$sql = "SELECT r.`CourseID`, r.`CourseGUID`, rl.`ID` `rowID`, rl.`UserID`, rl.`Comment`, rl.`CathedraEmployee`, rl.`CathedraComment`, rl.`CathedraCheck`, rl.`CathedraAllow`,
						rl.`InstituteEmployee`, rl.`InstituteComment`, rl.`InstituteCheck`, rl.`InstituteAllow`, rl.`RequestCME`, u.`username`, u.`last_update` `lastUpdate`,
						CONCAT_WS(' ', u.`lastname`, u.`firstname`, u.`middlename`) `fullname`
						FROM `requests` r LEFT JOIN `requests_listeners` rl ON r.`ID` = rl.`RequestID` LEFT JOIN `users` u ON u.`ID` = rl.`UserID`
						WHERE r.`CourseID` IN ('".implode("','", $coursesIDs)."') AND rl.`UserID` IS NOT NULL AND r.`IsDeleted` = 0";
					$data["sql"][] = $sql;
					if($dbResult = $link->query($sql)):
						if($dbResult->num_rows > 0):
							while($arResult = $dbResult->fetch_assoc()):
								$checks = array(
									"cathedra" => array(
										"date" => formatDate("d.m.Y H:i:s", $arResult["CathedraCheck"]),
										"comment" => $arResult["CathedraComment"],
										"person" => $arResult["CathedraEmployee"],
										"label" => ""
									),
									"institute" => array(
										"date" => formatDate("d.m.Y H:i:s", $arResult["InstituteCheck"]),
										"comment" => $arResult["InstituteComment"],
										"person" => $arResult["InstituteEmployee"],
										"label" => ""
									)
								);
								
								if(strlen($checks["cathedra"]["comment"]) > 0):
									$checks["cathedra"]["label"] = ": ".$checks["cathedra"]["date"]." ".$checks["cathedra"]["person"];
								endif;

								if(strlen($checks["institute"]["comment"]) > 0):
									$checks["institute"]["label"] = ": ".$checks["institute"]["date"]." ".$checks["institute"]["person"];
								endif;

								
								$courses[$arResult["CourseID"]]["users"][] = array(
									"id" => $arResult["UserID"],
									"rowID" => $arResult["rowID"],
									"username" => $arResult["username"],
									"fullname" => $arResult["fullname"],
									"lastUpdate" => formatDate("d.m.Y H:i:s", $arResult["lastUpdate"]),
									"requestCME" => (strlen($arResult["RequestCME"]) > 0) ? $arResult["RequestCME"] : null,
									"comment" => $arResult["Comment"],
									"cathedraAllow" => $arResult["CathedraAllow"],
									"instituteAllow" => $arResult["InstituteAllow"],
									"checks" => $checks
								);

							endwhile;
						endif;
					endif;
					
					$data["courses"] = array_values($courses);
				else: $data["courses"] = new stdClass();
				endif;
			else:
				$data["error"] = "Ошибка при выполнении запроса";
				$data["sqlerror"] = $link->error;
			endif;
		endif;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;