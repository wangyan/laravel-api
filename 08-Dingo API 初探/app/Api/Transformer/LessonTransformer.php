<?php
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