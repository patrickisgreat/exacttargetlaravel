# A Simple API for connecting a Laravel app to Exact Target

[![Build Status](https://semaphoreci.com/api/v1/pbisgreat/exacttargetlaravel/branches/master/badge.svg)](https://semaphoreci.com/pbisgreat/exacttargetlaravel)
This Laravel package provides a collection of methods for clean and easy interaction with the all new
Exact Target REST API as well as methods for use with Fuel SDK.

It implements the following Contract which explains what is currently available for use in your Controllers or models.
You can get a good idea of what each one will do just from the comments.
This is a work in progress and there are many more endpoints to make methods for.

This build includes one implementation for Laravel.

```php
<?php
	interface EtInterface {
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
	public function getToken($clientId, $clientSecret, $getTokenUri);

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
	public function upsertRowset($values, $deKey);

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
	public function deleteRow($deName, $props);

	/**
	* @param $deName
	*  Required -- Name of the Data Extension to query
	*
	* @return array
	*/
	public function getDeColumns($deName);

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
	public function getRows($deName, $keyName='', $primaryKey='');

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
	public function asyncUpsertRowset($keys, $values, $deKey);

	/**
	* PUT
	*
	* Upserts a data extension row by key.
	*
	* /dataevents/key:{key}/rows/{primaryKeys}
	*/
	public function upsertRow($pKey, $pVal, $values, $deKey);

	/**
	* PUT
	*
	* Asynchronously upserts a data extension row by key.
	*
	* these async methods need testing when / if we have a need for async requests (which we will)
	*
	* /dataeventsasync/key:{key}/rows/{primaryKeys}
	*/
	public function asyncUpsertRow($pKey, $pVal, $values, $deKey);

	/**
	* Create a Data extension by passing an array of DE Name keys => Column props values.
	*
	* @param $deStructures
	* @return array (response)
	*/
	public function createRow($deName, $props);

	/**
	* POST
	*
	* To validate an email address, perform an HTTP POST specifying the email address and validators
	* to be used in the payload of the HTTP POST. You can use more than one validator in the same call.
	*
	* /validateEmail
	*
	*/
	public function validateEmail($email);

	/**
	* Create a Data extension by passing an array of DE Name keys => Column props values.
	*
	* @param $deStructures
	* @return array (response)
	*/
	public function createDe($deStructures)

}
```

## Installation


Begin by installing this package through Composer. Edit your project's `composer.json` file to require `digitaladditive/exacttargetlaravel`. Or just run composer require digitaladditive/exacttargetlaravel

	"require-dev": {
		"digitaladditive/ExactTargetLaravel": "~1.1"
	}

Next, update Composer from the Terminal:

    composer update --dev

Next, You will have to fill out your Exact Target CLIENT_ID and CLIENT_SECRET within the ExactTargetLaravelConfig.php file located in the root of this package with your Exact Target API credentials.

Make sure you have 'mcrypt' installed an enabled on your web server or Fuel SDK calls will fail

Now just write a use statement at the top of your Laravel app files like so:

	use digitaladditive/ExactTargetLaravel/LaravelEtApi;


## Usage

A few usage examples

```php

<?php
	/* asynchronously upsert a batch of Rows to a DE*/
	return $this->etConnect()->asyncUpsertRowset(["primaryKey" => 1], ["emailAddress" => "newemail@newemail.com"], 'TestingRest  ');

	/* upsert a single row to a DE */
	return $this->etConnect()->upsertRow('primaryKey', 1, ['emailAddress' => 'oncemore@oncemore.com'], 'TestingRest');

	/* Validate an Email address */
	return $this->etConnect()->validateEmail('patrickisgreat@gmail.com');

	/* Delete a Row from a DE */
	return $this->etConnect()->deleteRow('TestingRest', ['primaryKey' => 1]);
```
