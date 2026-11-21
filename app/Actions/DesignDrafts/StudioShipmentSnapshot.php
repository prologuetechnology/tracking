<?php

namespace App\Actions\DesignDrafts;

use App\Actions\Tracking\ResolveTrackingPayload;
use App\Models\Company;
use App\Models\DesignDraftRevision;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudioShipmentSnapshot
{
    public function __construct(private readonly ResolveTrackingPayload $resolver) {}

    /**
     * @return array{token: string, captured_at: string, expires_at: string, trackingData: array, shipmentCoordinates: ?array, shipmentDocuments: array}
     */
    public function load(Company $company, int $staffId, array $lookup, ?string $token, ?DesignDraftRevision $revision = null): array
    {
        $capturing = $token === null;
        $token ??= (string) Str::uuid();
        $scope = [$company->id, $company->pipeline_company_id, $company->brand, $company->requires_brand,
            $staffId, $lookup['search_option'], $lookup['tracking_number'], $token];
        if ($revision) {
            $scope[] = $revision->getKey();
        }
        $key = 'tracking-studio:'.hash('sha256', json_encode($scope, JSON_THROW_ON_ERROR));

        if (! $capturing) {
            $encrypted = Cache::get($key);
            if (! is_string($encrypted) || ! $company->is(Company::findByIdentifier($company->brand, $company->pipeline_company_id))) {
                throw ValidationException::withMessages(['snapshot_token' => 'Load this shipment again for a fresh preview.']);
            }

            return json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        }

        $allowed = $revision ? ClassicDesign::visibleSections($company, $revision->design) : ClassicDesign::availableSections($company);
        $payload = $this->resolver->execute(
            trackingNumber: $lookup['tracking_number'],
            searchOption: $lookup['search_option'],
            companyId: (string) $company->pipeline_company_id,
            brand: $company->brand,
            expectedCompany: $company,
            visibleSections: $allowed,
        );
        if (! $payload['found']) {
            throw ValidationException::withMessages(['tracking_number' => 'No shipment was available for this company and reference.']);
        }

        $tracking = $payload['trackingData'];
        foreach (['special_instructions' => 'specialInstructions', 'customer_pos' => 'poNumbers'] as $feature => $field) {
            if (! in_array($feature, $allowed, true)) {
                unset($tracking[$field]);
            }
        }

        $capturedAt = now();
        $expiresAt = $capturedAt->copy()->addMinutes(15);
        $snapshot = [
            'token' => $token,
            'captured_at' => $capturedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'trackingData' => $tracking,
            'shipmentCoordinates' => $payload['shipmentCoordinates'],
            'shipmentDocuments' => $payload['shipmentDocuments'],
        ];
        if ($revision) {
            $snapshot['review_signature'] = $this->signature($this->page($company, $revision, $snapshot));
        }
        Cache::put($key, Crypt::encryptString(json_encode($snapshot, JSON_THROW_ON_ERROR)), $expiresAt);

        return $snapshot;
    }

    /**
     * @param  array{trackingNumber: string, searchOption: string}  $lookup
     * @return array{token: string, captured_at: string, expires_at: string, page: array}
     */
    public function savedRevision(Company $company, DesignDraftRevision $revision, int $staffId, array $lookup, ?string $token): array
    {
        $snapshot = $this->load($company, $staffId, [
            'tracking_number' => $lookup['trackingNumber'], 'search_option' => $lookup['searchOption'],
        ], $token, $revision);
        $page = $this->page($company, $revision, $snapshot);
        if (! hash_equals($snapshot['review_signature'], $this->signature($page))) {
            throw ValidationException::withMessages(['snapshot_token' => 'The page has changed. Load the shipment again to review it before exporting.']);
        }

        return [
            'token' => $snapshot['token'], 'captured_at' => $snapshot['captured_at'],
            'expires_at' => $snapshot['expires_at'], 'page' => $page,
        ];
    }

    private function signature(array $page): string
    {
        return hash('sha256', json_encode($page, JSON_THROW_ON_ERROR));
    }

    public function page(Company $company, DesignDraftRevision $revision, array $snapshot): array
    {
        $snapshot['company'] = $company;
        if ($snapshot['shipmentDocuments'] && ! $company->apiToken()->exists()) {
            $snapshot['shipmentDocuments'] = [];
        }

        return TrackingDesignPage::fromPayload($revision, $snapshot);
    }
}
