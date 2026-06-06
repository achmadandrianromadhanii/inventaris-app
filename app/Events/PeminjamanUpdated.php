<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PeminjamanUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $aksi,
        public string $pesan,
        public ?array $data = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('peminjaman'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'peminjaman.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'aksi' => $this->aksi,
            'pesan' => $this->pesan,
            'data' => $this->data,
            'waktu' => now()->format('H:i:s'),
        ];
    }
}
