<?php

return [
    'default_platform' => 'gemini', 'default_style' => 'museum-editorial',
    'default_aspect_ratio' => '2:3', 'default_language' => 'vi',
    'default_text_mode' => 'editable-layout', 'default_mode' => 'prompt_only',
    'templates_path' => base_path('Modules/PromptEngine/Templates'),
    'override_templates_path' => resource_path('prompt-engine'),
    'cache' => ['enabled' => true, 'ttl' => 3600],
    'storage' => ['enabled' => true, 'save_input' => true, 'save_analysis' => true, 'save_design_plan' => true, 'save_output' => true],
    'platforms' => ['gemini', 'chatgpt-image', 'midjourney', 'flux', 'stable-diffusion', 'generic'],
    'styles' => ['museum-editorial', 'ancient-encyclopedia', 'luxury-classical', 'minimal-educational', 'dunhuang-inspired', 'dark-academia'],
    'aspect_ratios' => ['1:1', '4:5', '3:4', '2:3', '9:16', '16:9'],
    'text_modes' => ['rendered', 'editable-layout'], 'modes' => ['prompt_only', 'generate_image'],
    'image_generation' => [
        'enabled' => true, 'default_provider' => 'chatgpt-image',
        'disk' => 'public', 'directory' => 'prompt-engine/generated',
        'timeout' => 120, 'retries' => 2,
        'max_bytes' => 15 * 1024 * 1024,
        'gemini' => ['api_key' => env('GEMINI_API_KEY'), 'model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.0-flash-preview-image-generation')],
        'chatgpt-image' => ['api_key' => env('OPENAI_API_KEY'), 'model' => 'gpt-image-2'],
    ],
    'character_data' => [
        '福' => ['pinyin'=>'fú','vietnamese_reading'=>'Phúc','traditional_form'=>'福','simplified_form'=>'福','meaning_vi'=>'Phước lành, hạnh phúc, may mắn và thịnh vượng.','meaning_en'=>'Blessing, happiness, good fortune and prosperity.','radical'=>'礻','radical_meaning'=>'thị: nghi lễ, thần linh','stroke_count'=>13,'structure'=>'left-right','character_type'=>'phono-semantic compound','components'=>['礻','畐'],'semantic_component'=>'礻','phonetic_component'=>'畐'],
        '德' => ['pinyin'=>'dé','vietnamese_reading'=>'Đức','meaning_vi'=>'Đức hạnh, phẩm chất đạo đức và cách hành xử ngay chính.','meaning_en'=>'Virtue, moral character and righteous conduct.','radical'=>'彳','stroke_count'=>15,'structure'=>'left-right'],
        '龍' => ['pinyin'=>'lóng','vietnamese_reading'=>'Long','simplified_form'=>'龙','meaning_vi'=>'Rồng; biểu tượng quyền năng và điềm lành.','meaning_en'=>'Dragon; a symbol of power and auspiciousness.','radical'=>'龍','stroke_count'=>16,'structure'=>'single-component'],
        '医' => ['pinyin'=>'yī','vietnamese_reading'=>'Y','traditional_form'=>'醫','meaning_vi'=>'Y học, chữa bệnh hoặc người thầy thuốc.','meaning_en'=>'Medicine, medical treatment, or physician.','radical'=>'匚','stroke_count'=>7,'structure'=>'enclosure'],
        '醫' => ['pinyin'=>'yī','vietnamese_reading'=>'Y','simplified_form'=>'医','meaning_vi'=>'Y học, chữa bệnh hoặc người thầy thuốc.','meaning_en'=>'Medicine, medical treatment, or physician.','radical'=>'酉','stroke_count'=>18,'structure'=>'top-bottom'],
        '學' => ['pinyin'=>'xué','vietnamese_reading'=>'Học','simplified_form'=>'学','meaning_vi'=>'Học tập, tri thức và sự rèn luyện.','meaning_en'=>'Learning, study and cultivation of knowledge.','radical'=>'子','stroke_count'=>16,'structure'=>'top-bottom'],
        '爱' => ['pinyin'=>'ài','vietnamese_reading'=>'Ái','traditional_form'=>'愛','meaning_vi'=>'Tình yêu, sự quý mến và lòng quan tâm.','meaning_en'=>'Love, affection and care.','radical'=>'爫','stroke_count'=>10,'structure'=>'top-bottom'],
        '刻' => ['pinyin'=>'kè','vietnamese_reading'=>'Khắc','meaning_vi'=>'Khắc, chạm hoặc ghi dấu lên vật liệu.','meaning_en'=>'To carve, engrave, or inscribe.','radical'=>'刂','stroke_count'=>8,'structure'=>'left-right'],
    ],
];
