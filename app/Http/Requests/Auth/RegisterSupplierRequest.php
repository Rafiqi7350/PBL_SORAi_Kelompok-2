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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'commodity_id' => ['required', 'uuid', 'exists:commodities,id'],
            'max_capacity' => ['required', 'string', 'max:255'],
            'delivery_area' => ['required', 'string', 'max:255'],
            'nib_number' => ['required', 'string', 'max:255'],
            'warehouse_address' => ['required', 'string'],
            'owner_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'company_name.required' => 'Nama Kelompok Tani / Distributor wajib diisi.',
            'phone.required' => 'Nomor WhatsApp Operasional wajib diisi.',
            'commodity_id.required' => 'Kategori Komoditas Utama wajib dipilih.',
            'commodity_id.exists' => 'Kategori komoditas yang dipilih tidak valid.',
            'max_capacity.required' => 'Kapasitas Pasok Maksimal wajib diisi.',
            'delivery_area.required' => 'Jangkauan Area Pengiriman Armada wajib diisi.',
            'nib_number.required' => 'Nomor NIB / Legalitas Usaha wajib diisi.',
            'warehouse_address.required' => 'Alamat Gudang / Lokasi Dapur Utama wajib diisi.',
        ];
    }
}
