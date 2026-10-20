<?php

namespace Tests\Feature;

use App\Services\Marketing\PollinationsImageService;
use DomainException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PollinationsImageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['pollinations.enabled' => true, 'pollinations.mode' => 'keyed', 'pollinations.api_key' => 'test-key', 'pollinations.max_bytes' => 1024 * 1024]);
        Http::preventStrayRequests();
    }

    public function test_missing_key_never_calls_upstream(): void
    {
        config(['pollinations.api_key' => '']);
        Http::fake();
        try { app(PollinationsImageService::class)->generate('A complete car on a road', 1); $this->fail('Expected configuration error'); }
        catch (DomainException $e) { $this->assertStringContainsString('API_KEY', $e->getMessage()); }
        Http::assertNothingSent();
    }

    public function test_keyed_response_is_decoded_to_binary_and_checked(): void
    {
        // Valid 256x256 PNG, generated with zlib, no image-extension dependency in test.
        $chunk = fn ($type, $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $png = "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', 256, 256, 8, 2, 0, 0, 0))
            . $chunk('IDAT', gzcompress(str_repeat("\0" . str_repeat("\0", 256 * 3), 256))) . $chunk('IEND', '');
        Http::fake(['gen.pollinations.ai/*' => Http::response(['data' => [['b64_json' => base64_encode($png)]]])]);
        $result = app(PollinationsImageService::class)->generate('A complete car on a road', 1);
        $this->assertSame($png, $result['bytes']);
        $this->assertSame('png', $result['extension']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key') && $request['response_format'] === 'b64_json');
    }

    public function test_payment_error_is_not_retried_or_exposed(): void
    {
        Http::fake(['gen.pollinations.ai/*' => Http::response(['secret' => 'private-upstream-detail'], 402)]);
        try { app(PollinationsImageService::class)->generate('A car', 1); $this->fail('Expected 402 handling'); }
        catch (DomainException $e) {
            $this->assertStringContainsString('Pollen', $e->getMessage());
            $this->assertStringNotContainsString('private-upstream-detail', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_json_error_cannot_be_saved_as_an_image(): void
    {
        Http::fake(['gen.pollinations.ai/*' => Http::response(['data' => [['b64_json' => base64_encode('{"error":"blocked"}')]]])]);
        $this->expectException(DomainException::class);
        app(PollinationsImageService::class)->generate('A car', 1);
    }

    public function test_classic_uses_fixed_host_without_authentication(): void
    {
        config(['pollinations.mode' => 'classic']);
        Http::fake(['image.pollinations.ai/*' => Http::response('limit', 429)]);
        try { app(PollinationsImageService::class)->generate('A car', 1); $this->fail('Expected rate limit'); }
        catch (DomainException $e) { $this->assertStringContainsString('giới hạn', $e->getMessage()); }
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://image.pollinations.ai/prompt/') && !$request->hasHeader('Authorization'));
    }
}
