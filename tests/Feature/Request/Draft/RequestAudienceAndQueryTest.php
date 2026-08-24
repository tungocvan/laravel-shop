<?php

namespace Tests\Feature\Request\Draft;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Queries\RequestCatalogQuery;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Models\RequestTypeAudience;

class RequestAudienceAndQueryTest extends RequestDraftTestCase
{
    public function test_catalog_enforces_user_and_role_audience_without_leaking_direct_ulids(): void
    {
        $designerId = $this->activeUser('Designer');
        $userAudienceId = $this->activeUser('User audience');
        $roleAudienceId = $this->activeUser('Role audience');
        $deniedId = $this->activeUser('Denied');
        $roleId = $this->adminRole('Catalog role');
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => config('auth.providers.users.model'), 'model_id' => $roleAudienceId]);

        $userType = $this->publishedType($designerId, $this->simpleSchema(), $userAudienceId);
        $roleType = $this->publishedType($designerId, $this->simpleSchema(), $designerId);
        RequestTypeAudience::query()->where('request_type_version_id', $roleType->current_published_version_id)->delete();
        RequestTypeAudience::query()->create(['request_type_version_id' => $roleType->current_published_version_id, 'actor_type' => 'role', 'actor_id' => $roleId, 'capability' => 'create']);
        $query = app(RequestCatalogQuery::class);

        $this->assertSame([$userType->id], $query->paginate($userAudienceId, '', null, 25)->pluck('id')->all());
        $this->assertSame([$roleType->id], $query->paginate($roleAudienceId, '', null, 25)->pluck('id')->all());
        $this->assertSame([], $query->paginate($deniedId, '', null, 25)->pluck('id')->all());

        $this->expectException(ModelNotFoundException::class);
        $query->findEligible($userType->public_id, $deniedId, AudienceCapability::Create);
    }

    public function test_catalog_and_my_requests_queries_are_bounded_for_twenty_five_rows(): void
    {
        $actorId = $this->activeUser();
        foreach (range(1, 25) as $index) {
            $type = $this->publishedType($actorId, $this->simpleSchema());
            app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $catalogQuery = app(RequestCatalogQuery::class);
        $catalog = $catalogQuery->paginate($actorId, '', null, 25);
        $catalog->items();
        $catalogQuery->groupOptions($actorId);
        $catalogQueries = count(DB::getQueryLog());
        $catalogSql = array_column(DB::getQueryLog(), 'query');
        DB::flushQueryLog();
        $mine = app(MyRequestsQuery::class)->paginate($actorId, '', '', 25);
        $mine->items();
        $mineQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(25, $catalog);
        $this->assertCount(25, $mine);
        $this->assertLessThanOrEqual(15, $catalogQueries, json_encode($catalogSql));
        $this->assertLessThanOrEqual(15, $mineQueries);
    }

    public function test_request_numbers_are_derived_from_unique_database_ids(): void
    {
        $actorId = $this->activeUser();
        $type = $this->publishedType($actorId, $this->simpleSchema());
        $requests = collect(range(1, 30))->map(fn () => app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid()));

        $this->assertSame(30, $requests->pluck('request_number')->unique()->count());
        foreach ($requests as $request) {
            $this->assertStringEndsWith(str_pad((string) $request->id, 8, '0', STR_PAD_LEFT), $request->request_number);
        }
    }
}
