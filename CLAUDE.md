# The Salty Parrot - PHP Web Application

## Project Commands
- **Run locally**: Use PHP's built-in server with `php -S localhost:8000`
- **Deploy**: Upload files via FTP to hosting provider
- **Database**: Connect using credentials in `config/db_connect.php`
- **Discord API**: Test Discord integration with `php discord_test.php`

## Code Style Guidelines
- **Files**: Use snake_case for filenames (e.g., `discord_service.php`)
- **PHP Variables**: Use camelCase for variable names
- **Formatting**: 4-space indentation
- **Database**: Always use PDO with prepared statements
- **Error Handling**: Wrap API calls and DB operations in try/catch blocks
- **Input Sanitization**: Use htmlspecialchars for user input
- **Component Structure**: Reusable UI elements go in `/components/`
- **API Endpoints**: Place in `/api/` directory with descriptive names
- **Session Management**: Initialize with session_start() at top of pages
- **Authentication**: Use Discord OAuth flow through `/discord/` endpoints

## Architecture Notes
- Backend-driven app with PHP generating HTML pages
- Discord integration for sharing game content
- Simple REST API for game content generators