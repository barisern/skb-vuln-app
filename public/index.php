<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/config/Initializer.php';

use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\AdminController;
use App\Controllers\GiftCardController;


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($uri, '/');
$route = empty($route) ? 'home' : $route;

$method = $_SERVER['REQUEST_METHOD'];

// Handle API routes
if (strpos($route, 'api/') === 0) {
    header('Content-Type: application/json');
    $apiRoute = substr($route, 4);
    
    try {
        switch ($apiRoute) {
            case 'login':
                $controller = new AuthController();
                if ($method === 'POST') {
                    $controller->login();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'register':
                $controller = new AuthController();
                if ($method === 'POST') {
                    $controller->register();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'products':
                $controller = new ProductController();
                if ($method === 'GET') {
                    $controller->list();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'purchase':
                $controller = new ProductController();
                if ($method === 'POST') {
                    $controller->purchase();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;

            case 'orders':
                $controller = new ProductController();
                if ($method === 'GET') {
                    $controller->getUserOrders();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;

            case 'gift-cards/redeem':
                $controller = new GiftCardController();
                if ($method === 'POST') {
                    $controller->redeem();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'admin/gift-cards':
                $controller = new AdminController();
                if ($method === 'GET') {
                    $controller->listGiftCards();
                } elseif ($method === 'POST') {
                    $controller->createGiftCard();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;

            case 'admin/settings/debug':
                $controller = new AdminController();
                if ($method === 'GET') {
                    $controller->getDebugMode();
                } elseif ($method === 'POST') {
                    $controller->setDebugMode();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;

            case 'admin/users':
                $controller = new AdminController();
                if ($method === 'GET') {
                    $controller->listUsers();
                } elseif ($method === 'POST') {
                    $controller->createUser();
                } else {
                    $controller->devListUsers();
                }
                break;

            case (preg_match('/^admin\/users\/([a-f0-9]+)$/', $apiRoute, $matches) ? true : false):
                $controller = new AdminController();
                $userId = $matches[1];
                if ($method === 'DELETE') {
                    $controller->deleteUser($userId);
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
                
            case 'admin/products/upload':
                $controller = new ProductController();
                if ($method === 'POST') {
                    $controller->bulkUpload();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
            case 'admin/products/add':
                $controller = new AdminController();
                if ($method === 'POST') {
                    $controller->addProduct();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
            
            case 'admin/products/export':
                $controller = new AdminController();
                if ($method === 'GET') {
                    $controller->exportProducts();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
            
            case 'admin/products/export-example':
                $controller = new AdminController();
                if ($method === 'GET') {
                    $controller->exportExampleProduct();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
            
            case 'user-info':
                $authController = new AuthController();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $authController->getUserInfo($_GET['id']);
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;    

            case 'admin-info':
                $controller = new AdminController();
                if ($method === 'GET') {
                    $controller->getAdminInfo();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

// Handle view routes
try {
    switch ($route) {
        case 'home':
            $controller = new ProductController();
            $controller->homePage();
            break;
            
        case 'products':
            $controller = new ProductController();
            $controller->listPage();
            break;
            
        case 'login':
            $controller = new AuthController();
            $controller->loginPage();
            break;
            
        case 'register':
            $controller = new AuthController();
            $controller->registerPage();
            break;
            
        case 'logout':
            $controller = new AuthController();
            $controller->logout();
            break;
            
        case 'profile':
            $controller = new AuthController();
            $controller->profilePage();
            break;
            
        case 'admin':
            $controller = new AdminController();
            if (isset($_GET['template'])) {
                $controller->listUsers();
            } else {
                $controller->dashboard();
            }
            break;
            
        case 'admin/users':
            $controller = new AdminController();
            $controller->usersPage();
            break;
            
        case 'admin/products/add':
            $controller = new AdminController();
            $controller->addProductPage();
            break;
            
        case 'healthcheck':
            require_once 'healthcheck.php';
            break;
            
        default:
            http_response_code(404);
            echo 'Page not found';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo 'Internal server error' . $e->getMessage();
} 