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
     * Get slow database queries from telescope
     * 
     * @param int $threshold Minimum duration in milliseconds
     * @param int $limit Number of queries to retrieve
     * @return array Array of slow query entries with parsed data
     * @throws Exception If query fails
     */
    public function getSlowQueries(int $threshold = 100, int $limit = 10): array
    {
        $this->connect();

        try {
            $stmt = $this->pdo->prepare("
                SELECT uuid, content, created_at 
                FROM telescope_entries 
                WHERE type = 'query'
                AND CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2)) > ?
                ORDER BY CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2)) DESC
                LIMIT ?
            ");
            
            $stmt->bindValue(1, $threshold, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $entries = $stmt->fetchAll();
            $queries = [];
            
            foreach ($entries as $entry) {
                $content = json_decode($entry['content'], true);
                
                if (is_array($content)) {
                    $queries[] = [
                        'uuid' => $entry['uuid'],
                        'sql' => $content['sql'] ?? 'UNKNOWN',
                        'duration' => $content['time'] ?? $content['duration'] ?? null,
                        'created_at' => $entry['created_at'],
                        'connection_name' => $content['connection_name'] ?? null,
                        'bindings' => $content['bindings'] ?? []
                    ];
                }
            }
            
            return $queries;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch slow queries: " . $e->getMessage());
        }
    }

    /**
     * Get comprehensive performance data from multiple telescope entry types
     * 
     * @param int $hours Time window for analysis in hours
     * @param int $thresholdSlow Slow request threshold in milliseconds
     * @param float $thresholdError Error rate threshold percentage
     * @return array Comprehensive performance data
     * @throws Exception If query fails
     */
    public function getPerformanceData(int $hours = 24, int $thresholdSlow = 1000, float $thresholdError = 5.0): array
    {
        $this->connect();

        try {
            $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // HTTP Requests Performance
            $requestsData = $this->getRequestsPerformance($cutoffTime, $thresholdSlow);
            
            // Database Performance
            $databaseData = $this->getDatabasePerformance($cutoffTime);
            
            // Queue Performance
            $queueData = $this->getQueuePerformance($cutoffTime);
            
            // Cache Performance
            $cacheData = $this->getCachePerformance($cutoffTime);
            
            // Error Summary
            $errorData = $this->getErrorSummary($cutoffTime);
            
            return [
                'time_window' => $hours,
                'cutoff_time' => $cutoffTime,
                'requests' => $requestsData,
                'database' => $databaseData,
                'queue' => $queueData,
                'cache' => $cacheData,
                'errors' => $errorData,
                'thresholds' => [
                    'slow_request' => $thresholdSlow,
                    'error_rate' => $thresholdError
                ]
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch performance data: " . $e->getMessage());
        }
    }

    /**
     * Get HTTP requests performance data
     */
    private function getRequestsPerformance(string $cutoffTime, int $thresholdSlow): array
    {
        // Total requests and success rate
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_requests,
                AVG(CAST(JSON_EXTRACT(content, '$.duration') AS DECIMAL(10,2))) as avg_duration,
                COUNT(CASE WHEN CAST(JSON_EXTRACT(content, '$.response_status') AS UNSIGNED) BETWEEN 200 AND 299 THEN 1 END) as success_count,
                COUNT(CASE WHEN CAST(JSON_EXTRACT(content, '$.duration') AS DECIMAL(10,2)) > ? THEN 1 END) as slow_count
            FROM telescope_entries 
            WHERE type = 'request' 
            AND created_at >= ?
        ");
        $stmt->execute([$thresholdSlow, $cutoffTime]);
        $basic = $stmt->fetch();

        // Peak hour analysis
        $stmt = $this->pdo->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as count
            FROM telescope_entries 
            WHERE type = 'request' 
            AND created_at >= ?
            GROUP BY HOUR(created_at)
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->execute([$cutoffTime]);
        $peakHour = $stmt->fetch();

        return [
            'total' => (int)$basic['total_requests'],
            'success_count' => (int)$basic['success_count'],
            'success_rate' => $basic['total_requests'] > 0 ? round(($basic['success_count'] / $basic['total_requests']) * 100, 1) : 0,
            'avg_duration' => round((float)$basic['avg_duration'], 1),
            'slow_count' => (int)$basic['slow_count'],
            'peak_hour' => $peakHour ? sprintf("%02d:00-%02d:00", $peakHour['hour'], $peakHour['hour'] + 1) : 'N/A',
            'peak_requests' => $peakHour ? (int)$peakHour['count'] : 0
        ];
    }

    /**
     * Get database performance data
     */
    private function getDatabasePerformance(string $cutoffTime): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_queries,
                AVG(CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2))) as avg_time,
                COUNT(CASE WHEN CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2)) > 100 THEN 1 END) as slow_count
            FROM telescope_entries 
            WHERE type = 'query' 
            AND created_at >= ?
        ");
        $stmt->execute([$cutoffTime]);
        $basic = $stmt->fetch();

        // Most expensive query
        $stmt = $this->pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(content, '$.sql')) as query_sql,
                CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2)) as duration
            FROM telescope_entries 
            WHERE type = 'query' 
            AND created_at >= ?
            ORDER BY CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2)) DESC
            LIMIT 1
        ");
        $stmt->execute([$cutoffTime]);
        $expensive = $stmt->fetch();

        return [
            'total_queries' => (int)$basic['total_queries'],
            'avg_time' => round((float)$basic['avg_time'], 1),
            'slow_count' => (int)$basic['slow_count'],
            'most_expensive' => $expensive ? [
                'sql' => substr($expensive['query_sql'], 0, 50) . '...',
                'duration' => round((float)$expensive['duration'], 1)
            ] : null
        ];
    }

    /**
     * Get queue performance data
     */
    private function getQueuePerformance(string $cutoffTime): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_jobs,
                AVG(CAST(JSON_EXTRACT(content, '$.time') AS DECIMAL(10,2))) as avg_time,
                COUNT(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(content, '$.status')) = 'processed' THEN 1 END) as success_count
            FROM telescope_entries 
            WHERE type = 'job' 
            AND created_at >= ?
        ");
        $stmt->execute([$cutoffTime]);
        $basic = $stmt->fetch();

        // Failed jobs
        $stmt = $this->pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(content, '$.name')) as job_name
            FROM telescope_entries 
            WHERE type = 'job' 
            AND JSON_UNQUOTE(JSON_EXTRACT(content, '$.status')) = 'failed'
            AND created_at >= ?
            LIMIT 5
        ");
        $stmt->execute([$cutoffTime]);
        $failed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'total_jobs' => (int)$basic['total_jobs'],
            'success_count' => (int)$basic['success_count'],
            'success_rate' => $basic['total_jobs'] > 0 ? round(($basic['success_count'] / $basic['total_jobs']) * 100, 1) : 0,
            'avg_processing_time' => round((float)$basic['avg_time'] / 1000, 1), // Convert to seconds
            'failed_jobs' => $failed
        ];
    }

    /**
     * Get cache performance data
     */
    private function getCachePerformance(string $cutoffTime): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_operations,
                COUNT(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(content, '$.type')) = 'hit' THEN 1 END) as hits,
                COUNT(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(content, '$.type')) = 'miss' THEN 1 END) as misses
            FROM telescope_entries 
            WHERE type = 'cache' 
            AND created_at >= ?
        ");
        $stmt->execute([$cutoffTime]);
        $basic = $stmt->fetch();

        // Most accessed cache keys
        $stmt = $this->pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(content, '$.key')) as cache_key,
                COUNT(*) as access_count
            FROM telescope_entries 
            WHERE type = 'cache' 
            AND created_at >= ?
            GROUP BY JSON_UNQUOTE(JSON_EXTRACT(content, '$.key'))
            ORDER BY access_count DESC
            LIMIT 1
        ");
        $stmt->execute([$cutoffTime]);
        $mostAccessed = $stmt->fetch();

        return [
            'total_operations' => (int)$basic['total_operations'],
            'hits' => (int)$basic['hits'],
            'misses' => (int)$basic['misses'],
            'hit_rate' => $basic['total_operations'] > 0 ? round(($basic['hits'] / $basic['total_operations']) * 100, 1) : 0,
            'miss_rate' => $basic['total_operations'] > 0 ? round(($basic['misses'] / $basic['total_operations']) * 100, 1) : 0,
            'most_accessed' => $mostAccessed ? [
                'key' => $mostAccessed['cache_key'],
                'count' => (int)$mostAccessed['access_count']
            ] : null
        ];
    }

    /**
     * Get error summary data
     */
    private function getErrorSummary(string $cutoffTime): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_exceptions,
                COUNT(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(content, '$.level')) = 'critical' THEN 1 END) as critical_count,
                COUNT(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(content, '$.level')) = 'warning' THEN 1 END) as warning_count,
                COUNT(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(content, '$.level')) = 'info' THEN 1 END) as info_count
            FROM telescope_entries 
            WHERE type = 'exception' 
            AND created_at >= ?
        ");
        $stmt->execute([$cutoffTime]);
        $basic = $stmt->fetch();

        // Recent critical exceptions
        $stmt = $this->pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(content, '$.class')) as exception_class
            FROM telescope_entries 
            WHERE type = 'exception' 
            AND JSON_UNQUOTE(JSON_EXTRACT(content, '$.level')) = 'critical'
            AND created_at >= ?
            LIMIT 3
        ");
        $stmt->execute([$cutoffTime]);
        $critical = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'total_exceptions' => (int)$basic['total_exceptions'],
            'critical' => (int)$basic['critical_count'],
            'warnings' => (int)$basic['warning_count'],
            'info' => (int)$basic['info_count'],
            'critical_exceptions' => $critical
        ];
    }

    /**
     * Get exceptions from telescope with filtering and grouping options
     * 
     * @param int $limit Number of exceptions to retrieve
     * @param string|null $level Filter by error level (error, warning, critical, etc.)
     * @param string|null $since Time period filter (1h, 24h, 7d)
     * @param string|null $groupBy Group exceptions by 'type', 'file', or 'message'
     * @return array Array of exception entries with parsed data
     * @throws Exception If query fails
     */
    public function getExceptions(int $limit = 10, ?string $level = null, ?string $since = null, ?string $groupBy = null): array
    {
        $this->connect();

        try {
            // Build the base query
            $whereConditions = ["type = 'exception'"];
            $params = [];

            // Add time filter if specified
            if ($since) {
                $hours = $this->parseSinceToHours($since);
                $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
                $params[] = $hours;
            }

            // Add level filter if specified
            if ($level) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.level')) = ?";
                $params[] = $level;
            }

            $whereClause = implode(' AND ', $whereConditions);

            if ($groupBy) {
                return $this->getGroupedExceptions($whereClause, $params, $limit, $groupBy);
            } else {
                return $this->getIndividualExceptions($whereClause, $params, $limit);
            }

        } catch (PDOException $e) {
            throw new Exception("Failed to fetch exceptions: " . $e->getMessage());
        }
    }

    /**
     * Get individual exceptions without grouping
     */
    private function getIndividualExceptions(string $whereClause, array $params, int $limit): array
    {
        $stmt = $this->pdo->prepare("
            SELECT uuid, content, created_at 
            FROM telescope_entries 
            WHERE {$whereClause}
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        
        $entries = $stmt->fetchAll();
        $exceptions = [];
        
        foreach ($entries as $entry) {
            $content = json_decode($entry['content'], true);
            
            if (is_array($content)) {
                $exceptions[] = [
                    'uuid' => $entry['uuid'],
                    'created_at' => $entry['created_at'],
                    'class' => $content['class'] ?? 'Unknown',
                    'message' => $content['message'] ?? 'No message',
                    'file' => $content['file'] ?? 'Unknown file',
                    'line' => $content['line'] ?? 0,
                    'level' => $content['level'] ?? 'error',
                    'trace' => $content['trace'] ?? [],
                    'context' => $content['context'] ?? []
                ];
            }
        }
        
        return $exceptions;
    }

    /**
     * Get exceptions grouped by specified field
     */
    private function getGroupedExceptions(string $whereClause, array $params, int $limit, string $groupBy): array
    {
        // Determine the JSON path for grouping
        $groupField = match($groupBy) {
            'type' => '$.class',
            'file' => '$.file', 
            'message' => '$.message',
            default => '$.class'
        };

        $stmt = $this->pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(content, '{$groupField}')) as group_key,
                COUNT(*) as count,
                MAX(created_at) as latest_occurrence,
                ANY_VALUE(JSON_UNQUOTE(JSON_EXTRACT(content, '$.class'))) as class,
                ANY_VALUE(JSON_UNQUOTE(JSON_EXTRACT(content, '$.message'))) as message,
                ANY_VALUE(JSON_UNQUOTE(JSON_EXTRACT(content, '$.file'))) as file,
                ANY_VALUE(JSON_EXTRACT(content, '$.line')) as line,
                ANY_VALUE(JSON_UNQUOTE(JSON_EXTRACT(content, '$.level'))) as level,
                ANY_VALUE(uuid) as uuid
            FROM telescope_entries 
            WHERE {$whereClause}
            GROUP BY JSON_UNQUOTE(JSON_EXTRACT(content, '{$groupField}'))
            ORDER BY count DESC, latest_occurrence DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        
        $entries = $stmt->fetchAll();
        $exceptions = [];
        
        foreach ($entries as $entry) {
            $exceptions[] = [
                'uuid' => $entry['uuid'],
                'created_at' => $entry['latest_occurrence'],
                'class' => $entry['class'] ?? 'Unknown',
                'message' => $entry['message'] ?? 'No message',
                'file' => $entry['file'] ?? 'Unknown file',
                'line' => (int)($entry['line'] ?? 0),
                'level' => $entry['level'] ?? 'error',
                'count' => (int)$entry['count'],
                'group_key' => $entry['group_key']
            ];
        }
        
        return $exceptions;
    }

    /**
     * Parse time period string to hours
     */
    private function parseSinceToHours(string $since): int
    {
        return match($since) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 168, // 7 * 24
            '1d' => 24,
            '12h' => 12,
            '3d' => 72,
            default => 24 // Default to 24h if unrecognized
        };
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
     * Get job entries from telescope with filtering options
     * 
     * @param int $limit Number of jobs to retrieve
     * @param string|null $status Filter by job status (pending, processing, completed, failed, cancelled)
     * @param string|null $queue Filter by specific queue name
     * @param int $hours Time window for analysis in hours
     * @return array Array of job entries with parsed data
     * @throws Exception If query fails
     */
    public function getJobs(int $limit = 10, ?string $status = null, ?string $queue = null, int $hours = 24): array
    {
        $this->connect();

        try {
            // Build the base query
            $whereConditions = ["type = 'job'"];
            $params = [];

            // Add time filter
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
            $params[] = $hours;

            // Add status filter if specified
            if ($status) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.status')) = ?";
                $params[] = $status;
            }

            // Add queue filter if specified
            if ($queue) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.queue')) = ?";
                $params[] = $queue;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $stmt = $this->pdo->prepare("
                SELECT uuid, content, created_at 
                FROM telescope_entries 
                WHERE {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $params[] = $limit;
            $stmt->execute($params);
            
            $entries = $stmt->fetchAll();
            $jobs = [];
            
            foreach ($entries as $entry) {
                $content = json_decode($entry['content'], true);
                
                if (is_array($content)) {
                    // Parse job name from the class name
                    $jobClass = $content['name'] ?? $content['displayName'] ?? 'Unknown Job';
                    $jobName = $this->extractJobName($jobClass);
                    
                    $jobs[] = [
                        'uuid' => $entry['uuid'],
                        'created_at' => $entry['created_at'],
                        'job_name' => $jobName,
                        'job_class' => $jobClass,
                        'queue' => $content['queue'] ?? 'default',
                        'status' => $content['status'] ?? 'unknown',
                        'connection' => $content['connection'] ?? null,
                        'tries' => $content['tries'] ?? null,
                        'max_tries' => $content['maxTries'] ?? null,
                        'timeout' => $content['timeout'] ?? null,
                        'failed_at' => $content['failed_at'] ?? null,
                        'exception' => $content['exception'] ?? null,
                        'data' => $content['data'] ?? []
                    ];
                }
            }
            
            return $jobs;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch job entries: " . $e->getMessage());
        }
    }

    /**
     * Extract a clean job name from the job class
     */
    private function extractJobName(string $jobClass): string
    {
        // If it's a full class name, get just the class name
        if (str_contains($jobClass, '\\')) {
            $parts = explode('\\', $jobClass);
            return end($parts);
        }
        
        return $jobClass;
    }

    /**
     * Get cache entries from telescope with filtering options
     * 
     * @param int $limit Number of cache operations to retrieve
     * @param string|null $operation Filter by cache operation (hit, miss, write, forget, flush)
     * @param int $hours Time window for analysis in hours
     * @return array Array of cache entries with parsed data
     * @throws Exception If query fails
     */
    public function getCacheEntries(int $limit = 50, ?string $operation = null, int $hours = 24): array
    {
        $this->connect();

        try {
            // Build the base query
            $whereConditions = ["type = 'cache'"];
            $params = [];

            // Add time filter
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
            $params[] = $hours;

            // Add operation filter if specified
            if ($operation) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.type')) = ?";
                $params[] = $operation;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $stmt = $this->pdo->prepare("
                SELECT uuid, content, created_at 
                FROM telescope_entries 
                WHERE {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $params[] = $limit;
            $stmt->execute($params);
            
            $entries = $stmt->fetchAll();
            $cacheOps = [];
            
            foreach ($entries as $entry) {
                $content = json_decode($entry['content'], true);
                
                if (is_array($content)) {
                    $cacheOps[] = [
                        'uuid' => $entry['uuid'],
                        'created_at' => $entry['created_at'],
                        'operation' => $content['type'] ?? 'unknown',
                        'key' => $content['key'] ?? 'unknown',
                        'value' => $content['value'] ?? null,
                        'result' => $content['result'] ?? null,
                        'expiration' => $content['expiration'] ?? null,
                        'tags' => $content['tags'] ?? [],
                        'hit' => ($content['type'] ?? '') === 'hit',
                        'miss' => ($content['type'] ?? '') === 'miss'
                    ];
                }
            }
            
            return $cacheOps;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch cache entries: " . $e->getMessage());
        }
    }

    /**
     * Get cache statistics for summary analysis
     * 
     * @param int $hours Time window for analysis in hours
     * @return array Cache statistics including hit/miss ratios
     * @throws Exception If query fails
     */
    public function getCacheStats(int $hours = 24): array
    {
        $this->connect();

        try {
            $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

            // Get basic cache operation counts
            $stmt = $this->pdo->prepare("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(content, '$.type')) as operation_type,
                    COUNT(*) as count
                FROM telescope_entries 
                WHERE type = 'cache' 
                AND created_at >= ?
                GROUP BY JSON_UNQUOTE(JSON_EXTRACT(content, '$.type'))
            ");
            $stmt->execute([$cutoffTime]);
            $operationCounts = $stmt->fetchAll();

            // Get most frequent cache keys
            $stmt = $this->pdo->prepare("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(content, '$.key')) as cache_key,
                    COUNT(*) as frequency
                FROM telescope_entries 
                WHERE type = 'cache' 
                AND created_at >= ?
                GROUP BY JSON_UNQUOTE(JSON_EXTRACT(content, '$.key'))
                ORDER BY frequency DESC
                LIMIT 10
            ");
            $stmt->execute([$cutoffTime]);
            $topKeys = $stmt->fetchAll();

            // Calculate totals
            $totalOps = 0;
            $hits = 0;
            $misses = 0;
            $writes = 0;
            $deletes = 0;

            foreach ($operationCounts as $op) {
                $count = (int)$op['count'];
                $totalOps += $count;
                
                switch ($op['operation_type']) {
                    case 'hit':
                        $hits = $count;
                        break;
                    case 'miss':
                        $misses = $count;
                        break;
                    case 'write':
                    case 'put':
                    case 'set':
                        $writes = $count;
                        break;
                    case 'forget':
                    case 'delete':
                    case 'flush':
                        $deletes = $count;
                        break;
                }
            }

            return [
                'total_operations' => $totalOps,
                'hits' => $hits,
                'misses' => $misses,
                'writes' => $writes,
                'deletes' => $deletes,
                'hit_rate' => $totalOps > 0 ? round(($hits / $totalOps) * 100, 1) : 0,
                'miss_rate' => $totalOps > 0 ? round(($misses / $totalOps) * 100, 1) : 0,
                'top_keys' => $topKeys,
                'operation_counts' => $operationCounts
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch cache statistics: " . $e->getMessage());
        }
    }

    /**
     * Get user activity entries from telescope with filtering options
     * 
     * @param int|null $userId Specific user ID to track
     * @param int $limit Number of activities to retrieve
     * @param int $hours Time window for analysis in hours
     * @param bool $includeAnonymous Include non-authenticated requests
     * @param bool $suspiciousOnly Show only potentially suspicious activity
     * @return array Array of user activity entries with parsed data
     * @throws Exception If query fails
     */
    public function getUserActivity(?int $userId = null, int $limit = 20, int $hours = 24, bool $includeAnonymous = false, bool $suspiciousOnly = false): array
    {
        $this->connect();

        try {
            // Build the base query
            $whereConditions = ["type = 'request'"];
            $params = [];

            // Add time filter
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
            $params[] = $hours;

            // Add user filter
            if ($userId !== null) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.user_id')) = ?";
                $params[] = (string)$userId;
            } elseif (!$includeAnonymous) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.user_id')) IS NOT NULL";
            }

            $whereClause = implode(' AND ', $whereConditions);

            $stmt = $this->pdo->prepare("
                SELECT uuid, content, created_at 
                FROM telescope_entries 
                WHERE {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $params[] = $limit;
            $stmt->execute($params);
            
            $entries = $stmt->fetchAll();
            $activities = [];
            
            foreach ($entries as $entry) {
                $content = json_decode($entry['content'], true);
                
                if (is_array($content)) {
                    $activity = [
                        'uuid' => $entry['uuid'],
                        'created_at' => $entry['created_at'],
                        'user_id' => $content['user_id'] ?? null,
                        'method' => $content['method'] ?? 'UNKNOWN',
                        'uri' => $content['uri'] ?? 'UNKNOWN',
                        'status' => $content['response_status'] ?? $content['status'] ?? null,
                        'duration' => $content['duration'] ?? null,
                        'ip_address' => $content['ip_address'] ?? null,
                        'user_agent' => $content['user_agent'] ?? null,
                        'session_id' => $content['session_id'] ?? null,
                        'payload' => $content['payload'] ?? [],
                        'response' => $content['response'] ?? []
                    ];
                    
                    // Add suspicious activity detection
                    $activity['suspicious'] = $this->detectSuspiciousActivity($activity, $content);
                    
                    // Filter suspicious only if requested
                    if (!$suspiciousOnly || $activity['suspicious']) {
                        $activities[] = $activity;
                    }
                }
            }
            
            return $activities;
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch user activity: " . $e->getMessage());
        }
    }

    /**
     * Get user activity statistics for summary analysis
     * 
     * @param int|null $userId Specific user ID to analyze
     * @param int $hours Time window for analysis in hours
     * @return array User activity statistics
     * @throws Exception If query fails
     */
    public function getUserActivityStats(?int $userId = null, int $hours = 24): array
    {
        $this->connect();

        try {
            $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // Build base conditions
            $whereConditions = ["type = 'request'", "created_at >= ?"];
            $params = [$cutoffTime];
            
            if ($userId !== null) {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.user_id')) = ?";
                $params[] = (string)$userId;
            } else {
                $whereConditions[] = "JSON_UNQUOTE(JSON_EXTRACT(content, '$.user_id')) IS NOT NULL";
            }
            
            $whereClause = implode(' AND ', $whereConditions);

            // Get basic activity counts
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(content, '$.ip_address'))) as unique_ips,
                    COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(content, '$.user_id'))) as unique_users,
                    AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS UNSIGNED)) as avg_duration,
                    MIN(created_at) as first_activity,
                    MAX(created_at) as last_activity
                FROM telescope_entries 
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $basicStats = $stmt->fetch();

            // Get most visited URIs
            $stmt = $this->pdo->prepare("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(content, '$.uri')) as uri,
                    COUNT(*) as visits
                FROM telescope_entries 
                WHERE {$whereClause}
                GROUP BY JSON_UNQUOTE(JSON_EXTRACT(content, '$.uri'))
                ORDER BY visits DESC
                LIMIT 5
            ");
            $stmt->execute($params);
            $topUris = $stmt->fetchAll();

            // Get error rate
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as error_count
                FROM telescope_entries 
                WHERE {$whereClause}
                AND CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.response_status')) AS UNSIGNED) >= 400
            ");
            $stmt->execute($params);
            $errorStats = $stmt->fetch();

            $totalRequests = (int)$basicStats['total_requests'];
            $errorCount = (int)$errorStats['error_count'];
            
            return [
                'total_requests' => $totalRequests,
                'unique_ips' => (int)$basicStats['unique_ips'],
                'unique_users' => (int)$basicStats['unique_users'],
                'avg_duration' => round((float)$basicStats['avg_duration'], 2),
                'error_count' => $errorCount,
                'error_rate' => $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 1) : 0,
                'first_activity' => $basicStats['first_activity'],
                'last_activity' => $basicStats['last_activity'],
                'top_uris' => $topUris,
                'session_duration' => $this->calculateSessionDuration($basicStats['first_activity'], $basicStats['last_activity'])
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch user activity statistics: " . $e->getMessage());
        }
    }

    /**
     * Detect suspicious activity patterns
     */
    private function detectSuspiciousActivity(array $activity, array $content): bool
    {
        // Check for failed authentication attempts
        if (($activity['status'] ?? 0) >= 400 && ($activity['status'] ?? 0) < 500) {
            return true;
        }
        
        // Check for admin/sensitive endpoints
        $sensitivePatterns = ['/admin', '/api/admin', '/dashboard/admin', '/user/delete', '/config'];
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($activity['uri'] ?? '', $pattern)) {
                return true;
            }
        }
        
        // Check for unusual response times (> 5 seconds)
        if (($activity['duration'] ?? 0) > 5000) {
            return true;
        }
        
        return false;
    }

    /**
     * Calculate session duration between first and last activity
     */
    private function calculateSessionDuration(?string $firstActivity, ?string $lastActivity): array
    {
        if (!$firstActivity || !$lastActivity) {
            return ['hours' => 0, 'minutes' => 0, 'seconds' => 0, 'formatted' => '0s'];
        }
        
        $start = new \DateTime($firstActivity);
        $end = new \DateTime($lastActivity);
        $duration = $end->diff($start);
        
        return [
            'hours' => $duration->h + ($duration->days * 24),
            'minutes' => $duration->i,
            'seconds' => $duration->s,
            'formatted' => $this->formatSessionDuration($duration)
        ];
    }

    /**
     * Format session duration for display
     */
    private function formatSessionDuration(\DateInterval $duration): string
    {
        $parts = [];
        
        $totalHours = $duration->h + ($duration->days * 24);
        if ($totalHours > 0) {
            $parts[] = $totalHours . 'h';
        }
        if ($duration->i > 0) {
            $parts[] = $duration->i . 'm';
        }
        if ($duration->s > 0 || empty($parts)) {
            $parts[] = $duration->s . 's';
        }
        
        return implode(' ', $parts);
    }

    /**
     * Close database connection
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }
} 