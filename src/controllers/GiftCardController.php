<?php

namespace App\Controllers;

use App\Models\GiftCard;
use App\Utils\Auth;

class GiftCardController extends BaseController {
    private $giftCard;
    private $auth;
    
    public function __construct() {
        parent::__construct();
        $this->giftCard = new GiftCard();
        $this->auth = new Auth();
    }
    
    public function redeem() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $payload = $this->auth->validateToken($token);
        
        if (!$payload) {
            http_response_code(401);
            $this->json(['error' => 'Unauthorized']);
            return;
        }
        
        $data = $this->getRequestData();
        $code = $data['code'] ?? '';
        
        if (empty($code)) {
            http_response_code(400);
            $this->json(['error' => 'Gift card code is required']);
            return;
        }
        
        $result = $this->giftCard->useGiftCard($code, $payload->user_id);
        
        if ($result) {
            $this->json(['success' => true]);
        } else {
            http_response_code(400);
            $this->json(['error' => 'Invalid or already used gift card']);
        }
    }

    public function listAll() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $payload = $this->auth->validateToken($token);
        
        if (!$payload || $payload->role !== 'admin') {
            http_response_code(401);
            $this->json(['error' => 'Unauthorized']);
            return;
        }
        
        $giftCards = $this->giftCard->getAllGiftCards();
        $this->json(['giftCards' => $giftCards]);
    }
} 