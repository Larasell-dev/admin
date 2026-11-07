import { Link, router, useHttp, usePage } from "@inertiajs/react";
import { AlertDialog, Button, Field, PasswordInput } from "@larasell-dev/ui";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { useEffect, useState, type FormEvent } from "react";
import { assertPresent } from "ts-extras";
import type { Page } from "../../../../types";
import { type Cookies, useCookies } from "../../../Support/cookies";
import { overlayExclusive } from "../../../Support/hotkeys";
import { useInterval } from "../../../Support/useInterval";
import { useOnPageVisible } from "../../../Support/useOnPageVisible";
import { fbs } from "fbtee";

/** How often to check whether the session is still alive. */
const checkInterval = 5_000;

/**
 * Laravel sets the XSRF-TOKEN cookie on every response with the session's lifetime, so it is
 * gone exactly when the session expired from inactivity.
 */
function sessionAlive(cookies: Cookies): boolean {
  return cookies.get("XSRF-TOKEN") !== undefined;
}

/**
 * Once the session expires, asks the admin for their password to sign back in without leaving
 * the page, so what they were doing, such as unsaved changes, is kept.
 */
export default function SessionExpiredDialog() {
  const { admin, loginUrl } = usePage<Page>().props;
  assertPresent(admin, "The session expired dialog needs a signed-in admin.");
  const cookies = useCookies();
  // Kept after closing, so the popup can animate out instead of unmounting with its content.
  // The key changes each time the session expires, so the form starts empty again.
  const [session, setSession] = useState<{ expired: boolean; key: number } | null>(null);
  const expired = session?.expired ?? false;
  const expire = () =>
    setSession((current) =>
      current?.expired ? current : { expired: true, key: (current?.key ?? 0) + 1 },
    );

  const check = () => {
    if (!sessionAlive(cookies)) expire();
  };
  useInterval(check, checkInterval);
  // Timers are throttled in background tabs, so check right away when the admin comes back.
  useOnPageVisible(check);

  // A visit would land on the sign-in page, or fail if it saves something, so hold it back,
  // except the dialog's own visit to the sign-in page.
  useEffect(() => {
    return router.on("before", (event) => {
      const { url, method } = event.detail.visit;
      if (
        sessionAlive(cookies) ||
        (method === "get" && url.pathname === new URL(loginUrl, location.href).pathname)
      ) {
        return;
      }
      event.preventDefault();
      expire();
    });
  }, [cookies, loginUrl]);

  return (
    // Can't be dismissed: the only ways on are signing back in or going to the sign-in page.
    <AlertDialog.Root open={expired}>
      <HotkeysProvider
        scope="session-expired-dialog"
        exclusive={overlayExclusive}
        enabled={expired}
      >
        {session && (
          <SignInAgain
            key={session.key}
            email={admin.email}
            loginUrl={loginUrl}
            onSignedIn={() => setSession({ ...session, expired: false })}
          />
        )}
      </HotkeysProvider>
    </AlertDialog.Root>
  );
}

function SignInAgain({
  email,
  loginUrl,
  onSignedIn,
}: {
  email: string;
  loginUrl: string;
  onSignedIn: () => void;
}) {
  const form = useHttp({ email, password: "" });
  const cookies = useCookies();

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const failed = (
      message = fbs(
        "Couldn't sign you in. Try again.",
        "Session expired dialog: error below the password field when signing back in failed for a reason other than a wrong password",
      ),
    ) => form.setError("password", message);

    try {
      // Without the cookie there is no CSRF token to sign in with; any request starts a new
      // session that hands one out.
      if (!sessionAlive(cookies)) await fetch(loginUrl, { method: "HEAD" });
    } catch {
      return failed();
    }

    // Wrong passwords come back as validation errors. Other failures reject after their callback
    // told the admin, and stay in the console.
    await form.post(loginUrl, {
      onSuccess: onSignedIn,
      onHttpException: ({ status }) =>
        failed(
          status === 429
            ? fbs(
                "Too many attempts. Try again in a minute.",
                "Session expired dialog: error below the password field after too many sign-in attempts",
              )
            : undefined,
        ),
      onNetworkError: () => failed(),
    });
  }

  return (
    <AlertDialog.Popup
      title={fbs(
        "Your session expired",
        "Session expired dialog: title of the dialog that asks the admin for their password after they were signed out for inactivity",
      )}
      description={fbs(
        [
          "You were signed out after a while without activity. Enter the password for ",
          fbs.param("email", email),
          " to continue where you left off.",
        ],
        "Session expired dialog: text below the title, with the signed-in admin's email address",
      )}
    >
      <form onSubmit={submit} className="contents">
        <Field
          label={fbs(
            "Password",
            "Session expired dialog: label of the field for the admin's password to sign back in",
          )}
          name="password"
          error={form.errors.password ?? form.errors.email}
        >
          <PasswordInput
            autoComplete="current-password"
            autoFocus
            value={form.data.password}
            onChange={(event) => form.setData("password", event.currentTarget.value)}
          />
        </Field>
        <AlertDialog.Actions>
          <Button variant="secondary" render={<Link href={loginUrl} />}>
            <fbt desc="Session expired dialog: button that goes to the sign-in page to sign in as a different admin">
              Use another account
            </fbt>
          </Button>
          <AlertDialog.Confirm type="submit" loading={form.processing}>
            <fbt desc="Session expired dialog: submit button that signs back in with the entered password and returns to the page">
              Continue
            </fbt>
          </AlertDialog.Confirm>
        </AlertDialog.Actions>
      </form>
    </AlertDialog.Popup>
  );
}
