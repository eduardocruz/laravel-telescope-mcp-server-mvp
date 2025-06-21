# Laravel Telescope MCP Server - Installation Guide

## Quick Start

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Database
```bash
# Copy environment file
cp .env.example .env

# Edit .env file with your Laravel database connection
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_laravel_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Test the Setup
```bash
# Run basic tests
php test-basic.php
```

### 4. Test the MCP Server
```bash
# Start the server (it will run continuously)
php server.php

# Test in another terminal:
# The server should respond to MCP protocol messages
```

## Cursor Integration

This guide shows you how to integrate the Laravel Telescope MCP Server with Cursor for AI-assisted Laravel debugging.

## 🚀 Quick Setup (Recommended)

### Option 1: Automatic Setup with Deeplink

1. **Generate your custom deeplink:**
   ```bash
   php generate-cursor-deeplink.php --db-database=your_laravel_db --db-username=your_user --db-password=your_pass
   ```

2. **Click the generated deeplink** - Cursor will open and prompt to add the MCP server

3. **Accept the configuration** - The server will be automatically added to Cursor

### Option 2: Manual JSON Configuration

1. **Copy the template configuration:**
   ```bash
   cp cursor-mcp-config.template.json cursor-mcp-config.json
   ```

2. **Edit the configuration** with your database details:
   ```json
   {
     "mcpServers": {
       "laravel-telescope": {
         "command": "php",
         "args": ["/FULL/PATH/TO/laravel-telescope-mcp-server/server.php"],
         "env": {
           "DB_HOST": "127.0.0.1",
           "DB_PORT": "3306",
           "DB_DATABASE": "your_laravel_database",
           "DB_USERNAME": "your_db_username", 
           "DB_PASSWORD": "your_db_password",
           "MCP_SERVER_NAME": "Laravel Telescope MCP Server"
         }
       }
     }
   }
   ```

3. **Add to Cursor:**
   - Open Cursor Settings (Cmd/Ctrl + ,)
   - Go to "Features" → "Model Context Protocol"
   - Click "Add Server"
   - Paste your JSON configuration

## 📋 Prerequisites

- **PHP 8.1+** with PDO MySQL extension
- **Composer** for dependency management
- **Laravel application** with Telescope installed and configured
- **Cursor IDE** with MCP support enabled

## 🔧 Server Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your database settings
   ```

3. **Test the server:**
   ```bash
   php test-database.php
   ```

## 🛠️ Available Tools

Once configured, you'll have access to these tools in Cursor:

### `telescope_recent_requests`
Lists recent HTTP requests from your Laravel application
- **Parameters:** `limit` (1-100, default: 10)
- **Example:** Shows GET/POST requests with status codes, timing, and user info

### `telescope_status` 
Checks database connection and Telescope status
- **No parameters**
- **Example:** Confirms connection to your Laravel database

### `hello_world`
Simple connectivity test
- **Parameters:** `name` (optional)
- **Example:** Verifies MCP server is responding

### `get_recent_entries`
Raw telescope entries for debugging
- **Parameters:** `limit` (1-50, default: 5)  
- **Example:** Shows recent telescope entries of all types

## 🎯 Usage Examples

### In Cursor Chat:
```
Show me the last 20 HTTP requests from my Laravel app
```

```
Check if my telescope database is connected
```

```
What are the recent 404 errors in my application?
```

## 🔍 Troubleshooting

### Server Won't Start
- Check PHP version: `php --version` (needs 8.1+)
- Verify dependencies: `composer install`
- Test database connection: `php test-database.php`

### Database Connection Issues
- Verify database credentials in configuration
- Ensure MySQL/MariaDB is running
- Check telescope_entries table exists: `SHOW TABLES LIKE 'telescope_entries'`

### Cursor Integration Issues
- Restart Cursor after adding MCP configuration
- Check Cursor's MCP logs for connection errors
- Verify server path is absolute in configuration
- Test server manually: `php server.php` (should wait for input)

### Common Fixes
```bash
# Fix permissions
chmod +x server.php

# Test server syntax
php -l server.php

# Check MCP server logs
tail -f mcp.log
```

## 📁 Project Structure

```
laravel-telescope-mcp-server/
├── server.php                          # Main MCP server entry point
├── src/
│   ├── Database.php                    # Database connection & queries
│   └── TelescopeTools.php             # MCP tool implementations
├── generate-cursor-deeplink.php        # Deeplink generator
├── cursor-mcp-config.template.json     # Configuration template
├── .env.example                        # Environment variables template
└── composer.json                       # PHP dependencies
```

## 🔗 Integration Examples

### Laravel Debugging Workflow
1. **Check recent requests:** `telescope_recent_requests` 
2. **Identify slow queries:** Look for high duration values
3. **Debug specific endpoints:** Filter by URI patterns
4. **Monitor error rates:** Check 4xx/5xx status codes

### Performance Analysis
- Use `telescope_recent_requests` with high limits (50-100)
- Sort by duration to find bottlenecks
- Identify frequently accessed endpoints
- Monitor user activity patterns

## 🆘 Support

If you encounter issues:
1. Check this troubleshooting guide
2. Verify your Laravel Telescope is working: `php artisan telescope:install`
3. Test database connectivity independently
4. Check Cursor's MCP server logs

---

**🎉 You're ready to use Laravel Telescope with Cursor!**

Start by asking Cursor: *"Show me recent HTTP requests from my Laravel application"* 