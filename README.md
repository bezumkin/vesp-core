## Vesp Core

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bezumkin/vesp-core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bezumkin/vesp-core/?branch=master)
[![Build Status](https://travis-ci.com/bezumkin/vesp-core.svg?branch=master)](https://travis-ci.com/bezumkin/vesp-core)
[![Coverage Status](https://coveralls.io/repos/github/bezumkin/vesp-core/badge.svg?branch=master)](https://coveralls.io/github/bezumkin/vesp-core?branch=master)
[![Latest Stable Version](https://poser.pugx.org/vesp/core/v/stable)](https://packagist.org/packages/vesp/core)
[![Total Downloads](https://poser.pugx.org/vesp/core/downloads)](https://packagist.org/packages/vesp/core)
[![License](https://poser.pugx.org/vesp/core/license)](https://packagist.org/packages/vesp/core)

Библиотека для быстрого создания API при помощи [Slim 4][slim], [Eloquent][eloquent] и миграций [Phinx][phinx].
Содержит заготовки контроллеров, базовые модели, примеры миграции и **JWT** авторизацию.


### Подключение Clockwork

![](https://file.modx.pro/files/5/5/4/554c5b8f8a68a900334989f540a21f51.png)

На время разработки вы можете подключить [библиотеку Clockwork][clockwork], которая будет собирать ваши запросы 
через middlewrae и выводить при помощи браузерного расширения для [Firefox][cw-firefox] и [Chrome][cw-chrome].

```sh
composer require itsgoingd/clockwork
```

После этого можно собирать данные о работе маршрутов через добавление к ним middleware:
```php
$app->any('/api/some-action', [App\Controllers\SomeAction::class, 'process'])
    ->add(Vesp\Middlewares\Clockwork::class);
```

А для просмотра данных из браузерного расширения нужно добавить специальный маршрут
```php
$app->get('/__clockwork/{id:(?:[0-9-]+|latest)}', [Vesp\Controllers\Data\Clockwork::class, 'process']);
```

Обратите внимание, что данные могут содержать чувствительную информацию, поэтому лучше защитить этот маршрут 
авторизацией через Web-сервер или другим способом.


[slim]: https://github.com/slimphp/slim
[eloquent]: https://github.com/illuminate/database
[phinx]: https://github.com/robmorgan/phinx
[clockwork]: https://github.com/itsgoingd/clockwork
[cw-firefox]: https://addons.mozilla.org/en-US/firefox/addon/clockwork-dev-tools
[cw-chrome]: https://chrome.google.com/webstore/detail/clockwork/dmggabnehkmmfmdffgajcflpdjlnoemp
