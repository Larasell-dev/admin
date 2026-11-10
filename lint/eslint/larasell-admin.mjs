// Project lint rules, loaded by oxlint as a JS plugin (ESLint's rule API).

import { readFileSync } from "node:fs";
import path from "node:path";

const MARKER = /^\s*((?:Arrange|Act|Assert)(?: & (?:Arrange|Act|Assert))*)\s*$/;

/** `it`, `test`, `it.skip`, `test.each([...])` and the like, but not `pattern.test(...)`. */
function isTestCallee(callee) {
  let node = callee;
  while (node.type === "MemberExpression" || node.type === "CallExpression") {
    node = node.type === "MemberExpression" ? node.object : node.callee;
  }

  return node.type === "Identifier" && (node.name === "it" || node.name === "test");
}

/**
 * Every test marks what it sets up, what it exercises and what it checks with // Arrange,
 * // Act and // Assert comments, in that order. Sections that can't be told apart share one
 * comment, such as // Act & Assert, and a test that sets nothing up has no // Arrange.
 */
const arrangeActAssert = {
  meta: {
    type: "suggestion",
    messages: {
      sections:
        "Mark this test's sections with // Arrange, // Act and // Assert, in that order. Found: {{found}}. " +
        "Leave out // Arrange when the test sets nothing up, and combine sections that can't be split, such as // Act & Assert.",
      unmarkedStart:
        "Start this test with its // {{marker}} comment: the code above it belongs to no section.",
    },
  },
  create(context) {
    return {
      CallExpression(node) {
        if (!isTestCallee(node.callee)) {
          return;
        }

        const callback = node.arguments.find(
          (argument) =>
            argument.type === "ArrowFunctionExpression" || argument.type === "FunctionExpression",
        );
        if (!callback) {
          return;
        }

        const markers = context.sourceCode
          .getCommentsInside(callback.body)
          .concat(context.sourceCode.getCommentsBefore(callback.body))
          .filter((comment) => comment.type === "Line" && MARKER.test(comment.value))
          .toSorted((a, b) => a.range[0] - b.range[0]);
        const sections = markers.flatMap((marker) => marker.value.trim().split(" & "));

        if (sections.join() !== "Arrange,Act,Assert" && sections.join() !== "Act,Assert") {
          context.report({
            node: callback,
            messageId: "sections",
            data: {
              found:
                markers.length === 0
                  ? "none"
                  : markers.map((marker) => `// ${marker.value.trim()}`).join(", "),
            },
          });
          return;
        }

        const first =
          callback.body.type === "BlockStatement" ? callback.body.body[0] : callback.body;
        if (first && first.range[0] < markers[0].range[0]) {
          context.report({
            node: first,
            messageId: "unmarkedStart",
            data: { marker: markers[0].value.trim() },
          });
        }
      },
    };
  },
};

/** What each module builds on, shared with tests/Arch/ModulesTest.php. */
const MODULES = JSON.parse(readFileSync(new URL("../modules.json", import.meta.url), "utf8"));

const FRONTEND = path.resolve(import.meta.dirname, "../../resources/js");

/** "Platform", a module's name, or null for files outside both, such as app.tsx and types/. */
function areaOf(file) {
  const [first] = path.relative(FRONTEND, file).split(path.sep);

  return first === "Platform" || Object.hasOwn(MODULES, first) ? first : null;
}

/**
 * The frontend keeps the backend's module boundaries: resources/js/Platform never imports a
 * module, and a module imports only the platform, itself and the modules it builds on.
 */
const moduleBoundaries = {
  meta: {
    type: "problem",
    messages: {
      platform:
        "The platform doesn't use modules, but this imports the {{module}} module. " +
        "Have the module register itself with the platform instead, e.g. through Larasell.booting().",
      module:
        "The {{from}} module doesn't build on the {{module}} module. " +
        "Move what both need into resources/js/Platform, or list {{module}} in {{from}}'s buildsOn in lint/modules.json.",
    },
  },
  create(context) {
    const from = areaOf(context.filename);
    if (from === null) {
      return {};
    }

    function check(source) {
      if (
        source?.type !== "Literal" ||
        typeof source.value !== "string" ||
        !source.value.startsWith(".")
      ) {
        return;
      }

      const module = areaOf(path.resolve(path.dirname(context.filename), source.value));
      if (module === null || module === "Platform" || module === from) {
        return;
      }

      if (from === "Platform") {
        context.report({ node: source, messageId: "platform", data: { module } });
      } else if (!MODULES[from].buildsOn.includes(module)) {
        context.report({ node: source, messageId: "module", data: { from, module } });
      }
    }

    return {
      ImportDeclaration: (node) => check(node.source),
      ExportNamedDeclaration: (node) => check(node.source),
      ExportAllDeclaration: (node) => check(node.source),
      ImportExpression: (node) => check(node.source),
    };
  },
};

/** The most classes one string may hold before it's split into groups. */
const MAX_CLASSES = 10;

/** The text of a string literal or a template literal without expressions, else null. */
function staticText(node) {
  if (node?.type === "Literal" && typeof node.value === "string") {
    return node.value;
  }
  if (node?.type === "TemplateLiteral" && node.expressions.length === 0) {
    return node.quasis[0].value.cooked;
  }

  return null;
}

const classCount = (text) => text.split(/\s+/).filter(Boolean).length;

/**
 * Long class lists are hard to read and review on one line. Past MAX_CLASSES, a className is
 * written as cn() with one group of related classes per line, such as layout, color and states,
 * and each group stays within MAX_CLASSES too.
 */
const groupClassNames = {
  meta: {
    type: "suggestion",
    messages: {
      className:
        "This className has {{count}} classes. Write it as cn() with one group of related classes per line, such as layout, color and states, each at most {{max}}.",
      group:
        "This cn() group has {{count}} classes. Split it into groups of related classes, one per line, each at most {{max}}.",
    },
  },
  create(context) {
    function check(node, messageId) {
      const text = staticText(node);
      const count = text === null ? 0 : classCount(text);
      if (count > MAX_CLASSES) {
        context.report({ node, messageId, data: { count, max: MAX_CLASSES } });
      }
    }

    return {
      JSXAttribute(node) {
        if (node.name.type !== "JSXIdentifier" || node.name.name !== "className") {
          return;
        }
        check(
          node.value?.type === "JSXExpressionContainer" ? node.value.expression : node.value,
          "className",
        );
      },
      CallExpression(node) {
        if (node.callee.type === "Identifier" && node.callee.name === "cn") {
          for (const argument of node.arguments) {
            check(argument, "group");
          }
        }
      },
    };
  },
};

export default {
  meta: { name: "larasell-admin" },
  rules: {
    "arrange-act-assert": arrangeActAssert,
    "group-class-names": groupClassNames,
    "module-boundaries": moduleBoundaries,
  },
};
