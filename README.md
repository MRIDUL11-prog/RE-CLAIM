# RE:CLAIM – Campus Lost and Found

**RE:CLAIM** is a web-based Campus Lost and Found Management System designed to help students and college staff report, search, and recover lost belongings within a campus.

The system provides a simple platform where users can report lost or found items, search for items, submit claims, and communicate with administrators.

## 🚀 Features

- **User Registration & Login:** Secure user authentication.
- **Report Lost Items:** Users can report items they have lost.
- **Report Found Items:** Users can submit details about found belongings.
- **Browse & Search Items:** Search and browse available lost and found items.
- **Category Management:** Organize items into different categories.
- **Claim Management:** Users can submit claims for items they believe belong to them.
- **Admin Dashboard:** Administrators can manage users, items, claims, and feedback.
- **Feedback System:** Users can share feedback.
- **Email Notifications:** Email notifications for supported system activities.
- **Password Reset:** Password recovery functionality.

## 🛠️ Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Page structure |
| CSS3 | Styling and layout |
| JavaScript | Client-side interactions |
| PHP | Backend development |
| MySQL | Database management |
| Bootstrap / Custom CSS | User interface styling |
| PHPMailer | Email functionality |
| XAMPP | Local development environment |

## 📂 Project Structure

```text
RE-CLAIM/
│
├── admin/
├── assets/
├── config/
├── css/
├── database/
│   └── campus_lost_found.sql
├── images/
├── includes/
├── js/
├── uploads/
│
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── feedback.php
├── search.php
├── README.md
└── .gitignore
```

*Note: The structure above is illustrative. Your actual project may contain additional files and folders.*

## ⚙️ Installation & Setup

Follow these steps to run RE:CLAIM on your local computer.

### 1. Install Required Software

- XAMPP
- Visual Studio Code
- Web Browser

### 2. Clone the Repository

```bash
git clone https://github.com/YOUR_GITHUB_USERNAME/RE-CLAIM.git
```

### 3. Move the Project

Place the project folder inside the XAMPP `htdocs` directory.

```text
C:/xampp/htdocs/RE-CLAIM/
```

### 4. Create the Database

1. Start Apache and MySQL from XAMPP.
2. Open phpMyAdmin.
3. Create a database named `campus_lost_found`.
4. Import `database/campus_lost_found.sql`.

### 5. Configure Database Connection

Create your local `config/database.php` file using the example configuration file.

Update the database host, username, password, database name, and port according to your MySQL setup.

### 6. Configure Email Settings

Set up your SMTP credentials in your local `config/mail.php` file.

**Important:** Never upload real database passwords, SMTP credentials, or other sensitive information to GitHub.

### 7. Run the Project

Open your browser and visit:

```text
http://localhost/RE-CLAIM/
```

## 🗄️ Database Tables

The project uses MySQL with the following tables:

- `users` – Stores user information.
- `categories` – Stores item categories.
- `items` – Stores lost and found item details.
- `claims` – Stores item claim requests.
- `feedback` – Stores user feedback.
- `password_resets` – Manages password reset requests.

## 🔐 Security

- Password hashing for user passwords.
- Input validation and sanitization.
- Prepared statements for database operations.
- Protected configuration files.
- SMTP credentials stored separately from public source code.

## 🎯 Project Objective

The main objective of RE:CLAIM is to make the lost and found process easier, faster, and more organized within a college campus.

It reduces the difficulty of finding lost belongings and provides a centralized platform for students and administrators.

## 🔮 Future Improvements

- Real-time notifications.
- Image-based item search.
- QR code-based item identification.
- Improved mobile responsiveness.
- Advanced search and filtering.

## 👨‍💻 Developer

**RE:CLAIM – Campus Lost and Found**

Developed as an academic project using PHP and MySQL.

## 📄 License

This project is developed for educational purposes.