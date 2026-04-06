<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class GetShipmentDocumentsWithMetadata
{
    public function __invoke(array $documents, ?array $documentTypes = []): array
    {
        $collection = collect($documents);
        // If documentTypes is provided and not empty, filter; otherwise, return all
        if (is_array($documentTypes) && ! empty($documentTypes)) {
            $collection = $collection->whereIn('name', $documentTypes);
        }

        return $collection
            ->map(function ($doc) {
                $url = $this->normalizeDocumentUrl($doc['file']);

                if ($this->isLocalFakeDocument($url)) {
                    return [
                        ...$doc,
                        'url' => $url,
                        'type' => 'application/pdf',
                        'size' => '1024',
                        'last_modified' => 'Wed, 06 Mar 2024 12:00:00 GMT',
                    ];
                }

                try {
                    $response = Http::withOptions(['stream' => true])->head($url);

                    if (! $response->successful()) {
                        return [
                            ...$doc,
                            'url' => $url,
                            'type' => null,
                            'size' => null,
                            'last_modified' => null,
                            'error' => true,
                        ];
                    }

                    return [
                        ...$doc,
                        'url' => $url,
                        'type' => $response->header('Content-Type'),
                        'size' => $response->header('Content-Length'),
                        'last_modified' => $response->header('Last-Modified'),
                    ];
                } catch (\Exception $e) {
                    return [
                        ...$doc,
                        'url' => $url,
                        'type' => null,
                        'size' => null,
                        'error' => true,
                    ];
                }
            })
            ->map(function ($doc) {
                unset($doc['file']);

                return $doc;
            })
            ->values()
            ->all();
    }

    private function normalizeDocumentUrl(string $url): string
    {
        if ($this->isLocalFakeDocument($url)) {
            return $url;
        }

        return preg_replace('/^http:/i', 'https:', $url) ?? $url;
    }

    private function isLocalFakeDocument(string $url): bool
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        return str_starts_with($url, $appUrl.'/testing/fake-pipeline/documents/');
    }
}
