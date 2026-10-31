// Oxlint JS plugin with the project's own lint rules. See
// https://oxc.rs/docs/guide/usage/linter/writing-js-plugins.html

// Tailwind margin utilities: m-4, mt-[3px], mbs-2, …, and arbitrary properties like
// [margin-top:3px]. A leading "-" (-mt-2) or a negative value (mt-[-3px]) is a negative margin.
// mx-auto is allowed: it centers a block horizontally, which gap or padding can't do.
const MARGIN_UTILITY = /^(-?)m(?:[xytrblse]|b[se])?-(.+)$/;
const MARGIN_PROPERTY = /^\[margin(?:-[a-z-]+)?:(.+)\]$/;

// Calls whose string arguments are class lists, e.g. cn("flex", active && "mt-2").
const CLASS_HELPERS = new Set(["cn", "clsx", "cva", "twMerge", "twJoin"]);

// className, class, and component props like DANGEROUS_className.
const isClassAttribute = (name) => name === "class" || /classname$/i.test(name);

// The utility without its variants (md:, [&_a]:, data-[state=open]:) and important modifier.
// Colons inside brackets or parentheses belong to a value, as in text-(length:--x).
function utilityOf(token) {
  let depth = 0;
  let start = 0;
  for (let i = 0; i < token.length; i++) {
    if (token[i] === "[" || token[i] === "(") depth++;
    else if (token[i] === "]" || token[i] === ")") depth--;
    else if (token[i] === ":" && depth === 0) start = i + 1;
  }
  return token.slice(start).replace(/^!|!$/g, "");
}

function isMarginClass(token) {
  const utility = utilityOf(token);
  if (utility === "mx-auto") return false;
  const property = MARGIN_PROPERTY.exec(utility);
  if (property) return !property[1].startsWith("-");
  const match = MARGIN_UTILITY.exec(utility);
  return match !== null && match[1] !== "-" && !match[2].startsWith("[-");
}

const marginClasses = (value) => value.split(/\s+/).filter(isMarginClass);

function isClassContext(node) {
  for (let current = node.parent; current; current = current.parent) {
    if (current.type === "JSXAttribute") {
      return current.name.type === "JSXIdentifier" && isClassAttribute(current.name.name);
    }
    if (
      current.type === "CallExpression" &&
      current.callee.type === "Identifier" &&
      CLASS_HELPERS.has(current.callee.name)
    ) {
      return true;
    }
  }
  return false;
}

// margin, marginTop, marginInline, "margin-top", …
const isMarginProperty = (key) => /^margin(?:[A-Z-]|$)/.test(key);

function propertyName(property) {
  if (property.computed) return null;
  if (property.key.type === "Identifier") return property.key.name;
  if (property.key.type === "Literal" && typeof property.key.value === "string") {
    return property.key.value;
  }
  return null;
}

// Only values that are negative as written pass: -4, -offset, "-4px", "-1rem -2rem".
function isNegative(value) {
  if (value.type === "UnaryExpression" && value.operator === "-") return true;
  if (value.type === "Literal") {
    if (typeof value.value === "number") return value.value < 0;
    if (typeof value.value === "string") {
      const parts = value.value.trim().split(/\s+/);
      return parts.every((part) => part.startsWith("-"));
    }
  }
  if (value.type === "TemplateLiteral" && value.expressions.length === 0) {
    const parts = value.quasis[0].value.cooked.trim().split(/\s+/);
    return parts.every((part) => part.startsWith("-"));
  }
  return false;
}

const noMargin = {
  meta: {
    type: "suggestion",
    docs: {
      description: "Forbid positive margins; space elements with gap or padding instead.",
    },
    messages: {
      marginClass:
        "Don't use the margin class `{{ className }}`. Use gap or padding instead; only negative margins are allowed.",
      marginStyle:
        "Don't set `{{ property }}` in inline styles. Use gap or padding instead; only negative margins are allowed.",
    },
    schema: [],
  },
  create(context) {
    function checkClasses(node, value) {
      if (!isClassContext(node)) return;
      for (const className of marginClasses(value)) {
        context.report({ node, messageId: "marginClass", data: { className } });
      }
    }

    return {
      Literal(node) {
        if (typeof node.value === "string") checkClasses(node, node.value);
      },
      TemplateElement(node) {
        checkClasses(node, node.value.cooked ?? node.value.raw);
      },
      JSXAttribute(node) {
        if (
          node.name.type !== "JSXIdentifier" ||
          node.name.name !== "style" ||
          node.value?.type !== "JSXExpressionContainer" ||
          node.value.expression.type !== "ObjectExpression"
        ) {
          return;
        }
        for (const property of node.value.expression.properties) {
          if (property.type !== "Property") continue;
          const name = propertyName(property);
          if (name !== null && isMarginProperty(name) && !isNegative(property.value)) {
            context.report({ node: property, messageId: "marginStyle", data: { property: name } });
          }
        }
      },
    };
  },
};

// Expressions a class list passes through on its way to a class attribute or helper call:
// className={open ? `…` : "…"}, className={open && `…`}, (`…`) as string.
const CLASS_VALUE_WRAPPERS = new Set([
  "ConditionalExpression",
  "LogicalExpression",
  "ParenthesizedExpression",
  "TSAsExpression",
  "TSNonNullExpression",
]);

// Whether the node is a class list's value: a class attribute's, or a class helper's argument.
function isClassValue(node) {
  let current = node.parent;
  while (current && CLASS_VALUE_WRAPPERS.has(current.type)) current = current.parent;
  if (current?.type === "JSXExpressionContainer") current = current.parent;

  if (current?.type === "JSXAttribute") {
    return current.name.type === "JSXIdentifier" && isClassAttribute(current.name.name);
  }
  return (
    current?.type === "CallExpression" &&
    current.callee.type === "Identifier" &&
    CLASS_HELPERS.has(current.callee.name)
  );
}

const preferCn = {
  meta: {
    type: "suggestion",
    docs: {
      description: "Build conditional class lists with cn() instead of interpolating strings.",
    },
    messages: {
      template:
        'Don\'t interpolate class names in a template literal. Pass the parts to cn() instead, e.g. cn("flex", open && "gap-2").',
      concatenation:
        'Don\'t concatenate class names. Pass the parts to cn() instead, e.g. cn("flex", open && "gap-2").',
    },
    schema: [],
  },
  create(context) {
    return {
      TemplateLiteral(node) {
        if (node.expressions.length > 0 && isClassValue(node)) {
          context.report({ node, messageId: "template" });
        }
      },
      BinaryExpression(node) {
        // Only the outermost + of a chain like "a " + b + " c" reports.
        if (node.operator === "+" && isClassValue(node)) {
          context.report({ node, messageId: "concatenation" });
        }
      },
    };
  },
};

// Font sizes come only from the typescale of @larasell-dev/ui: its type-* utilities, like
// type-body-md, set a role's size together with its line height, weight, and tracking.
// Tailwind's own sizes bypass it. text-left, text-mauve-500 and the like aren't sizes.
const NAMED_FONT_SIZE = /^text-(?:xs|sm|base|lg|\d?xl)(?:\/.+)?$/;
// Arbitrary sizes: text-[13px], text-[calc(…)], text-[length:…], text-(length:--x), text-[larger].
const ARBITRARY_FONT_SIZE =
  /^text-(?:\[(?:[\d.]|-[\d.]|(?:calc|clamp|min|max)\(|(?:length|percentage|absolute-size|relative-size):|(?:xx?x?-)?(?:small|large)\]|medium\]|larger\]|smaller\])|\((?:length|percentage):)/;
const FONT_SIZE_PROPERTY = /^\[font-size:/;

function isOffScaleFontSize(token) {
  const utility = utilityOf(token);
  return (
    NAMED_FONT_SIZE.test(utility) ||
    ARBITRARY_FONT_SIZE.test(utility) ||
    FONT_SIZE_PROPERTY.test(utility)
  );
}

const noOffScaleFontSize = {
  meta: {
    type: "suggestion",
    docs: {
      description: "Forbid font sizes that aren't on the typescale of @larasell-dev/ui.",
    },
    messages: {
      fontSizeClass:
        "Don't set the font size with `{{ className }}`. Use a type-* utility like type-body-md instead.",
      fontSizeStyle:
        "Don't set `{{ property }}` in inline styles. Use a type-* utility like type-body-md instead.",
    },
    schema: [],
  },
  create(context) {
    function checkClasses(node, value) {
      if (!isClassContext(node)) return;
      for (const className of value.split(/\s+/).filter(isOffScaleFontSize)) {
        context.report({ node, messageId: "fontSizeClass", data: { className } });
      }
    }

    return {
      Literal(node) {
        if (typeof node.value === "string") checkClasses(node, node.value);
      },
      TemplateElement(node) {
        checkClasses(node, node.value.cooked ?? node.value.raw);
      },
      JSXAttribute(node) {
        if (
          node.name.type !== "JSXIdentifier" ||
          node.name.name !== "style" ||
          node.value?.type !== "JSXExpressionContainer" ||
          node.value.expression.type !== "ObjectExpression"
        ) {
          return;
        }
        for (const property of node.value.expression.properties) {
          if (property.type !== "Property") continue;
          const name = propertyName(property);
          if (name === "fontSize" || name === "font-size") {
            context.report({
              node: property,
              messageId: "fontSizeStyle",
              data: { property: name },
            });
          }
        }
      },
    };
  },
};

export default {
  meta: { name: "larasell" },
  rules: {
    "no-margin": noMargin,
    "no-off-scale-font-size": noOffScaleFontSize,
    "prefer-cn": preferCn,
  },
};
