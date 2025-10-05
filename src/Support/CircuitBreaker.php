<?php

namespace AbdelAzizHassan\Authentica\Support;

class CircuitBreaker
{
    public const STATE_CLOSED    = 'closed';
    public const STATE_OPEN      = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        protected CircuitStore $store,
        protected string $nsKey,
        protected int $failureThreshold,
        protected int $openSeconds,
        protected int $halfOpenSuccessesToClose
    ) {}

    protected function k(string $suffix): string
    {
        return "authentica:circuit:{$this->nsKey}:{$suffix}";
    }

    public function state(): string
    {
        return (string) $this->store->get($this->k('state'), self::STATE_CLOSED);
    }

    /** Should we allow an outbound call now? */
    public function allow(): bool
    {
        $state = $this->state();

        if ($state === self::STATE_CLOSED) return true;

        if ($state === self::STATE_OPEN) {
            $openedAt = (int) $this->store->get($this->k('opened_at'), 0);
            if (time() - $openedAt >= $this->openSeconds) {
                $this->toHalfOpen();
                return true; // allow probe
            }
            return false; // still open
        }

        // HALF_OPEN -> allow probes
        return true;
    }

    public function recordSuccess(): void
    {
        $state = $this->state();

        if ($state === self::STATE_HALF_OPEN) {
            $succ = (int) $this->store->increment($this->k('half_open_succ'));
            if ($succ >= $this->halfOpenSuccessesToClose) {
                $this->toClosed();
            }
            return;
        }

        // In closed, keep counters clean
        $this->resetCounters();
    }

    public function recordFailure(): void
    {
        $state = $this->state();

        if ($state === self::STATE_HALF_OPEN) {
            // any failure in half-open -> open again
            $this->toOpen();
            return;
        }

        $fails = (int) $this->store->increment($this->k('fails'));
        if ($fails >= $this->failureThreshold) {
            $this->toOpen();
        }
    }

    public function forceOpen(?int $seconds = null): void
    {
        $this->toOpen($seconds);
    }

    public function reset(): void
    {
        $this->toClosed();
    }

    /** Factory from config array */
    public static function makeFromConfig(CircuitStore $store, array $cfg): self
    {
        return new self(
            $store,
            (string) ($cfg['key'] ?? 'authentica_v2'),
            (int) ($cfg['failure_threshold'] ?? 5),
            (int) ($cfg['open_seconds'] ?? 60),
            (int) ($cfg['half_open_successes_to_close'] ?? 2),
        );
    }

    // --- internal state transitions ---

    protected function toClosed(): void
    {
        $this->store->put($this->k('state'), self::STATE_CLOSED);
        $this->resetCounters();
    }

    protected function toOpen(?int $seconds = null): void
    {
        $this->store->put($this->k('state'), self::STATE_OPEN);
        $this->store->put($this->k('opened_at'), time());
        $this->resetCounters();
        if ($seconds && $seconds > 0) {
            $this->store->put($this->k('force_open_until'), time() + $seconds, $seconds);
        } else {
            $this->store->forget($this->k('force_open_until'));
        }
    }

    protected function toHalfOpen(): void
    {
        $this->store->put($this->k('state'), self::STATE_HALF_OPEN);
        $this->store->put($this->k('half_open_succ'), 0);
        $this->store->put($this->k('fails'), 0);
    }

    protected function resetCounters(): void
    {
        $this->store->forget($this->k('fails'));
        $this->store->forget($this->k('half_open_succ'));
    }
}
