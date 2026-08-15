<?php

namespace Modules\Administrative\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Administrative\Enums\HistoryActorType;
use Modules\Administrative\Enums\SubmissionAction;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Models\AdministrativeSubmission;

class AdministrativeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = AdministrativeProcedure::query()->orderBy('sort_order')->get();

        if ($procedures->isEmpty()) {
            $this->call(ProcedureSeeder::class);
            $procedures = AdministrativeProcedure::query()->orderBy('sort_order')->get();
        }

        $statuses = [
            SubmissionStatus::Pending,
            SubmissionStatus::Pending,
            SubmissionStatus::NeedSupplement,
            SubmissionStatus::Approved,
            SubmissionStatus::Approved,
            SubmissionStatus::Rejected,
        ];
        $relationships = ['father', 'mother', 'guardian', 'student'];
        $firstNames = ['Nguyễn Văn', 'Trần Thị', 'Lê Văn', 'Phạm Thị', 'Hoàng Văn', 'Võ Thị', 'Đặng Văn', 'Bùi Thị'];
        $studentNames = ['Minh Anh', 'Gia Hân', 'Hoàng Nam', 'Khánh Linh', 'Đức Anh', 'Bảo Ngọc', 'Quang Huy', 'Ngọc Hà'];

        foreach ($procedures as $procedureIndex => $procedure) {
            for ($i = 1; $i <= 12; $i++) {
                $sequence = ($procedureIndex * 12) + $i;
                $status = $statuses[($sequence - 1) % count($statuses)];
                $submittedAt = now()->subDays(($sequence * 3) % 90)->subMinutes($sequence * 7);
                $code = 'DEMO-'.$submittedAt->format('ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
                $applicant = $firstNames[($sequence - 1) % count($firstNames)].' Demo '.$sequence;
                $student = $studentNames[($sequence - 1) % count($studentNames)].' Demo '.$sequence;

                $attributes = [
                    'procedure_id' => $procedure->id,
                    'lookup_token_hash' => Hash::make('DEMO-LOOKUP-'.$sequence),
                    'applicant_name' => $applicant,
                    'phone' => '090'.str_pad((string) (1000000 + $sequence), 7, '0', STR_PAD_LEFT),
                    'email' => 'administrative.demo'.$sequence.'@example.test',
                    'wants_email_receipt' => $sequence % 3 !== 0,
                    'student_name' => $student,
                    'student_code' => 'HS-DEMO-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                    'date_of_birth' => now()->subYears(6 + ($sequence % 12))->subDays($sequence)->toDateString(),
                    'current_class' => 'Lớp '.(($sequence % 12) + 1).chr(65 + ($sequence % 4)),
                    'academic_year' => '2026-2027',
                    'relationship' => $relationships[($sequence - 1) % count($relationships)],
                    'relationship_other' => null,
                    'status' => $status,
                    'response' => $this->responseFor($status, $sequence),
                    'rejection_reason_code' => $status === SubmissionStatus::Rejected ? 'missing_documents' : null,
                    'rejection_reason' => $status === SubmissionStatus::Rejected ? 'Hồ sơ demo còn thiếu giấy tờ bắt buộc. Vui lòng bổ sung bản chụp rõ ràng.' : null,
                    'submitted_at' => $submittedAt,
                    'processed_at' => $status === SubmissionStatus::Pending ? null : $submittedAt->copy()->addHours(4 + ($sequence % 24)),
                    'version' => $status === SubmissionStatus::Pending ? 1 : 2,
                    'supplement_reason' => $status === SubmissionStatus::NeedSupplement ? 'Vui lòng bổ sung giấy tờ còn thiếu và kiểm tra lại thông tin học sinh.' : null,
                    'supplement_requested_at' => $status === SubmissionStatus::NeedSupplement ? $submittedAt->copy()->addHours(5) : null,
                    'resubmitted_at' => null,
                    'revision_count' => $status === SubmissionStatus::NeedSupplement ? 1 : 0,
                ];

                $submission = AdministrativeSubmission::withTrashed()->updateOrCreate(
                    ['submission_code' => $code],
                    $attributes
                );

                if ($submission->trashed()) {
                    $submission->restore();
                }

                $submission->statusHistories()->delete();
                $submission->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => SubmissionStatus::Pending,
                    'action' => SubmissionAction::Submitted,
                    'actor_type' => HistoryActorType::PublicUser,
                    'note' => 'Dữ liệu demo: hồ sơ được nộp thành công.',
                    'metadata' => ['demo' => true],
                    'created_at' => $submittedAt,
                ]);

                if ($status !== SubmissionStatus::Pending) {
                    $submission->statusHistories()->create([
                        'from_status' => SubmissionStatus::Pending,
                        'to_status' => $status,
                        'action' => match ($status) {
                            SubmissionStatus::Approved => SubmissionAction::Approved,
                            SubmissionStatus::Rejected => SubmissionAction::Rejected,
                            SubmissionStatus::NeedSupplement => SubmissionAction::SupplementRequested,
                            default => SubmissionAction::Submitted,
                        },
                        'actor_type' => HistoryActorType::Admin,
                        'actor_id' => null,
                        'note' => $this->responseFor($status, $sequence),
                        'reason_code' => $status === SubmissionStatus::Rejected ? 'missing_documents' : null,
                        'reason' => $status === SubmissionStatus::Rejected ? 'Thiếu giấy tờ bắt buộc (demo).' : ($status === SubmissionStatus::NeedSupplement ? 'Cần bổ sung hồ sơ (demo).' : null),
                        'metadata' => ['demo' => true],
                        'created_at' => $submittedAt->copy()->addHours(4 + ($sequence % 24)),
                    ]);
                }
            }
        }
    }

    private function responseFor(SubmissionStatus $status, int $sequence): ?string
    {
        return match ($status) {
            SubmissionStatus::Approved => "Hồ sơ demo #{$sequence} đã được kiểm tra và phê duyệt.",
            SubmissionStatus::Rejected => "Hồ sơ demo #{$sequence} chưa đạt yêu cầu. Vui lòng xem lý do từ chối.",
            SubmissionStatus::NeedSupplement => "Hồ sơ demo #{$sequence} cần bổ sung thông tin hoặc giấy tờ.",
            default => null,
        };
    }
}
