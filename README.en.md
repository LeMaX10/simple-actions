# Simple Actions

Language: [Русский](README.md) | [English](README.en.md)

A package for implementing the pattern of simple reusable actions in Laravel applications.
Inspired by Laravel Actions, but not overloaded with contexts. The main focus is on the principle of 1 action object = 1 action.
To simplify the implementation of logic involving multiple actions, use cases (UseCases) are provided. Use cases are an aggregation of multiple actions into a single scenario.

The goal of the package is to bring convenience to code architecture, solve some routine operations and tasks, while remaining as simple as possible without context overload, and without bloating or blurring the object's responsibility.

## Installation

```bash
composer require lemax10/simple-actions
```

## Artisan generation commands

The package adds commands for quickly creating boilerplate:

```bash
php artisan make:action User/CreateUser
php artisan make:usecase User/RegisterUser
```

What will be created:
- `app/Actions/User/CreateUserAction.php`
- `app/UseCases/User/RegisterUserUseCase.php`

You can specify a full name with a suffix if necessary:

```bash
php artisan make:action Actions/User/CreateUserAction
php artisan make:usecase UseCases/User/RegisterUserUseCase
```

The `--force` flag will overwrite an existing file.

## Main features
- Simple and clear API for creating Actions
- **UseCase** pattern - aggregating Actions into scenarios
- **DIP (SOLID)** - loading via Service Container, substituting implementations
- Full lifecycle events (beforeRun, running, ran, failed, afterRun)
- Observer pattern similar to Eloquent
- Database transaction management
- Advanced result caching (support for the standard Laravel Cache driver or any supported by it)
- **Memoization** - caching in memory for the duration of the request
- **Idempotency** - protection against repeated parallel execution and potential "race condition" effects.
- Conditional execution
- Helpers for convenient use

## Quick start

### Creating an Action

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

### Usage

```php
// Create and execute
$user = CreateUserAction::make()->run('John Doe', 'john@example.com');

// Via helper
$user = action(CreateUserAction::class, 'John Doe', 'john@example.com');

// Conditional execution
$user = CreateUserAction::make()
    ->runIf($condition, 'John', 'john@example.com');

$user = CreateUserAction::make()
    ->runUnless($condition, 'John', 'john@example.com');
```

### Idempotency (optional)

To protect against repeated parallel execution of the same action:

```php
$order = CreateOrderAction::make()
    ->idempotent("order:create:{$requestId}", 300)
    ->run($payload);

// Key generation from arguments
$order = CreateOrderAction::make()
    ->idempotent(fn (array $payload) => 'order:' . $payload['external_id'])
    ->run($payload);

// Auto-generation of a key similar to rememberAuto/memo
$order = CreateOrderAction::make()
    ->idempotentAuto('order:create', 300)
    ->run($payload);
```

If the key has already been used, the saved result will be returned without re-executing `handle`.
If the same key is currently "in progress", an `ActionIdempotencyInProgressException` will be thrown.

By default, a cache-based repository is used. The repository can be switched:

```php
$result = SomeAction::make()
    ->idempotentRepository('cache')
    ->idempotentStore('redis')
    ->idempotent('my:key', 300)
    ->run($payload);
```

You can register custom idempotency drivers via `IdempotencyRepositoryManager::extend(...)`. For example, if you need to store locks in a database or an abstract storage.

### UseCase - Scenarios from Actions

A UseCase is a full-fledged Action that aggregates other Actions:

```php
use LeMaX10\SimpleActions\UseCase;

class RegisterUserUseCase extends UseCase
{
    protected function handle(array $data): User
    {
        // All actions are executed within a transaction
        $user = CreateUserAction::make()->run($data['name'], $data['email']);
        return tap($user, function() use ($user, $data) {
            SendWelcomeEmailAction::make()->run($user);
            CreateUserProfileAction::make()->run($user, $data['profile']);
        });
    }
}

// Usage like a regular Action
$user = RegisterUserUseCase::make()->run($data);

// Or via helper
$user = usecase(RegisterUserUseCase::class, $data);
```

### Result typing

To maintain strict typing when working with `run()` and helpers `action()`/`usecase()`, specify the return type via PHPDoc-generic:

```php
use App\Models\User;
use LeMaX10\SimpleActions\Action;

/**
 * @extends Action<User>
 */
class FindUserAction extends Action
{
    protected function handle(int $id): User
    {
        return User::query()->findOrFail($id);
    }
}

$user = FindUserAction::make()->run(1);            // User|false
$user = action(FindUserAction::class, 1);          // User|false
$maybe = FindUserAction::make()->runIf(false, 1);  // User|false|null

use App\Models\User;
use LeMaX10\SimpleActions\UseCase;

/**
 * @extends UseCase<User>
 */
class RegisterUserUseCase extends UseCase
{
    protected function handle(array $data): User
    {
        return CreateUserAction::make()->run($data['name'], $data['email']);
    }
}

$user = RegisterUserUseCase::make()->run($data);   // User|false
$user = usecase(RegisterUserUseCase::class, $data); // User|false
```

`false` is possible if execution was stopped in a `beforeRun`/`running` event.

## Events

### Lifecycle events

An Action has a full lifecycle with 5 events:

1. **beforeRun** - before execution starts
2. **running** - immediately before calling handle()
3. **ran** - after successful execution
4. **failed** - on execution error
5. **afterRun** - always executed at the end

### Registering listeners

```php
// Simple registration
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

### Stopping execution

`beforeRun` and `running` events can stop execution by returning `false`:

```php
CreateUserAction::beforeRun(function (ActionBeforeRun $event) {
    if ($user->isBanned()) {
        return false; // Will stop execution
    }
});
```

### Observer pattern

Create an observer to group event logic:

```php
use LeMaX10\SimpleActions\Observers\ActionObserver;

class CreateUserActionObserver extends ActionObserver
{
    public function beforeRun(ActionBeforeRun $event): void
    {
        // Logic before execution
    }

    public function ran(ActionRan $event): void
    {
        // Logic after successful execution
    }

    public function failed(ActionFailed $event): void
    {
        // Logic on error
    }
}

// Register observer
CreateUserAction::observe(CreateUserActionObserver::class);
```

### Disabling events

```php
CreateUserAction::withoutEvents(function () {
    CreateUserAction::make()->run('John', 'john@example.com');
});
```

### Local events for a specific instance

If you need to attach a hook(s) for a single call:

```php
CreateUserAction::make()
    ->before(fn () => Log::info('single before'))
    ->after(fn () => Log::info('single after'))
    ->run($data);

// Next call — without these hooks
CreateUserAction::make()->run($data);
```

Available local methods:
`before` (beforeRun),
`runningLocal` (running),
`then` (ran),
`onFail` (failed),
`after` (afterRun).
**Returning `false` from local `before`/`runningLocal` stops only the current event instance.

### Conditional local events

Helpers `*When` / `*Unless` run a local callback based on a condition (Boolean or `Closure`, arguments are the same as for `run`):

```php
GetUserAction::make()
    ->beforeWhen(fn ($id) => $id > 0, fn () => Log::debug('positive id'))
    ->afterUnless(false, fn () => Log::debug('always after'))
    ->run(10);
```

`Unless` inverts the condition of `When`.
Local hooks do not affect other instances and do not add global listeners or events.

## Transactions

### Automatic transactions

```php
class CreateUserAction extends Action
{
    // Always execute in a transaction
    protected bool $singleTransaction = true;

    protected function handle(string $name, string $email): User
    {
        return User::create(['name' => $name, 'email' => $email]);
    }
}
```

### Dynamic management

```php
// Enable transaction
CreateUserAction::make()
    ->withTransaction()
    ->run('John', 'john@example.com');

// Disable transaction (overrides $singleTransaction)
CreateUserAction::make()
    ->withoutTransaction()
    ->run('John', 'john@example.com');
```

## Caching

### Basic caching

```php
// Cache for 60 seconds
$result = CalculateAction::make()
    ->remember('calc-key', 60)
    ->run($data);

// Permanent caching
$result = CalculateAction::make()
    ->rememberForever('calc-key')
    ->run($data);
```

### Auto-generation of keys

```php
// Key is generated automatically based on arguments
$result = CalculateAction::make()
    ->rememberAuto('prefix', 60)
    ->run($value);
```

### Cache tags

```php
$result = GetUserDataAction::make()
    ->tags(['users', 'user-' . $userId])
    ->remember('user-data-' . $userId, 60)
    ->run($userId);

// Clear by tags
Cache::tags(['users'])->flush();
```

### Selecting a cache driver

```php
$result = HeavyCalculationAction::make()
    ->store('redis')
    ->remember('calculation-key', 3600)
    ->run($data);
```

### Conditional caching

```php
// Cache only if condition is true
$result = GetDataAction::make()
    ->remember('data-key', 60)
    ->cacheWhen($user->isPremium())
    ->run();

// With closure
$result = CalculateAction::make()
    ->remember('calc-key', 60)
    ->cacheWhen(fn ($value) => $value > 100)
    ->run($value);

// Cache if condition is NOT met
$result = GetDataAction::make()
    ->remember('data-key', 60)
    ->cacheUnless($user->isAdmin())
    ->run();
```

### Cache management

```php
$action = GetDataAction::make()->remember('key', 60);

// Check if exists in cache
if ($action->isCached('key')) {
    // ...
}

// Get the cache key
$cacheKey = $action->getCacheKey();

// Remove from cache
$action->forget('key');
```

## In-memory memoization

Memoization allows saving the results of an Action's execution in PHP memory for the duration of the current request. This helps avoid repeated execution of identical actions, database queries, or external API calls within a single request.

### Difference from caching

- **Caching** (`remember()`) - saves the result in the cache (Laravel CacheManager) between requests
- **Memoization** (`memo()`) - saves the result in PHP memory only for the duration of the current request

### Basic usage

```php
// First call - executes handle()
$user = GetUserAction::make()->memo()->run($userId);

// Repeated call with the same arguments - returns the result from memory
$user = GetUserAction::make()->memo()->run($userId); // handle() is not executed

// Different arguments - executes handle() again
$otherUser = GetUserAction::make()->memo()->run($otherUserId);
```

### Forced refresh

```php
// Save the result
$data = FetchDataAction::make()->memo()->run($params);

// Get the memoized result
$data = FetchDataAction::make()->memo()->run($params);

// Force refresh the result
$freshData = FetchDataAction::make()
    ->memo(force: true)
    ->run($params); // handle() will execute again

// Now memo() returns the updated result
$data = FetchDataAction::make()->memo()->run($params); // Will return $freshData
```

### Events and memoization

By default, when a result is taken from memory (memoized), events are **NOT** triggered again. This is related to performance and implemented as an optimization.

```php
CreateUserAction::ran(function($event) {
    Log::info('User created'); // Log only on actual creation
});

// First call - handle() executes, events fire
$user1 = CreateUserAction::make()->memo()->run($data);
// Log: "User created"

// Second call - result from memory, events DO NOT fire
$user2 = CreateUserAction::make()->memo()->run($data);
// Log: (empty)
```

If for some reason it is necessary to fire events even for memoized results, use the `forceEvents` argument:

```php
CreateUserAction::ran(function($event) {
    Cache::tags(['users'])->flush(); // Need to do it every time
});

// Events will fire even for a memoized result
$user = CreateUserAction::make()
    ->memo(forceEvents: true)
    ->run($data);
```

Some recommendations:

**When to use forceEvents:**
- Side effects are needed in events (cache clearing, notifications)
- Events are used for auditing/logging every access
- Debugging - you want to see all calls

**When NOT to use forceEvents:**
- Events are only for internal Action logic
- Performance is critical (overhead of duplicating events will slow down the application)
- Events duplicate what already happened on the first call

### Custom memoization key

By default, the key is generated based on the arguments. However, you can set your own key:

```php
// Use a custom key instead of argument hash
$result = CalculateAction::make()
    ->memo(key: 'my-custom-key')
    ->run($value1, $value2);

// Will return the same result, even with different arguments!
$result = CalculateAction::make()
    ->memo(key: 'my-custom-key')
    ->run($differentValue1, $differentValue2);
```

### Memoization management (for complex scenarios)

```php
$action = GetDataAction::make();

// Check if the result is memoized
if ($action->isMemoized([$userId])) {
    // Result is already in memory
}

// Forget a specific result
$action->memoForget([$userId]);

// Forget all results for this Action
GetDataAction::memoFlush();

// Clear memoization for all Actions (rarely used)
Action::memoFlushAll();

// Get the number of memoized results
$count = GetDataAction::getMemoizedCount();
```

### Practical examples

**Avoiding the N+1 problem in a UseCase:**

```php
class GetUserPermissionsAction extends Action
{
    protected function handle(int $userId): array
    {
        // Heavy database query
        return DB::table('permissions')
            ->join('user_permissions', ...)
            ->where('user_id', $userId)
            ->get()
            ->toArray();
    }
}

class ProcessUsersUseCase extends UseCase
{
    protected function handle(array $userIds): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            // If userId repeats - the query won't run again
            $permissions = GetUserPermissionsAction::make()
                ->memo()
                ->run($userId);
            
            $results[] = ProcessUserAction::make()->run($userId, $permissions);
        }
        
        return $results;
    }
}
```

**Using between application layers:**

```php
// Controller
class OrderController
{
    public function show(int $orderId)
    {
        // First request
        $order = GetOrderAction::make()->memo()->run($orderId);
        
        // Calls a UseCase that also requests the order
        $invoice = GenerateInvoiceUseCase::make()->run($orderId);
        
        return view('order.show', compact('order', 'invoice'));
    }
}

// UseCase
class GenerateInvoiceUseCase extends UseCase
{
    protected function handle(int $orderId): Invoice
    {
        // Won't execute the query again - will take from memory
        $order = GetOrderAction::make()->memo()->run($orderId);
        
        return GeneratePdfInvoice::make()->run($order);
    }
}
```

**Caching external API requests:**

```php
class FetchExchangeRateAction extends Action
{
    protected function handle(string $currency): float
    {
        // External API request
        return Http::get("https://api.example.com/rate/{$currency}")
            ->json('rate');
    }
}

// Anywhere in the application
$rate1 = FetchExchangeRateAction::make()->memo()->run('USD');
$rate2 = FetchExchangeRateAction::make()->memo()->run('USD'); // Won't hit the API
$rate3 = FetchExchangeRateAction::make()->memo()->run('EUR'); // Will hit for EUR
```

**Combining with caching:**

```php
// Memoization + caching = maximum performance
$report = GenerateReportAction::make()
    ->memo() // In memory for the request duration
    ->remember('report-key', 3600) // In Redis for an hour
    ->run($params);

// First request: executes handle() -> saves to Redis and memory
// Second request within the same HTTP request: takes from memory
// Third request in a new HTTP request: takes from Redis
```

### When to use memoization

✅ **Use memo() when:**
- The Action is called multiple times in a single request with the same arguments
- You need to avoid duplicate DB/API queries or duplicate execution of complex logic or calculations
- The Action is used in loops or recursively
- The Action is called from different layers (Controller -> UseCase -> other Actions)
- The result is only needed for the duration of the current request

❌ **Do not use memo() when:**
- The Action is called only once per request
- The result must be fresh on every call
- The Action has side effects (sending email, writing to DB)
- You need to persist the result across different HTTP requests (use `remember()`)

### Performance

Memoization adds minimal overhead, but it eases routine tasks and eliminates duplicate queries to DB/Cache. Consider this as a kind of price for convenience.
Example:
- Without `memo()`: ~0 overhead
- With `memo()` (first call): ~5-10μs (hash generation)
- With `memo()` (subsequent calls): ~1-2μs (array check)
- With `memo(forceEvents: true)`: +50-100μs (event firing)

**Event optimization:**
By default, events are not fired for memoized results, saving approximately ~50-100μs on each repeated call, depending on the logic you've implemented. Use `forceEvents: true` only when you really need to fire events again on repeated action calls.

## Comprehensive usage

All features can be combined:

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

// Register observer
CreateOrderAction::observe(OrderActionObserver::class);

// Usage with transaction, caching, and events
$order = CreateOrderAction::make()
    ->withTransaction()
    ->rememberAuto('order', 3600)
    ->cacheWhen(fn ($user) => $user->isPremium())
    ->tags(['orders', 'user-' . $user->id])
    ->store('redis')
    ->run($user, $items);
```


## Best practices

### 1. One Action - one action, UseCase - scenario

```php
// ✅ Good - atomic actions
class CreateUserAction extends Action { }
class SendEmailAction extends Action { }
class LogActivityAction extends Action { }

// ✅ Good - UseCase aggregates actions
class RegisterUserUseCase extends UseCase {
    protected function handle($data) {
        $user = CreateUserAction::make()->run($data);
        SendEmailAction::make()->run($user);
        LogActivityAction::make()->run($user);
        return $user;
    }
}

// ❌ Bad - too general
class UserAction extends Action { }
```

### 2. Use abstractions for interchangeable Actions (DIP)

```php
// ✅ Good - dependency on an abstract class
abstract class NotificationAction extends Action {
    // handle() is implemented by child classes
}

class SendEmailAction extends NotificationAction {
    protected function handle(User $user, string $message): bool { /* ... */ }
}

class SendSmsAction extends NotificationAction {
    protected function handle(User $user, string $message): bool { /* ... */ }
}

class NotifyUserUseCase extends UseCase {
    protected function handle(User $user, string $message) {
        // Dependency on abstraction - easy to substitute via container
        return app(NotificationAction::class)->run($user, $message);
    }
}

// ❌ Bad - impossible to substitute in tests
class NotifyUserUseCase extends UseCase {
    protected function handle(User $user, string $message) {
        // Tightly coupled to SendEmailAction, cannot be substituted
        return (new SendEmailAction())->run($user, $message);
    }
}
```

### 3. Use typing

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

### 3. UseCase for aggregating Actions

A UseCase is the same Action, but intended for coordinating multiple other Actions into a single scenario:

```php
use LeMaX10\SimpleActions\UseCase;

class RegisterUserUseCase extends UseCase
{
    // UseCase supports all Action features:
    // - Events (beforeRun, running, ran, failed, afterRun)
    // - Transactions (enabled by default)
    // - Caching
    
    protected function handle(array $data): User
    {
        // UseCase coordinates the execution of several Actions
        $user = CreateUserAction::make()->run($data['name'], $data['email']);
        
        SendWelcomeEmailAction::make()->run($user);
        
        CreateUserProfileAction::make()->run($user, $data['profile']);
        
        NotifyAdminAction::make()->run($user);
        
        return $user;
    }
}

// Using UseCase like a regular Action
$user = RegisterUserUseCase::make()
    ->remember('user-registration-' . $email, 300) // Can be cached
    ->run($data);

// UseCase supports events
RegisterUserUseCase::ran(function ($event) {
    Log::info('User registered', ['user' => $event->result]);
});

// UseCase runs in a transaction (by default)
// If any nested action fails, everything is rolled back
```

**Advantages of UseCase:**
- All Actions in a UseCase execute within a single transaction
- A UseCase can be cached entirely
- Events track the whole scenario
- Reusable business logic

## Dependency Inversion Principle (SOLID)

Actions are loaded via Laravel Service Container (`app(static::class)`), allowing the application of the dependency inversion principle:

### Approach 1: Substitution via abstract classes

```php
// Base abstract class (instead of an interface)
abstract class SendNotificationAction extends Action {

}

// Real implementation
class SendEmailNotificationAction extends SendNotificationAction {
    protected function handle(User $user, string $message): bool {
        Mail::to($user)->send(new Notification($message));
        return true;
    }
}

// Test implementation
class FakeSendNotificationAction extends SendNotificationAction {
    protected function handle(User $user, string $message): bool {
        Log::info('Fake notification sent');
        return true;
    }
}

// Registration in ServiceProvider
public function register() {
    $this->app->bind(
        SendNotificationAction::class,
        SendEmailNotificationAction::class
    );
}

// UseCase depends on abstraction, not a concrete object
class RegisterUserUseCase extends UseCase {
    protected function handle(array $data): User {
        $user = CreateUserAction::make()->run($data);
        
        // Get implementation from container
        app(SendNotificationAction::class)->run($user, 'Welcome!');
        
        return $user;
    }
}

// In tests, you can substitute
public function test_registration() {
    $this->app->bind(
        SendNotificationAction::class,
        FakeSendNotificationAction::class  // Substitution!
    );
    
    $user = RegisterUserUseCase::make()->run($data);
    
    $this->assertDatabaseHas('users', ['email' => $data['email']]);
}
```

### Approach 2: Substitution of a concrete class

```php
// UseCase depends on a concrete class
class RegisterUserUseCase extends UseCase {
    protected function handle(array $data): User {
        $user = CreateUserAction::make()->run($data);
        
        // Using a concrete class
        SendEmailAction::make()->run($user, 'Welcome!');
        
        return $user;
    }
}

// In tests, substitute the concrete class with a fake
public function test_registration() {
    // Substitute SendEmailAction with FakeEmailAction
    $this->app->bind(SendEmailAction::class, FakeEmailAction::class);
    
    $user = RegisterUserUseCase::make()->run($data);
    
    $this->assertDatabaseHas('users', ['email' => $data['email']]);
}
```

### Approach 3: Registration by string keys (less preferred, but possible)

```php
// In ServiceProvider
public function register() {
    $this->app->bind('notification.action', function ($app) {
        if ($app->environment('testing')) {
            return new FakeNotificationAction();
        }
        return new SendEmailNotificationAction();
    });
}

// UseCase uses the string key
class RegisterUserUseCase extends UseCase {
    protected function handle(array $data): User {
        $user = CreateUserAction::make()->run($data);
        
        app('notification.action')->run($user, 'Welcome!');
        
        return $user;
    }
}
```

### Dependency injection via constructor

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

// Laravel automatically resolves dependencies
$result = SendEmailAction::make()->run($user, 'Hello');
```

### Global substitution for testing

```php
// In tests
class RegisterUserTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        
        // Globally substitute heavy Actions with fakes
        $this->app->bind(SendEmailAction::class, FakeEmailAction::class);
        $this->app->bind(NotifySlackAction::class, FakeSlackAction::class);
    }
    
    public function test_user_registration() {
        // All UseCases will use fake Actions
        $user = RegisterUserUseCase::make()->run($data);
        
        $this->assertTrue($user->exists);
    }
}
```

### Conditional substitution

```php
// In ServiceProvider
public function register() {
    // Use different implementations based on environment
    if ($this->app->environment('testing')) {
        $this->app->bind(PaymentActionInterface::class, FakePaymentAction::class);
    } elseif ($this->app->environment('local')) {
        $this->app->bind(PaymentActionInterface::class, SandboxPaymentAction::class);
    } else {
        $this->app->bind(PaymentActionInterface::class, StripePaymentAction::class);
    }
}
```

### Advantages of DIP in Actions

- **Testability**: easy to substitute implementations in tests
- **Flexibility**: can change implementation without modifying UseCase
- **Isolation**: UseCases depend on abstractions, not concrete classes
- **Reusability**: different implementations of the same abstraction
- **Feature Flags**: enable/disable functionality via container

### Why not interfaces?

⚠**Important**: You cannot use interfaces with a concrete `run()` signature because the base `Action` contract already defines `run(...$args): mixed` with variadic parameters. Any other interface with a concrete signature will be incompatible.

**Solution**: Use abstract classes or substitute concrete classes via the container.

## Helpers

The package provides convenient helpers:

### `action()` - Quick Action execution

```php
// Quick execution of an Action
$result = action(CalculateAction::class, $data);

// Equivalent to:
$result = CalculateAction::make()->run($data);
```

### `usecase()` - Quick UseCase execution

```php
// Quick execution of a UseCase
$user = usecase(RegisterUserUseCase::class, $data);

// Equivalent to:
$user = RegisterUserUseCase::make()->run($data);
```

### `action_with()` - Action with configuration

Allows configuring an Action before execution via a callback:

```php
// With memoization
$user = action_with(
    GetUserAction::class,
    fn(Action $action) => $action->memo(),
    $userId
);

// With caching
$report = action_with(
    GenerateReportAction::class,
    fn(Action $action) => $action->rememberAuto('reports', 3600),
    $from, $to
);

// Combination of options
$result = action_with(
    ProcessOrderAction::class,
    fn(Action $action) => $action->memo()->withTransaction(),
    $orderId, $items
);

// With cache tags
$data = action_with(
    GetUserDataAction::class,
    fn(Action $action) => $action->remember('user-'.$id, 3600)->tags(['users']),
    $userId
);
```

### `usecase_with()` - UseCase with configuration

Allows configuring a UseCase before execution via a callback:

```php
// With memoization
$user = usecase_with(
    RegisterUserUseCase::class,
    fn(UseCase $usecase) => $usecase->memo(),
    $userId
);

// With caching
$report = usecase_with(
    GenerateFinanceReportUseCase::class,
    fn(UseCase $usecase) => $usecase->rememberAuto('reports', 3600),
    $from, $to
);

// Combination of options
$result = usecase_with(
    GenerateFinanceReportUseCase::class,
    fn(UseCase $usecase) => $usecase->memo()->withTransaction(),
    $orderId, $items
);

// With cache tags
$data = usecase_with(
    GenerateFinanceReportFromUserUseCase::class,
    fn(UseCase $usecase) => $usecase->remember('user-'.$userModel->getKey(), 3600)->tags(['reports']),
    $userModel
);
```

### `generate_args_hash()` - Generating an argument hash

Function for generating an MD5 hash from an array of arguments. Used internally in the package for memoization and caching, but also available for external use:

```php
// Generates a hash from arguments
$hash = generate_args_hash([$userId, $type, ['option' => 'value']]);
// Result: "5d41402abc4b2a76b9719d911017c592"

// Using to create unique keys
$cacheKey = "custom-key:" . generate_args_hash($params);
Cache::remember($cacheKey, 3600, fn() => heavyCalculation($params));
```

**Why does it exist?:**
- Uses `json_encode` for performance (usually faster than `serialize`)
- Automatic fallback to `serialize` for complex objects (Closure, Resources)
- Returns an MD5 hash for key compactness
- To avoid duplication in several places (Caching, memoization), it is extracted into a separate helper

### Usage in controllers

Helpers are especially convenient in controllers and services:

```php
class UserController extends Controller
{
    public function register(Request $request)
    {
        $user = usecase(RegisterUserUseCase::class, $request->validated());
        
        return response()->json(['user' => $user]);
    }
    
    public function show(int $userId) // Called via injection
    {
        // With memoization to avoid repeated queries (Rough example for demonstration)
        $user = action_with(
            GetUserAction::class,
            static fn(Action $action) => $action->memo(),
            $userId
        );
        
        return view('user.show', compact('user'));
    }
    
    public function sendEmail(User $user)
    {
        action(SendEmailAction::class, $user, 'Welcome!');
        
        return back()->with('success', 'Email sent');
    }
}
```

### 4. Cache heavy operations

```php
class GenerateReportAction extends Action
{
    protected function handle(Carbon $from, Carbon $to): Report
    {
        // Heavy calculations
    }
}

$report = GenerateReportAction::make()
    ->rememberAuto('reports', 3600)
    ->tags(['reports'])
    ->run($from, $to);
```

## License

GPL-2.0-only

## Author

Vladimir Pyankov (aka LeMaX10)
- Email: v@pyankov.pro
- Website: https://pyankov.pro