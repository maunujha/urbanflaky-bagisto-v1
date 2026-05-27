<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Customer\Facades\Captcha;

class ContactRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return Captcha::getValidations([
            'name'    => 'string|required|max:120',
            'email'   => 'string|required|email|max:160',
            'contact' => ['required', new PhoneNumber],
            'topic'   => 'nullable|string|max:60',
            'message' => 'required|string|max:2000',
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return array_merge(Captcha::getValidationMessages(), [
            'contact.required' => 'Please enter and verify your phone number.',
        ]);
    }

    /**
     * After standard validation, ensure the phone has been OTP-verified
     * and the verified phone matches the submitted contact number.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $verified      = (bool) session('contact_phone_verified');
            $verifiedPhone = (string) session('contact_otp_phone');
            $submitted     = preg_replace('/\D/', '', (string) $this->input('contact'));
            $submitted     = substr($submitted, -10);

            if (! $verified || $verifiedPhone !== $submitted) {
                $v->errors()->add('contact', 'Please verify your phone number with the OTP before submitting.');
            }
        });
    }
}
