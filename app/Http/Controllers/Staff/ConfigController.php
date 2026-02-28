<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ConfigController extends Controller
{
    private const TOOL_CONFIGS = [
        'announce' => [
            'icon' => 'fa-bullhorn',
            'label' => 'Announce',
            'description' => 'External tracker configuration',
        ],
        'api-keys' => [
            'icon' => 'fa-key',
            'label' => 'API Keys',
            'description' => 'Third-party API keys and tokens',
        ],
        'audit' => [
            'icon' => 'fa-file-text',
            'label' => 'Audit',
            'description' => 'Audit logging configuration',
        ],
        'backup' => [
            'icon' => 'fa-save',
            'label' => 'Backup',
            'description' => 'Database backup settings',
        ],
        'cache' => [
            'icon' => 'fa-database',
            'label' => 'Cache',
            'description' => 'Cache driver configuration',
        ],
        'chat' => [
            'icon' => 'fa-comments',
            'label' => 'Chat',
            'description' => 'Chat system settings',
        ],
        'donation' => [
            'icon' => 'fa-money-bill',
            'label' => 'Donation',
            'description' => 'Donation system configuration',
        ],
        'email-blacklist' => [
            'icon' => 'fa-ban',
            'label' => 'Email Blacklist',
            'description' => 'Email address blacklist',
        ],
        'hitrun' => [
            'icon' => 'fa-warning',
            'label' => 'Hit & Run',
            'description' => 'Hit and run penalty settings',
        ],
        'mail' => [
            'icon' => 'fa-envelope',
            'label' => 'Mail',
            'description' => 'Mail driver and settings',
        ],
        'torrent' => [
            'icon' => 'fa-download',
            'label' => 'Torrent',
            'description' => 'Torrent system configuration',
        ],
        'unit3d' => [
            'icon' => 'fa-cog',
            'label' => 'Unit3D',
            'description' => 'Unit3D core settings',
        ],
    ];

    /**
     * Show a list of configuration tools.
     */
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        $tools = collect($this->TOOL_CONFIGS)->map(function ($config, $key) {
            return array_merge($config, ['key' => $key]);
        })->values();

        return view('Staff.config.index', ['tools' => $tools]);
    }

    /**
     * Display tool editor for a config file.
     */
    public function edit(string $tool): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        if (! isset($this->TOOL_CONFIGS[$tool])) {
            abort(404);
        }

        $filename = $tool . '.php';
        $path = config_path($filename);

        if (! File::exists($path)) {
            abort(404);
        }

        // Load the config file
        $config = include $path;

        $toolConfig = $this->TOOL_CONFIGS[$tool];

        return view('Staff.config.edit', [
            'tool'     => $tool,
            'toolLabel' => $toolConfig['label'],
            'config'   => is_array($config) ? $config : [],
        ]);
    }

    /**
     * Display config value editor with nested access.
     */
    public function show(string $tool, ?string $key = null): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        if (! isset($this->TOOL_CONFIGS[$tool])) {
            abort(404);
        }

        $filename = $tool . '.php';
        $path = config_path($filename);

        if (! File::exists($path)) {
            abort(404);
        }

        $config = include $path;
        $toolConfig = $this->TOOL_CONFIGS[$tool];

        return view('Staff.config.show', [
            'tool'       => $tool,
            'toolLabel'  => $toolConfig['label'],
            'toolIcon'   => $toolConfig['icon'],
            'key'        => $key,
            'config'     => is_array($config) ? $config : [],
        ]);
    }

    /**
     * Update a configuration value.
     */
    public function update(Request $request, string $tool, string $key): \Illuminate\Http\RedirectResponse
    {
        if (! isset($this->TOOL_CONFIGS[$tool])) {
            abort(404);
        }

        $data = $request->validate([
            'value' => ['required'],
        ]);

        $filename = $tool . '.php';
        $path = config_path($filename);
        if (! File::exists($path)) {
            abort(404);
        }

        // Load existing config array
        $config = include $path;
        if (! is_array($config)) {
            $config = [];
        }

        $incoming = $data['value'];

        // Helper to coerce types
        $coerce = function ($val) {
            if (is_array($val)) return $val;
            if ($val === '1' || $val === 1) return 1;
            if ($val === '0' || $val === 0) return 0;
            $lower = strtolower((string) $val);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            // JSON array/object
            if (is_string($val) && (str_starts_with($val, '{') || str_starts_with($val, '['))) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            // integers
            if (is_string($val) && ctype_digit($val)) return (int) $val;
            return $val;
        };

        // If incoming is an array with nested keys (from forms named value[child])
        if (is_array($incoming)) {
            foreach ($incoming as $nestedKey => $nestedValue) {
                $coerced = $coerce($nestedValue);
                // ensure parent exists
                if (! isset($config[$key]) || ! is_array($config[$key])) {
                    $config[$key] = [];
                }
                $config[$key][$nestedKey] = $coerced;
            }
        } else {
            $config[$key] = $coerce($incoming);
        }

        // Render array back to PHP file (note: this will lose comments and env() wrappers)
        $export = var_export($config, true);
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $export . ";\n";

        File::put($path, $contents);

        // Clear config cache so next request picks up changes
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // ignore
        }

        return redirect()->route('staff.config.show', ['tool' => $tool])
            ->with('success', "Configuration updated successfully.");
    }
}
