<?php

$action = $_POST['action'];

if ($action == "cpcommunitie_test") {

	$value = $_POST['postID'];	

	echo $value*100;
	exit;

}

echo "Incorrect call to menu AJAX functions (".$action.")";

?>
