<?php

namespace Modules\Invoices\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Invoices\Models\Invoices;

class InvoiceService
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->orderedQuery($filters)->paginate($this->normalizePerPage($perPage));
    }

    public function filterOptions(array $filters): array
    {
        $query = $this->filteredQuery($filters, false);
        return [
            'names' => (clone $query)->whereNotNull('name')->where('name', '<>', '')->distinct()->orderBy('name')->pluck('name')->all(),
            'tax_codes' => (clone $query)->whereNotNull('tax_code')->where('tax_code', '<>', '')->distinct()->orderBy('tax_code')->pluck('tax_code')->all(),
        ];
    }

    public function years(): array
    {
        $yearExpression = DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%Y', issued_date) AS INTEGER)" : 'YEAR(issued_date)';
        return Invoices::query()->whereNotNull('issued_date')->selectRaw($yearExpression.' as year')->distinct()->orderByDesc('year')->pluck('year')->filter()->map(fn ($year) => (int) $year)->values()->all();
    }

    public function statistics(array $filters): array
    {
        $row = $this->filteredQuery($filters)
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount_sum')
            ->selectRaw('COALESCE(SUM(vat_amount), 0) as vat_amount_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tax_rate = 5 THEN total_amount ELSE 0 END), 0) as tax_rate_5_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tax_rate = 8 THEN total_amount ELSE 0 END), 0) as tax_rate_8_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tax_rate = 10 THEN total_amount ELSE 0 END), 0) as tax_rate_10_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tax_rate IS NOT NULL AND tax_rate NOT IN (5, 8, 10) THEN total_amount ELSE 0 END), 0) as tax_rate_other_sum')->first();
        return ['count'=>(int)($row?->invoice_count??0),'total_amount'=>$row?->total_amount_sum??0,'vat_amount'=>$row?->vat_amount_sum??0,'by_tax_rate'=>[5=>$row?->tax_rate_5_sum??0,8=>$row?->tax_rate_8_sum??0,10=>$row?->tax_rate_10_sum??0,'other'=>$row?->tax_rate_other_sum??0]];
    }

    public function dashboard(): array
    {
        $summary = Invoices::query()->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'sold' THEN total_amount ELSE 0 END), 0) as sold_amount")->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'purchase' THEN total_amount ELSE 0 END), 0) as purchase_amount")->selectRaw("COUNT(DISTINCT CASE WHEN invoice_type = 'sold' AND name IS NOT NULL AND name <> '' THEN name END) as sold_customers")->selectRaw("COUNT(DISTINCT CASE WHEN invoice_type = 'purchase' AND name IS NOT NULL AND name <> '' THEN name END) as purchase_customers")->first();
        $yearExpression = DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%Y', issued_date) AS INTEGER)" : 'YEAR(issued_date)';
        $yearly = Invoices::query()->whereNotNull('issued_date')->selectRaw($yearExpression.' as year, COALESCE(SUM(CASE WHEN invoice_type="sold" THEN total_amount ELSE 0 END), 0) as sold_total, COALESCE(SUM(CASE WHEN invoice_type="purchase" THEN total_amount ELSE 0 END), 0) as purchase_total')->groupByRaw($yearExpression)->orderByDesc('year')->get()->toArray();
        return ['sold_amount'=>$summary?->sold_amount??0,'purchase_amount'=>$summary?->purchase_amount??0,'sold_customers'=>(int)($summary?->sold_customers??0),'purchase_customers'=>(int)($summary?->purchase_customers??0),'yearly'=>$yearly];
    }

    public function selected(array $ids): Collection
    {
        $ids = collect($ids)->filter(fn ($id)=>filter_var($id,FILTER_VALIDATE_INT)!==false&&(int)$id>0)->map(fn($id)=>(int)$id)->unique()->values()->all();
        return $ids === [] ? collect() : Invoices::query()->whereKey($ids)->orderByDesc('issued_date')->orderByDesc('id')->get();
    }

    public function filter(array $filters = [], bool $returnBuilder = false): Builder|Collection
    {
        $query = $this->orderedQuery($filters);
        return $returnBuilder ? $query : $query->get();
    }

    public function filteredBuilder(array $filters = []): Builder { return $this->filteredQuery($filters); }

    private function orderedQuery(array $filters): Builder
    {
        $query = $this->filteredQuery($filters);
        [$column,$direction] = match($filters['sort']??'date_desc'){'date_asc'=>['issued_date','asc'],'amount_desc'=>['total_amount','desc'],'amount_asc'=>['total_amount','asc'],'invoice_desc'=>['invoice_number','desc'],'invoice_asc'=>['invoice_number','asc'],'partner_asc'=>['name','asc'],'partner_desc'=>['name','desc'],default=>['issued_date','desc']};
        return $query->orderBy($column,$direction)->orderByDesc('id');
    }

    private function filteredQuery(array $filters, bool $includeTaxRate = true): Builder
    {
        $query = Invoices::query();
        foreach (['lookup_code','symbol','invoice_number','type','tax_code','name','address','email','phone'] as $field) {
            if (filled($filters[$field]??null)) $query->where($field,'like','%'.$filters[$field].'%');
        }
        if (filled($filters['invoice_type']??null)) $query->where('invoice_type',strtolower((string)$filters['invoice_type']));
        if (filled($filters['issued_date_from']??null)) $query->whereDate('issued_date','>=',$filters['issued_date_from']);
        if (filled($filters['issued_date_to']??null)) $query->whereDate('issued_date','<=',$filters['issued_date_to']);
        if ($includeTaxRate && filled($filters['tax_rate']??null) && $filters['tax_rate']!=='all') {
            $filters['tax_rate']==='other' ? $query->whereNotNull('tax_rate')->whereNotIn('tax_rate',[5,8,10]) : $query->where('tax_rate',$filters['tax_rate']);
        }
        $pdfStatus = $filters['pdf_status'] ?? 'all';
        if ($pdfStatus === 'available') $query->whereHas('file', fn($q)=>$q->where('status','available'));
        elseif ($pdfStatus === 'error') $query->whereHas('file', fn($q)=>$q->where('status','error'));
        elseif ($pdfStatus === 'missing') $query->whereDoesntHave('file', fn($q)=>$q->whereIn('status',['available','error']));
        return $query;
    }

    private function normalizePerPage(int $perPage): int { return in_array($perPage,[10,25,50,100],true)?$perPage:10; }
}
