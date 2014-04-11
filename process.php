<?
set_time_limit(0);

require_once "lib/database.php";
require_once "lib/basis.php";

$result = mysql_query("SELECT * FROM `users`");
while($user = mysql_fetch_array($result)) {

	if(empty($user['data'])) {
		$name = "New User";
	} else {
		$user_info = unserialize($user['data']);
		$name      = $user_info['profile']['full_name'];
	}

	echo "<hr>";
	echo "Processing <b>{$name}</b> ";

	$basis = new Basis($user['email'], $user['password']);
	
	if(!$basis->login()) {
		echo("Couldn't login! Error returned: " . $basis->error_string);
		continue;
	}
	
	
	if(!$basis->collectData()) {
		echo("Couldn't collect data! Error returned: " . $basis->error_string);
		continue;
	}
	
	$user_info = $basis->userInfo();
	if(isset($_GET['debug'])) var_dump($user_info);
	
	if(isset($user_info['last_synced'])) {
		$user_info =  mysql_real_escape_string(serialize($user_info));
		mysql_query("UPDATE `users` SET `data`='{$user_info}' WHERE `id`='{$user['id']}'");
	} else {
		echo "Couldn't find required fields in data! Stopping to prevent data loss/corruption.";
		continue;
	}
	
	$user_feed = $basis->userFeed();
	if(isset($_GET['debug'])) var_dump($user_feed);
	
	foreach($user_feed as $item) {
		$type = $item['type'];
		
		if($type == 'day' || $type == 'week') {
			$day  = $item['day'];
			$data = serialize($item);
			
			mysql_query("DELETE FROM `stats` WHERE `user`='{$user['id']}' AND `type`='{$type}' AND `day`='{$day}'");
			mysql_query("INSERT INTO `stats` VALUES('{$user['id']}', '{$type}', '{$day}', '{$data}', '".time()."');");
		}
	}

	$user_habits = $basis->userHabits();
	if(isset($_GET['debug'])) var_dump($user_habits);
	
	foreach($user_habits as $item) {
		$habit = $item['habit'];
		
		if(isset($_GET['debug_habits'])) var_dump($habit);
		
		$type = 'habit_' . $habit['template_name'];
		$day  = $habit['week'];
		$data = mysql_real_escape_string(serialize($habit));
		
		mysql_query("DELETE FROM `stats` WHERE `user`='{$user['id']}' AND `type`='{$type}' AND `day`='{$day}'");
		mysql_query("INSERT INTO `stats` VALUES('{$user['id']}', '{$type}', '{$day}', '{$data}', '".time()."');");
	}
	
}
?>