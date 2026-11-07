import { Field as BaseField } from "@base-ui/react/field";
import { useFormContext } from "@inertiajs/react";
import { Checkbox, Field, Input, Section, SectionStack, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { type ReactNode, useState } from "react";
import { slugify } from "../../Platform/Support/slugify";
import { truncate } from "../../Platform/Support/truncate";
import { useCoupledField } from "../../Platform/Support/useCoupledField";
import FormCheckbox from "../../Platform/Panel/Components/FormCheckbox";
import FormSelect from "../../Platform/Panel/Components/FormSelect";

type Channel = Larasell.Admin.SalesChannel.Data.SalesChannelData;
type Option = Larasell.Admin.SalesChannel.Data.SalesChannelOptionData;

/**
 * The fields of a sales channel, in sections. It must sit inside an Inertia `<Form>`, which
 * submits them and provides their errors. Without a channel it starts empty and asks for the key,
 * which can't change later, and for the type when more than one is registered.
 */
export default function SalesChannelForm({
  channel,
  types = [],
  locales,
  currencies,
}: {
  channel?: Channel;
  types?: string[];
  locales: Option[];
  currencies: Option[];
}) {
  const errors = useFormContext()?.errors ?? {};
  // A new channel's key follows its name until the admin edits it; an existing key can't change.
  const key = useCoupledField([slugify, truncate(64), slugify], { enabled: !channel });
  const [pickedLocales, setPickedLocales] = useState(
    channel?.locales ?? locales.slice(0, 1).map((option) => option.value),
  );
  const [pickedCurrencies, setPickedCurrencies] = useState(
    channel?.currencies ?? currencies.slice(0, 1).map((option) => option.value),
  );

  return (
    <SectionStack>
      {errors.channel && (
        <p role="alert" className="type-body-md text-intent-negative">
          {errors.channel}
        </p>
      )}

      <Section.Root>
        <Section.Header
          title={fbs(
            "General",
            "Sales channel form: heading of the section with the channel's name, key and whether it's active",
          )}
          description={fbs(
            "How the channel is named here and in code.",
            "Sales channel form: explains the section with the channel's name and key",
          )}
        />
        <Stack spacing={5}>
          <div className="@container">
            <div className="grid items-start gap-5 @md:grid-cols-2">
              <Field
                label={fbs("Name", "Sales channel form: label of the field for the channel's name")}
                name="name"
                error={errors.name}
              >
                <Input {...key.source} defaultValue={channel?.name} autoComplete="off" />
              </Field>

              <Field
                label={fbs(
                  "Key",
                  "Sales channel form: label of the field for the channel's key, the name code uses for it",
                )}
                name="key"
                error={errors.key}
                description={
                  channel
                    ? fbs(
                        "Code refers to the channel by its key, so it can't change.",
                        "Sales channel form: hint below the read-only key of an existing channel",
                      )
                    : fbs(
                        "Lowercase letters and numbers, joined by - or _. It can't change later.",
                        "Sales channel form: hint below the key field of a new channel, listing its rules",
                      )
                }
              >
                <Input
                  {...key.target}
                  defaultValue={channel?.key}
                  readOnly={channel !== undefined}
                  autoComplete="off"
                  spellCheck={false}
                />
              </Field>
            </div>
          </div>

          {!channel &&
            (types.length > 1 ? (
              <FormSelect
                label={fbs(
                  "Type",
                  "Sales channel form: label of the select for the kind of channel, e.g. storefront",
                )}
                error={errors.type}
                name="type"
                items={types.map((type) => ({ value: type, label: type }))}
                defaultValue={types.slice(0, 1)}
              />
            ) : (
              <input type="hidden" name="type" value={types[0] ?? ""} />
            ))}

          <BaseField.Root name="active" className="relative flex flex-col gap-1.5">
            <Checkbox.Label>
              <FormCheckbox
                name="active"
                value="1"
                uncheckedValue="0"
                defaultChecked={channel?.active ?? true}
              />
              <fbt desc="Sales channel form: label of the checkbox that lets customers buy through the channel">
                Active
              </fbt>
            </Checkbox.Label>
          </BaseField.Root>
        </Stack>
      </Section.Root>

      <Section.Root>
        <Section.Header
          title={fbs(
            "Languages",
            "Sales channel form: heading of the section with the languages the channel sells in",
          )}
          description={fbs(
            "The languages customers can shop in. They come from the store's supported locales.",
            "Sales channel form: explains the languages section; the options are configured in larasell.locale.supported",
          )}
        />
        <Choices
          name="locales"
          label={fbs(
            "Sells in",
            "Sales channel form: label of the field that picks the languages the channel can sell in",
          )}
          options={locales}
          picked={pickedLocales}
          onChange={setPickedLocales}
          error={errors.locales ?? errors["locales.0"]}
        >
          <DefaultSelect
            name="default_locale"
            label={fbs(
              "Default language",
              "Sales channel form: label of the select for the language the channel uses when a customer picks none",
            )}
            options={locales.filter((option) => pickedLocales.includes(option.value))}
            defaultValue={channel?.defaultLocale}
            error={errors.default_locale}
          />
        </Choices>
      </Section.Root>

      <Section.Root>
        <Section.Header
          title={fbs(
            "Currencies",
            "Sales channel form: heading of the section with the currencies the channel sells in",
          )}
          description={fbs(
            "The currencies customers can pay in. They come from the currencies enabled in the settings.",
            "Sales channel form: explains the currencies section",
          )}
        />
        <Choices
          name="currencies"
          label={fbs(
            "Sells in",
            "Sales channel form: label of the field that picks the currencies the channel can sell in",
          )}
          options={currencies}
          withCode
          picked={pickedCurrencies}
          onChange={setPickedCurrencies}
          error={errors.currencies ?? errors["currencies.0"]}
        >
          <DefaultSelect
            name="default_currency"
            label={fbs(
              "Default currency",
              "Sales channel form: label of the select for the currency prices are shown in first",
            )}
            options={currencies.filter((option) => pickedCurrencies.includes(option.value))}
            defaultValue={channel?.defaultCurrency}
            withCode
            error={errors.default_currency}
          />
        </Choices>
      </Section.Root>
    </SectionStack>
  );
}

/**
 * Picks several options, with the select of the default below. Typing filters them, which
 * hundreds of locales or currencies need. With `withCode`, each option shows its code after its
 * name, less prominent.
 */
function Choices({
  name,
  label,
  options,
  picked,
  onChange,
  withCode = false,
  error,
  children,
}: {
  name: string;
  label: string;
  options: Option[];
  picked: string[];
  onChange: (picked: string[]) => void;
  withCode?: boolean;
  error?: string;
  children: ReactNode;
}) {
  return (
    <Stack spacing={5}>
      <FormSelect
        label={label}
        error={error}
        name={`${name}[]`}
        multiple
        items={options}
        itemToLabel={(option) => optionLabel(option, withCode)}
        defaultValue={picked}
        onValueChange={onChange}
        placeholder={fbs(
          "None picked",
          "Sales channel form: shown on the button of the languages or currencies field while none is picked",
        )}
        filter={{
          placeholder: fbs(
            "Search",
            "Sales channel form: placeholder of the search field in the list of languages or currencies the channel sells in",
          ),
          empty: fbs(
            "Nothing matches",
            "Sales channel form: shown in the list of languages or currencies when the typed text matches none",
          ),
        }}
      >
        {(option) => (
          <>
            {option.label}
            {withCode && <span className="text-base-tertiary"> {option.value}</span>}
          </>
        )}
      </FormSelect>
      {children}
    </Stack>
  );
}

/** How an option reads as text, e.g. "Euro (EUR)" with its code. */
const optionLabel = (option: Option, withCode: boolean) =>
  withCode && option.label !== option.value ? `${option.label} (${option.value})` : option.label;

/** Picks one of the picked options as the default; a removed default falls back to the first. */
function DefaultSelect({
  name,
  label,
  options,
  defaultValue,
  withCode = false,
  error,
}: {
  name: string;
  label: string;
  options: Option[];
  defaultValue?: string;
  withCode?: boolean;
  error?: string;
}) {
  return (
    <div className="max-w-xs">
      <FormSelect
        label={label}
        error={error}
        name={name}
        items={options}
        itemToLabel={(option) => optionLabel(option, withCode)}
        defaultValue={defaultValue ? [defaultValue] : []}
        disabled={options.length === 0}
      />
    </div>
  );
}
