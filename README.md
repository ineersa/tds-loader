# TDS loader

## Установка

```
composer require ineersa/tds-loader
```

## Пример использования

```php
$loader = new \Ineersa\TDS\Loader('<hash>', '<url>');
$content = $loader->getContent();

return new Response($content);
```

`hash` - полученный хеш для РК
`url` - ссылкка на tds

`$content` - возвращает строку с контентом либо пусто

Есть возможность включить debug.

CURL обязателен.