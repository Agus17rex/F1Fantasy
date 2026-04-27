# F1 Fantasy League — TFG DAW

Proyecto de Trabajo de Fin de Grado para el ciclo superior de Desarrollo de Aplicaciones Web.

## Stack Tecnológico

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 + Vite + Pinia + Vue Router
- **Base de datos**: MySQL 8.0
- **API Externa**: Jolpica F1 API (Open Source, sustituta de Ergast)
- **Autenticación**: Laravel Sanctum (SPA tokens)

## Estructura del Proyecto

```
f1-fantasy/
├── backend/          # API REST Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/        # Controladores públicos/usuario
│   │   │   │   └── Admin/      # Controladores panel admin
│   │   │   ├── Middleware/
│   │   │   └── Requests/       # Form Requests (validación)
│   │   ├── Models/             # Eloquent Models
│   │   └── Services/           # Lógica de negocio
│   ├── database/
│   │   ├── migrations/         # Esquema de BBDD
│   │   └── seeders/            # Datos iniciales
│   └── routes/
│       └── api.php             # Rutas de la API
└── frontend/         # SPA Vue 3
    └── src/
        ├── views/              # Páginas
        ├── components/         # Componentes reutilizables
        ├── stores/             # Pinia stores (estado global)
        ├── services/           # Llamadas a la API
        └── router/             # Vue Router
```

## Módulos del Sistema

### 1. Autenticación
- Registro / Login / Logout
- Roles: `user`, `admin`
- Tokens Sanctum para SPA

### 2. Fantasy
- Crear / unirse a ligas privadas
- Selección de equipo (pilotos + escudería)
- Presupuesto limitado (100M €)
- Transferencias entre rondas

### 3. Carreras y Puntuación
- Sincronización con API F1 (Jolpica)
- Puntuación automática basada en resultados
- Panel admin para revisar/corregir puntos
- Clasificación en tiempo real

### 4. API Externa (Jolpica F1 API)
- Endpoint base: `https://api.jolpi.ca/ergast/f1/`
- Pilotos de la temporada actual
- Escuderías (constructors)
- Calendario de carreras
- Resultados de carrera

## Instalación Rápida

### Backend
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

## Variables de Entorno (Backend)

```env
APP_NAME="F1 Fantasy"
APP_URL=http://localhost:8000
DB_DATABASE=f1_fantasy
DB_USERNAME=root
DB_PASSWORD=secret
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```
