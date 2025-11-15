<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CompleteUploadRequest validates data for completing a video upload.
 *
 * Verifies that the uploaded file is valid and can be processed.
 *
 * Validates:
 * - storage_path: Required, must match uploaded file path format
 * - file_size: Optional file size in bytes for validation
 */
class CompleteUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only authenticated users can complete uploads.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'storage_path' => 'required|string|regex:/^videos\/uploads\/\d+\/.+\.(?:mp4|webm|mov|avi|mkv|flv|wmv)$/',
            'file_size' => 'nullable|integer|min:1048576', // Min 1 MB
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'storage_path.required' => 'Storage path is required',
            'storage_path.string' => 'Storage path must be a string',
            'storage_path.regex' => 'Storage path format is invalid. Expected: videos/uploads/{video_id}/{uuid}.{extension}',
            'file_size.integer' => 'File size must be an integer',
            'file_size.min' => 'File size must be at least 1 MB',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Normalizes input data before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('storage_path')) {
            $this->merge([
                'storage_path' => trim($this->get('storage_path')),
            ]);
        }

        if ($this->has('file_size')) {
            $this->merge([
                'file_size' => (int) $this->get('file_size'),
            ]);
        }
    }
}
