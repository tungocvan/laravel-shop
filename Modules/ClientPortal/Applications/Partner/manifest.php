<?php
return [
 'key'=>'partners','source_module'=>'Partner','name'=>'Quản lý đối tác',
 'description'=>'Quản lý khách hàng, nhà cung cấp và các đối tác liên quan.',
 'icon'=>'users','route'=>'client.partners.index','permission'=>'client.partners.access','sort_order'=>65,
 'layout'=>['mode'=>'workspace'],
 'hub'=>['key'=>'overview','name'=>'Đối tác','eyebrow'=>'Danh mục doanh nghiệp','title'=>'Quản lý đối tác','description'=>'Tra cứu và quản lý danh sách đối tác.','supporting'=>['visible'=>false,'title'=>'','body'=>'']],
 'features'=>[
  'list'=>['name'=>'Danh sách đối tác','description'=>'Tìm kiếm và lọc đối tác.','eyebrow'=>'Danh mục','page_title'=>'Quản lý đối tác','page_description'=>'Tra cứu khách hàng, nhà cung cấp và các tổ chức liên quan.','route'=>'client.partners.index','permission'=>'client.partners.view','icon'=>'users','sort_order'=>10,'actions'=>[
   'lookup'=>['name'=>'Tra cứu doanh nghiệp','description'=>'Tra cứu và đối chiếu dữ liệu doanh nghiệp từ nguồn tham khảo.','eyebrow'=>'Danh mục','page_title'=>'Tra cứu doanh nghiệp','page_description'=>'Kiểm tra nguồn tham khảo và chỉ đồng bộ sau khi xác nhận.','route'=>'client.partners.lookup','permission'=>'client.partners.lookup','icon'=>'search','sort_order'=>15,'actions'=>[
   'sync'=>['name'=>'Đồng bộ doanh nghiệp','permission'=>'client.partners.sync']]],
  'create'=>['name'=>'Thêm đối tác','permission'=>'client.partners.create'],
   'edit'=>['name'=>'Sửa đối tác','permission'=>'client.partners.edit'],
   'delete'=>['name'=>'Xóa đối tác','permission'=>'client.partners.delete']]],
  'create'=>['name'=>'Thêm đối tác','description'=>'Nhập thông tin đối tác.','eyebrow'=>'Danh mục','page_title'=>'Thêm đối tác','page_description'=>'Nhập thông tin định danh, phân loại và liên hệ.','route'=>'client.partners.create','permission'=>'client.partners.create','icon'=>'plus','sort_order'=>20],
  'edit'=>['name'=>'Sửa đối tác','description'=>'Cập nhật thông tin đối tác.','eyebrow'=>'Danh mục','page_title'=>'Sửa đối tác','page_description'=>'Cập nhật thông tin đối tác hiện có.','route'=>null,'permission'=>'client.partners.edit','icon'=>'pencil','sort_order'=>30],
 ],
 'navigation'=>['lookup'=>['name'=>'Tra cứu doanh nghiệp','route'=>'client.partners.lookup','permission'=>'client.partners.lookup','icon'=>'search','sort_order'=>20],'list'=>['name'=>'Đối tác','route'=>'client.partners.index','permission'=>'client.partners.view','icon'=>'users','sort_order'=>10]],
];