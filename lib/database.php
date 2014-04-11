<?
date_default_timezone_set('America/New_York');

$con = @mysql_connect("localhost", "", "")
	   		or die("Couldn't establish DB connection");
	   		
$sel = @mysql_select_db("basisfriends")
			or die("Couldn't select database");
?>