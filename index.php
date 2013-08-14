<?php
session_start();
include('Facebook.php');

if(!isset($_REQUEST['code']) && !isset($_GET['finished']))
	Facebook::connectionPartOne();

elseif(!isset($_GET['finished']))
	Facebook::connectionPartTwo('http://localhost/fb/?finished=1');

else {
	$fb = new Facebook();
	var_dump($fb->user);
}

