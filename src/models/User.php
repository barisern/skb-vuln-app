<?php

namespace App\Models;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

class User {
    private $collection;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $client = new Client($config['mongodb']['uri']);
        $database = $client->selectDatabase($config['mongodb']['database']);
        $this->collection = $database->users;
    }
    
    private function documentToArray($document) {
        if (!$document) return null;
        $array = (array)$document;
        if (isset($array['_id'])) {
            $array['id'] = (string)$array['_id'];
        }
        return $array;
    }

    public function findByCredentials($username, $password) {
        $user = $this->collection->findOne([
            'username' => $username,
            'password' => $password
        ]);
        return $this->documentToArray($user);
    }
    
    public function create($data) {
        $document = [
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user',
            'balance' => (float)($data['balance'] ?? 0.00),
            'firstName' => $data['firstName'] ?? '',
            'lastName' => $data['lastName'] ?? '',
            'identityNumber' => $data['identityNumber'] ?? '',
        ];
        
        $result = $this->collection->insertOne($document);
        $document['_id'] = $result->getInsertedId();
        return $this->documentToArray($document);
    }
    
    public function findById($id) {
        try {
            $user = $this->collection->findOne(['_id' => new ObjectId($id)]);
            return $this->documentToArray($user);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function updateBalance($userId, $amount) {
        $user = $this->findById($userId);
        if (!$user) return false;
        
        $newBalance = $user['balance'] + $amount;
        return $this->collection->updateOne(
            ['_id' => new ObjectId($userId)],
            ['$set' => ['balance' => $newBalance]]
        )->getModifiedCount() > 0;
    }
    
    public function getBalance($userId) {
        $user = $this->findById($userId);
        return $user ? $user['balance'] : 0;
    }
    
    public function getAllUsers() {
        $users = $this->collection->find([]);
        $result = [];
        foreach ($users as $user) {
            $result[] = $this->documentToArray($user);
        }
        return $result;
    }
    
    public function getUsersByRole($role) {
        $users = $this->collection->find(['role' => $role]);
        $result = [];
        foreach ($users as $user) {
            $result[] = $this->documentToArray($user);
        }
        return $result;
    }

    public function register($username, $password, $role = 'user', $data = []) {
        $document = [
            'username' => $username,
            'password' => $password,
            'role' => $role,
            'balance' => 0.00,
            'firstName' => $data['firstName'] ?? '',
            'lastName' => $data['lastName'] ?? '',
            'identityNumber' => $data['identityNumber'] ?? ''
        ];
        
        try {
            $result = $this->collection->insertOne($document);
            $document['_id'] = $result->getInsertedId();
            return $this->documentToArray($document);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteById($id) {
        try {
            return $this->collection->deleteOne(['_id' => new ObjectId($id)])->getDeletedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
} 