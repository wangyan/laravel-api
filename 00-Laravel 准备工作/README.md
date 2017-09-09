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
