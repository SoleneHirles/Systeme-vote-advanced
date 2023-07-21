<?php 
	session_start();
	if(!isset($_SESSION['valid_user'])){
		header("Location: index.php");
	}

	//Bouton déconnexion cliqué
	if(isset($_POST['deconnexion'])){
		session_destroy();
		header("location: index.php");
		exit();
	}

	// Récupération de tous les sondages
	$bd=connectionBD();
	$auj=strtotime(date('Y-m-d'));
	$pseudo=mysqli_real_escape_string($bd,$_SESSION['identifiant']);
	$req="SELECT * FROM `sondages` WHERE `createur`='$pseudo' ORDER BY `creation`desc";
	$reponse=mysqli_query($bd,$req);
	$tabSondage=array();

	//Récupération du tableau associatif
	while ($obj = mysqli_fetch_object($reponse)) {
		if($obj->publique==1){
			$obj->publique="publique";
		}else{
			$obj->publique="privé";
		}
		if(strtotime($obj->cloture)<=$auj){
			$obj->cloture="Fermé";
		}
		array_push($tabSondage, $obj);
	}
	$objetSondage= new stdClass();
	$objetSondage->tab=$tabSondage;
	mysqli_free_result($reponse);

	$req="SELECT * FROM `sondages`
			INNER JOIN `resultats` ON resultats.id=sondages.id WHERE `votant`='$pseudo'";
	$repVote=mysqli_query($bd,$req);
	$tabVote=array();
	//Récupération du tableau associatif
	while ($obj = mysqli_fetch_object($repVote)) {
		if($obj->publique==1){
			$obj->publique="publique";
		}else{
			$obj->publique="privé";
		}
		if(strtotime($obj->cloture)<=$auj){
			$obj->cloture="Fermé";
		}
		array_push($tabVote, $obj);
	}
	$objetVote= new stdClass();
	$objetVote->tab=$tabVote;
	mysqli_free_result($repVote);

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
		<title>Mon profil</title>

		<!-- Style -->
		<link rel="stylesheet" href="packages/w3.css">

		<!-- Tabulator -->
		<link href="packages/tabulator-master/dist/css/tabulator.min.css" rel="stylesheet">
		<script type="text/javascript" src="packages/tabulator-master/dist/js/tabulator.min.js"></script>
		<link rel="stylesheet" type="text/css" href="packages/style.css">
		<!-- Script principal JS -->
		<script type="text/javascript">
			//Formatteur customisé de colonnes
			var BtnSuppr= function(cell,formatterParams,onRendered){
            	return '<button class="w3-round-medium w3-btn w3-teal">Supprimer </button'
            }

            var BtnVisib= function(cell,formatterParams,onRendered){
            	return '<button class="w3-round-medium w3-btn w3-teal">Modifier la visibilité</button'
            }

            var BtnRes= function(cell,formatterParams,onRendered){
            	return '<button class="w3-round-medium w3-btn w3-teal">Résultats</button'
            }

            var BtnParticipation= function(cell,formatterParams,onRendered){
            	return '<button class="w3-round-medium w3-btn w3-teal">Afficher la participation</button'
            }

            var BtnFin= function(cell,formatterParams,onRendered){
            	return '<button class="w3-round-medium w3-btn w3-teal">Modifier la date de clôture</button'
            }


			function init(){
				var tdata=<?php echo json_encode($tabSondage); ?>;
				var tVote=<?php echo json_encode($tabVote); ?>;

				//Définition des colonnes
				var colVote=[
					{title:"Identifiant", field:"id"},
					{title:"Visibilité", field:"publique"},
					{title:"Titre", field:"titre"},
					{title:"Thème", field:"theme"},
					{title:"Clôture", field:"cloture"},
					{title:"Date du vote", field:"date"}
				];

				var cols=[
					{title:"Identifiant", field:"id"},
					{title:"Visibilité", field:"publique"},
					{title:"Titre", field:"titre"},
					{title:"Thème", field:"theme"},
					{title:"Clôture", field:"cloture"},
					// {title:"Afficher la participation", formatter:BtnParticipation},
					{title:"Résultats", formatter:BtnRes, cellClick:res},
					// {title:"Modifier la visibilité", formatter:BtnVisib},
					// {title:"Modifier la date de clôture", formatter:BtnFin},
					{title:"Supprimer", formatter:BtnSuppr, cellClick:supprimer},
				];

				//Définition de la table
				var table=new Tabulator("#tabulator",{
					data:tdata,
					columns:cols,
					pagination: "local",
	                paginationSize: 10,
	                responsiveLayout:true,
                	layout:"fitDataTable"
				});

				var tabVote=new Tabulator('#tab-vote',{
					data:tVote,
					columns:colVote,
					pagination: "local",
	                paginationSize: 10,
	                responsiveLayout:true,
                	layout:"fitDataTable"
				})
			}

			function res(e,cell){
				id=cell.getData().id;
				url="idVote.php";
				const Http = new XMLHttpRequest();
                   
                Http.open("POST", url);
                Http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                Http.addEventListener('load', function () {

					if (Http.status >= 200 && Http.status < 400) {

				        window.location.href='resultats.php';

				    } else {
				        console.error(Http.status + " " + Http.statusText);
				        alert("Erreur programme");
						return;

				    }

				});
                Http.send("id="+id);
			}

			function supprimer(e, cell){
				// requete HTTP
				url="supprimer.php";
				const Http = new XMLHttpRequest();
                   
                Http.open("POST", url);
                Http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                Http.addEventListener('load', function () {

					if (Http.status >= 200 && Http.status < 400) {

				        location.reload();

				    } else {
				        console.error(Http.status + " " + Http.statusText);
				        alert("Erreur programme");
						return;

				    }
				});
				Http.send("id="+cell.getData().id);

				console.log(cell.getData());
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
			<table class="w3-centered tabSpace">
				<thead class="gras w3-teal ">
					<td class="padding1">
						Mes sondages
					</td>
					<td class="padding1">
						Mes votes
					</td>
				</thead>
				<tr>
					<td>
						<div id="tabulator"></div>
					</td>
					<td>
						<div id="tab-vote"></div>
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>