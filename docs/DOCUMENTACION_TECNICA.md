# Documentación Técnica — F1 Fantasy League
## TFG Grado Superior DAW

---

## 1. Descripción del Proyecto

**F1 Fantasy League** es una aplicación web tipo liga fantasy basada en la Fórmula 1, similar a la Fantasy Premier League pero centrada en pilotos y escuderías de F1. Los usuarios pueden crear ligas privadas, seleccionar equipos con presupuesto limitado y competir según los resultados reales de cada carrera.

---

## 2. Arquitectura

### Stack tecnológico

| Capa       | Tecnología            | Versión  |
|------------|----------------------|----------|
| Frontend   | Vue 3 + Vite          | 3.4 / 5.x |
| Estado     | Pinia                 | 2.x      |
| Routing    | Vue Router            | 4.x      |
| Estilos    | Tailwind CSS          | 3.x      |
| HTTP       | Axios                 | 1.x      |
| Backend    | Laravel               | 11.x     |
| Auth       | Laravel Sanctum       | 4.x      |
| Base datos | MySQL                 | 8.0      |
| API extern | Jolpica F1 API        | —        |

### Patrón arquitectónico

```
Frontend SPA (Vue 3)
    ↕ HTTP/JSON (Bearer Token)
Backend API REST (Laravel 11)
    ↕ Eloquent ORM
MySQL 8 Database
    ↑
Jolpica F1 API (externa, solo lectura)
```

---

## 3. Esquema de Base de Datos

### Tablas principales

```
users
  id, name, username, email, password, role (user|admin),
  avatar, total_points, budget, timestamps

constructors
  id, api_id, name, nationality, logo, color, price, is_active

drivers
  id, api_id, code, number, first_name, last_name,
  nationality, date_of_birth, photo, constructor_id(FK), price, is_active

circuits
  id, api_id, name, location, country, lat, lng, image

races
  id, api_id, season, round, name, circuit_id(FK),
  date, time, is_sprint, status (upcoming|active|scored|cancelled),
  transfer_deadline

race_results
  id, race_id(FK), driver_id(FK), constructor_id(FK),
  grid_position, finish_position, status, points_official,
  fastest_lap, driver_of_the_day, qualifying_position,
  fantasy_points, fantasy_points_calculated

scoring_rules
  id, event, points, description, is_active

leagues
  id, name, code (unique 8 chars), description, owner_id(FK),
  max_members, is_private, season, status (active|finished|draft)

league_members
  id, league_id(FK), user_id(FK), total_points, rank, joined_at

fantasy_teams
  id, user_id(FK), league_id(FK), name, remaining_budget, total_points

fantasy_team_drivers  (pivot)
  id, fantasy_team_id(FK), driver_id(FK), is_captain, selected_at, removed_at

fantasy_team_constructors  (pivot)
  id, fantasy_team_id(FK), constructor_id(FK), selected_at, removed_at

fantasy_team_race_points
  id, fantasy_team_id(FK), race_id(FK), points_earned, breakdown (JSON)
```

---

## 4. API REST

### Endpoints públicos

| Método | Endpoint                        | Descripción               |
|--------|---------------------------------|---------------------------|
| POST   | /api/auth/register              | Registrar usuario         |
| POST   | /api/auth/login                 | Iniciar sesión            |
| GET    | /api/f1/drivers                 | Listar pilotos            |
| GET    | /api/f1/drivers/{id}            | Detalle piloto            |
| GET    | /api/f1/constructors            | Listar escuderías         |
| GET    | /api/f1/races                   | Calendario de carreras    |
| GET    | /api/f1/races/next              | Próxima carrera           |
| GET    | /api/f1/races/{id}              | Detalle carrera           |
| GET    | /api/f1/standings/drivers       | Clasificación pilotos     |
| GET    | /api/f1/standings/constructors  | Clasificación escuderías  |

### Endpoints autenticados (Bearer token)

| Método | Endpoint                                    | Descripción                    |
|--------|---------------------------------------------|--------------------------------|
| POST   | /api/auth/logout                            | Cerrar sesión                  |
| GET    | /api/auth/me                                | Usuario actual                 |
| GET    | /api/leagues                                | Mis ligas                      |
| POST   | /api/leagues                                | Crear liga                     |
| GET    | /api/leagues/{id}                           | Detalle + clasificación        |
| DELETE | /api/leagues/{id}                           | Eliminar liga (solo dueño)     |
| POST   | /api/leagues/join                           | Unirse con código              |
| GET    | /api/teams/{id}                             | Ver equipo fantasy             |
| POST   | /api/teams/{id}/drivers                     | Añadir piloto al equipo        |
| DELETE | /api/teams/{id}/drivers/{driverId}          | Quitar piloto del equipo       |
| POST   | /api/teams/{id}/constructor                 | Seleccionar escudería          |
| PATCH  | /api/teams/{id}/captain/{driverId}          | Marcar capitán                 |

### Endpoints admin (requiere rol admin)

| Método | Endpoint                                | Descripción                        |
|--------|-----------------------------------------|------------------------------------|
| GET    | /api/admin/dashboard                    | Estadísticas generales             |
| GET    | /api/admin/races                        | Listado con estado de puntuación   |
| POST   | /api/admin/sync                         | Sincronizar datos desde API F1     |
| POST   | /api/admin/races/{id}/sync-results      | Sincronizar resultados de carrera  |
| POST   | /api/admin/races/{id}/score             | Calcular puntos fantasy            |
| PUT    | /api/admin/prices                       | Actualizar precios pilotos/escuds  |

---

## 5. Sistema de Puntuación Fantasy

### Puntos por posición en carrera

| Posición | Puntos |
|----------|--------|
| 1º       | 25     |
| 2º       | 18     |
| 3º       | 15     |
| 4º       | 12     |
| 5º       | 10     |
| 6º       | 8      |
| 7º       | 6      |
| 8º       | 4      |
| 9º       | 2      |
| 10º      | 1      |

### Puntos por clasificación

| Posición | Puntos |
|----------|--------|
| Pole     | 10     |
| 2ª       | 9      |
| 3ª       | 8      |
| 4ª       | 7      |
| 5ª       | 6      |

### Bonificaciones y penalizaciones

| Evento              | Puntos |
|---------------------|--------|
| Vuelta rápida       | +5     |
| Piloto del día      | +5     |
| Supera a 3+ coches  | +3     |
| Supera a 5+ coches  | +5     |
| Supera compañero    | +3     |
| DNF                 | -15    |
| DNS                 | -20    |
| Penalización grid   | -5     |
| Penalización tiempo | -5     |
| Descalificado       | -25    |

### Reglas del equipo fantasy

- **5 pilotos** + **1 escudería** por equipo
- **Presupuesto**: 100M € máximo
- **Capitán**: multiplica sus puntos x2
- **Máximo 2 pilotos** de la misma escudería
- Los puntos de la escudería = suma de puntos de sus 2 pilotos

---

## 6. API Externa — Jolpica F1

**Base URL**: `https://api.jolpi.ca/ergast/f1`

Endpoints utilizados:
- `/{season}/drivers.json` — Pilotos de la temporada
- `/{season}/constructors.json` — Escuderías
- `/{season}.json` — Calendario de carreras
- `/{season}/{round}/results.json` — Resultados de carrera
- `/{season}/driverStandings.json` — Clasificación pilotos
- `/{season}/constructorStandings.json` — Clasificación escuderías

---

## 7. Estructura de Archivos

### Backend (Laravel)
```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── F1Controller.php
│   │   │   │   ├── LeagueController.php
│   │   │   │   └── FantasyTeamController.php
│   │   │   └── Admin/
│   │   │       └── AdminRaceController.php
│   │   └── Middleware/
│   │       └── IsAdmin.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Driver.php
│   │   ├── Constructor.php
│   │   ├── Circuit.php
│   │   ├── Race.php
│   │   ├── RaceResult.php
│   │   ├── ScoringRule.php
│   │   ├── League.php
│   │   ├── LeagueMember.php
│   │   ├── FantasyTeam.php
│   │   └── FantasyTeamRacePoints.php
│   └── Services/
│       ├── F1ApiService.php
│       └── FantasyScoringService.php
├── database/
│   ├── migrations/
│   │   ├── ..._create_users_table.php
│   │   ├── ..._create_f1_entities_table.php
│   │   ├── ..._create_race_results_table.php
│   │   └── ..._create_fantasy_tables.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── CircuitSeeder.php
├── routes/
│   └── api.php
├── bootstrap/
│   └── app.php
└── config/
    └── services.php
```

### Frontend (Vue 3)
```
frontend/src/
├── assets/css/main.css
├── components/
│   └── layout/
│       ├── AppLayout.vue
│       └── AdminLayout.vue
├── views/
│   ├── auth/
│   │   ├── LoginView.vue
│   │   └── RegisterView.vue
│   ├── dashboard/
│   │   └── DashboardView.vue
│   ├── race/
│   │   ├── RacesView.vue
│   │   ├── RaceDetailView.vue
│   │   ├── DriversView.vue
│   │   └── ConstructorsView.vue
│   ├── leagues/
│   │   ├── LeaguesView.vue
│   │   ├── LeagueDetailView.vue
│   │   └── MyTeamView.vue
│   └── admin/
│       ├── AdminDashboardView.vue
│       ├── AdminRacesView.vue
│       ├── AdminDriversView.vue
│       └── AdminScoringView.vue
├── stores/
│   ├── auth.js
│   └── f1.js
├── services/
│   ├── api.js
│   ├── authService.js
│   ├── f1Service.js
│   ├── leagueService.js
│   └── adminService.js
├── router/
│   └── index.js
├── App.vue
└── main.js
```

---

## 8. Guía de Instalación

### Requisitos previos
- PHP 8.2+
- Composer
- Node.js 20+
- MySQL 8.0
- npm o pnpm

### Backend

```bash
cd backend

# 1. Instalar dependencias
composer install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar base de datos en .env
DB_DATABASE=f1_fantasy
DB_USERNAME=root
DB_PASSWORD=secret

# 4. Crear tablas y datos iniciales
php artisan migrate --seed

# 5. Lanzar servidor
php artisan serve
# → http://localhost:8000
```

### Frontend

```bash
cd frontend

# 1. Instalar dependencias
npm install

# 2. Configurar entorno
cp .env.example .env.local
# VITE_API_URL=http://localhost:8000/api

# 3. Lanzar servidor de desarrollo
npm run dev
# → http://localhost:5173
```

### Primer uso

1. Acceder a `http://localhost:5173`
2. Registrar una cuenta (o usar `admin@f1fantasy.local` / `admin1234`)
3. Ir al panel admin → **Sincronizar datos F1** para cargar pilotos y carreras
4. Crear una liga, seleccionar equipo y competir

---

## 9. Mejoras futuras (extensiones del TFG)

- [ ] Transferencias entre rondas con historial
- [ ] Notificaciones por email (deadline de transferencias)
- [ ] Modo Sprint Race con puntuación diferenciada
- [ ] Estadísticas avanzadas por piloto y escudería
- [ ] Gráficas de evolución de puntos por ronda
- [ ] Chat dentro de cada liga
- [ ] App móvil (Capacitor/Ionic)
- [ ] WebSockets para puntuación en tiempo real
