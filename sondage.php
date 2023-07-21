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

	
	if(isset($_POST['creer']) and null!==$_POST['creer']){
		$bdd=connectionBD();
		$titre=mysqli_real_escape_string($bdd,$_POST['titre']);
		$porte=mysqli_real_escape_string($bdd,$_POST['porte']);
		$theme=mysqli_real_escape_string($bdd,$_POST['theme']);
		$cloture=mysqli_real_escape_string($bdd,$_POST['cloture']);
		$createur=mysqli_real_escape_string($bdd,$_SESSION['identifiant']);
		$email=mysqli_real_escape_string($bdd,$_SESSION['email']);
		$requete="INSERT INTO `sondages`(`titre`, `publique`, `theme`,`cloture`, `createur`, `emailCrea`) VALUES ('$titre','$porte','$theme','$cloture','$createur','$email')";
		mysqli_query($bdd,$requete);
		$idSondage=mysqli_real_escape_string($bdd,mysqli_insert_id($bdd));
		$question=mysqli_real_escape_string($bdd,$_POST['question']);
		$requete="INSERT INTO `question`(`id-sondage`, `quest`) VALUES ('$idSondage','$question')";
		mysqli_query($bdd,$requete);
		$idQuestion=mysqli_real_escape_string($bdd,mysqli_insert_id($bdd));
		$reponse=explode('¤',$_POST['reponse']);
		foreach ($reponse as $value) {
			$value=mysqli_real_escape_string($bdd,$value);
			$requete="INSERT INTO `reponse-possible`(`id-sondage`, `id-question`,`reponse`) VALUES('$idSondage','$idQuestion','$value')";
			mysqli_query($bdd,$requete);
		}
		if($_POST['porte']==0){
			$elect=explode('¤',$_POST['electeur']);
			foreach ($elect as $value) {
				$value=mysqli_real_escape_string($bdd,$value);
				$requete="INSERT INTO `electeurs`VALUES('$idSondage','$value')";
				mysqli_query($bdd,$requete);
			}
		}
		$_SESSION['msg']="Votre sondage a bien été créé avec l'identifiant ".$idSondage;
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
		<title>Créer un sondage</title>
		<link rel="stylesheet" href="packages/w3.css">
				<link rel="stylesheet" type="text/css" href="packages/style.css">
		<script type="text/javascript">
			var auj;

			function cache_Decache(id) {
				var x = document.getElementById(id);
				if (x.className.indexOf("w3-show") == -1) {
			    	x.className += " w3-show";
				} else {
			    	x.className = x.className.replace("w3-show", "");
				}
			}

			function conversionA2Chiffres(nb){
				if(nb<10){
					nb="0"+nb;
				}
				return nb;
			}

			function ajoute(name,idtab, a_ajouter=document.getElementsByName(name)[0].value){
				// var a_ajouter=document.getElementsByName(name)[0].value;
				if(name.includes('rep')){
					var reponses=document.getElementsByName('reponse')[0];
					reponses.value=reponses.value+'¤'+a_ajouter;
					
				}
				if(name.includes('elect')){
					var electeurs=document.getElementsByName('electeur')[0];
					if(!electeurs.value.includes(a_ajouter)){
						if(electeurs.value==""){
							electeurs.value=a_ajouter;
						}else{
							electeurs.value=electeurs.value+'¤'+a_ajouter;
						}
					}else{
						alert(a_ajouter+" est déjà dans la liste des électeurs.");
						return;
					}
					
				}
				
				var table=document.getElementById(idtab);
				var inputAjout=table.lastElementChild.removeChild(table.lastElementChild.lastElementChild);
				var tr=document.createElement('tr');
				var tdValue=document.createElement('td');
				tdValue.innerHTML=a_ajouter;
				var tdSuppr=document.createElement('td');
				var inputSuppr=document.createElement('input');
				inputSuppr.name="Suppr"+name;
				inputSuppr.value="X";
				inputSuppr.type="button";
				inputSuppr.addEventListener('click',function(){
					Suppr(this,'reponse');
				});
				tdSuppr.appendChild(inputSuppr);
				tr.appendChild(tdValue);
				tr.appendChild(tdSuppr);
				inputAjout.firstElementChild.firstElementChild.value="";
				table.lastElementChild.appendChild(tr);
				if(!name.includes('rep') || table.lastElementChild.childElementCount<5){
					table.lastElementChild.appendChild(inputAjout);
				}
				
			}

			function Suppr(elt,name){
				var parent= elt.parentNode;
				var supParent=parent.parentNode;
				var content=supParent.firstElementChild.innerHTML;
				var table=supParent.parentNode;
				table.removeChild(supParent);
				var s=document.getElementsByName(name)[0].value;
				s=s.replace(content,"");
				s=s.replace("¤¤","¤");
				if(s[0]=='¤'){s[0]=''}
				if(s[s.length-1]=='¤'){s[s.length-1]='¤'}
				document.getElementsByName(name)[0].value=s;

			}

			function verifElecteur(name,idtab){
				var a_ajouter=document.getElementsByName(name)[0].value;
				url="verifElect.php";
				const Http = new XMLHttpRequest();
                   
                Http.open("POST", url);
                Http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                Http.addEventListener('load', function () {

					if (Http.status >= 200 && Http.status < 400) {

				        if(this.responseText!=false){
				        	ajoute(name,idtab,this.responseText);
				        	document.getElementsByName(name)[0].value="";

				        }else{
				        	document.getElementsByName(name)[0].value="";
				        	alert(a_ajouter+" n'est pas un électeur existant.");
							return;
				        };

				    } else {
				    	document.getElementsByName(name)[0].value="";
				        console.error(Http.status + " " + Http.statusText);
				        alert("Erreur programme");
						return;

				    }

				});
                Http.send("elect="+a_ajouter);
			}

			function init() {
				auj=new Date();
				max=(auj.getFullYear()+1)+"-"+conversionA2Chiffres(auj.getMonth()+1)+"-"+conversionA2Chiffres(auj.getDate());
				auj=auj.getFullYear()+"-"+conversionA2Chiffres(auj.getMonth()+1)+"-"+conversionA2Chiffres(auj.getDate());
				document.getElementById("cloture").setAttribute("min",auj);
				document.getElementById("cloture").setAttribute("max",max);
				document.getElementById("cloture").setAttribute("value",max);
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
			<h4 class="w3-teal padding1 gras w3-round-large">
				Création de sondage
			</h4>
			<br>
			<form action="sondage.php" class="w3-center" method="POST">
				<table class="w3-centered margeAuto">
					<tr>
						<td class="">
							<table class="w3-card-4 w3-margin padding1">
								<tr>
									<td>
										<label for="titre">Titre du sondage&nbsp;:</label>
									</td>
									<td>
										<input type="text" name="titre" id="titre" required>
									</td>
								</tr>
								<tr>
									<td>Portée du sondage&nbsp;:</td>
									<td> 
										<input type="radio" name="porte" value="1" id="public" checked onchange="cache_Decache('theme');cache_Decache('elec');">
										<label for="public">Public</label>
										<input type="radio" name="porte" value="0" id="prive" onchange="cache_Decache('theme');cache_Decache('elec');">
										<label for="prive">Privé</label>
									</td>
								</tr>
								<tr id="theme" class="w3-show w3-hide displayTRow">
									<td><label for="sujet">Thème du sondage&nbsp;: </label></td>
									<td><input type="text" name="theme" id="sujet"></td>
								</tr>
								<tr>
									<td><label for="date">Date de clôture du sondage&nbsp;: </label></td>
									<td><input type="date" name="cloture" id="cloture" required></td>
								</tr>
							</table>
						</td>
						<td id="elec" class="w3-hide w3-card-4 w3-margin padding1">

							<label class="w3-teal padding1 w3-round">Les électeurs &nbsp;:</label><br>
							<table id='tab-elec' class="w3-margin">
								<tr>
									<td><input type="text" name="elect"></td>
									<td><input type="button" name="ajoutElecteur" value="+" onclick="verifElecteur('elect','tab-elec');"></td>
								</tr>
							</table>
							<input type="hidden" name="electeur" value="">
						</td>
					</tr>
				</table>
				
				
				<br>
				
				<table class="w3-card-4 padding1 tabSpace margeAuto">
					<tr>
						<td>
							<label class="w3-teal padding1 w3-round">Entrez votre question</label>
							<br>
							<br>
							<textarea cols="50" rows="5" name="question" required></textarea>
						</td>
						<td>
							<label class="w3-teal padding1 w3-round">Choix des réponses :</label><br>

							<table id='tab-rep'>
								<tr>
									<td>oui</td>
									<td><input type="button" onclick="Suppr(this,'reponse')" name="SupprReponse" value="X"></td>
								</tr>
								<tr>
									<td>non</td>
									<td><input type="button" onclick="Suppr(this,'reponse')" name="SupprReponse" value="X"></td>
								</tr>
								<tr>
									<td>abstention</td>
									<td><input type="button" onclick="Suppr(this,'reponse')" name="SupprReponse" value="X"></td>
								</tr>
								<tr>
									<td><input type="text" name="rep"></td>
									<td><input type="button" name="ajoutRep" value="+" onclick="ajoute('rep','tab-rep');"></td>
								</tr>
							</table>

							<br>
							<br>
							<input type="hidden" name="reponse" value="oui¤non¤abstention">

						</td>
					</tr>
				</table>
				<br>
				<!-- <div id="elec" class="w3-hide">
					Les électeurs :
					<br>
					<table id='tab-elec'>
						<tr>
							<td><input type="text" name="elect"></td>
							<td><input type="button" name="ajoutElecteur" value="+" onclick="verifElecteur('elect','tab-elec');"></td>
						</tr>
					</table>
					<input type="hidden" name="electeur" value="">
				</div>
 -->
				<input class="w3-button w3-teal w3-border w3-round-large" type="submit" name="creer" value="Créer le sondage">
			</form>
		</div>
		<br>
		<br>

	</body>
</html>