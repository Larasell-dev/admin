import { Section } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import type { SectionComponentProps } from "../../extensions";
import UrlFields from "./UrlFields";

/**
 * The category's URL in each storefront channel that sells in the content language. An
 * automatic one sits below its parent's URL.
 */
export default function CategoryUrlsSection(
  section: SectionComponentProps<Larasell.Admin.Seo.Data.SeoUrlsData>,
) {
  return (
    <Section.Root>
      <Section.Header
        title={fbs(
          "URLs",
          "Category form: heading of the section with the category's web addresses",
        )}
        description={fbs(
          "Where customers find the category in each storefront. Leave a URL empty to make it from the category's name, below its parent's URL, so it follows when either changes.",
          "Category form: explains the URLs section; an empty field gives the category an address made from its name and its parent's address",
        )}
      />
      <UrlFields
        {...section}
        empty={
          <fbt desc="Category form: shown in the URLs section while no storefront has a domain in the content language">
            No storefront has a domain in this language yet, so the category has no URL in it.
          </fbt>
        }
      />
    </Section.Root>
  );
}
