<?php

namespace App\Http\Resources;

use App\Models\CompanyApiToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyApiTokenResource extends JsonResource
{
    private bool $revealToken = false;

    /**
     * Return a resource instance that includes the full plaintext token.
     * Should only be used immediately after token creation.
     */
    public static function withToken(CompanyApiToken $resource): self
    {
        $instance = new self($resource);
        $instance->revealToken = true;

        return $instance;
    }

    private function maskedToken(): string
    {
        $token = $this->api_token;
        $visibleLength = 4;

        if (strlen($token) <= $visibleLength) {
            return $token;
        }

        return str_repeat('*', strlen($token) - $visibleLength) . substr($token, -$visibleLength);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'api_token' => $this->revealToken ? $this->api_token : $this->maskedToken(),
            'bol' => $this->bol,
            'is_valid' => (bool) $this->is_valid,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
