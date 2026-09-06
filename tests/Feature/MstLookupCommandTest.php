<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MstLookupCommandTest extends TestCase
{
    public function test_it_looks_up_company_by_name_and_prints_expected_fields(): void
    {
        Http::fake([
            'https://masothue.com/Search/*' => Http::response($this->searchHtml(), 200),
            'https://masothue.com/1700285659-benh-vien-da-khoa-kien-giang' => Http::response($this->detailHtml(), 200),
        ]);

        $this->artisan('mst:lookup', ['query' => 'Bệnh viện đa khoa Kiên Giang'])
            ->expectsOutputToContain('BỆNH VIỆN ĐA KHOA KIÊN GIANG')
            ->expectsOutputToContain('1700285659')
            ->expectsOutputToContain('Đang hoạt động')
            ->expectsOutputToContain('TRƯƠNG CÔNG THÀNH')
            ->assertSuccessful();

        Http::assertSentCount(2);
    }

    public function test_it_can_output_json(): void
    {
        Http::fake([
            'https://masothue.com/Search/*' => Http::response($this->searchHtml(), 200),
            'https://masothue.com/1700285659-benh-vien-da-khoa-kien-giang' => Http::response($this->detailHtml(), 200),
        ]);

        $this->artisan('mst:lookup', [
            'query' => 'Bệnh viện đa khoa Kiên Giang',
            '--json' => true,
        ])
            ->expectsOutputToContain('"tax_code": "1700285659"')
            ->expectsOutputToContain('"match_type": "exact"')
            ->assertSuccessful();
    }

    public function test_it_fails_when_search_has_no_results(): void
    {
        Http::fake([
            'https://masothue.com/Search/*' => Http::response('<html><body><h1>Không có kết quả</h1></body></html>', 200),
        ]);

        $this->artisan('mst:lookup', ['query' => 'Don vi khong ton tai'])
            ->expectsOutputToContain('Khong tim thay ket qua')
            ->assertFailed();
    }

    private function searchHtml(): string
    {
        return <<<'HTML'
        <html><body>
            <h3>
                <a href="/1700285659-benh-vien-da-khoa-kien-giang">BỆNH VIỆN ĐA KHOA KIÊN GIANG</a>
            </h3>
            <h3>
                <a href="/1700463439-benh-vien-da-khoa-tinh-kien-giang-nop-ho">BỆNH VIỆN ĐA KHOA TỈNH KIÊN GIANG (NỘP HỘ)</a>
            </h3>
        </body></html>
        HTML;
    }

    private function detailHtml(): string
    {
        return <<<'HTML'
        <html><body><table>
            <tr><td>Mã số thuế</td><td>1700285659</td></tr>
            <tr><td>Địa chỉ Thuế</td><td>Số 13 Nam Kỳ Khởi Nghĩa, Phường Rạch Giá, An Giang</td></tr>
            <tr><td>Tình trạng</td><td>Đang hoạt động</td></tr>
            <tr><td>Người đại diện</td><td>TRƯƠNG CÔNG THÀNH</td></tr>
            <tr><td>Ngày hoạt động</td><td>1998-09-18</td></tr>
            <tr><td>Quản lý bởi</td><td>Thuế cơ sở</td></tr>
            <tr><td>Loại hình DN</td><td>Đơn vị hành chính, đơn vị sự nghiệp</td></tr>
        </table></body></html>
        HTML;
    }
}
