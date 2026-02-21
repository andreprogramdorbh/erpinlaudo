# Inlaudo ERP - AI Coding Agent Instructions

## Project Overview

This is a custom PHP 8.0+ MVC framework (SaaS base) with integrated ERP features. The app uses a flat, single-vendor namespace (`App\`), minimal dependencies (only phpdotenv), and homemade routing/middleware system. Multi-tenant architecture centered on `usuario_id` data isolation.

## Architecture Overview

### Request Flow
1. **Entry**: `public/index.php` → `app/bootstrap.php` (loads .env, validates critical vars, sets error handling)
2. **Routing**: `app/Core/Router.php` matches HTTP method + URI → executes middleware chain → instantiates controller
3. **Controller Layer**: `app/Controllers/*` extends `App\Core\Controller` (currently minimal)
4. **Model Layer**: `app/Models/*` extends `App\Core\Model` → accesses PDO via `App\Core\Database::getInstance()`
5. **View Layer**: `View::render('folder/view')` → concatenates header + content + footer layouts

### Key Components

| Component | File | Purpose |
|-----------|------|---------|
| **Router** | [app/Core/Router.php](app/Core/Router.php) | Static registration of routes with middleware groups |
| **Database** | [app/Core/Database.php](app/Core/Database.php) | PDO singleton, loads config from `config/database.php` |
| **Auth** | [app/Core/Auth.php](app/Core/Auth.php) | Session-based login, Argon2ID hashing, permission checking |
| **Permission** | [app/Core/Permission.php](app/Core/Permission.php) | Role-based access (superadmin, financeiro, operador, leitura) |
| **Middleware** | [app/Middlewares/*](app/Middlewares/) | AuthMiddleware, PermissionMiddleware, CsrfMiddleware, SessionTimeoutMiddleware |
| **Logger** | [app/Core/Logger.php](app/Core/Logger.php) | Writes to `storage/logs/` (dev vs prod error detail levels) |

## Critical Patterns

### 1. Routing with Middleware Groups
```php
// From routes/web.php
Router::group(["middleware" => ["Permission:view_clients"]], function () {
    Router::get("/clientes", "ClientesController@index");
    Router::post("/clientes/store", "ClientesController@store");
});
```
- Middleware applied in **registration order**, checked before controller execution
- Colon syntax `Permission:view_clients` passes argument to middleware
- Routes without middleware group default to `[]`

### 2. Data Isolation via usuario_id
All models assume multi-tenant isolation:
```php
// Models always filter by user context
$clientes = $this->clienteModel->findByUsuarioId($usuarioId, 'ativo');
// NEVER query without usuario_id constraint when displaying user data
```
**Do not skip this in new features—security depends on it.**

### 3. Session & Auth State
```php
// Login sets these:
$_SESSION['user_id']     // Primary key for data isolation
$_SESSION['user_role']   // Lowercase role (e.g., 'superadmin', 'financeiro')
$_SESSION['login_time']  // Timestamp, checked by SessionTimeoutMiddleware
```
**Use `Auth::check()` and `Auth::user()` for current user context, never assume $_SESSION exists.**

### 4. View Rendering with Layout Injection
```php
View::render('clientes/index', ['title' => 'Clientes', 'data' => $data]);
```
- Renders `app/Views/clientes/index.php` wrapped in `app/Views/layout/header.php` + `app/Views/layout/footer.php`
- Extract variables are available in view scope; use `$title`, `$data` directly
- CSRF token available via `View::csrfField()` helper

### 5. Error Handling & Logging
- **Dev**: Full error display + Logger::debug() to `storage/logs/`
- **Prod**: Generic "unexpected error" message + Logger::error() (no trace details exposed)
- **Bootstrap**: Validates critical .env vars early (DB_HOST, DB_DATABASE, DB_USERNAME) → exit if missing

## Common Tasks

### Adding a New Route with Permission Check
1. Define route in [routes/web.php](routes/web.php):
   ```php
   Router::group(["middleware" => ["Permission:manage_contracts"]], function () {
       Router::get("/contratos", "ContratosController@index");
       Router::post("/contratos/store", "ContratosController@store");
   });
   ```
2. Add permission to role in [app/Core/Permission.php](app/Core/Permission.php)
3. Create controller in `app/Controllers/ContratosController.php`

### Adding a New Model Method
1. Extend [app/Core/Model.php](app/Core/Model.php) base class (inherits PDO via `$this->pdo`)
2. Use prepared statements with params: `$this->pdo->prepare($sql)->execute([$param])`
3. Always apply `usuario_id` filter for multi-tenant isolation
4. Return `PDO::FETCH_OBJ` (already configured in Database singleton)

### Creating a View Template
- File path: `app/Views/folder/file.php` (converted from dot notation `folder.file`)
- Layout auto-injected; write only the content body
- Access extracted data directly: `<?php echo htmlspecialchars($title); ?>`
- Include CSRF field: `<?php echo View::csrfField(); ?>`

## Environment & Config

- **File**: `.env` (loaded by `app/bootstrap.php`)
- **Critical vars** (required or app exits): `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`
- **Config loader**: `config/database.php` reads from `$_ENV`, fallback to defaults for non-critical vars
- **Test connection**: Navigate to `/test_env.php` in dev (remove after validation)

## Dependency Injection Notes

No built-in DI container. Controllers instantiate dependencies directly:
```php
public function __construct() {
    $this->clienteModel = new Cliente();
    $this->logger = new Logger();
}
```
Database singleton accessed via `Database::getInstance()` in Model base class.

## Testing & Debugging

- **Logs location**: `storage/logs/app.log` (created by Logger)
- **Debug endpoint**: `public/test_env.php` validates .env loading and DB connection
- **Router param parsing**: Limited; assumes numeric ID at end of URI (e.g., `/clientes/edit/123` → ID=123)
  - For complex routing, may need enhancement to Router class

## Common Pitfalls

1. **Missing usuario_id in queries**: User-specific data will leak across tenants
2. **Assuming $_SESSION exists**: Always check `Auth::check()` first
3. **Middleware not registered**: If Permission denied but role should pass, verify permission string in [app/Core/Permission.php](app/Core/Permission.php)
4. **View file path mismatch**: Dot notation converts to folder structure (e.g., `clientes.edit` → `app/Views/clientes/edit.php`)
5. **Not regenerating session ID on login**: Already handled by `Auth::login()` → `Auth::regenerateSession()`
