<?php

namespace Tests\Unit;

use Tests\TestCase;
use DTApi\Helpers\TelHelper;


class HelperTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testWillExpirateAtTest()
    {

        // Comparing condition if hours difference is less than and equal to 24
        $due_time = "2021-03-20 10:00:00";
        $created_at = "2021-03-20 00:00:00";
        $this->assertEquals("2021-03-20 01:30:00", TelHelper::willExpireAt($due_time, $created_at));

        // Comparing condition if hours difference is greater than 24 and less than and equal to 72
        $due_time = "2021-03-22 00:00:00";
        $created_at = "2021-03-20 00:00:00";
        $this->assertEquals("2021-03-20 16:00:00", TelHelper::willExpireAt($due_time, $created_at));

        // Comparing condition if hours difference is greater than 72 and less than and equal to 90
        $due_time = "2021-03-23 01:00:00";
        $created_at = "2021-03-20 00:00:00";
        $this->assertEquals("2021-03-23 01:00:00", TelHelper::willExpireAt($due_time, $created_at));

        // Comparing condition if hours difference is greater than 90
        $due_time = "2021-03-24 00:00:00";
        $created_at = "2021-03-20 00:00:00";
        $this->assertEquals("2021-03-22 00:00:00", TelHelper::willExpireAt($due_time, $created_at));
    }
}
