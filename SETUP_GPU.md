# Налаштування StoryVerse на GPU ПК

## Твій ПК
- **GPU:** RTX 4070 SUPER (12GB VRAM)
- **RAM:** 32GB DDR5
- **CPU:** Ryzen 5 7500F

Цього достатньо для **13B uncensored моделі** на GPU!

---

## Швидкий старт

### 1. Клонування та налаштування

```bash
# Клонуй репозиторій
git clone <repo-url> storyverse
cd storyverse

# Скопіюй .env
cp backend/.env.example backend/.env

# Згенеруй APP_KEY
docker compose run --rm app php artisan key:generate
```

### 2. Додай свої API ключі в backend/.env

```env
GEMINI_API_KEY=твій_ключ
CLAUDE_API_KEY=твій_ключ
OPENAI_API_KEY=твій_ключ
```

### 3. Запуск Docker з GPU підтримкою

```bash
# Встанови NVIDIA Container Toolkit (якщо ще не встановлено)
# https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/install-guide.html

# Запусти все
docker compose up -d

# Перевір що GPU видно в Ollama
docker exec storyverse-ollama nvidia-smi
```

### 4. Завантаж uncensored модель

```bash
# Для твого GPU (12GB VRAM) - оптимальна модель:
docker exec storyverse-ollama ollama pull wizard-vicuna-uncensored:13b

# Або ще краща якість (може бути трохи повільніше):
docker exec storyverse-ollama ollama pull nous-hermes-2:13b
```

### 5. Запусти міграції

```bash
docker compose exec app php artisan migrate --seed
```

### 6. Відкрий в браузері

- Frontend: http://localhost:5173
- Backend API: http://localhost:8888

---

## Поточний стан проекту

### Що працює:
- Реєстрація/логін користувачів
- Створення/редагування історій та глав
- AI генерація тексту (Gemini/Claude/OpenAI)
- 18+ режим в редакторі глав (використовує Ollama)
- Laravel Policies для авторизації
- SOLID архітектура

### Що потрібно доробити:
- [ ] Протестувати Ollama з 13B моделлю на GPU
- [ ] Перевірити якість українського тексту від uncensored моделі
- [ ] Adventure Mode (AI Dungeon Master)
- [ ] Collaborative writing
- [ ] Image generation (DALL-E / Stable Diffusion)

---

## Рекомендовані Ollama моделі для 18+ контенту

| Модель | VRAM | Якість | Примітка |
|--------|------|--------|----------|
| wizard-vicuna-uncensored:7b | 6GB | Середня | Швидка, базова |
| wizard-vicuna-uncensored:13b | 10GB | Хороша | **Рекомендую для твого GPU** |
| nous-hermes-2:13b | 10GB | Відмінна | Краще слідує інструкціям |
| dolphin-mixtral:8x7b | 26GB | Найкраща | Потрібно 24GB+ VRAM |

---

## Тестування 18+ AI

```bash
# Перевір що модель працює
curl http://localhost:11434/api/generate -d '{
  "model": "wizard-vicuna-uncensored:13b",
  "prompt": "Напиши короткий романтичний уривок українською мовою.",
  "stream": false
}'
```

Якщо відповідь приходить швидко і українською — все працює!

---

## Troubleshooting

### GPU не видно в Docker
```bash
# Перевір NVIDIA drivers
nvidia-smi

# Перевір Container Toolkit
docker run --rm --gpus all nvidia/cuda:12.0-base nvidia-smi
```

### Модель не завантажується (OOM)
Спробуй менший квант:
```bash
docker exec storyverse-ollama ollama pull wizard-vicuna-uncensored:13b-q4_0
```

### Повільна генерація
Переконайся що модель на GPU:
```bash
docker logs storyverse-ollama | grep -i "gpu\|cuda"
```
