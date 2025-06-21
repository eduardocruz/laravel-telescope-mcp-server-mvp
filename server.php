<?php

declare(strict_types=1);

// Try to find the autoloader in different locations
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',           // When running from project root
    __DIR__ . '/../../../autoload.php',        // When installed as dependency
    __DIR__ . '/../../vendor/autoload.php',    // Alternative location
];

$autoloaderFound = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    fwrite(STDERR, "Error: Could not find autoloader. Please run 'composer install'.\n");
    exit(1);
}

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use PhpMcp\Server\Server;
use PhpMcp\Server\Defaults\StreamLogger;
use TelescopeMcp\TelescopeTools;

// Create the tools class
require_once __DIR__ . '/src/TelescopeTools.php';
require_once __DIR__ . '/src/Database.php';

// Set up logger
$logger = new StreamLogger(__DIR__.'/mcp.log', 'info');

// Create MCP server with correct v1.0 syntax
$server = Server::make()
    ->withBasePath(__DIR__)
    ->withLogger($logger)
    ->withTool([TelescopeTools::class, 'helloWorld'], 'hello_world', 'A simple hello world test')
    ->withTool([TelescopeTools::class, 'telescopeStatus'], 'telescope_status', 'Check Laravel Telescope database connection and status')
    ->withTool([TelescopeTools::class, 'getRecentEntries'], 'get_recent_entries', 'Get recent telescope entries for testing')
    ->withTool([TelescopeTools::class, 'telescopeRecentRequests'], 'telescope_recent_requests', 'List recent HTTP requests from Laravel Telescope')
    ->withTool([TelescopeTools::class, 'telescopeSlowQueries'], 'telescope_slow_queries', 'Find slow database queries from Laravel Telescope');

// Start the server with stdio transport
$exitCode = $server->run('stdio');

exit($exitCode); 