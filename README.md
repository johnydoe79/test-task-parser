# test-task-parser

# Инструкция по развертыванию и запуску тестов

## Требования
- Docker >= 20.10
- Docker Compose >= 1.29
- Git (для клонирования репозитория)

## Шаги установки

1. **Склонируйте проект:**
   ```bash
   git clone https://github.com/johnydoe79/test-task-parser.git
   cd test-task-parser

2. Соберите контейнеры и запустите:
docker compose up --build -d 

3. Установите зависимости
docker exec -it symfony_php composer install

# Запуск тестов
docker exec -it symfony_php vendor/bin/phpunit


