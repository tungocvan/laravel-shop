<?php

declare(strict_types=1);

namespace Modules\PromptEngine\Http\Controllers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\PromptEngine\Http\Requests\GenerateCharacterPromptRequest;
use Modules\PromptEngine\Http\Resources\PromptEngineResource;
use Modules\PromptEngine\Services\PromptEngineService;
use Throwable;

final class PromptEngineController
{
    public function __construct(private PromptEngineService $engine) {}

    public function index(): View
    {
        return view('PromptEngine::index', [
            'styles' => config('promptengine.config.styles', []),
            'aspectRatios' => config('promptengine.config.aspect_ratios', []),
            'defaultStyle' => config('promptengine.config.default_style', 'museum-editorial'),
            'defaultAspectRatio' => config('promptengine.config.default_aspect_ratio', '2:3'),
            'imageGenerationEnabled' => (bool) config('promptengine.config.image_generation.enabled'),
        ]);
    }

    public function generate(GenerateCharacterPromptRequest $request): JsonResponse
    {
        $result = $this->engine->generate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Prompt generated successfully.',
            'data' => (new PromptEngineResource($result))->resolve($request),
        ]);
    }

    public function generateImage(GenerateCharacterPromptRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['mode'] = 'generate_image';
            $result = $this->engine->generate($data);

            return response()->json([
                'success' => true,
                'message' => 'Image workflow completed.',
                'data' => (new PromptEngineResource($result))->resolve($request),
            ]);
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            Log::warning('PromptEngine image provider request failed.', [
                'provider' => $request->input('platform'),
                'status' => $status,
            ]);

            return response()->json([
                'success' => false,
                'message' => $status === 429
                    ? 'Nhà cung cấp đang giới hạn lượt tạo ảnh hoặc tài khoản đã hết quota. Vui lòng kiểm tra Billing và thử lại sau.'
                    : 'Nhà cung cấp ảnh từ chối yêu cầu. Vui lòng kiểm tra API key, model và Billing.',
            ], in_array($status, [401, 403, 429], true) ? $status : 502);
        } catch (Throwable $exception) {
            Log::error('PromptEngine image generation failed.', [
                'provider' => $request->input('platform'),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->isProduction()
                    ? 'Không thể tạo ảnh lúc này. Vui lòng thử lại sau.'
                    : $exception->getMessage(),
            ], 500);
        }
    }
}
