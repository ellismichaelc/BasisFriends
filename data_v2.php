<?
require_once "lib/database.php";

$output = array();

$result = mysql_query("SELECT * FROM `users` WHERE `data` <> ''");
while($user = mysql_fetch_array($result)) {	
	$today     = date("Y-m-d");
	$yesterday = date("Y-m-d", strtotime("yesterday"));
	
	$query_days = mysql_query("SELECT * FROM `stats` WHERE `user`='{$user['id']}' AND `type`='day'"); // AND `day`='{$today}'
	$user_days  = array();
	$user_feed  = array();
	
	while($row = mysql_fetch_array($query_days)) {
		$user_days[ $row['day'] ] = $row;
	}
	
	$user_feed = isset($user_days[ $today ]) ? $user_days[ $today ] : false;
	$user_data = unserialize($user_feed['data']);
	$user_info = unserialize($user['data']);
	$old_feed  = isset($user_days[ $yesterday ]) ? $user_days[ $yesterday ] : false;
	$old_data  = $old_feed ? unserialize($old_feed['data']) : false;
	$syncd     = date("c", $user_info['last_synced']);
	$name      = $user_info['profile']['full_name'];
	$level     = "Level " . $user_info['level'];
	$monday    = date("Y-m-d", date("N") == 1 ? time() : strtotime("last monday"));
	
	if(isset($_GET['debug'])) {	
		var_dump($user_data);
		var_dump($user_info);
		var_dump(unserialize($user_days[ $yesterday ]['data']));
	}
	
	$step_goal = $pulse = $cal_goal = $cals = $steps = $sleep = $sleepq = "--";

	if($user_feed) {
		if($user_data['resting_heartrate'] > 0) $pulse = round($user_data['resting_heartrate']);
		$step_goal = $user_data['steps_goal'];
		$cal_goal  = $user_data['calories_goal'];
		$cals      = $user_data['calories'] . " burned";
		$steps     = $user_data['steps'] . " taken";
	}

	if($old_data) {

		if(!is_numeric($pulse) && $old_data['resting_heartrate'] > 0) $pulse = round($old_data['resting_heartrate']);

		if($old_data['sleep'] > 0) {
			$sleep  = $old_data['sleep'];
			$sleepq = $old_data['sleep_quality'] . "%";
		}
	
	}
	
	if(is_numeric($pulse)) $pulse = $pulse  . " BPM";
	
	$name   = explode(" ", $name);
	$name   = $name[0] . " " . $name[1][0] . ".";
	$habits = array();
	
	$user_habits = mysql_query("SELECT * FROM `stats` WHERE `user`='{$user['id']}' AND `day`='{$monday}' AND `type` LIKE 'habit_%'");
	while($user_habit = mysql_fetch_array($user_habits)) {
	
		$user_habit = unserialize($user_habit['data']);
		$template   = $user_habit['template_name'];

		if(isset($_GET['debug_habits'])) {
			var_dump($user_habit);
		}
		
		$today      = $user_habit['daily_scores'][ date('N') - 1 ];
		$data_type  = $user_habit['goal_units'];
		
		// idk how to handle this yet
		if($data_type == "time") continue;
		if($template == "more_night_sleep") continue;
		
		$score      = $today['score'];
		$goal       = $today['goal'];
		$status     = $score >= $goal;
		$title1     = $user_habit['copy']['title'];
		$title2     = $user_habit['copy']['requirement'];
		$state      = $today['state'];// == 0 ? "0" : "1";
		$icon       = $user_habit['icon']['small_on'];
		$preface    = $user_habit['copy']['status_preface'];
		$type       = $user_habit['goal_type'];
		
		if(empty($score)) $score = 0;
		
		$percent = round(($score / $goal) * 100);
		
		if($percent > 100) $percent = 100;
		
		$habits[] = array("template"    => $template,
						  "title"       => $title1,
						  "desc"        => $title2,
						  "state"       => $state,
						  "score"       => $score,
						  "goal"        => $goal,
						  "units"       => $data_type,
						  "percent"     => $percent,
						  "icon"        => $icon,
						  "preface"     => $preface,
						  "type"        => $type);
	}

    $output[] = array("id"     => $user['id'], 
    	  			  "name"   => $name, 
    	  			  "pulse"  => $pulse, 
    	  			  "cals"   => $cals, 
    	  			  "steps"  => $steps, 
    	  			  "syncd"  => $syncd,
    	  			  "habits" => $habits,
    	  			  "sleep"  => $sleep,
    	  			  "sleepq" => $sleepq,
    	  			  "level"  => $level,
    	  			  "active" => date("d") == date("d", $user_info['last_synced']) ? "1" : "0");
}

$last_update = mysql_result(mysql_query("SELECT `updated` FROM `stats` ORDER BY `updated` DESC LIMIT 1"), 0);

$more_data = array("last_update" => date("c", $last_update));

$output = array_merge($more_data, array("users" => $output));

die(json_encode($output));
?>