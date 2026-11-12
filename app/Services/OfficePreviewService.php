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
            try {
                if (!$this->renderWithMicrosoftWord($source, $temporary)) {
                    $this->renderWithPhpWord($source, $temporary);
                }
            } catch (\Throwable $exception) {
                // A missing office binary or an unsupported embedded document
                // should not make a private preview endpoint return 422. Keep
                // the original download available and provide a valid PDF
                // fallback explaining why the inline preview is limited.
                report($exception);
                $this->writeFallbackPdf($temporary);
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

    private function writeFallbackPdf(string $destination): void
    {
        $stream = "BT\n/F1 16 Tf\n72 720 Td\n(Document preview is unavailable. Download the original file to view it.) Tj\nET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
        file_put_contents($destination, $pdf);
    }
}
