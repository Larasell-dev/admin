import { RuleTester } from "oxlint/plugins-dev";
import { describe, it } from "vitest";
import plugin from "./plugin.mjs";

RuleTester.describe = describe;
RuleTester.it = it;

const ruleTester = new RuleTester({ languageOptions: { parserOptions: { lang: "tsx" } } });

const template = { messageId: "template" };
const concatenation = { messageId: "concatenation" };

ruleTester.run("prefer-cn", plugin.rules["prefer-cn"], {
  valid: [
    '<div className="flex gap-2" />',
    '<div className={cn("flex", open && "gap-2", { hidden: !open })} />',
    '<div className={open ? "flex" : "hidden"} />',
    '<div className={open && "flex"} />',
    "<div className={classes} />",
    // A template literal without interpolations is one plain string
    "<div className={`flex gap-2`} />",
    // Interpolations outside class lists
    "<a href={`/orders/${id}`} title={`Order ${number}`} />",
    "const label = `Order ${number}`;",
    'const url = base + "/orders";',
    "track(`opened ${name}`)",
    // Calls other than class helpers own their arguments
    "<div className={styles(`card-${variant}`)} />",
    // Tagged templates aren't plain interpolation
    "<div className={tw`flex ${gap}`} />",
  ],
  invalid: [
    {
      code: '<div className={`flex ${open ? "gap-2" : ""}`} />',
      errors: [template],
    },
    {
      code: "<div className={`min-h-0 ${className}`} />",
      errors: [template],
    },
    {
      code: '<Error DANGEROUS_className={`truncate ${muted ? "text-mauve-500" : ""}`} />',
      errors: [template],
    },
    {
      code: "<div class={`flex ${extra}`} />",
      errors: [template],
    },
    {
      // Inside a conditional or a logical expression
      code: '<div className={open ? `flex ${gap}` : "hidden"} />',
      errors: [template],
    },
    {
      code: "<div className={open && `flex ${gap}`} />",
      errors: [template],
    },
    {
      // Inside a class helper: pass the parts as separate arguments
      code: '<div className={cn("p-2", `flex ${gap}`)} />',
      errors: [template],
    },
    {
      code: 'const classes = clsx(`flex ${gap}`, "p-2");',
      errors: [template],
    },
    {
      // One report for a whole chain of concatenations
      code: '<div className={"flex " + gap + " p-2"} />',
      errors: [concatenation],
    },
    {
      code: 'const classes = cn("flex " + gap);',
      errors: [concatenation],
    },
  ],
});
