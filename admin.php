<?php if(!HL_XBOX_LOADED) die('Direct script access denied.');

/*
  Add admin menu pages
/*/
function hl_xbox_admin_menu() {
  add_menu_page('Xbox', 'Xbox', 'manage_options', 'hl_xbox_users', 'hl_xbox_view_users', HL_XBOX_URL.'menu_icon.png');
  add_submenu_page('hl_xbox_users', 'Users', 'Users', 'manage_options', 'hl_xbox_users', 'hl_xbox_view_users');
  add_submenu_page('hl_xbox_users', 'Games', 'Games', 'manage_options', 'hl_xbox_games', 'hl_xbox_view_games');
} // end func: hl_xbox_admin_menu





/*
	Update a single User via AJAX
*/
add_action('wp_ajax_hl_xbox_ajax_update_user', 'hl_xbox_ajax_update_user');
function hl_xbox_ajax_update_user() {
	$user_id = intval($_POST['user_id']);
	if($user_id<=0) die('Error updating.');
	$result = hl_xbox_api_update_users($user_id);
	if($result['status']!='success') {
		if($result['lines'][0]=='No users who require an update were found. Please wait and try again later.') {
			die('Updated too recently.');
		}
		die('Error updating.');
	}
	echo 'Updated!';
	die();
} // end func: hl_xbox_ajax_update_user


/*
	Update a all Users via AJAX
*/
add_action('wp_ajax_hl_xbox_ajax_update_all_users', 'hl_xbox_ajax_update_all_users');
function hl_xbox_ajax_update_all_users() {
	$result = hl_xbox_api_update_users();
	if($result['status']!='success') {
		if($result['lines'][0]=='No users who require an update were found. Please wait and try again later.') {
			die('Updated too recently.');
		}
		die('Error updating.');
	}
	echo 'Updated!';
	die();
} // end func: hl_xbox_ajax_update_all_users



/*
	List all users
*/
function hl_xbox_view_users() {
	global $wpdb;
	if($_GET['action']=='edit') return hl_xbox_edit_user($_GET['id']);
	if($_GET['action']=='delete') return hl_xbox_delete_user($_GET['id']);
	
	$uri_args = array();
	$where_conditions = array();
	if($_GET['s']!='') {
		$search_string = stripslashes($_GET['s']);
		$uri_args['s'] = hl_e($search_string);
	}
	if($search_string) {
		$total_objects = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.HL_XBOX_DB_PREFIX.'users WHERE MATCH(gamertag) AGAINST (%s)', $search_string));
	} else {
		$total_objects = $wpdb->get_var('SELECT COUNT(*) FROM '.HL_XBOX_DB_PREFIX.'users');
	}
	$per_page = 10;
	$num_pages = ceil($total_objects/$per_page);
	$current_page = ($_GET['start']>0)?intval($_GET['start']):1;
	$pagination_url = 'admin.php?page=hl_xbox_users';
	foreach($uri_args as $uri_key=>$uri_val) if($uri_val!='') $pagination_url .= '&'.$uri_key.'='.$uri_val;
	$pagination = paginate_links(array(
		'base' => $pagination_url.'%_%',
		'format' => '&start=%#%',
		'total' => $num_pages,
		'current' => $current_page
	));
	if($search_string) {
		$sql = $wpdb->prepare('
			SELECT id, gamertag, gamerscore, last_seen, api_last_updated
			FROM '.HL_XBOX_DB_PREFIX.'users
			WHERE MATCH(gamertag) AGAINST (%s)
			ORDER BY gamertag ASC
			LIMIT %d, %d
		', $search_string, ($current_page-1)*$per_page, $per_page);
	} else{
		$sql = $wpdb->prepare('
			SELECT id, gamertag, gamerscore, last_seen, api_last_updated
			FROM '.HL_XBOX_DB_PREFIX.'users
			ORDER BY gamertag ASC
			LIMIT %d, %d
		', ($current_page-1)*$per_page, $per_page);
	}
	$objects = $wpdb->get_results($sql);
	$num_objects = ($objects)?count($objects):0;
	
?>
<div class="wrap">
<form method="get" action="<?php echo $pagination_url; ?>">
	<input type="hidden" name="page" value="hl_xbox_users" />
	
	<h2>Xbox Users</h2>
	
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="text" value="<?php echo hl_e($search_string); ?>" name="s" />
			<input type="submit" value="Search" class="button-secondary" />
		</div>
		<div class="tablenav-pages">
			<span class="displaying-num">Displaying <?php echo number_format(($current_page-1)*$per_page+1); ?>&ndash;<?php echo number_format(($current_page-1)*$per_page+$num_objects); ?> of <?php echo number_format($total_objects); ?></span>
			<?php echo $pagination; ?>
		</div>
	</div>
	<table class="widefat post fixed" cellspacing="0" style="clear: none;">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-title" width="75">&nbsp;</th>
				<th scope="col" class="manage-column column-title">Gamertag</th>
				<th scope="col" class="manage-column column-title">Gamerscore</th>
				<th scope="col" class="manage-column column-title">Last Online</th>
				<th scope="col" class="manage-column column-title">Last Updated</th>
				<th scope="col" class="manage-column column-title" width="95">&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-title">&nbsp;</th>
				<th scope="col" class="manage-column column-title">Gamertag</th>
				<th scope="col" class="manage-column column-title">Gamerscore</th>
				<th scope="col" class="manage-column column-title">Last Online</th>
				<th scope="col" class="manage-column column-title">Last Updated</th>
				<th scope="col" class="manage-column column-title">&nbsp;</th>
			</tr>
		</tfoot>
		<tbody>
			<?php if($num_objects>0): ?>
				<?php foreach($objects as $object): ?>
					<tr>
						<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo hl_e($object->id); ?>"><img src="<?php echo hl_xbox_get_user_image_url($object->id); ?>" /></a></td>
						<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo hl_e($object->id); ?>"><?php echo hl_e($object->gamertag); ?></a></td>
						<td><?php echo number_format($object->gamerscore); ?></td>
						<td><?php echo date('Y-m-d H:i',strtotime($object->last_seen)); ?></td>
						<td><?php echo date('Y-m-d H:i',strtotime($object->api_last_updated)); ?></td>
						<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo hl_e($object->id); ?>">edit</a> | <a href="admin.php?page=hl_xbox_users&amp;action=delete&amp;id=<?php echo hl_e($object->id); ?>">delete</a></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr><td colspan="4">No users were found.</td></tr>
			<?php endif; ?>
		</tbody>
	</table>
	<div class="tablenav">
		<div class="alignleft actions">
			<p>
				<a href="admin.php?page=hl_xbox_users&amp;action=edit" class="button-primary">Add new user</a>
				<span class="button-secondary hl_xbox_ajax_update_all_users">Update now</span>
			</p>
		</div>
		<div class="tablenav-pages">
			<span class="displaying-num">Displaying <?php echo number_format(($current_page-1)*$per_page+1); ?>&ndash;<?php echo number_format(($current_page-1)*$per_page+$num_objects); ?> of <?php echo number_format($total_objects); ?></span>
			<?php echo $pagination; ?>
		</div>
	</div>
</form>
</div>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	
	jQuery(".hl_xbox_ajax_update_all_users").click(function(){
		var button = jQuery(this);
		button.text('Working...');
		jQuery.post(ajaxurl, { action: 'hl_xbox_ajax_update_all_users' }, function(response) {
			button.text(response);
		});
		return false;
	});

});
</script>
<?php
} // end func: hl_xbox_view_users





/*
	Edit user information
*/
function hl_xbox_edit_user($id=false) {
	global $wpdb;
	
	if($_GET['subaction']=='edit_usergame') return hl_xbox_edit_usergame($_GET['id'], $_GET['game_id']);
	if($_GET['subaction']=='delete_usergame') return hl_xbox_delete_usergame($_GET['id'], $_GET['game_id']);
	
	$id = intval($id);
	if($id>0) {
		$object = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.HL_XBOX_DB_PREFIX.'users WHERE id=%d LIMIT 1',$id));
		if(!$object) return hl_xbox_error('The selected user could not be found.');
		$object_gamertag = $object->gamertag;
		$action = 'edit';
	} else {
		$action = 'new';
		$object = (object) array('id'=>0, 'gamertag'=>'', 'gamerscore'=>0, 'api_last_updated'=>0);
	}
		
	if($_POST['submit']) {
		$object->gamertag = stripslashes($_POST['object']['gamertag']);
		if($object->gamertag!='') {
			if($action=='edit') {
				if($object->gamertag!=$object_gamertag) {
					$gamertag_valid = hl_xbox_api_check_new_gamertag($object->gamertag);
					if($gamertag_valid) {
						$wpdb->query($wpdb->prepare(
							'UPDATE '.HL_XBOX_DB_PREFIX.'users SET gamertag=%s WHERE id=%d LIMIT 1',
							$object->gamertag, $object->id
						));
						$msg_updated = 'User saved successfully.';
					} else {
					$msg_error = 'Error, please check this is a valid gamertag on Xbox Live and that it does not already exist on this site. If this error continues please try again later as the Xbox API may be experiencing technical difficulty.';
					}
				} else {
					$msg_updated = 'User saved successfully.';
				}
			} // end EDIT
			if($action=='new') {
				$gamertag_valid = hl_xbox_api_check_new_gamertag($object->gamertag);
				if($gamertag_valid) {
					$wpdb->query($wpdb->prepare('INSERT INTO '.HL_XBOX_DB_PREFIX.'users (gamertag, api_last_updated) VALUES (%s, %s)', $object->gamertag, '1970-01-01'));
					$object->id = $wpdb->insert_id;
					hl_xbox_api_update_users($object->id); // CREATE NEW USER INFORMATION FROM XBOX LIVE!!!
					$action = 'edit';
					$msg_updated = 'The gamertag has been added to the database and profile is now being fetched, this process should only take a moment.';
				} else {
					$msg_error = 'Error, please check this is a valid gamertag on Xbox Live and that it does not already exist on this site. If this error continues please try again later as the Xbox API may be experiencing technical difficulty.';
				}
			} // end NEW
		} else {
			$msg_error = 'Gamertag is a required field.';
		}
	} // end POST
	
	
	if($action!='new') {
		$gamerscores = $wpdb->get_results($wpdb->prepare('
			SELECT gamerscore, datetime
			FROM '.HL_XBOX_DB_PREFIX.'gamerscores
			WHERE user_id = %d
			ORDER BY datetime DESC
		', $object->id));
		$num_gamerscores = $wpdb->num_rows;
		if($num_gamerscores>0) {
			$json_gamerscore = array();
			foreach($gamerscores as $gamerscore) {
				$json_gamerscore[] = array( strtotime($gamerscore->datetime)*1000, $gamerscore->gamerscore );
			}
			$json_gamerscore = array($json_gamerscore);
		}
		
		$games = $wpdb->get_results($wpdb->prepare('
			SELECT g.id, g.name, g.total_achievements, g.total_gamerscore, ug.last_played, ug.current_gamerscore, ug.current_achievements
			FROM '.HL_XBOX_DB_PREFIX.'usergames AS ug
			JOIN '.HL_XBOX_DB_PREFIX.'games AS g on ug.game_id = g.id
			WHERE ug.user_id = %d
			ORDER BY ug.last_played DESC
		', $object->id));
		$num_games = $wpdb->num_rows;
	}
	
?>

<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo HL_XBOX_URL; ?>excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo HL_XBOX_URL; ?>jquery.flot.0.6.min.js"></script>

<div class="wrap">

	<?php if($msg_updated): ?><div class="updated"><p><?php echo $msg_updated; ?></p></div><?php endif; ?>
	<?php if($msg_error): ?><div class="error"><p><?php echo $msg_error; ?></p></div><?php endif; ?>
	
	<h2>Xbox User Details</h2>
	
	<form method="post" action="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $object->id; ?>">
		<table class="form-table">
			<tr>
				<th scope="row">Gamertag</th>
				<td>
					<input type="text" name="object[gamertag]" size="40" value="<?php echo hl_e($object->gamertag); ?>" />
					<br /><span class="description">Changing your gamertag will reset all details on the next automatic update.</span>
				</td>
			</tr>
		</table>
		<div class="submit">
			<a href="admin.php?page=hl_xbox_users" class="button-secondary">Cancel</a>
			<input type="submit" name="submit" value="Save" class="button-primary" />
		</div>
	</form>
	
	<?php if($action!='new'): ?>
		<div style="width: 60%">
			
			<?php if($num_gamerscores>0): ?>
				<h2>Gamerscore history</h2>
				<div id="xbox_user_gamerscore_graph" style="width:100%;height:320px;"></div>
				<script type="text/javascript">
				jQuery(document).ready(function($){
					var data = <?php echo json_encode($json_gamerscore); ?>;
					var opts = {
						xaxis: { mode: "time" }
					};
					$.plot(
						$("#xbox_user_gamerscore_graph"),
						data,
						opts
					);
				});
				</script>
			<?php endif; ?>
			
			
			<h2>Games played&hellip;</h2>
			<table class="widefat post fixed" cellspacing="0" style="clear: none;">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-title" width="75">&nbsp;</th>
						<th scope="col" class="manage-column column-title">Game</th>
						<th scope="col" class="manage-column column-title" width="120">Last played</th>
						<th scope="col" class="manage-column column-title" width="120">Achievements</th>
						<th scope="col" class="manage-column column-title" width="120">Gamerscore</th>
						<th scope="col" class="manage-column column-title" width="120">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-title">&nbsp;</th>
						<th scope="col" class="manage-column column-title">Game</th>
						<th scope="col" class="manage-column column-title">Last played</th>
						<th scope="col" class="manage-column column-title">Achievements</th>
						<th scope="col" class="manage-column column-title">Gamerscore</th>
						<th scope="col" class="manage-column column-title" width="120">&nbsp;</th>
					</tr>
				</tfoot>
				<tbody>
					<?php if($num_games>0): ?>
						<?php foreach($games as $game): ?>
							<tr>
								<td><a href="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo $game->id; ?>"><img src="<?php echo hl_xbox_get_game_image_url($game->id, 64); ?>" /></a></td>
								<td><a href="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo $game->id; ?>"><?php echo hl_e($game->name); ?></a></td>
								<td><?php echo hl_e(date('Y-m-d', strtotime($game->last_played))); ?></td>
								<td><?php echo hl_e($game->current_achievements); ?>/<?php echo hl_e($game->total_achievements); ?> (<?php echo hl_percent($game->current_achievements,$game->total_achievements); ?>%)</td>
								<td><?php echo hl_e($game->current_gamerscore); ?>/<?php echo hl_e($game->total_gamerscore); ?> (<?php echo hl_percent($game->current_gamerscore,$game->total_gamerscore); ?>%)</td>
								<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $object->id; ?>&amp;subaction=edit_usergame&amp;game_id=<?php echo $game->id; ?>">edit</a> | <a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $object->id; ?>&amp;subaction=delete_usergame&amp;game_id=<?php echo $game->id; ?>">delete</a></td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr><td colspan="5">No games played.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			
		</div>
		<p><br /></p>
	<?php endif; ?>
	
</div>
<?php
} // end func: hl_xbox_edit_user


/*
	Delete a user and any related usergames
*/
function hl_xbox_delete_user($id) {
	global $wpdb;
	$id = intval($id);
	if(isset($_POST['submit'])) {
		$wpdb->query($wpdb->prepare('DELETE FROM '.HL_XBOX_DB_PREFIX.'users WHERE id=%d LIMIT 1',$id));
		$wpdb->query($wpdb->prepare('DELETE FROM '.HL_XBOX_DB_PREFIX.'usergames WHERE user_id=%d',$id));
		?>
		<div class="wrap">
			<h2>Delete User</h2>
			<p>The selected user has been deleted.</p>
			<a href="admin.php?page=hl_xbox_users" class="button-primary">Back</a>
		</div>
		<?php
		return;
	}
	?>
	<div class="wrap">
		<h2>Delete User</h2>
		<p>Are you sure you wish to permanently delete this user along with any gameplay information for their games?</p>
		<form method="post" action="admin.php?page=hl_xbox_users&amp;action=delete&amp;id=<?php echo $id; ?>">
			<p>
				<a href="admin.php?page=hl_xbox_users" class="button-secondary">Cancel</a>
				<input type="submit" name="submit" value="Delete" class="button-primary" />
			</p>
		</form>
	</div>
	<?php
} // end func: hl_xbox_delete_user


/*
	Edit a usergame record
*/
function hl_xbox_edit_usergame($user_id, $game_id) {
	global $wpdb;
	$user_id = intval($user_id);
	$game_id = intval($game_id);
	
	$object = $wpdb->get_row($wpdb->prepare('
		SELECT ug.*, g.name, g.total_achievements, g.total_gamerscore
		FROM '.HL_XBOX_DB_PREFIX.'usergames AS ug
		LEFT JOIN '.HL_XBOX_DB_PREFIX.'games AS g ON ug.game_id=g.id
		WHERE ug.game_id=%d AND ug.user_id=%d 
		LIMIT 1
	', $game_id, $user_id));
	if(!$object) return hl_xbox_error('The selected user game could not be found.');
	
	if($_POST['submit']) {
		$object_first_played = strtotime($_POST['object']['first_played']);
		$object_last_played = strtotime($_POST['object']['last_played']);
		$object->current_achievements = intval($_POST['object']['current_achievements']);
		$object->current_gamerscore = intval($_POST['object']['current_gamerscore']);
		$y2k = 946684800; // Xbox Live was launched in Nov 02
		
		if($object_first_played>$y2k and $object_last_played>$y2k and $object->current_achievements<=$object->total_achievements and $object->current_gamerscore<=$object->total_gamerscore) {
		
			$object->first_played = date('Y-m-d H:i:s',$object_first_played);
			$object->last_played = date('Y-m-d H:i:s',$object_last_played);
			$wpdb->query($wpdb->prepare(
				'UPDATE '.HL_XBOX_DB_PREFIX.'usergames SET first_played=%s, last_played=%s, current_achievements=%d, current_gamerscore=%d WHERE game_id=%d AND user_id=%d LIMIT 1'
				, $object->first_played, $object->last_played, $object->current_achievements, $object->current_gamerscore, $game_id, $user_id
			));
			$msg_updated = 'User game saved successfully.';
		} else {
			$msg_error = 'Please make sure have entered correct data and try again.</p><p>e.g. dates must be in the correct format, gamerscore cannot be above the maximum for this game.';
		}
	} // end POST
	
?>
<div class="wrap">

	<?php if($msg_updated): ?><div class="updated"><p><?php echo $msg_updated; ?></p></div><?php endif; ?>
	<?php if($msg_error): ?><div class="error"><p><?php echo $msg_error; ?></p></div><?php endif; ?>
	
	<h2>Xbox User Game Details</h2>
	<p>Update this users details for this game using the form below. Please note that these fields may be changed when automatically updated if they are not the same as on Xbox Live.</p>
	
	<form method="post" action="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user_id; ?>&amp;subaction=edit_usergame&amp;game_id=<?php echo $game_id; ?>">
		<table class="form-table">
			<tr>
				<th scope="row">Game</th>
				<td><a href="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo $object->game_id; ?>"><?php echo $object->name; ?></a></td>
			</tr>
			<tr>
				<th scope="row">First played</th>
				<td>
					<input type="text" name="object[first_played]" size="40" value="<?php echo hl_e($object->first_played); ?>" />
					<span class="description">yyyy-mm-dd hh:mm:ss</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Last played</th>
				<td>
					<input type="text" name="object[last_played]" size="40" value="<?php echo hl_e($object->last_played); ?>" />
					<span class="description">yyyy-mm-dd hh:mm:ss</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Achievements</th>
				<td>
					<input type="text" name="object[current_achievements]" size="40" value="<?php echo hl_e($object->current_achievements); ?>" />
					<span class="description">Out of <strong><?php echo $object->total_achievements; ?></strong> possible achievements</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Gamerscore</th>
				<td>
					<input type="text" name="object[current_gamerscore]" size="40" value="<?php echo hl_e($object->current_gamerscore); ?>" />
					<span class="description">Out of <strong><?php echo $object->total_gamerscore; ?></strong> possible points</span>
				</td>
			</tr>
			
		</table>
		<div class="submit">
			<a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user_id; ?>" class="button-secondary">Cancel</a>
			<input type="submit" name="submit" value="Save" class="button-primary" />
		</div>
	</form>
</div>
<?php
} // end func: hl_xbox_edit_usergame


/*
	Delete a usergame record
*/
function hl_xbox_delete_usergame($user_id, $game_id) {
	global $wpdb;
	$user_id = intval($user_id);
	$game_id = intval($game_id);
	if(isset($_POST['submit'])) {
		$wpdb->query($wpdb->prepare('DELETE FROM '.HL_XBOX_DB_PREFIX.'usergames WHERE user_id=%d AND game_id=%d LIMIT 1',$user_id, $game_id));
		?>
		<div class="wrap">
			<h2>Delete User Game</h2>
			<p>The selected game data for this user has been deleted.</p>
			<a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user_id; ?>" class="button-primary">Back</a>
		</div>
		<?php
		return;
	}
	?>
	<div class="wrap">
		<h2>Delete User Game</h2>
		<p>Are you sure you wish to permanently delete the user data associated with this game? The game itself will not be deleted.</p>
		<form method="post" action="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user_id; ?>&amp;subaction=delete_usergame&amp;game_id=<?php echo $game_id; ?>">
			<p>
				<a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user_id; ?>" class="button-secondary">Cancel</a>
				<input type="submit" name="submit" value="Delete" class="button-primary" />
			</p>
		</form>
	</div>
	<?php
} // end func: hl_xbox_delete_usergame
















/*
	Display all games
*/
function hl_xbox_view_games() {
	global $wpdb;
	if($_GET['action']=='edit') return hl_xbox_edit_game($_GET['id']);
	if($_GET['action']=='delete') return hl_xbox_delete_game($_GET['id']);
	
	$uri_args = array();
	$where_conditions = array();
	if($_GET['s']!='') {
		$search_string = stripslashes($_GET['s']);
		$uri_args['s'] = hl_e($search_string);
	}
	if($search_string) {
		$total_objects = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.HL_XBOX_DB_PREFIX.'games WHERE MATCH(name) AGAINST (%s)', $search_string));
	} else {
		$total_objects = $wpdb->get_var('SELECT COUNT(*) FROM '.HL_XBOX_DB_PREFIX.'games');
	}
	$per_page = 20;
	$num_pages = ceil($total_objects/$per_page);
	$current_page = ($_GET['start']>0)?intval($_GET['start']):1;
	$pagination_url = 'admin.php?page=hl_xbox_games';
	foreach($uri_args as $uri_key=>$uri_val) if($uri_val!='') $pagination_url .= '&'.$uri_key.'='.$uri_val;
	$pagination = paginate_links(array(
		'base' => $pagination_url.'%_%',
		'format' => '&start=%#%',
		'total' => $num_pages,
		'current' => $current_page
	));
	if($search_string) {
		$sql = $wpdb->prepare('
			SELECT *
			FROM '.HL_XBOX_DB_PREFIX.'games
			WHERE MATCH(name) AGAINST (%s)
			ORDER BY name ASC
			LIMIT %d, %d
		', $search_string, ($current_page-1)*$per_page, $per_page);
	} else{
		$sql = $wpdb->prepare('
			SELECT *
			FROM '.HL_XBOX_DB_PREFIX.'games
			ORDER BY name ASC
			LIMIT %d, %d
		', ($current_page-1)*$per_page, $per_page);
	}
	$objects = $wpdb->get_results($sql);
	$num_objects = ($objects)?count($objects):0;
	

?>
<div class="wrap">
<form method="get" action="<?php echo $pagination_url; ?>">
	<input type="hidden" name="page" value="hl_xbox_games" />
	
	<h2>Xbox Games</h2>
	
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="text" value="<?php echo hl_e($search_string); ?>" name="s" />
			<input type="submit" value="Search" class="button-secondary" />
		</div>
		<div class="tablenav-pages">
			<span class="displaying-num">Displaying <?php echo number_format(($current_page-1)*$per_page+1); ?>&ndash;<?php echo number_format(($current_page-1)*$per_page+$num_objects); ?> of <?php echo number_format($total_objects); ?></span>
			<?php echo $pagination; ?>
		</div>
	</div>
	<table class="widefat post fixed" cellspacing="0" style="clear: none;">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-title" width="75">&nbsp;</th>
				<th scope="col" class="manage-column column-title">Name</th>
				<th scope="col" class="manage-column column-title">Achievements</th>
				<th scope="col" class="manage-column column-title">Gamerscore</th>
				<th scope="col" class="manage-column column-title" width="95">&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-title">&nbsp;</th>
				<th scope="col" class="manage-column column-title">Name</th>
				<th scope="col" class="manage-column column-title">Achievements</th>
				<th scope="col" class="manage-column column-title">Gamerscore</th>
				<th scope="col" class="manage-column column-title">&nbsp;</th>
			</tr>
		</tfoot>
		<tbody>
			<?php if($num_objects>0): ?>
				<?php foreach($objects as $object): ?>
					<tr>
						<td><a href="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo hl_e($object->id); ?>"><img src="<?php echo hl_xbox_get_game_image_url($object->id, 64); ?>" /></a></td>
						<td><a href="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo hl_e($object->id); ?>"><?php echo hl_e($object->name); ?></a></td>
						<td><?php echo hl_e($object->total_achievements); ?></td>
						<td><?php echo hl_e($object->total_gamerscore); ?></td>
						<td><a href="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo hl_e($object->id); ?>">edit</a> | <a href="admin.php?page=hl_xbox_games&amp;action=delete&amp;id=<?php echo hl_e($object->id); ?>">delete</a></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr><td colspan="5">No games were found.</td></tr>
			<?php endif; ?>
		</tbody>
	</table>
	<div class="tablenav">
		<div class="tablenav-pages">
			<span class="displaying-num">Displaying <?php echo number_format(($current_page-1)*$per_page+1); ?>&ndash;<?php echo number_format(($current_page-1)*$per_page+$num_objects); ?> of <?php echo number_format($total_objects); ?></span>
			<?php echo $pagination; ?>
		</div>
	</div>
</form>
</div>
<?php
} // end func: hl_xbox_view_games


/*
	Edit game form, + users who have played it
*/
function hl_xbox_edit_game($id) {
	global $wpdb;
	$id = intval($id);
	$object = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.HL_XBOX_DB_PREFIX.'games WHERE id=%d LIMIT 1',$id));
	if(!$object) return hl_xbox_error('The selected game could not be found.');
	$users = $wpdb->get_results($wpdb->prepare('
		SELECT u.id, u.gamertag, ug.current_gamerscore, ug.current_achievements
		FROM '.HL_XBOX_DB_PREFIX.'usergames AS ug
		JOIN '.HL_XBOX_DB_PREFIX.'users AS u on ug.user_id = u.id
		WHERE ug.game_id = %d
	', $object->id));
	if($_POST['submit']) {
		$object->name = stripslashes($_POST['object']['name']);
		$object->total_achievements = intval($_POST['object']['total_achievements']);
		$object->total_gamerscore = intval($_POST['object']['total_gamerscore']);
		if($object->name!='') {
			$wpdb->query($wpdb->prepare(
				'UPDATE '.HL_XBOX_DB_PREFIX.'games SET name=%s, total_achievements=%d, total_gamerscore=%d WHERE id=%d LIMIT 1',
				$object->name, $object->total_achievements, $object->total_gamerscore, $object->id
			));
			$msg_updated = 'Game saved successfully.';
		} else {
			$msg_error = 'Game name is a required field.';
		}
	}
?>
<div class="wrap">
	<?php if($msg_updated): ?>
		<div class="updated"><p><?php echo $msg_updated; ?></p></div>
	<?php endif; ?>
	<?php if($msg_error): ?>
		<div class="error"><p><?php echo $msg_error; ?></p></div>
	<?php endif; ?>
	<h2>Xbox Game Details</h2>
	<form method="post" action="admin.php?page=hl_xbox_games&amp;action=edit&amp;id=<?php echo $object->id; ?>">
		<table class="form-table">
			<tr>
				<th scope="row">Name</th>
				<td><input type="text" name="object[name]" size="40" value="<?php echo hl_e($object->name); ?>" /></td>
			</tr>
			<tr>
				<th scope="row">Available achievements</th>
				<td><input type="text" name="object[total_achievements]" size="40" value="<?php echo hl_e($object->total_achievements); ?>" /></td>
			</tr>
			<tr>
				<th scope="row">Available gamerscore</th>
				<td><input type="text" name="object[total_gamerscore]" size="40" value="<?php echo hl_e($object->total_gamerscore); ?>" /></td>
			</tr>
		</table>
		<div style="width: 60%">
			<h2>Played by&hellip;</h2>
			<table class="widefat post fixed" cellspacing="0" style="clear: none;">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-title" width="75">&nbsp;</th>
						<th scope="col" class="manage-column column-title">Gamertag</th>
						<th scope="col" class="manage-column column-title">Achievements</th>
						<th scope="col" class="manage-column column-title">Gamerscore</th>
						<th scope="col" class="manage-column column-title">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-title">&nbsp;</th>
						<th scope="col" class="manage-column column-title">Gamertag</th>
						<th scope="col" class="manage-column column-title">Achievements</th>
						<th scope="col" class="manage-column column-title">Gamerscore</th>
						<th scope="col" class="manage-column column-title">&nbsp;</th>
					</tr>
				</tfoot>
				<tbody>
					<?php if($users and count($users)>0): ?>
						<?php foreach($users as $user): ?>
							<tr>
								<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user->id; ?>"><img src="<?php echo hl_xbox_get_user_image_url($user->id); ?>" /></a></td>
								<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user->id; ?>"><?php echo hl_e($user->gamertag); ?></a></td>
								<td><?php echo hl_e($user->current_achievements); ?>/<?php echo hl_e($object->total_achievements); ?> (<?php echo hl_percent($user->current_achievements,$object->total_achievements); ?>%)</td>
								<td><?php echo hl_e($user->current_gamerscore); ?>/<?php echo hl_e($object->total_gamerscore); ?> (<?php echo hl_percent($user->current_gamerscore,$object->total_gamerscore); ?>%)</td>
								<td><a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user->id; ?>&amp;subaction=edit_usergame&amp;game_id=<?php echo $object->id; ?>">edit</a> | <a href="admin.php?page=hl_xbox_users&amp;action=edit&amp;id=<?php echo $user->id; ?>&amp;subaction=delete_usergame&amp;game_id=<?php echo $object->id; ?>">delete</a></td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr><td colspan="5">No users have played this game.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="submit">
			<a href="admin.php?page=hl_xbox_games" class="button-secondary">Cancel</a>
			<input type="submit" name="submit" value="Save" class="button-primary" />
		</div>
	</form>
</div>
<?php
} // end func: hl_xbox_edit_game


/*
	Delete a game and any usergames
*/
function hl_xbox_delete_game($id) {
	global $wpdb;
	$id = intval($id);
	if(isset($_POST['submit'])) {
		$wpdb->query($wpdb->prepare('DELETE FROM '.HL_XBOX_DB_PREFIX.'games WHERE id=%d LIMIT 1',$id));
		$wpdb->query($wpdb->prepare('DELETE FROM '.HL_XBOX_DB_PREFIX.'usergames WHERE game_id=%d',$id));
		?>
		<div class="wrap">
			<h2>Delete Game</h2>
			<p>The selected game has been deleted.</p>
			<a href="admin.php?page=hl_xbox_games" class="button-primary">Back</a>
		</div>
		<?php
		return;
	}
	?>
	<div class="wrap">
		<h2>Delete Game</h2>
		<p>Are you sure you wish to permanently delete this game along with any gameplay information for gamers?</p>
		<form method="post" action="admin.php?page=hl_xbox_games&amp;action=delete&amp;id=<?php echo $id; ?>">
			<p>
				<a href="admin.php?page=hl_xbox_games" class="button-secondary">Cancel</a>
				<input type="submit" name="submit" value="Delete" class="button-primary" />
			</p>
		</form>
	</div>
	<?php
} // end func: hl_xbox_delete_game












/*
  Display an error page with optional message
/*/
function hl_xbox_error($msg=false) {
?>
  <div class="wrap">
    <h2>An Error Occurred</h2>
    <?php if($msg!=''): ?>
      <div class="error"><p><?php echo $msg; ?></p></div>
    <?php endif; ?>
    <p>Unfortunately an error occurred while trying to handle your request that the system could not resolve. Please try again.</p>
  </div>
<?php
} // end func: hl_xbox_error

