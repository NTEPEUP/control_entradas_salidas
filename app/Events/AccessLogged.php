<?php
namespace App\Events;

use App\Models\AccessLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class AccessLogged implements ShouldBroadcast
{
      use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;
    public function __construct(AccessLog $log) { $this->log = $log; }

    public function broadcastOn() { return new Channel('dashboard'); }
    public function broadcastAs() { return 'access.new'; }

  public function broadcastWith()
  {
    $total = AccessLog::count();
    $granted = AccessLog::where('status', 'granted')->count();
    $denied = AccessLog::where('status', 'denied')->count();
    $rate = $total > 0 ? round(($granted / $total) * 100, 1) : 0;

    return [
      'owner_name' => $this->log->owner_name,
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
