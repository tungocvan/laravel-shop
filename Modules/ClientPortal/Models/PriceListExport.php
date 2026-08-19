<?php
namespace Modules\ClientPortal\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class PriceListExport extends Model
{
    use HasUuids;
    protected $table='client_portal_price_list_exports';
    protected $guarded=[];
    protected $casts=['selected_ids'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];
    public $incrementing=false;
    protected $keyType='string';
}
