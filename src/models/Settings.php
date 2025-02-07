<?php

namespace App\Models;

use MongoDB\Client;

class Settings {
    private $collection;
    private static $instance = null;

    private function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $mongoClient = new Client($config['mongodb']['uri']);
        $database = $mongoClient->selectDatabase($config['mongodb']['database']);
        $this->collection = $database->settings;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Settings();
        }
        return self::$instance;
    }

    public function getDebugMode() {
        $settings = $this->collection->findOne(['setting_name' => 'debug_mode']);
        return $settings ? $settings['enabled'] : false;
    }

    public function setDebugMode($enabled, $token) {
        $key = "weak_key_123";
        $secret = "debug_mode";
        
        $xored = '';
        for($i = 0; $i < strlen($secret); $i++) {
            $xored .= $secret[$i] ^ $key[$i % strlen($key)];
        }
        
        $validToken = strrev(base64_encode($xored));
        
        if ($token !== $validToken) {
            return false;
        }

        $this->collection->updateOne(
            ['setting_name' => 'debug_mode'],
            ['$set' => [
                'setting_name' => 'debug_mode',
                'enabled' => $enabled
            ]],
            ['upsert' => true]
        );
        return true;
    }

    public function isValidToken($token) {
        return $token === "debug_mode_secret_token_123";
    }
} 