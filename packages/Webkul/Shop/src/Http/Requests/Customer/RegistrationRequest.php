<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Customer\Facades\Captcha;

class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => 'string|required',
            'last_name'  => 'string|required',
            'phone'      => 'required|digits:10|regex:/^[6-9]\d{9}$/|unique:customers,phone,NULL,id,channel_id,'.core()->getCurrentChannel()->id,
        ];

        return Captcha::getValidations($rules);
    }

    public function messages(): array
    {
        return array_merge(Captcha::getValidationMessages(), [
            'phone.regex'  => 'Mobile number must start with 6, 7, 8, or 9.',
            'phone.unique' => 'An account with this mobile number already exists.',
            'phone.digits' => 'Mobile number must be exactly 10 digits.',
        ]);
    }
}
