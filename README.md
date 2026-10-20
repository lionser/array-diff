# lionser/array-diff

Структурное сравнение вложенных массивов. Результат: список изменений в формате `field / old / new`.

## Установка

```bash
composer require lionser/array-diff
```

Из приватного репозитория:

```json
{
    "repositories": [
        { "type": "vcs", "url": "git@github.com:lionser/array-diff.git" }
    ]
}
```

## Использование

```php
use Lionser\ArrayDiff\ArrayDiffer;

$differ = new ArrayDiffer();

$changes = $differ->diffToArray(
    ['user' => ['name' => 'Ann', 'tags' => ['a', 'b']]],
    ['user' => ['name' => 'Bob', 'tags' => ['a', 'c']]],
);
```

Результат:

```php
[
    ['field' => 'user.name', 'old' => 'Ann', 'new' => 'Bob'],
    ['field' => 'user.tags', 'old' => ['a', 'b'], 'new' => ['a', 'c']],
]
```

Метод `diff()` возвращает список объектов `Change` со свойствами `field`, `old`, `new`, `hadOld`, `hasNew`.

Разделитель пути задаётся в конструкторе:

```php
$differ = new ArrayDiffer('/');
```

## Семантика

- ассоциативные массивы сравниваются рекурсивно по ключам;
- списки (`array_is_list`) сравниваются целиком, одной записью;
- пустой массив считается ассоциативным, поэтому `[]` → `['a' => 1]` даёт запись `a`;
- если с одной стороны ассоциативный массив, а с другой список или скаляр, возвращается одна запись для всего значения;
- добавленный или удалённый ключ с массивом возвращается одной записью, без раскрытия внутрь;
- значения сравниваются строго (`===`), `1` и `'1'` считаются разными;
- `null` и отсутствующий ключ различаются через `hadOld` и `hasNew`, в `toArray()` оба дают `null`;
- порядок записей: сначала ключи из `$before`, затем новые ключи из `$after`.

## Разработка

```bash
composer install
composer test
composer stan
```
