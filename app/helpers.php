<?php

use App\Services\DateService;
use Illuminate\Support\Facades\Log;


if (!function_exists('access_nested_array')) {
    /**
     * Access a nested array or object using a list of keys.
     * If any key along the path does not exist, return an empty string.
     *
     * @param mixed $data The nested data structure (array or object).
     * @param array $path A list of keys/indices to traverse the structure.
     * @return mixed The value at the specified path, or an empty string if the path is invalid.
     */
    function access_nested_array($data, array $path)
    {
        foreach ($path as $key) {
            if ((is_array($data) && array_key_exists($key, $data))
                || (is_object($data))
            ) {
                $data = $data[$key];
            }
        }

        return $data ?? null;
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format a given date string into 'd/m/Y' format.
     * If the date is null or cannot be parsed, return 'N/A'.
     *
     * @param string|null $date The date string to format, or null.
     * @return string|null The formatted date string in 'd/m/Y' format, or 'N/A' if the date is invalid or null.
     */
    function formatDate(?string $date): ?string
    {
        if (!$date) {
            return 'N/A';
        }

        $dateService = app(DateService::class);
        $parsedDate = $dateService->parseDateWithMultipleFormats($date);

        return $parsedDate?->format('d/m/Y') ?? 'N/A';
    }
}

if (!function_exists('formatSummary')) {
    /**
     * Checks the given summary array or object to determine if it contains changes.
     * If the summary is not an array, it wraps it in an array. If all items are empty, it returns "nu sunt modificari".
     *
     * @param mixed $summary The summary to check, which can be an object or an array.
     * @return string|array The formatted summary or "nu sunt modificari".
     */
    function formatSummary($summary)
    {
        if (!is_array($summary)) {
            $summary = [$summary];
        }
        if (count($summary) == 0) return null;
        foreach ($summary as $item) {
            if (
                (isset($item->nume_produs) && $item->nume_produs !== '') ||
                (isset($item->pret) && $item->pret !== '') ||
                (isset($item->detalii_aditionale) && $item->detalii_aditionale !== '')
            ) {
                Log::info("info", ['annex' => $summary]);
                return $summary;
            }
        }

        return null;
    }
}

if (!function_exists('runPrompt')) {
    /**
     * Runs a prompt using the LLM OCR Service and returns the formatted JSON properties.
     *
     * @param string $promptText The text used to prompt the LLM OCR service.
     * @param object $LLMOCRService The service handling the document's OCR and LLM interaction.
     * @return mixed The decoded JSON from the response or null if errors occur.
     */
    function runPrompt($promptText, $LLMOCRService)
    {
        $buildPrompt = $LLMOCRService->getFileData($promptText);

        $resultContent = access_nested_array($buildPrompt, ['content', 0, 'text']);
        $resultContent = str_replace(['```json', '```', "\n"], '', $resultContent);

        if (preg_match('/{.*}/s', $resultContent, $jsonMatches)) {
            $jsonContent = $jsonMatches[0];
        } else {
            Log::error('Failed to extract JSON from content.', ['resultContent' => $resultContent]);
            return null;
        }

        $decodedResult = json_decode($jsonContent, false);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decoding error.', ['jsonContent' => $jsonContent, 'json_error' => json_last_error_msg()]);
            return null;
        }

        return $decodedResult;
    }
}

if (!function_exists('runPrompt2')) {
    /**
     * Runs a prompt using the LLM OCR Service and returns the formatted JSON properties.
     *
     * @param string $promptText The text used to prompt the LLM OCR service.
     * @param object $LLMOCRService The service handling the document's OCR and LLM interaction.
     * @return mixed The decoded JSON from the response or null if errors occur.
     */
    function runPrompt2($promptText, $LLMOCRService)
    {
        $buildPrompt = $LLMOCRService->getFileData($promptText);

        $resultContent = access_nested_array($buildPrompt, ['content', 0, 'text']);

        return $resultContent;
    }
}
