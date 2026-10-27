import "../../css/app.css";
import { App, http } from "@inertiajs/react";
import { afterEach, expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import { UnsavedChangesToastHost } from "../Components/UnsavedChangesToast";
import Profile from "./Profile";

const props = {
  errors: {},
  admin: { name: "Ada Lovelace", email: "ada@example.com", avatarUrl: null },
  updateUrl: "/admin/profile",
  avatarUploadUrl: "/admin/profile/avatar",
};

// Without the sidebar layout, which needs the whole panel's props; the toast's host stands in.
function ProfilePage() {
  return (
    <>
      <Profile {...props} />
      <UnsavedChangesToastHost />
    </>
  );
}

function renderProfile() {
  const initialPage = { component: "Profile", url: "/admin/profile", version: null, props };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={ProfilePage}
      resolveComponent={() => ProfilePage}
    />,
  );
}

type HttpResponse = { status: number; data: string; headers: Record<string, string> };

/**
 * Answers the avatar upload with the status and body instead of sending it to a server, and
 * returns the signals that abort each upload. A promised body holds the answer until it resolves;
 * aborting rejects it, like the browser does. Saving the profile gets the page back.
 */
function fakeUpload(status: number, body: unknown) {
  const signals: (AbortSignal | undefined)[] = [];
  vi.spyOn(http, "getClient").mockReturnValue({
    async request({ url, signal }): Promise<HttpResponse> {
      if (url !== props.avatarUploadUrl) {
        const page = { component: "Profile", url: "/admin/profile", version: null, props };
        return { status: 200, data: JSON.stringify(page), headers: { "x-inertia": "true" } };
      }
      signals.push(signal);
      const aborted = new Promise<never>((_, reject) =>
        signal?.addEventListener("abort", () => reject(new DOMException("Aborted", "AbortError"))),
      );
      return { status, data: JSON.stringify(await Promise.race([body, aborted])), headers: {} };
    },
  });
  return signals;
}

afterEach(() => vi.restoreAllMocks());

const pictureInput = () => page.elementLocator(document.getElementById("avatar") as HTMLElement);
const name = () => page.getByRole("textbox", { name: "Name" });
const toast = () => page.getByRole("region", { name: "Unsaved changes" });
const submittedAvatar = () =>
  new FormData(document.querySelector("form") as HTMLFormElement).get("avatar");
const png = new File(["png"], "me.png", { type: "image/png" });

it.each([
  [
    "the server takes no pictures that large",
    413,
    "The picture is larger than the server accepts.",
  ],
  ["the server fails", 500, "The picture couldn't be uploaded. Try again."],
])("says so when %s", async (_, status, message) => {
  fakeUpload(status, { message: "Error" });
  await renderProfile();

  await userEvent.upload(pictureInput(), png);

  await expect.element(page.getByText(message)).toBeVisible();
});

it("doesn't save while the picture uploads", async () => {
  let respond!: (body: unknown) => void;
  fakeUpload(201, new Promise((resolve) => (respond = resolve)));
  await renderProfile();
  await name().fill("Ada King");
  await expect.element(toast()).toBeVisible();

  await userEvent.upload(pictureInput(), png);

  await expect.element(toast().getByRole("button", { name: "Save" })).toBeDisabled();

  respond({ path: "avatars/me.png", url: "/storage/avatars/me.png" });

  await expect.element(toast().getByRole("button", { name: "Save" })).toBeEnabled();
  expect(submittedAvatar()).toBe("avatars/me.png");
});

it("stops the upload when the changes are cancelled, and keeps the picture", async () => {
  let respond!: (body: unknown) => void;
  const signals = fakeUpload(201, new Promise((resolve) => (respond = resolve)));
  await renderProfile();
  await name().fill("Ada King");
  await userEvent.upload(pictureInput(), png);
  await expect.poll(() => signals).toHaveLength(1);

  await toast().getByRole("button", { name: "Cancel" }).click();

  await expect.element(toast()).not.toBeInTheDocument();
  expect(signals[0]?.aborted).toBe(true);

  respond({ path: "avatars/me.png", url: "/storage/avatars/me.png" });

  await new Promise((resolve) => setTimeout(resolve, 50));
  expect(submittedAvatar()).toBe("");
  await expect.element(toast()).not.toBeInTheDocument();
});

it("stops the upload when the page goes away", async () => {
  const signals = fakeUpload(201, new Promise(() => {}));
  const screen = await renderProfile();
  await userEvent.upload(pictureInput(), png);
  await expect.poll(() => signals).toHaveLength(1);

  await screen.unmount();

  expect(signals[0]?.aborted).toBe(true);
});

it("forgets why a picture was rejected once the profile is saved", async () => {
  fakeUpload(422, { message: "Invalid", errors: { avatar: ["The avatar must be square."] } });
  await renderProfile();
  await userEvent.upload(pictureInput(), png);
  await expect.element(page.getByText("The avatar must be square.")).toBeVisible();
  await name().fill("Ada King");

  await toast().getByRole("button", { name: "Save" }).click();

  await expect.element(page.getByText("The avatar must be square.")).not.toBeInTheDocument();
});
