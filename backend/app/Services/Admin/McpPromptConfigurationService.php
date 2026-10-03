<?php

namespace App\Services\Admin;

use App\Enums\McpPromptVersionStatus;
use App\Models\AdminUser;
use App\Models\McpPromptVersion;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final class McpPromptConfigurationService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly AccessLogService $accessLogs,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        $definitions = McpPromptDefaults::all();
        $versions = McpPromptVersion::query()
            ->whereIn('prompt_key', array_keys($definitions))
            ->with(['createdByAdminUser:id,name', 'publishedByAdminUser:id,name'])
            ->orderByDesc('version')
            ->get()
            ->groupBy('prompt_key');

        $items = [];

        foreach ($definitions as $key => $definition) {
            $promptVersions = $versions->get($key, collect());
            $latestVersion = $promptVersions->first();
            $publishedVersion = $promptVersions->first(
                static fn (McpPromptVersion $version): bool => $version->status === McpPromptVersionStatus::Published,
            );

            $items[] = [
                'key' => $key,
                'title' => $definition['title'],
                'description' => $definition['description'],
                'current_version' => $publishedVersion?->version,
                'draft_version' => $latestVersion?->status === McpPromptVersionStatus::Draft ? $latestVersion->version : null,
                'content' => $latestVersion?->content ?? $definition['content'],
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function getForAdmin(string $key): array
    {
        $definition = $this->definition($key);
        $versions = McpPromptVersion::query()
            ->where('prompt_key', $key)
            ->with(['createdByAdminUser:id,name', 'publishedByAdminUser:id,name'])
            ->orderByDesc('version')
            ->limit(50)
            ->get();

        $latestVersion = $versions->first();

        return [
            'key' => $key,
            'title' => $definition['title'],
            'description' => $definition['description'],
            'default_content' => $definition['content'],
            'content' => $latestVersion?->content ?? $definition['content'],
            'versions' => $versions->map(static fn (McpPromptVersion $version): array => [
                'id' => $version->id,
                'version' => $version->version,
                'status' => $version->status->value,
                'content' => $version->content,
                'created_by' => $version->createdByAdminUser?->name,
                'published_by' => $version->publishedByAdminUser?->name,
                'created_at' => $version->created_at?->toIso8601String(),
                'published_at' => $version->published_at?->toIso8601String(),
            ]),
        ];
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function resolve(string $key, array $variables = []): string
    {
        $definition = $this->definition($key);
        $template = Cache::rememberForever($this->cacheKey($key), fn (): string =>
            McpPromptVersion::query()
                ->where('prompt_key', $key)
                ->where('status', McpPromptVersionStatus::Published->value)
                ->orderByDesc('version')
                ->value('content') ?? $definition['content']
        );

        $replacements = [];

        foreach ($variables as $name => $value) {
            $replacements['{{'.$name.'}}'] = (string) ($value ?? '');
        }

        return strtr($template, $replacements);
    }

    public function saveDraft(string $key, string $content, AdminUser $adminUser, Request $request): McpPromptVersion
    {
        $this->definition($key);

        $version = $this->database->transaction(function () use ($key, $content, $adminUser): McpPromptVersion {
            $number = ((int) McpPromptVersion::query()->where('prompt_key', $key)->max('version')) + 1;

            return McpPromptVersion::query()->create([
                'prompt_key' => $key,
                'version' => $number,
                'content' => $content,
                'status' => McpPromptVersionStatus::Draft,
                'created_by_admin_user_id' => $adminUser->getKey(),
            ]);
        });

        $this->accessLogs->record($adminUser, 'mcp_prompt.draft_saved', $request, metadata: [
            'prompt_key' => $key,
            'version' => $version->version,
        ]);

        return $version;
    }

    public function publish(McpPromptVersion $version, AdminUser $adminUser, Request $request): McpPromptVersion
    {
        abort_unless($version->status === McpPromptVersionStatus::Draft, 409);

        $version->forceFill([
            'status' => McpPromptVersionStatus::Published,
            'published_by_admin_user_id' => $adminUser->getKey(),
            'published_at' => now(),
        ])->save();

        Cache::forget($this->cacheKey($version->prompt_key));
        $this->accessLogs->record($adminUser, 'mcp_prompt.published', $request, metadata: [
            'prompt_key' => $version->prompt_key,
            'version' => $version->version,
        ]);

        return $version;
    }

    public function restore(McpPromptVersion $source, AdminUser $adminUser, Request $request): McpPromptVersion
    {
        $restored = $this->database->transaction(function () use ($source, $adminUser): McpPromptVersion {
            $number = ((int) McpPromptVersion::query()->where('prompt_key', $source->prompt_key)->max('version')) + 1;

            return McpPromptVersion::query()->create([
                'prompt_key' => $source->prompt_key,
                'version' => $number,
                'content' => $source->content,
                'status' => McpPromptVersionStatus::Published,
                'created_by_admin_user_id' => $adminUser->getKey(),
                'published_by_admin_user_id' => $adminUser->getKey(),
                'published_at' => now(),
            ]);
        });

        Cache::forget($this->cacheKey($source->prompt_key));
        $this->accessLogs->record($adminUser, 'mcp_prompt.restored', $request, metadata: [
            'prompt_key' => $source->prompt_key,
            'source_version' => $source->version,
            'version' => $restored->version,
        ]);

        return $restored;
    }

    /**
     * @return array{title: string, description: string, content: string}
     */
    private function definition(string $key): array
    {
        $definition = McpPromptDefaults::all()[$key] ?? null;

        if ($definition === null) {
            throw ValidationException::withMessages(['prompt' => 'O prompt solicitado não está disponível.']);
        }

        return $definition;
    }

    private function cacheKey(string $key): string
    {
        return 'admin.mcp-prompt.published.'.$key;
    }
}
