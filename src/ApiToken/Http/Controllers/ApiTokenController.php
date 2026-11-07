<?php

namespace Larasell\Admin\ApiToken\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\ApiToken\Actions\CreateApiToken;
use Larasell\Admin\ApiToken\Data\ApiTokensPageData;
use Larasell\Admin\ApiToken\Data\CreateApiTokenPageData;
use Larasell\Admin\ApiToken\Data\CreatedApiTokenData;
use Larasell\Admin\ApiToken\Enums\ApiTokenExpiry;
use Larasell\Admin\ApiToken\Tables\ApiTokens;
use Larasell\Admin\Platform\Auth\Models\Admin;

/**
 * @internal The settings pages for API tokens.
 */
class ApiTokenController
{
    public function index(): Response
    {
        return Inertia::render('ApiToken/Pages/Index', new ApiTokensPageData(
            createUrl: route('larasell.admin.settings.api-tokens.create'),
            apiTokens: ApiTokens::make(),
        ));
    }

    public function create(): Response
    {
        return Inertia::render('ApiToken/Pages/Create', new CreateApiTokenPageData(
            storeUrl: route('larasell.admin.settings.api-tokens.store'),
            indexUrl: route('larasell.admin.settings.api-tokens.index'),
            expiryOptions: array_map(fn (ApiTokenExpiry $expiry): array => ['value' => $expiry->value, 'label' => $expiry->label()], ApiTokenExpiry::cases()),
            defaultExpiresIn: ApiTokenExpiry::ThreeMonths->value,
        ));
    }

    public function store(Request $request, CreateApiToken $createApiToken): RedirectResponse
    {
        $admin = $request->user('larasell-admin');

        [$apiToken, $plainTextToken] = $createApiToken->handle(
            $request->only(['name', 'description', 'expires_in']),
            $admin instanceof Admin ? $admin : null,
        );

        // Flash data, unlike props, stays out of the browser history, so the token shows once.
        Inertia::flash('apiToken', (new CreatedApiTokenData($apiToken->name, $plainTextToken))->toArray());

        return redirect()->route('larasell.admin.settings.api-tokens.index');
    }
}
