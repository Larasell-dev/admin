import { Field, Input, Stack, Tooltip, TruncateMiddle } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import type { ReactNode } from "react";
import type { SectionComponentProps } from "../../extensions";

type Props = Larasell.Admin.Seo.Data.SeoUrlsData;

/**
 * The URL fields of a product's or category's URLs section, one per storefront channel that
 * sells in the content language, or `empty` while there is none. An empty field makes the URL
 * from the name, which then follows renames; a typed path stays.
 */
export default function UrlFields({
  props,
  name,
  error,
  empty,
}: SectionComponentProps<Props> & { empty: ReactNode }) {
  return props.urls.length > 0 ? (
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
                "Product and category forms: placeholder of a URL field while there is no name in the content language yet",
              )
            }
            autoComplete="off"
            spellCheck={false}
          />
        </Field>
      ))}
    </Stack>
  ) : (
    <p className="type-body-md text-base-secondary">{empty}</p>
  );
}
