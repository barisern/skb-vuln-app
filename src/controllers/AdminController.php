<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\GiftCard;
use App\Models\Product;
use App\Models\Settings;
use App\Utils\Auth;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class AdminController extends BaseController {
    private $user;
    private $giftCard;
    private $auth;
    private $product;
    
    public function __construct() {
        parent::__construct();
        $this->user = new User();
        $this->giftCard = new GiftCard();
        $this->auth = new Auth();
        $this->product = new Product();
    }
    
    private function checkAdminAccess() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$token || !$this->auth->isAdmin($token)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }
        }
    }
    
    public function dashboard() {
        $this->checkAdminAccess();
        echo $this->render('admin/dashboard.html.twig');
    }
    
    public function listUsers() {
        $this->checkAdminAccess();
        
        $users = $this->user->getAllUsers();
        
        echo $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }

    public function devListUsers() {
        $users = $this->user->getAllUsers();
        
        echo json_encode($users);
    }
    
    public function createGiftCard() {
        $this->checkAdminAccess();
        
        $data = $this->getRequestData();
        $result = $this->giftCard->createGiftCard($data['amount']);
        
        if ($result) {
            $this->json(['success' => true, 'code' => $result]);
        } else {
            http_response_code(400);
            $this->json(['error' => 'Failed to create gift card']);
        }
    }
    
    public function listGiftCards() {
        $this->checkAdminAccess();
        $cards = $this->giftCard->getAllGiftCards();
        $this->json($cards);
    }

    public function addProductPage() {
        $this->checkAdminAccess();
        echo $this->render('admin/add-product.html.twig');
    }

    public function addProduct() {
        $this->checkAdminAccess();
        
        $data = $this->getRequestData();
        
        $result = $this->product->createProduct($data);
        
        if ($result) {
            $this->json(['success' => true, 'product' => $result]);
        } else {
            http_response_code(400);
            $this->json(['error' => 'Failed to create product']);
        }
    }

    public function exportProducts() {
        $this->checkAdminAccess();
        
        $products = $this->product->getAllProducts();
        
        $tempFile = sys_get_temp_dir() . '/product_export_' . uniqid() . '.products';
        
        try {
            $serializedData = serialize($products);
            $base64Data = base64_encode($serializedData);
            file_put_contents($tempFile, $base64Data);
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="products_export.products"');
            header('Content-Length: ' . filesize($tempFile));
            readfile($tempFile);
            
            unlink($tempFile);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            $this->json(['error' => 'Failed to export products: ' . $e->getMessage()]);
        }
    }

    public function usersPage() {
        $this->checkAdminAccess();
        $users = $this->user->getAllUsers();
        echo $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }

    public function createUser() {
        $this->checkAdminAccess();
        $data = $this->getRequestData();
        
        $result = $this->user->create($data);
        
        if ($result) {
            $this->json(['success' => true, 'user' => $result]);
        } else {
            http_response_code(400);
            $this->json(['error' => 'Failed to create user']);
        }
    }

    public function deleteUser($userId) {
        $this->checkAdminAccess();
        
        try {
            $this->user->deleteById($userId);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            $this->json(['error' => 'Failed to delete user']);
        }
    }

    public function getDebugMode() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$token || !$this->auth->isAdmin($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $settings = Settings::getInstance();
        echo json_encode([
            'enabled' => $settings->getDebugMode()
        ]);
    }

    public function setDebugMode() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$token || !$this->auth->isAdmin($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $debugToken = $data['token'] ?? '';
        $enabled = $data['enabled'] ?? false;

        $settings = Settings::getInstance();
        $success = $settings->setDebugMode($enabled, $debugToken);

        echo json_encode([
            'success' => $success
        ]);
    }

    public function exportExampleProduct() {
        $this->checkAdminAccess();
        
        $exampleProduct = [[ 
            'name' => 'Example Product',
            'price' => 99.99,
            'description' => 'This is an example product for testing',
            'stock' => 10,
            'featured' => true
        ]];
        
        $serializedData = serialize($exampleProduct);
        $base64Data = base64_encode($serializedData);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="example_product.products"');
        echo $base64Data;
        exit;
    }

    public function getAdminInfo() {
        $admins = $this->user->getUsersByRole('admin');
        
        if (!empty($admins)) {
            $this->json([
                'success' => true,
                'admin_id' => $admins[0]['id']
            ]);
        } else {
            http_response_code(404);
            $this->json([
                'success' => false,
                'message' => 'No admin found'
            ]);
        }
    }
} 