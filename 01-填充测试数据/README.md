#  Laravel 5 开发 API 笔记

> 环境：homestead 5.3.2     
> 版本：laravel 5.4     

## Laravel 准备工作

```bash
composer config -g repo.packagist composer https://packagist.laravel-china.org
composer create-project --prefer-dist laravel/laravel api 5.4.*
```

将 `App\User` 移动移动到 `App\Models\User` 目录

```php
<?php
// App\Models\User.php
namespace App\Models;
```

```php
<?php
// config\auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

## 第1章 填充测试数据

### 1.1 生成数据库表

> <http://d.laravel-china.org/docs/5.4/migrations>     

创建数据库表

```bash
php artisan make:migration create_lessons_table --create=lessons
```

创建模型和控制器（这里将模型放到 Models 目录下）

```bash
php artisan make:controller LessonController -m Models\\Lesson
```

修改 migrate

```php
<?php
// database\migrations\2017_07_01_182454_create_lessons_table.php
Schema::create('lessons', function (Blueprint $table) {
    $table->increments('id');
    $table->string('title');
    $table->text('body');
    $table->boolean('free');
    $table->timestamps();
});
```

解决导入错误

```php
<?php
// app\Providers\AppServiceProvider.php

use Illuminate\Support\Facades\Schema;

public function boot()
{
    Schema::defaultStringLength(191);
}
```

生成数据表

```bash
php artisan migrate
```

### 1.2 Eloquent 模型工厂填充数据

> <http://d.laravel-china.org/docs/5.4/database-testing#writing-factories>     

修改 `ModelFactory.php`

```php
<?php
// database\factories\ModelFactory.php
$factory->define(App\Models\Lesson::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'title' => $faker->sentence,
        'body' => $faker->paragraph,
        'free' => $faker->boolean(),
    ];
});
```

方法一：使用 tinker 生成数据

```bash
php artisan tinker
>>>factory(App\Models\Lesson::class,60)->create();
>>> quit
```

方法二：编写 Seeders  生成数据

> <http://d.laravel-china.org/docs/5.4/seeding>     

```bash
php artisan make:seeder LessonsTableSeeder
```

```php
<?php
//  database\seeds\LessonsTableSeeder.php
    public function run()
    {
        factory(App\Lesson::class, 50)->create();
    }
```

```bash
php artisan db:seed --class=LessonsTableSeeder
```

### 1.3 小结

```bash
php artisan migrate

php artisan tinker
>>>factory(App\Models\Lesson::class,60)->create();
>>> quit
```