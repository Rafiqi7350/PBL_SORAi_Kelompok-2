<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUmkmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc,filter',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan format email resmi (contoh: user@domain.com).',
            'email.regex' => 'Format email tidak valid. Pastikan menyertakan domain yang benar (contoh: nama@bisnis.com).',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok dengan kata sandi.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'business_name.required' => 'Nama Usaha / Bisnis Kuliner wajib diisi.',
            'owner_name.required' => 'Nama Pemilik / Penanggung Jawab wajib diisi.',
            'phone.required' => 'Nomor WhatsApp Operasional wajib diisi.',
            'address.required' => 'Alamat Dapur / Lokasi Operasional wajib diisi.',
        ];
    }
}
