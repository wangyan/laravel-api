<?php
namespace App\Api\Controllers;

use App\Api\Transformer\LessonTransformer;
use App\Models\Lesson;

class LessonController extends BaseController
{
    public function index()
    {
        // Responding With An Array
        // $lessons =  Lesson::all();
        // return $this->response->array($lessons->toArray());

        // Responding With A Collection Of Items
        // return $lessons  = Lesson::all();
        // return $this->collection($lessons,new LessonTransformer());

        // Responding With Paginated Items
        $lessons  = Lesson::paginate(10);
        return $this->paginator($lessons, new LessonTransformer);
    }

    /**
     * 通过实例化 LessonTransformer 来处理 $lesson 数据，然后再返回
     * LessonTransformer 类必须继承 TransformerAbstract 抽象类
     * 因为 TransformerAbstract 类提供了 item 和 collection 方法
     * 此外 LessonTransformer 类中必须有 transform 方法
     */
    public function show($id)
    {
        $lesson = Lesson::findOrFail($id);
        // Responding With A Single Item
        return $this->response->item($lesson, new LessonTransformer);
    }

}