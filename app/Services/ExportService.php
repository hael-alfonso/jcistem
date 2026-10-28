<?php

namespace App\Services;

use App\Models\{ChapterSetting, GeneratedReport, User};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportService
{
    public function download(User $user, string $title, array $sections, string $format)
    {
        $name = Str::slug($title).'-'.now()->format('Ymd-His').'.'.$format;
        $path = 'generated-reports/'.$user->id.'/'.Str::uuid().'.'.$format;
        Storage::disk('local')->makeDirectory(dirname($path));
        $absolute = Storage::disk('local')->path($path);
        $chapter = ChapterSetting::where('key', 'report_chapter_name')->value('value') ?? 'JCI Carmona';
        try {
            if ($format === 'docx') {
                $word = new \PhpOffice\PhpWord\PhpWord;
                $word->setDefaultFontName('Arial');
                $word->setDefaultFontSize(11);
                \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
                $section = $word->addSection(['marginTop' => 900, 'marginBottom' => 900, 'marginLeft' => 900, 'marginRight' => 900]);
                $section->addImage(public_path('assets/images/brand/logo.png'), ['width' => 100]);
                $section->addText($chapter, ['bold' => true]);
                $section->addText($title, ['bold' => true]);
                $section->addText('Generated '.now()->format('d M Y, H:i').' · PHP currency');
                foreach ($sections as $heading => $body) {
                    $section->addText((string) $heading, ['bold' => true], ['spaceBefore' => 180, 'keepNext' => true]);
                    foreach (explode("\n", (string) $body) as $line) $section->addText($line);
                }
                \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($absolute);
            } else {
                $font = config('chapter.report_font_path', 'C:/Windows/Fonts/arial.ttf');
                $options = new \Dompdf\Options;
                $options->set('isRemoteEnabled', false);
                $options->set('isPhpEnabled', false);
                $options->set('chroot', array_filter([public_path('assets/images/brand'), is_file($font) ? dirname($font) : null]));
                $dompdf = new \Dompdf\Dompdf($options);
                $html = '<html><head><meta charset="utf-8"><style>@page{margin:40pt}body{font-family:Arial,DejaVu Sans,sans-serif;font-size:11pt;line-height:1.45}h1,h2{font-size:11pt;page-break-after:avoid}p{white-space:pre-wrap;overflow-wrap:break-word}';
                if (is_file($font)) $html .= '@font-face{font-family:Arial;src:url("file:///'.str_replace('\\', '/', $font).'")}';
                $html .= '</style></head><body><img width="110" src="data:image/png;base64,'.base64_encode(file_get_contents(public_path('assets/images/brand/logo.png'))).'"><h1>'.e($chapter).' — '.e($title).'</h1><p>Generated '.e(now()->format('d M Y, H:i')).' · PHP currency</p>';
                foreach ($sections as $heading => $body) $html .= '<h2>'.e($heading).'</h2><p>'.e($body).'</p>';
                $dompdf->loadHtml($html.'</body></html>', 'UTF-8');
                $dompdf->setPaper('A4'); $dompdf->render();
                file_put_contents($absolute, $dompdf->output());
            }
            $record = GeneratedReport::create(['generated_by' => $user->id, 'type' => $title, 'path' => $path]);
            ChapterService::audit('report_generated', $record);
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return Storage::disk('local')->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
