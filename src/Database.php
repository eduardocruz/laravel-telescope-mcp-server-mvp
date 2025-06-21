<?php

declare(strict_types=1);

namespace TelescopeMcp;

use PDO;
use PDOException;
use Exception;

/**
 * Simple Database connection class for Laravel Telescope
 * 
 * Handles basic PDO connection and queries to telescope_entries table
 */
class Database
{
    private ?PDO $pdo = null;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $this->port = $_ENV['DB_PORT'] ?? '3306';
        $this->database = $_ENV['DB_DATABASE'] ?? 'laravel_telescope';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
    }

    /**
     * Establish database connection
     * 
     * @throws Exception If connection fails
     */
    public function connect(): void
    {
        if ($this->pdo !== null) {
            return; // Already connected
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get recent telescope entries
     * 
     * @param int $limit Number of entries to retrieve
     * @return array Array of telescope entries
     * @throws Exception If query fails
     */
    public function getRecentEntries(int $limit = 10): array
    {
        $this->connect();

        try {
            $stmt = $this->pdo->prepare("
                SELECT uuid, type, family_hash, content, created_at 
                FROM telescope_entries 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch telescope entries: " . $e->getMessage());
        }
    }

    /**
     * Get recent HTTP request entries from telescope
     * 
     * @param int $limit Number of request entries to retrieve
     * @return array Array of request entries with parsed data
     * @throws Exception If query fails
     */
    public function getRecentRequests(int $limit = 10): array
    {
        $this->connect();

        try {
            $stmt = $this->pdo->prepare("
                SELECT uuid, content, created_at 
                FROM telescope_entries 
                WHERE type = 'request'
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $entries = $stmt->fetchAll();
            $requests = [];
            
            foreach ($entries as $entry) {
                $content = json_decode($entry['content'], true);
                
                if (is_array($content)) {
                    $requests[] = [
                        'uuid' => $entry['uuid'],
                        'method' => $content['method'] ?? 'UNKNOWN',
                        'uri' => $content['uri'] ?? 'UNKNOWN',
                        'status' => $content['response_status'] ?? $content['status'] ?? null,
                        'created_at' => $entry['created_at'],
                        'ip_address' => $content['ip_address'] ?? null,
                        'user_id' => $content['user_id'] ?? null,
                        'duration' => $content['duration'] ?? null
                    ];
                }
            }
            
            return $requests;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch request entries: " . $e->getMessage());
        }
    }

    /**
     * Test if telescope_entries table exists and is accessible
     * 
     * @return array Status information about the table
     */
    public function testTableAccess(): array
    {
        try {
            $this->connect();
            
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'telescope_entries'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                return [
                    'success' => false,
                    'message' => 'telescope_entries table not found',
                    'count' => 0
                ];
            }
            
            // Count entries
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM telescope_entries");
            $result = $stmt->fetch();
            $count = (int) $result['count'];
            
            // Get most recent entry date
            $stmt = $this->pdo->query("SELECT MAX(created_at) as latest FROM telescope_entries");
            $result = $stmt->fetch();
            $latest = $result['latest'];
            
            return [
                'success' => true,
                'message' => 'Table access successful',
                'count' => $count,
                'latest_entry' => $latest,
                'connection_info' => "{$this->host}:{$this->port}/{$this->database}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * Get database connection info
     * 
     * @return array Connection details
     */
    public function getConnectionInfo(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'connected' => $this->pdo !== null
        ];
    }

    /**
     * Close database connection
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }
} 