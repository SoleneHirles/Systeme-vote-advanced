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

	if(!isset($_SESSION['id-vote']) or $_SESSION['id-vote']==null){
		$_SESSION['msg']="Vous n'avez pas sélectionné de sondages pour lequel vous voulez voir les résultats.";
		header("location: index.php");
	}

	$bd=connectionBD();
	$idRes=mysqli_real_escape_string($bd,$_SESSION['id-vote']);
	$pseudo=mysqli_real_escape_string($bd,$_SESSION['identifiant']);
	$req="SELECT * FROM `sondages` WHERE `id`='$idRes' AND `createur`='$pseudo'";
	$rep=mysqli_query($bd,$req);
	$nbLigne=mysqli_num_rows($rep);
	if($nbLigne!=1){
		$_SESSION['msg']="Vous n'avez pas les droits pour voir les résultats du sondage $idRes. Il est également possible que ce sondage n'existe plus.";
		header("location: index.php");
	}
	$sondage=mysqli_fetch_object($rep);
	$auj=date('Y-m-d');
	if(strtotime($sondage->cloture)>strtotime($auj)){
		$req="UPDATE `sondages`SET `cloture`=CURRENT_TIMESTAMP() WHERE `id`='$idRes'";
		mysqli_query($bd,$req);
		$req="SELECT * FROM `sondages` WHERE `id`='$idRes' AND `createur`='$pseudo'";
		$rep=mysqli_query($bd,$req);
		$sondage=mysqli_fetch_object($rep);
	}
	
	$voteAttendu;
	if(!$sondage->publique){
		$req="SELECT * FROM `electeurs` WHERE `id`='$idRes'";
		$rep=mysqli_query($bd,$req);
		$voteAttendu=mysqli_num_rows($rep);
	}
	$req="SELECT * FROM `resultats` WHERE `id`='$idRes' ";
	$rep=mysqli_query($bd,$req);
	$voteRealise=mysqli_num_rows($rep);

	$req="SELECT * FROM `question` WHERE `id-sondage`='$idRes'";
	$rep=mysqli_query($bd,$req);
	$tabId=array();
	$tabQuest=array();
	$tabIdRep=array();
	$tabRep=array();
	while ($obj = mysqli_fetch_object($rep)) {
		array_push($tabId,$obj->idQuestion);
		array_push($tabQuest,$obj->quest);
		$req="SELECT `idReponse` FROM `reponses` WHERE `id-sondage`='$idRes' AND `id-question`='".$obj->idQuestion."'";
		$reponse=mysqli_query($bd,$req);
		$tabTemp=array();
		while($objet=mysqli_fetch_object($reponse)){
			array_push($tabTemp,$objet->idReponse);
		}
		$tabTemp=array_count_values($tabTemp);
		foreach ($tabTemp as $value) {
			$value=$value*100/$voteRealise;
		}
		array_push($tabIdRep,$tabTemp);
		$req="SELECT `idReponse`,`reponse` FROM `reponse-possible` WHERE `id-sondage`='$idRes' AND `id-question`='".$obj->idQuestion."'";
		$reponse=mysqli_query($bd,$req);
		$tabTemp=array();
		while($objet=mysqli_fetch_assoc($reponse)){
			array_push($tabTemp,$objet);
		}
		array_push($tabRep,$tabTemp);

	}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Résultats du sondage</title>

		<!-- Style -->
		<link rel="stylesheet" href="packages/w3.css">
		<link rel="stylesheet" type="text/css" href="packages/style.css">
		<!-- Plotly -->
		<script src="packages/plotly.js"></script>
		<script type="text/javascript">
			function creerPlot(id,donnees,label){
				var data = [{
				  values: donnees,
				  labels: label,
				  type: 'pie'
				}];

				var config={
					responsive:true,
				}

				Plotly.newPlot(id, data, label,config);
			}

			function init(){
				var idQuest=<?php echo json_encode($tabId); ?>;
				var quest=<?php echo json_encode($tabQuest); ?>;
				var result=<?php echo json_encode($tabIdRep); ?>;
				var reponsePossible=<?php echo json_encode($tabRep); ?>;

				for (let i=0; i <idQuest.length; i++){
					var propiete=Object.keys(result[i]);
					var donnees=[];
					var label=[];
					for(let j=0;j<propiete.length;j=j+1){
						donnees.push(result[i][propiete[j]]);
						label.push(chercheRep(reponsePossible[i],propiete[j]));
					}
					creerPlot(idQuest[i],donnees,label);
				}
			}

			function chercheRep(tab,idRep){
				for (var i = 0; i < tab.length; i++) {
					if(tab[i]['idReponse']==idRep){
						return tab[i]['reponse'];
					}
				}
			}

		</script>
	</head>
	<body onload="init();">
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
			<h4 class="w3-teal padding1 gras w3-round-large" >Résultats du sondage n°<?php echo $idRes; ?> : <?php echo $sondage->titre; ?> </h4>
			<br>
			<table class="w3-card-4 padding1 tabSpace w3-centered margeAuto">
				<tr>
					<td class="gras">Nombre de votes obtenus:</td>
					<td><?php echo $voteRealise; ?></td>
				</tr>
				<?php
					if(!$sondage->publique){
						print("
							<tr>
								<td class=gras>Nombre de votes attendus:</td>
								<td>$voteAttendu</td>
							</tr>
						");
					}
				?>
				<tr>
					<td class="gras">Date de création: </td>
					<td><?php echo $sondage->creation; ?></td>
				</tr>
				<tr>
					<td class="gras">Date de clôture: </td>
					<td><?php echo $sondage->cloture; ?></td>
				</tr>
			</table>

			<br>
			<br>
			<?php

				$req="SELECT * FROM `question` WHERE `id-sondage`='$idRes'";
				$rep=mysqli_query($bd,$req);

				while ($obj = mysqli_fetch_object($rep)) {
					print("
						<h4 class='w3-teal padding1 taille25 margeAuto w3-center w3-round-large'>".$obj->quest."</h4>
						<br>
						<div class='w3-card-4 taille75 margeAuto'>
							<div class='taille50 margeAuto' id=".$obj->idQuestion."></div>
						</div>
					"
					);
				}


			?>
			

		</div>
	</body>
</html>