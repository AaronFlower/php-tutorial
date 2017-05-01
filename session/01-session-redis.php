<?php
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://localhost:6379');
session_start();
$sid = session_id();
if ($_SESSION['username']) {
	echo "Hi,{$_SESSION['username']}, Welcome back to our site. your session id is $sid. \n";
} else {
	$username = $_GET['username'] ? $_GET['username'] : 'eason';
	$age = $_GET['age'] ? $_GET['age'] : 23;
	$_SESSION['username'] = $username;
	$_SESSION['age'] = $age;
	echo "Hi, {$_SESSION['username']}, We create a new session for you. Your session id is $sid. \n You can append it your http request cookie. \n";
}
