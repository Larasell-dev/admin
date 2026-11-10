import { Field, Select } from "@larasell-dev/ui";
import { fbs } from "fbtee";

type Option = Larasell.Admin.Customer.Data.CustomerOptionData;

/** A searchable select of countries, named in the interface language, that sends `country_code`. */
export default function CountrySelect({
  countries,
  defaultValue,
  error,
}: {
  countries: Option[];
  defaultValue?: string | null;
  error?: string;
}) {
  return (
    <Field
      label={fbs("Country", "Customer form: label of the select for a country")}
      name="country_code"
      error={error}
    >
      <Select
        items={countries}
        itemToKey={(option) => option.value}
        itemToLabel={(option) => option.label}
        defaultValue={defaultValue ?? null}
        placeholder={fbs(
          "Choose a country",
          "Customer form: shown in the country select until one is picked",
        )}
        filter={{
          placeholder: fbs(
            "Search countries",
            "Customer form: placeholder of the search field in the country select",
          ),
          empty: fbs(
            "No country matches",
            "Customer form: shown in the country select when the search matches none",
          ),
        }}
      />
    </Field>
  );
}
