<?php
	session_start();
	if(!isset($_SESSION['valid_user']) ){
		header("Location: index.php");
	}

	//Bouton déconnexion cliqué
	if(isset($_POST['deconnexion'])){
		session_destroy();
		header("location: index.php");
		exit();
	}

	if(!isset($_SESSION['id-vote']) or $_SESSION['id-vote']==null){
		$_SESSION['msg']="Vous n'avez pas sélectionné de sondages auquel voter";
		header("location: index.php");
	}

	$bd=connectionBD();
	$idVote=mysqli_real_escape_string($bd,$_SESSION['id-vote']);
	$pseudo=mysqli_real_escape_string($bd,$_SESSION['identifiant']);
	$req="SELECT * FROM `sondages` WHERE `id`='$idVote'";
	$rep=mysqli_query($bd,$req);
	$nbLigne=mysqli_num_rows($rep);
	if($nbLigne!=1){
		$_SESSION['msg']="Le sondage $idVote auquel vous voulez voter n'existe pas.";
		header("location: index.php");
	}
	$sondage=mysqli_fetch_object($rep);
	if(strtotime($sondage->cloture)<=strtotime(date('Y-m-d'))){
		$_SESSION['msg']="Le sondage $idVote auquel vous voulez voter est fermé.";
		header("location: index.php");
	}
	if(!$sondage->publique){
		$req="SELECT * FROM `electeurs` WHERE `id`='$idVote' AND `pseudo`='$pseudo'";
		$rep=mysqli_query($bd,$req);
		$nbLigne=mysqli_num_rows($rep);
		if($nbLigne!=1){
			$_SESSION['msg']="Vous n'avez pas les droits pour participer au sondage $idVote .";
			header("location: index.php");
		}
	}

	$req="SELECT * FROM `resultats` WHERE `id`='$idVote' AND `votant`='$pseudo'";
	$rep=mysqli_query($bd,$req);
	$nbLigne=mysqli_num_rows($rep);
	if($nbLigne!=0){
		$_SESSION['msg']="Vous avez déjà participé au sondage $idVote .";
		header("location: index.php");
	}

	$req="SELECT * FROM `question` WHERE `id-sondage`='$idVote'";
	$rep=mysqli_query($bd,$req);

	//Vote envoyé
	if(isset($_POST['voter'])){
		$req="INSERT INTO `resultats`(`id`, `votant`) VALUES('$idVote','$pseudo')";
		mysqli_query($bd,$req);
		$tabQuest=explode('¤',$_POST['tabQuestion']);
		foreach ($tabQuest as $value) {
			if($value !=""){
				$idRep=$_POST["$value"];
				$req="INSERT INTO `reponses` VALUES('$idVote','$value','$idRep','$pseudo')";
				mysqli_query($bd,$req);
			}
		}

		$_SESSION['msg']="Votre vote pour le sondage $idVote a bien été pris en compte";
		header("location: index.php");
	}

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

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Voter à un sondage</title>
				<link rel="stylesheet" type="text/css" href="packages/style.css">
		<!-- Style -->
		<link rel="stylesheet" href="packages/w3.css">
	</head>
	<body>
		<div class="w3-display-container w3-indigo  w3-topbar w3-bottombar w3-border-black w3-center entete">
			<div class="w3-display-middle ">
				<h1 class="gras">
                	Système de vote en ligne
            	</h1>
			</div>
        </div>

		<ul class="w3-light-blue liste w3-border-black w3-border-bottom gras" id="deco">
			<li class="inline">
				<button class="w3-button" onclick="window.location.href='index.php';">Accueil</button>
			</li>
			<li class="inline">
				<button class="w3-button" onclick="window.location.href='sondage.php';">Création de sondage</button>
			</li>
			<li class="inline">
				<button class="w3-button" onclick="window.location.href='profil.php';">Mon Profil : <?php echo $_SESSION['identifiant']; ?></button>
			</li>
			<li class="inline w3-right">
				<form  class="inline" action="index.php" method="POST">
					<input class="w3-button" type="submit" name="deconnexion" value="Déconnexion">
				</form>
			</li>

			<!-- <li class="inline w3-right">
				<button class="inline gras w3-button"> Bonjour <?php echo $_SESSION['identifiant']; ?></button>
			</li> -->
		</ul>

		<div class="w3-card-4 w3-margin padding1 w3-center">

			<h4 class="w3-teal padding1 gras w3-round-large">Sondage n°<?php echo "$idVote"; ?></h4>
			<br>

			<form action="vote.php" method="POST">
				<table class="w3-card-4 w3-centered padding1 tabSpace margeAuto">
					<tr>
						<td>

							<label for="titre">Titre du sondage :</label>
							<input type="text" name="titre" id="titre" value=<?php echo $sondage->titre; ?> disabled>
						</td>
					</tr>
				
				<?php
					$tabQuest="";
					while($obj=mysqli_fetch_object($rep)){
						print("
							<tr>
								<td>
									<textarea cols=50 rows=5 name=question".$obj->idQuestion." disabled>".$obj->quest."
									</textarea>

								</td>
							</tr>
							<tr>
								<td>
									<label class='w3-teal w3-round-large padding1'>Les réponses :</label>
									<br>
									<br>
						");
						$tabQuest=$tabQuest."¤".$obj->idQuestion;
						$req="SELECT * FROM `reponse-possible` WHERE `id-sondage`='$idVote' AND `id-question`='".$obj->idQuestion."'";
						$reponse=mysqli_query($bd,$req);
						while($objet=mysqli_fetch_object($reponse)){
							if($objet->reponse!=''){
								print("
									<input type=radio id=".$objet->idReponse." name=".$obj->idQuestion." value=".$objet->idReponse." required>
									<label for=".$objet->idReponse.">".$objet->reponse."</label>
								");
							}
						}
						print("
								</td>
							</tr>
						");

					}
					print("<input type=hidden name=tabQuestion value=".$tabQuest." />");

				?>
				</table>
				<br>
				<input  class="w3-button w3-teal w3-round-large w3-border" type="submit" name="voter" value="Voter !">

			</form>
		</div>

	</body>
</html>