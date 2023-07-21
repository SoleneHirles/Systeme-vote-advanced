<?php
	session_start();
	$_SESSION['id-vote']=$_POST['id'];
	exit(200);

?>