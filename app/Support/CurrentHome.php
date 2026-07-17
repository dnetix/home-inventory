<?php

namespace App\Support;

use App\Exceptions\MissingCurrentHomeException;
use App\Models\Home;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the home all queries are scoped to. Registered as a scoped
 * container binding so the override never leaks across Octane requests.
 */
class CurrentHome
{
    private ?Home $override = null;

    public function get(): ?Home
    {
        return $this->override ?? Auth::user()?->currentHome;
    }

    public function id(): ?int
    {
        return $this->get()?->id;
    }

    public function idOrFail(): int
    {
        return $this->id() ?? throw new MissingCurrentHomeException;
    }

    /**
     * Force the current home where no authenticated user exists (seeders, jobs, tests).
     */
    public function override(?Home $home): void
    {
        $this->override = $home;
    }
}
