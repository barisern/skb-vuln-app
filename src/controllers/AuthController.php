<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\Auth;

class AuthController extends BaseController {
    private $auth;
    
    public function __construct() {
        parent::__construct();
        $this->auth = new Auth();
    }
    
    public function loginPage() {
        echo $this->render('auth/login.html.twig');
    }
    
    public function registerPage() {
        echo $this->render('auth/register.html.twig');
    }

    public function profilePage() {
        echo $this->render('profile/index.html.twig');
    }
    
    public function login() {
        $data = $this->getRequestData();
        $token = $this->auth->login($data['username'], $data['password']);
        
        if ($token) {
            $this->json(['token' => $token]);
        } else {
            http_response_code(401);
            $this->json(['error' => 'Invalid credentials']);
        }
    }
    
    public function register() {
        $data = $this->getRequestData();
        $result = $this->auth->register(
            $data['username'], 
            $data['password'],
            $data['role'] ?? 'user',
            [
                'firstName' => $data['firstName'] ?? '',
                'lastName' => $data['lastName'] ?? '',
                'identityNumber' => $data['identityNumber'] ?? ''
            ]
        );
        
        if ($result) {
            $this->json(['success' => true]);
        } else {
            http_response_code(400);
            $this->json(['error' => 'Registration failed']);
        }
    }
    
    public function logout() {
        // TODO: Implement JWT token invalidation? How?
        header('Location: /login');
    }

    public function getUserInfo($id) {
        try {
            $auth = new Auth();
            $token = $auth->getTokenFromHeader();
            
            if (!$token) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            $payload = $auth->verifyToken($token);
            if (!$payload) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }
            
            if ($id) {
                $user = $auth->getUserById($id);
            } else {
                $user = $auth->getUserById($payload->user_id);
            }
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            $userData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'balance' => $user['balance'],
                'role' => $user['role']
            ];
            
            if ($user['role'] == 'admin') {
                $userData['password'] = $user['password'];
            }
            
            echo json_encode($userData);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
} 