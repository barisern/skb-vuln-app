<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Utils\Auth;

class ProductController extends BaseController {
    private $product;
    private $order;
    private $auth;
    
    public function __construct() {
        parent::__construct();
        $this->product = new Product();
        $this->order = new Order();
        $this->auth = new Auth();
    }
    
    public function listPage() {
        $products = $this->product->getAllProducts();
        echo $this->render('products/list.html.twig', ['products' => $products]);
    }

    public function homePage() {
        $products = $this->product->getFeaturedProducts();
        echo $this->render('home/index.html.twig', ['products' => $products]);
    }
    
    public function list() {
        $products = $this->product->getAllProducts();
        $this->json(['products' => $products]);
    }
    
    public function purchase() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $payload = $this->auth->validateToken($token);
        
        if (!$payload) {
            http_response_code(401);
            $this->json(['error' => 'Unauthorized']);
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['product_id'])) {
            http_response_code(400);
            $this->json(['error' => 'Product ID is required']);
            return;
        }
        
        $result = $this->product->purchase($data['product_id'], $payload->user_id);
        
        if ($result['success']) {
            $this->json([
                'success' => true,
                'order' => $result['order'],
                'message' => 'Purchase successful'
            ]);
        } else {
            http_response_code(400);
            $this->json([
                'success' => false,
                'error' => $result['error'] ?? 'Purchase failed'
            ]);
        }
    }
    
    public function getUserOrders() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $payload = $this->auth->validateToken($token);
        
        if (!$payload) {
            http_response_code(401);
            $this->json(['error' => 'Unauthorized']);
            return;
        }
        
        $orders = $this->order->getUserOrders($payload->user_id);
        $this->json(['orders' => $orders]);
    }
    
    public function bulkUpload() {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$token || !$this->auth->isAdmin($token)) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
            return;
        }

        if (!isset($_FILES['file'])) {
            http_response_code(400);
            $this->json(['error' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['file'];
        
        if (!preg_match('/\.products$/', $file['name'])) {
            http_response_code(400);
            $this->json(['error' => 'Invalid file type. Only .products files are allowed']);
            return;
        }

        try {
            $content = file_get_contents($file['tmp_name']);
            
            $serializedData = base64_decode($content);
            
            $result = $this->product->bulkUpload($serializedData);
            
            $this->json(['success' => true, 'message' => 'Products imported successfully']);
        } catch (\Exception $e) {
            http_response_code(400);
            $this->json(['error' => 'Failed to import products: ' . $e->getMessage()]);
        }
    }
} 