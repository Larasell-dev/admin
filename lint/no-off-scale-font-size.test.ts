import { RuleTester } from "oxlint/plugins-dev";
import { describe, it } from "vitest";
import plugin from "./plugin.mjs";

RuleTester.describe = describe;
RuleTester.it = it;

const ruleTester = new RuleTester({ languageOptions: { parserOptions: { lang: "tsx" } } });

const classError = (className: string) => ({ messageId: "fontSizeClass", data: { className } });
const styleError = (property: string) => ({ messageId: "fontSizeStyle", data: { property } });

ruleTester.run("no-off-scale-font-size", plugin.rules["no-off-scale-font-size"], {
  valid: [
    // The typescale's roles
    '<p className="type-body-md type-headline-lg type-label-sm-strong" />',
    'cn("flex", large && "type-display-lg")',
    // text-* utilities that aren't font sizes
    '<p className="text-left text-mauve-500 text-ellipsis text-wrap text-balance text-white" />',
    '<p className="text-[#fff] text-[red] text-[var(--color-x)] text-(--my-color) text-(color:--x)" />',
    // Dynamic class names can't be checked
    "<p className={`text-${size}`} />",
    // Strings outside class lists
    'const label = "text-9xl";',
    'track("text-[13px]")',
    '<input placeholder="text-5xl" />',
    // Other style properties
    "<p style={{ fontWeight: 600, lineHeight: 1.5 }} />",
    "<p style={styles} />",
  ],
  invalid: [
    {
      code: '<p className="flex text-sm" />',
      errors: [classError("text-sm")],
    },
    {
      // Every Tailwind size, including the ones the typescale's roles are built from
      code: '<h1 className="text-xs text-sm text-base text-lg text-xl text-2xl text-3xl text-4xl text-5xl text-9xl" />',
      errors: [
        "text-xs",
        "text-sm",
        "text-base",
        "text-lg",
        "text-xl",
        "text-2xl",
        "text-3xl",
        "text-4xl",
        "text-5xl",
        "text-9xl",
      ].map(classError),
    },
    {
      // Arbitrary sizes
      code: '<p className="text-[13px] text-[0.8rem] text-[2em] text-[120%] text-[calc(1rem+2px)] text-[clamp(1rem,2vw,2rem)]" />',
      errors: [
        "text-[13px]",
        "text-[0.8rem]",
        "text-[2em]",
        "text-[120%]",
        "text-[calc(1rem+2px)]",
        "text-[clamp(1rem,2vw,2rem)]",
      ].map(classError),
    },
    {
      // Sizes marked as lengths, size keywords, and arbitrary properties
      code: '<p className="text-[length:var(--x)] text-(length:--x) text-[larger] text-[x-large] [font-size:13px]" />',
      errors: [
        "text-[length:var(--x)]",
        "text-(length:--x)",
        "text-[larger]",
        "text-[x-large]",
        "[font-size:13px]",
      ].map(classError),
    },
    {
      code: '<p className="md:text-lg [&_a]:text-[11px] !text-base text-sm! text-sm/6 text-[13px]/5" />',
      errors: [
        "md:text-lg",
        "[&_a]:text-[11px]",
        "!text-base",
        "text-sm!",
        "text-sm/6",
        "text-[13px]/5",
      ].map(classError),
    },
    {
      code: '<Error DANGEROUS_className="text-[11px]" />',
      errors: [classError("text-[11px]")],
    },
    {
      code: '<span className={cn("flex", big && "text-2xl", { "text-[10px]": small })} />',
      errors: [classError("text-2xl"), classError("text-[10px]")],
    },
    {
      code: "<p className={`flex ${big ? 'text-7xl' : 'type-body-md'}`} />",
      errors: [classError("text-7xl")],
    },
    {
      // Inline styles skip the scale, whatever the value
      code: '<p style={{ fontSize: 13, "font-size": "1rem", fontSize: size }} />',
      errors: [styleError("fontSize"), styleError("font-size"), styleError("fontSize")],
    },
  ],
});
