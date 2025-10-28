# My PHP Application

## Overview
This project is a PHP application designed to provide a seamless installation and configuration experience. It includes a **Universal Installer** with comprehensive features for quick and easy deployment.

## Universal Installer Features

### ✨ What Makes It Universal?

The installer now includes extensive functionality to support various deployment scenarios:

1. **System Requirements Check**
   - PHP version validation (8.0+)
   - Required PHP extensions verification (mysqli, pdo, json, mbstring, openssl, curl, fileinfo, tokenizer)
   - Directory permissions check

2. **Multi-Database Support**
   - MySQL / MariaDB
   - PostgreSQL
   - SQLite
   - Automatic database creation if it doesn't exist

3. **Automatic Directory Setup**
   - Creates required storage directories (cache, logs, sessions, uploads)
   - Sets up bootstrap cache directory
   - Validates directory permissions

4. **Environment Configuration**
   - Generates `.env` file from template
   - Automatic security key generation
   - Configurable application environment (production/local)
   - Debug mode toggle

5. **Database Management**
   - Connection testing before installation
   - Optional database schema execution
   - Transaction support for safe installations

6. **Security Features**
   - Secure random key generation for encryption
   - `.htaccess` file generation for Apache
   - File permission validation
   - Secure `.env` file permissions (0600)

7. **Error Handling & Rollback**
   - Comprehensive error reporting
   - Warning messages for non-critical issues
   - Automatic rollback on installation failure
   - Post-installation cleanup recommendations

8. **Dual Installation Methods**
   - Command-line installer for server deployments
   - Web-based installer with modern UI for browser-based installation

## Project Structure
```
my-php-app
├── src
│   ├── public
│   │   └── index.php          # Entry point for the application
│   ├── App
│   │   ├── Installer.php      # Universal installer with comprehensive features
│   │   └── Application.php     # Manages application logic
│   ├── Config
│   │   └── Database.php       # Manages database connections
│   └── Views
│       └── install.php        # Modern web-based installation wizard
├── scripts
│   └── install.php            # Command-line installation script
├── database
│   └── schema.sql             # Sample database schema (optional)
├── .env.example                # Template for environment variables
├── .gitignore                  # Git ignore rules
├── composer.json               # Composer dependencies and scripts
├── phpunit.xml                # PHPUnit configuration
└── README.md                  # Project documentation
```

## Installation Instructions

### Method 1: Command-Line Installation (Recommended for servers)

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd my-php-app
   ```

2. **Install Dependencies**
   Ensure you have Composer installed, then run:
   ```bash
   composer install
   ```

3. **Run the Installer**
   Execute the installation script:
   ```bash
   php scripts/install.php
   ```
   
   The installer will prompt you for:
   - Database driver (mysql/pgsql/sqlite)
   - Database connection details (host, port, database name, username, password)
   - Application environment (local/production)
   - Debug mode preference
   - Optional database schema file path

4. **Access the Application**
   After installation, configure your web server to point to `src/public/` and access your application.

### Method 2: Web-Based Installation (Recommended for shared hosting)

1. **Upload Files**
   Upload all files to your web server

2. **Navigate to Installer**
   Open your browser and go to:
   ```
   http://yourdomain.com/src/Views/install.php
   ```

3. **Follow the Wizard**
   - The installer will check system requirements
   - Fill in the database configuration form
   - Configure application settings
   - Click "Install Application"

4. **Secure Your Installation**
   After successful installation, delete:
   - `scripts/install.php`
   - `src/Views/install.php`

## New Features Explained

### 1. Comprehensive Requirements Check
The installer validates all necessary PHP extensions and versions before proceeding, preventing installation failures.

### 2. Database Connection Testing
Before making any changes, the installer tests database connectivity to ensure credentials are valid.

### 3. Automatic Directory Creation
All required directories for caching, logging, sessions, and file uploads are automatically created with proper permissions.

### 4. Security Key Generation
A cryptographically secure random key is automatically generated for application security (encryption, sessions, etc.).

### 5. Environment File Generation
The `.env` file is automatically created from the template with your specified configuration, eliminating manual editing.

### 6. Multi-Database Support
Choose from MySQL, MariaDB, PostgreSQL, or SQLite based on your hosting requirements.

### 7. Rollback Capability
If installation fails at any point, the installer automatically rolls back changes to prevent partial installations.

### 8. Schema Support
Optionally provide a SQL schema file during installation to automatically set up your database tables and initial data.

## Usage

### Using the Command-Line Installer
```bash
php scripts/install.php
```

Follow the interactive prompts to configure your installation. The installer provides clear feedback at each step.

### Using the Web Installer
Navigate to `src/Views/install.php` in your browser and use the modern installation wizard with a user-friendly interface.

### Post-Installation
1. Review the `.env` file and adjust any additional settings
2. Delete installation files for security:
   ```bash
   rm scripts/install.php
   rm src/Views/install.php
   ```
3. Configure your web server (Apache/Nginx) to point to `src/public/`
4. Set up scheduled tasks (cron jobs) if needed
5. Configure file upload limits and other PHP settings as needed

## Configuration Options

### Database Drivers
- **mysql**: MySQL or MariaDB databases
- **pgsql**: PostgreSQL databases
- **sqlite**: SQLite file-based database

### Environment Settings
- **production**: Optimized for live sites (caching enabled, errors hidden)
- **local**: Development mode (detailed errors, no caching)

### Debug Mode
Enable debug mode only in development environments for detailed error reporting.

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Ensure the web server has write permissions to the application directory
   - Set proper ownership: `chown -R www-data:www-data /path/to/app`

2. **Database Connection Failed**
   - Verify database credentials
   - Ensure database server is running
   - Check firewall rules if using remote database

3. **Missing PHP Extensions**
   - Install required extensions via your package manager
   - Ubuntu/Debian: `sudo apt-get install php-mysqli php-pdo php-mbstring php-curl`
   - CentOS/RHEL: `sudo yum install php-mysqli php-pdo php-mbstring php-curl`

4. **Directory Not Writable**
   - Set permissions: `chmod -R 755 storage bootstrap/cache`
   - Ensure SELinux is properly configured if applicable

## Contributing
Feel free to submit issues or pull requests to improve the application. Your contributions are welcome!

## License
This project is open-source and available under the MIT License.