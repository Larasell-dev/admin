import { useFormContext } from "@inertiajs/react";
import { Field, Input, Section, Select, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import CountrySelect from "./CountrySelect";

type Customer = Larasell.Admin.Customer.Data.CustomerDetailData;
type Option = Larasell.Admin.Customer.Data.CustomerOptionData;

/**
 * The profile fields of a customer, in a section. It must sit inside an Inertia `<Form>`. An
 * individual gets a person's fields, a company its company's; the type can't change later.
 */
export default function ProfileFields({
  type,
  customer,
  locales,
  currencies,
  countries,
}: {
  type: "individual" | "company";
  customer?: Customer;
  locales: Option[];
  currencies: Option[];
  countries: Option[];
}) {
  const errors = useFormContext()?.errors ?? {};
  const person = customer?.person;
  const company = customer?.company;

  return (
    <Section.Root>
      <Section.Header
        title={fbs(
          "Profile",
          "Customer form: heading of the section with the customer's name and details",
        )}
        description={
          type === "company"
            ? fbs(
                "The company's legal details. Only its name and country are required.",
                "Customer form: explains the profile section of a company customer",
              )
            : fbs(
                "Who the customer is. Only the name is required.",
                "Customer form: explains the profile section of an individual customer",
              )
        }
      />
      <Stack spacing={5}>
        <div className="@container">
          {type === "company" ? (
            <div className="grid items-start gap-5 @md:grid-cols-2">
              <Field
                label={fbs("Legal name", "Customer form: label of the company's legal name")}
                name="legal_name"
                error={errors.legal_name}
              >
                <Input defaultValue={company?.legalName} autoComplete="off" />
              </Field>
              <Field
                label={fbs(
                  "Legal form",
                  "Customer form: label of the company's legal form, e.g. GmbH",
                )}
                name="legal_form"
                error={errors.legal_form}
              >
                <Input defaultValue={company?.legalForm ?? ""} autoComplete="off" />
              </Field>
              <CountrySelect
                countries={countries}
                defaultValue={company?.countryCode}
                error={errors.country_code}
              />
              <Field
                label={fbs(
                  "VAT ID",
                  "Customer form: label of the company's VAT identification number",
                )}
                name="vat_id"
                error={errors.vat_id}
              >
                <Input defaultValue={company?.vatId ?? ""} autoComplete="off" />
              </Field>
              <Field
                label={fbs(
                  "Company number",
                  "Customer form: label of the company's registration number",
                )}
                name="company_number"
                error={errors.company_number}
              >
                <Input defaultValue={company?.companyNumber ?? ""} autoComplete="off" />
              </Field>
              <Field
                label={fbs("Industry", "Customer form: label of the company's industry")}
                name="industry"
                error={errors.industry}
              >
                <Input defaultValue={company?.industry ?? ""} autoComplete="off" />
              </Field>
              <Field
                label={fbs("Website", "Customer form: label of the company's website")}
                name="website"
                error={errors.website}
              >
                <Input
                  prefix={<Input.Text>https://</Input.Text>}
                  defaultValue={company?.website?.replace(/^https?:\/\//, "") ?? ""}
                  inputMode="url"
                  // The prefix shows https:// already, so a URL pasted into the empty field drops its own.
                  onPaste={(event) => {
                    const input = event.currentTarget;
                    const text = event.clipboardData.getData("text").trim();
                    if (input.value !== "" || !/^https?:\/\//i.test(text)) return;
                    event.preventDefault();
                    document.execCommand("insertText", false, text.replace(/^https?:\/\//i, ""));
                  }}
                  spellCheck={false}
                  autoComplete="off"
                />
              </Field>
            </div>
          ) : (
            <div className="grid items-start gap-5 @md:grid-cols-2">
              <Field
                label={fbs("First name", "Customer form: label of the person's first name")}
                name="first_name"
                error={errors.first_name}
              >
                <Input defaultValue={person?.firstName} autoComplete="off" />
              </Field>
              <Field
                label={fbs("Last name", "Customer form: label of the person's last name")}
                name="last_name"
                error={errors.last_name}
              >
                <Input defaultValue={person?.lastName} autoComplete="off" />
              </Field>
              <Field
                label={fbs("Email", "Customer form: label of the person's email")}
                name="email"
                error={errors.email}
              >
                <Input defaultValue={person?.email ?? ""} type="email" autoComplete="off" />
              </Field>
              <Field
                label={fbs("Phone", "Customer form: label of the person's phone number")}
                name="phone"
                error={errors.phone}
              >
                <Input defaultValue={person?.phone ?? ""} type="tel" autoComplete="off" />
              </Field>
            </div>
          )}
        </div>
        <div className="@container">
          <div className="grid items-start gap-5 @md:grid-cols-2">
            <PreferenceSelect
              name="preferred_locale"
              label={fbs(
                "Preferred language",
                "Customer form: label of the language the customer prefers",
              )}
              options={locales}
              defaultValue={customer?.preferredLocale}
              error={errors.preferred_locale}
            />
            <PreferenceSelect
              name="preferred_currency"
              label={fbs(
                "Preferred currency",
                "Customer form: label of the currency the customer prefers",
              )}
              options={currencies}
              defaultValue={customer?.preferredCurrency}
              error={errors.preferred_currency}
            />
          </div>
        </div>
      </Stack>
    </Section.Root>
  );
}

/** A searchable select whose first option, "No preference", sends an empty value. */
function PreferenceSelect({
  name,
  label,
  options,
  defaultValue,
  error,
}: {
  name: string;
  label: string;
  options: Option[];
  defaultValue?: string | null;
  error?: string;
}) {
  const items = [
    {
      value: "",
      label: fbs(
        "No preference",
        "Customer form: first option of the preferred language and currency selects",
      ),
    },
    // A saved value the shop no longer offers stays selectable, so saving doesn't drop it.
    ...(defaultValue && !options.some((option) => option.value === defaultValue)
      ? [{ value: defaultValue, label: defaultValue }]
      : []),
    ...options,
  ];

  return (
    <Field label={label} name={name} error={error}>
      <Select
        items={items}
        itemToKey={(option) => option.value}
        itemToLabel={(option) => option.label}
        defaultValue={defaultValue ?? ""}
        placeholder={fbs(
          "No preference",
          "Customer form: shown in the preferred language and currency selects while the customer has no preference",
        )}
        filter={{
          placeholder: fbs(
            "Search",
            "Customer form: placeholder of the search field in the language and currency selects",
          ),
          empty: fbs(
            "Nothing matches",
            "Customer form: shown in the language and currency selects when the search matches no option",
          ),
        }}
      />
    </Field>
  );
}
