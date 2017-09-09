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

## 第2章 初步实现 API 系统

### 2.1 注册路由

> <http://d.laravel-china.org/docs/5.4/routing>

```php
<?php
// routes\api.php
Route::group(['prefix'=>'v1'], function (){
    Route::resource('lesson','LessonController');
});
```

查看已注册的路由

```bash
# 注意这里不是 api:route
php artisan route:list
```

![查看已注册的路由](https://imgcdn.wangyan.org/l/laravel-route.jpg)

### 2.2 编辑控制器

> <http://d.laravel-china.org/docs/5.4/controllers>    
> <http://d.laravel-china.org/docs/5.4/eloquent>     

```php
<?php
// app\Http\Controllers\LessonController.php
    public function index()
    {
        return Lesson::all();
    }

    public function show(Lesson $lesson)
    {
        // 旧版本
        //return Lesson::findOrfail($id);
        return $lesson;
    }
```

访问：<http://api.dev/api/v1/lesson> 和 <http://api.dev/api/v1/lesson/1>

## 第3章 API 字段映射

### 3.1 附加状态到 Response 响应

> <http://d.laravel-china.org/docs/5.4/responses>

```php
<?php
// app\Http\Controllers\LessonController.php
    public function index()
    {
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>Lesson::all()->toArray()
        ]);
    }

    public function show(Lesson $lesson)
    {
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$lesson->toArray()
        ]);
    }

```

### 3.2 隐藏数据库字段结构

> <http://php.net/manual/zh/function.array-map.php>     

```php
<?php
// app\Http\Controllers\LessonController.php
    public function index()
    {
        $lessons = Lesson::all();
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->transformCollection($lessons)
        ]);
    }

    public function show(Lesson $lesson)
    {
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->transform($lesson->toArray())
        ]);
    }
    
    public function transformCollection($lessons)
    {
        return array_map([$this, 'transform'],$lessons->toArray());
    }

    public function transform($lessons)
    {
        return [
            'title'=>$lessons['title'],
            'content'=>$lessons['body'],
            'is_free'=>(boolean) $lessons['free']
        ];
    }
```

## 第4章 重构 API 代码

```php
<?php
// app\Transformer\LessonTransfromer.php
namespace App\Transformer;

class LessonTransfromer extends Transformer
{
    public function transform($lessons)
    {
        return [
            'title'=>$lessons['title'],
            'content'=>$lessons['body'],
            'is_free'=>(boolean) $lessons['free']
        ];
    }
}
```

```php
<?php
// app\Transformer\Transformer.php
namespace App\Transformer;

abstract class Transformer
{
    public function transformCollection($items)
    {
        return array_map([$this, 'transform'],$items->toArray());
    }

    public abstract function transform($items);
}
```

```php
<?php
// app\Http\Controllers\LessonController.php
use App\Transformer\LessonTransfromer;

class LessonController extends Controller
{
    protected $lessonTransfromer;

    // 依赖注入
    public function __construct(LessonTransfromer $lessonTransfromer)
    {
        $this->lessonTransfromer = $lessonTransfromer;
    }

    public function index()
    {
        $lessons = Lesson::all();
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->lessonTransfromer->transformCollection($lessons)
        ]);
    }

    public function show(Lesson $lesson)
    {
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->lessonTransfromer->transform($lesson->toArray())
        ]);
    }
}
```

## 第5章 处理错误返回

生成 ApiController

```shell
php artisan make:controller ApiController
```

```php
<?php
// App\Http\Controllers\ApiController
class ApiController extends Controller
{

    protected $statusCode = 200;

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function responseNotFound($message = 'Not Found')
    {
        return $this->setStatusCode(404)->responseError($message);
    }

    public function responseError($message)
    {
        return $this->response([
            'status'=>'failed',
            'error'=>[
                'status_code'=>$this->getStatusCode(),
                'message'=>$message
            ]
        ]);
    }

    public function response($data)
    {
        return \Response::json($data);
    }
}
```

```php
<?php
// LessonController.php
class LessonController extends ApiController
{
    public function index()
    {
        $lessons = Lesson::all();
        return $this->response([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->lessonTransfromer->transformCollection($lessons)
        ]);
    }

    public function show(Lesson $lesson)
    {
        return $this->response([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->lessonTransfromer->transform($lesson->toArray())
        ]);
    }
}
```

<http://d.laravel-china.org/docs/5.4/errors>

```php
<?php
// app\Exceptions\Handler.php
    use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFoundException;
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            $api = new \App\Http\Controllers\ApiController;
            return $api->responseNotFound('404 not found');
        }
        return parent::render($request, $exception);
    }
```

## 第6章 对请求API的用户认证

生成用户验证模块

```shell
php artisan make:auth
```

```php
<?php
// app\Http\Controllers\Auth\RegisterController.php
use App\Models\User;
```

编辑控制器

```php
<?php
// app\Http\Controllers\LessonController.php
    public function __construct(LessonTransfromer $lessonTransfromer)
    {
        $this->middleware('auth.basic',['only'=>['index', 'store']]);
    }

    public function store(Request $request)
    {
        if (! $request->get('title') or ! $request->get('body') or ! $request->get('free')){
            return $this->setStatusCode(422)->response('validata fails');
        }

        Lesson::create($request->all());

        return $this->setStatusCode(201)->response([
            'status'=>'success',
            'message'=>'lesson created'
        ]);
    }
```

```php
<?php
// app\Models\Lesson.php
class Lesson extends Model
{
    protected $fillable = ['title','body','free'];
}
```

最后使用 postman 调试 `http://api.dev/api/v1/lesson/`