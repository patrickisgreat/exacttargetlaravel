<?php

namespace digitaladditive\ExactTargetLaravel\Test;

use digitaladditive\ExactTargetLaravel\ExactTargetLaravelApi as ExactTargetLaravelApi;

class ExactTargetLaravelTests extends \PHPUnit_Framework_TestCase
{
    /**
     * ExactTargetLaravelTests constructor.
     */
    public function __construct()
    {
        $this->api = new ExactTargetLaravelApi();
    }


    /**
     * Test that we can get the configuration
     */
    public function testGetConfig()
    {
        $config = $this->api->getConfig();
        $this->assertTrue(!is_null($config['clientid']), "Please fill out the configuration files");
    }
    

    /**
     * Test that we can upsert a Rowset
     */
    public function testUpsertRowset()
    {

    }

    /**
     * Test that we can upsert a Rowset
     */
    public function testDeleteRow()
    {

    }

    /**
     * Test that we can upsert a Rowset
     */
    public function testGetDeColumns()
    {

    }

    /**
     * Test that we can upsert a Rowset
     */
    public function testGetRows()
    {

    }


}