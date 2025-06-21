<?php

declare(strict_types=1);

/**
 * Generate Cursor Deeplink for Laravel Telescope MCP Server
 * 
 * This script generates a cursor:// deeplink that can be used to automatically
 * configure the Laravel Telescope MCP server in Cursor.
 */

// Get the current directory (where this script is located)
$serverPath = __DIR__ . '/server.php';

// Default configuration
$defaultConfig = [
    'mcpServers' => [
        'laravel-telescope' => [
            'command' => 'phpx',
            'args' => ['execute', 'eduardocruz/laravel-telescope-mcp-server'],
            'env' => [
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'your_laravel_database',
                'DB_USERNAME' => 'your_db_username',
                'DB_PASSWORD' => 'your_db_password',
                'MCP_SERVER_NAME' => 'Laravel Telescope MCP Server'
            ]
        ]
    ]
];

echo "🔗 Laravel Telescope MCP Server - Cursor Deeplink Generator\n";
echo "=========================================================\n\n";

// Check if command line arguments are provided
if ($argc > 1) {
    // Parse command line arguments
    $options = [];
    for ($i = 1; $i < $argc; $i++) {
        if (strpos($argv[$i], '=') !== false) {
            [$key, $value] = explode('=', $argv[$i], 2);
            $key = ltrim($key, '-');
            $options[$key] = $value;
        }
    }
    
    // Update configuration with provided options
    if (isset($options['db-host'])) {
        $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_HOST'] = $options['db-host'];
    }
    if (isset($options['db-port'])) {
        $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_PORT'] = $options['db-port'];
    }
    if (isset($options['db-database'])) {
        $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_DATABASE'] = $options['db-database'];
    }
    if (isset($options['db-username'])) {
        $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_USERNAME'] = $options['db-username'];
    }
    if (isset($options['db-password'])) {
        $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_PASSWORD'] = $options['db-password'];
    }
    
    echo "✅ Using custom database configuration\n\n";
} else {
    echo "ℹ️  Using default configuration (you'll need to customize database settings)\n\n";
}

// Display current configuration
echo "📋 Current Configuration:\n";
echo "  Server Path: {$serverPath}\n";
echo "  Database Host: " . $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_HOST'] . "\n";
echo "  Database Port: " . $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_PORT'] . "\n";
echo "  Database Name: " . $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_DATABASE'] . "\n";
echo "  Database User: " . $defaultConfig['mcpServers']['laravel-telescope']['env']['DB_USERNAME'] . "\n";
echo "  Database Pass: " . (empty($defaultConfig['mcpServers']['laravel-telescope']['env']['DB_PASSWORD']) ? '(empty)' : '***') . "\n\n";

// Generate JSON configuration
$jsonConfig = json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Base64 encode the configuration
$encodedConfig = base64_encode($jsonConfig);

// Generate the deeplink
$deeplink = "cursor://settings/mcp?config=" . urlencode($encodedConfig);

echo "🔗 Generated Cursor Deeplink:\n";
echo "============================\n";
echo $deeplink . "\n\n";

echo "📝 How to use:\n";
echo "1. Copy the deeplink above\n";
echo "2. Paste it in your browser or click it directly\n";
echo "3. Cursor will open and prompt to add the MCP server\n";
echo "4. Accept the configuration to add Laravel Telescope MCP server\n\n";

echo "⚙️  Custom Usage Examples:\n";
echo "php generate-cursor-deeplink.php --db-database=my_laravel_app --db-username=myuser --db-password=mypass\n";
echo "php generate-cursor-deeplink.php --db-host=localhost --db-database=telescope_db\n\n";

echo "📄 Raw JSON Configuration:\n";
echo "==========================\n";
echo $jsonConfig . "\n\n";

echo "💾 Tip: You can also manually copy the JSON above and paste it into Cursor's MCP settings.\n"; 