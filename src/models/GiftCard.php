<?php

namespace App\Models;

use MongoDB\Client;
use MongoDB\Driver\Exception\BulkWriteException;

class GiftCard {
    private $collection;
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
        $client = new Client($this->config['mongodb']['uri']);
        $this->collection = $client->{$this->config['mongodb']['database']}->gift_cards;
    }
    
    public function createGiftCard($amount) {
        $maxAttempts = 3;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            try {
                $code = $this->config['gift_card_prefix'] . bin2hex(random_bytes(8));
                $data = [
                    'code' => $code,
                    'amount' => intval($amount),
                    'used' => false
                ];
                
                $this->collection->insertOne($data);
                return $code;
            } catch (BulkWriteException $e) {
                if ($e->getCode() === 11000) {
                    $attempt++;
                    continue;
                }
                throw $e;
            }
        }
        
        throw new \Exception('Failed to generate unique gift card code after ' . $maxAttempts . ' attempts');
    }
    
    public function validateGiftCard($code) {
        $trimmedCode = trim($code);
        return $this->collection->findOne([
            'code' => $trimmedCode,
            'used' => false
        ]);
    }
    
    public function useGiftCard($code, $userId) {
        $trimmedCode = trim($code);
        $giftCard = $this->validateGiftCard($trimmedCode); 
        
        if (!$giftCard) {
            return false;
        }

        $user = new User();
        $user->updateBalance($userId, $giftCard['amount']); 
        
        // Processing transaction simulation...
        sleep(3);
        
        $this->collection->updateOne(
            ['code' => $trimmedCode],
            ['$set' => ['used' => true]]  
        );
        
        return true;
    }

    public function searchGiftCards($query) {
        return $this->collection->find($query)->toArray();
    }

    public function getAllGiftCards() {
        return $this->collection->find([], [
            'sort' => ['_id' => -1]
        ])->toArray();
    }
} 