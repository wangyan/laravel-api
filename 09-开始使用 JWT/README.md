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

## 第7章 引入 Dingo API  和 JWT

### 7.1 引入 Dingo API

> 官方： <https://github.com/dingo/api>     
> 中文： <https://github.com/liyu001989/dingo-api-wiki-zh>    

```php
<?php
// api\composer.json
"require": {
    "dingo/api": "1.0.*@dev"
}
```

```bash
composer update
```

注册 `provider `

```bash
# config\app.php
'providers' => [
    Dingo\Api\Provider\LaravelServiceProvider::class,
]
```

添加 `Facades`

```bash
# config\app.php
'aliases' => [
    'API' => Dingo\Api\Facade\API::class,
    'DingoRoute' => Dingo\Api\Facade\Route::class,
]
```

发布配置文件

```bash
php artisan vendor:publish --provider="Dingo\Api\Provider\LaravelServiceProvider"
```

### 7.2 引入 JWT

> 主页 <https://github.com/tymondesigns/jwt-auth/>    
> 安装文档 <https://github.com/tymondesigns/jwt-auth/wiki/Installation>    

```php
<?php
// api\composer.json
"require": {
    "tymon/jwt-auth": "0.5.*"
}
```

```bash
composer update
```

注册 `provider `

```bash
# config\app.php
'providers' => [
    Tymon\JWTAuth\Providers\JWTAuthServiceProvider::class,
]
```

添加 `Facades`

```bash
# config\app.php
'aliases' => [
    'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class,
    'JWTFactory' => Tymon\JWTAuth\Facades\JWTFactory::class,
]
```

发布配置文件

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\JWTAuthServiceProvider"
```

生成key

```bash
php artisan jwt:generate
```

## 第8章 Dingo API 初探

### 8.1 Dingo API 配置

> <https://github.com/liyu001989/dingo-api-wiki-zh/blob/master/Configuration.md>

```bash
# api.dev\.env
API_STANDARDS_TREE=vnd
API_PREFIX=api
API_VERSION=v1
API_DEBUG=true
```

> <https://github.com/liyu001989/dingo-api-wiki-zh/blob/master/Authentication.md>

```bash
# config\api.php

    // 仅供参考
    'auth' => [
        'basic' => function($app) {
            return new Dingo\Api\Auth\Provider\Basic($app['auth']);
        },
        'jwt' => function ($app){
            return new Dingo\Api\Auth\Provider\JWT($app['Tymon\JWTAuth\JWTAuth']);
        },
    ],

    'auth' => [
        'basic' => 'Dingo\Api\Auth\Provider\Basic',
        'jwt' => 'Dingo\Api\Auth\Provider\JWT',
    ],
```

添加路由中间件

```bash
# app\Http\Kernel.php
    protected $routeMiddleware = [
        'jwt.auth' => \Tymon\JWTAuth\Middleware\GetUserFromToken::class,
        'jwt.refresh' => \Tymon\JWTAuth\Middleware\RefreshToken::class,
    ];
```

### 8.2  Dingo API 路由

这里的路由就是 `Dingo API Endpoints` ，端点是路由的另一种说法,详细用法见下面文档。

> <https://github.com/dingo/api/wiki/Creating-API-Endpoints>    

> 请注意：`v1` 不能乱用，它已经在你的环境配置中定义好了。 `API_VERSION=v1`

```php
<?php
$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Api\Controllers'], function ($api) {
        $api->get('lessons', 'LessonController@index');
    });
});
```

查看路由,注意区别 `route:lists`

```bash
php artisan api:route
```

地址是 ` /api/lessons` 没有 `v1` 前缀，访问 `http://api.dev/api/lessons/`  看到已经正常。

![查看已注册的路由](images/api-route.jpg)

### 8.2  Dingo API 响应

> <https://github.com/dingo/api/wiki/Responses>    

下面例子演示如何使用响应生成器 (Response Builder)

#### 8.2.1 创建 BaseController

为了使用 `Dingo\Api\Routing\Helpers trait` 首先要创建基础控制器

```php
<?php
// app\Api\Controllers\BaseController.php
namespace App\Api\Controllers;

use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;

class BaseController extends Controller
{
    use Helpers;
}
```

#### 8.2.2  响应一个数组

意思是直接以数组形式返回 $lessons 原始数据

创建 LessonController 继承上面的 BaseController

```php
<?php
// app\Api\Controllers\LessonController.php
namespace App\Api\Controllers;

use App\Models\Lesson;

class LessonController extends BaseController
{
    public function index()
    {
        $lessons =  Lesson::all();
        return $this->response->array($lessons->toArray());
    }
}
```

<http://api.dev/api/lessons>

#### 8.2.3  响应一个元素

通过实例化 LessonTransformer 来处理 $lesson 数据，然后再返回经过处理的数据。

首先，新建 `LessonController` 类

```php
<?php
// app\Api\Controllers\LessonController.php
namespace App\Api\Controllers;

use App\Api\Transformer\LessonTransformer;
use App\Models\Lesson;

class LessonController extends BaseController
{
    /**
     * 通过实例化 LessonTransformer 来处理 $lesson 数据，然后再返回
     * LessonTransformer 类必须继承 TransformerAbstract 抽象类
     * 因为 TransformerAbstract 类提供了 item 和 collection 方法
     * 此外 LessonTransformer 类中必须有 transform 方法
     */
    public function show($id)
    {
        $lesson = Lesson::findOrFail($id);
        return $this->response->item($lesson, new LessonTransformer);
    }

}
```

接着，新建 `LessonTransformer` ，命名空间是 `App\Api\Transformer`

```php
<?php
// app\Api\Transformer\LessonTransformer.php

namespace App\Api\Transformer;

use App\Models\Lesson;
use League\Fractal\TransformerAbstract;

class LessonTransformer extends TransformerAbstract
{
    public function transform(Lesson $lesson)
    {
        return [
            'title'=>$lesson['title'],
            'content'=>$lesson['body'],
            'is_free'=>(boolean) $lesson['free']
        ];
    }
}
```

#### 8.2.4  响应一个元素集合

 `collection` 会自动调用  `new LessonTransformer()` 中的 `transform` 方法

 意思就是上面 `LessonTransformer` 类中必须有 `transform` 方法

```php
<?php
// app\Api\Controllers\LessonController.php

namespace App\Api\Controllers;

use App\Api\Transformer\LessonTransformer;
use App\Models\Lesson;

class LessonController extends BaseController
{
    public function index()
    {
        $lessons  = Lesson::all();
        return $this->collection($lessons,new LessonTransformer());
    }
}
```

#### 8.2.5  响应分页

```php
<?php
// app\Api\Controllers\LessonController.php

namespace App\Api\Controllers;

use App\Api\Transformer\LessonTransformer;
use App\Lesson;

class LessonController extends BaseController
{
    public function index()
    {
        $lessons  = Lesson::paginate(10);
        return $this->paginator($lessons, new LessonTransformer);
    }
}
```

## 第9章 开始使用 JWT 

### 9.1 路由

```php
<?php
// routes\api.php
$api = app('Dingo\Api\Routing\Router');

$api->version('v2', function ($api) {
    $api->group(['namespace' => 'App\Api\Controllers'], function ($api) {
        $api->post('user/login', 'AuthController@authenticate');
        $api->post('user/register', 'AuthController@register');
        $api->group(['middleware' => 'jwt.auth'], function ($api) {
            $api->get('lessons', 'LessonController@index');
            $api->get('lesson/{id}', 'LessonController@show');
        });
    });
});
```

### 9.2 创建  AuthController

定义 user 模型位置
```php
<?php
// api\config\jwt.php
 'user' => 'App\Models\User',
```

创建 AuthController，注意这里 use 了 Request

```php
<?php
// app\Api\Controllers\AuthController.php
namespace App\Api\Controllers;

use App\Models\User;
// 注意这里 use 了  Request
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends BaseController
{
    public function authenticate(Request $request)
    {
        // 返回请求中 email 和 password 的值
        $credentials = $request->only('email', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        // 登录成功后，以 json 形式返回 token 值
        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $newUser = [
            'email' => $request->get('email'),
            'name' =>  $request->get('name'),
            'password' =>  $request->get('password'),
        ];

        $user = User::create($newUser);
        $token = JWTAuth::fromUser($user);
        // 注册成功后返回 token
        return response()->json(compact('token'));
    }
}
```

### 9.3 调试 

登录调试：post 请求，body 携带 emial 和 password 参数

> <http://api.dev/api/user/login>

注册调试：post 请求，body 携带 emial、name 和 password 参数

> <http://api.dev/api/user/register>

先登录获取 token ，然后显示内容

> <http://api.dev/api/lesson/1?token=xxx>
> <http://api.dev/api/lessons?token=xxx>