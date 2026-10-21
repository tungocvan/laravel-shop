<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Jobs\LookupOfficialSourceLegalJob;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\OfficialSourceLegalLookup;

class OfficialSourceLegalLookupController extends Controller
{
    public function store(OfficialSourceFacility $facility): JsonResponse
    {
        $result = DB::transaction(function () use ($facility): array {
            $lookup = OfficialSourceLegalLookup::query()->where('facility_id', $facility->id)->lockForUpdate()->first();
            if ($lookup && in_array($lookup->status, ['queued', 'running'], true)) {
                return [$lookup, false];
            }

            $lookup ??= new OfficialSourceLegalLookup(['facility_id' => $facility->id]);
            $lookup->fill([
                'status' => 'queued',
                'query_name' => $facility->facility_name,
                'candidates' => null,
                'candidate_count' => 0,
                'selected_tax_code' => null,
                'requested_by' => auth('admin')->id(),
                'reviewed_by' => null,
                'requested_at' => now(),
                'completed_at' => null,
                'reviewed_at' => null,
                'error_message' => null,
            ]);
            $lookup->save();

            LookupOfficialSourceLegalJob::dispatch($lookup->id)->afterCommit();

            return [$lookup, true];
        });

        return response()->json([
            'message' => $result[1] ? 'Đã đưa tra cứu vào hàng đợi.' : 'Cơ sở này đang được tra cứu.',
            'lookup' => $this->payload($result[0]),
        ], 202);
    }

    public function show(OfficialSourceFacility $facility): JsonResponse
    {
        $lookup = OfficialSourceLegalLookup::query()->where('facility_id', $facility->id)->first();

        return response()->json(['lookup' => $lookup ? $this->payload($lookup) : null]);
    }

    public function review(Request $request, OfficialSourceFacility $facility): JsonResponse
    {
        $data = $request->validate(['tax_code' => ['required', 'string', 'regex:/^[0-9]{10}([0-9]{3})?$/']]);
        $lookup = OfficialSourceLegalLookup::query()->where('facility_id', $facility->id)->firstOrFail();

        if ($lookup->status !== 'ready') {
            return response()->json(['message' => 'Chỉ có thể xác nhận kết quả đã tra cứu.'], 422);
        }

        $found = collect($lookup->candidates ?? [])->contains(
            fn ($candidate) => ($candidate['tax_code'] ?? null) === $data['tax_code']
        );
        if (! $found) {
            return response()->json(['message' => 'Mã số thuế không thuộc danh sách ứng viên.'], 422);
        }

        $lookup->update([
            'status' => 'reviewed',
            'selected_tax_code' => $data['tax_code'],
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Đã xác nhận ứng viên. Chưa ghi vào hồ sơ Partner.',
            'lookup' => $this->payload($lookup),
        ]);
    }

    private function payload(OfficialSourceLegalLookup $lookup): array
    {
        return [
            'status' => $lookup->status,
            'query_name' => $lookup->query_name,
            'candidates' => $lookup->candidates ?? [],
            'candidate_count' => $lookup->candidate_count,
            'selected_tax_code' => $lookup->selected_tax_code,
            'error_message' => $lookup->error_message,
            'completed_at' => $lookup->completed_at?->toIso8601String(),
        ];
    }
}
