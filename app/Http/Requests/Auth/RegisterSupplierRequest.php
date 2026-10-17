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
            'company_name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]+$/', 'min:10', 'max:15'],
            'commodity_id' => ['required', 'uuid', 'exists:commodities,id'],
            'max_capacity' => ['required', 'string', 'max:255'],
            'delivery_area' => ['required', 'string', 'max:255'],
            'nib_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'warehouse_address' => ['required', 'string', 'min:5', 'max:500'],
            'owner_name' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'.]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan format email resmi (contoh: supplier@domain.com).',
            'email.regex' => 'Format email tidak valid. Pastikan menyertakan domain yang benar (contoh: nama@distributor.com).',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk ke akun Anda.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok dengan kata sandi.',
            'password_confirmation.required' => 'Konfirmasi kata sandi wajib diisi.',
            'company_name.required' => 'Nama Kelompok Tani / Distributor wajib diisi.',
            'company_name.min' => 'Nama Kelompok Tani / Distributor minimal 3 karakter.',
            'phone.required' => 'Nomor WhatsApp Operasional wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp Operasional hanya boleh berisi angka.',
            'phone.min' => 'Nomor WhatsApp minimal 10 digit angka.',
            'phone.max' => 'Nomor WhatsApp maksimal 15 digit angka.',
            'commodity_id.required' => 'Kategori Komoditas Utama wajib dipilih.',
            'commodity_id.exists' => 'Kategori komoditas yang dipilih tidak valid.',
            'max_capacity.required' => 'Kapasitas Pasok Maksimal wajib diisi.',
            'delivery_area.required' => 'Jangkauan Area Pengiriman Armada wajib diisi.',
            'nib_number.regex' => 'Nomor NIB / Legalitas Usaha hanya boleh berisi angka.',
            'nib_number.max' => 'Nomor NIB maksimal 20 digit angka.',
            'warehouse_address.required' => 'Alamat Gudang / Lokasi Dapur Utama wajib diisi.',
            'warehouse_address.min' => 'Alamat gudang minimal 5 karakter.',
            'owner_name.regex' => 'Nama pemilik/PIC hanya boleh berisi huruf, spasi, atau tanda petik.',
        ];
    }
}
