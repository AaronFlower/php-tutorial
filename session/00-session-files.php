<?php
	ini_set('session.save_handler', 'files');
	ini_set('session.save_path', '/tmp');
	session_start(); //  启动新会话或者重用现有会话。
	$sid = session_id();
	// 如果请求附带的 cookie 的 session id 的信息，那么就会重现会话。否则就启动新的会话。
	if (isset($_SESSION['username'])) {
		echo "Hi, {$_SESSION['username']}, Welcome back to the session. Your session id is $sid \n";
	} else {
		$_SESSION['username'] = 'eason';
		echo "Hi, {$_SESSION['username']}, We create a new session for you. Your session id is $sid. \n You can append it your http request cookie. \n";
	}
