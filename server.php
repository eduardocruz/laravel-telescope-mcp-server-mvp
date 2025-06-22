<?php

declare(strict_types=1);

// Try to find the autoloader in different locations
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',           // When running from project root
    __DIR__ . '/../../autoload.php',           // When installed as dependency (vendor/eduardocruz/package-name/)
    __DIR__ . '/../../../autoload.php',        // Alternative dependency location
    __DIR__ . '/../../vendor/autoload.php',    // Another alternative location
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
use PhpMcp\Server\Transports\StdioServerTransport;
use TelescopeMcp\TelescopeTools;
use Psr\Log\AbstractLogger;

// Simple logger that writes to stderr
class StderrLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        fwrite(STDERR, sprintf("MCP: [%s][%s] %s %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, empty($context) ? '' : json_encode($context)));
    }
}

// Create the tools class
require_once __DIR__ . '/src/TelescopeTools.php';
require_once __DIR__ . '/src/Database.php';

try {
    $logger = new StderrLogger();
    $logger->info('Starting Laravel Telescope MCP Server...');

    // Create MCP server with the available methods
    $server = Server::make()
        ->withLogger($logger)
        ->withTool([TelescopeTools::class, 'helloWorld'], 'hello_world', 'A simple hello world test')
        ->withTool([TelescopeTools::class, 'telescopeStatus'], 'telescope_status', 'Check Laravel Telescope database connection and status')
        ->withTool([TelescopeTools::class, 'getRecentEntries'], 'get_recent_entries', 'Get recent telescope entries for testing')
        ->withTool([TelescopeTools::class, 'telescopeRecentRequests'], 'telescope_recent_requests', 'List recent HTTP requests from Laravel Telescope')
        ->withTool([TelescopeTools::class, 'telescopeSlowQueries'], 'telescope_slow_queries', 'Find slow database queries from Laravel Telescope')
        ->withTool([TelescopeTools::class, 'telescopePerformanceSummary'], 'telescope_performance_summary', 'Get comprehensive application performance dashboard from Laravel Telescope')
        ->withTool([TelescopeTools::class, 'telescopeExceptions'], 'telescope_exceptions', 'Track and analyze application exceptions and errors from Laravel Telescope')
        ->withTool([TelescopeTools::class, 'telescopeJobs'], 'telescope_jobs', 'Monitor job queue performance and status from Laravel Telescope')
        ->withTool([TelescopeTools::class, 'telescopeCacheStats'], 'telescope_cache_stats', 'Analyze cache performance and statistics from Laravel Telescope')
        ->withTool([TelescopeTools::class, 'telescopeUserActivity'], 'telescope_user_activity', 'Track user activity and behavior patterns from Laravel Telescope');

    // Start the server with stdio transport
    $exitCode = $server->run('stdio');
    exit($exitCode);

} catch (\Throwable $e) {
    fwrite(STDERR, "[MCP SERVER CRITICAL ERROR]\n".$e."\n");
    exit(1);
} 