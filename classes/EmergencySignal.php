<?php

class EmergencySignal {

    var $EMERGENCY_SIGNAL = array();    // array( beacon0 => strength0, beacon1 => strength, ...)
    var $involved_rooms = false;        // array( room0 => array (beacon0 => strength, beacon1 => strength, ...),
                                        //          room1 => array (beacon1 => strength, beacon2 => strength, ...), 
                                        //          ... );
    var $avg_emerg_sig_strs = 0;
    var $sum_sqr_sig_strs = 0;
    var $involved_beacons = array();
    var $DB;
    var $n = 0;

    function __construct(array $emergency_signal, $DB = false) {

        // This is here for demo purposes. Replace with DB code for your framework.
        $this->DB = $DB ?? false;

        // Validate that $emergency_signal is an array of integers
        $this->n = count($emergency_signal);
        if ($this->n == 0 || count(array_filter($emergency_signal,'is_int')) != $this->n) {
            throw new Exception("Invalid list of emergency beacon ids");
        }
        $this->EMERGENCY_SIGNAL = $emergency_signal;
        $this->set_average_and_stddev();
    }


    /**
     * get_best_match() loops multiple possible matching rooms, returns highest scoring room
     *
     * @param array An array of arrays, Readings taken multiple rooms
     * @return int Best matching room number
     */
    public function get_best_match() : int {

        // $this->involved_rooms   Can be (and may already have been) set manually, such as in a PHPUnit test.
        // If it's not set,  set it now based on the beacons reported in  $this->EMERGENCY_SIGNAL
        if (!$this->involved_rooms) {
            $this->get_rooms_with_matching_beacons();
        }

        $highest_scored_room_num = -1; // int
        $highest_score = -1; // float
        foreach ($this->involved_rooms as $room_number => $sampled_room) {
            $score = $this->get_correlation_score($sampled_room);
            if ($score > $highest_score) {
                $highest_score = $score;
                $highest_scored_room_num = $room_number;
            }
        }
        return $highest_scored_room_num;
    }

    private function set_average_and_stddev() {
        $this->avg_emerg_sig_strs = array_sum($this->EMERGENCY_SIGNAL) / $this->n;
        foreach (array_values($this->EMERGENCY_SIGNAL) as $x) {
            $this->sum_sqr_sig_strs += pow($x - $this->avg_emerg_sig_strs, 2);
        }
    }

    /**
     * Details about the cross correlation algorithm:
     *       https://en.wikipedia.org/wiki/Cross-correlation
     *       (we use the normalized form,  aka Pearson)
     *
     * The score is between -1 (not a match) and +1 (perfect match).
     *
     * @param array $sampled_room Readings taken during setup/install of BLE beacons
     * @return float The similarity (Euclidian distance) between emergency and sampled signals
     */
    function get_correlation_score(array $sampled_room) : float {

        $results = array();

        $sample_signal_strengths = array_column($sampled_room, "signal_strength");

        // pre-calc avg sample signal strengths
        $avg_sample_sig_strn = array_sum($sample_signal_strengths) / $this->n;

        // Numerator is sigma (Xi - avg_X) * (Yi - avgY)
        $numerator = 0;

        // Denominator is sigma (Y_i - avg_Y)^2
        $sum_squared_sample_deltas = 0;

        // Calculate Numerator and Denominator
        foreach ($this->involved_beacons as $beacon_id) {

            $tmp_strn_emerg_beacon = array_key_exists($beacon_id, $this->EMERGENCY_SIGNAL) ? $this->EMERGENCY_SIGNAL[$beacon_id] : 0;
            $tmp_strn_samp_room_beacon = array_key_exists($beacon_id, $sampled_room) ? $sampled_room[$beacon_id] : 0 ;

            $delta_x = $tmp_strn_emerg_beacon - $this->avg_emerg_sig_strs;
            $delta_y = $tmp_strn_samp_room_beacon - $avg_sample_sig_strn;

            $numerator += ($delta_x * $delta_y);            
            $sum_squared_sample_deltas += pow($delta_y, 2);
        }

        // Handle edge case where all signals have same strength
        if ($sum_squared_sample_deltas === 0) {
            $correlation = 0.0;
        } else {
            $denominator = sqrt($this->sum_sqr_sig_strs * $sum_squared_sample_deltas);
            $correlation = $denominator != 0 ? $numerator / $denominator : 0;
        }

        return $correlation;
    }

    private function get_rooms_with_matching_beacons() : array {

        $rooms = array();
        $beacon_ids = array_keys($this->EMERGENCY_SIGNAL);

        // Validate input.   $beacon_ids must be an array of integers
        if (count(array_filter($beacon_ids,'is_int')) != count($beacon_ids)) {
            throw new Exception("Invalid list of beacon ids");
        }
        $query =    "SELECT room_number, beacon_id, signal_strength " . 
                    "FROM signals WHERE beacon_id IN (" . implode(",",$beacon_ids) . ")";
        $result = mysqli_query($this->DB, $query);
        if (mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                if (!array_key_exists($row["room_number"],$rooms)) {
                    $rooms[$row["room_number"]] = array();
                }
                $rooms[$row["room_number"]][] = array( $row["beacon_id"] => $row["signal_strength"] );
            }
        }
        $this->set_rooms_with_matching_beacons($rooms);
    }

    public function set_rooms_with_matching_beacons($rooms) {

        $this->involved_rooms = $rooms;
        $this->involved_beacons = array_unique(
                                      array_merge( array_keys($this->EMERGENCY_SIGNAL), 
                                                   ...array_map('array_keys', $this->involved_rooms) 
                                      )
                                  );
    }
}
