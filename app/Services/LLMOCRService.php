<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Vision\V1\GcsSource;
use Google\Cloud\Vision\V1\InputConfig;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\AnnotateFileRequest;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\ApiCore\ApiException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Exception;

class LLMOCRService
{
    private $storage;
    private $vision;
    private $mimeType = 'application/pdf';
    private $language = 'ro';

    public function __construct()
    {
        // Initialize Google Cloud Storage and Vision clients
        $this->storage = new StorageClient([
            'projectId' => config('services.google_cloud.project_id'),
            'keyFile' => json_decode(file_get_contents(config('services.google_cloud.key_file')), true),
        ]);

        $this->vision = new ImageAnnotatorClient([
            'credentials' => config('services.google_cloud.key_file'),
        ]);
    }

    public function getFileData($prompt)
    {
        $endpoint = 'https://api.anthropic.com/v1/messages';
        $apiKey = config('services.claudeAiKey');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-api-key: $apiKey",
            'anthropic-version: 2023-06-01',
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($prompt));

        try {
            $response = curl_exec($ch);
            if ($response === false) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }

            curl_close($ch);

            $content = json_decode($response, true);
            return $content;
        } catch (Exception $e) {
            Log::info('Prompt: ' . $e);
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    public function uploadDocumentToCloudStorage(string $filePath, string $fileName)
    {
        // Get the bucket name from the environment configuration
        $bucketName = env('GOOGLE_CLOUD_STORAGE_BUCKET');
        if (!$bucketName) {
            Log::error('Google Cloud Storage bucket not configured');
            return null;
        }

        $bucket = $this->storage->bucket($bucketName);

        // Upload the file to Google Cloud Storage with metadata
        try {
            $response = $bucket->upload(fopen($filePath, 'rb'), [
                'name' => $fileName,
                'contentType' => $this->mimeType,
                'metadata' => [
                    'language' => $this->language,
                ],
            ]);

            return $response->info()['etag'];
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'file' => $fileName,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function gcsTextract(string $fileName)
    {
        $bucketName = env('GOOGLE_CLOUD_STORAGE_BUCKET');
        if (!$bucketName) {
            Log::error('Google Cloud Storage bucket not configured');
            return null;
        }

        $gcsUri = sprintf('gs://%s/%s', $bucketName, $fileName);
        $textOutput = '';

        try {
            $gcsSource = (new GcsSource())->setUri($gcsUri);
            $inputConfig = (new InputConfig())
                ->setGcsSource($gcsSource)
                ->setMimeType('application/pdf');

            $feature = (new Feature())->setType(Feature\Type::DOCUMENT_TEXT_DETECTION);
            $request = (new AnnotateFileRequest())
                ->setInputConfig($inputConfig)
                ->setFeatures([$feature]);

            // First request
            $response = $this->vision->batchAnnotateFiles([$request]);
            $responses = $response->getResponses();

            if (empty($responses)) {
                Log::error('No responses received from Vision API', ['file' => $fileName]);
                return 'No responses received';
            }

            foreach ($responses as $fileResponse) {
                if ($fileResponse->hasError()) {
                    Log::error('Vision API response error', [
                        'file' => $fileName,
                        'error' => $fileResponse->getError()->getMessage(),
                    ]);
                    return null;
                }

                // Retrieve the total number of pages
                $totalPages = $fileResponse->getTotalPages();
                $pageCount = 0;

                foreach ($fileResponse->getResponses() as $pageResponse) {
                    $fullTextAnnotation = $pageResponse->getFullTextAnnotation();
                    if ($fullTextAnnotation) {
                        $textOutput .= $fullTextAnnotation->getText() . "\n"; // Adding a new line for separation
                        $pageCount++;
                    } else {
                        Log::warning('No text found on page', ['file' => $fileName]);
                    }
                }

                // If total pages exceed the limit, loop to process the next pages
                // Loop through and process all pages
                while ($pageCount < $totalPages) {
                    $startPage = $pageCount + 1;
                    $endPage = min($startPage + 4, $totalPages);

                    Log::info('Processing pages', ['start' => $startPage, 'end' => $endPage]);

                    $request = (new AnnotateFileRequest())
                        ->setInputConfig($inputConfig)
                        ->setFeatures([$feature])
                        ->setPages(range($startPage, $endPage));  // Now range is one-indexed

                    $response = $this->vision->batchAnnotateFiles([$request]);
                    $responses = $response->getResponses();

                    foreach ($responses as $nextFileResponse) {
                        if ($nextFileResponse->hasError()) {
                            Log::error('Vision API response error', [
                                'file' => $fileName,
                                'error' => $nextFileResponse->getError()->getMessage(),
                            ]);
                            return null;
                        }

                        foreach ($nextFileResponse->getResponses() as $pageResponse) {
                            $fullTextAnnotation = $pageResponse->getFullTextAnnotation();
                            if ($fullTextAnnotation) {
                                $textOutput .= $fullTextAnnotation->getText() . "\n"; // Add new line for separation
                                $pageCount++; // Increment pageCount for one-indexed pages
                            } else {
                                Log::warning('No text found on page', ['file' => $fileName]);
                            }
                        }
                    }

                    // Ensure we stop when we've reached the last page
                    if ($pageCount >= $totalPages) {
                        break;
                    }
                }
            }

            if (empty(trim($textOutput))) {
                Log::warning('No text found in the document', ['file' => $fileName]);
                return 'No text found';
            }

            // dd($textOutput);

            return trim($textOutput);
        } catch (ApiException $e) {
            Log::error('Vision API Error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $fileName,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected Error', [
                'message' => $e->getMessage(),
                'file' => $fileName,
            ]);
            return null;
        }
    }

    public function getFileFromGCS($fileName)
    {
        $bucketName = env('GOOGLE_CLOUD_STORAGE_BUCKET');
        if (!$bucketName) {
            Log::error('Google Cloud Storage bucket not configured');
            return null;
        }

        $bucket = $this->storage->bucket($bucketName);
        $object = $bucket->object($fileName);

        // Set expiration time for the signed URL
        $expiresAt = Carbon::now()->addMinutes(10);

        try {
            // Generate a signed URL for the specified object
            $signedUrl = $object->signedUrl($expiresAt);
            return $signedUrl;
        } catch (\Exception $e) {
            Log::error('Error generating signed URL', [
                'file' => $fileName,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
