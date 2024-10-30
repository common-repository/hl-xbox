<?php
/*
	Widget Theme for HL Xbox
	   To change this theme, copy hl_xbox_widget.php
	   to your current theme folder, do not edit this
	   file directly.
	cp /wp-content/plugins/hl_xbox/hl_xbox_widget.php /wp-content/themes/YOURTHEME/hl_xbox_widget.php
	
	Available Properties:
		$before_widget
		$after_widget
		$before_title
		$after_title
		$user: object representing an Xbox user
			$user->user_id
			$user->gamertag
			$user->gamerscore
			$user->last_seen
			$user->zone
			$user->reputation
			$user->profile_url
		$games: array of $game
		$game: object representing a game
			$game->name: Game Name
			$game->total_achievements
			$game->total_gamerscore
			$game->game_id
			$game->first_played
			$game->last_played
			$game->current_achievements
			$game->current_gamerscore
*/
?>

<style type="text/css">
/*
	This is a basic set of rules designed to work well with
	the Twenty Ten theme provided as part of WordPress 3.0.
*/
.widget_hl_xbox_widget h3 {
	float: left;
	clear: none;
	width: 125px;
	padding: 4px 0 0 7px;
}
.widget_hl_xbox_widget h3 strong {
	color: #BBB;
	font-style: italic;
}
.hl_xbox_gamerpic {
	float: left;
	line-height: 0;
}
#main .widget-area ul.hl_xbox_games {
	clear: both;
	list-style: none;
	margin: 0;
	padding: 6px 0 0;
}

.hl_xbox_games li {
	background-repeat: no-repeat;
	background-position: top left;
	min-height: 64px;
	height: auto !important;
	height: 64px;
	padding-left: 70px;
	margin-bottom: 6px;
}
.hl_xbox_games a {
	display: block;
	font-size: 12px;
}
.hl_xbox_games em {
	display: block;
	font-size: 10px;
}
.hl_xbox_games span {
	display: block;
	font-size: 10px;
}
</style>

<?php echo $before_widget; # <li> ?>

<a href="<?php echo $user->profile_url; ?>" class="hl_xbox_gamerpic">
	<img class="hl_xbox_table_profile_img" src="<?php echo hl_xbox_get_user_image_url($user->user_id); ?>" />
</a>

<?php echo $before_title; ?>
	<?php echo hl_e($user->gamertag); ?>'s Xbox Games
	<strong><?php echo number_format($user->gamerscore); ?></strong>
<?php echo $after_title; ?>


<?php if($games): ?>

	<ul class="hl_xbox_games">
		<?php foreach($games as $game): ?>
			<li style="background-image: url(<?php echo hl_xbox_get_game_image_url($game->id, 64); ?>)">
				<a href="<?php echo hl_xbox_get_xbox_live_game_url($game->game_id,$user->gamertag); ?>"><?php echo hl_e($game->name); ?></a>
				<em>Last played <?php echo hl_time_ago($game->last_played); ?> ago</em>
				<span><?php echo hl_percent($game->current_gamerscore,$game->total_gamerscore); ?>% completed</span>
			</li>
		<?php endforeach; ?>
	</ul>
	
<?php else: ?>
	No games found.
<?php endif; ?>

<?php echo $after_widget; # </li> ?>
