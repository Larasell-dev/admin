import { Form, Link } from "@inertiajs/react";
import { Button, Container, Field, Input, PasswordInput, Stack } from "@larasell-dev/ui";
import { useState } from "react";

export default function Login({ loginUrl, devLoginUrl }: Larasell.Admin.Data.View.LoginPageData) {
  const [devLoginLoading, setDevLoginLoading] = useState(false);

  return (
    <main className="flex min-h-dvh items-center bg-white dark:bg-mauve-950">
      <Container>
        <div className="mx-auto w-full max-w-sm">
          <Stack spacing={8}>
            <Stack spacing={2}>
              <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">Sign in</h1>
              <p className="type-body-md text-mauve-600 dark:text-mauve-400">
                Enter your credentials to access the admin panel.
              </p>
            </Stack>

            <Form action={loginUrl} method="post" resetOnSuccess={["password"]}>
              {({ errors, processing }) => (
                <Stack spacing={6}>
                  <Stack spacing={5}>
                    <Field label="Email" name="email" error={errors.email}>
                      <Input type="email" autoComplete="username" />
                    </Field>

                    <Field label="Password" name="password" error={errors.password}>
                      <PasswordInput autoComplete="current-password" />
                    </Field>
                  </Stack>

                  <Button type="submit" fullWidth loading={processing}>
                    Sign in
                  </Button>

                  {devLoginUrl && (
                    <Button
                      variant="secondary"
                      fullWidth
                      loading={devLoginLoading}
                      render={
                        <Link
                          href={devLoginUrl}
                          onStart={() => setDevLoginLoading(true)}
                          onFinish={() => setDevLoginLoading(false)}
                        />
                      }
                    >
                      Log in as admin
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
