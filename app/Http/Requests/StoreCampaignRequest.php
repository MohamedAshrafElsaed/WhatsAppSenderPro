<?php

namespace App\Http\Requests;

use App\Models\Contact;
use App\Models\Template;
use App\Services\UsageTrackingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by policy in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'template_id' => [
                'nullable',
                'integer',
                'exists:templates,id',
            ],
            'message_type' => [
                'required',
                'string',
                Rule::in(['text', 'image', 'video', 'audio', 'document']),
            ],
            'message_content' => [
                'required',
                'string',
                'max:4096',
                'min:1',
            ],
            'message_caption' => [
                'nullable',
                'string',
                'max:1024',
                Rule::requiredIf(function () {
                    return in_array($this->input('message_type'), ['image', 'video']);
                }),
            ],
            'scheduled_at' => [
                'nullable',
                'date',
                'after:now',
                'before:' . now()->addYear(),
            ],
            'recipient_ids' => [
                'required',
                'array',
                'min:1',
                'max:10000', // Reasonable limit
            ],
            'recipient_ids.*' => [
                'required',
                'integer',
                'exists:contacts,id',
            ],
            'session_id' => [
                'required',
                'string',
                'max:255',
            ],
            'media' => [
                'nullable',
                'file',
                Rule::when(
                    $this->input('message_type') === 'text',
                    ['sometimes'],
                    ['required']
                ),
                Rule::when(
                    $this->input('message_type') === 'image',
                    ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'] // 5MB for images
                ),
                Rule::when(
                    $this->input('message_type') === 'video',
                    ['mimes:mp4,avi,mov,wmv', 'max:16384'] // 16MB for videos
                ),
                Rule::when(
                    $this->input('message_type') === 'audio',
                    ['mimes:mp3,ogg,wav,m4a,aac', 'max:16384'] // 16MB for audio
                ),
                Rule::when(
                    $this->input('message_type') === 'document',
                    ['mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt', 'max:10240'] // 10MB for documents
                ),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate template ownership if template is selected
            if ($this->filled('template_id')) {
                $template = Template::where('id', $this->input('template_id'))
                    ->where('user_id', $this->user()->id)
                    ->first();

                if (!$template) {
                    $validator->errors()->add(
                        'template_id',
                        'The selected template does not belong to you or does not exist.'
                    );
                }
            }

            // Validate contacts ownership
            if ($this->filled('recipient_ids')) {
                $recipientIds = $this->input('recipient_ids');
                $validContactsCount = Contact::where('user_id', $this->user()->id)
                    ->whereIn('id', $recipientIds)
                    ->count();

                if ($validContactsCount !== count($recipientIds)) {
                    $validator->errors()->add(
                        'recipient_ids',
                        'Some selected contacts do not belong to you or do not exist.'
                    );
                }

                // Validate at least one contact has valid WhatsApp
                $validWhatsAppCount = Contact::where('user_id', $this->user()->id)
                    ->whereIn('id', $recipientIds)
                    ->where('is_whatsapp_valid', true)
                    ->count();

                if ($validWhatsAppCount === 0) {
                    $validator->errors()->add(
                        'recipient_ids',
                        'None of the selected contacts have valid WhatsApp numbers. Please validate contacts first.'
                    );
                }
            }

            // Validate quota
            if ($this->filled('recipient_ids')) {
                $usageTracking = app(UsageTrackingService::class);
                $recipientCount = count($this->input('recipient_ids'));
                $remaining = $usageTracking->getRemainingQuota($this->user(), 'messages_per_month');

                if ($remaining !== 'unlimited' && $recipientCount > $remaining) {
                    $validator->errors()->add(
                        'recipient_ids',
                        "This campaign requires {$recipientCount} messages but you only have {$remaining} remaining in your quota. Please upgrade your plan or reduce recipients."
                    );
                }
            }

            // Validate media is provided for non-text messages if no template
            if (!$this->filled('template_id') && $this->input('message_type') !== 'text' && !$this->hasFile('media')) {
                $validator->errors()->add(
                    'media',
                    'Media file is required for ' . $this->input('message_type') . ' messages when not using a template.'
                );
            }

            // Validate scheduled time is at least 5 minutes in the future
            if ($this->filled('scheduled_at')) {
                $scheduledTime = \Carbon\Carbon::parse($this->input('scheduled_at'));
                if ($scheduledTime->lt(now()->addMinutes(5))) {
                    $validator->errors()->add(
                        'scheduled_at',
                        'Scheduled time must be at least 5 minutes in the future.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required.',
            'name.min' => 'Campaign name must be at least 3 characters.',
            'name.max' => 'Campaign name cannot exceed 255 characters.',
            'message_content.required' => 'Message content is required.',
            'message_content.max' => 'Message content cannot exceed 4096 characters.',
            'message_type.required' => 'Message type is required.',
            'message_type.in' => 'Invalid message type selected.',
            'recipient_ids.required' => 'At least one recipient must be selected.',
            'recipient_ids.min' => 'At least one recipient must be selected.',
            'recipient_ids.max' => 'Cannot send to more than 10,000 recipients at once.',
            'recipient_ids.*.exists' => 'One or more selected contacts do not exist.',
            'session_id.required' => 'WhatsApp session is required.',
            'media.required' => 'Media file is required for this message type.',
            'media.max' => 'Media file size exceeds the maximum allowed size.',
            'media.image' => 'The file must be a valid image.',
            'media.mimes' => 'The file format is not supported for this message type.',
            'scheduled_at.after' => 'Scheduled time must be in the future.',
            'scheduled_at.before' => 'Scheduled time cannot be more than 1 year in the future.',
            'template_id.exists' => 'The selected template does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'campaign name',
            'message_type' => 'message type',
            'message_content' => 'message content',
            'message_caption' => 'caption',
            'recipient_ids' => 'recipients',
            'session_id' => 'WhatsApp session',
            'scheduled_at' => 'scheduled time',
            'template_id' => 'template',
        ];
    }
}
