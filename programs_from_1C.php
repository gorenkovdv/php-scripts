<?php
require("./dbconnection.php");

function listenersGroup($value){
	switch($value):
		case 0: return "Врачи";
		case 1: return "Средний медицинский персонал";
		case 2: return "Без образования";
		default: return "Без образования";
	endswitch;
}

function convertDate($value){
	if($value == "0001-01-01" || $value == "0001-01-02") return "NULL";
	return date("Y-m-d", strtotime($value));
}

function parseAdditionalSpecialities($value){
	$string = "";
	$arr = explode("|", $value); $i = 0;
	foreach($arr as $sp):
		if($i > 0 && strlen($sp) > 0) $string .= ", ";
		$string .= $sp;
		if(strlen($sp) > 0) $i++;
	endforeach;
	
	return $string;
}

function program_from_1C($dbCourses, $debug = false){
	global $link;
	try{
	//	$wsdl = new SoapClient("http://1C.asmu.local/dpo/ws/ws4.1cws?wsdl",
		$wsdl = new SoapClient(
			"http://1C.asmu.local/dpo2/ws/ws4.1cws?wsdl",
			array(
				'login'			=> "web",
				'password'		=> "web", //Dtl0vjcnM
				'trace'			=> 1,
				'soap_version'	=> SOAP_1_2,
				'cache_wsdl'	=> WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
				'encoding'		=> 'UTF-8',
				'exceptions'	=> 0
			)
		);
	}
	catch(SoapFault $e) { }

	IF(!is_soap_fault($wsdl)):
		$idc = $wsdl;
		// за последнюю неделю $x['Name'] = date("d.m.Y",time()-60*60*24*20);
		// за все время
		$x['Name'] = "02.01.0001";
		$resultF =  $idc->__soapCall('GetCourseList', array('parameters' => $x));
		if($debug):
			if (is_soap_fault($resultF)):
				trigger_error("SOAP Fault: (faultcode: {$resultF->faultcode}, faultstring: {$resultF->faultstring})", E_ERROR);
			endif;
			//echo "<pre>"; print_r($resultF); echo "</pre>";
		endif;
		
		if (!is_soap_fault($resultF)):
			$res1 = $resultF;
			$res = (array)$res1->return->Course;
			
			$opp_arr = array();
			foreach ($res as $ob_opp):
				$opp_arr[] = (array)$ob_opp;
			endforeach;
			
			$insert_arr = array();
			$departments = array();
			
			$i = 0;
			foreach($opp_arr as $fields):
				if($field["DelStatus"] != 1 && !isset($dbCourses[$fields["UIDCourse"]])):
					$insert_arr[] = array(
						"Name" => $fields["CourseName"],
						"GUID" => $fields["UIDCourse"],
						"Price" => intval($fields["Cost"]),
						"Speciality" => $fields["Spec"],
						"AdditionalSpecialities" => parseAdditionalSpecialities($fields["DopSpec"]),
						"EGS" => $fields["UGS"],
						"ProfEducationType" => $fields["Kind"],
						"Volume" => $fields["Chasov"],
						"BeginDate" => convertDate($fields["DateBeg"]),
						"EndDate" => convertDate($fields["DateEnd"]),
						"EducationForm" => $fields["Form"],
						"Department" => $fields["Kafedra"],
						"DepartmentGUID" => $fields["UIDKafedra"],
						"ListenersGroup" => listenersGroup($fields["Forwho"]),
						"IsCME" => $fields["NMOStatus"],
						"MoodleID" => ($fields["MoodleID"] != "") ? $fields["MoodleID"] : "NULL",
						"Territory" => $fields["Territory"],
						"FullTimeBeginDate" => convertDate($fields["DateOch"]),
						"FullTimeEndDate" => convertDate($fields["DateOchEnd"]),
						"RequestDate" => convertDate($fields["DateZ"]),
						"SertificationExamDate" => convertDate($fields["DateExam"]),
						"IsEducationDistance" => $fields["DateOch"] == '0001-01-02' ? 1 : 0,
					);
				endif;
				
				if(!isset($departments[$fields["UIDKafedra"]])):
						$departments[$fields["UIDKafedra"]] = array(
							"name" => $fields["Kafedra"],
							"GUID" => $fields["UIDKafedra"]
						);
				endif;
			endforeach;
			
			//echo "<pre>"; print_r($insert_arr); echo "</pre>";
			//echo "<pre>"; print_r($departments); echo "</pre>";
			
			foreach($departments as $department):
				$sql = "INSERT INTO `departments` (`name`, `GUID`) VALUES ('".$department["name"]."', '".$department["GUID"]."')";
				//$link->query($sql);
			endforeach;
			
			IF(count($insert_arr) > 0):
				$headers = ""; $i = 0;
				foreach(array_keys($insert_arr[0]) as $key):
					if($i > 0): $headers .= ","; endif;
					$headers .= $key;
					$i++;
				endforeach;
				
				$values = ""; $i = 0;
				foreach($insert_arr as $fields):
					if($i > 0): $values .= ","; endif;
					
					$values .= "("; $j = 0;
					foreach($fields as $key => $value):
						if($j > 0): $values .= ","; endif;
						$values .= ($value != "NULL") ? "'".$value."'" : $value; $j++;
					endforeach;
					$values .= ")"; $i++;
				endforeach;
				
				$sql = "INSERT INTO `courses` (".$headers.") VALUES ".$values;
				//echo "<p style='word-break: break-all'>"; print_r($sql); echo "</p>";
				//if(!$link->query($sql)) echo $link->error;
			ENDIF;
		endif;
	ENDIF;
}

$dbCourses = array();
$sql = "SELECT * FROM `courses`";
if($dbResult = $link->query($sql)):
	while($arResult = $dbResult->fetch_assoc()):
		$dbCourses[$arResult["GUID"]] = 1;
	endwhile;
	
	program_from_1C($dbCourses);
else:
	echo "Ошибка при выполнении запроса: ".$link->error;
endif;