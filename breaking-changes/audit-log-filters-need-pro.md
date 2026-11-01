# Filtering the audit log needs Larasell Pro in production

The audit log's filters are now a Pro feature, the `admin.audit-log-search`
entitlement. On the Free plan, the page lists every record but shows a notice
instead of the filters, and filtered addresses answer 403.

## Who is affected

Shops in production without an activated Pro license. Local, staging and test
environments read the plan from `LARASELL_PLAN`, which defaults to `pro`, so
they keep the filters. The `larasell.admin.search-audit-log` gate still decides
which admins may filter; the entitlement applies on top of it.

## How to upgrade

Activate the shop's Pro license key from your larasell.dev dashboard on the
production server:

```sh
php artisan larasell:license:activate LL-XXXXXXXX-XXXXXXXXXXXXXXXXXXXXXXXX
```

Make sure the scheduler runs, so `larasell:license:renew` keeps the lease
fresh. To check what a Free shop sees locally, set `LARASELL_PLAN=free`.
