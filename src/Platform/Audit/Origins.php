<?php

namespace Larasell\Admin\Platform\Audit;

use Illuminate\Support\Facades\Auth;
use Larasell\Admin\Platform\Audit\Exceptions\MissingOrigin;
use Larasell\Larasell\Audit\Actors\Actor;
use Larasell\Larasell\Audit\Actors\UnknownActor;
use Larasell\Larasell\Audit\Origin;

/**
 * Who makes the changes of the current request, for the core's audited actions: the API token
 * on API requests, the signed-in admin on the panel's pages.
 *
 * @internal Inject the current Origin with #[CurrentOrigin]; the guard is registered by the
 * module that authenticates API requests. Listeners of core events, which also fire outside a
 * request, use currentOr().
 */
class Origins
{
    private ?string $apiGuard = null;

    public function apiGuard(string $guard): void
    {
        $this->apiGuard = $guard;
    }

    public function current(): Origin
    {
        return $this->find() ?? throw MissingOrigin::noOneSignedIn();
    }

    /**
     * The current request's origin, or else an unknown actor with the reason, for a change made
     * outside a request, by a job or command.
     */
    public function currentOr(string $reason): Origin
    {
        return $this->find() ?? Origin::cli(new UnknownActor($reason));
    }

    private function find(): ?Origin
    {
        $token = $this->apiGuard === null ? null : Auth::guard($this->apiGuard)->user();

        if ($token instanceof Actor) {
            return Origin::api($token);
        }

        $admin = Auth::guard('larasell-admin')->user();

        if ($admin instanceof Actor) {
            return Origin::web($admin);
        }

        return null;
    }
}
