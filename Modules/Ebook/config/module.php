<?php

return [
    'name' => 'Ebook',
    'type' => 'domain',
    'default_enabled' => true,
    'depends' => [],
    'permissions' => [
        'ebook.view',
        'ebook.create',
        'ebook.update',
        'ebook.delete',
        'ebook.upload',
        'ebook.sync',
    ],
    'tables' => [
        'ebook_folders',
        'ebook_documents',
        'ebook_document_recents',
    ],
];
