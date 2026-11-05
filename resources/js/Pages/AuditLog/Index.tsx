import { Link, router, usePage } from "@inertiajs/react";
import { Button, EmptyState, Field, Input } from "@larasell-dev/ui";
import { FileShieldLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { FormEvent, ReactNode } from "react";
import { Page } from "../../Components/Page";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Platform.Audit.Log.Data.AuditLogPageData;
type Filters = Larasell.Admin.Platform.Audit.Log.Data.AuditLogFiltersData;

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

  return (
    <>
      <Page.Header title={auditLog.title} />
      {canSearch && (
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
            title={
              filtered
                ? fbs(
                    "No records match these filters",
                    "Audit log page: shown instead of the list of recorded changes when the filters exclude every record",
                  )
                : (auditLog.emptyState?.title ?? "")
            }
            description={filtered ? undefined : (auditLog.emptyState?.description ?? undefined)}
          >
            {filtered && (
              <Button render={<Link href={indexUrl} />}>
                <fbt desc="Audit log page: button below 'No records match these filters' that removes all filters">
                  Clear filters
                </fbt>
              </Button>
            )}
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
      aria-label={fbs(
        "Filter the audit log",
        "Audit log page: accessible name of the bar of fields that filter the recorded changes",
      )}
      className="flex shrink-0 flex-wrap items-start gap-3 border-b border-border px-6 py-4"
    >
      <Filter>
        <Field
          label={fbs(
            "Event",
            "Audit log filters: label of the select for the kind of recorded change, e.g. created or deleted",
          )}
          name="event"
          error={errors.event}
        >
          <Select
            options={eventOptions}
            defaultValue={filters.event}
            all={fbs(
              "All events",
              "Audit log filters: option of the event select that doesn't filter by the kind of change",
            )}
          />
        </Field>
      </Filter>
      <Filter>
        <Field
          label={fbs(
            "Actor",
            "Audit log filters: label of the select for the kind of who made the change, e.g. an admin or an API token",
          )}
          name="actor_type"
          error={errors.actor_type}
        >
          <Select
            options={actorTypeOptions}
            defaultValue={filters.actor_type}
            all={fbs(
              "Anyone",
              "Audit log filters: option of the actor select that doesn't filter by who made the change",
            )}
          />
        </Field>
      </Filter>
      <Filter narrow>
        <Field
          label={fbs(
            "Actor ID",
            "Audit log filters: label of the field for the ID of who made the change",
          )}
          name="actor_id"
          error={errors.actor_id}
        >
          <Input defaultValue={filters.actor_id ?? ""} autoComplete="off" />
        </Field>
      </Filter>
      <Filter>
        <Field
          label={fbs(
            "Subject",
            "Audit log filters: label of the select for the kind of record that was changed, e.g. a product or an order",
          )}
          name="subject_type"
          error={errors.subject_type}
        >
          <Select
            options={subjectTypeOptions}
            defaultValue={filters.subject_type}
            all={fbs(
              "Anything",
              "Audit log filters: option of the subject select that doesn't filter by the kind of record changed",
            )}
          />
        </Field>
      </Filter>
      <Filter narrow>
        <Field
          label={fbs(
            "Subject ID",
            "Audit log filters: label of the field for the ID of the record that was changed",
          )}
          name="subject_id"
          error={errors.subject_id}
        >
          <Input defaultValue={filters.subject_id ?? ""} autoComplete="off" />
        </Field>
      </Filter>
      <Filter>
        <Field
          label={fbs(
            "From",
            "Audit log filters: label of the date field for the earliest day of changes to show",
          )}
          name="from"
          error={errors.from}
        >
          <Input type="date" defaultValue={filters.from ?? ""} />
        </Field>
      </Filter>
      <Filter>
        <Field
          label={fbs(
            "Until",
            "Audit log filters: label of the date field for the latest day of changes to show",
          )}
          name="until"
          error={errors.until}
        >
          <Input type="date" defaultValue={filters.until ?? ""} />
        </Field>
      </Filter>
      {/* Lined up with the inputs, below their labels. */}
      <div className="flex gap-2 pt-6">
        <Button type="submit" variant="primary">
          <fbt desc="Audit log filters: button that applies the filters to the list of recorded changes">
            Filter
          </fbt>
        </Button>
        {filtered && (
          <Button variant="secondary" render={<Link href={indexUrl} />}>
            <fbt desc="Audit log filters: button that removes all filters from the list of recorded changes">
              Clear
            </fbt>
          </Button>
        )}
      </div>
    </form>
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
