// Project lint rules, loaded by oxlint as a JS plugin (ESLint's rule API).

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

export default {
  meta: { name: "larasell-admin" },
  rules: { "arrange-act-assert": arrangeActAssert },
};
