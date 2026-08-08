<?php

namespace Modules\Admin\Http\Controllers\Api;
use App\Http\Controllers\Controller;


class AdminController extends Controller
{
   public function index()
    {
        return response()->json([
            'status' => 'Api Admin success',            
        ]);
    }  

}
