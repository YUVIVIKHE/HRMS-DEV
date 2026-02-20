# HRMS Admin Login System

A professional Human Resource Management System (HRMS) admin login portal with a modern, responsive UI built with PHP, MySQL, and XAMPP.

## Features

- **Professional UI Design**: Microsoft/SaaS-inspired interface with smooth animations
- **Fully Responsive**: Optimized for desktop, tablet, and mobile devices
- **Secure Authentication**: Password hashing with PHP's password_hash()
- **Session Management**: Secure session handling for logged-in users
- **Modern Dashboard**: Clean, intuitive admin dashboard with statistics
- **Smooth Animations**: Professional transitions and hover effects

## Requirements

- XAMPP (or any PHP 7.4+ and MySQL 5.7+ environment)
- Web browser (Chrome, Firefox, Safari, Edge)

## Installation

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install and start Apache and MySQL services

2. **Setup Project**
   - Copy all files to `C:\xampp\htdocs\hrms\` (Windows) or `/opt/lampp/htdocs/hrms/` (Linux)

3. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "Import" tab
   - Select `database.sql` file
   - Click "Go" to import

4. **Configure Database** (if needed)
   - Edit `config.php` if your MySQL credentials differ:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'hrms_db');
   ```

5. **Access Application**
   - Open browser and go to: http://localhost/hrms/login.php

## Default Login Credentials

- **Email**: admin@hrms.com
- **Password**: admin123

## File Structure

```
hrms/
├── css/
│   ├── login.css          # Login page styles
│   ├── dashboard.css      # Dashboard styles
│   └── add_employee.css   # Add employee form styles
├── js/
│   ├── login.js           # Login page scripts
│   ├── dashboard.js       # Dashboard scripts
│   └── add_employee.js    # Add employee form scripts
├── config.php             # Database configuration
├── login.php              # Login page
├── dashboard.php          # Admin dashboard
├── add_employee.php       # Add employee form
├── logout.php             # Logout handler
├── database.sql           # Database schema and sample data
└── README.md              # This file
```

## Features Breakdown

### Login Page
- Gradient background with animations
- Email and password validation
- Password visibility toggle
- Remember me functionality
- Responsive design for all screen sizes
- Error message display
- Loading state on form submission
- Support for both admin and employee logins

### Dashboard
- Collapsible sidebar navigation
- Statistics cards with icons
- Recent activities feed
- Quick action buttons
- User profile dropdown
- Notification badge
- Fully responsive layout
- Smooth animations and transitions

### Employee Management
- Add individual employees with 50+ fields
- Bulk upload via CSV file
- Auto-generated login credentials (email as username, 8-char password)
- Employee list with search functionality
- Automatic shift type assignment based on location:
  - India employees: Fixed shift, 8 hours/day required
  - International employees: Flexible shift, 9 hours/day required

### Attendance System
- Web-based clock IN/OUT functionality
- Real-time clock display
- Automatic overtime calculation based on employee's required hours
- Monthly attendance statistics:
  - Days present
  - Total working hours
  - Required hours per day (based on shift type)
  - Overtime hours
- Attendance history table
- Regularization request button (for late in/early out/forgot clock out)
- Flexible shift support for international employees

### Notifications
- Messenger-style notification page
- Create notifications with types (info/success/warning/urgent)
- Target audience selection (all employees/specific department)
- Dynamic notification count badge
- Delete functionality
- Accessible via bell icon in header

### Add Employee Page
- Multi-step form with 5 sections:
  1. Personal Information (name, DOB, gender, etc.)
  2. Contact Details (email, phone, addresses)
  3. Employment Details (job title, department, manager)
  4. Bank & Tax Information (account, PAN, Aadhar, PF, ESI)
  5. Document Information (passport details)
- Progress bar showing current step
- Form validation for required fields
- Smooth step transitions
- Copy address functionality
- Fully responsive design
- All 50+ employee fields included
- Auto-generates employee login credentials
- Displays password once with copy button

## Security Features

- Password hashing using `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- XSS protection with `htmlspecialchars()`
- Active status check for admin accounts

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Customization

### Change Colors
Edit the CSS files to modify the color scheme:
- Primary blue: `#0078D4`
- Secondary blue: `#0053A0`
- Gradient colors in `.login-left` and `.stat-icon` classes

### Add More Features
- Extend `dashboard.php` with additional sections
- Add more navigation items in the sidebar
- Create new pages and link them in the navigation

## Troubleshooting

**Database Connection Error**
- Verify MySQL is running in XAMPP
- Check database credentials in `config.php`
- Ensure database is imported correctly

**Login Not Working**
- Clear browser cache and cookies
- Verify admin account exists in database
- Check PHP error logs in `C:\xampp\php\logs\`

**Styles Not Loading**
- Check file paths are correct
- Ensure CSS files are in the `css/` folder
- Clear browser cache

## License

This project is open source and available for educational purposes.

## Support

For issues or questions, please check the code comments or modify as needed for your specific requirements.
