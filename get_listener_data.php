<?php
require("./dbconnection.php");
require("./save_file.php");

$data = array("get" => $_GET, "response" => 0, "output" => array(), "sql" => array());

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

	$addressPattern = array(
		'postcode' => '',
		'country' => '',
		'region' => '',
		'locality' => '',
		'localityType' => 0,
		'street' => '',
		'house' => '',
		'room' => ''
	);
	
	$data["output"]["registration"] = $addressPattern;
	$data["output"]["fact"] = $addressPattern;
	$data["output"]["passport"] = array(
		'number' => '',
		'series' => '',
		'unitCode' => '',
		'birthPlace' => '',
		'issuedBy' => '',
		'issuedDate' => '',
	);
	$data["output"]["work"] = array(
		'postcode' => '',
		'country' => '',
		'region' => '',
		'locality' => '',
		'localityType' => '',
		'street' => '',
		'house' => '',
		'room' => '',
		'listenerPosition' => '',
		'accessionDate' => '',
		'hrPhone' => '',
		'workPhone' => '',
		'fileURL' => null
	);
	$data["output"]["education"] = array(
		'currentDocument' => 0,
		'currentLevel' => 0,
		'fullName' => null,
		'levels' => array(),
	);
	$data["output"]["sertificates"] = array(
		'currentDocument' => 0,
		'documents' => array()
	);
	$data["output"]["others"] = array(
		'currentDocument' => 0,
		'documents' => array()
	);;
	
	if(in_array($tab, array(0, 1, 2, 3))):
		$sql = "SELECT `reg_address`, `fact_address`, `passport`, `work` FROM `users` WHERE `id` = '{$uid}'";
		$data["sql"][] = $sql;
		if($dbResult = $link->query($sql)):
			$data["response"] = 1;
			$arData = $dbResult->fetch_assoc();
			
			switch($tab):
				case 0:
					if(!is_null($arData["reg_address"])) $data["output"]["registration"] = json_decode($arData["reg_address"]);
					break;
				case 1:
					if(!is_null($arData["reg_address"])) $data["output"]["registration"] = json_decode($arData["reg_address"]);
					if(!is_null($arData["fact_address"])) $data["output"]["fact"] = json_decode($arData["fact_address"]);
					break;
				case 2:
					if(!is_null($arData["passport"])) $data["output"]["passport"] = json_decode($arData["passport"]);
					break;
				case 3:
					if(!is_null($arData["work"])) $data["output"]["work"] = json_decode($arData["work"]);

					$arPositionTypes = array();
					
					$sql = "SELECT * FROM `position_types`";
					if($dbResult = $link->query($sql)):
						while($arPositionType = $dbResult->fetch_assoc()):
							$arPositionTypes[] = $arPositionType["type"];
						endwhile;
					endif;
					
					$data["positionTypes"] = $arPositionTypes;
					break;
				default: break;
			endswitch;
		endif;
	else:
		if($tab == 4):
			for($i = 0; $i < 6; $i++) $data["output"]["education"]["levels"][$i] = array();
			
			$arEducationTypes = array();
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
			
			$data["output"]["educationTypes"] = $arEducationTypes;
		endif;
		
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
						case 4: $data["output"]["education"]["levels"][$arEducation["level"]][] = $outputArr; break;
						case 5: $data["output"]["sertificates"]["documents"][] = $outputArr; break;
						case 6: $data["output"]["others"]["documents"][] = $outputArr; break;
						default: break;
					endswitch;
				endif;
			endwhile;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;