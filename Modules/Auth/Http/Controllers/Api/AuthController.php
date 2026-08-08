<?php

namespace Modules\Auth\Http\Controllers\Api;
use App\Http\Controllers\Controller;


class AuthController extends Controller
{
   public function index()
    {
        return response()->json([
            'status' => 'Api Auth success',            
        ]);
    }  

}
