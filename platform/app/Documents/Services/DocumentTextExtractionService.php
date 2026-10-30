<?php

namespace App\Documents\Services;

use App\Documents\Exceptions\DocumentExtractionException;
use DOMDocument;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class DocumentTextExtractionService
{
    public function extract(string $path, string $originalFilename): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new DocumentExtractionException('source_unavailable');
        }
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $text = match ($extension) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            default => throw new DocumentExtractionException('unsupported_file_type'),
        };

        $text = $this->normalize($text);
        if ($text === '') {
            throw new DocumentExtractionException('no_text_extracted');
        }
        if (mb_strlen($text) > 150000) {
            throw new DocumentExtractionException('extracted_text_too_large');
        }

        return $text;
    }

    private function extractPdf(string $path): string
    {
        $binary = (string) config('ai.documents.pdf_to_text_binary', 'pdftotext');
        if (trim($binary) === '') {
            throw new DocumentExtractionException('pdf_extractor_not_configured');
        }

        try {
            $process = new Process([$binary, '-q', '-enc', 'UTF-8', '-layout', $path, '-']);
            $process->setTimeout((int) config('ai.documents.extraction_timeout', 90));
            $process->run();
            if (! $process->isSuccessful()) {
                throw new DocumentExtractionException('pdf_extraction_failed');
            }

            return $process->getOutput();
        } catch (DocumentExtractionException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new DocumentExtractionException('pdf_extractor_unavailable');
        }
    }

    private function extractDocx(string $path): string
    {
        if (! class_exists(ZipArchive::class) || ! class_exists(DOMDocument::class)) {
            throw new DocumentExtractionException('docx_extensions_unavailable');
        }
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new DocumentExtractionException('invalid_docx_archive');
        }

        try {
            $xmlText = $archive->getFromName('word/document.xml');
            if (! is_string($xmlText) || strlen($xmlText) > 20_000_000) {
                throw new DocumentExtractionException('docx_document_xml_unavailable');
            }
        } finally {
            $archive->close();
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML($xmlText, LIBXML_NONET | LIBXML_NOBLANKS);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded) {
            throw new DocumentExtractionException('invalid_docx_document_xml');
        }

        $wordNamespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $paragraphs = [];
        foreach ($document->getElementsByTagNameNS($wordNamespace, 'p') as $paragraph) {
            $line = '';
            foreach ($paragraph->getElementsByTagNameNS($wordNamespace, 't') as $node) {
                $line .= $node->textContent;
            }
            foreach ($paragraph->getElementsByTagNameNS($wordNamespace, 'tab') as $node) {
                $line .= "\t";
            }
            if (trim($line) !== '') {
                $paragraphs[] = $line;
            }
        }

        return implode("\n", $paragraphs);
    }

    private function normalize(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $text);
        $text = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $lines = array_map(fn (string $line) => trim(preg_replace('/[\t ]+/u', ' ', $line) ?? $line), explode("\n", $text));

        return trim(preg_replace("/\n{3,}/u", "\n\n", implode("\n", $lines)) ?? implode("\n", $lines));
    }
}
