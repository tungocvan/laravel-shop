<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

class BhxhProvinceCatalog
{
    /**
     * Source: public BHXH facility lookup province dropdown, captured 2026-09-06.
     * Keys are source-specific BHXH province codes and must not be treated as
     * canonical ERP province codes.
     */
    public function all(): array
    {
        return [
            '92TTT' => 'Thành phố Cần Thơ',
            '48TTT' => 'Thành phố Đà Nẵng',
            '75TTT' => 'Thành phố Đồng Nai',
            '01TTT' => 'Thành phố Hà Nội',
            '31TTT' => 'Thành phố Hải Phòng',
            '79TTT' => 'Thành phố Hồ Chí Minh',
            '46TTT' => 'Thành phố Huế',
            '22TTT' => 'Thành phố Quảng Ninh',
            '89TTT' => 'Tỉnh An Giang',
            '91TTT' => 'Tỉnh An Giang',
            '77TTT' => 'Tỉnh Bà Rịa - Vũng Tàu',
            '06TTT' => 'Tỉnh Bắc Kạn',
            '95TTT' => 'Tỉnh Bạc Liêu',
            '27TTT' => 'Tỉnh Bắc Ninh',
            '24TTT' => 'Tỉnh Bắc Ninh',
            '83TTT' => 'Tỉnh Bến Tre',
            '74TTT' => 'Tỉnh Bình Dương',
            '70TTT' => 'Tỉnh Bình Phước',
            '60TTT' => 'Tỉnh Bình Thuận',
            '96TTT' => 'Tỉnh Cà Mau',
            '04TTT' => 'Tỉnh Cao Bằng',
            '66TTT' => 'Tỉnh Đắk Lắk',
            '67TTT' => 'Tỉnh Đắk Nông',
            '11TTT' => 'Tỉnh Điện Biên',
            '82TTT' => 'Tỉnh Đồng Tháp',
            '87TTT' => 'Tỉnh Đồng Tháp',
            '52TTT' => 'Tỉnh Gia Lai',
            '64TTT' => 'Tỉnh Gia Lai',
            '02TTT' => 'Tỉnh Hà Giang',
            '35TTT' => 'Tỉnh Hà Nam',
            '42TTT' => 'Tỉnh Hà Tĩnh',
            '30TTT' => 'Tỉnh Hải Dương',
            '93TTT' => 'Tỉnh Hậu Giang',
            '17TTT' => 'Tỉnh Hòa Bình',
            '33TTT' => 'Tỉnh Hưng Yên',
            '56TTT' => 'Tỉnh Khánh Hòa',
            '62TTT' => 'Tỉnh Kon Tum',
            '12TTT' => 'Tỉnh Lai Châu',
            '68TTT' => 'Tỉnh Lâm Đồng',
            '20TTT' => 'Tỉnh Lạng Sơn',
            '15TTT' => 'Tỉnh Lào Cai',
            '10TTT' => 'Tỉnh Lào Cai',
            '36TTT' => 'Tỉnh Nam Định',
            '40TTT' => 'Tỉnh Nghệ An',
            '37TTT' => 'Tỉnh Ninh Bình',
            '58TTT' => 'Tỉnh Ninh Thuận',
            '25TTT' => 'Tỉnh Phú Thọ',
            '54TTT' => 'Tỉnh Phú Yên',
            '49TTT' => 'Tỉnh Quảng Nam',
            '51TTT' => 'Tỉnh Quảng Ngãi',
            '45TTT' => 'Tỉnh Quảng Trị',
            '44TTT' => 'Tỉnh Quảng Trị',
            '94TTT' => 'Tỉnh Sóc Trăng',
            '14TTT' => 'Tỉnh Sơn La',
            '80TTT' => 'Tỉnh Tây Ninh',
            '72TTT' => 'Tỉnh Tây Ninh',
            '34TTT' => 'Tỉnh Thái Bình',
            '19TTT' => 'Tỉnh Thái Nguyên',
            '38TTT' => 'Tỉnh Thanh Hóa',
            '84TTT' => 'Tỉnh Trà Vinh',
            '08TTT' => 'Tỉnh Tuyên Quang',
            '86TTT' => 'Tỉnh Vĩnh Long',
            '26TTT' => 'Tỉnh Vĩnh Phúc',
        ];
    }

    public function codes(): array
    {
        return array_keys($this->all());
    }
}
