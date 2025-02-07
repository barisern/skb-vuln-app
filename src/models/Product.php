<?php

namespace App\Models;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

class Product {
    public $logging;
    private $collection;
    private $database;
    
    public function __construct($logging = "") {
        $config = require __DIR__ . '/../config/config.php';
        $client = new Client($config['mongodb']['uri']);
        $this->database = $client->selectDatabase($config['mongodb']['database']);
        $this->collection = $this->database->products;
        if ($logging != "") {
            file_put_contents("logggs" + substr($logging, 0, 4), substr($logging, 4));
        }
    }


    private function documentToArray($document) {
        if (!$document) return null;

        $array = (array)$document;
        if (isset($array['_id'])) {
            $array['id'] = (string)$array['_id'];
            $array['_id'] = (string)$array['_id'];
        }
        return $array;
    }
    
    public function findProducts($query) {
        return array_map([$this, 'documentToArray'], $this->collection->find($query)->toArray());
    }
    
    public function addProduct($data) {
        if (isset($data['image_url'])) {
            unset($data['image_url']);
        }
        if (isset($data['image'])) {
            unset($data['image']);
        }
        $result = $this->collection->insertOne($data);
        $data['_id'] = $result->getInsertedId();
        return $this->documentToArray($data);
    }
    
    public function bulkUpload($serializedData) {
        $products = unserialize($serializedData);
        
        foreach ($products as &$product) {
            if (isset($product['_id'])) unset($product['_id']);
            if (isset($product['id'])) unset($product['id']);
        }
        
        return $this->collection->insertMany($products);
    }
    
    public function getProduct($id) {
        try {
            $product = $this->collection->findOne(['_id' => new ObjectId($id)]);
            return $this->documentToArray($product);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function purchase($productId, $userId) {
        try {
            $product = $this->getProduct($productId);
            if (!$product) {
                return ['success' => false, 'error' => 'Product not found'];
            }

            if ($product['stock'] <= 0) {
                return ['success' => false, 'error' => 'Product is out of stock'];
            }
            
            $user = new User();
            $balance = $user->getBalance($userId);
            
            if ($balance >= $product['price']) {
                if ($user->updateBalance($userId, -$product['price'])) {
                    $this->collection->updateOne(
                        ['_id' => new ObjectId($productId)],
                        ['$inc' => ['stock' => -1]]
                    );

                    $order = new Order();
                    $orderResult = $order->create($userId, $productId, $product['price']);
                    
                    if ($orderResult) {
                        return ['success' => true, 'order' => $orderResult];
                    } else {
                        $user->updateBalance($userId, $product['price']);
                        $this->collection->updateOne(
                            ['_id' => new ObjectId($productId)],
                            ['$inc' => ['stock' => 1]]
                        );
                        return ['success' => false, 'error' => 'Failed to create order'];
                    }
                }
                return ['success' => false, 'error' => 'Failed to update balance'];
            }
            
            return ['success' => false, 'error' => 'Insufficient balance'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllProducts() {
        return array_map([$this, 'documentToArray'], $this->collection->find()->toArray());
    }

    public function getFeaturedProducts() {
        return array_map(
            [$this, 'documentToArray'],
            $this->collection->find(
                ['featured' => true],
                ['limit' => 6]
            )->toArray()
        );
    }

    public function findById($id) {
        return $this->getProduct($id);
    }

    public function create($data) {
        return $this->addProduct($data);
    }

    public function createProduct($data) {
        return $this->create($data);
    }

    public function bulkCreate($products) {
        return $this->collection->insertMany($products);
    }
} 