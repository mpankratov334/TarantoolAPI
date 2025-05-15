# TarantoolAPI
part of php framework

---

#### Содержание
0. **Языки**: PHP (API), Lua (расширение функциональности Tarantool).
1. **Архитектура**: ADR (разделение на Actions, Domains, Responder).
2. **База данных**: Tarantool (NoSQL + Lua для гибридной логики).
3. **Тестирование**: Интеграционные тесты через HTTP-запросы.

---

# Архитектурный обзор проекта

## Модель: **ADR (Action-Domain-Responder)**

### 1. **API**
**Написано с помощью**: PHP (Laravel/Symfony-like подход)  
**Описание**:
  
- **Actions**
 - Папка `Actions` содержит обработчики запросов для каждой сущности 
 - Реализованы CRUD-операции для сущностей `event`, `task`, `user` через RESTful API.
- **Domains**:
  - Папка `Domains` инкапсулирует бизнес-логику:
  	- `EventDomain`: Управление событиями, задачами и их валидацией.
  	- `UserDomain`: Работа с пользователями и их настройками.
- **Responder**: 
	- Маршрутизация (`routes.php`) определяет, как запросы сопоставляются с действиями (Actions).

---

### 2. **База данных**
**Написана с помощью**: **Tarantool** (мультимодельная NoSQL СУБД) + Lua  
**Описание**:
 - **Файл `Db.php`**:
	- Реализует низкоуровневое взаимодействие с **Tarantool**:
  	- Управление спейсами: `makeSpace`, `truncate`, `drop`, `setSpace`.
  	- CRUD-операции: `insert`, `select`, `update`, `delete`, `upsert`.
  	- Расширенные методы: `evaluate` (исполнение Lua-кода), `indexSelect` (поиск по индексам).
  - **Lua-интеграция**:
  	- Использование `evaluate` для выполнения Lua-скриптов внутри Tarantool.
  	- Конвертация данных между PHP и Lua (`convertArrayToLuaStr`).

---

### 3. **Тестирование**
**Локальные HTTP-тесты**:
**Тип**: Интеграционные тесты API.
**Инструмент**: Файлы `.http` (формат Postman).
**Покрытие**:
- CRUD для событий (`/event/create`, `/event/delete`, `/event/update`).
- Управление задачами (`/task/create`, `/task/delete`).
- Работа с пользователями (`/user/create`).

---

### Схема взаимодействия компонентов
``` 
[Клиент]
→ [API (routes.php)]
→ [Action]
→ [Domain (бизнес-логика)]
→ [Db.php (Tarantool)]
→ [Lua-скрипты (в Tarantool)]
```
