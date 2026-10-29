<?php
namespace App\Http\Controllers\Demo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
final class EquipmentMaintenanceDatasetController extends Controller
{
    private const DIR = 'demo-datasets/equipment-maintenance';
    private const LIMIT = 10485760;
    private const MAX_FILES = 100;
    private function authorizeDataset(Request $request): void
    {
        $secret=config('warehouse_demo.backup_code');
        abort_unless(is_string($secret)&&strlen($secret)>=16,503);
        $given=$request->header('X-Demo-Backup-Code','');
        abort_unless(is_string($given)&&hash_equals($secret,$given),403);
    }
    private function validData(mixed $data): bool
    {
        return is_array($data)&&count($data)<=100&&isset($data['tb'],$data['plans'],$data['logs'])
            &&array_is_list($data['tb'])&&array_is_list($data['plans'])&&array_is_list($data['logs'])
            &&$this->safeTree($data);
    }
    private function safeTree(mixed $value,int $depth=0): bool
    {
        if($depth>24)return false;
        if(is_array($value)){
            if(count($value)>20000)return false;
            foreach($value as $key=>$item){if(is_string($key)&&strlen($key)>120)return false;if(!$this->safeTree($item,$depth+1))return false;}
            return true;
        }
        return is_null($value)||is_bool($value)||is_int($value)||(is_float($value)&&is_finite($value))
            ||(is_string($value)&&strlen($value)<=2000000);
    }
    private function read(string $path): ?array
    {
        $disk=Storage::disk('local');
        if(!$disk->exists($path)||$disk->size($path)>self::LIMIT)return null;
        try{$payload=json_decode($disk->get($path),true,512,JSON_THROW_ON_ERROR);}catch(\JsonException $e){return null;}
        if(!is_array($payload)||!is_string($payload['name']??null)||!is_string($payload['savedAt']??null)
            ||!$this->validData($payload['data']??null))return null;
        return $payload;
    }
    public function index(Request $request): JsonResponse
    {
        $this->authorizeDataset($request);$items=[];
        foreach(Storage::disk('local')->files(self::DIR) as $file){
            $id=pathinfo($file,PATHINFO_FILENAME);
            if(!preg_match('/^[a-z0-9-]{1,80}$/',$id))continue;
            $payload=$this->read($file);
            if($payload!==null)$items[]=['id'=>$id,'name'=>$payload['name'],'savedAt'=>$payload['savedAt']];
        }
        usort($items,fn($a,$b)=>strcmp($b['savedAt'],$a['savedAt']));
        return response()->json(['datasets'=>$items])->header('Cache-Control','no-store');
    }
    public function show(Request $request,string $dataset): JsonResponse
    {
        $this->authorizeDataset($request);
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/',$dataset),404);
        $payload=$this->read(self::DIR.'/'.$dataset.'.json');abort_if($payload===null,404);
        return response()->json($payload)->header('Cache-Control','no-store');
    }
    public function store(Request $request): JsonResponse
    {
        $this->authorizeDataset($request);abort_if(strlen($request->getContent())>self::LIMIT,413);
        $validated=Validator::make($request->all(),['name'=>['required','string','min:2','max:100'],'data'=>['required','array']])->validate();
        abort_unless($this->validData($validated['data']),422);
        $disk=Storage::disk('local');abort_if(count($disk->files(self::DIR))>=self::MAX_FILES,409);
        $payload=['name'=>trim($validated['name']),'savedAt'=>now()->toIso8601String(),'data'=>$validated['data']];
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        abort_if(strlen($json)>self::LIMIT,413);
        $id=substr(Str::slug($payload['name'])?:'bao-tri',0,40).'-'.Str::lower(Str::random(12));
        abort_unless($disk->put(self::DIR.'/'.$id.'.json',$json),500);
        return response()->json(['id'=>$id,'name'=>$payload['name']],201)->header('Cache-Control','no-store');
    }
}
