<?php

namespace digitaladditive\ExactTargetLaravel;

use GuzzleHttp\Exception\BadResponseException as BadResponseException;
use FuelSdk\ET_DataExtension_Column as ET_DataExtension_Column;
use GuzzleHttp\Exception\RequestException as RequestException;
use FuelSdk\ET_DataExtension_Row as ET_DataExtension_Row;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use FuelSdk\ET_DataExtension as ET_DataExtension;
use GuzzleHttp\Psr7\Request as Request;
use FuelSdk\ET_Client as ET_Client;
use FuelSdk\ET_Asset as ET_Asset;
use FuelSdk\ET_Patch as ET_Patch;
use FuelSdk\ET_Post as ET_Post;
use FuelSdk\ET_Get as ET_Get;
use GuzzleHttp\Client as Client;

/**
 *
 * Class ExactTargetLaravelApi
 *
 * @package App
 *
 */
class ExactTargetLaravelApi implements ExactTargetLaravelInterface
{
    use SerializeDataTrait;

    /**
     * client id
     * @var string
     */
    protected $clientId;

    /**
     * client secret
     * @var array
     */
    protected $clientSecret;

    /**
     * base uri
     * @var array
     */
    protected $getTokenUri;

    /**
     * Guzzle Client
     * @var object
     */
    protected $client;

    /**
     * Fuel Client
     * @var object
     */
    protected $fuel;

    /**
     * Fuel DE Object
     * @var object
     */
    protected $fuelDe;


    /**
     * ExactTargetLaravelApi constructor.
     */
    public function __construct($config=null)
    {
        $this->getTokenUri = 'https://auth.exacttargetapis.com/v1/requestToken';
        $this->client = new Client();
        $this->fuelDe = new ET_DataExtension_Row();
        $this->fuelDeColumn = new ET_DataExtension_Column();
        $this->fuelDext = new ET_DataExtension();
        $this->etAsset = new ET_Asset();
        //allows you to pull this from a database with your own implementations
        if ($config === null) {
            $this->config = $this->getConfig();
        } else {
            $this->config = $config;
            //$this->getConfig();
        }
        $this->clientId = $this->config['clientid'];
        $this->clientSecret = $this->config['clientsecret'];
        $this->accessToken = $this->getToken($this->clientId, $this->clientSecret, $this->getTokenUri);
    }

    /**
     * @return bool|mixed
     */
    public function getConfig()
    {
        $config = false;
        //moved this from constructor so we can override instantiating with DB credentials if desired.
        if (file_exists(__DIR__ .'/../ExactTargetLaravelConfig.php')) {
            $config = include __DIR__ .'/../ExactTargetLaravelConfig.php';
            $this->fuel = new ET_Client(false, false, $config);
            return $config;
        }

        return $config;
    }


    /**
     * reaches out to Exact Target Rest API with client secret and Id
     * returns the auth token
     *
     * Client is the guzzle object and all the methods you need
     *
     * @param $clientId
     * @param $clientSecret
     * @param $getTokenUri
     * @param Client $client
     * @return array
     */
    public function getToken($clientId, $clientSecret, $getTokenUri)
    {
        //------------------
        // Get Access Token
        //------------------
        $params = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret
        ];

        $params = json_encode($params);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json'
        ];

        $post = $this->client->post($getTokenUri, ['body' => $params, 'headers' => $headers]);

        $response = json_decode($post->getBody());

        return compact('response');
    }

    /**
    * POST
    *
    * /dataevents/key:{key}/rowset
    *
    * Upserts a batch of data extensions rows by key.
    *
    * @param $keys
    * @param $values
    * @param Client $client
    * @return array
    */
    public function upsertRowset($data, $dataExtensionKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataevents/key:'.$dataExtensionKey.'/rowset';

        if (is_array($data)) {
            $data = $this->it_serializes_data($data);
        }

        $request['body'] = $data;

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        try {
            //post upsert
            $response = $this->client->post($upsertUri, $request);
            $responseBody = json_decode($response->getStatusCode());
        } catch (BadResponseException $exception) {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo $exc. '\n';
        }

        return compact('responseBody');
    }
    /**
     * SOAP WDSL
     *
     * uses the Fuel SDK to delete a row by Primary Key
     * currently the v1 of the REST api does not support retrieval of data.
     * Hopefully this will change in the near future
     *
     * @param $deName
     * @param $props
     * @return array -- the response from Exact Target
     */
    public function deleteRow($deName, $props)
    {
        //new up & auth up ET Fuel
        $this->fuelDe->authStub = $this->fuel;

        $this->fuelDe->props = $props;

        $this->fuelDe->CustomerKey = $deName;

        $getRes = $this->fuelDe->delete();

        if ($getRes->status == true) {
            return $getRes->code;
        }

        return print 'Message: '.$getRes->code.'\n';
    }


    /**
     * @param $deName
     *  Required -- Name of the Data Extension to query
     *
     * @return array
     */
    public function getDeColumns($deName)
    {

        //Get all Data Extensions Columns filter by specific DE

        $this->fuelDeColumn->authStub = $this->fuel;

        $this->fuelDeColumn->filter = array('Property' => 'CustomerKey','SimpleOperator' => 'equals','Value' => $deName);

        $getResult = $this->fuelDeColumn->get();

        if ($getResult->status == true) {
            return $getResult->results;
        }

        return print 'Message: '.$getResult->message.'\n';
    }

    /**
     * SOAP WDSL
     *
     * uses the Fuel SDK to grab all the rows of a given Data Extension
     * currently the v1 of the REST api does not support retrieval of data.
     * Hopefully this will change in the near future
     *
     *
     * @param $keyName
     *  This is an optional param if set along with primaryKey the result will be filtered to a single row by PrimaryKey
     * @param $primaryKey
     *  This is an optional param if set along with keyName the result will be filtered to a single row by PrimaryKey
     * @param $deName
     *  Required -- Name of the Data Extension to query
     * @return array
     *  Response from ET
     */

    public function getRows($deName, $keyName='', $simpleOperator='', $keyValue='')
    {
        //get column names from DE
        $deColumns = $this->getDeColumns($deName);

        //new up & auth up ET Fuel
        $this->fuelDe->authStub = $this->fuel;

        $this->fuelDe->Name = $deName;

        //build array of Column names from DE
        foreach ($deColumns as $k => $v) {
            $this->fuelDe->props[] = $v->Name;
        }

        //if the function is called with these values -- filter by them
        if ($keyValue !== '' && $keyName !== '' && $simpleOperator == '') {
            $this->fuelDe->filter = array('Property' => $keyName, 'SimpleOperator' => 'equals', 'Value' => $keyValue);
        } elseif ($keyValue !== '' && $keyName !== '' && $simpleOperator != '') {
            $this->fuelDe->filter = array('Property' => $keyName, 'SimpleOperator' => $simpleOperator, 'Value' => $keyValue);
        }

        //get rows from the columns
        $results = $this->fuelDe->get();

        if ($results->status == false) {
            return $results->message;
        }

        $results->results['responseCode'] = $results->code;
        $results->moreResults = $results->moreResults;
        return $results->results;
    }

    /**
     * POST
     *
     * Asynchronously upserts a batch of data extensions rows by key.
     *
     * these async methods need testing when / if we have a need for async requests (which we will)
     *
     * /dataeventsasync/key:{key}/rowset
     *
     */
    public function asyncUpsertRowset($data, $deKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataeventsasync/key:'.$deKey.'/rowset';

        if (is_array($data)) {
            $data = $this->it_serializes_data($data);
        }

        $request['body'] = $data;

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        try {
            //post upsert
            $promise = $this->client->postAsync($upsertUri, $request);
            $promise->then(
            //chain logic to the response (can fire from other classes or set booleans)
                function (ResponseInterface $res) {
                    $response = $res->getStatusCode() . '\n';
                },
                function (RequestException $e) {
                    $response = $e->getMessage() . '\n';
                    $responseMethod = $e->getRequest()->getMethod();
                }
            );
            $promise->wait();
        } catch (BadResponseException $exception) {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo $exc;
        }
    }


    /**
     * PUT
     *
     * Upserts a data extension row by key.
     *
     * /dataevents/key:{key}/rows/{primaryKeys}
     */
    public function upsertRow($primaryKeyName, $primaryKeyValue, $data, $deKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataevents/key:'.$deKey.'/rows/'.$primaryKeyName.':'.$primaryKeyValue;

        $values = ['values' => $data];

        $request['body'] = json_encode($values);

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        try {
            //post upsert
            $response = $this->client->put($upsertUri, $request);
            $responseBody = json_decode($response->getBody());
            $responseCode = json_decode($response->getStatusCode());
        } catch (BadResponseException $exception) {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            //echo 'Oh No! Something went wrong! '.$exc;
            //return $exc;
            return (string) $exc;
        }
        return $responseCode;
    }



    /**
     * PUT
     *
     * Asynchronously upserts a data extension row by key.
     *
     * these async methods need testing when / if we have a need for async requests (which we will)
     *
     * /dataeventsasync/key:{key}/rows/{primaryKeys}
     */
    public function asyncUpsertRow($primaryKeyName, $primaryKeyValue, $data, $deKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataeventsasync/key:'.$deKey.'/rows/'.$primaryKeyName.':'.$primaryKeyValue;

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        //api implementation style
        if (is_array($data)) {
            $data = $this->it_serializes_data($data);
        }

        $request['body'] = $data;

        try {
            //post upsert
            $promise = $this->client->putAsync($upsertUri, $request);
            $promise->then(
            //chain logic to the response (can fire from other classes or set booleans)
                function (ResponseInterface $res) {
                    echo $res->getStatusCode() . '\n';
                },
                function (RequestException $e) {
                    echo $e->getMessage() . '\n';
                    echo $e->getRequest()->getMethod();
                }
            );
            $promise->wait();
        } catch (BadResponseException $exception) {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo 'Oh No! Something went wrong! '.$exc;
        }

        return compact('promise');
    }

    /**
     * Create a Data extension by passing an array of DE Name keys => Column props values.
     *
     * @param $deStructures
     * @return array (response)
     */
    public function createRow($deName, $props)
    {

        //new up & auth up ET Fuel
        $this->fuelDe->authStub = $this->fuel;

        $this->fuelDe->Name = $deName;

        $this->fuelDe->props = $props;

        $getRes = $this->fuelDe->post();

        if ($getRes->status == true) {
            return $getRes->code;
        }
        return $getRes;
    }


    /**
     * @param $deName
     * @param $props
     * @return ET_Patch|int
     */
    public function patchRow($deName, $props)
    {
        $this->fuelDe->authStub = $this->fuel;

        $this->fuelDe->Name = $deName;

        $this->fuelDe->props = $props;

        $getRes = $this->fuelDe->patch();

        if ($getRes->status == true) {
            return $getRes->code;
        }
        return $getRes;
    }
    /**
     * POST
     *
     * To validate an email address, perform an HTTP POST specifying the email address and validators
     * to be used in the payload of the HTTP POST. You can use more than one validator in the same call.
     *
     * /validateEmail
     *
     */
    public function validateEmail($email)
    {
        $request['headers'] = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        $request['body'] = json_encode([
            'email' => $email,
            'validators' => ['SyntaxValidator', 'MXValidator', 'ListDetectiveValidator']
        ]);

        $response = $this->client->post('https://www.exacttargetapis.com/address/v1/validateEmail', $request);

        $responseBody = json_decode($response->getBody());

        $responseCode = json_decode($response->getStatusCode());

        return compact('responseCode', 'responseBody');
    }


    /**
     * Create a Data extension by passing an array of DE Name keys => Column props values.
     *
     * @param $deStructures
     * @return array (response)
     */
    public function createDe($deStructures)
    {
        $this->fuelDext->authStub = $this->fuel;

        foreach ($deStructures as $k => $name) {
            $this->fuelDext->props = [
                'Name' => $k,
                'CustomerKey' => $k
            ];

            $this->fuelDext->columns = [];

            foreach ($name as $key => $val) {
                $this->fuelDext->columns[] = $val;
            }
            try {
                $getRes = $this->fuelDext->post();

                return $getRes->code;
            } catch (Exception $e) {
                return 'Message: '.$getRes->message.'\n';
            }
        }
        return compact('getRes');
    }

    /**
     * @param $props
     * @return int|string
     */
    public function deleteDe($props)
    {
        $this->fuelDext->authStub = $this->fuel;

        $this->fuelDext->props = $props;

        try {
            $getRes = $this->fuelDext->delete();
            return $getRes->code;
        } catch (Exception $e) {
            return 'Message: '.$getRes->message.'\n';
        }
    }

    /**
     * Upload a File to Exact Target FTP
     */
    public function it_uploads_a_file_via_ftp($host, $userName, $userPass, $remoteFilePath, $localFilePath)
    {
        $conn_id = ftp_connect($host);

        $login_result = ftp_login($conn_id, $userName, $userPass);

        ftp_pasv($conn_id, true);

        if (ftp_chdir($conn_id, 'Import') && ftp_put($conn_id, $remoteFilePath, $localFilePath, FTP_BINARY)) {
            ftp_close($conn_id);
            return true;
        }

        echo 'There was a problem while uploading $file\n';
        ftp_close($conn_id);
        return false;
    }

    /**
     * Transfer a File from FTP to Exact Target Portfolio
     *
     * @param $props array('filePath' => $_SERVER['PWD'] . '/sample-asset-TestFilePath.txt');
     * see tests for expected array structure of $props
     * @return true
     *
     */
    public function it_creates_a_portfolio_file($props)
    {
        $objType = 'Portfolio';

        try {
            $response = new ET_Post($this->fuel, $objType, $props);
            if ($response->status == 1) {
                return $response;
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * Get an Asset From the Content Portfolio
     *
     * @param $id int
     *
     * @return response
     *
     */
    public function it_gets_an_asset($id)
    {
        $request['headers'] = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];


        $response = $this->client->get('www.exacttargetapis.com/asset/v1/content/assets/'.$id);

        $responseBody = json_decode($response->getBody());

        $responseCode = json_decode($response->getStatusCode());

        return compact('responseCode', 'responseBody');
    }

    /**
     * Get an Asset From the Content Portfolio via Web Services (SOAP)
     *
     * @param $id int
     *
     * @return response
     *
     */
    public function it_gets_an_asset_soap($id)
    {
        $objectType = 'Portfolio';

        $properties = [
            'FileURL'
        ];

        $filter = [
            'Property'       => 'CustomerKey',
            'SimpleOperator' => 'equals',
            'Value'      => $id
        ];

        $getSinceLastBatch = false;

        $getSendsResponse = new ET_Get($this->fuel, $objectType, $properties, $filter, $getSinceLastBatch);

        return $getSendsResponse;
    }

    /**
     * @param $email
     * @param $first_name
     * @param $last_name
     * @param $custKey
     * @return ResponseInterface|\Psr\Http\Message\StreamInterface
     */
    public function trigger_send($email, $first_name, $last_name, $custKey)
    {
        $triggerUri = 'https://www.exacttargetapis.com/messaging/v1/messageDefinitionSends/key:'.$custKey.'/send';

        $request['headers'] = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        $request['body'] = json_encode([
            "From" => [
                "Address" => 'test@test.com',
                "Name" => 'test'
            ],
            "To" => [
                "Address" => $email,
                "SubscriberKey" => $email
            ],
            "Options" => [
                "RequestType" => "SYNC"
            ]
        ]);

        try {
            //post upsert
            $this->client->post($triggerUri, $request);
            $response = $this->client->post($triggerUri, $request);
            $response = $response->getBody();

            return $response;
        } catch (BadResponseException $exception) {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);

            return $exc;
        }
    }
}
