<?php 
	session_start();

	// Récupération de tous les sondages
	$bd=connectionBD();
	$req="SELECT * FROM `sondages` ORDER BY `creation`desc";
	$reponse=mysqli_query($bd,$req);
	$tabSondage=array();
	$auj=strtotime(date("Y-m-d"));
	//Récupération du tableau associatif
	while ($obj = mysqli_fetch_object($reponse)) {
		if(strtotime($obj->cloture)>$auj){ //Si le sondage est encore ouvert
			if($obj->publique==1){
				$obj->publique="publique";
			}else{
				$obj->publique="privé";
			}
			array_push($tabSondage, $obj);
		}
	}
	$objetSondage= new stdClass();
	$objetSondage->tab=$tabSondage;
	mysqli_free_result($reponse);


	if(!isset($_SESSION['valid_user'])){
		$_SESSION['valid_user']=0;
	}

	//Bouton déconnexion cliqué
	if(isset($_POST['deconnexion'])){
		session_destroy();
		header("location: index.php");
		exit();
	}

	//L'utilisateur souhaite s'incrire ou se connecter
	if(isset($_POST['choixScript'])){
		if (strcmp('connexion',$_POST['choixScript'])==0) {
			// fonction connexion
			$id=$_POST['identifiant'];
			$mdp=$_POST['mdp'];
			$connexion=connexion($id,$mdp);
			if($connexion){
				$_SESSION['valid_user']=1;
				if(chaineSansAt($connexion)){
					$_SESSION['identifiant']=$connexion;
					$_SESSION['email']=$id;
				}else{
					$_SESSION['identifiant']=$id;
					$_SESSION['email']=$connexion;
				}
			}else{
				$_SESSION['msg']="Identifiant ou Mot de passe incorrect";
			}
		}else{
			$em=$_POST['email'];
			$pseudo=$_POST['pseudo'];
			$mdp=$_POST['mdp'];
			if(inscription($em,$pseudo,$mdp)){
				$_SESSION['valid_user']=1;
				$_SESSION['identifiant']=$pseudo;
				$_SESSION['email']=$em;
				$_SESSION['msg']="Inscription validée !";
			}
		}
	}

	function emailValide($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	function pseudoValide($pseudo){
		return taillePseudoValide($pseudo) and chaineSansAt($pseudo);
	}

	function taillePseudoValide($pseudo){
		if(strlen($pseudo)<4 or strlen($pseudo)>10){
			return false;
		}
		return true;
	}

	function chaineSansAt($str){
		if(is_int(strpos($str,"@"))){
			return false;
		}
		return true;
	}

	function mdpValide($mdp){
		if(strlen($mdp)<8 or strlen($mdp)>20){
			return false;
		}
		return true;
	}

	function PseudoPasTrouve($pseudo,$bd){
		$requete="SELECT `pseudo` FROM `connexion` WHERE `pseudo`='$pseudo'";
		$reponse=mysqli_query($bd,$requete);
		$nbLigne=mysqli_num_rows($reponse);
		if($nbLigne==0){
			mysqli_free_result($reponse);
			return true;
		}
		mysqli_free_result($reponse);
		return false;
	}

	function emailPasTrouve($email,$bd){
		$requete="SELECT `email` FROM `connexion` WHERE `email`='$email'";
		$reponse=mysqli_query($bd,$requete);
		$nbLigne=mysqli_num_rows($reponse);
		if($nbLigne==0){
			mysqli_free_result($reponse);
			return true;
		}
		mysqli_free_result($reponse);
		return false;
	}

	function inscription($email,$pseudo,$mdp)
	{
		if(!emailValide($email)){
			$_SESSION['msg']="Echec de l'inscrition: email invalide";
			return false;
		}

		if(!pseudoValide($pseudo)){
			$_SESSION['msg']="Echec de l'inscription: le pseudo doit contenir entre 4 et 10 caractères et ne doit pas contenir le caractère '@'";
			return false;
		}

		if(!mdpValide($mdp)){
			$_SESSION['msg']="Echec inscription : le mot de passe doit contenir entre 8 et 20 caractères";
			return false;
		}

		$bdd=connectionBD();
		$email=mysqli_real_escape_string($bdd,$email);
		$pseudo=mysqli_real_escape_string($bdd,$pseudo);
		$mdp=mysqli_real_escape_string($bdd,$mdp);

		if (!PseudoPasTrouve($pseudo,$bdd)) {
			$_SESSION['msg']="Echec de l'inscription : pseudo déjà existant";
			return false;
		}

		if (!emailPasTrouve($email,$bdd)) {
			$_SESSION['msg']="Echec de l'inscription : email déjà existant";
			return false;
		}


		$requete="INSERT INTO `connexion` VALUES ('$email','$pseudo','$mdp')";
		mysqli_query($bdd,$requete);
		return true;
	}

	function recherche($id,$bd){
		if(chaineSansAt($id)){
			//cas où on se connecte avec un pseudo
			return !PseudoPasTrouve($id,$bd);//Pas trouvé
		}else{
			//cas ou on se connecte avec un email
			return !emailPasTrouve($id,$bd);
			
		}
	}

	function checkMdp($id,$mdp,$bd)
	{
		$requete;
		if(chaineSansAt($id)){//Avec @ email sinon pseudo
			$requete="SELECT `email` FROM `connexion` WHERE `pseudo`='$id' AND `mot de passe`='$mdp'";
		}else{
			$requete="SELECT `pseudo` FROM `connexion` WHERE `email`='$id' AND `mot de passe`='$mdp'";
		}
		
		$reponse=mysqli_query($bd,$requete);
		$nbLigne=mysqli_num_rows($reponse);

		if($nbLigne==0){
			mysqli_free_result($reponse);
			return false;
		}
		$ligne=mysqli_fetch_row($reponse);
		mysqli_free_result($reponse);
		return $ligne[0];
	}

	function connexion($id,$mdp){
		//Se connecte à la BD
		$bdd=connectionBD();
		$id=mysqli_real_escape_string($bdd,$id);
		$mdp=mysqli_real_escape_string($bdd,$mdp);
		$trouve=recherche($id,$bdd);// fait la fonction recherche
		if($trouve){//vérifie le mot de passe si l'identifiant est dans la BD
			return checkMdp($id,$mdp,$bdd);
		}else{
			return false;
		}	
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
		<title>Accueil</title>

		<link rel="stylesheet" href="packages/w3.css">
		<link href="packages/tabulator-master/dist/css/tabulator.min.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="packages/style.css">
		<script type="text/javascript" src="packages/tabulator-master/dist/js/tabulator.min.js"></script>
		<script type="text/javascript">
			var tdata=<?php echo json_encode($tabSondage); ?>;
			var valid=<?php echo $_SESSION['valid_user']; ?>;

			function changementPopUp(idFerme, idOuvrir){
				document.getElementById(idFerme).style.display='none';
				document.getElementById(idOuvrir).style.display='block';
			}

			function pop(id){
				document.getElementById(id).style.display='block';

			}
			
			function cacher(id) {
				var x = document.getElementById(id);
				
				if (x.className.indexOf("w3-show") == -1) {
			    	x.className += " w3-show";
				} else {
			   		x.className = x.className.replace(" w3-show", "");
				}
			
			}

			function init(){
				
				if(valid){
					cacher('co');
					cacher('deco');
				};
			}

		</script>

	</head>
	<body onload="init()">

		<div class="w3-display-container w3-indigo  w3-topbar w3-bottombar w3-border-black w3-center entete">
			<div class="w3-display-middle ">
				<h1 class="gras">
                	Système de vote en ligne
            	</h1>
			</div>
        </div>
		
		<ul class="w3-light-blue w3-center liste w3-border-black w3-border-bottom w3-hide w3-show gras" id="co">
			<li class="inline">
				<button class="w3-button" onclick="pop('connexion')">Connexion</button>
			</li>
			<li class="inline">
				<button class="w3-button" onclick="pop('inscription')">S'inscrire</button>
			</li>
		</ul>


		<ul class="w3-light-blue liste w3-border-black w3-border-bottom w3-hide gras" id="deco">
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

		
		
		<!-- Pop-up inscription -->
		<div id="inscription" class="w3-modal">
		    <div class="w3-modal-content w3-animate-top w3-card-4">
		    	<header class="w3-container w3-teal"> 
			    	<span onclick="document.getElementById('inscription').style.display='none'" class="w3-button w3-display-topright">&times;</span>
			        <h2>Inscription</h2>
			    </header>
			    <br/>
		        <div class="w3-container">
			        <form class="w3-container" action="index.php" method="POST">

						<label>Email</label>
						<input class="w3-input" type="email" required name='email'>
						<br>
						<label>Pseudo</label>
						<input class="w3-input" type="text" required name='pseudo'>
						<br>
						<label>Mot de passe</label>
						<input type="password" class="w3-input" required  name='mdp'>
						<br>
						<input type="submit" class="w3-input" name="inscription" value="S'inscrire">

						<input type="hidden" name="choixScript" value='inscription'>

					</form>
		    	</div>
		    	<br>
		    	<footer class="w3-container w3-teal">
			        <p>
			        	Déjà inscrit ? <button class="w3-button w3-white w3-border w3-border-red w3-round-large" onclick="changementPopUp('inscription', 'connexion')">Connectez-vous ici</button> 
			        </p>
			    </footer>
		    </div>
		</div>

		<!-- Pop-up connexion -->
		<div id="connexion" class="w3-modal">
		    <div class="w3-modal-content w3-animate-top w3-card-4">
		    	<header class="w3-container w3-teal"> 
			    	<span onclick="document.getElementById('connexion').style.display='none'" class="w3-button w3-display-topright">&times;</span>
			        <h2>Connectez-vous !</h2>
			    </header>
			    <br>
		        <div class="w3-container">
			        <form class="w3-container" action="index.php" method="POST">

						<label>Email ou pseudo</label>
						<input class="w3-input" type="text" required name='identifiant'>
						<br>
						<label>Mot de passe</label>
						<input type="password" class="w3-input" required  name='mdp'>
						<br>
						<input type="submit" class="w3-input" name="connexion" value="Se connecter">
						<br>

						<input type="hidden" name="choixScript" value='connexion'>

					</form>
		    	</div>
		    	<br>
		    	<footer class="w3-container w3-teal">
			        <p>
			        	Pas encore inscrit ?
			        	<button class="w3-button w3-white w3-border w3-border-red w3-round-large" onclick="changementPopUp('connexion','inscription')">Inscrivez-vous ici !</button>
			        </p>
			    </footer>
		    </div>
		</div>

		<br>

		<?php
			if(isset($_SESSION['msg']) and $_SESSION['msg']!=""){
				print("
					<div class=' w3-card-4 w3-margin padding1 w3-pale-blue gras'>".$_SESSION['msg']."</div>
				");
			}
			$_SESSION['msg']="";

		?>
		<br>
		<div class="w3-card-4 w3-margin padding1 w3-center">
			<!-- Filtrer de la table -->
			<div class="w3-teal padding1 w3-center w3-round-large w3-border">
				<select class="w3-input taille25 inline" id="filter-field">
			    	<option></option>
			    	<option value="id">Identifiant</option>
			    	<option value="publique">Visibilité</option>
			    	<option value="titre">Titre</option>
			    	<option value="theme">Thème</option>
				    <option value="cloture">Clôture</option>
				    <option value="createur">Créateur</option>
				</select>

				<input class="w3-input taille25 inline" id="filter-value" type="text" placeholder="value to filter">

				<button class="w3-button w3-white w3-round-large w3-border" id="filter-clear">Clear Filter</button>
			</div>
			<br>

			<div class="w3-card-4" id="tabulator"></div>
		</div>

		<!-- Script Tabulator -->
		<script type="text/javascript">

			var BtnVote=function(cell, formatterParams, onRendered){
            	return '<button class="w3-btn w3-teal w3-round-medium" >Voter</button>';
            }

            //Définition des colonnes
			var cols=[
				{title:"Identifiant", field:"id"},
				{title:"Visibilité", field:"publique"},
				{title:"Titre", field:"titre"},
				{title:"Thème", field:"theme"},
				{title:"Clôture", field:"cloture"},
				{title:'Créateur', field:"createur"},
				{ title: "Voter", formatter: BtnVote, cellClick: fVote, visible:valid}
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

			function fVote(e, cell){
				id=cell.getData().id;
				url="idVote.php";
				const Http = new XMLHttpRequest();
                   
                Http.open("POST", url);
                Http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                Http.addEventListener('load', function () {

					if (Http.status >= 200 && Http.status < 400) {

				        window.location.href='vote.php';

				    } else {
				        console.error(Http.status + " " + Http.statusText);
				        alert("Erreur programme");
						return;

				    }

				});
                Http.send("id="+id);
			}

			//Filtrer la table
			//Define variables for input elements
			var fieldEl = document.getElementById("filter-field");
			var valueEl = document.getElementById("filter-value");

			//Custom filter example
			function customFilter(data){
			    return data.car && data.rating < 3;
			}

			//Trigger setFilter function with correct parameters
			function updateFilter(){
			  var filterVal = fieldEl.options[fieldEl.selectedIndex].value;
			  if(filterVal){
			    table.setFilter(filterVal,"like", valueEl.value);
			  }
			}

			//Update filters on value change
			document.getElementById("filter-field").addEventListener("change", updateFilter);
			document.getElementById("filter-value").addEventListener("keyup", updateFilter);

			//Clear filters on "Clear Filters" button click
			document.getElementById("filter-clear").addEventListener("click", function(){
			  fieldEl.value = "";
			  // typeEl.value = "=";
			  valueEl.value = "";

			  table.clearFilter();
			});

		</script>
	</body>
</html>