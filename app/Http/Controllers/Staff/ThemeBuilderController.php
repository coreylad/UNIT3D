<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreThemeBuilderRequest;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeBuilderController extends Controller
{
    private const string STORAGE_DIR = 'theme-builder';

    /**
     * Display the theme builder index.
     */
    public function index(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        $baseStyles = [
            2 => 'Dark blue',
            3 => 'Dark green',
            4 => 'Dark pink',
            5 => 'Dark purple',
            6 => 'Dark red',
            7 => 'Dark teal',
            8 => 'Dark yellow',
            9 => 'Cosmic void',
        ];

        return view('Staff.theme-builder.index', [
            'themes' => $this->loadThemes($request->user()->id),
            'baseStyles' => $baseStyles,
        ]);
    }

    /**
     * Show the theme builder form.
     */
    public function create(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        $baseStyles = [
            2 => 'Dark blue',
            3 => 'Dark green',
            4 => 'Dark pink',
            5 => 'Dark purple',
            6 => 'Dark red',
            7 => 'Dark teal',
            8 => 'Dark yellow',
            9 => 'Cosmic void',
        ];

        return view('Staff.theme-builder.create', [
            'baseStyles' => $baseStyles,
        ]);
    }

    /**
     * Store a new theme and apply it to the creator.
     */
    public function store(StoreThemeBuilderRequest $request): \Illuminate\Http\RedirectResponse
    {
        $variables = $this->parseVariables($request->input('variables'));
        if ($variables === null) {
            return back()
                ->withErrors(['variables' => 'Use one --variable: value per line.'])
                ->withInput();
        }

        $themeId = Str::slug($request->input('name')).'-'.Str::lower(Str::random(6));
        $userId = $request->user()->id;
        $payload = [
            'id' => $themeId,
            'user_id' => $userId,
            'name' => $request->input('name'),
            'base_style' => (int) $request->input('base_style'),
            'variables' => $variables,
            'body_font' => trim((string) $request->input('body_font', '')),
            'heading_font' => trim((string) $request->input('heading_font', '')),
            'extra_css' => trim((string) $request->input('extra_css', '')),
            'created_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put(
            $this->themePath($userId, $themeId),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->applyTheme($request, $payload);

        return to_route('staff.theme_builder.index')
            ->with('success', 'Theme created and applied.');
    }

    /**
     * Apply an existing theme to the creator.
     */
    public function apply(Request $request, string $theme): \Illuminate\Http\RedirectResponse
    {
        $themeData = $this->loadTheme($request->user()->id, $theme);
        if ($themeData === null) {
            abort(404);
        }

        $this->applyTheme($request, $themeData);

        return back()->with('success', 'Theme applied.');
    }

    /**
     * Delete a theme.
     */
    public function destroy(Request $request, string $theme): \Illuminate\Http\RedirectResponse
    {
        $themeData = $this->loadTheme($request->user()->id, $theme);
        if ($themeData === null) {
            abort(404);
        }

        Storage::disk('local')->delete($this->themePath($request->user()->id, $theme));

        $cssUrl = route('staff.theme_builder.css', ['theme' => $theme]);
        $settings = $request->user()->settings;
        if ($settings !== null && $settings->custom_css === $cssUrl) {
            $settings->update([
                'custom_css' => null,
            ]);
        }

        return back()->with('success', 'Theme deleted.');
    }

    /**
     * Render the CSS for a theme.
     */
    public function css(Request $request, string $theme): \Illuminate\Http\Response
    {
        $themeData = $this->loadTheme($request->user()->id, $theme);
        if ($themeData === null) {
            abort(404);
        }

        $css = $this->renderCss($themeData);

        return response($css)
            ->header('Content-Type', 'text/css; charset=UTF-8')
            ->header('Cache-Control', 'private, max-age=300');
    }

    /**
     * Load all themes for the user.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadThemes(int $userId): array
    {
        $dir = self::STORAGE_DIR.'/'.$userId;
        if (! Storage::disk('local')->exists($dir)) {
            return [];
        }

        $themes = [];
        foreach (Storage::disk('local')->files($dir) as $path) {
            if (! Str::endsWith($path, '.json')) {
                continue;
            }

            $payload = json_decode((string) Storage::disk('local')->get($path), true);
            if (! is_array($payload)) {
                continue;
            }

            $themes[] = $payload;
        }

        usort($themes, fn (array $left, array $right) => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? '')));

        return $themes;
    }

    /**
     * Load a single theme for the user.
     *
     * @return array<string, mixed>|null
     */
    private function loadTheme(int $userId, string $themeId): ?array
    {
        $path = $this->themePath($userId, $themeId);
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $payload = json_decode((string) Storage::disk('local')->get($path), true);

        return is_array($payload) ? $payload : null;
    }

    private function themePath(int $userId, string $themeId): string
    {
        return self::STORAGE_DIR.'/'.$userId.'/'.$themeId.'.json';
    }

    /**
     * @return array<string, string>|null
     */
    private function parseVariables(string $raw): ?array
    {
        $variables = [];
        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (! preg_match('/^(--[a-z0-9-]+)\s*[:=]\s*(.+)$/i', $trimmed, $matches)) {
                return null;
            }

            $variables[$matches[1]] = trim($matches[2]);
        }

        return $variables;
    }

    /**
     * @param array<string, mixed> $theme
     */
    private function renderCss(array $theme): string
    {
        $lines = [':root {'];

        foreach (($theme['variables'] ?? []) as $name => $value) {
            $lines[] = '    '.$name.': '.$value.';';
        }

        $lines[] = '}';

        $bodyFont = trim((string) ($theme['body_font'] ?? ''));
        if ($bodyFont !== '') {
            $lines[] = 'body { font-family: '.$bodyFont.'; }';
        }

        $headingFont = trim((string) ($theme['heading_font'] ?? ''));
        if ($headingFont !== '') {
            $lines[] = 'h1, h2, h3, h4, h5, h6 { font-family: '.$headingFont.'; }';
        }

        $extraCss = trim((string) ($theme['extra_css'] ?? ''));
        if ($extraCss !== '') {
            $lines[] = $extraCss;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param array<string, mixed> $theme
     */
    private function applyTheme(Request $request, array $theme): void
    {
        $cssUrl = route('staff.theme_builder.css', ['theme' => $theme['id']]);

        UserSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'style' => (int) ($theme['base_style'] ?? 2),
                'custom_css' => $cssUrl,
                'standalone_css' => null,
            ]
        );
    }
}
