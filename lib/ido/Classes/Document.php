<?php

namespace Ido\Classes;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use MongoDB\Driver\ServerApi;
use MongoDB\BSON\ObjectId;
use Exception;

/**
 * Document class for MongoDB interactions.
 */
class Document
{
    private ?Client $client = null;
    private ?Collection $collection = null;
    private string $dbname = 'frederick-lowe';

    /**
     * Get or create a MongoDB client.
     *
     * @param string $dbname Database name
     * @param string $host MongoDB host
     * @param string $port MongoDB port
     * @return Client
     */
    public function getClient(
        string $dbname = 'frederick-lowe',
        string $host = 'localhost',
        string $port = '27017'
    ): Client {
        if (!$this->client) {
            $connectString = "mongodb://{$host}:{$port}/{$dbname}";
            $this->client = new Client(
                $connectString,
                [],
                ['serverApi' => new ServerApi(ServerApi::V1)]
            );
        }
        return $this->client;
    }

    /**
     * Get a MongoDB collection.
     *
     * @param string $collection Collection name
     * @return Collection
     */
    public function getConnection(string $collection): Collection
    {
        $client = $this->getClient($this->dbname);
        return $client->selectCollection($this->dbname, $collection);
    }

    /**
     * Convert a MongoDB cursor to an array.
     *
     * @param Cursor $cursor
     * @return array
     */
    public function cursorToArray(Cursor $cursor): array
    {
        return array_map([$this, 'documentToArray'], $cursor->toArray());
    }

    /**
     * Convert a BSON document to an array.
     *
     * @param BSONDocument $document
     * @return array
     */
    public function documentToArray(BSONDocument $document): array
    {
        $json = json_encode($document);
        // Note: $sanitationService is not defined in this scope. Consider injecting it if needed.
        // if (isset($sanitationService)) {
        //     $json = $sanitationService->sanitizeValue($json);
        // }
        return json_decode($json, true) ?? [];
    }

    /**
     * Convert various MongoDB result types to an array.
     *
     * @param mixed $result
     * @return mixed
     */
    public function resultToArray(mixed $result): mixed
    {
        if (!$result) {
            return [];
        }
        return match (get_class($result)) {
            Cursor::class => $this->cursorToArray($result),
            BSONDocument::class => $this->documentToArray($result),
            default => $result,
        };
    }

    /**
     * Expand references in the data.
     *
     * @param array $data
     * @param int $depth
     * @return array
     */
    public function expandReferences(array $data, int $depth = 0): array
    {
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if ($depth > 0 && $key === '_id' && isset($data['collection'])) {
                $id = $data['_id']['$oid'];
                $collection = $data['collection'];
                return $this->select($collection, ['_id' => new ObjectId($id)], [], true);
            }
            $data[$key] = $this->expandReferences($value, $depth + 1);
        }
        return $data;
    }

	/**
	 * Select documents from a collection.
	 *
	 * @param string $collection
	 * @param array $query
	 * @param array $options
	 * @param bool $expandReferences
	 * @param bool $multiple Whether to return multiple documents or just one
	 * @return array
	 */
	public function select(string $collection, array $query, array $options = [], bool $expandReferences = true, bool $multiple = false): array
	{
	    $connection = $this->getConnection($collection);
	    
	    if ($multiple) {
	        $cursor = $connection->find($query, $options);
	        $results = iterator_to_array($cursor);
	        $data = array_map([$this, 'resultToArray'], $results);
	    } else {
	        $result = $connection->findOne($query, $options);
	        $data = $result ? $this->resultToArray($result) : [];
	    }

	    if ($expandReferences) {
	        $data = $multiple ? array_map([$this, 'expandReferences'], $data) : $this->expandReferences($data);
	    }

	    return $data;
	}

    /**
     * Insert documents into a collection.
     *
     * @param string $collection
     * @param array $documents
     * @return bool
     */
    public function insert(string $collection, array $documents): bool
    {
        try {
            $connection = $this->getConnection($collection);
            $result = $connection->insertMany($documents);
            return $result->isAcknowledged() && $result->getInsertedCount() > 0;
        } catch (Exception $e) {
            // Log the exception
            error_log("Insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update documents in a collection.
     *
     * @param string $collection
     * @param array $query
     * @param array $update
     * @param array $options
     * @return bool
     */
    public function update(string $collection, array $query, array $update, array $options = []): bool
    {
        try {
            $connection = $this->getConnection($collection);
            $updateResult = $connection->updateOne($query, $update, $options);
            return $updateResult->isAcknowledged() && $updateResult->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete documents from a collection.
     *
     * @param string $collection
     * @param array $query
     * @param array $options
     * @return bool
     */
    public function delete(string $collection, array $query, array $options = []): bool
    {
        try {
            $connection = $this->getConnection($collection);
            $deleteResult = $connection->deleteMany($query, $options);
            return $deleteResult->isAcknowledged() && $deleteResult->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }
}