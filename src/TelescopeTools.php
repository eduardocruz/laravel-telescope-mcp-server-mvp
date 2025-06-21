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