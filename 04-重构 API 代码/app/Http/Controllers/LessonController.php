<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Transformer\LessonTransfromer;

class LessonController extends Controller
{
    protected $lessonTransfromer;

    /**
     * LessonController constructor.
     * @param LessonTransfromer $lessonTransfromer
     */
    public function __construct(LessonTransfromer $lessonTransfromer)
    {
        $this->lessonTransfromer = $lessonTransfromer;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lessons = Lesson::all();
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->lessonTransfromer->transformCollection($lessons)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function show(Lesson $lesson)
    {
        return \Response::json([
            'status'=>'Success',
            'status_code'=>'200',
            'data'=>$this->lessonTransfromer->transform($lesson->toArray())
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function edit(Lesson $lesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lesson $lesson)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lesson $lesson)
    {
        //
    }

    /**
     * @param $lessons
     * @return array
     */
    public function transformCollection($lessons)
    {
        return array_map([$this, 'transform'],$lessons->toArray());
    }

    /**
     * @param $lessons
     * @return array
     */
    public function transform($lessons)
    {
        return [
            'title'=>$lessons['title'],
            'content'=>$lessons['body'],
            'is_free'=>(boolean) $lessons['free']
        ];
    }
}
