<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->routeIs('pengguna.reset-password')) {
            return [
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        $pengguna = $this->route('pengguna');

        $rules = [
            'nama' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:150',
                Rule::unique('pengguna', 'email')->ignore($pengguna?->id),
            ],
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => is_string($this->nama) ? trim($this->nama) : $this->nama,
            'email' => is_string($this->email) ? trim(mb_strtolower($this->email)) : $this->email,
        ]);
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama pengguna wajib diisi.',
            'nama.max' => 'Nama pengguna maksimal 100 karakter.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email sudah digunakan.',

            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
