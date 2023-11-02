# Eine Testwebseite mit Minispielen

Eine Testwebseite für die Registrierung und Anmeldung von Benutzern, welche auch Minispiele enthält.

Muss noch überarbeitet werden!

---

Um ein PHP + Laravel-Projekt von GitHub auf einem anderen Rechner zum Laufen zu bringen, braucht man

neben [XAMPP](https://www.apachefriends.org/download.html) oder [Docker](https://www.docker.com/get-started/):

1. (PHP) [Composer](https://getcomposer.org/) runterladen.
    1. Wenn es direkt geht, dann zu Schritt 6 springen.

2. Über den [Xdebug Wizard](https://xdebug.org/wizard) (online) mithilfe von "phpinfo" oder "php -i" herausfinden, welche Xdebug.dll man downloaden muss.
3. In den empfohlenen Ordner verschieben und zu "xdebug.dll" umbenennen.
4. In der "php.ini" das "xdebug.start_with_request" von "yes" auf "trigger" umstellen.
5. Dann kann erst Composer installiert werden (zumindest in meinem Fall).

6. Beim Laravel-Projekt die Datei ".env.example" als ".env" kopieren.
7. Im Terminal "composer install" im Projket-Ordner ausführen.
8. Falls ein Fehler auftritt, den "Application Key" generieren lassen.
9. Bei Bedarf "Virtual Hosts" hinzufügen.
10. Unter "database\web_minigames.sql" die Datenbank-Struktur wiederherstellen.
    1. Oder mit "php artisan migrate" erstellen und die fehlenden Spalten ergänzen.
    2. Falls etwas nur positive Zahlen haben soll (n >= 0), dann UNSIGNED nehmen.
    3. Ansonsten ist SIGNED erforderlich.
    4. Bei Werten von 0 - 255 am besten UNSIGNED TINYINT nehmen.
11. Bei Fragen oder Problemen einfach Issue erstellen oder anderweitig Bescheid geben
    1. Oder selber beheben, wenn dies möglich ist.

Fertig. :-)

---
Es gibt aber noch einige Fehler, wie zum Beispiel, dass beim Registrieren eines Benutzers folgender Fehler bei mir kommt:

```
TypeError
PHP 8.2.4
Laravel 9.41.0

Illuminate\Log\LogManager::info(): Argument #2 ($context) must be of type array, App\Models\User given, called in C:\xampp\htdocs\web-minigames\vendor\laravel\framework\src\Illuminate\Support\Facades\Facade.php on line 338
```

Ich weiß nicht, woher das kommt, weil es auf einem anderen Rechner wunderbar ging. Aber zumindest wird der User angelegt und nach erneutem Abschicken wird man zurückgeleitet und bekommt den Hinweis, dass er schon "angelegt" wurde. Wenn ich mehr Lust und Zeit dazu habe, untersuche ich das mal genauer.


<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
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

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
