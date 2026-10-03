<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMcpPromptRequest;
use App\Models\AdminUser;
use App\Models\McpPromptVersion;
use App\Services\Admin\McpPromptConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class McpPromptController extends Controller
{
    public function index(McpPromptConfigurationService $prompts): Response
    {
        $this->authorize('viewAny', McpPromptVersion::class);

        return Inertia::render('mcp-prompts/presentation/pages/Index', [
            'prompts' => $prompts->listForAdmin(),
        ]);
    }

    public function show(string $promptKey, McpPromptConfigurationService $prompts): Response
    {
        $this->authorize('viewAny', McpPromptVersion::class);

        return Inertia::render('mcp-prompts/presentation/pages/Edit', [
            'prompt' => $prompts->getForAdmin($promptKey),
        ]);
    }

    public function saveDraft(
        SaveMcpPromptRequest $request,
        string $promptKey,
        McpPromptConfigurationService $prompts,
    ): RedirectResponse {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $version = $prompts->saveDraft($promptKey, $request->validated('content'), $adminUser, $request);

        return redirect()->route('admin.mcp-prompts.show', $promptKey)
            ->with('success', "Rascunho v{$version->version} salvo.");
    }

    public function publish(Request $request, McpPromptVersion $version, McpPromptConfigurationService $prompts): RedirectResponse
    {
        $this->authorize('publish', $version);

        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $prompts->publish($version, $adminUser, $request);

        return redirect()->route('admin.mcp-prompts.show', $version->prompt_key)
            ->with('success', "Versão v{$version->version} publicada.");
    }

    public function restore(Request $request, McpPromptVersion $version, McpPromptConfigurationService $prompts): RedirectResponse
    {
        $this->authorize('restore', $version);

        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $restored = $prompts->restore($version, $adminUser, $request);

        return redirect()->route('admin.mcp-prompts.show', $version->prompt_key)
            ->with('success', "Conteúdo da versão v{$version->version} restaurado e publicado como v{$restored->version}.");
    }
}
