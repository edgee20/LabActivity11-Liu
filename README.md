# Barangay Bulletin – Purok Liu

## Lab details

- Posts per page: **6**, because my last name, Liu, has 3 letters, so the lab minimum of 6 applies.
- Custom category: **Community**.
- Birthday post: **Purok Liu Clean-up Drive**, published on **April 20, 2026**.

## Running the project

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run dev
php artisan serve
```

The bulletin is available at `/posts`.

## Seeded accounts

Both named accounts use the password `bulletin2026`:

| Name | Email | Role |
| --- | --- | --- |
| Lola Liu | `lola.liu@bulletin.test` | Resident |
| Kagawad Ej Liu | `kagawad.liu@bulletin.test` | Author |

The seeder also creates eight additional resident users with the user factory.

## Seeded tags

The five tags are:

- Purok Liu
- Urgent
- Community
- Announcement
- Events

The pinned clean-up post always receives `Purok Liu` and `Urgent`. Other published
posts receive one to three tags through the `Post::tags()` relationship.

## Relationship map

- `User::author()` is `hasOne` because one login account can own one author profile.
- `User::comments()` is `hasMany` because a user can write many comments.
- `Author::user()` is `belongsTo` because the author profile stores `user_id`.
- `Author::posts()` is `hasMany` because an author publishes many posts.
- `Author::comments()` is `hasManyThrough` through posts to count comments received.
- `Post::author()` is `belongsTo` because posts store `author_id`.
- `Post::comments()` is `hasMany` because a post can receive many comments.
- `Post::tags()` and `Tag::posts()` are `belongsToMany` through the `post_tag` pivot.
- `Comment::post()` and `Comment::user()` are `belongsTo` because comments store both
  foreign keys.

Controllers eager-load the relationships needed by each view. Development and test
environments also prevent lazy loading so N+1 queries are detected early.

## Laravel

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

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
