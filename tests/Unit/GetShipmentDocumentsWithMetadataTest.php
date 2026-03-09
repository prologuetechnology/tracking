<?php

namespace Tests\Unit;

use App\Actions\GetShipmentDocumentsWithMetadata;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetShipmentDocumentsWithMetadataTest extends TestCase
{
    public function test_it_filters_documents_and_enriches_metadata(): void
    {
        Http::fake([
            'https://documents.example/*' => Http::response('', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => '1024',
                'Last-Modified' => 'Wed, 06 Mar 2024 12:00:00 GMT',
            ]),
        ]);

        $documents = (new GetShipmentDocumentsWithMetadata)([
            ['name' => 'bol', 'file' => 'http://documents.example/bol.pdf'],
            ['name' => 'pod', 'file' => 'http://documents.example/pod.pdf'],
            ['name' => 'invoice', 'file' => 'http://documents.example/invoice.pdf'],
        ], ['bol', 'pod']);

        $this->assertCount(2, $documents);
        $this->assertSame('https://documents.example/bol.pdf', $documents[0]['url']);
        $this->assertSame('application/pdf', $documents[0]['type']);
        $this->assertArrayNotHasKey('file', $documents[0]);
    }

    public function test_it_marks_documents_as_failed_when_the_head_request_errors(): void
    {
        Http::fake([
            'https://documents.example/*' => fn () => throw new \RuntimeException('boom'),
        ]);

        $documents = (new GetShipmentDocumentsWithMetadata)([
            ['name' => 'bol', 'file' => 'http://documents.example/bol.pdf'],
        ]);

        $this->assertTrue($documents[0]['error']);
        $this->assertNull($documents[0]['type']);
        $this->assertSame('https://documents.example/bol.pdf', $documents[0]['url']);
    }
}
