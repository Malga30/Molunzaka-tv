<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreVideoRequest validates data for creating a new video upload.
 *
 * Validates:
 * - title: Required, string, max 255 characters
 * - description: Optional string
 * - genres: Optional array of genre strings
 * - filename: Optional filename (defaults to 'video.mp4')
 */
class StoreVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only authenticated users can create videos.
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'genres' => 'nullable|array',
            'genres.*' => 'string|max:50',
            'filename' => 'nullable|string|max:255',
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
            'title.required' => 'Video title is required',
            'title.string' => 'Video title must be a string',
            'title.max' => 'Video title cannot exceed 255 characters',
            'description.string' => 'Description must be a string',
            'description.max' => 'Description cannot exceed 5000 characters',
            'genres.array' => 'Genres must be an array',
            'genres.*.string' => 'Each genre must be a string',
        ];
    }

    /**
     * Get the validated input as an array.
     *
     * Prepares data for model creation.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Ensure genres is always an array
        if (empty($validated['genres'])) {
            $validated['genres'] = [];
        }

        return $validated;
    }
}
