import { RuleTester } from "oxlint/plugins-dev";
import { describe, it } from "vitest";
import plugin from "./larasell-admin.mjs";

RuleTester.describe = describe;
RuleTester.it = it;

const sections = (found) => ({ messageId: "sections", data: { found } });

new RuleTester().run("arrange-act-assert", plugin.rules["arrange-act-assert"], {
  valid: [
    `it("marks all three sections", async () => {
      // Arrange
      const user = userEvent.setup();

      // Act
      await user.click(button());

      // Assert
      expect(dialog()).toBeVisible();
    });`,
    `test("leaves out arrange when it sets nothing up", () => {
      // Act
      const slug = slugify("Hello World");

      // Assert
      expect(slug).toBe("hello-world");
    });`,
    `it.each([1, 2])("combines sections that can't be split", (number) => {
      // Act & Assert
      expect(() => parse(number)).toThrow();
    });`,
    `it.skip("combines all three sections", function () {
      // Arrange & Act & Assert
      expect(true).toBe(true);
    });`,
    `it("marks a concise arrow function", () =>
      // Act & Assert
      expect(1).toBe(1));`,
    `describe("a group", () => {
      it("finds markers inside nested blocks and keeps other comments", () => {
        // Arrange
        // An ordinary comment under a marker.
        const items = [1, 2];

        // Act & Assert
        for (const item of items) {
          expect(item).toBeGreaterThan(0);
        }
      });
    });`,
    `it.todo("has no callback yet");`,
    `beforeEach(() => { render(); });`,
    `if (/x/.test(value)) { run(() => {}); }`,
  ],
  invalid: [
    {
      code: `it("has no markers", () => { expect(1).toBe(1); });`,
      errors: [sections("none")],
    },
    {
      code: `it("has no assert", () => {
        // Arrange
        const user = userEvent.setup();

        // Act
        user.click(button());
      });`,
      errors: [sections("// Arrange, // Act")],
    },
    {
      code: `test("marks its sections out of order", () => {
        // Act
        const result = run();

        // Arrange
        const other = 1;

        // Assert
        expect(result).toBe(other);
      });`,
      errors: [sections("// Act, // Arrange, // Assert")],
    },
    {
      code: `it("marks a section twice", () => {
        // Act
        run();

        // Act
        run();

        // Assert
        expect(count()).toBe(2);
      });`,
      errors: [sections("// Act, // Act, // Assert")],
    },
    {
      code: `it("combines sections that skip one", () => {
        // Arrange & Assert
        expect(1).toBe(1);
      });`,
      errors: [sections("// Arrange & Assert")],
    },
    {
      code: `it("spells a marker differently", () => {
        // arrange
        const value = 1;

        // Act and Assert
        expect(value).toBe(1);
      });`,
      errors: [sections("none")],
    },
    {
      code: `it.each([1])("is a parameterized test without markers", (number) => { expect(number).toBe(1); });`,
      errors: [sections("none")],
    },
    {
      code: `it("starts with code outside any section", () => {
        const value = 1;

        // Act
        const result = double(value);

        // Assert
        expect(result).toBe(2);
      });`,
      errors: [{ messageId: "unmarkedStart", data: { marker: "Act" }, line: 2 }],
    },
  ],
});
