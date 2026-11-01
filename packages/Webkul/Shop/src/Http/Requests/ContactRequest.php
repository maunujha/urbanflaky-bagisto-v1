<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use App\Support\IndianMobile;
use Illuminate\Foundation\Http\FormRequest;
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
            'contact' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! IndianMobile::isValid($value)) {
                    $fail('Enter a valid 10-digit Indian mobile number.');
                }
            }],
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
            /* Exact match on the normalised number — no suffix matching, so a
               padded "<anything><verified number>" can never pass. */
            if (! $this->verifiedPhone()) {
                $v->errors()->add('contact', 'Please verify your phone number with the OTP before submitting.');
            }
        });
    }

    /**
     * The server-verified number, or null. Callers must use this — never the
     * raw `contact` input — when storing or mailing the enquiry.
     */
    public function verifiedPhone(): ?string
    {
        $verifiedPhone = (string) session('contact_otp_phone');
        $submitted = IndianMobile::normalize($this->input('contact'));

        return session('contact_phone_verified') === true
            && $submitted !== null
            && hash_equals($verifiedPhone, $submitted)
                ? $verifiedPhone
                : null;
    }
}
