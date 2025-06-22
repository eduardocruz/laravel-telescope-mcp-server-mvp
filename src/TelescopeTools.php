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
} 