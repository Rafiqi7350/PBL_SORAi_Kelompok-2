<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSupplierRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'commodity_id' => ['required', 'uuid', 'exists:commodities,id'],
            'max_capacity' => ['required', 'string', 'max:255'],
            'delivery_area' => ['required', 'string', 'max:255'],
            'nib_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:255'],
            'warehouse_address' => ['required', 'string'],
            'owner_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan format email resmi (contoh: supplier@domain.com).',
            'email.regex' => 'Format email tidak valid. Pastikan menyertakan domain yang benar (contoh: nama@distributor.com).',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok dengan kata sandi.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'company_name.required' => 'Nama Kelompok Tani / Distributor wajib diisi.',
            'phone.required' => 'Nomor WhatsApp Operasional wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp Operasional hanya boleh berisi angka.',
            'commodity_id.required' => 'Kategori Komoditas Utama wajib dipilih.',
            'commodity_id.exists' => 'Kategori komoditas yang dipilih tidak valid.',
            'max_capacity.required' => 'Kapasitas Pasok Maksimal wajib diisi.',
            'delivery_area.required' => 'Jangkauan Area Pengiriman Armada wajib diisi.',
            'nib_number.regex' => 'Nomor NIB / Legalitas Usaha hanya boleh berisi angka.',
            'warehouse_address.required' => 'Alamat Gudang / Lokasi Dapur Utama wajib diisi.',
        ];
    }
}
