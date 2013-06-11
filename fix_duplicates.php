<?php
define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = "localhost"; // Necessary if running from command line
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);	

// Kör denna:
// update tblperson set Epost = replace(Epost, "'", ""); // Eftersom vissa epostfält är fnuttade i gamla db.
// Lägg till ett fält i tblperson som heter "nytt_id"

$result = db_query("SELECT * FROM tblperson WHERE Namn !='' GROUP BY Epost"); // Hämta alla rader med unika epostadresser
foreach ($result as $row) {
	$epost = $row->Epost;
	$new_id = $row->AnonymID; // AnonymID i detta resultatet ska användas som id för epostadressen	
	if($epost != null) {
		db_query("UPDATE tblperson SET nytt_id = $new_id WHERE Epost = '$epost'"); // Varje unik epostadress får ett id som är samma som en av dess AnonymID:s
		$newresult = db_query("SELECT KontaktID, nytt_id FROM tblkontakt INNER JOIN tblperson ON tblkontakt.AnonymID = tblperson.AnonymID WHERE nytt_id = $new_id");
		// Koppla ihop KontaktID från tblkontakt med tblperson genom AnonymID där de är samma i båda tabellerna och där nytt_id är samma som epostadressens nya id. 
		foreach($newresult as $record) {
			$kontakt_id = $record->KontaktID;		
			$new_anonym = $record->nytt_id; // Epostadressens id som ska användas som AnonymID i tblkontakt
			echo $kontakt_id . "\n";
			db_query("UPDATE tblkontakt SET AnonymID = $new_anonym WHERE KontaktID = $kontakt_id"); 
			// Ändrar AnonymID i tblkontakt så att det blir samma som epostadressens id. 
			// Nu kan ett AnonymID alltså förekomma flera gånger i tblkontakt, och är alltid samma som ett AnonymID i tblperson, och ett epost-id som är unikt för varje epostadress.			
			// Dubletter finns kvar i tblperson, men kommer att ignoreras av tblarende, eftersom det är KontaktID som sedan kopplas ihop med tblarende.      
		}	
	}
}

?>
 