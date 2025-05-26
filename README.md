atures

### Core Functionality
- Real-time live streaming
- S# Interactive Live Streaming Platform

A feature-rich live streaming platform built with PHP, MySQL, and Agora SDK that enables interactive streaming with presenter controls and attendee participation.

## Fecreen sharing capability
- Device testing before streaming
- Hand raise system for attendee participation
- Role-based access control (Presenter/Attendee)

### Presenter Features
- Start/Stop streaming
- Audio/Video controls
- Screen sharing (1080p quality)
- View and manage hand raises
- Approve speaker requests

### Attendee Features
- Watch live streams
- Request to speak via "Raise Hand"
- Audio/Video controls when approved as speaker
- Device testing before joining

## Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB
- Composer
- Agora.io account
- Web server (Apache/Nginx)

## Installation

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Install Dependencies**
   ```bash
   composer require agora/token-builder
   ```

3. **Database Setup**
   ```bash
   # Import database schema
   mysql -u your_username -p your_database < database.sql
   ```

4. **Configuration**
   
   Edit `config.php`:
   ```php
   // Database configuration
   define('DB_HOST', 'your_db_host');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');

   // Agora credentials
   define('AGORA_APP_ID', 'your_app_id');
   define('AGORA_APP_CERTIFICATE', 'your_app_certificate');
   define('AGORA_PRIMARY_CERTIFICATE', 'your_primary_certificate');
   ```

5. **Create Test Users**
   ```sql
   -- Create a presenter
   INSERT INTO users (username, password, role) 
   VALUES ('presenter', PASSWORD_HASH('your_password', PASSWORD_DEFAULT), 'presenter');

   -- Create an attendee
   INSERT INTO users (username, password, role) 
   VALUES ('attendee', PASSWORD_HASH('your_password', PASSWORD_DEFAULT), 'attendee');
   ```

## Project Structure

```
project/
├── config.php          # Configuration and database connection
├── database.sql        # Database schema
├── device_setup.php    # Device testing interface
├── index.php          # Main streaming interface
├── login.php          # User authentication
├── logout.php         # Session cleanup
├── stream_actions.php # Stream management
└── README.md          # Project documentation
```

## Usage Flow

### Presenter Flow
1. Login with presenter credentials
2. Complete device setup and testing
3. Start stream from main interface
4. Control stream (audio/video/screen)
5. Manage hand raises
6. Stop stream when finished

### Attendee Flow
1. Login with attendee credentials
2. Test devices if planning to participate
3. Watch the live stream
4. Use "Raise Hand" to request speaking
5. If approved, use audio/video controls

## Security Considerations

1. **Credential Security**
   - Store config.php outside web root
   - Use environment variables in production
   - Regular certificate rotation

2. **User Authentication**
   - Session-based authentication
   - Role-based access control
   - Password hashing

3. **Stream Security**
   - Token-based stream authentication
   - Expiring tokens
   - Secure device handling

## Troubleshooting

1. **Stream Issues**
   - Check Agora credentials
   - Verify device permissions
   - Check network connectivity
   - Monitor browser console

2. **Database Issues**
   - Verify database credentials
   - Check table structure
   - Monitor error logs

3. **Device Issues**
   - Allow browser permissions
   - Test different devices
   - Clear browser cache

## Development

### Adding New Features
1. Fork the repository
2. Create feature branch
3. Implement changes
4. Submit pull request

### Coding Standards
- Follow PSR-12 coding style
- Add comments for complex logic
- Update documentation

## License

[Your License Here]

## Support

For issues and feature requests, please [create an issue](your-repo-issues-url)

## Contributors

[List of Contributors]

---

For detailed Agora SDK documentation, visit [Agora.io Documentation](https://docs.agora.io/)
