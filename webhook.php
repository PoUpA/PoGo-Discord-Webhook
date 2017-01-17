<?php

//EDIT ME PLEASE

// mysql db name
define('SYS_DB_NAME', '');
// mysql username
define('SYS_DB_USER', '');
// mysql password
define('SYS_DB_PSWD', '');
// mysql server name
define('SYS_DB_HOST', '');
// mysql server port
define('SYS_DB_PORT', 3306);

// gmap api key : ( ABdsayDDH7dfgdfgdf54644dssdfsdfs )
$GMaps_Key = "";
// discord webhook : ( https://discordapp.com/api/webhooks/65464546465465/GSDGG564fghkjh6GDSG-IbZ8Easibxra3HghgHFFG546546854dglooiawjWP )
$DiscordWebhook = "";
// discord username : ( robotname )
$DiscordUsername = "";




//OPTION

// TimeZone
$IntervalTime = 3600;
// Zoom Map
$MapZoom = 14;
// Pokemon notify list
$PokemonNotify = "113, 131, 143, 149";
// Pokemon blacklist
$PokemonBlackList = "13, 16, 19, 21, 46, 48, 98";
//IV and Moveset 0/1
$IvEncounter = 1;
// % notify min iv 
$IvNotifify = 100;
// txt
$TxtLeft = "Disappears at";
//Time format
$FormatTime = "G:i:s";
//max simultane notify
$MaxNotif = 3;
//Lang
$TranslateJsonFile = "EN" ;




$MessagePost = "";

// MySQL 
$mysqli 	= new mysqli(SYS_DB_HOST, SYS_DB_USER, SYS_DB_PSWD, SYS_DB_NAME, SYS_DB_PORT);
if ($mysqli->connect_error != '') {
	exit('Error MySQL Connect');
}
$mysqli->set_charset('utf8');

//Translate
$pokedex_file			= file_get_contents($TranslateJsonFile.'/pokedex.json');
$pokedex 				= json_decode($pokedex_file);
$bestmoveset_file 		= file_get_contents($TranslateJsonFile.'/bestmoveset.json');
$bestmoveset 			= json_decode($bestmoveset_file);
$moveset_file 			= file_get_contents($TranslateJsonFile.'/movesetlist.json');
$movesets 				= json_decode($moveset_file);

// get last pokemon (last_modified)
$lastpokemon_file 		= 'lastpokemon.txt';
$last_id			= file_get_contents($lastpokemon_file);

//Control
$lastidstamp = strtotime("$last_id");
$controletime =  time() - ($lastidstamp+$IntervalTime);
if($controletime >= 3600){$last_id = date("Y-m-d H:i:s", time() - ($IntervalTime + 3600));}

// IV et Moveset Option
if($IvEncounter == 1){$reqOption=" OR ((individual_attack + individual_defense + individual_stamina)>='".(45*$IvNotifify/100)."')";}

// get new pokemon
$req	= "SELECT pokemon_id, disappear_time, latitude, longitude, individual_attack, individual_defense, individual_stamina, move_1, move_2, last_modified FROM pokemon
		 WHERE ((pokemon_id IN ( ".$PokemonNotify." ))".$reqOption.") AND last_modified>='".$last_id."' AND pokemon_id NOT IN ( ".$PokemonBlackList." )
		 ORDER BY last_modified ASC LIMIT ".$MaxNotif;

$result = $mysqli->query($req);
$data = $result->fetch_object();
$data_array = array();

while($data = $result->fetch_object()){
	$pokeid = $data->pokemon_id;
	$pokemon = new stdClass();
	$pokemon->last_modified	= $data->last_modified;
	$pokemon->last_position	= new stdClass();
	$pokemon->last_position->latitude = $data->latitude;
	$pokemon->last_position->longitude = $data->longitude;	
	$pokemon->iv = new stdClass();
	$pokemon->iv->individual_attack = $data->individual_attack;
	$pokemon->iv->individual_defense = $data->individual_defense ;
	$pokemon->iv->individual_stamina = $data->individual_stamina ;
	$move_1 = $data->move_1 ;
	$move_2 = $data->move_2 ;
	$pokemon->last_seen	= strtotime($data->disappear_time)+$IntervalTime;
		
	// last_modified control
	if ($last_id != $last_modified ) {

		// Find local adress
		$jsonadress = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=".$pokemon->last_position->latitude.",".$pokemon->last_position->longitude."&key=".$GMaps_Key ),true);
		foreach($jsonadress['results'] as $r){
			foreach($r['address_components'] as $n){
				if($n['types'][0] == "street_number"){
					$streetnumber = addslashes($n['long_name']);
				}
				if($n['types'][0] == "route"){
					$route = addslashes($n['long_name']);
				}
				if($n['types'][0] == "locality" && $n['types'][1] == "political"){
					$city = addslashes($n['long_name']);
				}
			}
		}
		
		// IV and best noveset
		if($IvEncounter == 1 && $pokemon->iv->individual_attack != NULL){
			if($movesets->movesetlist->$move_1 == $bestmoveset->pokemon->$pokeid->quick_move){$move1style="**";}
			if($movesets->movesetlist->$move_2 == $bestmoveset->pokemon->$pokeid->charge_move){$move2style="**";}
			$MessagePostOption = $pokemon->iv->individual_attack.'/'.$pokemon->iv->individual_defense.'/'.$pokemon->iv->individual_stamina.' ('.$move1style.$movesets->movesetlist->$move_1.$move1style.'/'.$move2style.$movesets->movesetlist->$move_2.$move2style.') ';
		}
		
		// add to webhook message
		$MessagePost.='
'.$pokedex->pokemon->$pokeid->name.' '.$MessagePostOption.$route.' '.$streetnumber.', '.$city.' '.$TxtLeft.' '.date($FormatTime,$pokemon->last_seen).'
https://maps.google.com/?q='.$pokemon->last_position->latitude.','.$pokemon->last_position->longitude.'&ll='.$pokemon->last_position->latitude.','.$pokemon->last_position->longitude.'&z='.$MapZoom.' 
';

		// Upadate file with last pokemon (last_modified)
		$file_content = $pokemon->last_modified;
		file_put_contents($lastpokemon_file, $file_content);	
	}
}

// Post webhook
If($MessagePost != ""){
	$datawebh = array("content" => $MessagePost, "username" => $DiscordUsername);
	$curl = curl_init($DiscordWebhook);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($datawebh));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	return curl_exec($curl);
}

mysqli_close($mysqli); 
?>