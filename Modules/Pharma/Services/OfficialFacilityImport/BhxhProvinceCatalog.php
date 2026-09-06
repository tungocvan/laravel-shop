<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

class BhxhProvinceCatalog
{
    /**
     * BHXH source geography is deliberately separate from ERP canonical geography.
     * Duplicate BHXH province names are source partitions, not fallback aliases.
     */
    public function all(): array
    {
        $unique = [];

        foreach ($this->sourceOptions() as $code => $name) {
            if (! in_array($name, $unique, true)) {
                $unique[$code] = $name;
            }
        }

        return $unique;
    }

    public function codes(): array
    {
        return array_keys($this->all());
    }

    public function provinceName(string $uiCode): ?string
    {
        return $this->all()[$uiCode] ?? null;
    }

    /** @return list<string> */
    public function sourceCodesFor(string $uiCode): array
    {
        $name = $this->provinceName($uiCode);
        if ($name === null) {
            return [];
        }

        return array_keys(array_filter(
            $this->sourceOptions(),
            fn (string $candidate): bool => $candidate === $name,
        ));
    }

    /**
     * Source partitions belonging to one ERP-facing province label.
     *
     * The source code remains the durable BHXH identity. partition_name is only an
     * operator-facing label and never replaces canonical ERP geography.
     */
    public function partitionsFor(string $uiCode): array
    {
        $codes = $this->sourceCodesFor($uiCode);
        $provinceName = $this->provinceName($uiCode);

        return array_map(fn (string $code): array => [
            'source_code' => $code,
            'source_name' => $provinceName,
            'partition_name' => $this->partitionName($code, $provinceName, count($codes)),
        ], $codes);
    }

    public function isSourceCodeFor(string $uiCode, string $sourceCode): bool
    {
        return in_array($sourceCode, $this->sourceCodesFor($uiCode), true);
    }

    public function aliases(): array
    {
        $partitions = [];

        foreach ($this->sourceOptions() as $code => $name) {
            $partitions[$name] ??= [];
            $partitions[$name][] = $code;
        }

        return array_filter($partitions, fn (array $codes): bool => count($codes) > 1);
    }

    private function partitionName(string $code, ?string $provinceName, int $partitionCount): string
    {
        if ($partitionCount === 1) {
            return $provinceName ?? $code;
        }

        // Verified operator mapping: after the An Giang/Kien Giang consolidation,
        // BHXH still exposes the two former geographic partitions independently.
        $known = [
            '89TTT' => 'Khu vực An Giang cũ',
            '91TTT' => 'Khu vực Kiên Giang cũ',
        ];

        return $known[$code] ?? (($provinceName ?? 'Địa bàn BHXH').' · mã '.$code);
    }

    private function sourceOptions(): array
    {
        return [
            '92TTT' => 'Thành phố Cần Thơ', '48TTT' => 'Thành phố Đà Nẵng', '75TTT' => 'Thành phố Đồng Nai',
            '01TTT' => 'Thành phố Hà Nội', '31TTT' => 'Thành phố Hải Phòng', '79TTT' => 'Thành phố Hồ Chí Minh',
            '46TTT' => 'Thành phố Huế', '22TTT' => 'Thành phố Quảng Ninh',
            '89TTT' => 'Tỉnh An Giang', '91TTT' => 'Tỉnh An Giang', '77TTT' => 'Tỉnh Bà Rịa - Vũng Tàu',
            '06TTT' => 'Tỉnh Bắc Kạn', '95TTT' => 'Tỉnh Bạc Liêu', '27TTT' => 'Tỉnh Bắc Ninh', '24TTT' => 'Tỉnh Bắc Ninh',
            '83TTT' => 'Tỉnh Bến Tre', '74TTT' => 'Tỉnh Bình Dương', '70TTT' => 'Tỉnh Bình Phước', '60TTT' => 'Tỉnh Bình Thuận',
            '96TTT' => 'Tỉnh Cà Mau', '04TTT' => 'Tỉnh Cao Bằng', '66TTT' => 'Tỉnh Đắk Lắk', '67TTT' => 'Tỉnh Đắk Nông',
            '11TTT' => 'Tỉnh Điện Biên', '82TTT' => 'Tỉnh Đồng Tháp', '87TTT' => 'Tỉnh Đồng Tháp',
            '52TTT' => 'Tỉnh Gia Lai', '64TTT' => 'Tỉnh Gia Lai', '02TTT' => 'Tỉnh Hà Giang', '35TTT' => 'Tỉnh Hà Nam',
            '42TTT' => 'Tỉnh Hà Tĩnh', '30TTT' => 'Tỉnh Hải Dương', '93TTT' => 'Tỉnh Hậu Giang', '17TTT' => 'Tỉnh Hòa Bình',
            '33TTT' => 'Tỉnh Hưng Yên', '56TTT' => 'Tỉnh Khánh Hòa', '62TTT' => 'Tỉnh Kon Tum', '12TTT' => 'Tỉnh Lai Châu',
            '68TTT' => 'Tỉnh Lâm Đồng', '20TTT' => 'Tỉnh Lạng Sơn', '15TTT' => 'Tỉnh Lào Cai', '10TTT' => 'Tỉnh Lào Cai',
            '36TTT' => 'Tỉnh Nam Định', '40TTT' => 'Tỉnh Nghệ An', '37TTT' => 'Tỉnh Ninh Bình', '58TTT' => 'Tỉnh Ninh Thuận',
            '25TTT' => 'Tỉnh Phú Thọ', '54TTT' => 'Tỉnh Phú Yên', '49TTT' => 'Tỉnh Quảng Nam', '51TTT' => 'Tỉnh Quảng Ngãi',
            '45TTT' => 'Tỉnh Quảng Trị', '44TTT' => 'Tỉnh Quảng Trị', '94TTT' => 'Tỉnh Sóc Trăng', '14TTT' => 'Tỉnh Sơn La',
            '80TTT' => 'Tỉnh Tây Ninh', '72TTT' => 'Tỉnh Tây Ninh', '34TTT' => 'Tỉnh Thái Bình', '19TTT' => 'Tỉnh Thái Nguyên',
            '38TTT' => 'Tỉnh Thanh Hóa', '84TTT' => 'Tỉnh Trà Vinh', '08TTT' => 'Tỉnh Tuyên Quang', '86TTT' => 'Tỉnh Vĩnh Long',
            '26TTT' => 'Tỉnh Vĩnh Phúc',
        ];
    }
}
