import { Section } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import type { SectionComponentProps } from "../../extensions";
import UrlFields from "./UrlFields";

/**
 * The product's URL in each storefront channel that sells in the content language.
 */
export default function ProductUrlsSection(
  section: SectionComponentProps<Larasell.Admin.Seo.Data.SeoUrlsData>,
) {
  return (
    <Section.Root>
      <Section.Header
        title={fbs("URLs", "Product form: heading of the section with the product's web addresses")}
        description={fbs(
          "Where customers find the product in each storefront. Leave a URL empty to make it from the product's name, so it follows when the name changes.",
          "Product form: explains the URLs section; an empty field gives the product an address made from its name",
        )}
      />
      <UrlFields
        {...section}
        empty={
          <fbt desc="Product form: shown in the URLs section while no storefront has a domain in the content language">
            No storefront has a domain in this language yet, so the product has no URL in it.
          </fbt>
        }
      />
    </Section.Root>
  );
}
