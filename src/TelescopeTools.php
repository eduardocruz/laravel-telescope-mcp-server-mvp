<?php

declare(strict_types=1);

namespace TelescopeMcp;

use Exception;

/**
 * Laravel Telescope MCP Tools
 * 
 * Basic tools for interacting with Laravel Telescope data
 */
class TelescopeTools
{
    private Database $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * A simple hello world tool to test MCP connection
     * 
     * @param string $name The name to greet
     * @return array MCP response format
     */
    public function helloWorld(string $name = 'World'): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => "Hello, {$name}! Laravel Telescope MCP Server is running."
                ]
            ]
        ];
    }

    /**
     * Check if we can connect to the Telescope database
     * 
     * @return array MCP response format
     */
    public function telescopeStatus(): array
    {
        try {
            $status = $this->database->testTableAccess();
            
            if ($status['success']) {
                $text = "✅ Database connection successful!\n" .
                       "📊 Found {$status['count']} telescope entries\n" .
                       "🔗 Connected to: {$status['connection_info']}";
                
                if ($status['latest_entry']) {
                    $text .= "\n📅 Latest entry: {$status['latest_entry']}";
                }
                
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text
                        ]
                    ]
                ];
            } else {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "❌ Database issue: " . $status['message']
                        ]
                    ]
                ];
            }
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Database connection failed: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Get recent telescope entries (for testing database functionality)
     * 
     * @param int $limit Number of entries to retrieve
     * @return array MCP response format
     */
    public function getRecentEntries(int $limit = 5): array
    {
        try {
            $entries = $this->database->getRecentEntries($limit);
            
            if (empty($entries)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📭 No telescope entries found in the database."
                        ]
                    ]
                ];
            }
            
            $text = "📊 Recent Telescope Entries (showing {$limit}):\n\n";
            
            foreach ($entries as $entry) {
                $text .= "🔸 UUID: " . substr($entry['uuid'], 0, 8) . "...\n";
                $text .= "   Type: {$entry['type']}\n";
                $text .= "   Created: {$entry['created_at']}\n";
                
                // Show a snippet of content if available
                if (!empty($entry['content'])) {
                    $content = json_decode($entry['content'], true);
                    if (is_array($content) && isset($content['method'])) {
                        $text .= "   Method: {$content['method']}\n";
                    }
                    if (is_array($content) && isset($content['uri'])) {
                        $text .= "   URI: {$content['uri']}\n";
                    }
                }
                
                $text .= "\n";
            }
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch entries: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * List recent HTTP requests from Laravel Telescope
     * 
     * @param int $limit Number of requests to retrieve (default: 10)
     * @return array MCP response format
     */
    public function telescopeRecentRequests(int $limit = 10): array
    {
        try {
            $requests = $this->database->getRecentRequests($limit);
            
            if (empty($requests)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📭 No HTTP requests found in telescope entries."
                        ]
                    ]
                ];
            }
            
            $text = "🌐 Recent HTTP Requests (showing " . count($requests) . " of {$limit}):\n\n";
            
            foreach ($requests as $request) {
                $statusIcon = $this->getStatusIcon($request['status']);
                $text .= "{$statusIcon} {$request['method']} {$request['uri']}\n";
                $text .= "   Status: " . ($request['status'] ?? 'Unknown') . "\n";
                $text .= "   Time: {$request['created_at']}\n";
                
                if ($request['duration']) {
                    $text .= "   Duration: {$request['duration']}ms\n";
                }
                
                if ($request['user_id']) {
                    $text .= "   User ID: {$request['user_id']}\n";
                }
                
                if ($request['ip_address']) {
                    $text .= "   IP: {$request['ip_address']}\n";
                }
                
                $text .= "   UUID: " . substr($request['uuid'], 0, 8) . "...\n\n";
            }
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch requests: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Find slow database queries from Laravel Telescope
     * 
     * @param int $threshold Minimum duration in milliseconds (default: 100)
     * @param int $limit Number of queries to retrieve (default: 10)
     * @return array MCP response format
     */
    public function telescopeSlowQueries(int $threshold = 100, int $limit = 10): array
    {
        try {
            $queries = $this->database->getSlowQueries($threshold, $limit);
            
            if (empty($queries)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📊 No slow queries found above {$threshold}ms threshold."
                        ]
                    ]
                ];
            }
            
            $text = "🐌 Slow Database Queries (>{$threshold}ms, showing " . count($queries) . " of {$limit}):\n\n";
            
            foreach ($queries as $query) {
                $duration = $query['duration'] ?? 'Unknown';
                $text .= "⏱️ Duration: {$duration}ms\n";
                $text .= "📅 Time: {$query['created_at']}\n";
                
                if ($query['connection_name']) {
                    $text .= "🔗 Connection: {$query['connection_name']}\n";
                }
                
                // Format SQL query for better readability
                $sql = $query['sql'];
                if (strlen($sql) > 200) {
                    $sql = substr($sql, 0, 200) . '...';
                }
                $text .= "💾 SQL: " . trim($sql) . "\n";
                
                // Show bindings if available
                if (!empty($query['bindings'])) {
                    $bindings = is_array($query['bindings']) ? 
                        implode(', ', array_slice($query['bindings'], 0, 5)) : 
                        $query['bindings'];
                    $text .= "🔗 Bindings: " . $bindings . "\n";
                }
                
                $text .= "🆔 UUID: " . substr($query['uuid'], 0, 8) . "...\n\n";
            }
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch slow queries: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Get comprehensive application performance dashboard
     * 
     * @param int $hours Time window for analysis in hours (default: 24)
     * @param bool $includeDetails Include detailed breakdowns per category (default: false)
     * @param int $thresholdSlow Slow request threshold in milliseconds (default: 1000)
     * @param float $thresholdError Error rate threshold percentage (default: 5.0)
     * @return array MCP response format
     */
    public function telescopePerformanceSummary(
        int $hours = 24, 
        bool $includeDetails = false, 
        int $thresholdSlow = 1000, 
        float $thresholdError = 5.0
    ): array {
        try {
            $performanceData = $this->database->getPerformanceData($hours, $thresholdSlow, $thresholdError);
            
            $text = "📊 Application Performance Dashboard (last {$hours}h):\n\n";
            
            // HTTP Requests Section
            $requests = $performanceData['requests'];
            $text .= "🌐 HTTP Requests:\n";
            $text .= "   Total: " . number_format($requests['total']) . " requests\n";
            $text .= "   Success Rate: {$requests['success_rate']}% (" . number_format($requests['success_count']) . "/" . number_format($requests['total']) . ")\n";
            $text .= "   Avg Response: {$requests['avg_duration']}ms\n";
            $text .= "   Slow Requests: " . number_format($requests['slow_count']) . " (>{$thresholdSlow}ms)\n";
            if ($requests['peak_hour'] !== 'N/A') {
                $text .= "   Peak Hour: {$requests['peak_hour']} (" . number_format($requests['peak_requests']) . " requests)\n";
            }
            $text .= "\n";
            
            // Database Performance Section
            $database = $performanceData['database'];
            $text .= "🗄️ Database Performance:\n";
            $text .= "   Total Queries: " . number_format($database['total_queries']) . "\n";
            $text .= "   Avg Query Time: {$database['avg_time']}ms\n";
            $text .= "   Slow Queries: " . number_format($database['slow_count']) . " (>100ms)\n";
            if ($database['most_expensive']) {
                $text .= "   Most Expensive: {$database['most_expensive']['sql']} ({$database['most_expensive']['duration']}ms)\n";
            }
            $text .= "\n";
            
            // Queue Status Section
            $queue = $performanceData['queue'];
            if ($queue['total_jobs'] > 0) {
                $text .= "⚡ Queue Status:\n";
                $text .= "   Jobs Processed: " . number_format($queue['total_jobs']) . "\n";
                $text .= "   Success Rate: {$queue['success_rate']}% (" . number_format($queue['success_count']) . "/" . number_format($queue['total_jobs']) . ")\n";
                $text .= "   Avg Processing: {$queue['avg_processing_time']}s\n";
                if (!empty($queue['failed_jobs'])) {
                    $text .= "   Failed Jobs: " . implode(', ', array_slice($queue['failed_jobs'], 0, 3)) . "\n";
                }
                $text .= "\n";
            }
            
            // Cache Performance Section
            $cache = $performanceData['cache'];
            if ($cache['total_operations'] > 0) {
                $text .= "🔄 Cache Performance:\n";
                $text .= "   Operations: " . number_format($cache['total_operations']) . "\n";
                $text .= "   Hit Rate: {$cache['hit_rate']}% (" . number_format($cache['hits']) . "/" . number_format($cache['total_operations']) . ")\n";
                $text .= "   Miss Rate: {$cache['miss_rate']}% (" . number_format($cache['misses']) . "/" . number_format($cache['total_operations']) . ")\n";
                if ($cache['most_accessed']) {
                    $text .= "   Most Accessed: {$cache['most_accessed']['key']} (" . number_format($cache['most_accessed']['count']) . " hits)\n";
                }
                $text .= "\n";
            }
            
            // Error Summary Section
            $errors = $performanceData['errors'];
            if ($errors['total_exceptions'] > 0) {
                $text .= "🚨 Error Summary:\n";
                $text .= "   Exceptions: " . number_format($errors['total_exceptions']) . " total\n";
                if ($errors['critical'] > 0) {
                    $text .= "   Critical: " . number_format($errors['critical']) . "\n";
                    if (!empty($errors['critical_exceptions'])) {
                        $text .= "     Types: " . implode(', ', array_slice($errors['critical_exceptions'], 0, 2)) . "\n";
                    }
                }
                if ($errors['warnings'] > 0) {
                    $text .= "   Warnings: " . number_format($errors['warnings']) . "\n";
                }
                if ($errors['info'] > 0) {
                    $text .= "   Info: " . number_format($errors['info']) . "\n";
                }
                $text .= "\n";
            }
            
            // Performance Trends Section
            $text .= "📈 Performance Trends:\n";
            
            // Calculate error rate
            $errorRate = $requests['total'] > 0 ? round((($requests['total'] - $requests['success_count']) / $requests['total']) * 100, 1) : 0;
            
            // Response time trend (simplified - would need historical data for real trends)
            if ($requests['avg_duration'] > 500) {
                $text .= "   🔴 Response time elevated ({$requests['avg_duration']}ms avg)\n";
            } elseif ($requests['avg_duration'] > 200) {
                $text .= "   🟡 Response time moderate ({$requests['avg_duration']}ms avg)\n";
            } else {
                $text .= "   🟢 Response time good ({$requests['avg_duration']}ms avg)\n";
            }
            
            // Error rate assessment
            if ($errorRate > $thresholdError) {
                $text .= "   🔴 Error rate elevated ({$errorRate}%)\n";
            } elseif ($errorRate > 1.0) {
                $text .= "   🟡 Error rate within range ({$errorRate}%)\n";
            } else {
                $text .= "   🟢 Error rate low ({$errorRate}%)\n";
            }
            
            // Cache performance assessment
            if ($cache['total_operations'] > 0) {
                if ($cache['hit_rate'] > 80) {
                    $text .= "   🟢 Cache hit rate excellent ({$cache['hit_rate']}%)\n";
                } elseif ($cache['hit_rate'] > 60) {
                    $text .= "   🟡 Cache hit rate moderate ({$cache['hit_rate']}%)\n";
                } else {
                    $text .= "   🔴 Cache hit rate low ({$cache['hit_rate']}%)\n";
                }
            }
            
            // Queue processing assessment
            if ($queue['total_jobs'] > 0) {
                if ($queue['success_rate'] > 95) {
                    $text .= "   🟢 Queue processing stable ({$queue['success_rate']}% success)\n";
                } elseif ($queue['success_rate'] > 85) {
                    $text .= "   🟡 Queue processing moderate ({$queue['success_rate']}% success)\n";
                } else {
                    $text .= "   🔴 Queue processing issues ({$queue['success_rate']}% success)\n";
                }
            }
            
            // Add detailed breakdown if requested
            if ($includeDetails) {
                $text .= "\n" . $this->formatDetailedBreakdown($performanceData);
            }
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to generate performance dashboard: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Format detailed performance breakdown
     * 
     * @param array $performanceData Raw performance data
     * @return string Formatted detailed breakdown
     */
    private function formatDetailedBreakdown(array $performanceData): string
    {
        $text = "📋 Detailed Breakdown:\n\n";
        
        $text .= "⏱️ Time Analysis:\n";
        $text .= "   Analysis Period: {$performanceData['time_window']} hours\n";
        $text .= "   Data From: {$performanceData['cutoff_time']}\n";
        $text .= "   Thresholds: Slow Request >{$performanceData['thresholds']['slow_request']}ms, Error Rate >{$performanceData['thresholds']['error_rate']}%\n\n";
        
        $requests = $performanceData['requests'];
        $text .= "🌐 Request Details:\n";
        $text .= "   Success Requests: " . number_format($requests['success_count']) . "\n";
        $text .= "   Failed Requests: " . number_format($requests['total'] - $requests['success_count']) . "\n";
        $text .= "   Slow Requests: " . number_format($requests['slow_count']) . "\n";
        if ($requests['total'] > 0) {
            $text .= "   Slow Request %: " . round(($requests['slow_count'] / $requests['total']) * 100, 1) . "%\n";
        }
        $text .= "\n";
        
        $database = $performanceData['database'];
        $text .= "🗄️ Database Details:\n";
        $text .= "   Total Queries: " . number_format($database['total_queries']) . "\n";
        $text .= "   Fast Queries: " . number_format($database['total_queries'] - $database['slow_count']) . "\n";
        $text .= "   Slow Queries: " . number_format($database['slow_count']) . "\n";
        if ($database['total_queries'] > 0) {
            $text .= "   Slow Query %: " . round(($database['slow_count'] / $database['total_queries']) * 100, 1) . "%\n";
        }
        
        return $text;
    }

    /**
     * Get application exceptions from Laravel Telescope
     * 
     * @param int $limit Number of exceptions to retrieve (default: 10)
     * @param string|null $level Filter by error level (error, warning, critical, etc.)
     * @param string|null $since Time period filter (1h, 24h, 7d)
     * @param string|null $groupBy Group exceptions by 'type', 'file', or 'message'
     * @return array MCP response format
     */
    public function telescopeExceptions(
        int $limit = 10, 
        ?string $level = null, 
        ?string $since = null, 
        ?string $groupBy = null
    ): array {
        try {
            $exceptions = $this->database->getExceptions($limit, $level, $since, $groupBy);
            
            if (empty($exceptions)) {
                $filterInfo = $this->buildFilterInfo($level, $since, $groupBy);
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📭 No exceptions found{$filterInfo}."
                        ]
                    ]
                ];
            }
            
            $text = $this->formatExceptionsOutput($exceptions, $limit, $level, $since, $groupBy);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch exceptions: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Monitor job queue performance and status from Laravel Telescope
     * 
     * @param int $limit Number of jobs to retrieve (default: 10)
     * @param string|null $status Filter by job status (pending, processing, completed, failed, cancelled)
     * @param string|null $queue Filter by specific queue name
     * @param int $hours Time window for analysis in hours (default: 24)
     * @return array MCP response format
     */
    public function telescopeJobs(
        int $limit = 10, 
        ?string $status = null, 
        ?string $queue = null, 
        int $hours = 24
    ): array {
        try {
            $jobs = $this->database->getJobs($limit, $status, $queue, $hours);
            
            if (empty($jobs)) {
                $filterInfo = $this->buildJobFilterInfo($status, $queue, $hours);
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📭 No jobs found{$filterInfo}."
                        ]
                    ]
                ];
            }
            
            $text = $this->formatJobsOutput($jobs, $limit, $status, $queue, $hours);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch jobs: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Format jobs output for display
     */
    private function formatJobsOutput(array $jobs, int $limit, ?string $status, ?string $queue, int $hours): string
    {
        $filterInfo = $this->buildJobFilterInfo($status, $queue, $hours);
        $count = count($jobs);
        
        $text = "⚡ Job Queue Status{$filterInfo} (showing {$count} of {$limit}):\n\n";
        
        foreach ($jobs as $job) {
            $statusIcon = $this->getJobStatusIcon($job['status']);
            $text .= "{$statusIcon} {$job['job_name']} - " . ucfirst($job['status']) . "\n";
            $text .= "   Queue: {$job['queue']}\n";
            
            // Show duration or processing time
            if ($job['status'] === 'completed' || $job['status'] === 'failed') {
                // For completed/failed jobs, we might not have duration in the telescope data
                // but we can show when it was processed
                $text .= "   Processed: {$job['created_at']}\n";
            } elseif ($job['status'] === 'processing') {
                $text .= "   Started: {$job['created_at']}\n";
                // Calculate how long it's been processing
                $startTime = new \DateTime($job['created_at']);
                $now = new \DateTime();
                $duration = $now->diff($startTime);
                $text .= "   Duration: " . $this->formatDuration($duration) . " (ongoing)\n";
            } else {
                $text .= "   Time: {$job['created_at']}\n";
            }
            
            // Show attempts for failed jobs
            if ($job['status'] === 'failed' && $job['tries'] !== null && $job['max_tries'] !== null) {
                $text .= "   Attempts: {$job['tries']}/{$job['max_tries']}\n";
            }
            
            // Show error for failed jobs
            if ($job['status'] === 'failed' && !empty($job['exception'])) {
                $errorMessage = is_array($job['exception']) 
                    ? ($job['exception']['message'] ?? 'Unknown error')
                    : (string) $job['exception'];
                $text .= "   Error: " . substr($errorMessage, 0, 100) . (strlen($errorMessage) > 100 ? '...' : '') . "\n";
            }
            
            $text .= "   UUID: " . substr($job['uuid'], 0, 8) . "...\n\n";
        }
        
        return $text;
    }

    /**
     * Build filter information string for job output
     */
    private function buildJobFilterInfo(?string $status, ?string $queue, int $hours): string
    {
        $filters = [];
        
        if ($status) {
            $filters[] = "status: {$status}";
        }
        
        if ($queue) {
            $filters[] = "queue: {$queue}";
        }
        
        if ($hours !== 24) {
            $filters[] = "last {$hours}h";
        }
        
        return !empty($filters) ? " (" . implode(', ', $filters) . ")" : "";
    }

    /**
     * Get appropriate icon for job status
     */
    private function getJobStatusIcon(string $status): string
    {
        return match(strtolower($status)) {
            'completed' => '✅',
            'failed' => '❌',
            'processing' => '🔄',
            'pending' => '⏳',
            'cancelled' => '🚫',
            'retry' => '🔁',
            default => '❓'
        };
    }

    /**
     * Format duration object to readable string
     */
    private function formatDuration(\DateInterval $duration): string
    {
        $parts = [];
        
        if ($duration->h > 0) {
            $parts[] = $duration->h . 'h';
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
     * Analyze cache performance and statistics from Laravel Telescope
     * 
     * @param int $limit Number of cache operations to analyze (default: 50)
     * @param string|null $operation Filter by cache operation (hit, miss, write, forget, flush)
     * @param int $hours Time window for analysis in hours (default: 24)
     * @param bool $showSummary Include hit/miss ratio summary (default: true)
     * @return array MCP response format
     */
    public function telescopeCacheStats(
        int $limit = 50, 
        ?string $operation = null, 
        int $hours = 24, 
        bool $showSummary = true
    ): array {
        try {
            $cacheEntries = $this->database->getCacheEntries($limit, $operation, $hours);
            $cacheStats = $showSummary ? $this->database->getCacheStats($hours) : null;
            
            if (empty($cacheEntries) && (!$cacheStats || $cacheStats['total_operations'] === 0)) {
                $filterInfo = $this->buildCacheFilterInfo($operation, $hours);
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📭 No cache operations found{$filterInfo}."
                        ]
                    ]
                ];
            }
            
            $text = $this->formatCacheStatsOutput($cacheEntries, $cacheStats, $limit, $operation, $hours, $showSummary);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch cache statistics: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Format cache statistics output for display
     */
    private function formatCacheStatsOutput(array $cacheEntries, ?array $cacheStats, int $limit, ?string $operation, int $hours, bool $showSummary): string
    {
        $filterInfo = $this->buildCacheFilterInfo($operation, $hours);
        $count = count($cacheEntries);
        
        $text = "🔄 Cache Performance Analysis{$filterInfo}:\n\n";
        
        // Add summary if requested and available
        if ($showSummary && $cacheStats && $cacheStats['total_operations'] > 0) {
            $text .= "📊 Summary:\n";
            $text .= "   Total Operations: " . number_format($cacheStats['total_operations']) . "\n";
            
            if ($cacheStats['hits'] > 0 || $cacheStats['misses'] > 0) {
                $totalHitMiss = $cacheStats['hits'] + $cacheStats['misses'];
                $text .= "   Hit Rate: {$cacheStats['hit_rate']}% ({$cacheStats['hits']}/{$totalHitMiss} operations)\n";
                $text .= "   Miss Rate: {$cacheStats['miss_rate']}% ({$cacheStats['misses']}/{$totalHitMiss} operations)\n";
            }
            
            if ($cacheStats['writes'] > 0) {
                $text .= "   Writes: " . number_format($cacheStats['writes']) . "\n";
            }
            
            if ($cacheStats['deletes'] > 0) {
                $text .= "   Deletes: " . number_format($cacheStats['deletes']) . "\n";
            }
            
            // Show most active cache key
            if (!empty($cacheStats['top_keys'])) {
                $topKey = $cacheStats['top_keys'][0];
                $text .= "   Most Active Key: {$topKey['cache_key']} ({$topKey['frequency']} operations)\n";
            }
            
            $text .= "\n";
        }
        
        if (!empty($cacheEntries)) {
            $text .= "Recent Operations (showing {$count}):\n\n";
            
            foreach ($cacheEntries as $entry) {
                $operationIcon = $this->getCacheOperationIcon($entry['operation']);
                $operationName = strtoupper($entry['operation']);
                $text .= "{$operationIcon} {$operationName}: {$entry['key']}\n";
                
                // Show value size if available
                if (!empty($entry['value'])) {
                    $valueSize = $this->formatValueSize($entry['value']);
                    if ($valueSize) {
                        $text .= "   Value Size: {$valueSize}\n";
                    }
                }
                
                // Show expiration for writes
                if ($entry['operation'] === 'write' && !empty($entry['expiration'])) {
                    $text .= "   TTL: " . $this->formatExpiration($entry['expiration']) . "\n";
                }
                
                // Show result for hits/misses
                if (in_array($entry['operation'], ['hit', 'miss']) && !empty($entry['result'])) {
                    $text .= "   Result: " . (is_string($entry['result']) ? $entry['result'] : 'Data retrieved') . "\n";
                }
                
                // Show tags if available
                if (!empty($entry['tags']) && is_array($entry['tags'])) {
                    $text .= "   Tags: " . implode(', ', $entry['tags']) . "\n";
                }
                
                $text .= "   Time: {$entry['created_at']}\n";
                $text .= "   UUID: " . substr($entry['uuid'], 0, 8) . "...\n\n";
            }
        }
        
        return $text;
    }

    /**
     * Build filter information string for cache output
     */
    private function buildCacheFilterInfo(?string $operation, int $hours): string
    {
        $filters = [];
        
        if ($operation) {
            $filters[] = "operation: {$operation}";
        }
        
        if ($hours !== 24) {
            $filters[] = "last {$hours}h";
        } else {
            $filters[] = "last 24h";
        }
        
        return !empty($filters) ? " (" . implode(', ', $filters) . ")" : "";
    }

    /**
     * Get appropriate icon for cache operation
     */
    private function getCacheOperationIcon(string $operation): string
    {
        return match(strtolower($operation)) {
            'hit' => '✅',
            'miss' => '❌',
            'write', 'put', 'set' => '💾',
            'forget', 'delete' => '🗑️',
            'flush' => '🧹',
            'read' => '📖',
            default => '❓'
        };
    }

    /**
     * Format value size for display
     */
    private function formatValueSize($value): ?string
    {
        if (is_string($value)) {
            $bytes = strlen($value);
        } elseif (is_array($value) || is_object($value)) {
            $bytes = strlen(json_encode($value));
        } else {
            return null;
        }
        
        if ($bytes < 1024) {
            return $bytes . 'B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . 'KB';
        } else {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        }
    }

    /**
     * Format expiration time for display
     */
    private function formatExpiration($expiration): string
    {
        if (is_numeric($expiration)) {
            // If it's a timestamp
            if ($expiration > time()) {
                $seconds = $expiration - time();
            } else {
                $seconds = (int)$expiration;
            }
        } elseif (is_string($expiration)) {
            // Try to parse as seconds
            $seconds = (int)$expiration;
        } else {
            return 'Unknown';
        }
        
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . 'm';
        } elseif ($seconds < 86400) {
            return round($seconds / 3600) . 'h';
        } else {
            return round($seconds / 86400) . 'd';
        }
    }

    /**
     * Track user activity and behavior patterns from Laravel Telescope
     * 
     * @param int|null $userId Specific user ID to track
     * @param int $limit Number of activities to retrieve (default: 20)
     * @param int $hours Time window for analysis in hours (default: 24)
     * @param bool $includeAnonymous Include non-authenticated requests (default: false)
     * @param bool $suspiciousOnly Show only potentially suspicious activity (default: false)
     * @return array MCP response format
     */
    public function telescopeUserActivity(
        ?int $userId = null, 
        int $limit = 20, 
        int $hours = 24, 
        bool $includeAnonymous = false, 
        bool $suspiciousOnly = false
    ): array {
        try {
            $activities = $this->database->getUserActivity($userId, $limit, $hours, $includeAnonymous, $suspiciousOnly);
            $stats = $this->database->getUserActivityStats($userId, $hours);
            
            if (empty($activities) && $stats['total_requests'] === 0) {
                $filterInfo = $this->buildUserActivityFilterInfo($userId, $hours, $includeAnonymous, $suspiciousOnly);
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📭 No user activity found{$filterInfo}."
                        ]
                    ]
                ];
            }
            
            $text = $this->formatUserActivityOutput($activities, $stats, $userId, $limit, $hours, $includeAnonymous, $suspiciousOnly);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch user activity: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Format user activity output for display
     */
    private function formatUserActivityOutput(array $activities, array $stats, ?int $userId, int $limit, int $hours, bool $includeAnonymous, bool $suspiciousOnly): string
    {
        $filterInfo = $this->buildUserActivityFilterInfo($userId, $hours, $includeAnonymous, $suspiciousOnly);
        $count = count($activities);
        
        $text = "👤 User Activity Monitor{$filterInfo}:\n\n";
        
        // Add user summary if tracking specific user
        if ($userId !== null && $stats['total_requests'] > 0) {
            $text .= "🔍 User #{$userId}:\n";
            $text .= "   Total Requests: " . number_format($stats['total_requests']) . "\n";
            $text .= "   Unique IPs: {$stats['unique_ips']}\n";
            $text .= "   Session Duration: {$stats['session_duration']['formatted']}\n";
            $text .= "   Last Active: {$stats['last_activity']}\n";
            
            if ($stats['error_rate'] > 0) {
                $text .= "   Error Rate: {$stats['error_rate']}% ({$stats['error_count']}/{$stats['total_requests']} requests)\n";
            }
            
            $text .= "\n";
        } elseif ($userId === null && $stats['total_requests'] > 0) {
            // General statistics for all users
            $text .= "📊 Activity Summary:\n";
            $text .= "   Total Requests: " . number_format($stats['total_requests']) . "\n";
            $text .= "   Active Users: {$stats['unique_users']}\n";
            $text .= "   Unique IPs: {$stats['unique_ips']}\n";
            $text .= "   Average Response: {$stats['avg_duration']}ms\n";
            
            if ($stats['error_rate'] > 0) {
                $text .= "   Error Rate: {$stats['error_rate']}% ({$stats['error_count']}/{$stats['total_requests']} requests)\n";
            }
            
            $text .= "\n";
        }
        
        if (!empty($activities)) {
            $text .= "Recent Activity (showing {$count}):\n\n";
            
            foreach ($activities as $activity) {
                $statusIcon = $this->getActivityStatusIcon($activity);
                $methodUri = "{$activity['method']} {$activity['uri']}";
                
                // Add suspicious flag if detected
                if ($activity['suspicious']) {
                    $methodUri .= " (SUSPICIOUS)";
                }
                
                $text .= "{$statusIcon} {$methodUri}\n";
                
                // Show user ID for multi-user view
                if ($userId === null && $activity['user_id']) {
                    $text .= "   User: #{$activity['user_id']}\n";
                }
                
                // Show IP address
                if ($activity['ip_address']) {
                    $text .= "   IP: {$activity['ip_address']}\n";
                }
                
                // Show status and duration
                if ($activity['status']) {
                    $text .= "   Status: {$activity['status']}\n";
                }
                
                if ($activity['duration']) {
                    $text .= "   Duration: {$activity['duration']}ms\n";
                }
                
                // Show suspicious details
                if ($activity['suspicious']) {
                    $suspiciousReasons = $this->getSuspiciousReasons($activity);
                    if (!empty($suspiciousReasons)) {
                        $text .= "   ⚠️ Flags: " . implode(', ', $suspiciousReasons) . "\n";
                    }
                }
                
                $text .= "   Time: {$activity['created_at']}\n";
                $text .= "   UUID: " . substr($activity['uuid'], 0, 8) . "...\n\n";
            }
        }
        
        // Add top URIs summary if available
        if (!empty($stats['top_uris'])) {
            $text .= "📈 Most Visited:\n";
            foreach (array_slice($stats['top_uris'], 0, 3) as $uri) {
                $text .= "   {$uri['uri']} ({$uri['visits']} times)\n";
            }
        }
        
        return $text;
    }

    /**
     * Build filter information string for user activity output
     */
    private function buildUserActivityFilterInfo(?int $userId, int $hours, bool $includeAnonymous, bool $suspiciousOnly): string
    {
        $filters = [];
        
        if ($userId !== null) {
            $filters[] = "user: #{$userId}";
        }
        
        if ($suspiciousOnly) {
            $filters[] = "suspicious only";
        }
        
        if ($includeAnonymous) {
            $filters[] = "including anonymous";
        }
        
        if ($hours !== 24) {
            $filters[] = "last {$hours}h";
        } else {
            $filters[] = "last 24h";
        }
        
        return !empty($filters) ? " (" . implode(', ', $filters) . ")" : "";
    }

    /**
     * Get appropriate icon for activity status
     */
    private function getActivityStatusIcon(array $activity): string
    {
        if ($activity['suspicious']) {
            return '⚠️';
        }
        
        $status = $activity['status'] ?? 0;
        
        if ($status >= 200 && $status < 300) {
            return '✅'; // Success
        } elseif ($status >= 300 && $status < 400) {
            return '🔄'; // Redirect
        } elseif ($status >= 400 && $status < 500) {
            return '❌'; // Client Error
        } elseif ($status >= 500) {
            return '💥'; // Server Error
        }
        
        return '📊'; // Unknown/Info
    }

    /**
     * Get reasons why activity is marked as suspicious
     */
    private function getSuspiciousReasons(array $activity): array
    {
        $reasons = [];
        
        $status = $activity['status'] ?? 0;
        if ($status >= 400 && $status < 500) {
            $reasons[] = 'Failed request';
        }
        
        // Check for admin/sensitive endpoints
        $sensitivePatterns = ['/admin', '/api/admin', '/dashboard/admin', '/user/delete', '/config'];
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($activity['uri'] ?? '', $pattern)) {
                $reasons[] = 'Sensitive endpoint';
                break;
            }
        }
        
        // Check for unusual response times
        if (($activity['duration'] ?? 0) > 5000) {
            $reasons[] = 'Slow response';
        }
        
        return $reasons;
    }

    /**
     * Format exceptions output based on grouping preference
     */
    private function formatExceptionsOutput(array $exceptions, int $limit, ?string $level, ?string $since, ?string $groupBy): string
    {
        $filterInfo = $this->buildFilterInfo($level, $since, $groupBy);
        $count = count($exceptions);
        
        if ($groupBy) {
            $text = "🚨 Grouped Application Exceptions{$filterInfo} (showing {$count}):\n\n";
            
            foreach ($exceptions as $exception) {
                $levelIcon = $this->getExceptionLevelIcon($exception['level']);
                $text .= "{$levelIcon} {$exception['class']}: {$exception['message']}\n";
                $text .= "   File: {$exception['file']}";
                if ($exception['line'] > 0) {
                    $text .= ":{$exception['line']}";
                }
                $text .= "\n";
                $text .= "   Latest: {$exception['created_at']}\n";
                $text .= "   Count: " . number_format($exception['count']) . " occurrences\n";
                $text .= "   UUID: " . substr($exception['uuid'], 0, 8) . "...\n\n";
            }
        } else {
            $text = "🚨 Recent Application Exceptions{$filterInfo} (showing {$count}):\n\n";
            
            foreach ($exceptions as $exception) {
                $levelIcon = $this->getExceptionLevelIcon($exception['level']);
                $text .= "{$levelIcon} {$exception['class']}: {$exception['message']}\n";
                $text .= "   File: {$exception['file']}";
                if ($exception['line'] > 0) {
                    $text .= ":{$exception['line']}";
                }
                $text .= "\n";
                $text .= "   Time: {$exception['created_at']}\n";
                $text .= "   Level: {$exception['level']}\n";
                $text .= "   UUID: " . substr($exception['uuid'], 0, 8) . "...\n";
                
                // Add stack trace preview if available
                if (!empty($exception['trace']) && is_array($exception['trace'])) {
                    $tracePreview = $this->formatStackTracePreview($exception['trace']);
                    if ($tracePreview) {
                        $text .= "   Stack: {$tracePreview}\n";
                    }
                }
                
                $text .= "\n";
            }
        }
        
        return $text;
    }

    /**
     * Build filter information string for output
     */
    private function buildFilterInfo(?string $level, ?string $since, ?string $groupBy): string
    {
        $filters = [];
        
        if ($level) {
            $filters[] = "level: {$level}";
        }
        
        if ($since) {
            $filters[] = "since: {$since}";
        }
        
        if ($groupBy) {
            $filters[] = "grouped by: {$groupBy}";
        }
        
        return !empty($filters) ? " (" . implode(', ', $filters) . ")" : "";
    }

    /**
     * Get appropriate icon for exception level
     */
    private function getExceptionLevelIcon(string $level): string
    {
        return match(strtolower($level)) {
            'critical', 'fatal', 'emergency' => '🔴',
            'error' => '❌',
            'warning', 'warn' => '⚠️',
            'notice', 'info' => 'ℹ️',
            'debug' => '🐛',
            default => '❓'
        };
    }

    /**
     * Format stack trace preview (first few frames)
     */
    private function formatStackTracePreview(array $trace): ?string
    {
        if (empty($trace)) {
            return null;
        }

        // Get first meaningful stack frame
        foreach (array_slice($trace, 0, 3) as $frame) {
            if (is_array($frame) && isset($frame['file']) && isset($frame['line'])) {
                $file = basename($frame['file']);
                return "{$file}:{$frame['line']}";
            } elseif (is_string($frame) && strpos($frame, '/') !== false) {
                // Handle string format stack traces
                if (preg_match('/([^\/]+\.php):(\d+)/', $frame, $matches)) {
                    return "{$matches[1]}:{$matches[2]}";
                }
            }
        }

        return null;
    }

    /**
     * Get status icon based on HTTP status code
     * 
     * @param int|null $status HTTP status code
     * @return string Status icon
     */
    private function getStatusIcon(?int $status): string
    {
        if ($status === null) {
            return '❓';
        }
        
        if ($status >= 200 && $status < 300) {
            return '✅'; // Success
        } elseif ($status >= 300 && $status < 400) {
            return '🔄'; // Redirect
        } elseif ($status >= 400 && $status < 500) {
            return '⚠️'; // Client Error
        } elseif ($status >= 500) {
            return '❌'; // Server Error
        }
        
        return '❓'; // Unknown
    }

    /**
     * Get detailed information for a specific exception from Laravel Telescope
     * 
     * @param string $exceptionId The UUID of the exception to analyze
     * @param bool $includeContext Include request context when exception occurred (default: true)
     * @param bool $includeRelated Include related entries from same request (default: true)
     * @return array MCP response format
     */
    public function telescopeExceptionDetail(
        string $exceptionId,
        bool $includeContext = true,
        bool $includeRelated = true
    ): array {
        try {
            $exceptionDetail = $this->database->getExceptionDetail($exceptionId, $includeContext, $includeRelated);
            
            if (empty($exceptionDetail)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "❌ Exception not found with ID: " . substr($exceptionId, 0, 8) . "..."
                        ]
                    ]
                ];
            }
            
            $text = $this->formatExceptionDetailOutput($exceptionDetail, $includeContext, $includeRelated);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to fetch exception details: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Identify recurring exception patterns and trends from Laravel Telescope
     * 
     * @param string $timeWindow Time period for analysis (1h, 24h, 7d, 30d) (default: 24h)
     * @param int $minOccurrences Minimum occurrences to be considered a pattern (default: 2)
     * @param string $groupBy Group patterns by 'class', 'file', or 'line' (default: class)
     * @return array MCP response format
     */
    public function telescopeExceptionPatterns(
        string $timeWindow = '24h',
        int $minOccurrences = 2,
        string $groupBy = 'class'
    ): array {
        try {
            $patterns = $this->database->getExceptionPatterns($timeWindow, $minOccurrences, $groupBy);
            
            if (empty($patterns)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "📊 No recurring exception patterns found in the last {$timeWindow} (minimum {$minOccurrences} occurrences)."
                        ]
                    ]
                ];
            }
            
            $text = $this->formatExceptionPatternsOutput($patterns, $timeWindow, $minOccurrences, $groupBy);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "❌ Failed to analyze exception patterns: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Format detailed exception output for display
     */
    private function formatExceptionDetailOutput(array $detail, bool $includeContext, bool $includeRelated): string
    {
        $exception = $detail['exception'];
        $levelIcon = $this->getExceptionLevelIcon($exception['level']);
        
        $text = "🔍 Exception Details:\n\n";
        $text .= "{$levelIcon} {$exception['class']}: {$exception['message']}\n\n";
        
        // Basic exception info
        $text .= "📋 Basic Information:\n";
        $text .= "   UUID: {$exception['uuid']}\n";
        $text .= "   Level: {$exception['level']}\n";
        $text .= "   File: {$exception['file']}";
        if ($exception['line'] > 0) {
            $text .= ":{$exception['line']}";
        }
        $text .= "\n";
        $text .= "   Time: {$exception['created_at']}\n\n";
        
        // Full stack trace
        if (!empty($exception['trace'])) {
            $text .= "📚 Stack Trace:\n";
            $text .= $this->formatFullStackTrace($exception['trace']);
            $text .= "\n";
        }
        
        // Request context
        if ($includeContext && !empty($detail['context'])) {
            $context = $detail['context'];
            $text .= "🌐 Request Context:\n";
            $text .= "   Method: {$context['method']} {$context['uri']}\n";
            $text .= "   Status: {$context['status']}\n";
            $text .= "   Duration: {$context['duration']}ms\n";
            if (!empty($context['user_id'])) {
                $text .= "   User: #{$context['user_id']}\n";
            }
            $text .= "   IP: {$context['ip_address']}\n\n";
        }
        
        // Related entries
        if ($includeRelated && !empty($detail['related'])) {
            $relatedCount = count($detail['related']);
            $text .= "🔗 Related Entries ({$relatedCount} total):\n";
            
            foreach (array_slice($detail['related'], 0, 5) as $related) {
                $typeIcon = $this->getEntryTypeIcon($related['type']);
                $text .= "   {$typeIcon} {$related['type']}";
                if (!empty($related['summary'])) {
                    $text .= ": {$related['summary']}";
                }
                $text .= " ({$related['created_at']})\n";
            }
            
            if (count($detail['related']) > 5) {
                $text .= "   ... and " . (count($detail['related']) - 5) . " more entries\n";
            }
        }
        
        return $text;
    }

    /**
     * Format exception patterns output for display
     */
    private function formatExceptionPatternsOutput(array $patterns, string $timeWindow, int $minOccurrences, string $groupBy): string
    {
        $count = count($patterns);
        $text = "📊 Exception Patterns Analysis (last {$timeWindow}, grouped by {$groupBy}):\n\n";
        $text .= "Found {$count} recurring pattern(s) with {$minOccurrences}+ occurrences:\n\n";
        
        foreach ($patterns as $pattern) {
            $levelIcon = $this->getExceptionLevelIcon($pattern['level']);
            $priorityIcon = $this->getPatternPriorityIcon($pattern['count'], $pattern['trend']);
            
            $text .= "{$priorityIcon} {$levelIcon} {$pattern['identifier']}\n";
            $text .= "   Occurrences: " . number_format($pattern['count']) . " times\n";
            $text .= "   Trend: {$pattern['trend']}\n";
            $text .= "   First Seen: {$pattern['first_seen']}\n";
            $text .= "   Last Seen: {$pattern['last_seen']}\n";
            
            if (!empty($pattern['message'])) {
                $text .= "   Message: " . substr($pattern['message'], 0, 80);
                if (strlen($pattern['message']) > 80) {
                    $text .= "...";
                }
                $text .= "\n";
            }
            
            if (!empty($pattern['files'])) {
                $text .= "   Affected Files: " . implode(', ', array_slice($pattern['files'], 0, 3));
                if (count($pattern['files']) > 3) {
                    $text .= " (+" . (count($pattern['files']) - 3) . " more)";
                }
                $text .= "\n";
            }
            
            $text .= "\n";
        }
        
        // Add summary insights
        $totalOccurrences = array_sum(array_column($patterns, 'count'));
        $criticalPatterns = array_filter($patterns, fn($p) => in_array(strtolower($p['level']), ['critical', 'fatal', 'emergency']));
        
        $text .= "💡 Summary Insights:\n";
        $text .= "   Total Exception Occurrences: " . number_format($totalOccurrences) . "\n";
        $text .= "   Critical Patterns: " . count($criticalPatterns) . "\n";
        $text .= "   Most Frequent: {$patterns[0]['identifier']} (" . number_format($patterns[0]['count']) . " times)\n";
        
        return $text;
    }

    /**
     * Format full stack trace for detailed display
     */
    private function formatFullStackTrace($trace): string
    {
        if (is_string($trace)) {
            return "   " . str_replace("\n", "\n   ", trim($trace)) . "\n";
        }
        
        if (!is_array($trace)) {
            return "   [Stack trace not available]\n";
        }
        
        $text = "";
        foreach (array_slice($trace, 0, 10) as $index => $frame) {
            if (is_array($frame)) {
                $file = $frame['file'] ?? 'unknown';
                $line = $frame['line'] ?? 0;
                $function = $frame['function'] ?? 'unknown';
                $class = $frame['class'] ?? '';
                
                $text .= "   #{$index} ";
                if ($class) {
                    $text .= "{$class}::";
                }
                $text .= "{$function}()\n";
                $text .= "       at " . basename($file);
                if ($line > 0) {
                    $text .= ":{$line}";
                }
                $text .= "\n";
            }
        }
        
        if (count($trace) > 10) {
            $text .= "   ... and " . (count($trace) - 10) . " more frames\n";
        }
        
        return $text;
    }

    /**
     * Get appropriate icon for entry type
     */
    private function getEntryTypeIcon(string $type): string
    {
        return match(strtolower($type)) {
            'request' => '🌐',
            'query' => '🗄️',
            'job' => '⚙️',
            'cache' => '💾',
            'exception' => '🚨',
            'log' => '📝',
            'notification' => '📧',
            'event' => '⚡',
            default => '📊'
        };
    }

    /**
     * Get priority icon based on pattern frequency and trend
     */
    private function getPatternPriorityIcon(int $count, string $trend): string
    {
        if ($count >= 50) {
            return '🔥'; // High frequency
        } elseif ($count >= 20) {
            return '⚡'; // Medium-high frequency
        } elseif ($count >= 10) {
            return '⚠️'; // Medium frequency
        } elseif (str_contains(strtolower($trend), 'increasing')) {
            return '📈'; // Trending up
        } else {
            return '📊'; // Low frequency
        }
    }
} 