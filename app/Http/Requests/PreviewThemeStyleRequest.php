<?php

namespace App\Http\Requests;

use App\Actions\Themes\ResolveThemeStyle;
use Illuminate\Foundation\Http\FormRequest;

class PreviewThemeStyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(['theme:show', 'theme:store', 'theme:update']) ?? false;
    }

    public function rules(): array
    {
        return ResolveThemeStyle::rules('style');
    }
}
