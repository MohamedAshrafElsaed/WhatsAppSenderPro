export type TemplateType =
    | 'text'
    | 'text_image'
    | 'text_video'
    | 'text_document';

export interface Template {
    id: number;
    user_id: number;
    name: string;
    type: TemplateType;
    content: string;
    caption: string | null;
    media_path: string | null;
    media_url: string | null;
    last_used_at: string | null;
    usage_count: number;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    // Computed attributes
    content_preview?: string;
    has_media?: boolean;
}

export interface Placeholder {
    value: string;
    label: string;
    description: string;
}

export interface TemplateFilters {
    search?: string;
    type?: TemplateType | '';
    sort_by?: 'created_at' | 'name' | 'usage' | 'last_used';
    sort_order?: 'asc' | 'desc';
}

export interface TemplateFormData {
    name: string;
    type: TemplateType;
    content: string;
    caption?: string;
    media?: File | null;
}
