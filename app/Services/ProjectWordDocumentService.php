<?php

namespace App\Services;

use App\Models\Project;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProjectWordDocumentService
{
    public function create(Project $project, string $stage): array
    {
        throw_unless(in_array($stage, ['concept', 'proposal'], true), RuntimeException::class, 'Unsupported project document stage.');
        $formKey = $stage === 'concept' ? 'internal-project-letter' : 'project-proposal-form';
        $entry = config('reference_forms.'.$formKey);
        $template = resource_path('forms/'.$entry['file']);
        throw_unless(is_file($template), RuntimeException::class, 'The official Word template is unavailable.');

        $directory = storage_path('app/private/generated-project-documents');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        $filename = Str::slug($project->reference ?: 'project-'.$project->id).'-'.$stage.'.docx';
        $destination = $directory.DIRECTORY_SEPARATOR.bin2hex(random_bytes(8)).'-'.$filename;
        throw_unless(copy($template, $destination), RuntimeException::class, 'The Word copy could not be prepared.');

        try {
            $this->fill($destination, $project, $stage);
        } catch (\Throwable $exception) {
            @unlink($destination);
            throw $exception;
        }

        return [$destination, $filename];
    }

    private function fill(string $path, Project $project, string $stage): void
    {
        $zip = new ZipArchive;
        throw_unless($zip->open($path) === true, RuntimeException::class, 'The Word template could not be opened.');
        try {
            $xml = $zip->getFromName('word/document.xml');
            throw_if($xml === false, RuntimeException::class, 'The Word template body is missing.');
            $filled = $stage === 'concept'
                ? $this->fillConceptXml($xml, $project)
                : $this->fillProposalXml($xml, $project);
            throw_unless($zip->addFromString('word/document.xml', $filled), RuntimeException::class, 'The filled Word document could not be saved.');
        } finally {
            $zip->close();
        }
    }

    private function fillConceptXml(string $xml, Project $project): string
    {
        $concept = $project->concept ?? [];
        $president = trim($concept['president_name'] ?? '') ?: 'Local Organization President';
        $lastName = collect(preg_split('/\s+/', $president))->filter()->last() ?: $president;
        $letterDate = $this->date($concept['letter_date'] ?? null) ?: now()->format('F j, Y');
        $schedule = $project->starts_on?->format('F j, Y') ?? 'To be confirmed';
        if ($project->starts_on && $project->ends_on && !$project->ends_on->isSameDay($project->starts_on)) $schedule .= ' to '.$project->ends_on->format('F j, Y');
        $funding = trim($concept['funding_source'] ?? $concept['partners'] ?? '') ?: 'chapter allocation and approved fundraising';
        $committee = trim($concept['committee_size'] ?? $concept['participants'] ?? '') ?: 'to be confirmed';
        $resourceText = 'The estimated working budget is PHP '.number_format((float) $project->proposed_budget, 2).', to be sourced from '.$funding.'. The project requires a committee of '.$committee.' active JCI members. '.trim($concept['resources'] ?? 'Committee sign-ups will open following Board approval.');
        $requestText = trim($concept['request'] ?? '') ?: 'Attached are the project brief and proposal form. I look forward to presenting this proposal at the next Board Meeting for review and approval.';

        $tokens = [
            '[Date]' => $letterDate,
            '[Name of Local Organization President]' => $president,
            '[Chapter Address]' => trim($concept['chapter_address'] ?? '') ?: 'Carmona City, Cavite',
            '[Project Name]' => $project->title,
            "[President's Last Name]" => $lastName,
            '[Number]' => trim($concept['sdg_number'] ?? '') ?: 'To be confirmed',
            '[Name of SDG]' => trim($concept['sdg_name'] ?? '') ?: 'Relevant Sustainable Development Goal',
            '[state the core project goal]' => trim($concept['objective'] ?? '') ?: 'deliver the proposed community initiative',
            '[Date or Month]' => $schedule,
            '[Venue or Platform]' => $project->venue ?: 'To be confirmed',
            '[Target group / expected participants]' => trim($concept['beneficiaries'] ?? '') ?: 'To be confirmed',
            '[Name of Submitting Member]' => $project->chair?->name ?? $project->owner?->name ?? 'Project proponent',
            '[PHP Amount]' => 'PHP '.number_format((float) $project->proposed_budget, 2),
            '[fundraising strategy or chapter allocation]' => $funding,
            '[Your Name]' => $project->owner?->name ?? 'Project proponent',
            '[Your Position/Title]' => trim($concept['proponent_position'] ?? '') ?: 'JCI Carmona Member',
            '[Contact Information]' => trim($concept['contact_info'] ?? '') ?: ($project->owner?->email ?? 'Contact information on file'),
        ];

        return $this->transform($xml, $tokens, function (string $text) use ($resourceText, $requestText) {
            if (str_starts_with($text, 'The estimated working budget is ')) return $resourceText;
            if (str_starts_with($text, 'Attached are the project brief and proposal form')) return $requestText;
            return null;
        });
    }

    private function fillProposalXml(string $xml, Project $project): string
    {
        $proposal = $project->proposal ?? [];
        $objectives = $this->lines($proposal['objectives'] ?? '', 3);
        $team = $this->tableValues($proposal['team'] ?? '', 5);
        $activities = $this->tableValues($proposal['activities'] ?? '', 4, true);
        $income = array_map(fn ($key) => $this->money(data_get($proposal, 'income_lines.'.$key)), array_keys(\App\Support\ChapterForms::PROPOSAL_INCOME_LINES));
        $expenses = array_map(fn ($key) => $this->money(data_get($proposal, 'expense_lines.'.$key)), array_keys(\App\Support\ChapterForms::PROPOSAL_EXPENSE_LINES));
        $amounts = [...$income, $this->money(array_sum(array_map(fn ($v) => (float) str_replace(',', '', $v), $income))), ...$expenses, $this->money(array_sum(array_map(fn ($v) => (float) str_replace(',', '', $v), $expenses)))];
        $state = ['objective' => 0, 'name' => 0, 'activity' => 0, 'amount' => 0];
        $chair = $project->chair?->name ?? $project->owner?->name ?? 'Project Chair';
        $tokens = [
            '[Enter project title]' => $project->title,
            '[Community / Business / Individual / International]' => $project->area,
            '[Name of designated or proposed Project Chair]' => $chair,
            '[DD/MM/YYYY]' => $project->starts_on?->format('d/m/Y') ?? 'To be confirmed',
            '[Enter background and rationale here.]' => trim($proposal['rationale'] ?? '') ?: 'To be completed',
            '[Identify the beneficiaries, their location, and expected number of participants or recipients.]' => trim($proposal['beneficiaries'] ?? '') ?: 'To be completed',
            '[Explain the benefits to JCI Carmona, member development, and long-term community impact.]' => trim($proposal['outputs'] ?? '') ?: 'To be completed',
            '[Project Chair Name]' => $chair,
            '[Commission Vice President]' => trim($proposal['committee_director'] ?? '') ?: 'To be designated',
            '[Local Organization President]' => trim($proposal['president_name'] ?? '') ?: 'Local Organization President',
            '[Commission]' => $project->area,
        ];

        return $this->transform($xml, $tokens, function (string $text) use (&$state, $objectives, $team, $activities, $amounts, $project) {
            if ($text === '[Enter a specific objective.]') return $objectives[$state['objective']++] ?? 'To be completed';
            if ($text === '[Name]') {
                if ($state['name'] === 0) $value = $project->chair?->name ?? $project->owner?->name ?? 'Project Chair';
                else $value = $team[$state['name']] ?? 'To be assigned';
                $state['name']++;
                return $value;
            }
            if (in_array($text, ['[Community assessment, needs survey, project scope]', '[Board approvals, permits, coordination with partners]', '[Actual project activities and delivery timeline]', '[Assessment, liquidation, final reporting, feedback]'], true)) return $activities[$state['activity']++] ?? 'To be completed';
            if ($text === '[0.00]') return $amounts[$state['amount']++] ?? '0.00';
            return null;
        }, function (string $text, array $tokens) use ($project) {
            if ($text === '[DD/MM/YYYY]') {
                static $dateIndex = 0;
                return ($dateIndex++ === 0 ? $project->starts_on : $project->ends_on)?->format('d/m/Y') ?? 'To be confirmed';
            }
            return str_replace(array_keys($tokens), array_values($tokens), $text);
        });
    }

    private function transform(string $xml, array $tokens, callable $paragraphReplacement, ?callable $tokenReplacement = null): string
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        throw_unless($dom->loadXML($xml), RuntimeException::class, 'The Word template XML is invalid.');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tokenReplacement ??= fn (string $text, array $tokens) => str_replace(array_keys($tokens), array_values($tokens), $text);

        foreach ($xpath->query('//w:body//w:p') as $paragraph) {
            $nodes = $xpath->query('.//w:t', $paragraph);
            if ($nodes->length === 0) continue;
            $text = '';
            foreach ($nodes as $node) $text .= $node->textContent;
            $whole = $paragraphReplacement($text);
            if ($whole !== null) {
                $this->replaceParagraphText($paragraph, $nodes, $whole);
                continue;
            }
            foreach ($nodes as $node) $node->textContent = $tokenReplacement($node->textContent, $tokens);
        }

        return $dom->saveXML();
    }

    private function replaceParagraphText(DOMElement $paragraph, \DOMNodeList $nodes, string $value): void
    {
        $nodes->item(0)->textContent = preg_replace('/\s+/', ' ', trim($value));
        for ($index = 1; $index < $nodes->length; $index++) $nodes->item($index)->textContent = '';
    }

    private function lines(string $value, int $limit): array
    {
        $lines = preg_split('/\R+/', trim($value));
        $lines = array_values(array_filter(array_map(fn ($line) => trim(preg_replace('/^[a-z0-9]+[.)]\s*/i', '', $line)), $lines)));
        return array_pad(array_slice($lines, 0, $limit), $limit, 'To be completed');
    }

    private function tableValues(string $value, int $limit, bool $preferSecond = false): array
    {
        $lines = preg_split('/\R+/', trim($value));
        $values = [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            $values[] = $preferSecond ? ($parts[1] ?? $parts[0]) : ($parts[1] ?? $parts[0]);
        }
        return array_pad(array_slice(array_values(array_filter($values)), 0, $limit), $limit, 'To be assigned');
    }

    private function money(mixed $value): string
    {
        return number_format((float) ($value ?: 0), 2);
    }

    private function date(mixed $value): ?string
    {
        if (!$value) return null;
        try { return \Carbon\Carbon::parse($value)->format('F j, Y'); }
        catch (\Throwable) { return trim((string) $value); }
    }
}
