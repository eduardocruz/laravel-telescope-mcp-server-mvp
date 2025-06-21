# Laravel Telescope MCP Server (PHP)

A Model Context Protocol (MCP) server built in PHP that provides AI agents with direct access to Laravel Telescope data, enabling efficient debugging and analysis of Laravel applications.

## Project Overview

This project is a PHP implementation of a Laravel Telescope MCP server, inspired by [bradleybernard/TelescopeMCP](https://github.com/bradleybernard/TelescopeMCP) but built using the [php-mcp/server](https://github.com/php-mcp/server) library instead of Python.

### Key Goals

- **Proof of Concept**: Create the most basic MCP server to demonstrate Laravel Telescope integration
- **PHP Native**: Built entirely in PHP using the php-mcp/server library
- **Cursor Integration**: Optimized for Cursor IDE with deeplink support for seamless development workflow
- **Minimal Viable Product**: Focus on core functionality to get something working quickly

## Features (Planned)

### Core MCP Tools
- `telescope_requests` - List recent HTTP requests from Telescope
- `telescope_search` - Search requests with basic filters (status, method, URI pattern)
- `telescope_request_details` - Get detailed information about a specific request
- `telescope_queries` - View database queries for a specific request

### Cursor Integration
- Deeplink support for quick server startup and debugging
- Development-friendly configuration
- Easy setup for Laravel projects

## Architecture

```
Laravel App → Telescope → MySQL Database
                              ↓
                    PHP MCP Server (php-mcp/server)
                              ↓
                         Cursor IDE
                              ↓
                      AI Assistant Integration
```

## Quick Start (Planned)

### Prerequisites
- PHP 8.1+
- Composer
- Laravel application with Telescope installed
- Cursor IDE

### Installation
```bash
git clone <this-repo>
cd laravel-telescope-mcp-server
composer install
```

### Configuration
```bash
# Copy environment file
cp .env.example .env

# Configure database connection to your Laravel app
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_laravel_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Running the Server

#### Option 1: Direct PHP Execution
```bash
php server.php
```

#### Option 2: Using Composer Script
```bash
composer run serve
```

#### Option 3: Using PHPX (Recommended)
If you have [PHPX](https://github.com/eduardocruz/phpx) installed:
```bash
phpx laravel-telescope-mcp-server
```

### Cursor Deeplink Integration

To integrate with Cursor, you'll need to create a deeplink configuration. Here's the MCP server configuration for Cursor:

#### For Cursor IDE Settings
Add this to your Cursor MCP configuration:

```json
{
  "telescope": {
    "command": "php",
    "args": [
      "/path/to/laravel-telescope-mcp-server/server.php"
    ],
    "env": {
      "DB_HOST": "127.0.0.1",
      "DB_PORT": "3306",
      "DB_DATABASE": "your_laravel_db",
      "DB_USERNAME": "your_username",
      "DB_PASSWORD": "your_password"
    }
  }
}
```

#### Alternative: Using PHPX
```json
{
  "telescope": {
    "command": "phpx",
    "args": [
      "laravel-telescope-mcp-server"
    ],
    "env": {
      "DB_HOST": "127.0.0.1",
      "DB_PORT": "3306",
      "DB_DATABASE": "your_laravel_db",
      "DB_USERNAME": "your_username",
      "DB_PASSWORD": "your_password"
    }
  }
}
```

#### Generating Cursor Deeplink
1. Get your server configuration JSON
2. Use `JSON.stringify()` to convert it and base64 encode it
3. Create the deeplink: `cursor://mcp/install?name=telescope&config=BASE64_ENCODED_CONFIG`

Example helper script for generating the deeplink:
```php
<?php
$config = [
    "telescope" => [
        "command" => "php",
        "args" => [getcwd() . "/server.php"],
        "env" => [
            "DB_HOST" => "127.0.0.1",
            "DB_PORT" => "3306",
            "DB_DATABASE" => "your_laravel_db",
            "DB_USERNAME" => "your_username",
            "DB_PASSWORD" => "your_password"
        ]
    ]
];

$encoded = base64_encode(json_encode($config));
echo "cursor://mcp/install?name=telescope&config=" . $encoded . "\n";
?>

## Development Roadmap

### Phase 1: Basic MCP Server
- [x] Project setup and README
- [ ] Basic MCP server using php-mcp/server
- [ ] Database connection to Laravel Telescope tables
- [ ] Simple request listing tool

### Phase 2: Core Tools
- [ ] Request search and filtering
- [ ] Request detail retrieval
- [ ] Query analysis tool
- [ ] Basic error handling

### Phase 3: Cursor Integration
- [ ] Deeplink configuration
- [ ] Development workflow optimization
- [ ] Documentation for Cursor setup

### Phase 4: Enhancement
- [ ] Performance optimization
- [ ] Additional Telescope data types (jobs, cache, etc.)
- [ ] Better error messages and debugging

## Technical Stack

- **Language**: PHP 8.1+
- **MCP Library**: [php-mcp/server](https://github.com/php-mcp/server)
- **Database**: MySQL/MariaDB (Laravel Telescope tables)
- **IDE Integration**: Cursor with deeplink support

## Inspiration

This project draws inspiration from:
- [bradleybernard/TelescopeMCP](https://github.com/bradleybernard/TelescopeMCP) - Python implementation
- [php-mcp/server](https://github.com/php-mcp/server) - PHP MCP server library

## Why PHP?

- Native integration with Laravel ecosystem
- Leverages existing PHP knowledge for Laravel developers
- Direct access to Laravel's database structure and conventions
- Easier deployment alongside existing PHP/Laravel infrastructure

## Contributing

This is a proof-of-concept project focused on getting a minimal viable product working. Contributions welcome once the basic functionality is established.

## License

MIT License - see LICENSE file for details.

---

**Status**: 🚧 Early Development - Proof of Concept Phase

**Next Steps**: Set up basic MCP server structure using php-mcp/server library. 