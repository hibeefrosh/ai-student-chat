# 🎓 AI Course Tutor

An intelligent tutoring system that uses AI to help students learn course materials through interactive chat.

---

## ✨ Features

- 🤖 AI-powered chat interface for students to ask questions about course materials
- 📚 Course material management for instructors
- 🔍 Semantic search to find relevant content for student questions
- 👤 User-friendly interface with real-time responses
- 🔒 Secure authentication for administrators

---

## 🛠️ Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Livewire, Tailwind CSS
- **AI**: Google Gemini API
- **Database**: MySQL or PostgreSQL
- **Authentication**: Laravel Breeze

---

## 🚀 Installation

### Prerequisites

- PHP 8.1+
- Composer
- Node.js & NPM
- MySQL or PostgreSQL
- Google Gemini API key

### Setup Instructions

1. **Clone the repository**

   ```bash
   git clone https://github.com/hibeefrosh/ai-student-chat.git
   cd ai-course-tutor
   ```

2. **Install dependencies**

   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Set up the environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your database in the `.env` file**

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ai_course_tutor
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Configure your Gemini API key in the `.env` file**

   ```env
   GEMINI_API_KEY=your_api_key_here
   GEMINI_MODEL=gemini-2.5-flash
   ```

6. **Run migrations and seed the database**

   ```bash
   php artisan migrate --seed
   ```

7. **Start the development server**

   ```bash
   php artisan serve
   ```

---

## 🧠 How It Works

1. **Administrators upload course materials** through the admin dashboard.
2. **The system processes and chunks materials** for efficient semantic retrieval.
3. **Students ask questions** through the chat interface.
4. **The AI searches for relevant content** from the uploaded materials.
5. **The AI generates helpful, accurate responses** based on course content.

---

## 👥 User Roles

- **Students**: Can chat with AI to learn about uploaded course materials.
- **Administrators**: Manage courses, upload materials, and review chat sessions.

---

## 📁 Project Structure

```
ai-course-tutor/
├── app/
│   ├── Http/               # Controllers and middleware
│   ├── Livewire/           # Livewire components
│   ├── Models/             # Eloquent models
│   └── Services/           # Service classes (AI integration, etc.)
├── config/                 # Configuration files
├── database/               # Migrations and seeders
├── public/                 # Publicly accessible files
├── resources/              # Views and assets
│   ├── css/
│   ├── js/
│   └── views/
├── routes/                 # Web routes
└── storage/                # Application storage
```

---

## 🔧 Advanced Configuration

### Customizing AI Behavior

Modify the `AIService.php` to customize:

- Context window size
- Response temperature
- Prompt engineering strategies

### Database Optimization

For large courses:

- Add database indexes for faster searching.
- Implement caching for frequently asked queries.
- Tune chunk sizes for better storage and retrieval.

---

## 🔍 Troubleshooting

### Common Issues

| Issue | Solution |
|:------|:---------|
| AI responses are not relevant | Check uploaded materials, validate chunking, and verify Gemini API integration |
| Slow response times | Optimize database queries and server performance |
| Authentication problems | Clear browser cache, reset passwords, and verify Laravel Breeze setup |

---

## 🔄 Updates & Maintenance

To update your application:

```bash
git pull
composer install
npm install
npm run build
php artisan migrate
```

---

## 🤝 Contributing

Contributions are welcome! 🚀

1. Fork this repository.
2. Create a new feature branch (`git checkout -b feature/amazing-feature`).
3. Commit your changes (`git commit -m 'Add amazing feature'`).
4. Push to your branch (`git push origin feature/amazing-feature`).
5. Open a Pull Request.


---

## 🙏 Acknowledgements

- [Laravel](https://laravel.com)
- [Livewire](https://livewire.laravel.com)
- [Tailwind CSS](https://tailwindcss.com)
- [Google Gemini API](https://ai.google.dev/)
- [All contributors](https://github.com/hibeefrosh/ai-student-chat.git)

---

## 📱 Mobile Support

- Fully responsive design
- Optimized for desktop, tablet, and mobile devices


---

## 📈 Analytics

Administrators can view:

- Usage statistics
- Popular student questions
- Engagement metrics
- AI response performance reports

---

## ❓ FAQ

**Q: Can I use my existing course materials?**  
**A:** Yes! The system supports PDF, Word documents, and plain text uploads.

**Q: How accurate are the AI responses?**  
**A:** The AI generates highly accurate answers based on your uploaded course materials.

**Q: Is student data private?**  
**A:** Absolutely. All conversations are private and securely stored.

---

## 📞 Support

- Open an issue on GitHub
- Email: **ibrahimsobande191@gmail.com**

---

# 🚀 Ready to get started?
> Clone the repo, upload your course materials, and empower your students with AI today!

---

