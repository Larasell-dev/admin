import { Link, router, usePage } from "@inertiajs/react";
import { Badge, Button, EmptyState, Field, Icon, Input } from "@larasell-dev/ui";
import { FileShieldLine, LockLine } from "@larasell-dev/ui/icons";
import type { FormEvent, ReactNode } from "react";
import { Page } from "../../Components/Page";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";
import type { Page as SharedPage } from "../../types";

type Props = Larasell.Admin.Data.View.AuditLog.AuditLogPageData;
type Filters = Larasell.Admin.Data.View.AuditLog.AuditLogFiltersData;

export default function Index({
  indexUrl,
  auditLog,
  filters,
  eventOptions,
  actorTypeOptions,
  subjectTypeOptions,
  canSearch,
}: Props) {
  const filtered = Object.values(filters).some((value) => value !== null);
  const entitled = usePage<SharedPage>().props.entitlements["admin.audit-log-search"] === true;

  return (
    <>
      <Page.Header title={auditLog.title} />
      {canSearch && !entitled && <ProNotice />}
      {canSearch && entitled && (
        <FilterBar
          // A new form for new filters, e.g. after clearing them.
          key={JSON.stringify(filters)}
          indexUrl={indexUrl}
          filters={filters}
          filtered={filtered}
          eventOptions={eventOptions}
          actorTypeOptions={actorTypeOptions}
          subjectTypeOptions={subjectTypeOptions}
        />
      )}
      {auditLog.pagination.total > 0 ? (
        <ResourceTable resource={auditLog} />
      ) : (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={FileShieldLine}
            title={filtered ? "No records match these filters" : (auditLog.emptyState?.title ?? "")}
            description={filtered ? undefined : (auditLog.emptyState?.description ?? undefined)}
          >
            {filtered && <Button render={<Link href={indexUrl} />}>Clear filters</Button>}
          </EmptyState>
        </div>
      )}
    </>
  );
}

type FilterBarProps = {
  indexUrl: string;
  filters: Filters;
  filtered: boolean;
  eventOptions: string[];
  actorTypeOptions: string[];
  subjectTypeOptions: string[];
};

function FilterBar({
  indexUrl,
  filters,
  filtered,
  eventOptions,
  actorTypeOptions,
  subjectTypeOptions,
}: FilterBarProps) {
  const errors = usePage().props.errors as Partial<Record<keyof Filters, string>>;

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    // Only the filters in use, so the address stays short and shareable.
    const query = Object.fromEntries(
      [...new FormData(event.currentTarget)].filter(([, value]) => value !== ""),
    );
    router.get(indexUrl, query);
  };

  return (
    <form
      onSubmit={submit}
      aria-label="Filter the audit log"
      className="flex shrink-0 flex-wrap items-start gap-3 border-b border-border px-6 py-4"
    >
      <Filter>
        <Field label="Event" name="event" error={errors.event}>
          <Select options={eventOptions} defaultValue={filters.event} all="All events" />
        </Field>
      </Filter>
      <Filter>
        <Field label="Actor" name="actor_type" error={errors.actor_type}>
          <Select options={actorTypeOptions} defaultValue={filters.actor_type} all="Anyone" />
        </Field>
      </Filter>
      <Filter narrow>
        <Field label="Actor ID" name="actor_id" error={errors.actor_id}>
          <Input defaultValue={filters.actor_id ?? ""} autoComplete="off" />
        </Field>
      </Filter>
      <Filter>
        <Field label="Subject" name="subject_type" error={errors.subject_type}>
          <Select options={subjectTypeOptions} defaultValue={filters.subject_type} all="Anything" />
        </Field>
      </Filter>
      <Filter narrow>
        <Field label="Subject ID" name="subject_id" error={errors.subject_id}>
          <Input defaultValue={filters.subject_id ?? ""} autoComplete="off" />
        </Field>
      </Filter>
      <Filter>
        <Field label="From" name="from" error={errors.from}>
          <Input type="date" defaultValue={filters.from ?? ""} />
        </Field>
      </Filter>
      <Filter>
        <Field label="Until" name="until" error={errors.until}>
          <Input type="date" defaultValue={filters.until ?? ""} />
        </Field>
      </Filter>
      {/* Lined up with the inputs, below their labels. */}
      <div className="flex gap-2 pt-6">
        <Button type="submit" variant="primary">
          Filter
        </Button>
        {filtered && (
          <Button variant="secondary" render={<Link href={indexUrl} />}>
            Clear
          </Button>
        )}
      </div>
    </form>
  );
}

function ProNotice() {
  return (
    <div className="flex shrink-0 items-center gap-2 border-b border-border px-6 py-4 type-body-md text-mauve-600 dark:text-mauve-400">
      <Icon icon={LockLine} size={4} />
      <span>Filtering the audit log by event, actor, subject and date is part of</span>
      <Badge>Pro</Badge>
    </div>
  );
}

function Filter({ narrow = false, children }: { narrow?: boolean; children: ReactNode }) {
  return <div className={narrow ? "w-24" : "w-40"}>{children}</div>;
}

/** The UI has no select yet; Input renders a native one with the field's label and styles. */
function Select({
  options,
  defaultValue,
  all,
}: {
  options: string[];
  defaultValue: string | null;
  all: string;
}) {
  return (
    <Input
      defaultValue={defaultValue ?? ""}
      render={
        <select>
          <option value="">{all}</option>
          {options.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      }
    />
  );
}

Index.layout = [SidebarLayout];
