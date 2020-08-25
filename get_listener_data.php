<?php
require("./dbconnection.php");
require("./save_file.php");

$data = array("get" => $_GET, "response" => 0, "sql" => array());

if($_SERVER["REQUEST_METHOD"] == "GET"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_GET["uid"]);
	$tab = intval($_GET["tab"]);
	
	$sql = "SELECT `username`, CONCAT_WS(' ', `lastname`, `firstname`, `middlename`) `fullName` FROM `users` WHERE `id` = '{$uid}'";
	$data["sql"][] = $sql;
	$dbResult = $link->query($sql);
	$arUser = $dbResult->fetch_assoc();
	$username = strtolower($arUser["username"]);
	$fullname = $arResult["fullName"];
	$dir = $saveFilesPath.$username."/";
	
	$data["registration"] = new stdClass();
	$data["fact"] = new stdClass();
	$data["passport"] = new stdClass();
	$data["work"] = new stdClass();
	$data["education"] = new stdClass();
	$data["sertificates"] = new stdClass();
	$data["others"] = new stdClass();
	
	if(in_array($tab, array(0, 1, 2, 3))):
		$sql = "SELECT `reg_address`, `fact_address`, `passport`, `work` FROM `users` WHERE `id` = '{$uid}'";
		$data["sql"][] = $sql;
		if($dbResult = $link->query($sql)):
			$data["response"] = 1;
			$arData = $dbResult->fetch_assoc();
			
			switch($tab):
				case 0:
					$jsonRegAddr = json_decode($arData["reg_address"]); if(is_null($jsonRegAddr)) $jsonRegAddr = new stdClass();
					$data["registration"] = $jsonRegAddr;
					break;
				case 1:
					$jsonRegAddr = json_decode($arData["reg_address"]); if(is_null($jsonRegAddr)) $jsonRegAddr = new stdClass();
					$jsonFactAddr = json_decode($arData["fact_address"]); if(is_null($jsonFactAddr)) $jsonFactAddr = new stdClass();
					$data["registration"] = $jsonRegAddr;
					$data["fact"] = $jsonFactAddr;
					break;
				case 2:
					$jsonPassportData = json_decode($arData["passport"]); if(is_null($jsonPassportData)) $jsonPassportData = new stdClass();
					$data["passport"] = $jsonPassportData;
					break;
				case 3:
					$arPositionTypes = array();
					$jsonWorkData = json_decode($arData["work"]); if(is_null($jsonWorkData)) $jsonWorkData = new stdClass();
					
					$sql = "SELECT * FROM `position_types`";
					if($dbResult = $link->query($sql)):
						while($arPositionType = $dbResult->fetch_assoc()):
							$arPositionTypes[] = $arPositionType["type"];
						endwhile;
					endif;
					
					$data["positionTypes"] = $arPositionTypes;
					$data["work"] = $jsonWorkData;
					break;
				default: break;
			endswitch;
		endif;
	else:
		switch($tab):
			case 4:
				$arEducationTypes = array();
				$jsonEducationData = array("currentLevel" => 0, "currentDocument" => 0, "fullName" => $fullname, "levels" => array());
				for($i = 0; $i < 6; $i++) $jsonEducationData["levels"][$i] = array();
				
				$sql = "SELECT * FROM `education_types`";
				$data["sql"][] = $sql;
				if($dbResult = $link->query($sql)):
					while($arEducationType = $dbResult->fetch_assoc()):
						$arEducationTypes[$arEducationType["level"]] = array();
						foreach($arEducationType as $key => $value):
							$arEducationTypes[$arEducationType["level"]][$key] = $value;
						endforeach;
					endwhile;
				endif;
				
				$data["educationTypes"] = $arEducationTypes;
				break;
			case 5: $jsonSertificatesData = array("currentDocument" => 0, "documents" => array()); break;
			case 6: $jsonOthersData = array("currentDocument" => 0, "documents" => array()); break;
			default: break;
		endswitch;
		
		$sql = "SELECT * FROM `documents` WHERE `uid` = '{$uid}'";
		$data["sql"][] = $sql;
		if($dbResult = $link->query($sql)):
			$data["response"] = 1;
			while($arEducation = $dbResult->fetch_assoc()):
				$outputArr = array(
					"id" => $arEducation["id"],
					"organization" => $arEducation["organization"],
					"speciality" => $arEducation["speciality"],
					"fullName" => ($arEducation["level"] < 4) ? $arEducation["fullName"] : null,
					"documentName" => $arEducation["documentName"],
					"comment" => $arEducation["comment"],
					"firstDate" => ($arEducation["firstDate"] != '0000-00-00') ? $arEducation["firstDate"] : null,
					"secondDate" => ($arEducation["secondDate"] != '0000-00-00') ? $arEducation["secondDate"] : null,
					"hours" => ($arEducation["level"] > 3) ? $arEducation["hours"] : null,
					"serial" => $arEducation["serial"],
					"fileURL" => (file_exists($dir.$arEducation["fileURL"])) ? $arEducation["fileURL"] : null
				);
				
				if($tab == $arEducation["type"]):
					switch($tab):
						case 4: $jsonEducationData["levels"][$arEducation["level"]][] = $outputArr; break;
						case 5: $jsonSertificatesData["documents"][] = $outputArr; break;
						case 6: $jsonOthersData["documents"][] = $outputArr; break;
						default: break;
					endswitch;
				endif;
			endwhile;
			
			switch($tab):
				case 4: $data["education"] = $jsonEducationData; break;
				case 5: $data["sertificates"] = $jsonSertificatesData; break;
				case 6: $data["others"] = $jsonOthersData; break;
				default: break;
			endswitch;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;