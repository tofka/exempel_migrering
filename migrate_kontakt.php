<?php
define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = "localhost"; // Necessary if running from command line
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Migrera kontakter som har epost:
$result = db_query("SELECT * FROM tblperson WHERE exporterad = 0 AND nytt_id != 0 AND Namn !='' GROUP BY nytt_id LIMIT 5000");

// Result is returned as a iterable object that returns a stdClass object on each iteration	 
foreach ($result as $record) {
	// Sätt variabler utifrån gamla db:
	$name = $record->Namn;
	$person_id = $record->AnonymID;
	$adress1 = $record->Gatuadress;
	$adress2 = $record->COadress;
	$postnummer = $record->Postnr;
	$epost = $record->Epost;
	$telefon = $record->Telefonnr;
	$ort = get_person_info('Bostadsort', $person_id);

	$node = new stdClass(); // Create a new node object
	$node->type = "kontakt"; // Or page, or whatever content type you like
	node_object_prepare($node); // Set some default values
	// If you update an existing node instead of creating a new one,
	// comment out the three lines above and uncomment the following:
	// $node = node_load($nid); // ...where $nid is the node id	
	
	// Mata in värden i fält:
	$node->title = $name;
	$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
	$node->uid = 1; // UID of the author of the node; or use $node->name
	$path = 'node_created_on' . date('YmdHis');
	$node->path = array('alias' => $path);	 
	$node->field_adress1[$node->language][]['value'] = $adress1;
	$node->field_adress2[$node->language][]['value'] = $adress2;
	$node->field_postnummer[$node->language][]['value'] = $postnummer;
	$node->field_e_post[$node->language][]['value'] = $epost;
	$node->field_telefon[$node->language][]['value'] = $telefon;
	$node->field_ort[$node->language][]['value'] = $ort;
	$node->field_person_id[$node->language][]['value'] = $person_id;

	db_query("UPDATE tblperson SET exporterad = 1 WHERE AnonymID = $person_id");

	if($node = node_submit($node)) { // Prepare node for saving
	    node_save($node);
	    echo "Kontakt with nid " . $node->nid . " (har epost) saved!\n";
	}
}

// Migrera kontakter där Epost = NULL:
$result = db_query("SELECT * FROM tblperson WHERE exporterad = 0 AND nytt_id = 0 LIMIT 5000");
// Result is returned as a iterable object that returns a stdClass object on each iteration	 
foreach ($result as $record) {
	// Sätt variabler utifrån gamla db:
	$name = $record->Namn;
	$person_id = $record->AnonymID;
	$adress1 = $record->Gatuadress;
	$adress2 = $record->COadress;
	$postnummer = $record->Postnr;
	$epost = $record->Epost;
	$telefon = $record->Telefonnr;
	$ort = get_person_info('Bostadsort', $person_id);

	$node = new stdClass(); // Create a new node object
	$node->type = "kontakt"; // Or page, or whatever content type you like
	node_object_prepare($node); // Set some default values
	// If you update an existing node instead of creating a new one,
	// comment out the three lines above and uncomment the following:
	// $node = node_load($nid); // ...where $nid is the node id	
	
	// Mata in värden i fält:
	$node->title = $name;
	$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
	$node->uid = 1; // UID of the author of the node; or use $node->name
	$path = 'node_created_on' . date('YmdHis');
	$node->path = array('alias' => $path);	 
	$node->field_adress1[$node->language][]['value'] = $adress1;
	$node->field_adress2[$node->language][]['value'] = $adress2;
	$node->field_postnummer[$node->language][]['value'] = $postnummer;
	$node->field_e_post[$node->language][]['value'] = $epost;
	$node->field_telefon[$node->language][]['value'] = $telefon;
	$node->field_ort[$node->language][]['value'] = $ort;
	$node->field_person_id[$node->language][]['value'] = $person_id;

	db_query("UPDATE tblperson SET exporterad = 1 WHERE AnonymID = $person_id");

	if($node = node_submit($node)) { // Prepare node for saving
	    node_save($node);
	    echo "Kontakt with nid " . $node->nid . " (har inte epost)saved!\n";
	}
}

function get_person_info($column, $person) {	// tex kön och ålder eller annat som anges i anonym-tabellen
	$result = db_query("SELECT $column FROM tblanonym WHERE AnonymID = $person");
	foreach ($result as $row){			
		return $row->$column;
	}	
}
?>