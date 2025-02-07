<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class BaseController {
    protected $twig;
    
    public function __construct() {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
            'auto_reload' => true
        ]);
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
    }
    
    protected function render($template, $data = []) {
        return $this->twig->render($template, $data);
    }
    
    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    protected function getRequestData() {
        return json_decode(file_get_contents('php://input'), true);
    }
} 