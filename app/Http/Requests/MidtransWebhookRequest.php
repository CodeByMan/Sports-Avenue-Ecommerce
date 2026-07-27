<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MidtransWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:100'],
            'transaction_id' => ['required', 'string', 'max:100'],
            'transaction_status' => [
                'required',
                'string',
                Rule::in([
                    'capture',
                    'settlement',
                    'pending',
                    'deny',
                    'expire',
                    'cancel',
                    'failure',
                    'refund',
                    'partial_refund',
                    'chargeback',
                    'partial_chargeback',
                ]),
            ],
            'status_code' => ['required', 'string', 'regex:/^\d{3}$/'],
            'gross_amount' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'signature_key' => ['required', 'string', 'size:128', 'regex:/^[a-fA-F0-9]{128}$/'],
            'payment_type' => ['nullable', 'string', 'max:100'],
            'fraud_status' => ['nullable', 'string', Rule::in(['accept', 'challenge', 'deny'])],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $supportReference = (string) Str::uuid();

        Log::warning('Malformed payment notification rejected.', [
            'support_reference' => $supportReference,
            'order_id' => $this->input('order_id'),
            'error_code' => 'PAYMENT_WEBHOOK_VALIDATION_FAILED',
            'validation_fields' => array_keys($validator->errors()->toArray()),
        ]);

        throw new HttpResponseException(response()->json([
            'message' => 'Payment notification could not be verified.',
            'support_reference' => $supportReference,
        ], 401));
    }
}
