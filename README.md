# Laravel Simple Actions

Реализация подхода простых действий.
Суть подхода одно действий равно одному объекту. Своего рода отсылка к laravel actions, но в более упрощенной реализации не перегруженная контекстами.

## Действие (action)

Помним, что одно действие должно быть равным одному объекту. Реализуем объект действие.

Пример:
```php
<?php
declare(strict_types=1);
namespace Example/Actions;

use LeMaX10\SimpleActions\Action;

/**
 * @method ExampleModel run(int $id)
 */
final class ExampleAction extends Action
{
    protected function handle(int $id): ExampleModel
    {
        return ExampleModel::findOrFail($id);
    }
}

var_dump(ExampleAction::make()->run(1)); // (ExampleModel)
```

## Дейтсвие в единой транзакции

Действие в единой транзакции, может быть как совокупонстью действий, так и совокупностью запросов к БД требующим соблюдения целостности и реализации в единой транзакции.
Для реализации действий единой транзакции необходимо определить свойство объекта: `$singleTransaction` в `true` значение.

```php
<?php
declare(strict_types=1);
namespace Example/Actions;

use LeMaX10\SimpleActions\Action;

/**
 * @method ExampleContactModel run(string $email, string $name)
 */
final class ExampleAction extends Action
{
    protected bool $singleTransaction = true;

    protected function handle(string $email, string $name): ExampleContactModel
    {
        $contact = new ExampleContactModel([
                'name' => $name
            ]);

        $contact->email()->associate($this->getEmailModel($email));
        $contact->save();
        return $contact;
    }

    private function getEmailModel(string $email): ExampleEmailModel
    {
        return ExampleEmailModel::firstOrCreate([
                'email' =>
            ]);
    }
}

var_dump(ExampleAction::make()->run('example@domain.com', 'User Name')); // (ExampleContactModel) ['name' => 'User Name', 'email' => (ExampleEmailModel) ['email' => 'example@domain.com']]
```

## Кешируемые действия
Каждое действие поддерживает кеширование результата. В таком случае, при повторном обращении к действию с тем же набором параметров, результатом будет выпуступать закешированные данные.

```php
<?php
declare(strict_types=1);
namespace Example/Actions;

use LeMaX10\SimpleActions\Action;

/**
 * @method ExampleModel run()
 */
final class ExampleAction extends Action
{
    protected function handle(): array
    {
        return ExampleContactModel::get()->all();
    }
}

// Simple in 10 minutes
var_dump(ExampleAction::make()->remember('exampleKey', 10)->run()); // ['1', '2', 3']

// Forever
var_dump(ExampleAction::make()->rememberForever('exampleKey')->run()); // ['1', '2', 3']

// Tags cache
var_dump(ExampleAction::make()->tags(['exampleTag1', 'exampleTag2'])->remember('exampleKey')->run()); // ['1', '2', 3']
```


### Описание вспомогательных методов
`ExampleAction::make()` - Создать экземпляр объекта действия

`->run(...)` - Обратиться к реализации действия. Сигнатура определяется объектом действия

`->remember(string $key[, Closure|\DateTimeInterface|\DateInterval|int|null $ttl])` - Результат действия должен быть закеширован и переиспользован

`->rememberForever(string $key)` - Результат действия должен быть навсегда закеширован и переиспользован

`->tags(array $tags)` - Устанавливаем теги кеш данных которыми будут помечены результаты.

### Модификация и расширение
Учитывая природу подхода, расширение действий не предусматривается текущей реализаций.
Вызов действий происходит через Laravel Container, благодаря чему вы можете в любой момент подменить реализацию того или иного действий в том числе глобально, главное сохранять сигнатуру действий.

Пример переопределения:
```php
app()->bind(ExampleAction::class, CustomExampleAction::class);
```
