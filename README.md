# VulnScope

### Open-Source Web VAPT & Security Assessment Platform

VulnScope is a web-based security assessment platform designed to help authorized security professionals organize targets, perform reconnaissance and security assessments, record findings and evidence, and generate security reports from a centralized interface.

> ⚠️ **Authorized Use Only:** VulnScope is intended for authorized security testing, vulnerability assessment, penetration testing, and security research. Only test systems, applications, domains, and infrastructure that you own or have explicit permission to assess.

---

## ✨ Features

* 🔎 **Reconnaissance**

  * Collect information about authorized targets
  * Organize reconnaissance results
  * Track target information from a centralized project

* 🎯 **Project & Target Management**

  * Create security assessment projects
  * Manage targets
  * Define assessment scope
  * Keep assessment information organized

* 🛡️ **Web Security Assessment**

  * Perform security checks against authorized web targets
  * Identify potential security issues
  * Record assessment results

* 🔍 **Findings Management**

  * Create and manage vulnerability findings
  * Track severity and status
  * Store supporting evidence
  * Organize findings by project/target

* 📄 **Security Reporting**

  * Generate security assessment reports
  * Include findings and evidence
  * Provide structured results for security reviews

* 🔐 **Scope Enforcement**

  * Keep testing activities associated with defined targets
  * Reduce the risk of accidentally testing unintended systems

* 📝 **Audit Logging**

  * Track important application and assessment activity
  * Maintain an assessment history

* ⚙️ **Background Jobs**

  * Support asynchronous assessment tasks
  * Process longer-running operations through the application queue

* 🧪 **Testing**

  * PHPUnit test suite
  * Feature tests
  * Unit tests
  * Additional verification scripts

---

## 🖥️ Screenshots

> Add screenshots of your actual application here.

### Dashboard

![VulnScope Dashboard](docs/screenshots/dashboard.png)

### Reconnaissance

![VulnScope Reconnaissance](docs/screenshots/reconnaissance.png)

### Findings

![VulnScope Findings](docs/screenshots/findings.png)

### Security Report

![VulnScope Report](docs/screenshots/report.png)

---

## 🏗️ Technology Stack

VulnScope is built using:

* **PHP**
* **Laravel**
* **Composer**
* **Blade**
* **PHPUnit**
* **MySQL / compatible relational database**

---

## 📋 Requirements

Before installing VulnScope, make sure your environment has:

* PHP 8.2+
* Composer
* A supported database
* PHP extensions required by Laravel and the project
* Git

Check your PHP and Composer versions:

```bash
php -v
composer -V
```

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/VulnScope.git
cd VulnScope
```

Replace `YOUR_USERNAME` with your GitHub username.

---

### 2. Install Dependencies

```bash
composer install
```

Do not commit the `vendor/` directory to GitHub. Composer will create it during installation.

---

### 3. Create Environment File

Copy the example environment file:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

---

### 4. Generate Application Key

```bash
php artisan key:generate
```

---

### 5. Configure the Database

Open `.env` and configure your local database.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vulnscope
DB_USERNAME=root
DB_PASSWORD=
```

Use your own local database credentials.

**Never commit `.env` or real credentials to GitHub.**

---

### 6. Run Database Migrations

```bash
php artisan migrate
```

If the project requires seed data:

```bash
php artisan db:seed
```

---

### 7. Start the Application

```bash
php artisan serve
```

The application will normally be available at:

```text
http://127.0.0.1:8000
```

---

## ⚙️ Queue Worker

Some VulnScope operations may run through Laravel's queue system.

Start the worker with:

```bash
php artisan queue:work
```

Keep the worker running while using features that depend on background jobs.

---

## 🧪 Testing

Run the Laravel test suite:

```bash
php artisan test
```

You can also run PHPUnit directly:

```bash
./vendor/bin/phpunit
```

On Windows:

```powershell
vendor\bin\phpunit
```

---

## 🔍 Verification

The repository includes additional verification scripts.

For example:

```bash
php verify_crud.php
```

and:

```bash
php verify_e2e.php
```

Use these checks when validating a local installation or preparing a release.

---

## 📁 Project Structure

```text
VulnScope/
│
├── app/
│   ├── Exceptions/
│   ├── Http/
│   ├── Jobs/
│   ├── Models/
│   └── Services/
│
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
│
├── public/
├── resources/
│   └── views/
│
├── routes/
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── .env.example
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
├── phpunit.xml
├── README.md
├── SECURITY.md
├── CONTRIBUTING.md
└── LICENSE
```

---

## 🔐 Security & Responsible Use

VulnScope is a security testing tool.

You are responsible for ensuring that you have appropriate authorization before using it against any target.

### You should only test:

* Systems you own
* Applications you operate
* Infrastructure belonging to your organization
* Targets for which you have explicit written authorization
* Dedicated security-testing environments
* CTF/lab environments where testing is permitted

### Do not use VulnScope to:

* Scan systems without authorization
* Attempt to gain unauthorized access
* Disrupt production services
* Bypass access controls on systems you do not own
* Conduct denial-of-service activity
* Steal credentials or sensitive information
* Evade security controls for unauthorized purposes

Always respect the scope, rules of engagement, and testing limitations provided by the system owner.

---

## 🛡️ Safe Testing Practices

When performing an assessment:

1. Define the authorized target.
2. Define the assessment scope.
3. Confirm permission before testing.
4. Use the least disruptive testing approach appropriate for the assessment.
5. Avoid unnecessary collection of sensitive information.
6. Preserve evidence responsibly.
7. Document findings accurately.
8. Stop testing if the system becomes unstable or the authorization boundary is unclear.
9. Securely handle generated reports and assessment data.
10. Remove sensitive data when it is no longer required.

---

## 🐛 Reporting Bugs

If you discover a normal application bug, please open a GitHub issue with:

* Description of the problem
* Steps to reproduce
* Expected behavior
* Actual behavior
* Environment details
* Relevant logs
* Screenshots, if applicable

Please do **not** publish credentials, API keys, private data, or other sensitive information in an issue.

---

## 🔒 Reporting Security Vulnerabilities

If you discover a security vulnerability in VulnScope itself, please follow the instructions in [`SECURITY.md`](SECURITY.md).

Please avoid publicly disclosing an unpatched security vulnerability.

---

## 🤝 Contributing

Contributions are welcome.

Before submitting a pull request:

1. Fork the repository.
2. Create a feature branch.
3. Make your changes.
4. Add or update tests where appropriate.
5. Run the test suite.
6. Review your changes for accidentally committed secrets.
7. Submit a pull request.

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for additional information.

---

## 📜 License

VulnScope is released under the **MIT License**.

See [`LICENSE`](LICENSE) for the complete license text.

---

## ⚠️ Disclaimer

VulnScope is provided for legitimate security assessment, testing, research, and educational purposes.

The availability of a security-testing feature does not grant permission to use it against a particular system.

The user is solely responsible for obtaining authorization and complying with all applicable laws, regulations, contracts, and rules of engagement.

The project maintainers are not responsible for misuse of the software or for damage resulting from unauthorized testing.

---

## ⭐ Support the Project

If VulnScope is useful to you:

* ⭐ Star the repository
* 🐛 Report reproducible bugs
* 💡 Suggest improvements
* 🔧 Contribute code
* 📖 Improve documentation
* 🔐 Help improve the security of the project

---

## 📌 Project Status

VulnScope is an actively developed security assessment project.

Features and implementation details may change as the project evolves.

For the most accurate information, refer to the source code, tests, release notes, and documentation included in the repository.

---

**VulnScope — Security Assessment Made Organized.**
