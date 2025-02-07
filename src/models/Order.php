<?php

namespace App\Models;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Order {
    private $collection;
    private $database;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $client = new Client($config['mongodb']['uri']);
        $this->database = $client->selectDatabase($config['mongodb']['database']);
        $this->collection = $this->database->orders;
    }
    
    private function documentToArray($document) {
        if (!$document) return null;
        $array = (array)$document;
        
        if (isset($array['_id'])) {
            $array['id'] = (string)$array['_id'];
        }
        
        if (isset($array['created_at']) && $array['created_at'] instanceof UTCDateTime) {
            $array['created_at'] = [
                '$date' => $array['created_at']->toDateTime()->format('c')
            ];
        }
        
        return $array;
    }
    
    public function create($userId, $productId, $amount) {
        $order = [
            'user_id' => new ObjectId($userId),
            'product_id' => new ObjectId($productId),
            'amount' => $amount,
            'status' => 'completed',
            'created_at' => new UTCDateTime(),
        ];
        
        $result = $this->collection->insertOne($order);
        $order['_id'] = $result->getInsertedId();
        return $this->documentToArray($order);
    }
    
    public function getUserOrders($userId) {
        $orders = $this->collection->find(
            ['user_id' => new ObjectId($userId)],
            [
                'sort' => ['created_at' => -1],
                'limit' => 10
            ]
        );
        
        $result = [];
        foreach ($orders as $order) {
            $product = $this->database->products->findOne(['_id' => $order['product_id']]);
            $orderArray = $this->documentToArray($order);
            $orderArray['product'] = $product ? (array)$product : null;
            $result[] = $orderArray;
        }
        
        return $result;
    }
    
    public function getOrder($orderId) {
        try {
            $order = $this->collection->findOne(['_id' => new ObjectId($orderId)]);
            return $this->documentToArray($order);
        } catch (\Exception $e) {
            return null;
        }
    }
} 