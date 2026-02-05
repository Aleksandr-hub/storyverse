# StoryVerse — AI-Powered Creative Writing Platform

## Концепція

**StoryVerse** — платформа для творчого писання з AI, яка об'єднує:
- 📝 Написання фанфіків та оригінальних історій
- 🤝 Спільне писання з друзями
- 🎮 AI Adventure режим (як D&D з AI Dungeon Master)
- 🎨 Генерація ілюстрацій до історій

**Автор:** Oleksandr Prytuliak
**Мета:** Pet project для портфоліо + потенційний продукт

---

## Унікальність

| Конкурент | Що вони мають | Чого не мають |
|-----------|---------------|---------------|
| Wattpad | Публікація історій | AI, українська, adventure mode |
| AO3 | Фанфіки | AI, сучасний UI |
| AI Dungeon | AI adventure | Спільнота, фанфіки, українська |
| NovelAI | AI writing | Спільне писання, спільнота |

**StoryVerse = Wattpad + AI Dungeon + українська локалізація**

---

## Технології

### Backend
- **PHP 8.4**
- **Laravel 12**
- **PostgreSQL 17** — основна БД
- **Redis 7.4** — кеш, сесії, real-time
- **RabbitMQ** — черги для AI запитів
- **ElasticSearch 8.x** — пошук історій, персонажів

### Frontend
- **Vue 3** + Composition API
- **Vite** — build tool
- **Pinia** — state management
- **TailwindCSS** — стилі
- **Socket.io** — real-time для collaborative writing

### AI & Media
- **Claude API** — генерація тексту
- **OpenAI DALL-E / Replicate** — генерація картинок
- **Whisper API** — voice-to-text (майбутнє)

### DevOps
- **Docker + Docker Compose**
- **GitHub Actions** — CI/CD
- **Prometheus + Grafana** — моніторинг

---

## Основні фічі

### 1. 📚 Story Mode (Фанфіки)
```
- Створення історій з AI-помічником
- Вибір всесвіту (Marvel, HP, Witcher, Original)
- AI знає канон — підказує, виправляє
- Персонажі з канону або свої
- Публікація, коментарі, рейтинги
- Теги, жанри, пошук
```

### 2. 🤝 Collaborative Mode
```
- Запрошення друзів до історії
- Писання по черзі
- Real-time редагування
- AI може бути одним з "авторів"
- Історія чату поруч
- Голосування за напрямок сюжету
```

### 3. 🎮 Adventure Mode (AI DM)
```
- AI веде гру як Dungeon Master
- Створюєш персонажа (клас, навички)
- Описуєш дії → AI описує результат
- Випадкові події, бої, діалоги
- Збереження прогресу
- Можна грати з друзями
```

### 4. 🎨 AI Illustrations
```
- Генерація обкладинок
- Ілюстрації до сцен
- Портрети персонажів
- Різні стилі (аніме, реалізм, фентезі)
```

### 5. 👥 Community
```
- Профілі авторів
- Підписки, фоловери
- Коментарі, рейтинги
- Конкурси та челенджі
- Рекомендації на основі вподобань
```

---

## Архітектура

```
┌─────────────────────────────────────────────────────────────────┐
│                      Frontend (Vue 3 + Vite)                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │   Editor    │  │  Adventure  │  │  Community  │              │
│  │  (Writing)  │  │   (Game)    │  │  (Social)   │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
└─────────────────────────────┬───────────────────────────────────┘
                              │ REST API + WebSocket
┌─────────────────────────────▼───────────────────────────────────┐
│                      API Gateway (Laravel 12)                   │
│                         Sanctum Auth                            │
├─────────────────────────────────────────────────────────────────┤
│                          Services                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │   Auth   │  │  Story   │  │Adventure │  │   AI     │        │
│  │ Service  │  │ Service  │  │ Service  │  │ Service  │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │  Media   │  │  Search  │  │  Social  │  │  Notify  │        │
│  │ Service  │  │ Service  │  │ Service  │  │ Service  │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
├─────────────────────────────────────────────────────────────────┤
│                        Message Queue                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                      RabbitMQ                             │  │
│  │   [ai_requests] [image_gen] [notifications] [search]     │  │
│  └──────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────┤
│                         Data Layer                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │PostgreSQL│  │  Redis   │  │  Elastic │  │   S3     │        │
│  │ (data)   │  │ (cache)  │  │ (search) │  │ (media)  │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    External Services                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                      │
│  │  Claude  │  │  DALL-E  │  │  OAuth   │                      │
│  │   API    │  │   API    │  │ (Google) │                      │
│  └──────────┘  └──────────┘  └──────────┘                      │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Core Tables

```sql
-- Користувачі
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    avatar_url VARCHAR(500),
    bio TEXT,
    oauth_provider VARCHAR(50),
    oauth_id VARCHAR(255),
    is_premium BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Всесвіти (Marvel, HP, Original...)
CREATE TABLE universes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    cover_url VARCHAR(500),
    is_official BOOLEAN DEFAULT FALSE,  -- Канонічний чи user-created
    creator_id UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Персонажі
CREATE TABLE characters (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    universe_id UUID REFERENCES universes(id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    avatar_url VARCHAR(500),
    traits JSONB,  -- {"personality": "brave", "skills": ["magic"]}
    is_canonical BOOLEAN DEFAULT FALSE,
    creator_id UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Історії
CREATE TABLE stories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(500) NOT NULL,
    slug VARCHAR(500) NOT NULL,
    description TEXT,
    cover_url VARCHAR(500),
    universe_id UUID REFERENCES universes(id),
    author_id UUID REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'draft',  -- draft, published, completed
    mode VARCHAR(50) DEFAULT 'story',    -- story, collaborative, adventure
    is_public BOOLEAN DEFAULT TRUE,
    rating VARCHAR(10) DEFAULT 'G',      -- G, PG, PG-13, R
    word_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    settings JSONB,  -- Mode-specific settings
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    published_at TIMESTAMP
);

-- Глави/епізоди історії
CREATE TABLE chapters (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    title VARCHAR(500),
    content TEXT,
    chapter_number INTEGER NOT NULL,
    word_count INTEGER DEFAULT 0,
    author_id UUID REFERENCES users(id),  -- Для collaborative
    is_ai_generated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Теги
CREATE TABLE tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(50)  -- genre, warning, character, etc.
);

CREATE TABLE story_tags (
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    tag_id UUID REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (story_id, tag_id)
);

-- Персонажі в історії
CREATE TABLE story_characters (
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    character_id UUID REFERENCES characters(id) ON DELETE CASCADE,
    role VARCHAR(50),  -- protagonist, antagonist, supporting
    PRIMARY KEY (story_id, character_id)
);

-- Ілюстрації
CREATE TABLE illustrations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    story_id UUID REFERENCES stories(id),
    chapter_id UUID REFERENCES chapters(id),
    image_url VARCHAR(500) NOT NULL,
    prompt TEXT,  -- AI prompt used
    position INTEGER,  -- Order in chapter
    created_at TIMESTAMP DEFAULT NOW()
);
```

### Social Tables

```sql
-- Підписки
CREATE TABLE follows (
    follower_id UUID REFERENCES users(id) ON DELETE CASCADE,
    following_id UUID REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (follower_id, following_id)
);

-- Вподобання
CREATE TABLE likes (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (user_id, story_id)
);

-- Коментарі
CREATE TABLE comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    chapter_id UUID REFERENCES chapters(id),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    parent_id UUID REFERENCES comments(id),  -- Для відповідей
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Закладки
CREATE TABLE bookmarks (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    chapter_id UUID REFERENCES chapters(id),  -- Last read chapter
    created_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (user_id, story_id)
);
```

### Adventure Mode Tables

```sql
-- Сесії пригод
CREATE TABLE adventure_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    status VARCHAR(50) DEFAULT 'active',  -- active, paused, completed
    current_scene JSONB,  -- Current game state
    history JSONB[],      -- Array of past actions/responses
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Персонажі гравців
CREATE TABLE player_characters (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id UUID REFERENCES adventure_sessions(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id),
    name VARCHAR(255) NOT NULL,
    class VARCHAR(100),
    stats JSONB,       -- {"strength": 10, "magic": 15}
    inventory JSONB,   -- ["sword", "potion"]
    health INTEGER DEFAULT 100,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Collaborators для спільних історій
CREATE TABLE story_collaborators (
    story_id UUID REFERENCES stories(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) DEFAULT 'writer',  -- owner, writer, reader
    can_edit BOOLEAN DEFAULT TRUE,
    joined_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (story_id, user_id)
);
```

---

## API Endpoints

### Auth
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/oauth/{provider}
```

### Stories
```
GET    /api/stories                    # List (with filters)
POST   /api/stories                    # Create
GET    /api/stories/{slug}             # Get story
PUT    /api/stories/{id}               # Update
DELETE /api/stories/{id}               # Delete
POST   /api/stories/{id}/publish       # Publish

GET    /api/stories/{id}/chapters      # List chapters
POST   /api/stories/{id}/chapters      # Add chapter
PUT    /api/chapters/{id}              # Update chapter
DELETE /api/chapters/{id}              # Delete chapter
```

### AI
```
POST   /api/ai/generate                # Generate text
POST   /api/ai/continue                # Continue story
POST   /api/ai/suggest                 # Get suggestions
POST   /api/ai/illustrate              # Generate image
POST   /api/ai/check-canon             # Check against universe canon
```

### Adventure
```
POST   /api/adventures                 # Start new adventure
GET    /api/adventures/{id}            # Get adventure state
POST   /api/adventures/{id}/action     # Player action
POST   /api/adventures/{id}/join       # Join adventure
DELETE /api/adventures/{id}            # End adventure
```

### Social
```
POST   /api/users/{id}/follow          # Follow user
DELETE /api/users/{id}/follow          # Unfollow
GET    /api/users/{id}/followers       # Get followers
GET    /api/users/{id}/following       # Get following

POST   /api/stories/{id}/like          # Like story
DELETE /api/stories/{id}/like          # Unlike
POST   /api/stories/{id}/bookmark      # Bookmark
DELETE /api/stories/{id}/bookmark      # Remove bookmark

GET    /api/stories/{id}/comments      # Get comments
POST   /api/stories/{id}/comments      # Add comment
DELETE /api/comments/{id}              # Delete comment
```

### Search
```
GET    /api/search/stories             # Search stories
GET    /api/search/users               # Search users
GET    /api/search/characters          # Search characters
GET    /api/universes                  # List universes
GET    /api/universes/{slug}           # Get universe
```

---

## Фази розробки

### Phase 1: Foundation (MVP)
- [ ] Laravel 12 project setup
- [ ] Docker Compose (PostgreSQL, Redis)
- [ ] Auth (email + Google OAuth)
- [ ] Basic Story CRUD
- [ ] Vue 3 frontend scaffold
- [ ] Simple text editor

### Phase 2: AI Integration
- [ ] Claude API integration
- [ ] AI text generation
- [ ] AI continue story
- [ ] AI suggestions
- [ ] RabbitMQ for async AI requests

### Phase 3: Community
- [ ] User profiles
- [ ] Follow system
- [ ] Likes, comments, bookmarks
- [ ] Story discovery/recommendations
- [ ] ElasticSearch integration

### Phase 4: Advanced Writing
- [ ] Rich text editor
- [ ] Universes & characters
- [ ] Tags & categories
- [ ] Story series

### Phase 5: Collaborative Mode
- [ ] Real-time collaboration (WebSockets)
- [ ] Turn-based writing
- [ ] AI as collaborator
- [ ] Chat alongside story

### Phase 6: Adventure Mode
- [ ] Adventure session management
- [ ] AI Dungeon Master
- [ ] Character creation
- [ ] Multiplayer adventures

### Phase 7: Media
- [ ] Image generation (DALL-E)
- [ ] Cover images
- [ ] Chapter illustrations
- [ ] Character portraits

### Phase 8: Polish
- [ ] CI/CD pipeline
- [ ] Monitoring (Prometheus + Grafana)
- [ ] Performance optimization
- [ ] Mobile responsiveness
- [ ] Ukrainian localization

---

## Монетизація (майбутнє)

| Tier | Price | Features |
|------|-------|----------|
| Free | $0 | 10 AI requests/day, ads |
| Pro | $5/mo | Unlimited AI, no ads, priority |
| Team | $15/mo | Collaboration, team features |

---

## Структура проекту

```
storyverse/
├── docker/
│   ├── php/
│   ├── nginx/
│   ├── node/
│   └── worker/
├── backend/                  # Laravel 12
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   │   ├── AI/
│   │   │   ├── Story/
│   │   │   └── Adventure/
│   │   └── Jobs/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── tests/
├── frontend/                 # Vue 3 + Vite
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── stores/
│   │   ├── composables/
│   │   └── services/
│   ├── public/
│   └── tests/
├── docs/
├── .github/
│   └── workflows/
├── docker-compose.yml
├── docker-compose.dev.yml
├── Makefile
└── README.md
```

---

## Команди

```bash
# Start development
make dev

# Run backend tests
make test-backend

# Run frontend tests
make test-frontend

# Generate API docs
make docs

# Deploy
make deploy
```

---

## Ресурси

- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Vue 3 Docs](https://vuejs.org/)
- [Claude API](https://docs.anthropic.com/)
- [Socket.io](https://socket.io/)
- [TailwindCSS](https://tailwindcss.com/)

---

## Notes

- Почати з MVP (Story mode only)
- AI features додавати поступово
- Спочатку один всесвіт (тестовий)
- Mobile-first design
- Українська мова — пріоритет
