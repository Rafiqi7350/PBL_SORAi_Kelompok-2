<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'supplier';
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

        if ($this->has('nib_number')) {
            $nib = trim((string) $this->input('nib_number'));
            $this->merge([
                'nib_number' => $nib === '' ? null : $nib,
            ]);
        }

        if ($this->has('owner_name')) {
            $owner = trim((string) $this->input('owner_name'));
            $this->merge([
                'owner_name' => $owner === '' ? null : $owner,
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
            'company_name' => ['required', 'string', 'min:3', 'max:255'],
            'owner_name' => ['nullable', 'string', 'min:2', 'max:255', 'regex:/^[a-zA-Z\s\'.]+$/'],
            'phone' => ['required', 'string', 'regex:/^[0-9]+$/', 'min:10', 'max:15'],
            'commodity_id' => ['nullable', 'uuid', 'exists:commodity_categories,id'],
            'max_capacity' => ['required', 'string', 'max:255'],
            'delivery_area' => ['required', 'string', 'max:255'],
            'nib_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'warehouse_address' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email akun wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan format email resmi (contoh: supplier@domain.com).',
            'email.regex' => 'Format email tidak valid. Pastikan menyertakan domain yang benar (contoh: nama@distributor.com).',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'company_name.required' => 'Nama Kelompok Tani / Distributor wajib diisi.',
            'company_name.min' => 'Nama Kelompok Tani / Distributor minimal 3 karakter.',
            'owner_name.min' => 'Nama pemilik / PIC minimal 2 karakter.',
            'owner_name.regex' => 'Nama pemilik / PIC hanya boleh berisi huruf, spasi, atau tanda petik.',
            'phone.required' => 'Nomor WhatsApp Operasional wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp Operasional hanya boleh berisi angka.',
            'phone.min' => 'Nomor WhatsApp minimal 10 digit angka.',
            'phone.max' => 'Nomor WhatsApp maksimal 15 digit angka.',
            'commodity_id.uuid' => 'Format kategori komoditas tidak valid.',
            'commodity_id.exists' => 'Kategori komoditas yang dipilih tidak valid.',
            'max_capacity.required' => 'Kapasitas Pasok Maksimal wajib diisi.',
            'delivery_area.required' => 'Jangkauan Area Pengiriman Armada wajib diisi.',
            'nib_number.regex' => 'Nomor NIB / Legalitas Usaha hanya boleh berisi angka.',
            'nib_number.max' => 'Nomor NIB maksimal 20 digit angka.',
            'warehouse_address.required' => 'Alamat Gudang / Lokasi Sentra Pengiriman wajib diisi.',
            'warehouse_address.min' => 'Alamat gudang minimal 5 karakter.',
        ];
    }
}
