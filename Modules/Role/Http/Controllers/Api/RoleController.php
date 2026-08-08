<?php

namespace Modules\Role\Http\Controllers\Api;
use App\Http\Controllers\Controller;


class RoleController extends Controller
{
   public function index()
    {
        return response()->json([
            'status' => 'Api Role success',            
        ]);
    }  

}
