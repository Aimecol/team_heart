# Team Heart Mission Authorization System

A complete web-based mission authorization system built with PHP, MySQL, HTML, CSS (Tailwind), and JavaScript.

## Features

- **User Management**
  - User registration and login
  - Role-based access (Admin, Manager, Staff)
  - Session management with CSRF protection

- **Member Management**
  - Create, Read, Update, Delete (CRUD) members
  - Track employee information
  - Department and position management

- **Mission Authorization**
  - Create mission authorizations
  - Approve/reject authorizations
  - Generate printable PDF-style documents
  - Track mission status (draft, pending, approved)
  - Automatic authorization number generation

- **Dashboard**
  - Overview statistics
  - Recent activity
  - Quick actions

- **Security**
  - Password hashing (bcrypt)
  - CSRF token protection
  - SQL injection prevention (PDO prepared statements)
  - XSS protection
  - Session security

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)

### Setup Steps

1. **Clone/Download the project**
   ```bash
   cd /var/www/html
   git clone <repository-url> mission-authorization-system
   cd mission-authorization-system
   ```

2. **Create Database**
   ```bash
   mysql -u root -p
   ```
   
   ```sql
   CREATE DATABASE team_heart_missions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

3. **Import Database Schema**
   ```bash
   mysql -u root -p team_heart_missions < database/schema.sql
   ```

4. **Configure Database Connection**
   
   Edit `config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "team_heart_missions";
   private $username = "your_username";
   private $password = "your_password";
   ```

5. **Set Permissions**
   ```bash
   chmod 755 /var/www/html/mission-authorization-system
   chown -R www-data:www-data /var/www/html/mission-authorization-system
   ```

6. **Configure Virtual Host (Apache)**
   
   Create `/etc/apache2/sites-available/mission-auth.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName mission.teamheart.local
       DocumentRoot /var/www/html/mission-authorization-system
       
       <Directory /var/www/html/mission-authorization-system>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/mission-auth-error.log
       CustomLog ${APACHE_LOG_DIR}/mission-auth-access.log combined
   </VirtualHost>
   ```
   
   Enable site:
   ```bash
   sudo a2ensite mission-auth
   sudo systemctl reload apache2
   ```

7. **Access the Application**
   
   Open browser: `http://mission.teamheart.local`
   or `http://localhost/mission-authorization-system`

## Default Login Credentials

After importing the database schema with sample data:

- **Admin User**
  - Email: admin@teamheartrw.org
  - Password: Admin@123

- **Manager User**
  - Email: manager@teamheartrw.org
  - Password: Admin@123

- **Staff User**
  - Email: staff@teamheartrw.org
  - Password: Admin@123

**Important:** Change these passwords immediately after first login in production!

## Directory Structure

```
mission-authorization-system/
├── config/
│   ├── database.php          # Database connection
│   └── session.php           # Session management & helpers
├── models/
│   ├── User.php              # User model
│   ├── Member.php            # Member model
│   └── MissionAuthorization.php  # Mission model
├── includes/
│   └── navbar.php            # Navigation bar
├── members/
│   ├── index.php             # List members
│   ├── create.php            # Create member
│   ├── edit.php              # Edit member
│   ├── view.php              # View member
│   └── delete.php            # Delete member
├── missions/
│   ├── index.php             # List missions
│   ├── create.php            # Create mission
│   ├── view.php              # View/approve mission
│   ├── edit.php              # Edit mission
│   ├── delete.php            # Delete mission
│   └── print.php             # Printable document
├── index.php                 # Homepage redirect
├── login.php                 # Login page
├── register.php              # Registration page
├── dashboard.php             # Dashboard
├── logout.php                # Logout handler
├── .htaccess                 # Apache configuration
└── README.md                 # This file
```

## Usage

### Creating a Member

1. Login to the system
2. Navigate to "Members" → "Add Member"
3. Fill in member details
4. Click "Create Member"

### Creating a Mission Authorization

1. Navigate to "Missions" → "Create Authorization"
2. Select a member from dropdown
3. Fill in mission details:
   - Purpose
   - Destination
   - Departure date
   - Return date
4. Click "Create Authorization"

### Approving a Mission

1. Navigate to "Missions"
2. Click "View" on a draft mission
3. Scroll to "Approve Authorization" section
4. Fill in authorization details:
   - Authorized by (name)
   - Position
   - Date
5. Click "Approve Authorization"

### Printing a Mission Authorization

1. Navigate to approved mission
2. Click "Print Document"
3. Use browser print function (Ctrl+P / Cmd+P)

## Database Schema

### Tables

- **users** - User accounts
- **members** - Organization members/travelers
- **mission_authorizations** - Mission authorization records
- **audit_logs** - System activity logs

### Key Relationships

- users → members (one-to-many)
- users → mission_authorizations (one-to-many)
- members → mission_authorizations (one-to-many)

## Security Features

1. **Password Security**
   - Bcrypt hashing
   - Minimum 6 characters
   - Secure password storage

2. **CSRF Protection**
   - Token generation and validation
   - Form protection

3. **SQL Injection Prevention**
   - PDO prepared statements
   - Parameter binding

4. **XSS Protection**
   - Input sanitization
   - Output escaping
   - htmlspecialchars()

5. **Session Security**
   - Secure session management
   - Session regeneration
   - Timeout handling

## Customization

### Changing Organization Details

Edit the header in `missions/print.php`:
```php
<div class="font-semibold">KG 685 St, 4 Kigali, Rwanda</div>
<div class="mt-1">info@teamheartrw.org</div>
<div>www.teamheartrw.org</div>
<div class="mt-1">+ (250) 788 919 482</div>
```

### Changing Logo

Replace the logo URL in files:
- `missions/print.php`
- `includes/navbar.php`
- `login.php`
- `register.php`

### Adding More Fields

1. Add column to database table
2. Update model class (create/update methods)
3. Add input field to form
4. Update display/print templates

## Troubleshooting

### Database Connection Error

- Check database credentials in `config/database.php`
- Verify MySQL service is running
- Check database exists

### Permission Denied

```bash
sudo chown -R www-data:www-data /var/www/html/mission-authorization-system
sudo chmod -R 755 /var/www/html/mission-authorization-system
```

### Print Layout Issues

- Ensure browser print settings are: A4, Portrait
- Check CSS @page settings in `missions/print.php`
- Try different browsers (Chrome recommended)

### Session Issues

- Check PHP session settings
- Verify session directory has write permissions
- Clear browser cookies

## Browser Compatibility

- Chrome 90+ (Recommended for printing)
- Firefox 88+
- Safari 14+
- Edge 90+

## License

Proprietary - Team Heart, Inc.

## Support

For support, contact: info@teamheartrw.org

## Version History

- **v1.0.0** (2026-02-06) - Initial release
  - User management
  - Member CRUD
  - Mission authorization system
  - Print functionality
