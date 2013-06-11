<?php

define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = "localhost"; // Necessary if running from command line
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
$result = db_query('SELECT * FROM tblarende WHERE exporterad = 0 LIMIT 1000');
// Result is returned as a iterable object that returns a stdClass object on each iteration	 

/////////////////////////////////////
// Fråge-id:t räknas bara upp första gången man kör scriptet. När man kör igen blir det 13:000 på alla
////////////////////////////////
foreach ($result as $record) {

	// Sätt variabler utifrån gamla db:
	$kontakt_id = $record->KontaktID;		
	$body = $record->ArendeFraga;
	$svar = $record->ArendeSvar;
	$noteringar = "Överfört från gamla databasen";
	$kontaktdatum = get_contact_date($kontakt_id);		
	$gammalt_kanal_id = get_contact_type($kontakt_id);
	$kanal_id = get_new_ID('migration_kanal', $gammalt_kanal_id, 2); 
	$gammalt_kategori_id = $record->FrageKategori;	
	$nytt_kategori_ID = get_new_ID('migration_fragekategori', $gammalt_kategori_id, 3);
	$kon = get_person_info('Kon', $kontakt_id);
	$alder = get_person_info('Alder', $kontakt_id);
	$gammalt_lan = "'" . get_person_info('Lan', $kontakt_id) . "'";
	$nytt_lan_id = get_new_ID('migration_lan', $gammalt_lan, 6);
	$gammalt_funktion_id = get_person_info('OfficiellStatus', $kontakt_id);
	$nytt_funktion_id = get_new_ID('migration_funktion', $gammalt_funktion_id, 10);
	$gammalt_hsk_id = get_person_info('TypAvHsk', $kontakt_id);
	$nytt_hsk_id = get_new_ID('migration_typ_av_hsk', $gammalt_hsk_id, 9); 
	$gammalt_sysselsattning_id = get_person_info('Sysselsattning', $kontakt_id);
	$nytt_sysselsattning_id = get_new_ID('migration_sysselsattning', $gammalt_sysselsattning_id, 8);
	$anhorig = get_person_info('ArAnhorig', $kontakt_id);
	$medlem = get_person_info('ArMedlem', $kontakt_id); 
	$gammalt_upptackte_id = get_person_info('UpptackteHL', $kontakt_id);
	$nytt_upptackte_id = get_new_ID('migration_upptackteHL', $gammalt_upptackte_id, 11);
	$tidning_program_id = get_person_info('KanalMedia', $kontakt_id);
	$tidning_program = get_media($tidning_program_id);
	$PUL = get_person_info('ArPULsamtycke', $kontakt_id);
	$service = $record->TypAvService; // id7 = juridiskt omb, id1 = ombudsman		
	$person_id = get_anonym_id($kontakt_id);



	$node = new stdClass(); // Create a new node object
	$node->type = "fraga"; // Or page, or whatever content type you like
	node_object_prepare($node); // Set some default values
	// If you update an existing node instead of creating a new one,
	// comment out the three lines above and uncomment the following:
	// $node = node_load($nid); // ...where $nid is the node id	
	
	// Mata in värden i fält:
	$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
	$node->uid = 1; // UID of the author of the node; or use $node->name
	$node->body[$node->language][0]['value']   = $body;
	$node->body[$node->language][0]['summary'] = text_summary($body);
	$node->body[$node->language][0]['format']  = 'filtered_html';
	$path = 'node_created_on' . date('YmdHis');
	$node->path = array('alias' => $path);	 
	$node->field_arbetsflode[$node->language][]['tid'] = 163; // Arkiverad
	$node->field_kontaktdatum[$node->language][]['value'] = $kontaktdatum;
	$node->field_kanal_f_r_kontakt[$node->language][]['tid'] = $kanal_id;
	$node->body[$node->language][]['value'] = $body;	
	$node->field__mne[$node->language][]['tid'] = $nytt_kategori_ID;
	$node->field_gammal_person_ref[$node->language][]['value'] = $person_id;

	if($kon == 'Man') {
		$kon_id = 36;	
	}
	elseif ($kon == 'Kvinna'){
		$kon_id = 37;
	}
	else {
		$kon_id = 38;
	}	
	$node->field_k_n[$node->language][]['tid'] = $kon_id;	
	$node->field_svar[$node->language][]['value'] = $svar;
	$node->field_h_rsellinjens_noteringar[$node->language][]['value'] = $noteringar;
	switch ($alder){	
		case '0-15':
			$age_id = 40;
			break;
		case '-15':
			$age_id = 40;
			break;
		case '16-24':
			$age_id = 41;
			break;
		case '25-34':
			$age_id = 42;
			break;
		case '35-44':
			$age_id = 43;
			break;
		case '45-54':
			$age_id = 44;
			break;
		case '55-64':
			$age_id = 45;
			break;
		case '65-74':
			$age_id = 46;
			break;
		case '75-84':
			$age_id = 47;
			break;
		case '85-110':
			$age_id = 48;
			break;
		case '85-':
			$age_id = 48;
			break;
		case null:
			$age_id = 165;
			break;
		default:
			$age_id = 165; // Okänt			
	}	
	$node->field__lder[$node->language][]['tid'] = $age_id;

	if($nytt_lan == null) {
		$node->field_l_n[$node->language][]['tid'] = 166;	
	}
	else {
		$node->field_l_n[$node->language][]['tid'] = $nytt_lan_id;	
	}

	if($gammalt_funktion_id != null && $gammalt_funktion_id != 7) {
		$status_id = 72;
	}
	elseif ($anhorig == 1) {
		$status_id = 71;
	}
	else {
		$status_id = 70;
	}

	$node->field_status[$node->language][]['tid'] = $status_id;
	$node->field_typ_av_h_rselskada[$node->language][]['tid'] = $nytt_hsk_id;
	$node->field_syssels_ttning[$node->language][]['tid'] = $nytt_sysselsattning_id;
	
	if($status_id == 71){
	$node->field_typ_av_anh_rig[$node->language][]['tid'] = 145;	// Okänt
	}

	$node->field_typ_av_funktion[$node->language][]['tid'] = $nytt_funktion_id;;
	
	if($medlem == 1){
		$medlem_id = 134;
	}
	else {
		$medlem_id = 135;
	}
	$node->field_medlem[$node->language][]['tid'] = $medlem_id;
	$node->field_upptackte_horsellinjen[$node->language][]['tid'] = $nytt_upptackte_id;
	$node->field_tidning_eller_program[$node->language][]['value'] = $tidning_program;
	if($PUL == 1){
		$PUL_val = 1;
	}
	else {
		$PUL_val = 0;
	}
	$node->field_godk_nner_lagring_enligt_p[$node->language][]['value'] = $PUL_val;

	$target_id = get_target_id($person_id);
	if($target_id != null) {
		$node->field_personuppgifter[$node->language][]['target_id'] = $target_id;
	}
	else {
		$node->field_personuppgifter[$node->language][]['target_id'] = 1448; // "Anonym" -id???
	}
	if($service == 7) {
		$hanv_jur = 1;
	}
	else {
		$hanv_jur = 0;		
	}
	$node->field_h_nvisad_till_juridisk_kon[$node->language][]['value'] = $hanv_jur;
	if ($service == 1) {
		$hanv_omb = 1;
	}
	else {
		$hanv_omb = 0;		
	}	
	$node->field_h_nvisad_till_ombudsman_in[$node->language][]['value'] = $hanv_omb;	
	
	db_query("UPDATE tblarende SET exporterad = 1 WHERE KontaktID = $kontakt_id");

	if($node = node_submit($node)) { // Prepare node for saving
	    node_save($node);
	    echo "Node with nid " . $node->nid . " saved!\n";
	}
}  

function get_target_id($person) {
	$result = db_query("SELECT entity_id FROM field_data_field_person_id WHERE field_person_id_value = $person");
	foreach ($result as $row) {
		return $row->entity_id;
	}
}

function get_media($media_id) {
	if($media_id == null) {
		return 'Ingen uppgift';
	}
	else {
		$result = db_query("SELECT KanalMedia FROM tblkanalmedia WHERE KanalMediaID = $media_id");
		foreach ($result as $row){
			return $row->KanalMedia;
		}
	}
}

function get_new_ID($table, $old, $vid) {
	if($old == null){
		$new = db_query("SELECT tid FROM taxonomy_term_data WHERE vid = $vid AND name = 'okänt'");
		foreach ($new as $unknown) {
			return $unknown->tid;
		}
	}
	else {
		$new = db_query("SELECT new_ID FROM $table WHERE old_ID = $old");
		foreach ($new as $id) {
			return $id->new_ID;
		}		
	}
}

function get_anonym_id($contact) {
	$result = db_query("SELECT AnonymID FROM tblkontakt WHERE KontaktID = $contact");
	foreach ($result as $row){
		return $row->AnonymID;
	}
}
function get_person_info($column, $contact) {	// tex kön och ålder eller annat som anges i anonym-tabellen
	$result = db_query("SELECT $column FROM tblanonym INNER JOIN tblkontakt ON tblanonym.AnonymID = tblkontakt.AnonymID WHERE KontaktID = $contact");
	foreach ($result as $row){			
		return $row->$column;
	}	
}

function get_contact_date($contact) {
	$result = db_query("SELECT Datum FROM tblkontakt WHERE KontaktID = $contact");
	foreach ($result as $row) {
		return $row->Datum;
	}
}

function get_contact_type($contact) {
	$result = db_query("SELECT KontakttypID FROM tblkontakt WHERE KontaktID = $contact");
	foreach($result as $row) {
		return $row->KontakttypID;
	}
}
?>