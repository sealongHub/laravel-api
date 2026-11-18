<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Vercel Blob image uploads

Set `BLOB_READ_WRITE_TOKEN` and `BLOB_STORE_ID` in the Laravel `.env` file. Keep the read-write token server-side and never expose it to the frontend. The shared `uploadImage($folderName, $imageFile)` helper converts supported raster images to WebP at quality 80 without resizing, then uploads and returns the Vercel URL; existing image database fields store that URL. SVG uploads are not supported. `getImage($value)` returns Vercel URLs unchanged and resolves legacy Laravel storage paths.

## Deploying Laravel as a separate Vercel project

Create a separate Vercel project from this repository and set its **Root Directory** to `laravel-api`. The `laravel-api/vercel.json` file routes all requests to the Laravel Docker container. Select the **Container** framework preset (Beta). Set `APP_URL` to the backend deployment URL.

Configure these Vercel environment variables before deploying:

- `APP_KEY` and `JWT_SECRET` (secrets)
- `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for a managed MySQL database
- `BLOB_READ_WRITE_TOKEN` and `BLOB_STORE_ID` for Vercel Blob
- `CORS_ALLOWED_ORIGINS` set to the frontend's exact origin(s), comma-separated if needed (no trailing slash)
- `CACHE_STORE=database`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, and `LOG_CHANNEL=stderr`

Do not use the bundled SQLite file for production: Vercel container filesystems are not a durable database. After setting the database variables, run `php artisan migrate --force` once against the managed MySQL database.

Deploy React as a second Vercel project with **Root Directory** set to `web_pos`. Set `VITE_API_URL` to the backend URL ending in `/api/`, and `VITE_IMAGE_BASE_URL` to the backend URL ending in `/storage/` for legacy local-storage image paths. These are build-time variables, so redeploy the frontend after changing them. The React build output stays in `web_pos/dist`; an API-only Laravel project does not need that `dist` folder. `web_pos/vercel.json` rewrites React Router URLs such as `/dashboard` to the SPA entry point.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
