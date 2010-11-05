<?php 


define('USERSESSION_KEY',  'SeR59On5ArY58qW7n31jDzwrjbQpPLub'); // 32 chars
define('USERSESSION_IVKEY', 'Jp9638NtUHK2C9dy'); // 16 chars
define('SHARED_IVKEY', 'Jpa45SNtUHK2C9dy'); // 16 chars

function ora_decrypt($data, $key, $iv, $isBase64 = false)
{
	if ($isBase64)
		$data = base64_decode($data);
		
	return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $iv));
}

function ora_encrypt($data, $key, $iv, $returnBase64 = false)
{
	if (!$returnBase64)
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $iv);
	else
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $iv));
}
function ora_getUniqueCode($length = "")
{	
	$code = md5(uniqid(rand(), true));
	if ($length != "") return substr($code, 0, $length);
	else return $code;
}

class Yamlifier
{
	public function getFields()
	{
		$getFields = create_function('$obj', 'return get_object_vars($obj);');
		return $getFields($this);
	}
	
	public function getYaml($section, $depth = 1)
	{
		if ($depth == 1)
			$data = "$section\r\n";
		else
			$data = "\r\n";
			
		$fields = $this->getFields();
		foreach ($fields as $field => $value) 
		{
			$data .= str_repeat("\t", $depth) . "$field: " . $this->getYamlValue($field, $depth + 1) . "\r\n";
		}
		
		return $data;
	}
	
	public function getYamlValue($field, $depth = 2)
	{
		if (is_bool($this->$field))
		{
			return $this->$field == true ? "true" : "false";
		}
			
		if (isset($this->$field) && $this->$field instanceof Yamlifier)
		{
			return $this->$field->getYaml($field, $depth);
		}
		return $this->$field;
	}
}

class MasterResult extends Yamlifier
{
	public $Success = true;
	public $Error = "";
}

class InformResult extends MasterResult
{
	public $EncryptedUserId = "";
}
class UserSession extends MasterResult
{
	public $Username = "";
	public $UserId = 0;
	public $Authenticated = false;
	public $FailureReason = "";
	public $UserKey = "01234567012345670123456701234567"; // Dynamicly generate me - must be 32 chars long
}

class GameSession extends MasterResult
{
	public $OwnerId = 0; // Store the user id
	public $GameId = 0;
	public $GameKey = "";// Dynamicly generate me - must be 32 chars long
}

$db = new PDO('sqlite:openra.db');