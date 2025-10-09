<?php

require(__DIR__ . "/../classes/EmergencySignal.php");

try {
	$emergency_call = $_POST["emergency_call"];
	$emergency_signal = new EmergencySignal($emergency_call);
	
	$best_match_room_number = $emergency_signal->get_best_match();
	print "The emergency is in room #" . $best_match_room_number;

}
catch (Exception $e) {
	$this->LOG( "Failed to match Emergency Call" );
	$this->ALARM( $this->USER, $emergency_call );
}


