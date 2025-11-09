<script lang="ts" setup>
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Phone,
    QrCode,
    RefreshCw,
    Smartphone,
    Trash2,
    Wifi,
    WifiOff,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Session {
    id: string;
    session_name: string;
    status: string;
    phone_number?: string;
    jid?: string;
    push_name?: string;
    platform?: string;
    connected_at?: string;
    last_seen?: string;
    is_active: boolean;
    created_at: string;
}

interface Summary {
    total_sessions: number;
    connected: number;
    pending: number;
    max_devices: number;
    available_slots: number | string;
}

interface Props {
    sessions: Session[];
    summary: Summary;
    canConnect: boolean;
    remainingSlots: number | string;
    wsBaseUrl: string;
}

const props = defineProps<Props>();
const { t } = useTranslation();

const showCreateDialog = ref(false);
const sessionName = ref('');
const creating = ref(false);
const qrDialogOpen = ref(false);
const currentQRSession = ref<Session | null>(null);
const qrCode = ref('');
const qrLoading = ref(false);
const websockets = ref<Map<string, WebSocket>>(new Map());

const createSession = async () => {
    if (!sessionName.value.trim()) return;

    creating.value = true;

    try {
        const response = await fetch('/whatsapp/sessions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                session_name: sessionName.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showCreateDialog.value = false;
            sessionName.value = '';

            router.reload({ only: ['sessions', 'summary'] });

            setTimeout(() => {
                const newSession = props.sessions.find(
                    (s) => s.id === data.data.session_id,
                );
                if (newSession) {
                    showQRCode(newSession);
                }
            }, 500);
        }
    } catch (error) {
        console.error('Failed to create session:', error);
    } finally {
        creating.value = false;
    }
};

const showQRCode = async (session: Session) => {
    currentQRSession.value = session;
    qrDialogOpen.value = true;
    qrLoading.value = true;

    try {
        const response = await fetch(`/whatsapp/sessions/${session.id}/qr`);
        const data = await response.json();

        if (data.success) {
            qrCode.value = data.data.qr_code;
        }
    } catch (error) {
        console.error('Failed to get QR code:', error);
    } finally {
        qrLoading.value = false;
    }

    connectWebSocket(session.id);
};

const connectWebSocket = async (sessionId: string) => {
    try {
        const response = await fetch(`/whatsapp/sessions/${sessionId}/ws-url`);
        const data = await response.json();

        if (data.success) {
            const ws = new WebSocket(data.data.ws_url);

            ws.onopen = () => {
                console.log('WebSocket connected for session:', sessionId);
            };

            ws.onmessage = (event) => {
                const message = JSON.parse(event.data);
                handleWebSocketMessage(sessionId, message);
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            ws.onclose = () => {
                console.log('WebSocket closed for session:', sessionId);
                websockets.value.delete(sessionId);
            };

            websockets.value.set(sessionId, ws);
        }
    } catch (error) {
        console.error('Failed to connect WebSocket:', error);
    }
};

const handleWebSocketMessage = (sessionId: string, message: any) => {
    console.log('WebSocket message:', message);

    if (message.type === 'connected') {
        router.reload({ only: ['sessions', 'summary'] });

        if (currentQRSession.value?.id === sessionId) {
            qrDialogOpen.value = false;
        }
    } else if (message.type === 'qr_ready') {
        qrCode.value = message.data.qr_code;
    } else if (message.type === 'disconnected') {
        router.reload({ only: ['sessions', 'summary'] });
    }
};

const refreshSession = async (sessionId: string) => {
    try {
        const response = await fetch(
            `/whatsapp/sessions/${sessionId}/refresh`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
            },
        );

        const data = await response.json();

        if (data.success) {
            router.reload({ only: ['sessions', 'summary'] });
        }
    } catch (error) {
        console.error('Failed to refresh session:', error);
    }
};

const deleteSession = async (sessionId: string) => {
    if (!confirm(t('whatsapp.confirm_delete'))) {
        return;
    }

    try {
        const response = await fetch(`/whatsapp/sessions/${sessionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (data.success) {
            router.reload({ only: ['sessions', 'summary'] });
        }
    } catch (error) {
        console.error('Failed to delete session:', error);
    }
};

const getStatusBadge = (status: string) => {
    const badges: Record<string, { variant: string; label: string }> = {
        connected: {
            variant: 'default',
            label: t('whatsapp.status.connected'),
        },
        pending: { variant: 'secondary', label: t('whatsapp.status.pending') },
        qr_ready: {
            variant: 'secondary',
            label: t('whatsapp.status.qr_ready'),
        },
        disconnected: {
            variant: 'destructive',
            label: t('whatsapp.status.disconnected'),
        },
        failed: { variant: 'destructive', label: t('whatsapp.status.failed') },
    };
    return badges[status] || { variant: 'secondary', label: status };
};

onUnmounted(() => {
    websockets.value.forEach((ws) => ws.close());
    websockets.value.clear();
});

onMounted(() => {
    props.sessions
        .filter((s) => s.status === 'pending' || s.status === 'qr_ready')
        .forEach((s) => connectWebSocket(s.id));
});
</script>

<template>
    <Head :title="t('whatsapp.title')" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('whatsapp.title') }}
                </h1>
                <p class="text-muted-foreground">
                    {{ t('whatsapp.subtitle') }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            {{ t('whatsapp.summary.total') }}
                        </CardTitle>
                        <Smartphone class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ summary.total_sessions }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            {{ t('whatsapp.summary.connected') }}
                        </CardTitle>
                        <Wifi class="size-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ summary.connected }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            {{ t('whatsapp.summary.pending') }}
                        </CardTitle>
                        <WifiOff class="size-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">
                            {{ summary.pending }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            {{ t('whatsapp.summary.available') }}
                        </CardTitle>
                        <Phone class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ summary.available_slots }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('whatsapp.summary.max') }}
                            {{ summary.max_devices }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Alert v-if="!canConnect" variant="destructive">
                <AlertCircle class="size-4" />
                <AlertDescription>
                    {{ t('whatsapp.limit_reached') }}
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle
                                >{{ t('whatsapp.sessions.title') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('whatsapp.sessions.description') }}
                            </CardDescription>
                        </div>
                        <Button
                            :disabled="!canConnect"
                            class="bg-[#25D366] hover:bg-[#128C7E]"
                            @click="showCreateDialog = true"
                        >
                            <Phone class="mr-2 size-4" />
                            {{ t('whatsapp.connect_new') }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="sessions.length === 0" class="py-12 text-center">
                        <Smartphone
                            class="mx-auto size-12 text-muted-foreground"
                        />
                        <h3 class="mt-4 text-lg font-medium">
                            {{ t('whatsapp.no_sessions') }}
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ t('whatsapp.get_started') }}
                        </p>
                        <Button
                            class="mt-4 bg-[#25D366] hover:bg-[#128C7E]"
                            @click="showCreateDialog = true"
                        >
                            {{ t('whatsapp.connect_first') }}
                        </Button>
                    </div>

                    <div v-else class="space-y-4">
                        <Card
                            v-for="session in sessions"
                            :key="session.id"
                            class="transition-shadow hover:shadow-md"
                        >
                            <CardContent class="p-6">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div class="flex-1 space-y-2">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex size-10 items-center justify-center rounded-full bg-[#25D366]/10"
                                            >
                                                <Phone
                                                    class="size-5 text-[#25D366]"
                                                />
                                            </div>
                                            <div>
                                                <h3 class="font-semibold">
                                                    {{ session.session_name }}
                                                </h3>
                                                <p
                                                    v-if="session.phone_number"
                                                    class="text-sm text-muted-foreground"
                                                >
                                                    {{ session.phone_number }}
                                                </p>
                                            </div>
                                            <Badge
                                                :variant="
                                                    getStatusBadge(
                                                        session.status,
                                                    ).variant as any
                                                "
                                            >
                                                {{
                                                    getStatusBadge(
                                                        session.status,
                                                    ).label
                                                }}
                                            </Badge>
                                        </div>
                                        <div
                                            v-if="session.connected_at"
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ t('whatsapp.connected_since') }}:
                                            {{
                                                new Date(
                                                    session.connected_at,
                                                ).toLocaleString()
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            v-if="
                                                session.status === 'pending' ||
                                                session.status === 'qr_ready'
                                            "
                                            size="sm"
                                            variant="outline"
                                            @click="showQRCode(session)"
                                        >
                                            <QrCode class="size-4" />
                                        </Button>

                                        <Button
                                            v-if="
                                                session.status ===
                                                'disconnected'
                                            "
                                            size="sm"
                                            variant="outline"
                                            @click="refreshSession(session.id)"
                                        >
                                            <RefreshCw class="size-4" />
                                        </Button>

                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="deleteSession(session.id)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showCreateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t('whatsapp.create.title') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('whatsapp.create.description') }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="session-name">
                            {{ t('whatsapp.create.name') }}
                        </Label>
                        <Input
                            id="session-name"
                            v-model="sessionName"
                            :placeholder="t('whatsapp.create.placeholder')"
                        />
                    </div>
                    <Alert>
                        <AlertCircle class="size-4" />
                        <AlertDescription>
                            {{ t('whatsapp.business_recommendation') }}
                        </AlertDescription>
                    </Alert>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCreateDialog = false">
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        :disabled="!sessionName.trim() || creating"
                        class="bg-[#25D366] hover:bg-[#128C7E]"
                        @click="createSession"
                    >
                        {{
                            creating ? t('common.creating') : t('common.create')
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="qrDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('whatsapp.qr.title') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('whatsapp.qr.description') }}
                    </DialogDescription>
                </DialogHeader>
                <div class="flex flex-col items-center gap-4 py-6">
                    <div
                        v-if="qrLoading"
                        class="flex size-64 items-center justify-center rounded-lg border border-border"
                    >
                        <div
                            class="size-8 animate-spin rounded-full border-4 border-[#25D366] border-t-transparent"
                        />
                    </div>

                    <img
                        v-else-if="qrCode"
                        :src="qrCode"
                        alt="QR Code"
                        class="size-64 rounded-lg border border-border"
                    />

                    <div
                        class="space-y-2 text-center text-sm text-muted-foreground"
                    >
                        <p>1. {{ t('whatsapp.qr.step1') }}</p>
                        <p>2. {{ t('whatsapp.qr.step2') }}</p>
                        <p>3. {{ t('whatsapp.qr.step3') }}</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="qrDialogOpen = false">
                        {{ t('common.close') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
