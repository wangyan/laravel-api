<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }


    /**
     * @param $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param string $message
     * @return mixed
     */
    public function responseNotFound($message = 'Not Found')
    {
        return $this->setStatusCode(404)->responseError($message);
    }

    /**
     * @param $message
     * @return mixed
     */
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

    /**
     * @param $data
     * @return mixed
     */
    public function response($data)
    {
        return \Response::json($data);
    }
}
