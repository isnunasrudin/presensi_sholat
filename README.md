# Prayer Attendance System

Aplikasi berbasis Laravel dan PrimeVue untuk mencatat kehadiran sholat Dhuhur dan Dhuha dengan sistem admin untuk manajemen data.

## Fitur

- ✅ Autentikasi pengguna (Login/Register)
- ✅ Dashboard dengan statistik kehadiran sholat
- ✅ Pencatatan sholat/tidak sholat/halangan untuk Dhuhur dan Dhuha
- ✅ Filter dan pencarian data kehadiran
- ✅ Panel admin untuk manajemen pengguna
- ✅ Responsive design dengan PrimeVue

## Teknologi

- **Backend**: Laravel 12
- **Frontend**: Vue 3 + PrimeVue
- **Database**: SQLite (default)
- **API**: RESTful API dengan Laravel Sanctum

## Instalasi

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm atau yarn

### Langkah-langkah

1. Install dependencies PHP:
```bash
cd prayer-attendance
composer install
```

2. Install dependencies JavaScript:
```bash
npm install
```

3. Setup environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Jalankan migrasi database:
```bash
php artisan migrate
```

5. (Opsional) Buat admin user:
```bash
php artisan tinker
```
Kemudian jalankan:
```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);
```

6. Jalankan development server:

Terminal 1 - Laravel:
```bash
php artisan serve
```

Terminal 2 - Vite:
```bash
npm run dev
```

7. Akses aplikasi di browser:
```
http://localhost:8000
```

## Struktur Database

### Users
- id
- name
- email
- password
- role (admin/user)
- timestamps

### Prayer Records
- id
- user_id (foreign key)
- prayer_type (dhuhur/dhuha)
- date
- status (sholat/tidak_sholat/halangan)
- notes (optional)
- timestamps
- unique(user_id, prayer_type, date)

## API Endpoints

### Authentication
- `POST /api/register` - Register user baru
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - Get authenticated user

### Prayer Records
- `GET /api/prayer-records` - List records (dengan filter)
- `POST /api/prayer-records` - Create/update record
- `GET /api/prayer-records/{id}` - Show record
- `PUT /api/prayer-records/{id}` - Update record
- `DELETE /api/prayer-records/{id}` - Delete record (admin only)
- `GET /api/prayer-records/statistics/summary` - Get statistics

### User Management (Admin Only)
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `GET /api/users/{id}` - Show user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

## Default Credentials

Setelah membuat admin user melalui tinker:
- Email: admin@example.com
- Password: password

## Pengembangan

### Build untuk Production

1. Build frontend:
```bash
npm run build
```

2. Optimize Laravel:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Testing

```bash
php artisan test
```

## Troubleshooting

### CORS Issues
Jika mengalami masalah CORS, pastikan middleware Sanctum sudah terkonfigurasi di `bootstrap/app.php`.

### Database Connection
Default menggunakan SQLite. Untuk menggunakan MySQL/PostgreSQL, update file `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prayer_attendance
DB_USERNAME=root
DB_PASSWORD=
```

### Vite Issues
Jika ada masalah dengan Vite, coba:
```bash
rm -rf node_modules
npm install
npm run dev
```

## Lisensi

Open Source - MIT License
