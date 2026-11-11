<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Silakan pilih file foto profil yang ingin diunggah.',
            'photo.image' => 'Foto profil harus berupa file gambar yang valid.',
            'photo.mimes' => 'Format foto profil harus JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto profil maksimal 2 MB.',
        ];
    }
}
