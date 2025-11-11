<?php

namespace App\Services;

use App\Models\Template;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TemplateService
{
    /**
     * Create a new template
     * @throws Exception
     */
    public function createTemplate(User $user, array $data): Template
    {
        // Validate placeholders
        $placeholderErrors = $this->validatePlaceholders($data['content']);
        if (!empty($placeholderErrors)) {
            throw new Exception(implode(', ', $placeholderErrors));
        }

        // Handle media upload if present
        if (isset($data['media']) && $data['media'] instanceof UploadedFile) {
            $mediaData = $this->handleMediaUpload($data['media'], $data['type']);
            $data['media_path'] = $mediaData['path'];
            $data['media_url'] = $mediaData['url'];
            unset($data['media']);
        }

        $data['user_id'] = $user->id;

        return Template::create($data);
    }

    /**
     * Validate placeholder syntax in content
     */
    public function validatePlaceholders(string $content): array
    {
        $allowedPlaceholders = [
            '{{first_name}}',
            '{{last_name}}',
            '{{phone}}',
            '{{custom_field_1}}',
            '{{custom_field_2}}',
            '{{custom_field_3}}',
        ];

        // Find all placeholders in content
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        $errors = [];
        if (!empty($matches[0])) {
            foreach ($matches[0] as $placeholder) {
                if (!in_array($placeholder, $allowedPlaceholders)) {
                    $errors[] = "Invalid placeholder: {$placeholder}";
                }
            }
        }

        return $errors;
    }

    /**
     * Handle media file upload
     * @throws Exception
     */
    public function handleMediaUpload(UploadedFile $file, string $type): array
    {
        // Validate file based on type
        $validationRules = $this->getMediaValidationRules($type);

        $maxSize = $validationRules['max_size'];
        $allowedMimes = $validationRules['mimes'];

        // Check file size (in KB)
        if ($file->getSize() > $maxSize * 1024) {
            throw new Exception("File size exceeds maximum allowed ({$maxSize}KB)");
        }

        // Check mime type
        if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
            throw new Exception("Invalid file type for {$type}");
        }

        // Store file
        $path = $file->store('templates', 'public');
        $url = Storage::url($path);

        return [
            'path' => $path,
            'url' => $url,
        ];
    }

    /**
     * Get validation rules for media based on type
     */
    private function getMediaValidationRules(string $type): array
    {
        return match ($type) {
            'text_image' => [
                'max_size' => 5120, // 5MB
                'mimes' => ['jpg', 'jpeg', 'png', 'gif'],
            ],
            'text_video' => [
                'max_size' => 16384, // 16MB
                'mimes' => ['mp4'],
            ],
            'text_document' => [
                'max_size' => 10240, // 10MB
                'mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            ],
            default => [
                'max_size' => 0,
                'mimes' => [],
            ],
        };
    }

    /**
     * Update a template
     * @throws Exception
     */
    public function updateTemplate(Template $template, array $data): Template
    {
        // Validate placeholders
        if (isset($data['content'])) {
            $placeholderErrors = $this->validatePlaceholders($data['content']);
            if (!empty($placeholderErrors)) {
                throw new Exception(implode(', ', $placeholderErrors));
            }
        }

        // Handle new media upload if present
        if (isset($data['media']) && $data['media'] instanceof UploadedFile) {
            // Delete old media
            $template->deleteMedia();

            // Upload new media
            $mediaData = $this->handleMediaUpload($data['media'], $data['type']);
            $data['media_path'] = $mediaData['path'];
            $data['media_url'] = $mediaData['url'];
            unset($data['media']);
        }

        // If type changed to text, remove media
        if (isset($data['type']) && $data['type'] === 'text' && $template->has_media) {
            $template->deleteMedia();
            $data['media_path'] = null;
            $data['media_url'] = null;
            $data['caption'] = null;
        }

        $template->update($data);

        return $template->fresh();
    }

    /**
     * Delete a template
     */
    public function deleteTemplate(Template $template): bool
    {
        return $template->delete();
    }

    /**
     * Get available placeholders
     */
    public function getAvailablePlaceholders(): array
    {
        return [
            [
                'value' => '{{first_name}}',
                'label' => 'First Name',
                'description' => 'Contact\'s first name',
            ],
            [
                'value' => '{{last_name}}',
                'label' => 'Last Name',
                'description' => 'Contact\'s last name',
            ],
            [
                'value' => '{{phone}}',
                'label' => 'Phone Number',
                'description' => 'Contact\'s phone number',
            ],
            [
                'value' => '{{custom_field_1}}',
                'label' => 'Custom Field 1',
                'description' => 'First custom field',
            ],
            [
                'value' => '{{custom_field_2}}',
                'label' => 'Custom Field 2',
                'description' => 'Second custom field',
            ],
            [
                'value' => '{{custom_field_3}}',
                'label' => 'Custom Field 3',
                'description' => 'Third custom field',
            ],
        ];
    }
}
