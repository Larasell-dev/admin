import { Field, Input, Section, Stack, Tooltip, TruncateMiddle } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import type { SectionComponentProps } from "../../extensions";

type Props = Larasell.Admin.Seo.Data.ProductUrlsData;

/**
 * The product's URL in each storefront channel that sells in the content language. An empty
 * field makes the URL from the product's name, which then follows renames; a typed path stays.
 */
export default function ProductUrlsSection({ props, name, error }: SectionComponentProps<Props>) {
  return (
    <Section.Root>
      <Section.Header
        title={fbs("URLs", "Product form: heading of the section with the product's web addresses")}
        description={fbs(
          "Where customers find the product in each storefront. Leave a URL empty to make it from the product's name, so it follows when the name changes.",
          "Product form: explains the URLs section; an empty field gives the product an address made from its name",
        )}
      />
      {props.urls.length > 0 ? (
        <Stack spacing={5}>
          {props.urls.map((url) => (
            <Field
              key={url.channelId}
              label={url.channel}
              name={name(`urls.${url.channelId}.path`)}
              error={error(`urls.${url.channelId}.path`)}
            >
              <Input
                prefix={
                  // The domain is cut in the middle, so the language at its end
                  // still tells a channel's domains apart; hover it for all of it.
                  <Tooltip.Truncated>
                    <Input.Text>
                      <TruncateMiddle>{url.baseUrl.replace(/^https?:\/\//, "")}</TruncateMiddle>
                    </Input.Text>
                  </Tooltip.Truncated>
                }
                defaultValue={url.customPath ?? ""}
                placeholder={
                  url.automaticPath ??
                  fbs(
                    "Made from the name",
                    "Product form: placeholder of a URL field while the product has no name in the content language yet",
                  )
                }
                autoComplete="off"
                spellCheck={false}
              />
            </Field>
          ))}
        </Stack>
      ) : (
        <p className="type-body-md text-base-secondary">
          <fbt desc="Product form: shown in the URLs section while no storefront has a domain in the content language">
            No storefront has a domain in this language yet, so the product has no URL in it.
          </fbt>
        </p>
      )}
    </Section.Root>
  );
}
