<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGpuModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('gpu_models', 'name')->ignore($this->route('gpuModel')->id),
            ],
            'vram_gb' => ['required', 'numeric', 'min:1'],
            'vcpus' => ['required', 'integer', 'min:1'],
            'ram_gb' => ['required', 'integer', 'min:1'],
            'storage' => ['required', 'string', 'max:255'],
            'price_per_gpu_hour' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'offerings' => ['required', 'array', 'min:1'],
            'offerings.*.gpu_count' => ['required', 'in:1,2,4,8'],
            'offerings.*.region' => ['required', 'string', 'max:255'],
            'offerings.*.stock' => ['required', 'integer', 'min:0'],
        ];
    }
}
