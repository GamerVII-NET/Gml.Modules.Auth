# WebMCR Launcher Authentication API

## Описание
API для аутентификации пользователей в лаунчере игры. Поддерживает все методы шифрования паролей WebMCR.

## Endpoint
```
POST https://your-domain.com/index.php?mode=ajax&do=launcher_auth
```

## Заголовки запроса
```
Content-Type: application/json; charset=utf-8
```

## Формат запроса
```json
{
    "Login": "username",
    "Password": "user_password"
}
```

## Формат ответа

### Успешная авторизация (200 OK)
```json
{
    "Login": "username",
    "UserUuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
    "Message": "Успешная авторизация"
}
```

### Ошибки
- **400 Bad Request** - Отсутствуют обязательные поля
```json
{
    "Message": "Login and Password are required"
}
```

- **401 Unauthorized** - Неверные учетные данные
```json
{
    "Message": "Неверный логин или пароль"
}
```

- **403 Forbidden** - Пользователь заблокирован или нет прав
```json
{
    "Message": "Пользователь заблокирован"
}
```
или
```json
{
    "Message": "Пользователь не имеет доступа"
}
```

- **404 Not Found** - Пользователь не найден
```json
{
    "Message": "Пользователь не найден"
}
```

- **405 Метод аунтификации** - Неверный метод запроса
```json
{
    "Message": "Неверный метод запроса"
}
```

## Требования к базе данных
1. Таблица пользователей (`mcr_users`) должна содержать поля:
   - `id` - ID пользователя
   - `login` - Логин пользователя
   - `password` - Хэш пароля
   - `salt` - Соль для пароля
   - `gid` - ID группы пользователя
   - `ban_server` - Статус бана
   - `uuid` - UUID пользователя (опционально)

2. Таблица групп (`mcr_groups`) должна содержать поля:
   - `id` - ID группы
   - `permissions` - Права группы в JSON формате

## Права доступа
Для успешной авторизации пользователь должен иметь право `sys_auth` в permissions своей группы.

## Примеры использования

### cURL
```bash
curl -X POST \
  'https://your-domain.com/index.php?mode=ajax&do=launcher_auth' \
  -H 'Content-Type: application/json; charset=utf-8' \
  -d '{
    "Login": "username",
    "Password": "password123"
}'
```

## Установка
1 - Переходим в `modules > ajax`

2 - Заливаем наш файл `auth..php`

3 - Переходим в главную директорию и открываем `system.php`

4 - В самом конце находим `$core->csrf_check();`, и редактируем его, чтоб оно было таким:


```json
if (!isset($_GET['mode']) || $_GET['mode'] !== 'ajax') {
    $core->csrf_check();
}
```

## Безопасность
1. API поддерживает CORS и принимает запросы с любого домена
2. Все пароли хэшируются с использованием различных методов шифрования WebMCR
3. Используется защита от CSRF-атак для не-AJAX запросов 
