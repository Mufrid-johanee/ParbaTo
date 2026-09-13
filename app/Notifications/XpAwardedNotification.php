<?php

namespace App\Notifications;

use App\Models\XpLedger;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class XpAwardedNotification extends Notification
{
    use Queueable;

    public function __construct(public XpLedger $ledger) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => '+'.$this->ledger->amount.' XP',
            'body' => $this->ledger->description,
            'link' => route('student.profile'),
            'type' => 'xp_awarded',
            'related_id' => $this->ledger->id,
            'ledger_id' => $this->ledger->id,
            'amount' => $this->ledger->amount,
        ];
    }
}
