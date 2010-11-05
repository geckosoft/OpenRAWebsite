<?php

// Include the settings
include dirname(__FILE__) . '/shared.php';
$db = $db;
switch ($_GET['do'])
{
	case 'login':
			$username = $_POST['username'];
			$password = ora_decrypt($_POST['password'], USERSESSION_KEY, USERSESSION_IVKEY, true);			
			
			// Strip possible hax
			$username = str_replace('%', '', $username);
			$username = str_replace('_', '', $username);
			
			$sth = $db->prepare('select * from users where username LIKE :username and password = :password');
			$sth->bindParam(':username', $username, PDO::PARAM_STR);
			$sth->bindParam(':password', $password, PDO::PARAM_STR);
			$sth->execute();
			$data = $sth->fetch();
			
			
 			$user = new UserSession();
 			$user->Username = $username; 			
			if (!$data || $data['banned'] == 1)
			{
				$user->UserId = 0;
				$user->Success = false;
				if ($data == null)
					$user->Error = "No user found with given username/password";
				else
					$user->Error = "User banned from the master server.";
				
 				die($user->getYaml("UserSession"));
			}
			
 			$user->UserId = $data['id'];
 			$user->Success = true;
 			$user->UserKey = ora_getUniqueCode(32);
 			
 			// Update the user key 
 			$sth = $db->prepare('UPDATE users SET userkey = :userkey, time_seen = :time WHERE id = :id');
			$sth->bindParam(':userkey', $user->UserKey, PDO::PARAM_STR);
			$sth->bindParam(':time', time(), PDO::PARAM_INT);
			$sth->bindParam(':id', $user->UserId, PDO::PARAM_INT);
			$sth->execute();
			
 			die($user->getYaml("UserSession"));			
		break;

	case 'inform':
			$user_id = $_POST['user_id'];
			$user_key = $_POST['user_key'];		
			$game_id = $_POST['game_id'];			
			

			
			// Then see if there is already a playersession active
			//$db->beginTransaction();
			// First see if the user id & key is (still) valid!
			$sth = $db->prepare('select * from users where id = :id and userkey = :userkey AND banned = 0');
			$sth->bindParam(':id', $user_id, PDO::PARAM_INT);
			$sth->bindParam(':userkey', $user_key, PDO::PARAM_STR);
			$sth->execute();
			
			if (!$sth->fetch())
			{
	 			$result = new InformResult();
	 			$result->Success = false;
	 			$result->Reason = "User session not valid anymore.";
	 			$result->EncryptedUserId = "";
	 			die($result->getYaml("InformSessionResult"));				
			}
			$sth->closeCursor();
			
			// Then get the game
			$sth = $db->prepare('select * from games where id = :id');
			$sth->bindParam(':id', $game_id, PDO::PARAM_INT);
			$sth->execute();
			
			$gameData = $sth->fetch();
			
			if (!$gameData)
			{
	 			$result = new InformResult();
	 			$result->Success = false;
	 			$result->Reason = "Game not found.";
	 			$result->EncryptedUserId = "";
	 			die($result->getYaml("InformSessionResult"));				
			}
			$sth->closeCursor();

			$sth = $db->prepare('select * from playersessions where game_id = :game_id AND user_id = :user_id');
			$sth->bindParam(':game_id', $game_id, PDO::PARAM_INT);
			$sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
			$sth->execute();
			$data = $sth->fetch();
			
			$sth->closeCursor();
			if ($data != false)
			{
				// Update
				$sth = $db->prepare('UPDATE playersessions SET joins = joins + 1, time_informed = :time WHERE id = :entry_id');
				$sth->bindParam(':time', time(), PDO::PARAM_INT);
				$sth->bindParam(':entry_id', $data['id'], PDO::PARAM_INT);
				$sth->execute();
				$sth->closeCursor();
			}else{
				// Insert				
				$sth = $db->prepare('INSERT INTO playersessions (id, joins, time_informed, user_id, game_id, ingame) VALUES (NULL, 1, :time, :user_id, :game_id, 0)');
				$sth->bindParam(':time', time(), PDO::PARAM_INT);
				$sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
				$sth->bindParam(':game_id', $game_id, PDO::PARAM_INT);
				$sth->execute();
				$sth->closeCursor();
			}
 		//	$db->commit();
			
 			$result = new InformResult();
 			$result->Success = true;
 			$result->EncryptedUserId = ora_encrypt($user_id, $gameData['game_key'], SHARED_IVKEY, true);
 			die($result->getYaml("InformSessionResult"));
 			
 			break;
}