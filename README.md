# ToothTrackr 

A comprehensive dental clinic management system designed for **Songco Dental and Medical Clinic** (SDMC), featuring efficient appointment scheduling, patient management, and administrative tools.

![Demo](./Media/toothtrackr.gif)


## Features

### Multi-User System
- **Admin Dashboard**: Complete clinic management and oversight
- **Dentist Portal**: Patient records, appointments, and schedule management
- **Patient Interface**: Appointment booking and personal health tracking

### Appointment Management
- Interactive calendar system with FullCalendar integration
- Real-time appointment scheduling and booking
- Automated email reminders and notifications
- Appointment history and tracking

### Clinical Features
- Digital dental records management
- Patient history and treatment tracking
- Service and procedure management
- PDF report generation with TCPDF

### Communication System
- Automated email notifications using PHPMailer
- Google Calendar integration for appointment sync
- SMS reminders and alerts
- Contact form with Gmail integration

### Modern UI/UX
- Responsive design with mobile-first approach
- Interactive animations and smooth transitions
- Customizable theme with dental clinic branding
- Intuitive navigation and user experience

## Technology Stack

### Backend
- **PHP** - Server-side scripting
- **MySQL** - Database management
- **Python/Flask** - Email automation API
- **Composer** - Dependency management

### Frontend
- **HTML5/CSS3** - Structure and styling
- **JavaScript/jQuery** - Interactive functionality
- **Bootstrap** - Responsive framework
- **Font Awesome** - Icon library
- **Owl Carousel** - Service showcase slider

### Libraries & Dependencies
- **PHPMailer** - Email functionality
- **TCPDF** - PDF generation
- **FullCalendar** - Calendar interface
- **Google Calendar API** - Calendar synchronization

## Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Python 3.x
- Composer
- Web browser

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/Saimeown/ToothTrackr.git
   cd ToothTrackr
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Python dependencies**
   ```bash
   cd api
   pip install flask
   ```

4. **Database Setup**
   - Start XAMPP services (Apache & MySQL)
   - Create database named `sdmc`
   - Import the database schema (if provided)
   - Update database credentials in `connection.php`

5. **Configure Email Settings**
   - Update Gmail API credentials in `api/` directory
   - Configure SMTP settings for PHPMailer

6. **Start the Application**
   - Place the project in `xampp/htdocs/ToothTrackr`
   - Access via `http://localhost/ToothTrackr`

## 📁 Project Structure

```
ToothTrackr/
├── admin/              # Admin dashboard and management
├── dentist/            # Dentist portal functionality
├── patient/            # Patient interface (login/signup)
├── api/                # Python Flask API for email automation
├── css/                # Stylesheets and themes
├── calendar/           # Calendar integration components
├── uploads/            # File upload storage
├── Media/              # Images and media assets
├── tcpdf/              # PDF generation library
├── vendor/             # Composer dependencies
├── connection.php      # Database configuration
├── Toothtrackr.php     # Main landing page
└── composer.json       # PHP dependencies
```

## Usage

### For Administrators
1. Login to admin portal
2. Manage dentists, patients, and appointments
3. Configure clinic information and services
4. Generate reports and analytics

### For Dentists
1. Access dentist dashboard
2. View and manage patient appointments
3. Update patient dental records
4. Manage personal schedule

### For Patients
1. Register for an account
2. Book appointments online
3. View appointment history
4. Receive email/SMS reminders

## Configuration

### Database Configuration
Update `connection.php` with your database credentials:
```php
$database = new mysqli("localhost", "username", "password", "sdmc");
```

### Email Configuration
Configure email settings in the `api/` directory for automated notifications.

### Google Calendar Integration
Add your Google Calendar API credentials in the `api/` folder.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Support

For technical support or bug reports:
- **Email**: toothtrackr@gmail.com
- **Phone**: +63 994 803 5127
- **GitHub Issues**: [Report a bug](https://github.com/Saimeown/ToothTrackr/issues)

## License

This project is developed for Songco Dental and Medical Clinic. All rights reserved.

## Acknowledgments

- **Songco Dental and Medical Clinic** - For project requirements and support
- **PHP Community** - For excellent documentation and libraries
- **Bootstrap Team** - For responsive design framework
- **FullCalendar** - For calendar functionality

---

**© 2025 ToothTrackr | All Rights Reserved**

*Built with ❤️ for better dental care management*
