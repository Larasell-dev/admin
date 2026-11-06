import { Form, Link } from "@inertiajs/react";
import { Button, Container, Field, Input, PasswordInput, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useState } from "react";
import { signInTransition } from "../utils/authTransition";

export default function Login({
  loginUrl,
  devLoginUrl,
}: Larasell.Admin.Platform.Auth.Data.LoginPageData) {
  const [devLoginLoading, setDevLoginLoading] = useState(false);

  return (
    <main className="flex min-h-dvh items-center bg-base-canvas">
      <Container>
        <div className="mx-auto w-full max-w-sm">
          <Stack spacing={8}>
            <Stack spacing={2}>
              <h1 className="type-headline-lg text-base-primary">
                <fbt desc="Login page: main heading of the form where an admin signs in to the panel">
                  Sign in
                </fbt>
              </h1>
              <p className="type-body-md text-base-secondary">
                <fbt desc="Login page: text below the 'Sign in' heading that asks the admin for their email and password">
                  Enter your credentials to access the admin panel.
                </fbt>
              </p>
            </Stack>

            <Form
              action={loginUrl}
              method="post"
              resetOnSuccess={["password"]}
              options={{ viewTransition: signInTransition() }}
            >
              {({ errors, processing }) => (
                <Stack spacing={6}>
                  <Stack spacing={5}>
                    <Field
                      label={fbs(
                        "Email",
                        "Login page: label of the field for the admin's email address",
                      )}
                      name="email"
                      error={errors.email}
                    >
                      <Input type="email" autoComplete="username" />
                    </Field>

                    <Field
                      label={fbs(
                        "Password",
                        "Login page: label of the field for the admin's password",
                      )}
                      name="password"
                      error={errors.password}
                    >
                      <PasswordInput autoComplete="current-password" />
                    </Field>
                  </Stack>

                  <Button type="submit" fullWidth loading={processing}>
                    <fbt desc="Login page: button that submits the email and password to sign in">
                      Sign in
                    </fbt>
                  </Button>

                  {devLoginUrl && (
                    <Button
                      variant="secondary"
                      fullWidth
                      loading={devLoginLoading}
                      render={
                        <Link
                          href={devLoginUrl}
                          viewTransition={signInTransition()}
                          onStart={() => setDevLoginLoading(true)}
                          onFinish={() => setDevLoginLoading(false)}
                        />
                      }
                    >
                      <fbt desc="Login page, only on a developer's machine: button that signs in as an admin without a password">
                        Log in as admin
                      </fbt>
                    </Button>
                  )}
                </Stack>
              )}
            </Form>
          </Stack>
        </div>
      </Container>
    </main>
  );
}
