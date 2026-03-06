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
                $url = preg_replace('/^http:/i', 'https:', $doc['file']);

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
}
