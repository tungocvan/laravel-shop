<?php

namespace Modules\Website\Http\Controllers\Api;
use App\Http\Controllers\Controller;


class WebsiteController extends Controller
{
   public function index()
    {
        return response()->json([
            'status' => 'Api Website success',            
        ]);
    }  

}
