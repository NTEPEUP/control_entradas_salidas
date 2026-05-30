<?php
namespace App\Events;

use App\Models\AccessLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class AccessLogged implements ShouldBroadcastNow
{
      use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;
    public function __construct(AccessLog $log) { $this->log = $log; }

    public function broadcastOn()
    {
        return new Channel('dashboard');
    }

    public function broadcastAs()
    {
        return 'access.new';
    }

  public function broadcastWith()
  {
    $total = AccessLog::count();
    $granted = AccessLog::where('status', 'granted')->count();
    $denied = AccessLog::where('status', 'denied')->count();
    $rate = $total > 0 ? round(($granted / $total) * 100, 1) : 0;

    $statusLabels = [
      'granted' => 'Acceso concedido',
      'denied' => 'Acceso denegado',
      'error' => 'Error de validación',
      'timeout' => 'Tiempo agotado',
    ];

    $message = $statusLabels[$this->log->status] ?? 'Nuevo acceso registrado';
    $message .= $this->log->pin ? ' - PIN ' . $this->log->pin : '';
    $message .= $this->log->owner_name ? ' (' . $this->log->owner_name . ')' : '';

    return [
      'status' => $this->log->status,
      'pin' => $this->log->pin,
      'owner_name' => $this->log->owner_name,
      'message' => $message,
      'log' => $this->log->toArray(),
      'stats' => [
        'total' => $total,
        'granted' => $granted,
        'denied' => $denied,
        'rate' => $rate,
      ],
    ];
  }
}
