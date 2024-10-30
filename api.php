<?php if(!HL_XBOX_LOADED) die('Direct script access denied.');


/*
	Update one or more users
*/
function hl_xbox_api_update_users($user_id=false) {
	global $wpdb;
	
	$time_limit = 3600; // Only allow one update an hour, set by 3rd party API!
	$lines = array();
	$timer_start = microtime(true);
	
	// Get user(s) to update
	$sql = 'SELECT id, gamertag, gamerscore, api_last_updated FROM '.HL_XBOX_DB_PREFIX.'users WHERE api_last_updated<\''.date('Y-m-d H:i:s', date_i18n('U')-$time_limit).'\'';
	$user_id = intval($user_id);
	if($user_id>0) $sql .= $wpdb->prepare(' AND id=%d LIMIT 1', $user_id);
	$users_raw = $wpdb->get_results($sql);
	if($wpdb->num_rows==0) return array('status'=>'error', 'lines'=>array('No users who require an update were found. Please wait and try again later.'));
	$lines[] = 'Updating Xbox Live information for <strong>'.$wpdb->num_rows.'</strong> user(s)';
	foreach($users_raw as $user) $users[$user->id] = $user;
	
	// Get XML for user(s)
	$users_xml = hl_xbox_api_get_remote_data($users);
	if(!$users_xml) return array('status'=>'error', 'lines'=>array('No data was returned from Xbox Live. Please wait and try again later.'));
	
	$games = array();
	$usergames = array();
	
	foreach($users_xml as $user_id=>$xml) {
		
		if($xml->ProfileUrl=='') continue;
		$gamertag = (string) $xml->Gamertag;
		
		$sql = $wpdb->prepare('
			UPDATE '.HL_XBOX_DB_PREFIX.'users SET
				account_status=%s,
				info=%s,
				last_seen=%s,
				title=%s,
				profile_url=%s,
				tile_url=%s,
				reputation=%s,
				gamerscore=%s,
				zone=%s,
				api_last_updated=%s
			WHERE id=%d
		',
			strtolower($xml->AccountStatus),
			$xml->PresenceInfo->Info,
			date('Y-m-d H:i:s',strtotime($xml->PresenceInfo->LastSeen)),
			$xml->PresenceInfo->Title,
			$xml->ProfileUrl,
			$xml->TileUrl,
			$xml->Reputation,
			$xml->GamerScore,
			strtolower($xml->Zone),
			date_i18n('Y-m-d H:i:s'),
			$user_id
		);
		$wpdb->query($sql);
		$lines[] = 'Updated profile information for <strong>'. $gamertag .'</strong>';
		
		$gamerscore = (int) $xml->GamerScore;
		if($gamerscore>$users[$user_id]->gamerscore) {
			$sql = $wpdb->prepare(
				'INSERT INTO '.HL_XBOX_DB_PREFIX.'gamerscores (user_id, gamerscore, datetime) VALUES (%d, %d, %s)',
				$user_id, $gamerscore, date_i18n('Y-m-d H:i:s')
			);
			$wpdb->query($sql);
			$lines[] = 'Recorded a new gamerscore for <strong>'. $gamertag .'</strong>: '.$users[$user_id]->gamerscore.' > '.$gamerscore;
		}
		
		
		if($xml->RecentGames->XboxUserGameInfo) {
			foreach($xml->RecentGames->XboxUserGameInfo as $game) {
				
				$game_details_url = (string) $game->DetailsURL;
				$game_id = substr($game_details_url, strpos($game_details_url,'=')+1);
				$game_id = substr($game_id,0,strpos($game_id, '&'));
				
				if(!array_key_exists($game_id, $games)) {
					$games[$game_id] = $wpdb->prepare(
						'(%s, %s, %d, %d, %s, %s)',
						$game_id, $game->Game->Name, $game->Game->TotalAchievements, $game->Game->TotalGamerScore, $game->Game->Image32Url, $game->Game->Image64Url
					);
				}
				
				// games may be created AFTER this point, so store game_id then find out ACTUAL id
				$usergames[] = array(
					'game_id'=>$game_id,
					'user_id'=>$user_id,
					'first_played'=>date_i18n('Y-m-d H:i:s',strtotime($game->LastPlayed)),
					'last_played'=>date_i18n('Y-m-d H:i:s', strtotime($game->LastPlayed)),
					'current_achievements'=>(int) $game->Achievements,
					'current_gamerscore'=>(int) $game->GamerScore
				);
				
				
			} // end foreach: $game
		} // end if: RecentGames
		
		
	} // end foreach: $users_xml
	
	
	if(count($games)>0) {
		$sql = '
			INSERT INTO '.HL_XBOX_DB_PREFIX.'games
				(game_id, name, total_achievements, total_gamerscore, image_32_url, image_64_url)
			VALUES
				'.implode(', ', $games).'
			ON DUPLICATE KEY UPDATE
				total_achievements=VALUES(total_achievements), total_gamerscore=VALUES(total_gamerscore), image_32_url=VALUES(image_64_url), image_32_url=VALUES(image_64_url)
		';
		$wpdb->query($sql);
		if($wpdb->rows_affected>0) {
			$lines[] = $wpdb->rows_affected.' game(s) were created/updated.';
		}
	}
	
	
	if(count($usergames)>0) {
		
		// Games may be created AFTER a usergame, so map game_id from URL to game id in table later
		$game_id_map = array();
		$raw_games = $wpdb->get_results('SELECT id, game_id FROM '.HL_XBOX_DB_PREFIX.'games');
		foreach($raw_games as $game) $game_id_map[$game->game_id] = $game->id;
		
		$usergames_sql = array();
		foreach($usergames as $usergame) {
			if(array_key_exists($usergame['game_id'], $game_id_map)) {
				$usergames_sql[] = $wpdb->prepare(
					'(%d, %d, %s, %s, %d, %d)',
					$game_id_map[$usergame['game_id']], $usergame['user_id'], $usergame['first_played'], $usergame['last_played'], $usergame['current_achievements'], $usergame['current_gamerscore']
				);
			}
		}
		
		if(count($usergames_sql)>0) {
			$sql = '
				INSERT INTO '.HL_XBOX_DB_PREFIX.'usergames 
					(game_id, user_id, first_played, last_played, current_achievements, current_gamerscore)
				VALUES 
					'.implode(', ',$usergames_sql).'
				ON DUPLICATE KEY UPDATE
					last_played=VALUES(last_played), current_achievements=VALUES(current_achievements), current_gamerscore=VALUES(current_gamerscore)
			';
			$wpdb->query($sql);
			if($wpdb->rows_affected>0) {
				$lines[] = $wpdb->rows_affected.' usergame(s) information was updated.';
			}
		}
		
	} // end count: $usergames
	
	$lines[] = 'Import completed in '.round(microtime(true)-$timer_start, 3).' seconds';
	
	return array('status'=>'success', 'lines'=>$lines);
	
} // end func: hl_xbox_api_update_users



/*
	Pulls in remote data for user(s) with curl_multi
*/
function hl_xbox_api_get_remote_data($users) {
	
	foreach($users as $user) $users_outstanding[$user->id] = $user;
	$users = $users_oustanding;
	$curl = EpiCurl::getInstance();
	$requests_completed = false;
	$max_retries = 5;
	$data = array();
	
	while(!$requests_completed) {
		
		if($max_retries<=0) {
			break;
		}
		
		$requests = array();
		
		foreach($users_outstanding as $user_id=>$user) {
			$url = HL_XBOX_API_URL.urlencode($user->gamertag);
			$request = curl_init($url);
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
			$requests[$user_id] = $curl->addCurl($request);
		}
		
		foreach($requests as $user_id=>$response) {
			try {
				if($response->code==200 and $response->data!='') {
					$data[$user_id] = simplexml_load_string($response->data);
					unset($users_outstanding[$user_id]);
				}
			} catch(Exception $e) {}
		}
		
		if(count($users_outstanding)==0) {
			break;
		}
		
		$max_retries--;
		
	} // end while: requests_completed
	
	if(count($data)==0) {
		return false;
	}
	
	return $data;
	
} // end func: hl_xbox_api_get_remote_data

