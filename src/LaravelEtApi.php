<?php

namespace digitaladditive\exacttargetlaravel;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use ET_Client;
use ET_DataExtension;
use ET_DataExtension_Row;
use ET_DataExtension_Column;

/**
 * Class EtApi
 * @package App
 */
class LaravelEtApi implements EtInterface {

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
     * Construction Work
     *
     * @param Client $client
     * @param ET_Client $fuel
     * @param ET_DataExtension_Row $fuelDe
     * @param ET_DataExtension_Column $fuelDeColumn
     */
    function __construct(Client $client, ET_Client $fuel, ET_DataExtension_Row $fuelDe, ET_DataExtension_Column $fuelDeColumn, ET_DataExtension $fuelDext)
    {

        $this->getTokenUri = 'https://auth.exacttargetapis.com/v1/requestToken';
        $this->client = $client;
        $this->fuelDeColumn = $fuelDeColumn;
        $this->fuel = $fuel;
        $this->fuelDe = $fuelDe;
        $this->fuelDext = $fuelDext;
        $this->config = $this->getConfig();
        $this->clientId = $this->config['clientid'];
        $this->clientSecret = $this->config['clientsecret'];
        $this->accessToken = $this->getToken($this->clientId, $this->clientSecret, $this->getTokenUri);

    }

    public function getConfig()
    {
        if (file_exists(__DIR__ .'/../config.php'))
        {
            $config = include __DIR__ .'/../config.php';

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
    public function upsertRowset($values, $deKey)
    {

        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataevents/key:'.$deKey.'/rowset';

        $serialized = [];

        foreach ($values as $k => $v)
        {
            $serialized[] =
                [
                    "keys" => $v['keys'],
                    "values" => $v['values']
                ];
        }
        $serialized = json_encode($serialized);

        $request['body'] = $serialized;

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        try {
            //post upsert
            $response = $this->client->post($upsertUri, $request);
            $responseBody = json_decode($response->getBody());

        } catch (BadResponseException $exception) {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo $exc. "\n";

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

        if ($getRes->status == true)
        {
            return $getRes->message;
        }

        return print 'Message: '.$getRes->message."\n";
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

        if ($getResult->status == true)
        {
            return $getResult->results;
        }

        return print 'Message: '.$getResult->message."\n";
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
    public function getRows($deName, $keyName='', $primaryKey='')
    {
        //get column names from DE
        $deColumns = $this->getDeColumns($deName);

        //new up & auth up ET Fuel
        $this->fuelDe->authStub = $this->fuel;

        $this->fuelDe->Name = $deName;

        //build array of Column names from DE
        foreach ($deColumns as $k => $v)
        {
            $this->fuelDe->props[] = $v->Name;
        }

        //if the function is calle with these values -- filter by them
        if ($primaryKey !== '' && $keyName !== '')
        {
            $this->fuelDe->filter = array('Property' => $keyName,'SimpleOperator' => 'equals','Value' => $primaryKey);
        }

        //get rows from the columns
        $getRes = $this->fuelDe->get();

        if ($getRes->status == true)
        {
            dd($getRes->OverallStatus);
            return $getRes;
        }

        return print 'Message: '.$getRes->message."\n";

        /*while ($results->OverallStatus=="MoreDataAvailable") {
            $rr = new ExactTarget_RetrieveRequest();
            $rr->ContinueRequest = $results->RequestID;
            $rrm = new ExactTarget_RetrieveRequestMsg();
            $rrm->RetrieveRequest = $rr;
            $results = null;
            $results = $client->Retrieve($rrm);
            $tempRequestID = $results->RequestID;
            print_r($results->OverallStatus.' : '.$results->RequestID.' : '.count($results->Results));
            print_r('<br>');
        }*/
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
    public function asyncUpsertRowset($keys, $values, $deKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataeventsasync/key:'.$deKey.'/rowset';

        //api implementation style
        $request['body'] = json_encode([[
            "keys" => $keys,
            "values" => $values
        ]]);

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
                function(ResponseInterface $res)
                {
                    echo $res->getStatusCode() . "\n";
                },
                function(RequestException $e)
                {
                    echo $e->getMessage() . "\n";
                    echo $e->getRequest()->getMethod();
                }
            );
        }
        catch (BadResponseException $exception)
        {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo $exc;

        }

        return compact('promise');
    }


    /**
     * PUT
     *
     * Upserts a data extension row by key.
     *
     * /dataevents/key:{key}/rows/{primaryKeys}
     */
    public function upsertRow($pKey, $pVal, $values, $deKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataevents/key:'.$deKey.'/rows/'.$pKey.':'.$pVal;

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        //api implementation style
        $request['body'] = json_encode([
            "values" => $values
        ]);

        try {
            //post upsert
            $response = $this->client->put($upsertUri, $request);
            $responseBody = json_decode($response->getBody());

        }
        catch (BadResponseException $exception)
        {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo "Oh No! Something went wrong! ".$exc;
        }
        return compact('responseBody');
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
    public function asyncUpsertRow($pKey, $pVal, $values, $deKey)
    {
        $upsertUri = 'https://www.exacttargetapis.com/hub/v1/dataeventsasync/key:'.$deKey.'/rows/'.$pKey.':'.$pVal;

        $request['headers'] = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken['response']->accessToken
        ];

        //api implementation style
        $request['body'] = json_encode([
            "values" => $values
        ]);

        try {
            //post upsert
            $promise = $this->client->putAsync($upsertUri, $request);
            $promise->then(
            //chain logic to the response (can fire from other classes or set booleans)
                function(ResponseInterface $res)
                {
                    echo $res->getStatusCode() . "\n";
                },
                function(RequestException $e)
                {
                    echo $e->getMessage() . "\n";
                    echo $e->getRequest()->getMethod();
                }
            );
        }
        catch (BadResponseException $exception)
        {
            //spit out exception if curl fails or server is angry
            $exc = $exception->getResponse()->getBody(true);
            echo "Oh No! Something went wrong! ".$exc;
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

        if ($getRes->status == true)
        {
            return compact('getRes');
        }

        return print 'Message: '.$getRes->message."\n";
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
            "email" => $email,
            "validators" => ["SyntaxValidator", "MXValidator", "ListDetectiveValidator"]
        ]);

        $response = $this->client->post('https://www.exacttargetapis.com/address/v1/validateEmail', $request);

        $responseBody = json_decode($response->getBody());

        return compact('responseBody');
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

        foreach ($deStructures as $k => $name)
        {

            $this->fuelDext->props = [
                "Name" => $k,
                "CustomerKey" => $k
            ];

            $this->fuelDext->columns = [];

            foreach ($name as $key => $val)
            {
                $this->fuelDext->columns[] = $val;
            }
            try
            {
                $getRes = $this->fuelDext->post();

                print 'The Following DE was created: '. $k. "\n";
            }
            catch (Exception $e)
            {
                echo "Oh No! Something went wrong! ".$exc;

                print 'Message: '.$getRes->message."\n";
            }
        }

        return compact('getRes');
    }

}