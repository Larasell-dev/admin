import "../../css/app.css";
import { App, Form, router } from "@inertiajs/react";
import { Component, type ReactNode } from "react";
import { expect, it } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import UnsavedChangesToast, { UnsavedChangesToastHost } from "./UnsavedChangesToast";

function EditPage() {
  return (
    <>
      <Form action="/admin/things/1" method="put">
        <input name="name" aria-label="Name" defaultValue="Shoes" />
        <UnsavedChangesToast />
      </Form>
      {/* SidebarLayout renders the host in the panel. */}
      <UnsavedChangesToastHost />
    </>
  );
}

// Renders through Inertia's App, so the router knows the current page like in the panel.
function renderPage() {
  const initialPage = {
    component: "EditPage",
    url: "/admin/things/1",
    version: null,
    props: {},
  };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={EditPage}
      resolveComponent={() => EditPage}
    />,
  );
}

// Whether the toast cancelled the visit. The listener is added just before the visit, so it runs
// after the toast's own, and then it stops the visit before any request is sent.
function wasCancelled(visit: () => void): boolean {
  let cancelled = false;
  const record = (event: Event) => {
    cancelled = event.defaultPrevented;
    event.preventDefault();
  };
  document.addEventListener("inertia:before", record);
  try {
    visit();
  } finally {
    document.removeEventListener("inertia:before", record);
  }
  return cancelled;
}

const toast = () => page.getByRole("region", { name: "Unsaved changes" });
const name = () => page.getByLabelText("Name");

it("only appears once the form has unsaved changes", async () => {
  await renderPage();
  await expect.element(toast()).not.toBeInTheDocument();

  await name().fill("Boots");
  await expect.element(toast()).toBeVisible();

  await name().fill("Shoes");
  await expect.element(toast()).not.toBeInTheDocument();
});

it("resets the whole form on cancel", async () => {
  await renderPage();
  await name().fill("Boots");

  await toast().getByRole("button", { name: "Cancel" }).click();

  await expect.element(name()).toHaveValue("Shoes");
  await expect.element(toast()).not.toBeInTheDocument();
});

it("blocks navigating away only while the form has unsaved changes", async () => {
  await renderPage();
  expect(wasCancelled(() => router.visit("/admin/elsewhere"))).toBe(false);

  await name().fill("Boots");
  await expect.element(toast()).toBeVisible();
  // The toast starts blocking in an effect, just after it appears.
  await expect.poll(() => wasCancelled(() => router.visit("/admin/elsewhere"))).toBe(true);
});

it("still prefetches links while the form has unsaved changes", async () => {
  await renderPage();
  await name().fill("Boots");
  await expect.element(toast()).toBeVisible();

  expect(wasCancelled(() => router.prefetch("/admin/elsewhere"))).toBe(false);
});

it("lets its save button submit the form", async () => {
  await renderPage();
  await name().fill("Boots");
  await expect.element(toast()).toBeVisible();
  const save = toast().getByRole("button", { name: "Save" }).element() as HTMLButtonElement;

  expect(wasCancelled(() => save.click())).toBe(false);
});

it("doesn't save while anything in the form is busy, like an upload", async () => {
  await renderPage();
  await name().fill("Boots");
  const save = toast().getByRole("button", { name: "Save" });
  await expect.element(save).toBeEnabled();
  const upload = document.createElement("div");
  upload.setAttribute("aria-busy", "true");

  name().element().after(upload);

  await expect.element(save).toBeDisabled();

  upload.setAttribute("aria-busy", "false");

  await expect.element(save).toBeEnabled();
});

it("asks before leaving the site only while the form has unsaved changes", async () => {
  await renderPage();
  const unload = () => {
    const event = new Event("beforeunload", { cancelable: true });
    window.dispatchEvent(event);
    return event.defaultPrevented;
  };

  expect(unload()).toBe(false);
  await name().fill("Boots");
  await expect.element(toast()).toBeVisible();
  await expect.poll(unload).toBe(true);
});

class Boundary extends Component<{ children: ReactNode }, { error: string | null }> {
  state = { error: null };
  static getDerivedStateFromError(error: Error) {
    return { error: error.message };
  }
  render() {
    return this.state.error ?? this.props.children;
  }
}

it("throws outside an Inertia form", async () => {
  await render(
    <Boundary>
      <UnsavedChangesToast />
    </Boundary>,
  );

  await expect
    .element(page.getByText("UnsavedChangesToast must be rendered inside an Inertia <Form>."))
    .toBeInTheDocument();
});
