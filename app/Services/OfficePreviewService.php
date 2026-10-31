<?php

namespace App\Services;

use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use RuntimeException;
use Symfony\Component\Process\Process;

class OfficePreviewService
{
    public function render(ProjectDocument $document): string
    {
        $source = Storage::disk('local')->path($document->path);
        $hash = hash_file('sha256', $source);
        throw_if($hash === false, RuntimeException::class, 'The uploaded file could not be read.');

        $relative = 'document-previews/'.$hash.'.pdf';
        $preview = Storage::disk('local')->path($relative);
        if ($this->validPdf($preview)) return $preview;

        Storage::disk('local')->makeDirectory('document-previews');
        $temporary = $preview.'.'.bin2hex(random_bytes(5)).'.tmp';

        try {
            if (!$this->renderWithMicrosoftWord($source, $temporary)) {
                $this->renderWithPhpWord($source, $temporary);
            }
            throw_unless($this->validPdf($temporary), RuntimeException::class, 'The document renderer did not create a valid PDF.');
            throw_unless(@rename($temporary, $preview), RuntimeException::class, 'The document preview could not be cached.');
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }

        return $preview;
    }

    private function renderWithMicrosoftWord(string $source, string $destination): bool
    {
        if (PHP_OS_FAMILY !== 'Windows' || app()->environment('testing')) return false;
        $script = base_path('scripts/convert-office-to-pdf.ps1');
        if (!is_file($script)) return false;

        try {
            $process = new Process([
                'powershell.exe', '-NoProfile', '-NonInteractive', '-STA',
                '-ExecutionPolicy', 'Bypass', '-File', $script,
                '-InputPath', $source, '-OutputPath', $destination,
            ]);
            $process->setTimeout(120);
            $process->run();

            return $process->isSuccessful() && $this->validPdf($destination);
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function renderWithPhpWord(string $source, string $destination): void
    {
        Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, base_path('vendor/dompdf/dompdf'));
        $word = IOFactory::load($source);
        IOFactory::createWriter($word, 'PDF')->save($destination);
    }

    private function validPdf(string $path): bool
    {
        if (!is_file($path) || filesize($path) < 100) return false;
        $stream = fopen($path, 'rb');
        if ($stream === false) return false;
        $header = fread($stream, 4);
        fclose($stream);

        return $header === '%PDF';
    }
}
