<?php

namespace Modules\Website\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Website\Models\PharmaQuickOrderRequest;

final class SendPharmaQuickOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $requestId,
        public readonly array $recipients
    ) {}

    public function handle(): void
    {
        $record = PharmaQuickOrderRequest::query()->find($this->requestId);
        if (! $record || $this->recipients === []) {
            return;
        }

        Mail::raw(
            "Yêu cầu đặt hàng nhanh Pharma #{$record->id} vừa được gửi.\n"
            ."Trạng thái: Chờ xét duyệt.\n"
            ."Loại người mua: {$record->buyer_type}\n"
            ."Số mặt hàng: ".count($record->requested_items ?? [])."\n"
            ."Vui lòng đăng nhập hệ thống để kiểm tra và xác minh điều kiện mua thuốc.\n"
            ."Chưa có phiếu xuất kho được tạo.",
            function ($message) use ($record): void {
                $message->to($this->recipients)
                    ->subject("Yêu cầu đặt hàng Pharma #{$record->id} - Chờ xét duyệt");
            }
        );
    }
}
