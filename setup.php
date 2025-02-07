<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Initializer;

class Setup {
    private $config;
    private $isInitialized = false;

    public function __construct() {
        $this->config = require __DIR__ . '/src/config/config.php';
    }

    public function checkStatus() {
        // Check MongoDB
        $mongoInitialized = false;
        try {
            $client = new MongoDB\Client($this->config['mongodb']['uri']);
            $client->listDatabases();
            $mongoInitialized = true;
            echo "MongoDB connection successful\n";
        } catch (Exception $e) {
            echo "MongoDB connection failed: " . $e->getMessage() . "\n";
        }

        $this->isInitialized = $mongoInitialized;
        return $this->isInitialized;
    }

    public function initialize() {
        if ($this->isInitialized) {
            echo "System is already initialized.\n";
            return true;
        }

        $initializer = new Initializer();
        if ($initializer->initialize()) {
            echo "System initialized successfully.\n";
            return true;
        } else {
            echo "System initialization failed.\n";
            return false;
        }
    }

    public function startServer($port = 8000) {
        if (!$this->checkStatus()) {
            echo "\n⚠️ Application is not initialized. Running initialization...\n";
            if (!$this->initialize()) {
                echo "\n❌ Failed to initialize the application.\n";
                exit(1);
            }
        }

        $host = 'skb';
        $publicDir = __DIR__ . '/public';

        echo "\n🌐 Starting PHP development server at http://$host:$port\n";
        echo "Press Ctrl+C to stop the server\n\n";

        passthru("php -S $host:$port -t $publicDir");
    }

    public function showHelp() {
        echo "\nVulnerable E-commerce Application Setup\n";
        echo "----------------------------------------\n";
        echo "Usage:\n";
        echo "  php setup.php --check         Check if application is initialized\n";
        echo "  php setup.php --init          Initialize the application\n";
        echo "  php setup.php --start[=PORT]  Start the development server (default port: 8000)\n";
        echo "\nExample:\n";
        echo "  php setup.php --start=8080    Start server on port 8080\n";
    }
}

$setup = new Setup();

if (php_sapi_name() === 'cli') {
    $options = getopt('', ['init', 'start::', 'check', 'help']);

    if (isset($options['help'])) {
        $setup->showHelp();
    }
    else if (isset($options['check'])) {
        if ($setup->checkStatus()) {
            echo "\n✅ Application is initialized and ready to use!\n";
        } else {
            echo "\n❌ Application is not initialized. Run 'php setup.php --init' to initialize.\n";
        }
    }
    else if (isset($options['init'])) {
        $setup->initialize();
    }
    else if (array_key_exists('start', $options)) {
        $port = is_numeric($options['start']) ? $options['start'] : 8000;
        $setup->startServer($port);
    }
    else {
        $setup->showHelp();
    }
} 