<?php if(!HL_XBOX_LOADED) die('Direct script access denied.');

class hl_xbox_widget extends WP_Widget {
	
	private $show_types = array('recent'=>'Most recent', 'gamerscore'=>'Most points', 'achievements'=>'Most achievements','random'=>'Random selection');
	
	function widget($args, $instance) {		
		global $wpdb;
		extract($args);
		
		$user_id = intval($instance['user_id']);
		$user = $wpdb->get_row($wpdb->prepare('SELECT id AS user_id, gamertag, gamerscore, last_seen, zone, reputation, profile_url FROM '.HL_XBOX_DB_PREFIX.'users WHERE id=%d LIMIT 1',$user_id));
		if(!$user) {
			echo 'Xbox user not found.';
			return;
		}
		
		$num_games = intval($instance['num_games']);
		if($num_games<=0 or $num_games>10) $num_games = 3;
		$show_types = (array_key_exists($instance['show_type'], $this->show_types))?$instance['show_type']:'recent';
		
		switch($show_types) {
			case 'random':
				$order_by = 'RAND() ASC';
				break;
			case 'gamerscore':
				$order_by = 'ug.current_gamerscore DESC';
				break;
			case 'achievements':
				$order_by = 'ug.current_achievements DESC';
				break;
			case 'recent':
			default:
				$order_by = 'ug.last_played DESC';
		}
		
		$games = $wpdb->get_results($wpdb->prepare('
			SELECT g.id, g.name, g.total_achievements, g.total_gamerscore, g.game_id, ug.first_played, ug.last_played, ug.current_achievements, ug.current_gamerscore
			FROM '.HL_XBOX_DB_PREFIX.'usergames AS ug
			JOIN '.HL_XBOX_DB_PREFIX.'games AS g ON ug.game_id=g.id
			WHERE ug.user_id=%d 
			ORDER BY '.$order_by.'
			LIMIT %d
		', $user->user_id, $num_games));
		
		$current_template_directory = get_template_directory();
		if(file_exists($current_template_directory.'/hl_xbox_widget.php')) {
			include $current_template_directory.'/hl_xbox_widget.php';
		} else {
			include HL_XBOX_DIR.'/hl_xbox_widget.php';
		}
		
	} // end func: widget
	
	
	function __construct() {
		parent::__construct(false, $name = 'Xbox Games', array('description'=>'Shows a list of games as a widget with customisable options.'));	
	} // end func: __construct
	
	
	function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['num_games'] = intval($new_instance['num_games']);
		$instance['user_id'] = intval($new_instance['user_id']);
		$instance['show_type'] = stripslashes($new_instance['show_type']);
		return $instance;
	} // end func: update
	
	
	function form($instance) {
		global $wpdb;
		$users = $wpdb->get_results('SELECT id, gamertag FROM '.HL_XBOX_DB_PREFIX.'users ORDER BY gamertag ASC');
		$poss_num_games = range(1,10);
		
		$user_id = intval(esc_attr($instance['user_id']));
		$num_games = intval(esc_attr($instance['num_games']));
		$show_type = stripslashes(esc_attr($instance['show_type']));
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id('user_id'); ?>"><?php _e('Gamertag'); ?></label><br />
			<select id="<?php echo $this->get_field_id('user_id'); ?>" name="<?php echo $this->get_field_name('user_id'); ?>">
				<?php foreach($users as $user): ?>
					<option value="<?php echo $user->id; ?>" <?php if($user->id==$user_id) echo 'selected="selected"'; ?>><?php echo hl_e($user->gamertag); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('num_games'); ?>"><?php _e('Games to show'); ?></label><br />
			<select id="<?php echo $this->get_field_id('num_games'); ?>" name="<?php echo $this->get_field_name('num_games'); ?>">
				<?php foreach($poss_num_games as $num): ?>
					<option value="<?php echo $num; ?>" <?php if($num_games==$num) echo 'selected="selected"'; ?>><?php echo $num; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('show_type'); ?>"><?php _e('Show'); ?></label><br />
			<select id="<?php echo $this->get_field_id('show_type'); ?>" name="<?php echo $this->get_field_name('show_type'); ?>">
				<?php foreach($this->show_types as $key=>$value): ?>
					<option value="<?php echo $key; ?>" <?php if($key==$show_type) echo 'selected="selected"'; ?>><?php echo $value; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		
		<?php 
	} // end func: form
	
} // end class: hl_xbox_widget