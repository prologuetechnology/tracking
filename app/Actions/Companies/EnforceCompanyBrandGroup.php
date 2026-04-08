<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Validation\ValidationException;

class EnforceCompanyBrandGroup
{
    public function execute(Company $company, array $siblingBrandAssignments = []): Company
    {
        $assignments = $this->normalizeAssignments($siblingBrandAssignments);

        $group = Company::query()
            ->where('is_active', true)
            ->where('pipeline_company_id', $company->pipeline_company_id)
            ->lockForUpdate()
            ->get();

        $this->validateAssignmentTargets($company, $group, $assignments);

        if ($group->count() <= 1) {
            $this->validateSingleCompany($company);

            $company->forceFill([
                'brand' => Company::normalizeBrand($company->brand),
            ])->save();

            return $company->load(ListCompanies::RELATIONS);
        }

        $brands = [];
        $errors = [];

        foreach ($group as $groupCompany) {
            $brand = $groupCompany->is($company)
                ? Company::normalizeBrand($company->brand)
                : ($assignments[$groupCompany->id] ?? Company::normalizeBrand($groupCompany->brand));

            if ($brand === null) {
                $errors['sibling_brand_assignments'] = [
                    'Every active company sharing this Pipeline company id must have a brand.',
                ];

                if ($groupCompany->is($company)) {
                    $errors['brand'] = ['Brand is required because this Pipeline company id is shared.'];
                }
            }

            $brands[$groupCompany->id] = $brand;
        }

        $seenBrands = [];
        foreach ($brands as $companyId => $brand) {
            if ($brand === null) {
                continue;
            }

            if (array_key_exists($brand, $seenBrands)) {
                $errors['brand'] = [
                    'Brand must be unique among active companies sharing this Pipeline company id.',
                ];
                $errors['sibling_brand_assignments'] = [
                    'Sibling brands must be unique among active companies sharing this Pipeline company id.',
                ];

                break;
            }

            $seenBrands[$brand] = $companyId;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($group as $groupCompany) {
            $groupCompany->forceFill([
                'requires_brand' => true,
                'brand' => $brands[$groupCompany->id],
            ])->save();
        }

        return $company->refresh()->load(ListCompanies::RELATIONS);
    }

    private function normalizeAssignments(array $assignments): array
    {
        $normalizedAssignments = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment) || ! array_key_exists('company_id', $assignment)) {
                continue;
            }

            $normalizedAssignments[(int) $assignment['company_id']] = Company::normalizeBrand(
                is_string($assignment['brand'] ?? null) ? $assignment['brand'] : null,
            );
        }

        return $normalizedAssignments;
    }

    private function validateAssignmentTargets(Company $company, $group, array $assignments): void
    {
        if ($assignments === []) {
            return;
        }

        $validSiblingIds = $group
            ->reject(fn (Company $groupCompany): bool => $groupCompany->is($company))
            ->pluck('id')
            ->all();

        foreach (array_keys($assignments) as $companyId) {
            if (! in_array($companyId, $validSiblingIds, true)) {
                throw ValidationException::withMessages([
                    'sibling_brand_assignments' => [
                        'Sibling brand assignments may only target active companies sharing this Pipeline company id.',
                    ],
                ]);
            }
        }
    }

    private function validateSingleCompany(Company $company): void
    {
        if ($company->requires_brand && Company::normalizeBrand($company->brand) === null) {
            throw ValidationException::withMessages([
                'brand' => ['Brand is required when requiring a brand.'],
            ]);
        }
    }
}
