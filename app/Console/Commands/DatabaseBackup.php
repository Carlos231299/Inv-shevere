<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprime la base de datos SQLite y la envía a Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando respaldo de base de datos...');

        $databasePath = database_path('database.sqlite');
        
        if (!File::exists($databasePath)) {
            $this->error('Error: No se encontró el archivo de base de datos.');
            return 1;
        }

        $token = env('TELEGRAM_BACKUP_TOKEN');
        $chatId = env('TELEGRAM_BACKUP_CHAT_ID');

        if (!$token || !$chatId) {
            $this->error('Error: Token o Chat ID de Telegram no configurados en .env');
            return 1;
        }

        $date = now()->format('Y-m-d_H-i-s');
        $backupName = "backup_salome_{$date}.sqlite";
        $tempPath = storage_path("app/{$backupName}");

        // 1. Copiar base de datos a ruta temporal
        File::copy($databasePath, $tempPath);

        try {
            // 2. Enviar a Telegram
            $response = Http::attach(
                'document', 
                file_get_contents($tempPath), 
                "{$backupName}"
            )->post("https://api.telegram.org/bot{$token}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => "📦 Respaldo Diario - Carnicería Salomé\n📅 Fecha: " . now()->format('d/m/Y h:i A')
            ]);

            if ($response->successful()) {
                $this->info('¡Respaldo enviado a Telegram exitosamente!');
                Log::info('Backup enviado a Telegram.');
            } else {
                $this->error('Error al enviar a Telegram: ' . $response->body());
                Log::error('Error en backup Telegram: ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('Error crítico: ' . $e->getMessage());
            Log::error('Excepción en backup: ' . $e->getMessage());
        } finally {
            // 3. Limpiar archivo temporal
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
        }

        return 0;
    }
}
