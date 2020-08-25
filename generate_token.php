<?php
class GenerateToken {
	function generate($ip){
		return base64_encode(microtime(true).$ip);
	}
}