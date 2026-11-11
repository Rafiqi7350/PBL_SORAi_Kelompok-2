<?php

namespace App\Http\Requests\Umkm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUmkmAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'umkm';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => trim((string) $this->input('email')),
            ]);
        }

        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\s+/', '', (string) $this->input('phone')) ?? '',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc,filter',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'business_name' => ['required', 'string', 'min:3', 'max:255'],
            'owner_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s\'.]+$/',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'min:10',
                'max:15',
            ],
            'address' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email akun wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan format email resmi (contoh: user@domain.com).',
            'email.regex' => 'Format email tidak valid. Pastikan menyertakan domain yang benar (contoh: nama@bisnis.com).',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'business_name.required' => 'Nama Usaha / Dapur Kuliner wajib diisi.',
            'business_name.min' => 'Nama Usaha minimal 3 karakter.',
            'owner_name.required' => 'Nama Pemilik / Pengelola wajib diisi.',
            'owner_name.min' => 'Nama Pemilik minimal 2 karakter.',
            'owner_name.regex' => 'Nama pemilik hanya boleh berisi huruf, spasi, atau tanda petik.',
            'phone.required' => 'Nomor WhatsApp Operasional wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka.',
            'phone.min' => 'Nomor WhatsApp minimal 10 digit angka.',
            'phone.max' => 'Nomor WhatsApp maksimal 15 digit angka.',
            'address.required' => 'Alamat Dapur / Lokasi Operasional wajib diisi.',
            'address.min' => 'Alamat operasional minimal 5 karakter.',
        ];
    }
}
