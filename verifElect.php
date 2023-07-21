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
		/* Fixe le jeu de caractères de la BDD en UTF-8 */
		mysqli_set_charset($bdd, "utf-8");
		return $bdd;
	}

	function chaineSansAt($str){
		if(is_int(strpos($str,"@"))){
			return false;
		}
		return true;
	}

	$bd=connectionBD();
	if(chaineSansAt($_POST['elect'])){
		$elect=mysqli_real_escape_string($bd,$_POST['elect']);
		$requete="SELECT `pseudo` FROM `connexion` WHERE `pseudo`='$elect'";
	}else{
		$elect=mysqli_real_escape_string($bd,$_POST['elect']);
		$requete="SELECT `pseudo` FROM `connexion` WHERE `email`='$elect'";
	}
	$reponse=mysqli_query($bd,$requete);
	$nbLigne=mysqli_num_rows($reponse);
	$pseudo=mysqli_fetch_object($reponse);
	if($nbLigne!=0){
		echo $pseudo->pseudo;
	}else{
		echo false;
	}
	mysqli_free_result($reponse);
	
?>