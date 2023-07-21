<?php
	function connectionBD(){
		// Connection à la BD
		$host="localhost";
		$user="root";
		$pass="";
		$base="systeme-vote";

		$bdd = mysqli_connect($host,$user,$pass,$base);
		if (!$bdd){
			die('Echec de connexion au serveur de base de données:'.mysqli_connect_error().' '.mysqli_connect_errno());
		}
		echo "connecté à la base de données <br>";
		/* Fixe le jeu de caractères de la BDD en UTF-8 */
		mysqli_set_charset($bdd, "utf-8");
		return $bdd;
	}

	$bd=connectionBD();
	$id=mysqli_real_escape_string($bd,$_POST['id']);
	$req1="DELETE FROM `electeurs` WHERE `id`='$id'";
	mysqli_query($bd,$req1);
	$req2="DELETE FROM `reponses` WHERE `id-sondage`='$id'";
	mysqli_query($bd,$req2);
	$req3="DELETE FROM `resultats` WHERE `id`='$id'";
	mysqli_query($bd,$req3);
	$req4="DELETE FROM `reponse-possible` WHERE `id-sondage`='$id'";
	mysqli_query($bd,$req4);
	$req5="DELETE FROM `question` WHERE `id-sondage`='$id'";
	mysqli_query($bd,$req5);
	$req6="DELETE FROM `sondages` WHERE `id`='$id'";
	mysqli_query($bd,$req6);

	exit(200);


	

?>