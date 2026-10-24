import { RuleTester } from "oxlint/plugins-dev";
import { describe, it } from "vitest";
import plugin from "./plugin.mjs";

RuleTester.describe = describe;
RuleTester.it = it;

const ruleTester = new RuleTester({ languageOptions: { parserOptions: { lang: "tsx" } } });

const classError = (className: string) => ({ messageId: "marginClass", data: { className } });
const styleError = (property: string) => ({ messageId: "marginStyle", data: { property } });

ruleTester.run("no-margin", plugin.rules["no-margin"], {
  valid: [
    // Negative margins
    '<div className="-mt-2 -mx-px -ms-4 -mbs-1" />',
    '<div className="md:-mt-2 [&_aside]:-ml-[100vw] data-[open]:-mb-1 !-mt-2 -mt-2!" />',
    '<div className="mt-[-3px] [margin-top:-3px]" />',
    'cn("flex", active && "-ml-2")',
    // Centering
    '<div className="mx-auto md:mx-auto !mx-auto" />',
    'clsx("p-2", "mx-auto")',
    "<div style={{ marginTop: -4, marginLeft: -offset }} />",
    '<div style={{ margin: "-4px -8px", "margin-top": "-1rem", marginInline: `-2px` }} />',
    // Utilities that only look like margins
    '<div className="max-w-sm min-h-dvh mix-blend-multiply gap-2 p-4 my" />',
    // Dynamic class names can't be checked
    "<div className={`mt-${size}`} />",
    // Strings outside class lists
    'const label = "mt-2";',
    'track("mx-auto")',
    '<input placeholder="mt-2" />',
    // Other style properties
    "<div style={{ padding: 4, marginless: 1 }} />",
    "<div style={styles} />",
  ],
  invalid: [
    {
      code: '<div className="flex mt-2" />',
      errors: [classError("mt-2")],
    },
    {
      // Every margin utility and both sides of the class list
      code: '<div className="m-4 mx-4 my-1 mt-px mr-2 mb-0 ml-7 ms-3 me-3 mbs-2 mbe-2" />',
      errors: [
        "m-4",
        "mx-4",
        "my-1",
        "mt-px",
        "mr-2",
        "mb-0",
        "ml-7",
        "ms-3",
        "me-3",
        "mbs-2",
        "mbe-2",
      ].map(classError),
    },
    {
      code: '<div className="hover:mt-2 md:[&_a]:ml-1 data-[state=open]:mb-2 !mt-2 mt-2! mt-[3px] [margin:4px]" />',
      errors: [
        "hover:mt-2",
        "md:[&_a]:ml-1",
        "data-[state=open]:mb-2",
        "!mt-2",
        "mt-2!",
        "mt-[3px]",
        "[margin:4px]",
      ].map(classError),
    },
    {
      code: '<Error DANGEROUS_className="mt-1.5" />',
      errors: [classError("mt-1.5")],
    },
    {
      code: '<div class="mt-2" />',
      errors: [classError("mt-2")],
    },
    {
      code: '<span className={cn("flex", nested && "ml-7", { "mb-2": open })} />',
      errors: [classError("ml-7"), classError("mb-2")],
    },
    {
      code: "<div className={`flex ${open ? 'mt-2' : '-mt-2'} mb-4`} />",
      errors: [classError("mt-2"), classError("mb-4")],
    },
    {
      // Class helpers outside JSX; only mx-auto is allowed, not other auto margins
      code: 'const classes = clsx("p-2", "mx-auto", "ml-auto", "my-auto");',
      errors: [classError("ml-auto"), classError("my-auto")],
    },
    {
      code: '<div style={{ margin: 8, marginTop: "4px", marginInlineStart: offset, "margin-left": "1rem" }} />',
      errors: [
        styleError("margin"),
        styleError("marginTop"),
        styleError("marginInlineStart"),
        styleError("margin-left"),
      ],
    },
    {
      // A shorthand is only negative when every side is
      code: '<div style={{ margin: "-4px 8px", marginBlock: 0 }} />',
      errors: [styleError("margin"), styleError("marginBlock")],
    },
  ],
});
