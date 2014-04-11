<?
require_once "lib/database.php";

$output = array();

$result = mysql_query("SELECT * FROM `users` WHERE `data` <> ''");
while($user = mysql_fetch_array($result)) {	
	$today     = date("Y-m-d");
	$user_feed = mysql_fetch_array(mysql_query("SELECT * FROM `stats` WHERE `user`='{$user['id']}' AND `day`='{$today}' AND `type`='day'"));
	$user_data = unserialize($user_feed['data']);
	$user_info = unserialize($user['data']);
	$syncd     = strftime("%m/%d/%y %I:%M%p", $user_info['last_synced']);
	$name      = $user_info['profile']['full_name'];
	
	$pulse = 0;
	$step_goal = $cal_goal = $cals = $steps = "--";

	if($user_feed) {
		$pulse     = round($user_data['resting_heartrate']);
		$step_goal = $user_data['steps_goal'];
		$cal_goal  = $user_data['calories_goal'];
		$cals      = $user_data['calories'];
		$steps     = $user_data['steps'];
	}

	if($pulse == 0) {
		$user_feed = mysql_fetch_array(mysql_query("SELECT * FROM `stats` WHERE `user`='{$user['id']}' AND `type`='day' AND `data` LIKE '%resting_heartrate\";d%' ORDER BY `day` DESC"));
		$user_data = unserialize($user_feed['data']);
		
		$pulse = round($user_data['resting_heartrate']);
	}
	
	$name = explode(" ", $name);
	$name = $name[0] . " " . $name[1][0] . ".";
	
    $output[] = array($name, $pulse, $cals . "/" . $cal_goal, $steps . "/" . $step_goal, $syncd);
}

$output = array("aaData" => $output, "sEcho" => @intval($_GET['sEcho']), "iTotalRecords" => count($output), "iTotalDisplayRecords" => count($output));

die(json_encode($output));
?>