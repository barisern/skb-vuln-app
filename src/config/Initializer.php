<?php

namespace App\Config;

use MongoDB\Client;
use MongoDB\BSON\Decimal128;
use Exception;

class Initializer {
    private $config;
    private $mongoClient;
    private $isInitialized = false;
    private $database;

    public function __construct() {
        $this->config = require __DIR__ . '/config.php';
    }

    public function initialize() {
        try {
            $this->initializeMongoDB();
            $this->createCollections();
            $this->createIndexes();
            $this->insertSampleData();
            $this->isInitialized = true;
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    private function initializeMongoDB() {
        try {
            $this->mongoClient = new Client($this->config['mongodb']['uri']);
            $this->database = $this->mongoClient->selectDatabase($this->config['mongodb']['database']);
            echo "MongoDB connection initialized successfully\n";
        } catch (Exception $e) {
            throw new Exception("MongoDB initialization failed: " . $e->getMessage());
        }
    }

    private function createCollections() {
        $collections = ['users', 'products', 'gift_cards', 'orders', 'settings'];
        foreach ($collections as $collection) {
            if (!in_array($collection, iterator_to_array($this->database->listCollectionNames()))) {
                $this->database->createCollection($collection);
                echo "Created collection: $collection\n";
            }
        }
    }

    private function createIndexes() {
        $this->database->users->createIndex(['username' => 1], ['unique' => true]);
        
        $this->database->products->createIndex(['name' => 1]);
        $this->database->products->createIndex(['featured' => 1]);
        
        $this->database->gift_cards->createIndex(['code' => 1], ['unique' => true]);
        
        echo "Created necessary indexes\n";
    }

    private function insertSampleData() {
        $this->insertDefaultAdmin();
        $this->insertSampleProducts();
        $this->insertSampleGiftCards();
        $this->initializeDefaultSettings();
    }

    private function initializeDefaultSettings() {
        $settings = $this->database->settings->findOne(['setting_name' => 'debug_mode']);
        
        if (!$settings) {
            $this->database->settings->insertOne([
                'setting_name' => 'debug_mode',
                'enabled' => false
            ]);
            echo "Initialized debug mode setting to off\n";
        }
    }

    private function generateRandomPassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < 32; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    private function insertDefaultAdmin() {
        try {
            $adminPassword = $this->generateRandomPassword();
            $adminUser = [
                'username' => 'admin',
                'password' => $adminPassword,
                'role' => 'admin',
                'balance' => 1000.00,
                'firstName' => 'Talha',
                'lastName' => 'Baris',
                'identityNumber' => '11111111230'
            ];

            if (!$this->database->users->findOne(['username' => 'admin'])) {
                $this->database->users->insertOne($adminUser);
            }
        } catch (Exception $e) {
            echo "Warning: " . $e->getMessage() . "\n";
        }
    }

    private function insertSampleProducts() {
        try {
            $products = [
                [
                    'name' => 'Premium Laptop',
                    'description' => 'High-performance laptop with latest specifications',
                    'price' => 999.99,
                    'stock' => 10,
                    'featured' => true
                ],
                [
                    'name' => 'Wireless Headphones',
                    'description' => 'Premium wireless headphones with noise cancellation',
                    'price' => 199.99,
                    'stock' => 20,
                    'featured' => true
                ],
                [
                    'name' => 'Smartwatch',
                    'description' => 'Feature-rich smartwatch with health monitoring',
                    'price' => 299.99,
                    'stock' => 15,
                    'featured' => true
                ],
                [
                    'name' => 'Gaming Console',
                    'description' => 'Next-gen gaming console with 4K support',
                    'price' => 499.99,
                    'stock' => 5,
                    'featured' => false
                ],
                [
                    'name' => 'Smartphone',
                    'description' => 'Latest smartphone with advanced camera system',
                    'price' => 799.99,
                    'stock' => 8,
                    'featured' => true
                ]
            ];

            if ($this->database->products->countDocuments() === 0) {
                $this->database->products->insertMany($products);
                echo "Inserted sample products\n";
            }
        } catch (Exception $e) {
            echo "Warning: " . $e->getMessage() . "\n";
        }
    }

    private function insertSampleGiftCards() {
        try {
            $giftCards = [
                [
                    'code' => 'GIFT-WELCOME50',
                    'amount' => 50.00,
                    'used' => false,
                    'created_at' => new \MongoDB\BSON\UTCDateTime(),
                    'expires_at' => new \MongoDB\BSON\UTCDateTime((time() + (30 * 24 * 60 * 60)) * 1000)
                ],
                [
                    'code' => 'GIFT-SPECIAL100',
                    'amount' => 100.00,
                    'used' => false,
                    'created_at' => new \MongoDB\BSON\UTCDateTime(),
                    'expires_at' => new \MongoDB\BSON\UTCDateTime((time() + (30 * 24 * 60 * 60)) * 1000)
                ],
                [
                    'code' => 'GIFT-VIP200',
                    'amount' => 200.00,
                    'used' => false,
                    'created_at' => new \MongoDB\BSON\UTCDateTime(),
                    'expires_at' => new \MongoDB\BSON\UTCDateTime((time() + (30 * 24 * 60 * 60)) * 1000)
                ]
            ];

            if ($this->database->gift_cards->countDocuments() === 0) {
                $this->database->gift_cards->insertMany($giftCards);
                echo "Inserted sample gift cards\n";
            }
        } catch (Exception $e) {
            echo "Warning: " . $e->getMessage() . "\n";
        }
    }

    public function isInitialized() {
        return $this->isInitialized;
    }

    public function getMongoClient() {
        return $this->mongoClient;
    }
} 