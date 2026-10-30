<?php
// backend_php/config/database.php
declare(strict_types=1);

class Database {
    private static ?MongoDB\Driver\Manager $manager = null;
    public const DB_NAME = 'test';
    public const URI = 'mongodb://mobileshope:bcDp4Oavbs9XntQz@ac-dzsraml-shard-00-00.sdhppgs.mongodb.net:27017,ac-dzsraml-shard-00-01.sdhppgs.mongodb.net:27017,ac-dzsraml-shard-00-02.sdhppgs.mongodb.net:27017/?ssl=true&replicaSet=atlas-q86f5x-shard-0&authSource=admin';

    public static function getManager(): MongoDB\Driver\Manager {
        if (self::$manager === null) {
            try {
                self::$manager = new MongoDB\Driver\Manager(self::URI, [
                    'connectTimeoutMS' => 10000,
                    'serverSelectionTimeoutMS' => 10000,
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit;
            }
        }
        return self::$manager;
    }

    public static function executeQuery(string $collection, array $filter = [], array $options = []): array {
        $manager = self::getManager();

        // Convert string _id to ObjectId if valid 24-hex string
        if (isset($filter['_id']) && is_string($filter['_id']) && preg_match('/^[a-f\d]{24}$/i', $filter['_id'])) {
            $filter['_id'] = new MongoDB\BSON\ObjectId($filter['_id']);
        }

        $query = new MongoDB\Driver\Query($filter, $options);
        $namespace = self::DB_NAME . '.' . $collection;
        $cursor = $manager->executeQuery($namespace, $query);
        $result = [];
        foreach ($cursor as $document) {
            $result[] = self::normalizeDocument($document);
        }
        return $result;
    }

    public static function executeFindOne(string $collection, array $filter = []): ?array {
        $docs = self::executeQuery($collection, $filter, ['limit' => 1]);
        return count($docs) > 0 ? $docs[0] : null;
    }

    public static function executeInsert(string $collection, array $document): string {
        $manager = self::getManager();
        $bulk = new MongoDB\Driver\BulkWrite();
        if (!isset($document['_id'])) {
            $id = new MongoDB\BSON\ObjectId();
            $document['_id'] = $id;
        } else if (is_string($document['_id'])) {
            $id = new MongoDB\BSON\ObjectId($document['_id']);
            $document['_id'] = $id;
        } else {
            $id = $document['_id'];
        }

        if (!isset($document['createdAt'])) {
            $document['createdAt'] = new MongoDB\BSON\UTCDateTime();
        }
        $document['updatedAt'] = new MongoDB\BSON\UTCDateTime();

        $bulk->insert($document);
        $namespace = self::DB_NAME . '.' . $collection;
        $manager->executeBulkWrite($namespace, $bulk);
        return (string)$id;
    }

    public static function executeUpdate(string $collection, array $filter, array $updateData, array $options = ['multi' => false, 'upsert' => false]): int {
        $manager = self::getManager();
        $bulk = new MongoDB\Driver\BulkWrite();

        // Convert string _id to ObjectId in filter if applicable
        if (isset($filter['_id']) && is_string($filter['_id']) && preg_match('/^[a-f\d]{24}$/i', $filter['_id'])) {
            $filter['_id'] = new MongoDB\BSON\ObjectId($filter['_id']);
        }

        // Ensure updatedAt timestamp is updated
        if (isset($updateData['$set'])) {
            $updateData['$set']['updatedAt'] = new MongoDB\BSON\UTCDateTime();
        } else {
            $updateData['$set'] = ['updatedAt' => new MongoDB\BSON\UTCDateTime()];
        }

        $bulk->update($filter, $updateData, $options);
        $namespace = self::DB_NAME . '.' . $collection;
        $result = $manager->executeBulkWrite($namespace, $bulk);
        return $result->getModifiedCount();
    }

    public static function executeDelete(string $collection, array $filter, array $options = ['limit' => 1]): int {
        $manager = self::getManager();
        $bulk = new MongoDB\Driver\BulkWrite();
        if (isset($filter['_id']) && is_string($filter['_id']) && preg_match('/^[a-f\d]{24}$/i', $filter['_id'])) {
            $filter['_id'] = new MongoDB\BSON\ObjectId($filter['_id']);
        }
        $bulk->delete($filter, $options);
        $namespace = self::DB_NAME . '.' . $collection;
        $result = $manager->executeBulkWrite($namespace, $bulk);
        return $result->getDeletedCount();
    }

    public static function executeAggregate(string $collection, array $pipeline): array {
        $manager = self::getManager();
        $cmd = new MongoDB\Driver\Command([
            'aggregate' => $collection,
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);
        $cursor = $manager->executeCommand(self::DB_NAME, $cmd);
        $result = [];
        foreach ($cursor as $doc) {
            $result[] = self::normalizeDocument($doc);
        }
        return $result;
    }

    public static function executeCount(string $collection, array $filter = []): int {
        $manager = self::getManager();
        $commandArgs = ['count' => $collection];
        if (!empty($filter)) {
            $commandArgs['query'] = $filter;
        }
        $cmd = new MongoDB\Driver\Command($commandArgs);
        $res = $manager->executeCommand(self::DB_NAME, $cmd)->toArray()[0];
        return (int)($res->n ?? 0);
    }

    public static function normalizeDocument(object|array $doc): array {
        $array = (array)$doc;
        $normalized = [];
        foreach ($array as $key => $value) {
            if ($value instanceof MongoDB\BSON\ObjectId) {
                $normalized[$key] = (string)$value;
            } elseif ($value instanceof MongoDB\BSON\UTCDateTime) {
                $normalized[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                $normalized[$key] = self::normalizeDocument($value);
            } elseif (is_array($value)) {
                $sub = [];
                foreach ($value as $k => $v) {
                    if ($v instanceof MongoDB\BSON\ObjectId) {
                        $sub[$k] = (string)$v;
                    } elseif ($v instanceof MongoDB\BSON\UTCDateTime) {
                        $sub[$k] = $v->toDateTime()->format('Y-m-d H:i:s');
                    } elseif (is_object($v) || is_array($v)) {
                        $sub[$k] = self::normalizeDocument($v);
                    } else {
                        $sub[$k] = $v;
                    }
                }
                $normalized[$key] = $sub;
            } else {
                $normalized[$key] = $value;
            }
        }
        return $normalized;
    }

    public static function toObjectId(string $id): MongoDB\BSON\ObjectId {
        return new MongoDB\BSON\ObjectId($id);
    }
}
