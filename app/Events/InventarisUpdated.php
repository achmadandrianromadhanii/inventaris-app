<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event ini di-trigger (dipicu) ketika ada aktivitas penambahan/pengurangan
 * inventaris (seperti Barang Masuk atau Barang Keluar).
 *
 * Menggunakan "ShouldBroadcastNow" (bukan ShouldBroadcast) sangat PENTING agar
 * event langsung disiarkan secara real-time via WebSocket (Reverb) ke seluruh client
 * tanpa harus menunggu diproses oleh antrean (queue worker). Ini memastikan badge
 * dan notifikasi muncul seketika (instan) tanpa jeda.
 */
class InventarisUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tipe,
        public string $pesan,
        public ?array $data = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('inventaris'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inventaris.updated';
    }
}
