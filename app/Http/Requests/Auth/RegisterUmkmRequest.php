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

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan format email resmi (contoh: user@domain.com).',
            'email.regex' => 'Format email tidak valid. Pastikan menyertakan domain yang benar (contoh: nama@bisnis.com).',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk ke akun Anda.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok dengan kata sandi.',
            'password_confirmation.required' => 'Konfirmasi kata sandi wajib diisi.',
            'business_name.required' => 'Nama Usaha / Bisnis Kuliner wajib diisi.',
            'business_name.min' => 'Nama Usaha minimal 3 karakter.',
            'owner_name.required' => 'Nama Pemilik / Penanggung Jawab wajib diisi.',
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
