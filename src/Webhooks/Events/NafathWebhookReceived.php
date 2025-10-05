<?php

namespace AbdelAzizHassan\Authentica\Webhooks\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NafathWebhookReceived
{
    use Dispatchable, SerializesModels;

    /** Normalized fields */
    public string $transactionId;
    public string $nationalId;
    public string $status;

    /** Full original payload (for advanced consumers) */
    public array $raw;

    public function __construct(string $transactionId, string $nationalId, string $status, array $raw)
    {
        $this->transactionId = $transactionId;
        $this->nationalId    = $nationalId;
        $this->status        = $status;
        $this->raw           = $raw;
    }
}
