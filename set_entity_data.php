<?php
require("./dbconnection.php");

parse_str(file_get_contents("php://input"), $_PUT);
$data = array("put" => $_PUT, "response" => 0);

if($_SERVER["REQUEST_METHOD"] == "PUT"):
	foreach($_PUT as $key => $value):
		if(!is_null($value)) $_PUT[$key] = htmlspecialchars($value);
	endforeach;
	
	$uid = intval($_PUT["uid"]);
	$entity = intval($_PUT["entity"]);

	$localityType = ($_PUT["localityType"] != "") ? "'".$_PUT["localityType"]."'" : "NULL";
	
	$sql = "SELECT * FROM `entities` WHERE `ID` = '{$entity}'";
	if($dbResult = $link->query($sql)):
		if($dbResult->num_rows > 0):
			$arResult = $dbResult->fetch_assoc();
			
			$putRow = "";
			foreach($_PUT as $key => $value):
				if(!in_array($key, array("id", "uid", "person", "username", "entity", "user"))):
					if($putRow != "") $putRow .= ", ";
					if($key != "localityType"): $putRow .= "`{$key}` = '{$value}'";
					else: $putRow .= "`{$key}` = {$localityType}";
					endif;
				endif;
			endforeach;
		
			$sql = "UPDATE `entities` SET {$putRow} WHERE `id` = '{$entity}'";
		/*
		else:
			$sql = "INSERT INTO `entities` (`position`,`organization`,`CTMU`,`ITN`,`IEC`,`country`,`region`,`locality`,`localityType`,`postcode`,`street`,`house`,`workPhone`,`hrPhone`,`bank`,`BIC`,`checkAcc`) VALUES ('".$_PUT["position"]."','".$_PUT["organization"]."','".$_PUT["CTMU"]."','".$_PUT["ITN"]."','".$_PUT["IEC"]."','".$_PUT["country"]."','".$_PUT["region"]."','".$_PUT["locality"]."',".$localityType.",'".$_PUT["postcode"]."','".$_PUT["street"]."','".$_PUT["house"]."','".$_PUT["workPhone"]."','".$_PUT["hrPhone"]."','".$_PUT["bank"]."','".$_PUT["BIC"]."','".$_PUT["checkAcc"]."')";
		*/
		endif;
		
		$data["sql"] = $sql;
		if($link->query($sql)):
			$data["response"] = 1;
		else:
			$data["error"] = "Ошибка при выполнении запроса";
			$data["sqlerror"] = $link->error;
		endif;
	else:
		$data["error"] = "Ошибка при выполнении запроса";
		$data["sqlerror"] = $link->error;
	endif;
endif;

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
echo $jsonData;