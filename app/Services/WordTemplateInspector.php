<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class WordTemplateInspector
{
    public function inspect(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->invalid();
        }
        try {
            if ($zip->locateName('word/document.xml') === false || $zip->locateName('[Content_Types].xml') === false) {
                $this->invalid();
            }
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += $entry['size'];
                if ($size > 50 * 1024 * 1024 || $zip->numFiles > 2000) {
                    $this->invalid('Isi dokumen terlalu besar. Sederhanakan file Word lalu unggah kembali.');
                }
            }
        } finally {
            $zip->close();
        }
        $converted = DocumentGenerator::convertPlaceholders($path);
        try {
            $processor = new TemplateProcessor($converted);
            $counts = $processor->getVariableCount();
        } catch (\Throwable $exception) {
            $this->invalid();
        } finally {
            if ($converted !== $path && is_file($converted)) unlink($converted);
        }
        $groups = DocumentDataContext::getContextKeys();
        $catalog = array_merge(...array_values($groups));
        $variables = array_map('strval', array_keys($counts));
        sort($variables);
        $known = array_values(array_intersect($variables, $catalog));
        $unknown = array_values(array_diff($variables, $catalog));
        $suggestions = [];
        foreach ($unknown as $key) {
            $best = null;
            $distance = 5;
            foreach ($catalog as $candidate) {
                $score = levenshtein(substr($key, 0, 100), $candidate);
                if ($score < $distance) { $distance = $score; $best = $candidate; }
            }
            $suggestions[$key] = $best;
        }

        return [
            'variables' => $variables, 'known' => $known, 'unknown' => $unknown,
            'counts' => $counts, 'suggestions' => $suggestions,
            'groups' => array_keys(array_filter($groups, fn ($keys) => count(array_intersect($keys, $known)) > 0)),
        ];
    }

    private function invalid(string $message = 'File Word tidak valid atau rusak. Simpan ulang sebagai .docx lalu unggah kembali.'): never
    {
        throw ValidationException::withMessages(['file_template' => $message]);
    }
}
