import { usePage } from '@inertiajs/vue3';

const API_BASE_URL =
    import.meta.env.VITE_WHATSAPP_API_URL || 'http://localhost:8988';

/**
 * Get JWT token from page props
 */
function getJWTToken(): string {
    const page = usePage();
    return (page.props.auth as any)?.jwt_token || '';
}

/**
 * Make authenticated request to WhatsApp API
 *
 * NOTE: This is for future use. Currently, all API calls should be made
 * from Laravel backend using WhatsAppApiService.
 */
async function makeRequest(method: string, endpoint: string, data?: any) {
    const token = getJWTToken();

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        method,
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: data ? JSON.stringify(data) : undefined,
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'API request failed');
    }

    return response.json();
}

/**
 * WhatsApp API methods
 *
 * IMPORTANT: These methods are for future use.
 * Currently, all WhatsApp API calls should be made from Laravel backend.
 */
export const whatsappApi = {
    // Create session
    createSession: (sessionName: string) =>
        makeRequest('POST', '/api/sessions', { session_name: sessionName }),

    // Get all sessions
    getSessions: () => makeRequest('GET', '/api/sessions'),

    // Get session QR code
    getSessionQR: (sessionId: string) =>
        makeRequest('GET', `/api/sessions/${sessionId}/qr`),

    // Get session status
    getSessionStatus: (sessionId: string) =>
        makeRequest('GET', `/api/sessions/${sessionId}/status`),

    // Delete session
    deleteSession: (sessionId: string) =>
        makeRequest('DELETE', `/api/sessions/${sessionId}`),

    // Send message
    sendMessage: (sessionId: string, to: string, message: string) =>
        makeRequest('POST', `/api/sessions/${sessionId}/send`, { to, message }),

    // Validate phone number
    validateAccount: (phoneNumber: string) =>
        makeRequest('POST', '/api/validate', { phone_number: phoneNumber }),

    // Refresh session
    refreshSession: (sessionId: string) =>
        makeRequest('POST', `/api/sessions/${sessionId}/refresh`),
};
