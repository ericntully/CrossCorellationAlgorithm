<?php

use PHPUnit\Framework\TestCase;

require(__DIR__ . "/../classes/EmergencySignal.php");

class EmergencySignalTest extends TestCase {
    public function testAdd(): void {

        $emergency_call =   array(  7777771 =>  10,   //  BLE id 7777771 (the beacon in room 410) at signal strength 10%
                                    7777772 =>  95,   //  BLE id 7777772 (the beacon in room 411) at signal strength 95%
                                    7777773 =>  10,   //  BLE id 7777773 (the beacon in room 412) at signal strength 95%
                                    7777762 =>  95,   //  BLE id 7777762 (the beacon in room 311) at signal strength 95%
                                    7777782 =>  95,   //  BLE id 7777782 (the beacon in room 511) at signal strength 95%
                            );

        $presampled_rooms =    array(410 => array(  7777770 =>  10, 
                                                    7777771 =>  95, 
                                                    7777772 =>  10, 
                                                    7777761 =>  95, 
                                                    7777781 =>  95),

                                     411 => array(  7777771 =>  10, 
                                                    7777772 =>  95, 
                                                    7777773 =>  10, 
                                                    7777762 =>  95, 
                                                    7777782 =>  95),

                                     412 => array(  7777772 =>  10, 
                                                    7777773 =>  95, 
                                                    7777774 =>  10, 
                                                    7777763 =>  95, 
                                                    7777783 =>  95),

                                     311 => array(  7777761 =>  10, 
                                                    7777762 =>  95, 
                                                    7777763 =>  10, 
                                                    7777752 =>  95, 
                                                    7777772 =>  95),

                                     511 => array(  7777781 =>  10, 
                                                    7777782 =>  95, 
                                                    7777783 =>  10, 
                                                    7777772 =>  95, 
                                                    7777792 =>  95)
                            );


        $emergency_signal = new EmergencySignal($emergency_call);

        //  Next line only appears in PHPUnit tests to control which rooms should be compared 
        //  to $emergency_call.  When the EmergencySignal class sees that $presampled_rooms has 
        //  already been set, it will skip the step that loads related rooms from the database.
        $emergency_signal->set_rooms_with_matching_beacons($presampled_rooms);

        $this->assertEquals(411, $emergency_signal->get_best_match());

    }
}
