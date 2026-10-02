<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'extensions:csv', 'mimes:csv,txt', 'max:40960']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Оберіть CSV-файл.',
            'file.file' => 'Не вдалося завантажити файл.',
            'file.extensions' => 'Файл має мати розширення .csv.',
            'file.mimes' => 'Завантажте текстовий CSV-файл.',
            'file.max' => 'Розмір файлу не повинен перевищувати 40 МБ.',
        ];
    }
}
