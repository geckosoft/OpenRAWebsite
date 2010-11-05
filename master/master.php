<?php

// Include the settings
include dirname(__FILE__) . '/shared.php';

$db = $db;
switch ($_GET['do'])
{	
	case 'register':
			$user_id = $_POST['user_id'];
			$user_key = $_POST['user_key'];		
			$game_key = ora_getUniqueCode(32);
			
			// First see if the user id & key is valid
			$sth = $db->prepare('select * from users where id = :id and userkey = :userkey AND banned = 0');
			$sth->bindParam(':id', $user_id, PDO::PARAM_INT);
			$sth->bindParam(':userkey', $user_key, PDO::PARAM_STR);
			$sth->execute();
			
			if (!$sth->fetch())
			{				
	 			$game = new GameSession();
	 			$game->Success = false;
	 			$game->Reason = "User session is not valid anymore.";
	 			die($game->getYaml("GameSession"));
			}
			
 			$game = new GameSession();
 					
			$sth = $db->prepare('INSERT INTO games (id, user_id, time_created, time_started, time_ended, game_key) VALUES (NULL, :user_id, :time, 0, 0, :game_key)');
			$sth->bindParam(':time', time(), PDO::PARAM_INT);
			$sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
			$sth->bindParam(':game_key', $game_key, PDO::PARAM_STR);
			$sth->execute();
				
 			$game->GameId = $db->lastInsertId();
 			$game->GameKey = $game_key;
 			$game->OwnerId  = $user_id;
 			
 			die($game->getYaml("GameSession"));
		break;
	case 'validate':
			$user_id = $_POST['user_id'];
			$game_id = $_POST['game_id'];
			
			$sth = $db->prepare('select * from playersessions where game_id = :game_id AND user_id = :user_id');
			$sth->bindParam(':game_id', $game_id, PDO::PARAM_INT);
			$sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
			$sth->execute();
			
			$result = new MasterResult();	
			if (!$sth->fetch())
			{		
				$result->Success = false;
				$result->Reason = "User not linked to this game.";
			}
			else
			{
				$result->Success = true;
			}
			die($result->getYaml("MasterResult"));
		break;
}