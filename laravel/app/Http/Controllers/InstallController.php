<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Support\InstallationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PDO;
use Spatie\Permission\Models\Role;
use Throwable;

class InstallController extends Controller
{
    /**
     * Step 1: Server Requirements & Directory Permissions
     */
    public function step1(): View
    {
        $requirements = [
            'PHP >= 8.2.0' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'BCMath Extension' => extension_loaded('bcmath'),
            'Ctype Extension' => extension_loaded('ctype'),
            'cURL Extension' => extension_loaded('curl'),
            'DOM Extension' => extension_loaded('dom'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
            'JSON Extension' => extension_loaded('json'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'Zip Extension' => extension_loaded('zip'),
            'GD Extension' => extension_loaded('gd'),
        ];

        $permissions = [
            'storage' => is_writable(storage_path()),
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework' => is_writable(storage_path('framework')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            '.env (or root directory writable)' => File::exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path()),
        ];

        $allRequirementsMet = ! in_array(false, $requirements, true) && ! in_array(false, $permissions, true);

        return view('installer.step1', [
            'requirements' => $requirements,
            'permissions' => $permissions,
            'allRequirementsMet' => $allRequirementsMet,
        ]);
    }

    /**
     * Step 2: Database Configuration
     */
    public function step2(): View
    {
        return view('installer.step2', [
            'defaultHost' => env('DB_HOST', '127.0.0.1'),
            'defaultPort' => env('DB_PORT', '3306'),
            'defaultDatabase' => env('DB_DATABASE', ''),
            'defaultUsername' => env('DB_USERNAME', 'root'),
        ]);
    }

    /**
     * Process Step 2 Database Connection
     */
    public function postStep2(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'numeric'],
            'db_name' => ['required', 'string'],
            'db_user' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        $host = $validated['db_host'];
        $port = (int) $validated['db_port'];
        $database = $validated['db_name'];
        $username = $validated['db_user'];
        $password = $validated['db_password'] ?? '';

        // Test database connection
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Throwable $e) {
            return redirect()
                ->route('install.step2')
                ->withInput()
                ->with('error', 'Could not connect to MySQL database: '.$e->getMessage());
        }

        // Update .env file
        $this->updateEnvFile([
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);

        return redirect()->route('install.step3');
    }

    /**
     * Step 3: Migration & Super Admin Setup
     */
    public function step3(): View
    {
        return view('installer.step3');
    }

    /**
     * Process Step 3
     */
    public function postStep3(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Generate app key if needed
            if (blank(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Run base migrations
            Artisan::call('migrate', ['--force' => true]);

            // Seed essential roles and statuses
            Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'OrderStatusSeeder', '--force' => true]);

            // Create Super Admin User
            $admin = Admin::query()->updateOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => Hash::make($validated['admin_password']),
                    'is_active' => true,
                ]
            );

            // Assign Super Admin Role
            $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
            if ($superAdminRole) {
                $admin->assignRole($superAdminRole);
            }

            return redirect()->route('install.step4');
        } catch (Throwable $e) {
            return redirect()
                ->route('install.step3')
                ->withInput()
                ->with('error', 'Installation setup failed: '.$e->getMessage());
        }
    }

    /**
     * Step 4: Installation Completion
     */
    public function step4(): View
    {
        InstallationHelper::markInstalled();

        try {
            Artisan::call('optimize:clear');
        } catch (Throwable) {
            // Ignore
        }

        return view('installer.step4');
    }

    /**
     * Helper to write database credentials to .env file safely.
     *
     * @param  array<string, mixed>  $data
     */
    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $examplePath = base_path('.env.example');
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $content = File::get($envPath);

        foreach ($data as $key => $value) {
            $value = (string) $value;
            if (str_contains($value, ' ') && ! str_starts_with($value, '"')) {
                $value = '"'.$value.'"';
            }

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $content);
    }
}
