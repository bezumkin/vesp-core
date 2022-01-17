## Vesp Core

[![Latest Stable Version](https://poser.pugx.org/vesp/core/v/stable)](https://packagist.org/packages/vesp/core)
[![Total Downloads](https://poser.pugx.org/vesp/core/downloads)](https://packagist.org/packages/vesp/core)
[![License](https://poser.pugx.org/vesp/core/license)](https://packagist.org/packages/vesp/core)

Библиотека для быстрого создания API при помощи [Slim 4][slim], [Eloquent][eloquent] и миграций [Phinx][phinx].
Содержит заготовки контроллеров, базовые модели, примеры миграции и **JWT** авторизацию.


### Подключение Clockwork

![](https://file.modx.pro/files/5/5/4/554c5b8f8a68a900334989f540a21f51.png)

На время разработки вы можете подключить [библиотеку Clockwork][clockwork], которая будет собирать ваши запросы 
через middleware и выводить при помощи браузерного расширения для [Firefox][cw-firefox] и [Chrome][cw-chrome].

```sh
composer require itsgoingd/clockwork:^4.1 --dev
```

После этого можно собирать данные о работе маршрутов через добавление к ним middleware:
```php
$app->any('/api/some-action', App\Controllers\SomeAction::class)
    ->add(Vesp\Middlewares\Clockwork::class);
```

А для просмотра данных из браузерного расширения нужно добавить специальный маршрут
```php
$app->get(
    '/__clockwork/{id:(?:[0-9-]+|latest)}[/{direction:(?:next|previous)}[/{count:\d+}]]', 
    Vesp\Controllers\Data\Clockwork::class
);
```

Если у вас включен Xdebug, и вы профилируете запросы, то нужен еще один маршрут:
```php
$app->get('/__clockwork/{id:[0-9-]+}/extended', Vesp\Controllers\Data\Clockwork::class);
```

Обратите внимание, что данные могут содержать чувствительную информацию, поэтому лучше защитить эти маршруты 
авторизацией через Web-сервер или другим способом.


[slim]: https://github.com/slimphp/slim
[eloquent]: https://github.com/illuminate/database
[phinx]: https://github.com/robmorgan/phinx
[clockwork]: https://github.com/itsgoingd/clockwork
[cw-firefox]: https://addons.mozilla.org/en-US/firefox/addon/clockwork-dev-tools
[cw-chrome]: https://chrome.google.com/webstore/detail/clockwork/dmggabnehkmmfmdffgajcflpdjlnoemp
