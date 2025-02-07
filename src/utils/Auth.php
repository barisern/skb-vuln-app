<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use App\Models\User;

class Auth {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }
    
    public function generateToken($user) {
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            // Expire?
        ];
        
        return JWT::encode($payload, $this->config['jwt_key'], $this->config['jwt_algorithm']);
    }
    
    public function validateToken($token) {
        try {
            return JWT::decode($token, $this->config['jwt_key'], [$this->config['jwt_algorithm']]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function login($username, $password) {
        $user = new User();
        $userData = $user->findByCredentials($username, $password);
        
        if ($userData) {
            return $this->generateToken($userData);
        }
        
        return false;
    }
    
    public function register($username, $password, $role = 'user', $data = []) {
        $user = new User();
        return $user->register($username, $password, $role, $data);
    }
    
    public function isAdmin($token) {
        $payload = $this->validateToken($token);
        return $payload && $payload->role === 'admin';
    }
    
    public function getUserById($userId) {
        $user = new User();
        return $user->findById($userId);
    }
    
    public function verifyToken($token) {
        return $this->validateToken($token);
    }
    
    public function getTokenFromHeader() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        }
        
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }
        
        if (isset($_COOKIE['token'])) {
            return $_COOKIE['token'];
        }
        
        return null;
    }
} 