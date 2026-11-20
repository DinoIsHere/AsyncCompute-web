<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LaunchInstanceRequest extends FormRequest
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
            'offering_id' => ['required', 'integer', 'exists:instance_offerings,id'],
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/'],
            'ssh_key_name' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Configure the validator instance: check real-time stock.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $offeringId = $this->input('offering_id');

            if (!$offeringId) {
                return;
            }

            $offering = DB::table('instance_offerings')->find($offeringId);

            if (!$offering) {
                $validator->errors()->add('offering_id', 'That configuration no longer exists.');
                return;
            }

            if ((int) $offering->stock < 1) {
                $validator->errors()->add('offering_id', 'This configuration is out of stock.');
            }
        });
    }
}
