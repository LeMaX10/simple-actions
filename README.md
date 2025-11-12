# Simple Actions

Пакет для реализации паттерна простых переиспользуемых действий (Actions) в Laravel приложениях.
Вдохновлен Laravel Actions, но не перегружен контекстами. Основной упор сосредоточен на принципе 1 объект = 1 действие.

## Установка

```bash
composer require lemax10/simple-actions
```

## Основные возможности
- Простой и понятный API для создания Actions
- **UseCase** паттерн - агрегирование Actions в сценарии
- **DIP (SOLID)** - загрузка через Service Container, подмена реализаций
- Полный цикл жизни событий (beforeRun, running, ran, failed, afterRun)
- Observer паттерн подобно Eloquent
- Управление транзакциями БД
- Продвинутое кеширование результатов
- Условное выполнение
- Хелперы для удобного использования

## Быстрый старт

### Создание Action

```php
use LeMaX10\SimpleActions\Action;

class CreateUserAction extends Action
{
    protected function handle(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
        ]);
    }
}
```

### Использование

```php
// Создание и выполнение
$user = CreateUserAction::make()->run('John Doe', 'john@example.com');

// Через хелпер
$user = action(CreateUserAction::class, 'John Doe', 'john@example.com');

// Условное выполнение
$user = CreateUserAction::make()
    ->runIf($condition, 'John', 'john@example.com');

$user = CreateUserAction::make()
    ->runUnless($condition, 'John', 'john@example.com');
```

### UseCase - Сценарии из Actions

UseCase это полноценный Action, агрегирующий другие Actions:

```php
use LeMaX10\SimpleActions\UseCase;

class RegisterUserUseCase extends UseCase
{
    protected function handle(array $data): User
    {
        // Все действия выполняются в транзакции
        $user = CreateUserAction::make()->run($data['name'], $data['email']);
        return tap($user, function() use($user, $data) {
            SendWelcomeEmailAction::make()->run($user);
            CreateUserProfileAction::make()->run($user, $data['profile']);
        });
    }
}

// Использование как обычного Action
$user = RegisterUserUseCase::make()->run($data);

// Или через хелпер
$user = usecase(RegisterUserUseCase::class, $data);
```

## События

### Цикл жизни событий

Action имеет полный цикл жизни с 5 событиями:

1. **beforeRun** - перед началом выполнения
2. **running** - непосредственно перед вызовом handle()
3. **ran** - после успешного выполнения
4. **failed** - при ошибке выполнения
5. **afterRun** - всегда выполняется в конце

### Регистрация слушателей

```php
// Простая регистрация
CreateUserAction::beforeRun(function (ActionBeforeRun $event) {
    Log::info('Creating user', $event->arguments);
});

CreateUserAction::ran(function (ActionRan $event) {
    Log::info('User created', ['user' => $event->result]);
});

CreateUserAction::failed(function (ActionFailed $event) {
    Log::error('Failed to create user', [
        'exception' => $event->exception
    ]);
});
```

### Остановка выполнения

События `beforeRun` и `running` могут остановить выполнение, вернув `false`:

```php
CreateUserAction::beforeRun(function (ActionBeforeRun $event) {
    if ($user->isBanned()) {
        return false; // Остановит выполнение
    }
});
```

### Observer паттерн

Создайте observer для группировки логики событий:

```php
use LeMaX10\SimpleActions\Observers\ActionObserver;

class CreateUserActionObserver extends ActionObserver
{
    public function beforeRun(ActionBeforeRun $event): void
    {
        // Логика перед выполнением
    }

    public function ran(ActionRan $event): void
    {
        // Логика после успешного выполнения
    }

    public function failed(ActionFailed $event): void
    {
        // Логика при ошибке
    }
}

// Регистрация observer
CreateUserAction::observe(CreateUserActionObserver::class);
```

### Отключение событий

```php
CreateUserAction::withoutEvents(function () {
    CreateUserAction::make()->run('John', 'john@example.com');
});
```

## Транзакции

### Автоматические транзакции

```php
class CreateUserAction extends Action
{
    // Всегда выполнять в транзакции
    protected bool $singleTransaction = true;

    protected function handle(string $name, string $email): User
    {
        return User::create(['name' => $name, 'email' => $email]);
    }
}
```

### Динамическое управление

```php
// Включить транзакцию
CreateUserAction::make()
    ->withTransaction()
    ->run('John', 'john@example.com');

// Отключить транзакцию (переопределяет $singleTransaction)
CreateUserAction::make()
    ->withoutTransaction()
    ->run('John', 'john@example.com');
```

## Кеширование

### Базовое кеширование

```php
// Кеширование на 60 секунд
$result = CalculateAction::make()
    ->remember('calc-key', 60)
    ->run($data);

// Постоянное кеширование
$result = CalculateAction::make()
    ->rememberForever('calc-key')
    ->run($data);
```

### Автогенерация ключей

```php
// Ключ генерируется автоматически на основе аргументов
$result = CalculateAction::make()
    ->rememberAuto('prefix', 60)
    ->run($value);
```

### Теги кеша

```php
$result = GetUserDataAction::make()
    ->tags(['users', 'user-' . $userId])
    ->remember('user-data-' . $userId, 60)
    ->run($userId);

// Очистка по тегам
Cache::tags(['users'])->flush();
```

### Выбор драйвера кеша

```php
$result = HeavyCalculationAction::make()
    ->store('redis')
    ->remember('calculation-key', 3600)
    ->run($data);
```

### Условное кеширование

```php
// Кешировать только если условие истинно
$result = GetDataAction::make()
    ->remember('data-key', 60)
    ->cacheWhen($user->isPremium())
    ->run();

// С closure
$result = CalculateAction::make()
    ->remember('calc-key', 60)
    ->cacheWhen(fn ($value) => $value > 100)
    ->run($value);

// Кешировать если НЕ выполнено условие
$result = GetDataAction::make()
    ->remember('data-key', 60)
    ->cacheUnless($user->isAdmin())
    ->run();
```

### Управление кешем

```php
$action = GetDataAction::make()->remember('key', 60);

// Проверка наличия в кеше
if ($action->isCached('key')) {
    // ...
}

// Получение ключа кеша
$cacheKey = $action->getCacheKey();

// Удаление из кеша
$action->forget('key');
```

## Комплексное использование

Все возможности можно комбинировать:

```php
class CreateOrderAction extends Action
{
    protected bool $singleTransaction = true;

    protected function handle(User $user, array $items): Order
    {
        $order = Order::create(['user_id' => $user->id]);
        
        foreach ($items as $item) {
            $order->items()->create($item);
        }
        
        return $order;
    }
}

// Регистрация observer
CreateOrderAction::observe(OrderActionObserver::class);

// Использование с транзакцией, кешированием и событиями
$order = CreateOrderAction::make()
    ->withTransaction()
    ->rememberAuto('order', 3600)
    ->cacheWhen(fn ($user) => $user->isPremium())
    ->tags(['orders', 'user-' . $user->id])
    ->store('redis')
    ->run($user, $items);
```


## Лучшие практики

### 1. Один Action - одно действие, UseCase - сценарий

```php
// ✅ Хорошо - атомарные действия
class CreateUserAction extends Action { }
class SendEmailAction extends Action { }
class LogActivityAction extends Action { }

// ✅ Хорошо - UseCase агрегирует действия
class RegisterUserUseCase extends UseCase {
    protected function handle($data) {
        $user = CreateUserAction::make()->run($data);
        SendEmailAction::make()->run($user);
        LogActivityAction::make()->run($user);
        return $user;
    }
}

// ❌ Плохо - слишком общее
class UserAction extends Action { }
```

### 2. Используйте абстракции для взаимозаменяемых Actions (DIP)

```php
// ✅ Хорошо - зависимость от абстрактного класса
abstract class NotificationAction extends Action {
    // handle() реализуют дочерние классы
}

class SendEmailAction extends NotificationAction {
    protected function handle(User $user, string $message): bool { /* ... */ }
}

class SendSmsAction extends NotificationAction {
    protected function handle(User $user, string $message): bool { /* ... */ }
}

class NotifyUserUseCase extends UseCase {
    protected function handle(User $user, string $message) {
        // Зависимость от абстракции - легко подменить через контейнер
        return app(NotificationAction::class)->run($user, $message);
    }
}

// ❌ Плохо - невозможность подмены в тестах
class NotifyUserUseCase extends UseCase {
    protected function handle(User $user, string $message) {
        // Жестко привязан к SendEmailAction, нельзя подменить
        return (new SendEmailAction())->run($user, $message);
    }
}
```

### 3. Используйте типизацию

```php
class CreateUserAction extends Action
{
    protected function handle(
        string $name,
        string $email,
        ?string $phone = null
    ): User {
        // ...
    }
}
```

### 3. UseCase для агрегирования Actions

UseCase - это тот же Action, но предназначенный для координации множества других Actions в единый сценарий:

```php
use LeMaX10\SimpleActions\UseCase;

class RegisterUserUseCase extends UseCase
{
    // UseCase поддерживает все возможности Action:
    // - События (beforeRun, running, ran, failed, afterRun)
    // - Транзакции (по умолчанию включены)
    // - Кеширование
    
    protected function handle(array $data): User
    {
        // UseCase координирует выполнение нескольких Actions
        $user = CreateUserAction::make()->run($data['name'], $data['email']);
        
        SendWelcomeEmailAction::make()->run($user);
        
        CreateUserProfileAction::make()->run($user, $data['profile']);
        
        NotifyAdminAction::make()->run($user);
        
        return $user;
    }
}

// Использование UseCase как обычного Action
$user = RegisterUserUseCase::make()
    ->remember('user-registration-' . $email, 300) // Можно кешировать
    ->run($data);

// UseCase поддерживает события
RegisterUserUseCase::ran(function ($event) {
    Log::info('User registered', ['user' => $event->result]);
});

// UseCase выполняется в транзакции (по умолчанию)
// Если любое вложенное действие упадет - откатится всё
```

**Преимущества UseCase:**
- Все Actions в UseCase выполняются в единой транзакции
- UseCase можно кешировать целиком
- События отслеживают весь сценарий
- Переиспользуемая бизнес-логика

## Dependency Inversion Principle (SOLID)

Actions загружаются через Laravel Service Container (`app(static::class)`), что позволяет применять принцип инверсии зависимостей:

### Подход 1: Подмена через абстрактные классы

```php
// Базовый абстрактный класс (вместо интерфейса)
abstract class SendNotificationAction extends Action {

}

// Реальная реализация
class SendEmailNotificationAction extends SendNotificationAction {
    protected function handle(User $user, string $message): bool {
        Mail::to($user)->send(new Notification($message));
        return true;
    }
}

// Тестовая реализация
class FakeSendNotificationAction extends SendNotificationAction {
    protected function handle(User $user, string $message): bool {
        Log::info('Fake notification sent');
        return true;
    }
}

// Регистрация в ServiceProvider
public function register() {
    $this->app->bind(
        SendNotificationAction::class,
        SendEmailNotificationAction::class
    );
}

// UseCase зависит от абстракции, а не конкретного объекта
class RegisterUserUseCase extends UseCase {
    protected function handle(array $data): User {
        $user = CreateUserAction::make()->run($data);
        
        // Получаем реализацию из контейнера
        app(SendNotificationAction::class)->run($user, 'Welcome!');
        
        return $user;
    }
}

// В тестах можно подменить
public function test_registration() {
    $this->app->bind(
        SendNotificationAction::class,
        FakeSendNotificationAction::class  // Подмена!
    );
    
    $user = RegisterUserUseCase::make()->run($data);
    
    $this->assertDatabaseHas('users', ['email' => $data['email']]);
}
```

### Подход 2: Подмена конкретного класса

```php
// UseCase зависит от конкретного класса
class RegisterUserUseCase extends UseCase {
    protected function handle(array $data): User {
        $user = CreateUserAction::make()->run($data);
        
        // Использование конкретного класса
        SendEmailAction::make()->run($user, 'Welcome!');
        
        return $user;
    }
}

// В тестах подменяем конкретный класс на fake
public function test_registration() {
    // Подменяем SendEmailAction на FakeEmailAction
    $this->app->bind(SendEmailAction::class, FakeEmailAction::class);
    
    $user = RegisterUserUseCase::make()->run($data);
    
    $this->assertDatabaseHas('users', ['email' => $data['email']]);
}
```

### Подход 3: Регистрация по строковым ключам (менее предпочтительный, но возможный способ)

```php
// В ServiceProvider
public function register() {
    $this->app->bind('notification.action', function ($app) {
        if ($app->environment('testing')) {
            return new FakeNotificationAction();
        }
        return new SendEmailNotificationAction();
    });
}

// UseCase использует строковой ключ
class RegisterUserUseCase extends UseCase {
    protected function handle(array $data): User {
        $user = CreateUserAction::make()->run($data);
        
        app('notification.action')->run($user, 'Welcome!');
        
        return $user;
    }
}
```

### Внедрение зависимостей через конструктор

```php
class SendEmailAction extends Action {
    public function __construct(
        protected Mailer $mailer,
        protected LoggerInterface $logger
    ) {
        parent::__construct();
    }
    
    protected function handle(User $user, string $message): void {
        $this->mailer->send($user->email, $message);
        $this->logger->info('Email sent', ['user' => $user->id]);
    }
}

// Laravel автоматически резолвит зависимости
$result = SendEmailAction::make()->run($user, 'Hello');
```

### Глобальная подмена для тестирования

```php
// В тестах
class RegisterUserTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        
        // Глобально подменяем тяжелые Actions на фейки
        $this->app->bind(SendEmailAction::class, FakeEmailAction::class);
        $this->app->bind(NotifySlackAction::class, FakeSlackAction::class);
    }
    
    public function test_user_registration() {
        // Все UseCase будут использовать фейковые Actions
        $user = RegisterUserUseCase::make()->run($data);
        
        $this->assertTrue($user->exists);
    }
}
```

### Условная подмена

```php
// В ServiceProvider
public function register() {
    // В зависимости от окружения используем разные реализации
    if ($this->app->environment('testing')) {
        $this->app->bind(PaymentActionInterface::class, FakePaymentAction::class);
    } elseif ($this->app->environment('local')) {
        $this->app->bind(PaymentActionInterface::class, SandboxPaymentAction::class);
    } else {
        $this->app->bind(PaymentActionInterface::class, StripePaymentAction::class);
    }
}
```

### Преимущества DIP в Actions

- **Тестируемость**: легко подменять реализации в тестах
- **Гибкость**: можно менять реализацию без изменения UseCase
- **Изоляция**: UseCase зависят от абстракций, а не конкретных классов
- **Переиспользование**: разные реализации одной абстракции
- **Feature Flags**: включать/выключать функциональность через контейнер

### Почему не интерфейсы?

⚠**Важно**: Нельзя использовать интерфейсы с конкретной сигнатурой `run()`, так как базовый контракт `Action` уже определяет `run(...$args): mixed` с variadic параметрами. Любой другой интерфейс с конкретной сигнатурой будет несовместим.

**Решение**: Используйте абстрактные классы или подменяйте конкретные классы через контейнер.

## Хелперы

Пакет предоставляет удобные хелперы:

```php
// Быстрое выполнение Action
$result = action(CalculateAction::class, $data);

// Быстрое выполнение UseCase
$user = usecase(RegisterUserUseCase::class, $data);

// Эквивалентно:
// $result = CalculateAction::make()->run($data);
// $user = RegisterUserUseCase::make()->run($data);
```

Хелперы особенно удобны в контроллерах и сервисах:

```php
class UserController extends Controller
{
    public function register(Request $request)
    {
        $user = usecase(RegisterUserUseCase::class, $request->validated());
        
        return response()->json(['user' => $user]);
    }
    
    public function sendEmail(User $user)
    {
        action(SendEmailAction::class, $user, 'Welcome!');
        
        return back()->with('success', 'Email sent');
    }
}
```

### 4. Кешируйте тяжелые операции

```php
class GenerateReportAction extends Action
{
    protected function handle(Carbon $from, Carbon $to): Report
    {
        // Тяжелые вычисления
    }
}

$report = GenerateReportAction::make()
    ->rememberAuto('reports', 3600)
    ->tags(['reports'])
    ->run($from, $to);
```

## Лицензия

GPL-2.0-only

## Автор

Vladimir Pyankov (aka LeMaX10)
- Email: v@pyankov.pro
- Website: https://pyankov.pro
