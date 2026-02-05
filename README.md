# 📖 StoryVerse

AI-powered creative writing platform for fanfiction, collaborative storytelling, and AI adventures.

## Features

- 📝 **Story Mode** — Write stories with AI assistance
- 🤝 **Collaborative Mode** — Write together with friends
- 🎮 **Adventure Mode** — AI Dungeon Master experience
- 🎨 **AI Illustrations** — Generate images for your stories
- 👥 **Community** — Share, comment, follow authors

## Tech Stack

### Backend
- PHP 8.4 + Laravel 12
- PostgreSQL 17
- Redis 7.4
- RabbitMQ
- ElasticSearch 8.x

### Frontend
- Vue 3 + Vite
- Pinia
- TailwindCSS
- Socket.io

### AI
- Claude API (text generation)
- DALL-E API (image generation)

### DevOps
- Docker + Docker Compose
- GitHub Actions
- Prometheus + Grafana

## Quick Start

```bash
# Clone repository
git clone https://github.com/yourusername/storyverse.git
cd storyverse

# Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Start services
docker-compose up -d

# Install dependencies & migrate
docker-compose exec backend composer install
docker-compose exec backend php artisan migrate --seed
docker-compose exec frontend npm install

# Start development
docker-compose exec frontend npm run dev
```

## URLs (Development)

| Service | URL |
|---------|-----|
| Frontend | http://localhost:3000 |
| API | http://localhost:8000 |
| API Docs | http://localhost:8000/api/documentation |
| RabbitMQ | http://localhost:15672 |
| Mailpit | http://localhost:8025 |

## Documentation

- [API Guide](docs/API.md)
- [Architecture](CLAUDE.md)
- [Contributing](CONTRIBUTING.md)

## Author

**Oleksandr Prytuliak**

## License

MIT
