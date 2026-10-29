<?php
namespace Modules\Demo\Http\Controllers\Projects\HumanResources;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
final class HumanResourcesController extends Controller { public function __invoke(): View { return view('demo.quan-ly-nhan-su.index'); } }
