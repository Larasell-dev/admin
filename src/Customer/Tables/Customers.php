<?php

namespace Larasell\Admin\Customer\Tables;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Columns\BadgeColumn;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Enums\Variant;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Larasell\Customers\Actions\ChangeCustomerStatus;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Enums\CustomerStatus;
use Larasell\Larasell\Customers\Enums\CustomerType;
use Larasell\Larasell\Customers\Models\Customer;

class Customers extends Table
{
    /**
     * @return Builder<Customer>
     */
    protected function model(): Builder
    {
        return Customer::query()->with(['personProfile', 'companyProfile'])->latest('id');
    }

    public function title(): string
    {
        return __('Customers');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('customer_number', __('Number')),
            TextColumn::make('name', __('Name'))->map(fn (mixed $value, Customer $customer): string => $customer->displayName()),
            TextColumn::make('email', __('Email'))->map(fn (mixed $value, Customer $customer): ?string => $customer->personProfile?->email?->value()),
            TextColumn::make('type', __('Type'))->map(fn (CustomerType $type): string => self::typeLabel($type)),
            BadgeColumn::make('status', __('Status'))
                ->map(fn (CustomerStatus $status): string => self::statusLabel($status))
                ->variant([CustomerStatus::Active->value => Variant::Success, CustomerStatus::Blocked->value => Variant::Danger]),
        ];
    }

    public static function typeLabel(CustomerType $type): string
    {
        return match ($type) {
            CustomerType::Individual => __('Individual'),
            CustomerType::Company => __('Company'),
        };
    }

    public static function statusLabel(CustomerStatus $status): string
    {
        return match ($status) {
            CustomerStatus::Active => __('Active'),
            CustomerStatus::Blocked => __('Blocked'),
            CustomerStatus::Archived => __('Archived'),
        };
    }

    public function rowLink(Customer $customer): string
    {
        return route('larasell.admin.customers.show', $customer);
    }

    public function actions(): array
    {
        $status = fn (CustomerStatus $to) => fn (Customer $customer) => app(ChangeCustomerStatus::class)->handle(CustomerId::of($customer), $to);
        $is = fn (CustomerStatus $status) => fn (Customer $customer): bool => $customer->status === $status;
        $isNot = fn (CustomerStatus $status) => fn (Customer $customer): bool => $customer->status !== $status;

        return [
            Action::make(Translate::later('Block'), handle: $status(CustomerStatus::Blocked))
                ->hidden($isNot(CustomerStatus::Active))
                ->confirm(title: __('Block customer'), message: __('The customer keeps their history but can\'t get new accounts until unblocked.'))
                ->icon(Icon::Forbid)
                ->destructive(),
            Action::make(Translate::later('Unblock'), handle: $status(CustomerStatus::Active))
                ->hidden($isNot(CustomerStatus::Blocked))
                ->icon(Icon::CheckboxCircle),
            Action::make(Translate::later('Deactivate'), handle: $status(CustomerStatus::Archived))
                ->hidden($is(CustomerStatus::Archived))
                ->confirm(title: __('Deactivate customer'), message: __('The customer is archived. Their history stays, and you can reactivate them later.'))
                ->icon(Icon::InboxArchive),
            Action::make(Translate::later('Reactivate'), handle: $status(CustomerStatus::Active))
                ->hidden($isNot(CustomerStatus::Archived))
                ->icon(Icon::InboxUnarchive),
        ];
    }

    public function headerActions(): array
    {
        return [
            TableAction::make(Translate::later('Add customer'))->route('larasell.admin.customers.create')->icon(Icon::Add),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No customers yet'),
            'description' => __('Customers appear here once they buy or you add them.'),
            'icon' => Icon::User3,
            'actions' => $this->headerActions(),
        ];
    }
}
