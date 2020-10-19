<?php
require("./dbconnection.php");
require("./generate_token.php");

$data = array("get" => $_GET, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "GET"):
	foreach($_GET as $key => $value):
		$_GET[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_GET["uid"]);
	
	$sql = "SELECT `id`, `username`, `lastname`, `firstname`, `middlename`, `email`, `birthdate`, `phone`, `snils`, `ip`, `accounts`
		FROM `users` WHERE `id` = '{$uid}'";
	$data["sql"][] = $sql;
	if($dbResult = $link->query($sql)):
		$arUser = $dbResult->fetch_assoc();

		/* Обновление данных пользователя */
		$username = strtolower($arUser["username"]);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, "https://accounts.asmu.local/api/public/users/fio?login={$username}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$jsonInfo = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, "https://accounts.asmu.local/api/public/users/structures?login={$username}");
		$jsonStructures = curl_exec($ch);
		curl_close($ch);
		
		$arInfo = json_decode($jsonInfo, true);
		$arStructures = json_decode($jsonStructures, true);
		
		$data["arInfo"] = $arInfo;
		$data["arStructures"] = $arStructures;
		
		$birthdate = "";
		if(is_null($arUser["birthdate"]) && $arInfo["birthday"] != "0000-00-00"):
			$birthdate .= ", `birthdate` = '".$arInfo["birthday"]."'";
		endif;
		
		$sql = "UPDATE `users` SET `uid` = '".$arInfo["id"]."', `lastname` = '".$arInfo["lastname"]."', `firstname` = '".$arInfo["firstname"]."',
			`middlename` = '".$arInfo["middlename"]."' ".$birthdate." WHERE `username` = '{$username}'";
		$data["sql"][] = $sql;
		if(!$link->query($sql)):
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
		
		$dbStructures = array(); $newStructures = array();
		$sql = "SELECT * FROM `users_departments` WHERE `uid` = '".$arUser["id"]."'";
		$data["sql"][] = $sql;
		if($dbResult = $link->query($sql)):
			if($dbResult->num_rows > 0):
				$arStructure = $dbResult->fetch_assoc();
				$dbStructures[] = $arStructure["departmentGUID"];
			endif;
			
			if(is_array($arStructures["list"]))
			foreach($arStructures["list"] as $arStructure):
				if(!in_array($arStructure["StructureGUID"], $dbStructures) && !isset($newStructures[$arStructure["StructureGUID"]])):
					$newStructures[$arStructure["StructureGUID"]] = array(
						"name" => $arStructure["Structure"],
						"GUID" => $arStructure["StructureGUID"]
					);
				endif;
			endforeach;
			
			if(count($newStructures) > 0):
				$i = 0;
				$insertQuery = "INSERT INTO `users_departments` (`uid`, `departmentGUID`, `departmentName`) VALUES ";
				foreach($newStructures as $arStructure):
					if($i > 0) $insertQuery .= ",";
					$insertQuery .= "('".$arUser["id"]."', '".$arStructure["GUID"]."', '".$arStructure["name"]."')";
					$i++;
				endforeach;
				
				$data["dbStructures"] = $dbStructures;
				$data["newStructures"] = $newStructures;
				$data["insertQuery"] = $insertQuery;
				
				$data["sql"][] = $insertQuery;
				
				if(!$link->query($insertQuery)):
					$data["error"] = "Ошибка при выполнении запроса 5";
					$data["sqlerror"] = $link->error;
				endif;
			endif;
		else:
			$data["error"] = "Ошибка при выполнении запроса 6";
			$data["sqlerror"] = $link->error;
		endif;
		
		$data["response"] = 1;
		$data["profile"] = array(
			"lastname" => $arUser["lastname"],
			"firstname" => $arUser["firstname"],
			"middlename" => $arUser["middlename"],
			"email" => $arUser["email"],
			"phone" => $arUser["phone"],
			"snils" => $arUser["snils"],
			"birthDate" => $arUser["birthdate"]
		);
	else: $data["error"] = "Ошибка при выполнении запроса";
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;