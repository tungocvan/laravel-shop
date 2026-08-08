Project:
- Laravel 12
- Livewire 3.x
- TailwindCSS 4
- Kien truc dang theo Modules/*

Muc tieu thread nay:
- [ghi 1 muc tieu ro rang, chi 1 viec lon]

Context da chot truoc do:
- Modules duoc chia thanh 3 loai: shell, domain, support
- Admin duoc xem la shell module
- Domain module tu so huu model, service, policy, route, livewire cua no
- Support module dung cho auth/role/template/capability dung chung
- CreateModule v2 da duoc cap nhat ho tro --type=shell|domain|support

File lien quan:
- /Z:/var/www/pharma/app/Console/Commands/CreateModule.php
- /Z:/var/www/pharma/Modules/ModuleServiceProvider.php
- [them file khac neu can]

Trang thai hien tai:
- Da xong:
  - [liet ke 2-5 y quan trong]
- Chua xong:
  - [liet ke viec con lai]

Yeu cau cu the cho thread nay:
- [vd: phan tich]
- [vd: de xuat]
- [vd: sua code]
- [vd: review]

Rang buoc:
- Uu tien giu kien truc module ro vai tro
- Khong nhoi business logic vao Admin shell
- Neu sua code, uu tien production-ready, clean, de scale

Dau ra mong muon:
- [vd: phan tich kien truc]
- [vd: code hoan chinh]
- [vd: plan refactor]
- [vd: checklist migration]


Ví dụ thực tế cho dự án của bạn
Project:
- Laravel 12
- Livewire 3.x
- TailwindCSS 4
- Kien truc dang theo Modules/*

Muc tieu thread nay:
- Refactor ModuleServiceProvider de boot module theo type shell/domain/support

Context da chot truoc do:
- Admin la shell module
- Pharma la domain module
- Role/Auth/Template la support module
- CreateModule v2 da ho tro --type=shell|domain|support
- Hien tai ModuleServiceProvider dang auto-load module theo kieu rat mem ve convention

File lien quan:
- /Z:/var/www/pharma/Modules/ModuleServiceProvider.php
- /Z:/var/www/pharma/app/Console/Commands/CreateModule.php

Trang thai hien tai:
- Da xong:
  - Phan loai 3 loai module
  - Cap nhat CreateModule v2
- Chua xong:
  - Chuan hoa boot logic theo module type
  - Giam coupling trong module loader

Yeu cau cu the cho thread nay:
- Phan tich ModuleServiceProvider hien tai
- De xuat boot strategy moi
- Neu hop ly thi sua code

Rang buoc:
- Khong pha vo cac module dang co neu chua can
- Uu tien migration strategy mem, co backward compatibility

Dau ra mong muon:
- Danh gia kien truc
- Code refactor neu can
- Huong dan cach su dung sau khi doi

< 35%: an toan, lam binh thuong
35% - 50%: bat dau gom context, tranh doi chu de
> 50%: neu con viec lon, mo thread moi
> 65%: chi nen chot summary va handoff
> 75%: dung thread, mo chat moi ngay
