<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDO;

class AdminDatabaseController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        ActivityLog::record('menu_opened', "{$request->user()?->username} membuka menu Database.", $request, [
            'properties' => ['menu' => 'Database'],
        ]);

        $backupDirectory = storage_path('app/private/database-backups');
        File::ensureDirectoryExists($backupDirectory);

        $backups = collect(File::files($backupDirectory))
            ->filter(fn ($file) => $file->getExtension() === 'sql')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $this->humanFileSize($file->getSize()),
                'created_at' => date('d-m-Y H:i:s', $file->getMTime()),
            ]);

        return view('superadmin.database.index', compact('backups'));
    }

    public function backup(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $backupDirectory = storage_path('app/private/database-backups');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.config('database.connections.'.config('database.default').'.database').'-'.now()->format('Ymd-His').'.sql';
        $path = $backupDirectory.DIRECTORY_SEPARATOR.$filename;

        File::put($path, $this->buildSqlDump());

        ActivityLog::record('database_backup_created', "Backup database {$filename} dibuat.", $request, [
            'properties' => ['filename' => $filename],
        ]);

        return response()->download($path);
    }

    public function download(Request $request, string $filename)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        abort_unless($filename === basename($filename) && Str::endsWith($filename, '.sql'), 404);

        $path = storage_path('app/private/database-backups/'.$filename);
        abort_unless(File::exists($path), 404);

        return response()->download($path);
    }

    public function import(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'database_file' => ['required', 'file', 'max:51200'],
            'confirm_import' => ['required', 'accepted'],
        ]);

        if (! in_array(strtolower($validated['database_file']->getClientOriginalExtension()), ['sql', 'txt'], true)) {
            throw ValidationException::withMessages([
                'database_file' => 'File database harus berformat .sql atau .txt.',
            ]);
        }

        $content = File::get($validated['database_file']->getRealPath());
        $statements = $this->splitSqlStatements($content);

        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }
        } finally {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        }

        ActivityLog::record('database_imported', 'Database diimport dari file SQL.', $request, [
            'properties' => [
                'filename' => $validated['database_file']->getClientOriginalName(),
                'statement_count' => count($statements),
            ],
        ]);

        return redirect()->route('admin.database')->with('status', 'Import database berhasil.');
    }

    public function reset(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'confirm_reset' => ['required', 'in:RESET'],
        ]);

        $admin = $request->user();
        $backupFilename = null;

        if ($request->boolean('backup_before_reset')) {
            $backupDirectory = storage_path('app/private/database-backups');
            File::ensureDirectoryExists($backupDirectory);

            $backupFilename = 'before-reset-'.now()->format('Ymd-His').'.sql';
            File::put($backupDirectory.DIRECTORY_SEPARATOR.$backupFilename, $this->buildSqlDump());
        }

        $this->truncateDataTables();

        if (Schema::hasTable('users')) {
            $adminRecord = [
                'id_user' => $admin->getKey(),
                'username' => $admin->username,
                'password' => $admin->password,
                'class_name' => $admin->class_name,
                'level' => $admin->level,
                'status' => $admin->status ?? 'aktif',
            ];

            if (Schema::hasColumn('users', 'api_token')) {
                $adminRecord['api_token'] = null;
            }

            if (Schema::hasColumn('users', 'deleted_at')) {
                $adminRecord['deleted_at'] = null;
            }

            DB::table('users')->insert($adminRecord);
        }

        ActivityLog::record('database_reset', 'Database direset oleh admin.', $request, [
            'properties' => [
                'backup_filename' => $backupFilename,
                'confirm_reset' => $validated['confirm_reset'],
            ],
        ]);

        return redirect()->route('admin.database')->with('status', 'Reset database berhasil. Akun admin yang sedang dipakai tetap dibuat ulang.');
    }

    private function buildSqlDump(): string
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $database = $connection->getDatabaseName();
        $tables = $this->tableNames();
        $lines = [
            '-- Exambro database backup',
            '-- Database: '.$database,
            '-- Created at: '.now()->format('Y-m-d H:i:s'),
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        foreach ($tables as $table) {
            $escapedTable = str_replace('`', '``', $table);
            $create = $connection->selectOne("SHOW CREATE TABLE `{$escapedTable}`");
            $createSql = (array) $create;
            $createStatement = end($createSql);

            $lines[] = "DROP TABLE IF EXISTS `{$escapedTable}`;";
            $lines[] = $createStatement.';';
            $lines[] = '';

            $rows = $connection->table($table)->get();

            foreach ($rows as $row) {
                $values = collect((array) $row)
                    ->map(fn ($value) => $this->sqlValue($pdo, $value))
                    ->implode(', ');

                $columns = collect(array_keys((array) $row))
                    ->map(fn ($column) => '`'.str_replace('`', '``', $column).'`')
                    ->implode(', ');

                $lines[] = "INSERT INTO `{$escapedTable}` ({$columns}) VALUES ({$values});";
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    private function truncateDataTables(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->tableNames() as $table) {
                if ($table === 'migrations') {
                    continue;
                }

                DB::table($table)->truncate();
            }
        } finally {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function tableNames(): array
    {
        $database = DB::connection()->getDatabaseName();

        return collect(DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', [$database]))
            ->pluck('TABLE_NAME')
            ->filter()
            ->values()
            ->all();
    }

    private function sqlValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $statement = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($quote === null && $char === '-' && $next === '-') {
                while ($i < $length && ! in_array($sql[$i], ["\r", "\n"], true)) {
                    $i++;
                }

                continue;
            }

            if ($quote === null && $char === '/' && $next === '*') {
                $i += 2;

                while ($i < $length - 1 && ! ($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }

                $i++;
                continue;
            }

            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $quote = $quote === $char ? null : ($quote ?? $char);
            }

            if ($quote === null && $char === ';') {
                $trimmed = trim($statement);

                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }

                $statement = '';
                continue;
            }

            $statement .= $char;
        }

        $trimmed = trim($statement);

        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
