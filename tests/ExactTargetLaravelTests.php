<?php

namespace digitaladditive\ExactTargetLaravel\Test;

use digitaladditive\ExactTargetLaravel\ExactTargetLaravelApi as ExactTargetLaravelApi;
use PHPUnit\Framework\TestCase;

class ExactTargetLaravelTests extends TestCase
{
    /**
     * ExactTargetLaravelTests constructor.
     */
    public function __construct()
    {
        parent::__construct();
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
    public function testUpsertRowsetJson()
    {
        $values =
            '[
            {
                "keys":{
                "primaryKey": "1"
                        },
                "values":{
                "emailAddress": "newemail@email.com"
                        }
            },
            {
                "keys": {
                "primaryKey": "2"
                        },
                "values":{
                "emailAddress": "newemail2@email.com"
                        }
            }
        ]';

        $test = $this->api->upsertRowset($values, 'ETApiTest');
        $this->assertTrue(is_array($test));
    }

    /**
     *
     */
    public function testUpsertRowsetArray()
    {
        $array = [
            [
                'values' => [
                    "emailAddress" => "newnewemail2@email.com"
                ],
                'keys' => [
                    'primaryKey' => 5,
                ]
            ],
            [
                'values' => [
                    "emailAddress" => "newnewemail3@email.com"
                ],
                'keys' => [
                    'primaryKey' => 6,
                ]
            ]
        ];

        $test = $this->api->upsertRowset($array, 'ETApiTest');
        $this->assertEquals(200, $test['responseBody']);
    }

    /**
     * Test that we can delete a Row
     */
    public function testDeleteRow()
    {
        $test = $this->api->deleteRow('ETApiTest', ['primaryKey' => 1]);
        $this->assertEquals(200, $test);
    }

    /**
     * Test that we can get the Columns from a Data Extension
     */
    public function testGetDeColumns()
    {
        $test = $this->api->getDeColumns('ETApiTest');
        $this->assertTrue(count($test) == 2);
    }

    /**
     * Test that we can Get the rows from a data extension
     */
    public function testGetRows()
    {
        $test = $this->api->getRows('ETApiTest');
        $this->assertTrue(is_object($test[0]));
    }

    /**
     * Test that we can Upsert data to an existing Row in an ET Data Extension
     */
    public function testUpsertRow()
    {
        $values = ['emailAddress' => 'nothing@nothing.com'];
        $test = $this->api->upsertRow('primaryKey', '2', $values, 'ETApiTest');
        $delete = $this->api->deleteRow('ETApiTest', ['primaryKey' => 2]);
        $this->assertEquals(200, $test);
    }

    /**
     * Test Creating a new Row in a DE
     */
    public function testCreateRow()
    {
        $props = [
            'primaryKey' => 9,
            'emailAddress' => 'nothing@nothing.com'
        ];
        $test = $this->api->createRow('ETApiTest', $props);
        $delete = $this->api->deleteRow('ETApiTest', ['primaryKey' => 9]);
        $this->assertEquals(200, $test);
    }

    /**
     * Test Patching an existing Row in a DE
     */
    public function testPatchRow()
    {
        $props = [
            'primaryKey' => 9,
            'emailAddress' => 'nothing@nothing.com'
        ];
        $createTestRow = $this->api->createRow('ETApiTest', $props);
        $testPatch = $this->api->patchRow('ETApiTest', [
            'primaryKey' => 9,
            'emailAddress' => 'newaddress@newaddress.com'
        ]);
        $delete = $this->api->deleteRow('ETApiTest', ['primaryKey' => 9]);
        $this->assertEquals(200, $testPatch);
    }

    /**
     *
     */
    public function testValidateEmail()
    {
        $email = 'test@test.com';
        $test = $this->api->validateEmail($email);
        $this->assertEquals(200, $test['responseCode']);
    }

    /**
     *
     */
    public function testCreateDe()
    {
        //Data Extension Names && Columns
        $deStructures = [
            "TestDE" => [
                ["Name" => "id", "FieldType" => "Number", "IsPrimaryKey" => "true", "IsRequired" => "true"],
                ["Name" => "created_at", "FieldType" => "Date"],
                ["Name" => "update_at", "FieldType" => "Date"]
            ]
        ];

        $test = $this->api->createDe($deStructures);
        $this->assertEquals(200, $test);

    }

    /**
     *
     */
    public function testDeleteDe()
    {
        $props = ["Name" => 'TestDE'];

        $test = $this->api->deleteDe($props);

        $this->assertEquals(200, $test);
    }

    public function testCreateContentBuilderAsset()
    {
        $test_img = file_get_contents(__DIR__.'/../test_image.jpg');

        $base64EncImage = base64_encode($test_img);
        $ext = pathinfo($this->filePath, PATHINFO_EXTENSION);
        $json = json_encode([
            "name" => "test_image.jpg",
            "assetType" => [
                "name" => $ext,
                "id" => "28"
            ],
            // this maps to email folder in THD account
            // we'll need to determine which folder(s) to use or if it even matters
            "category" => [
                "id" => "53169"
            ],
            "file" => $base64EncImage
        ]);

        $test = $this->create_content_builder_asset($json);

        $this->assertEquals(201, $test);

    }
//    public function testTriggerSend()
//    {
//        $email = "digitaladditive.5ae940f.new@emailtests.com";
//        $first_name = "";
//        $last_name = "";
//        $custKey = "EtApiLibTest";
//
//        $test = $this->api->trigger_send($email, $first_name, $last_name, $custKey);
//
//        $this->assertTrue($test->responses[0]->hasErrors === false);
//    }

    // you can reEnable these test in an env that has FTP installed. My simple CI environment doesn't allow FTP
    // these tests pass in production environments
//    public function testUploadViaFtp()
//    {
//        $host = 'ftp1.exacttarget.com';
//        $userName = '10673211';
//        $userPass = 'Xf9.g4.E.';
//        $remoteFilePath = 'GitHub-Mark.png';
//        $localFilePath = realpath(dirname(__FILE__)) .'/GitHub-Mark.png';
//
//        $test = $this->api->it_uploads_a_file_via_ftp($host, $userName, $userPass, $remoteFilePath, $localFilePath);
//
//        $this->assertTrue($test);
//    }
//
//    public function testCreatePortfolioFile()
//    {
//        $props = [
//            "CustomerKey" => 'Github_Mark',
//            "DisplayName" => 'GitHub-Mark.png',
//            "Source" => array("URN"=>"File://ETFTP/Import/GitHub-Mark.png"),
//            "Description" => 'an image'
//        ];
//
//        $test = $this->api->it_creates_a_portfolio_file($props);
//        $this->assertTrue($test);
//    }

}
