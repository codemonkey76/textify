<p align="center"
[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Feea46f7e-aabb-45b1-b4b0-3916941e466a%3Fdate%3D1%26label%3D1%26commit%3D1&style=plastic)](https://forge.laravel.com/servers/836870/sites/2600873)
</p>

# Textify - Voicemail to SMS Transcription Service

Textify is a Laravel-based application that automatically transcribes voicemail attachments and sends them as SMS messages to configured destinations. It provides a seamless bridge between voice messages and text notifications.

## 🚀 Features

- Automatic voicemail transcription using AWS Transcribe
- SMS delivery via ClickSend API
- Multiple destination support per account
- Webhook-based email processing
- Asynchronous job processing
- Status tracking for transcriptions and SMS messages
- Comprehensive error handling and logging

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- MySQL/PostgreSQL
- AWS Account with Transcribe Service access
- ClickSend Account
- Laravel Queue Worker (Redis/Database)

## ⚙️ Installation

1. Clone the repository:
```bash
git clone https://github.com/codemonkey76/textify.git
cd textify
```

2. Install dependencies:
3. Copy the environment file:
4. Configure your environment variables in `.env`:
5. Generate application key
6. Run migrations:
7. Setup the queue worker:

## 🏗️ Architecture

The application follows a queue-based architecture for processing voicemails:

- Email with voicemail attachment is received via webhook
- Attachment is validated and stored
- Transcription job is queued
- AWS Transcribe processes the voicemail
- Transcription is sent via SMS to configured destinations
- Status tracking for both transcription and SMS delivery

## 🔒 Security

- Webhook signatures are verified
- Single WAV attachment validation
- Secure file storage
- API authrntication for external services

## 💻 API endpoints

### Webhook Endpoint
```bash
POST /inbound
```

Receives emails with voicemail attachments. Requires webhook signature verification.

## 🔧 Configuration

### AWS Transcribe

- Configure language settings in `config/services.php`
- Adjust transcription delay and retry settings
- Setup appropriate IAM permissions

### ClickSend

- Configure API credentials
- Setup webhook endpoints
- Monitor SMS delivery status

## 📝 Queue Management

The application uses Laravel's queue system for processing jobs:

- TranscribeVoicemail
- CheckTranscriptionStatus
- NotifyAccount
- NotifyDestination
- CheckSmsStatus

Monitor and manage queues using Laravel Horizon (if installed).

## 🛠️ Development

### Running Tests

```bash
php artisan test
```

### Code Style

The project follows PSR-12 coding standards.

## 📄 License

MIT License

## 👥 Contributing

- Fork the repository
- Create a feature branch
- Commit your changes
- Push to the branch
- Create a Pull Request

## ⚠️ Support

For support, please open an issue in the GitHub repository or contact the maintainers.

## 🙏 Acknowledgments

- Laravel Framework
- AWS Transcribe Service
- ClickSend API

