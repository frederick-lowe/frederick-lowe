<?php
namespace Ido\Classes;

use \MongoDB\Client;
use \MongoDB\Collection;
use \MongoDB\Driver\Cursor;
use \MongoDB\Model\BSONDocument;

class Document {

    /** @var \MonogDB\Client $client */
    private ?\MongoDB\Client $client = null;

    /** @var \MonogDB\Collection $collection */
    private ?\MongoDB\Collection $collection = null;

	public function __construct () 
	{

	}

	public function getClient(
		string $dbname, ?string $host = 'localhost', ?string $port = '27017'
	) : \MongoDB\Client 
	{
		$connectString = 'mongodb://' . $host . ':' . $port . DIRECTORY_SEPARATOR . $dbname;

		if(!$this->client) {
            $this->client = new Client(
                $connectString, [], 
                ['serverApi' => new \MongoDB\Driver\ServerApi(\MongoDB\Driver\ServerApi::V1)]
            );
		}
		return $this->client;
	}

    public function getConnection (string $collection) : Collection {
    	$dbname = 'frederick-lowe';
    	$client = $this->getClient($dbname);
        return $this->client->$dbname->$collection;
    }


    public function cursorToArray (\MongoDB\Driver\Cursor $cursor) : array {
        $results = [];
        foreach ($cursor as $document) {
            array_push($results, $this->documentToArray($document));
        }
        return $results;
    }

    public function documentToArray (\MongoDB\Model\BSONDocument $document) : array {
        $document = json_encode($document);
        if (isset($sanitationService)) {
            $document = $sanitationService->sanitizeValue($document);
        }
        $result = json_decode($document, true) ?? [];
        return $result;
    }

    public function resultToArray(mixed $result): mixed {
    	if(!$result) {
    		return [];
    	}

        return match (get_class($result)) {
            'MongoDB\Driver\Cursor' => $this->cursorToArray($result),
            'MongoDB\Model\BSONDocument' => $this->documentToArray($result),
            default => (function() use ($result) {
                return $result;
            })()
        };
    }

	public function select(string $collection, array $query, ?array $options) 
	{
		$connection = $this->getConnection($collection);
		$result = $connection->findOne($query, $options);
		return $this->resultToArray($result);
	}

	public function insert(string $collection, array $documents) 
	{

	}

	public function update(string $collection, array $query, ?array $options) 
	{

	}

	public function delete(string $collection, array $query, ?array $options) 
	{

	}

}