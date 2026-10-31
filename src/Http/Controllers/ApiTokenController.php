<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Actions\CreateApiToken;
use Larasell\Admin\Data\View\ApiTokensPageData;
use Larasell\Admin\Data\View\CreateApiTokenPageData;
use Larasell\Admin\Data\ViewModel\CreatedApiTokenData;
use Larasell\Admin\Enums\ApiTokenExpiry;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Panel\ApiTokens;

/**
 * @internal The settings pages for API tokens.
 */
class ApiTokenController
{
    public function index(): Response
    {
        return Inertia::render('ApiToken/Index', new ApiTokensPageData(
            createUrl: route('larasell.admin.settings.api-tokens.create'),
            apiTokens: ApiTokens::make(),
        ));
    }

    public function create(): Response
    {
        return Inertia::render('ApiToken/Create', new CreateApiTokenPageData(
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
