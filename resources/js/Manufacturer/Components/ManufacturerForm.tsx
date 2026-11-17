import { useFormContext } from "@inertiajs/react";
import { Field, Input, Select, Stack } from "@larasell-dev/ui";
import Textarea from "../../Platform/Panel/Components/Textarea";
import { fbs } from "fbtee";
import { useState } from "react";

type Manufacturer = Larasell.Admin.Manufacturer.Data.ManufacturerDetailData;
type Address = Larasell.Admin.Manufacturer.Data.PostalAddressData;
type Country = Larasell.Admin.Manufacturer.Data.CountryOptionData;
type Errors = Record<string, string | undefined>;

/** A manufacturer's fields with its optional EU responsible person. It must sit inside an Inertia `<Form>`. */
export default function ManufacturerForm({
  manufacturer,
  countries,
  part,
  errors: own,
}: {
  /** Its own errors, when it isn't the surrounding form's, e.g. in the product form's slide-over. */
  errors?: Errors;
  manufacturer?: Manufacturer;
  countries: Country[];
  /** The manufacturer's own fields, or its responsible person's. */
  part: "manufacturer" | "responsible-person";
}) {
  const context: Errors = useFormContext()?.errors ?? {};
  const errors = own ?? context;
  // Its address is required once the responsible person has a name.
  const [personName, setPersonName] = useState(manufacturer?.responsiblePersonName ?? "");

  if (part === "responsible-person") {
    return (
      <Stack spacing={5}>
        <div className="@container">
          <div className="grid items-start gap-5 @md:grid-cols-2">
            <Field
              label={fbs("Name", "Manufacturer form: label of the responsible person's name")}
              name="responsible_person[name]"
              error={errors["responsible_person.name"]}
            >
              <Input
                defaultValue={manufacturer?.responsiblePersonName ?? ""}
                onChange={(event) => setPersonName(event.currentTarget.value)}
                autoComplete="off"
              />
            </Field>
            <Field
              label={fbs("Email", "Manufacturer form: label of the responsible person's email")}
              name="responsible_person[email]"
              error={errors["responsible_person.email"]}
            >
              <Input
                type="email"
                defaultValue={manufacturer?.responsiblePersonEmail ?? ""}
                autoComplete="off"
              />
            </Field>
          </div>
        </div>
        <AddressFields
          prefix="responsible_person.address"
          required={personName.trim() !== ""}
          address={manufacturer?.responsiblePersonAddress ?? null}
          countries={countries}
          errors={errors}
        />
      </Stack>
    );
  }

  return (
    <Stack spacing={5}>
      <Field
        label={fbs("Legal name", "Manufacturer form: label of the manufacturer's legal name")}
        name="name"
        error={errors.name}
        required
      >
        <Input defaultValue={manufacturer?.name} autoComplete="off" />
      </Field>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs("Email", "Manufacturer form: label of the manufacturer's contact email")}
            name="email"
            error={errors.email}
          >
            <Input type="email" defaultValue={manufacturer?.email ?? ""} autoComplete="off" />
          </Field>
          <Field
            label={fbs("Website", "Manufacturer form: label of the manufacturer's website URL")}
            name="url"
            error={errors.url}
          >
            <Input
              type="url"
              defaultValue={manufacturer?.url ?? ""}
              autoComplete="off"
              placeholder="https://"
            />
          </Field>
        </div>
      </div>
      <AddressFields
        prefix="address"
        required
        address={manufacturer?.address ?? null}
        countries={countries}
        errors={errors}
      />
    </Stack>
  );
}

/** A postal address, sent as `{prefix}[street]` and so on; street lines go one per line. */
function AddressFields({
  prefix,
  address,
  countries,
  errors,
  required,
}: {
  /** Whether the address must be filled in; the responsible person's must once it has a name. */
  required: boolean;
  prefix: string;
  address: Address | null;
  countries: Country[];
  errors: Errors;
}) {
  // address.street → address[street], responsible_person.address.city → responsible_person[address][city]
  const name = (field: string) => {
    const [first, ...rest] = [...prefix.split("."), field];
    return first + rest.map((part) => `[${part}]`).join("");
  };
  return (
    <Stack spacing={5}>
      <Field
        label={fbs("Street", "Manufacturer form: label of the address's street lines")}
        name={name("street")}
        error={errors[`${prefix}.street`]}
        required={required}
      >
        <Textarea name={name("street")} defaultValue={address?.street ?? ""} />
      </Field>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs("Postcode", "Manufacturer form: label of the address's postcode")}
            name={name("postcode")}
            error={errors[`${prefix}.postcode`]}
            required={required}
          >
            <Input defaultValue={address?.postcode ?? ""} autoComplete="off" />
          </Field>
          <Field
            label={fbs("City", "Manufacturer form: label of the address's city")}
            name={name("city")}
            error={errors[`${prefix}.city`]}
            required={required}
          >
            <Input defaultValue={address?.city ?? ""} autoComplete="off" />
          </Field>
          <Field
            label={fbs(
              "State or region",
              "Manufacturer form: label of the address's optional state",
            )}
            name={name("state")}
            error={errors[`${prefix}.state`]}
          >
            <Input defaultValue={address?.state ?? ""} autoComplete="off" />
          </Field>
          <Field
            label={fbs("Country", "Manufacturer form: label of the address's country")}
            name={name("country")}
            error={errors[`${prefix}.country`]}
            required={required}
          >
            <Select
              items={[{ code: "", label: "—" }, ...countries]}
              itemToKey={(country) => country.code}
              itemToLabel={(country) => country.label}
              defaultValue={address?.country ?? ""}
              filter={{
                placeholder: fbs(
                  "Search countries",
                  "Manufacturer form: placeholder of the country search",
                ),
                empty: fbs(
                  "No country found",
                  "Manufacturer form: shown when the country search matches none",
                ),
              }}
            />
          </Field>
        </div>
      </div>
    </Stack>
  );
}
