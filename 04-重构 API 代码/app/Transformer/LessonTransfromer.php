<?php
namespace App\Transformer;

/**
 * Class LessonTransfromer
 * @package App\Transformer
 */
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